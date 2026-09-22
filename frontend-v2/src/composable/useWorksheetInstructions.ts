import { onMounted, onUnmounted, ref } from 'vue'
import { useApi } from '/@src/composable/useApi'

export interface WorksheetInstruction {
  value: number
  label: string
  number: string
  active: boolean
  fileCount: number
  disabled: boolean
  reason: string
}

const changeEvent = 'worksheet-instructions-changed'
const options = ref<WorksheetInstruction[]>([])
const loading = ref(false)
const error = ref('')
let subscribers = 0
let pending: Promise<void> | null = null
let generation = 0
let timer: ReturnType<typeof setInterval> | undefined
let removeListeners: (() => void) | undefined

// Notify the open worksheet, including worksheets in other tabs.
export function notifyWorksheetInstructionsChanged() {
  window.dispatchEvent(new Event(changeEvent))
  try {
    localStorage.setItem(changeEvent, String(Date.now()))
  } catch { /* Same-tab refresh still works when browser storage is unavailable. */ }
}

export function useWorksheetInstructions() {
  const api = useApi()

  async function refresh(invalidate = false): Promise<void> {
    if (pending && !invalidate) return pending
    const requestGeneration = ++generation
    loading.value = true
    pending = (async () => {
      try {
        const rows = await api.get(`/sysadmin/worksheet-instructions?_refresh=${Date.now()}`, { timeout: 15000 })
        if (!Array.isArray(rows)) throw new Error('Invalid instruction response')
        if (requestGeneration === generation) {
          options.value = rows
          error.value = ''
        }
      } catch {
        if (requestGeneration === generation) {
          error.value = 'Status file IK belum dapat diperiksa. Klik Perbarui daftar untuk mencoba lagi.'
        }
      } finally {
        if (requestGeneration === generation) {
          loading.value = false
          pending = null
        }
      }
    })()
    return pending
  }

  const onChange = () => { void refresh(true) }
  const onFocus = () => { if (!document.hidden) void refresh(true) }
  const onStorage = (event: StorageEvent) => { if (event.key === changeEvent) onChange() }

  onMounted(() => {
    if (++subscribers === 1) {
      options.value = []
      error.value = ''
      window.addEventListener(changeEvent, onChange)
      window.addEventListener('focus', onFocus)
      window.addEventListener('storage', onStorage)
      document.addEventListener('visibilitychange', onFocus)
      removeListeners = () => {
        window.removeEventListener(changeEvent, onChange)
        window.removeEventListener('focus', onFocus)
        window.removeEventListener('storage', onStorage)
        document.removeEventListener('visibilitychange', onFocus)
      }
      timer = setInterval(() => { if (!document.hidden) void refresh() }, 15000)
    }
    void refresh()
  })

  // Every instance registers its cleanup, but the listeners belong to the shared store.
  onUnmounted(() => {
    if (--subscribers === 0) {
      clearInterval(timer)
      removeListeners?.()
      removeListeners = undefined
      generation++
      pending = null
      loading.value = false
      options.value = []
    }
  })

  return { options, loading, error, refresh }
}
