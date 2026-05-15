import { onBeforeUnmount, onMounted } from 'vue';

// Fullpage scroll for the homepage: one wheel notch / one keystroke = one
// section jump. No partial scrolling. Mobile and reduced-motion fall back to
// native scroll (CSS scroll-snap still applies if present).

const BREAKPOINT = '(min-width: 768px)';
const REDUCED_MOTION = '(prefers-reduced-motion: reduce)';
const SECTIONS_SELECTOR = '.home > section, .site-footer';
const WHEEL_THRESHOLD = 30;
const LOCK_MS = 800;
const WHEEL_RESET_MS = 200;
// Beyond this many consecutive transitions (same direction, within the streak
// window), each new transition jumps 2 sections instead of 1.
const STREAK_BOOST_AFTER = 3;
const STREAK_WINDOW_MS = 1500;

export function useFullpageScroll(): void {
  let sections: HTMLElement[] = [];
  let locked = false;
  let lockTimer: number | null = null;
  let wheelAccumulator = 0;
  let wheelResetTimer: number | null = null;
  let mql: MediaQueryList | null = null;
  let attached = false;
  let streak = 0;
  let streakDir = 0;
  let lastIntentAt = 0;
  let pendingDir = 0;

  function isInsideScrollable(target: EventTarget | null): boolean {
    let el = target instanceof Element ? target : null;
    while (el && el !== document.body) {
      const cs = window.getComputedStyle(el);
      if (
        (cs.overflowY === 'auto' || cs.overflowY === 'scroll') &&
        el.scrollHeight > el.clientHeight
      ) {
        return true;
      }
      el = el.parentElement;
    }
    return false;
  }

  function isBodyLocked(): boolean {
    return window.getComputedStyle(document.body).overflow === 'hidden';
  }

  function refreshSections(): void {
    sections = Array.from(document.querySelectorAll<HTMLElement>(SECTIONS_SELECTOR));
  }

  function currentIndex(): number {
    // Bias toward the section whose top is just above the current scrollY:
    // when sitting exactly between two, picking the upper one matches what
    // the user "is on" and the next/previous arithmetic stays intuitive.
    const y = window.scrollY + 2;
    let best = 0;
    let bestDist = Infinity;
    for (let i = 0; i < sections.length; i++) {
      const dist = Math.abs(sections[i].offsetTop - y);
      if (dist < bestDist) {
        bestDist = dist;
        best = i;
      }
    }
    return best;
  }

  function lock(): void {
    locked = true;
    if (lockTimer) window.clearTimeout(lockTimer);
    lockTimer = window.setTimeout(() => {
      locked = false;
      wheelAccumulator = 0;
      // Si l'utilisateur a continué à scroller pendant le lock, l'intention
      // a été enregistrée — on la rejoue immédiatement avec le streak boosté.
      if (pendingDir !== 0) {
        const d = pendingDir;
        pendingDir = 0;
        // Intention déjà comptée quand l'utilisateur a scrollé pendant le lock.
        move(d, false);
      }
    }, LOCK_MS);
  }

  function goTo(index: number): void {
    if (index < 0 || index >= sections.length) return;
    lock();
    sections[index].scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  // Une intention = un seuil molette franchi ou une touche pressée. On la
  // compte même pendant le lock, c'est ce qui fait gonfler le streak quand
  // l'utilisateur scroll vite sans attendre la fin des transitions.
  function registerIntent(dir: number): void {
    const now = performance.now();
    if (dir === streakDir && now - lastIntentAt <= STREAK_WINDOW_MS) {
      streak += 1;
    } else {
      streak = 1;
      streakDir = dir;
    }
    lastIntentAt = now;
  }

  function move(dir: number, registerNew = true): void {
    if (registerNew) registerIntent(dir);
    if (locked) {
      pendingDir = dir;
      return;
    }
    const stepSize = streak > STREAK_BOOST_AFTER ? 2 : 1;
    const target = currentIndex() + dir * stepSize;
    const clamped = Math.max(0, Math.min(sections.length - 1, target));
    goTo(clamped);
  }

  function onWheel(e: WheelEvent): void {
    if (isBodyLocked()) return;
    if (isInsideScrollable(e.target)) return;
    e.preventDefault();
    // Pas de early return sur `locked` : on accumule quand même pour que les
    // scrolls pendant la transition fassent grossir le streak.

    wheelAccumulator += e.deltaY;
    if (wheelResetTimer) window.clearTimeout(wheelResetTimer);
    wheelResetTimer = window.setTimeout(() => {
      wheelAccumulator = 0;
    }, WHEEL_RESET_MS);

    if (Math.abs(wheelAccumulator) < WHEEL_THRESHOLD) return;
    const dir = wheelAccumulator > 0 ? 1 : -1;
    wheelAccumulator = 0;
    move(dir);
  }

  function onKey(e: KeyboardEvent): void {
    if (isBodyLocked()) return;

    const t = e.target;
    if (t instanceof HTMLElement) {
      const tag = t.tagName;
      if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || t.isContentEditable) {
        return;
      }
    }
    if (isInsideScrollable(t)) return;

    let dir = 0;
    switch (e.key) {
      case 'ArrowDown':
      case 'PageDown':
        dir = 1;
        break;
      case 'ArrowUp':
      case 'PageUp':
        dir = -1;
        break;
      case ' ':
        dir = e.shiftKey ? -1 : 1;
        break;
      case 'Home':
        e.preventDefault();
        goTo(0);
        return;
      case 'End':
        e.preventDefault();
        goTo(sections.length - 1);
        return;
      default:
        return;
    }
    e.preventDefault();
    move(dir);
  }

  function attach(): void {
    if (attached) return;
    refreshSections();
    if (sections.length === 0) return;
    window.addEventListener('wheel', onWheel, { passive: false });
    window.addEventListener('keydown', onKey);
    attached = true;
  }

  function detach(): void {
    if (!attached) return;
    window.removeEventListener('wheel', onWheel);
    window.removeEventListener('keydown', onKey);
    attached = false;
  }

  function evaluate(): void {
    const wide = mql ? mql.matches : window.matchMedia(BREAKPOINT).matches;
    const reduced = window.matchMedia(REDUCED_MOTION).matches;
    if (wide && !reduced) {
      attach();
    } else {
      detach();
    }
  }

  onMounted(() => {
    mql = window.matchMedia(BREAKPOINT);
    mql.addEventListener('change', evaluate);
    // Refresh on layout shifts (e.g., packages loading injects DOM).
    const obs = new MutationObserver(() => {
      if (attached) refreshSections();
    });
    obs.observe(document.body, { childList: true, subtree: true });
    evaluate();

    onBeforeUnmount(() => {
      obs.disconnect();
    });
  });

  onBeforeUnmount(() => {
    if (mql) mql.removeEventListener('change', evaluate);
    detach();
    if (lockTimer) window.clearTimeout(lockTimer);
    if (wheelResetTimer) window.clearTimeout(wheelResetTimer);
  });
}
