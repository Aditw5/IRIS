import { computed, reactive } from 'vue'
import { createSharedComposable, useCssVar } from '@vueuse/core'
import { HSLToHex } from '/@src/utils/color-converter'

export const useThemeColors = createSharedComposable(() => {
  const primary = useCssVar('--primary', document.documentElement)
  const success = useCssVar('--success', document.documentElement)
  const info = useCssVar('--info', document.documentElement)
  const warning = useCssVar('--warning', document.documentElement)
  const danger = useCssVar('--danger', document.documentElement)
  const purple = useCssVar('--purple', document.documentElement)
  const blue = useCssVar('--blue', document.documentElement)
  const green = useCssVar('--green', document.documentElement)
  const yellow = useCssVar('--yellow', document.documentElement)
  const orange = useCssVar('--orange', document.documentElement)

  const themeColors = reactive({
    primary: 'hsl( 159 85% 43%)',
    success: 'rgb(6, 214, 158)',//computed(() => HSLToHex(success.value)),
    info: 'rgb(3, 135, 201)',
    // warning: computed(() => HSLToHex(warning.value)),
    warning: 'hsl( 35 95% 62%)',
    danger: computed(() => HSLToHex(danger.value)),
    // purple: computed(() => HSLToHex(purple.value)),
    // blue: computed(() => HSLToHex(blue.value)),
    // green: computed(() => HSLToHex(green.value)),
    // yellow: computed(() => HSLToHex(yellow.value)),
    // orange: computed(() => HSLToHex(orange.value)),
    purple: 'hsl(261, 32%, 55%)',
    blue: 'hsl( 198 100% 61%)',
    green: 'hsl( 113 59% 71%)',
    yellow: 'hsl( 43 100% 72%)',
    orange: 'hsl( 19 100% 75%)',
    lightText: '#a2a5b9',
    fadeGrey: '#ededed',
    primaryMedium: '#b4e4ce',
    primaryLight: '#f7fcfa',
    secondary: '#ff227d',
    accent: '#797bf2',
    accentMedium: '#d4b3ff',
    accentLight: '#b8ccff',
  } as const)

  return themeColors
})
