<script setup lang="ts">
defineProps<{
  initials?: string;
  ariaLabel?: string;
}>();
</script>

<template>
  <svg
    class="ui-monogram"
    viewBox="0 0 400 500"
    role="img"
    :aria-label="ariaLabel ?? 'Monogramme Matthieu Rivière'"
    xmlns="http://www.w3.org/2000/svg"
    preserveAspectRatio="xMidYMid slice"
  >
    <defs>
      <linearGradient id="ui-monogram-bg" x1="0%" y1="0%" x2="100%" y2="100%">
        <stop offset="0%" stop-color="var(--color-accent)" />
        <stop offset="100%" stop-color="var(--color-accent-2)" />
      </linearGradient>
      <linearGradient id="ui-monogram-glow" x1="50%" y1="0%" x2="50%" y2="100%">
        <stop offset="0%" stop-color="rgba(255,255,255,0.18)" />
        <stop offset="60%" stop-color="rgba(255,255,255,0)" />
      </linearGradient>
      <filter id="ui-monogram-grain">
        <feTurbulence type="fractalNoise" baseFrequency="0.9" numOctaves="2" seed="3" />
        <feColorMatrix values="0 0 0 0 1  0 0 0 0 1  0 0 0 0 1  0 0 0 0.18 0" />
      </filter>
    </defs>

    <rect width="400" height="500" fill="url(#ui-monogram-bg)" />
    <rect width="400" height="500" fill="url(#ui-monogram-glow)" />

    <g stroke="rgba(255,255,255,0.16)" stroke-width="1" fill="none">
      <polygon points="200,80 320,150 320,290 200,360 80,290 80,150" />
      <polygon points="200,130 280,175 280,265 200,310 120,265 120,175" />
    </g>

    <g
      font-family="var(--font-display)"
      font-weight="600"
      letter-spacing="-6"
      text-anchor="middle"
      fill="rgba(255,255,255,0.96)"
    >
      <text x="200" y="265" font-size="180">{{ initials ?? 'MR' }}</text>
    </g>

    <text
      x="200"
      y="430"
      text-anchor="middle"
      font-family="var(--font-mono)"
      font-size="14"
      letter-spacing="6"
      fill="rgba(255,255,255,0.7)"
    >
      STUDIO · QUÉBEC
    </text>

    <rect width="400" height="500" filter="url(#ui-monogram-grain)" opacity="0.55" />
  </svg>
</template>

<style lang="scss" scoped>
.ui-monogram {
  display: block;
  width: 100%;
  height: 100%;
  border-radius: inherit;
}
</style>
