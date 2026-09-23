<script setup lang="ts">
withDefaults(defineProps<{ name?: string; size?: number }>(), { name: 'arrow', size: 24 })
const paths: Record<string, string> = {
  arrow: 'M4 12h16m-6-6 6 6-6 6',
  diagonal: 'M6 18 18 6M6 6h12v12',
  check: 'm5 12 4 4L19 6',
  shield: 'M12 3 4 6v6c0 5 8 9 8 9s8-4 8-9V6l-8-3Zm-4 9 3 3 5-6',
  calibration:
    'M12 3v3m0 12v3M3 12h3m12 0h3M8 12a4 4 0 1 0 8 0 4 4 0 0 0-8 0ZM5 5l2 2m10 10 2 2M5 19l2-2M17 7l2-2',
  repair: 'm14 6 4 4 3-3a7 7 0 0 1-9 9l-6 6-4-4 6-6a7 7 0 0 1 9-9l-3 3Z',
  cloud: 'M6 18a5 5 0 0 1-1-10 7 7 0 0 1 13-1 5.5 5.5 0 0 1 0 11M12 12v9m-3-6 3-3 3 3',
  chart: 'M4 3v17h17M8 15l4-5 4 2 5-7',
  layers: 'm12 3 10 5-10 5L2 8l10-5ZM2 12l10 5 10-5M2 16l10 5 10-5',
  file: 'M14 2H5v20h14V7l-5-5Zm0 0v6h5M8 12h8m-8 4h6',
  bell: 'M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM9 21h6',
  lock: 'M7 11V7a5 5 0 0 1 10 0v4M5 11h14v10H5V11Zm7 4v2',
  globe: 'M3 12h18M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Zm0 0c5 5 5 13 0 18-5-5-5-13 0-18Z',
  grid: 'M3 3h7v7H3V3Zm11 0h7v7h-7V3ZM3 14h7v7H3v-7Zm11 0h7v7h-7v-7Z',
  user: 'M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0ZM4 21v-2a8 8 0 0 1 16 0v2',
  play: 'm9 5 11 7-11 7V5Z',
  pause: 'M9 5v14m6-14v14',
  menu: 'M4 7h16M4 12h16M4 17h16',
  close: 'm6 6 12 12M6 18 18 6',
  down: 'M12 4v16m-6-6 6 6 6-6',
  search: 'm16 16 5 5M3 10a7 7 0 1 0 14 0 7 7 0 0 0-14 0Z',
  activity: 'M2 12h5l3-8 4 16 3-8h5',
  folder: 'M3 5h7l2 3h9v12H3V5Z',
  link: 'm10 14 4-4M8 16l-2 2a3 3 0 0 1-4-4l5-5a3 3 0 0 1 4 0m2 6a3 3 0 0 0 4 0l5-5a3 3 0 0 0-4-4l-2 2',
}
</script>

<template>
  <svg
    :width="size"
    :height="size"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.65"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
  >
    <path :d="paths[name] || paths.arrow" />
  </svg>
</template>
