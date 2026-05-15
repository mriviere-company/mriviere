import { ref, watch, onMounted } from 'vue';

type Theme = 'dark' | 'light';

const STORAGE_KEY = 'mriviere.theme';
const theme = ref<Theme>('dark');

function applyTheme(value: Theme) {
  document.documentElement.dataset.theme = value;
}

export function useTheme() {
  onMounted(() => {
    const stored = localStorage.getItem(STORAGE_KEY) as Theme | null;
    if (stored === 'dark' || stored === 'light') {
      theme.value = stored;
    } else if (window.matchMedia('(prefers-color-scheme: light)').matches) {
      theme.value = 'light';
    }
    applyTheme(theme.value);
  });

  watch(theme, (next) => {
    applyTheme(next);
    try {
      localStorage.setItem(STORAGE_KEY, next);
    } catch {
      /* localStorage may be unavailable */
    }
  });

  function toggle() {
    theme.value = theme.value === 'dark' ? 'light' : 'dark';
  }

  function set(value: Theme) {
    theme.value = value;
  }

  return { theme, toggle, set };
}
