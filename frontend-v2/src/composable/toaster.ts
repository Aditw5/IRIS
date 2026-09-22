import { useToast } from 'primevue/usetoast'
import { createSharedComposable } from '@vueuse/core'
type typeNotify =
    | 'success'
    | 'error'
    | 'info'
    | 'warn'

export const useToaster = createSharedComposable(() => {
    const toast = useToast()
    return {
        success: (message: any, title: any = null, duration: any = 3000) => {
            title = title ? title : 'Info'
            return toast.add({ severity: 'success', summary: title, detail: message, life: duration, group: 'br' })
        },
        error: (message: any, title: any = null, duration: any = 3000) => {
            title = title ? title : 'Info'
            return toast.add({ severity: 'error', summary: title, detail: message, life: duration, group: 'br' })
        },
        info: (message: any, title: any = null, duration: any = 3000) => {
            title = title ? title : 'Info'
            return toast.add({ severity: 'info', summary: title, detail: message, life: duration, group: 'br' })
        },
        warn: (message: any, title: any = null, duration: any = 3000) => {
            title = title ? title : 'Info'
            return toast.add({ severity: 'warn', summary: title, detail: message, life: duration, group: 'br' })
        },
        dismissAll: () => {
          toast.removeAllGroups()
        },
    }
})
