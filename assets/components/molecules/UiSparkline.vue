<script setup lang="ts">
import { computed } from 'vue';

interface SeriesPoint {
  label: string;
  value: number;
}

const props = withDefaults(defineProps<{
  series: SeriesPoint[];
  height?: number;
  ariaLabel?: string;
}>(), {
  height: 120,
});

const VBOX_W = 600;
const PAD_X = 8;
const PAD_Y = 16;

const path = computed(() => {
  const series = props.series;
  if (series.length === 0) return '';
  const max = Math.max(1, ...series.map((p) => p.value));
  const stepX = (VBOX_W - PAD_X * 2) / Math.max(1, series.length - 1);
  return series.map((p, i) => {
    const x = PAD_X + i * stepX;
    const y = PAD_Y + (props.height - PAD_Y * 2) * (1 - p.value / max);
    return `${i === 0 ? 'M' : 'L'} ${x.toFixed(1)} ${y.toFixed(1)}`;
  }).join(' ');
});

const fillPath = computed(() => {
  const series = props.series;
  if (series.length === 0) return '';
  const max = Math.max(1, ...series.map((p) => p.value));
  const stepX = (VBOX_W - PAD_X * 2) / Math.max(1, series.length - 1);
  let d = `M ${PAD_X} ${props.height - PAD_Y}`;
  series.forEach((p, i) => {
    const x = PAD_X + i * stepX;
    const y = PAD_Y + (props.height - PAD_Y * 2) * (1 - p.value / max);
    d += ` L ${x.toFixed(1)} ${y.toFixed(1)}`;
  });
  d += ` L ${PAD_X + (series.length - 1) * stepX} ${props.height - PAD_Y} Z`;
  return d;
});

const peakIndex = computed(() => {
  let idx = 0;
  let max = -Infinity;
  props.series.forEach((p, i) => {
    if (p.value > max) {
      max = p.value;
      idx = i;
    }
  });
  return idx;
});

const peakLabel = computed(() => props.series[peakIndex.value]);
</script>

<template>
  <div class="ui-sparkline">
    <svg
      :viewBox="`0 0 ${VBOX_W} ${height}`"
      :height="height"
      role="img"
      :aria-label="ariaLabel ?? 'Timeseries'"
      preserveAspectRatio="none"
    >
      <defs>
        <linearGradient id="sparkline-fill" x1="0%" y1="0%" x2="0%" y2="100%">
          <stop offset="0%" stop-color="var(--color-accent)" stop-opacity="0.35" />
          <stop offset="100%" stop-color="var(--color-accent)" stop-opacity="0" />
        </linearGradient>
      </defs>
      <path :d="fillPath" fill="url(#sparkline-fill)" />
      <path :d="path" fill="none" stroke="var(--color-accent-2)" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />
    </svg>
    <p v-if="peakLabel" class="ui-sparkline__peak">
      Pic : <strong>{{ peakLabel.value }}</strong> · {{ peakLabel.label }}
    </p>
  </div>
</template>

<style lang="scss" scoped>
.ui-sparkline {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
  width: 100%;

  svg {
    width: 100%;
    display: block;
    border-radius: var(--radius-sm);
    background: rgba(255, 255, 255, 0.02);
  }

  &__peak {
    margin: 0;
    font-family: var(--font-mono);
    font-size: var(--fs-xs);
    color: var(--color-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.08em;

    strong {
      color: var(--color-accent-2);
      font-variant-numeric: tabular-nums;
    }
  }
}
</style>
