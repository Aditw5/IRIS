<template>
    <div>
        <Dialog v-model:visible="isOpen" header="Sesi Login Berakhir" :style="{ width: '30rem' }" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }" position="center" :modal="true" :draggable="false" :closable="false">
            <div class="flex" style="align-items: center;">
                <i class="iconify" data-icon="feather:alert-circle" aria-hidden="true" style="color: green;font-size: 4rem;margin-right: 10px;"></i>
                <p class="m-0">
                    {{ message }}
                </p>
            </div>
        </Dialog>
    </div>
</template>
  
<script setup lang="ts">
import { useUserSession } from '/@src/stores/userSession'
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import Dialog from 'primevue/dialog';

const router = useRouter()
const userSession = useUserSession()
const isOpen = ref(false)
const message = ref('')

const LAST_ACTIVITY_KEY = 'ulab_last_activity_at'
const AUTO_LOGOUT_EVENT_KEY = 'ulab_auto_logout_at'
const INACTIVITY_TIMEOUT = 30 * 60 * 1000
const CHECK_INTERVAL = 15 * 1000
const ACTIVITY_THROTTLE = 1000
const COUNTDOWN_SECONDS = 5
const ACTIVITY_EVENTS = ['pointerdown', 'mousemove', 'keydown', 'scroll', 'touchstart', 'click']

let checkInterval: ReturnType<typeof window.setInterval> | undefined
let countdownInterval: ReturnType<typeof window.setInterval> | undefined
let countdown = COUNTDOWN_SECONDS
let lastActivityWrite = 0

const getNow = () => Date.now()

const getLastSharedActivity = () => {
    const value = Number(window.localStorage.getItem(LAST_ACTIVITY_KEY) || 0)
    return Number.isFinite(value) && value > 0 ? value : getNow()
}

const hasTimedOut = () => getNow() - getLastSharedActivity() >= INACTIVITY_TIMEOUT

const closeWarning = () => {
    if (countdownInterval) {
        window.clearInterval(countdownInterval)
        countdownInterval = undefined
    }

    countdown = COUNTDOWN_SECONDS
    isOpen.value = false
}

const rememberActivity = (force = false) => {
    if (!userSession.isLoggedIn) return

    const now = getNow()
    if (!force && now - lastActivityWrite < ACTIVITY_THROTTLE) return

    lastActivityWrite = now
    window.localStorage.setItem(LAST_ACTIVITY_KEY, String(now))
    closeWarning()
}

const redirectToLogin = () => {
    router.replace({
        name: 'auth-login',
    })
}

const logout = () => {
    closeWarning()
    window.localStorage.setItem(AUTO_LOGOUT_EVENT_KEY, String(getNow()))
    userSession.logoutUser()
    redirectToLogin()
}

const updateCountdownMessage = () => {
    message.value = `Anda akan logout otomatis dalam ${countdown} detik karena tidak ada aktivitas.`
}

const showPopUpLogout = () => {
    if (isOpen.value || !userSession.isLoggedIn || !hasTimedOut()) return

    countdown = COUNTDOWN_SECONDS
    updateCountdownMessage()
    isOpen.value = true

    countdownInterval = window.setInterval(() => {
        if (!hasTimedOut()) {
            closeWarning()
            return
        }

        countdown -= 1

        if (countdown <= 0) {
            logout()
            return
        }

        updateCountdownMessage()
    }, 1000)
}

const checkIdle = () => {
    if (!userSession.isLoggedIn) {
        closeWarning()
        return
    }

    if (hasTimedOut()) {
        showPopUpLogout()
    } else {
        closeWarning()
    }
}

const handleActivity = () => {
    rememberActivity()
}

const handleVisibilityChange = () => {
    if (document.visibilityState !== 'visible') return

    checkIdle()
    if (!isOpen.value) {
        rememberActivity(true)
    }
}

const handleStorage = (event: StorageEvent) => {
    if (event.key === LAST_ACTIVITY_KEY && !hasTimedOut()) {
        closeWarning()
        return
    }

    if (event.key === AUTO_LOGOUT_EVENT_KEY && event.newValue) {
        userSession.logoutUser()
        redirectToLogin()
    }
}

onMounted(() => {
    rememberActivity(true)
    ACTIVITY_EVENTS.forEach((eventName) => {
        window.addEventListener(eventName, handleActivity, { passive: true })
    })
    document.addEventListener('visibilitychange', handleVisibilityChange)
    window.addEventListener('storage', handleStorage)
    checkInterval = window.setInterval(checkIdle, CHECK_INTERVAL)
})

onUnmounted(() => {
    closeWarning()
    if (checkInterval) {
        window.clearInterval(checkInterval)
        checkInterval = undefined
    }
    ACTIVITY_EVENTS.forEach((eventName) => {
        window.removeEventListener(eventName, handleActivity)
    })
    document.removeEventListener('visibilitychange', handleVisibilityChange)
    window.removeEventListener('storage', handleStorage)
})
</script>
