<script setup lang="ts">
import { nextTick, onMounted, onBeforeUnmount, reactive, ref, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useHead } from '@vueuse/head'

import { useDarkmode } from '/@src/stores/darkmode'
import { useUserSession } from '/@src/stores/userSession'
import { useToaster } from '/@src/composable/toaster'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'
import { useStorage } from '@vueuse/core'
import Password from 'primevue/password'
import Dialog from 'primevue/dialog'
import * as faceapi from 'face-api.js'

export type UserData = Record<string, any> | null
type StepId = 'login' | 'forgot-password'
type ForgotStep = 'identify' | 'otp' | 'reset'

declare global {
  interface Window {
    turnstile?: {
      render: (container: HTMLElement, options: Record<string, any>) => string
      reset: (widgetId?: string) => void
      remove: (widgetId?: string) => void
    }
    onTurnstileLoad?: () => void
    requestIdleCallback?: (callback: IdleRequestCallback, options?: IdleRequestOptions) => number
  }
}

const TURNSTILE_SRC =
  'https://challenges.cloudflare.com/turnstile/v0/api.js?onload=onTurnstileLoad&render=explicit'

const step = ref<StepId>('login')
const isLoading = ref(false)
const darkmode = useDarkmode()
const router = useRouter()
const route = useRoute()
const notif = useToaster()
const toast = useToast()
const redirect = route.query.redirect as string
const namaUser = ref('')
const kataSandi = ref('')
const captchaToken = ref('')
const captchaWidgetId = ref<string | null>(null)
const captchaContainer = ref<HTMLElement | null>(null)
const captchaRenderError = ref('')
const isCaptchaLoading = ref(true)
const isCaptchaRendered = ref(false)
const turnstileCheckTimer = ref<number | null>(null)
const port = window.location.port
const isDev = port ? true : false
const error = ref('')
const isError = ref(false)
const referenceDescriptor = ref()
const videoRecog = ref(false)
const currentStep = ref(0)
const userSession = useUserSession()
const listMenu = useStorage('list_menu', [])
const LAST_AUTH_ROUTE_KEY = 'ulab_last_auth_route'
const LAST_ACTIVITY_KEY = 'ulab_last_activity_at'
const DEFAULT_AUTH_ROUTE_NAME = 'module-dashboard-registrasi'
listMenu.value = []

const defaultRouteName = (user: any) => {
  const menu = user?.kelompokUser?.menu
  return menu && menu !== 'app' ? menu : DEFAULT_AUTH_ROUTE_NAME
}

const goToAuthenticatedRoute = (target: any) => {
  const resolved = router.resolve(target)

  localStorage.setItem(LAST_ACTIVITY_KEY, String(Date.now()))

  if (
    resolved.fullPath
    && resolved.fullPath !== '/'
    && resolved.fullPath !== '/app'
    && !resolved.fullPath.startsWith('/auth')
  ) {
    localStorage.setItem(LAST_AUTH_ROUTE_KEY, resolved.fullPath)
  }

  router.replace(target)
}

const faceModelsLoaded = ref(false)
const faceModelsLoading = ref(false)

useHead({
  title: 'Auth Login - ' + import.meta.env.VITE_PROJECT,
  link: [
    {
      rel: 'preconnect',
      href: 'https://challenges.cloudflare.com',
    },
    {
      rel: 'dns-prefetch',
      href: 'https://challenges.cloudflare.com',
    },
  ],
  script: [
    {
      src: TURNSTILE_SRC,
      async: true,
      defer: true,
    },
  ],
})

const authApi = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  withCredentials: true,
})

const clearTurnstileTimer = () => {
  if (turnstileCheckTimer.value) {
    window.clearTimeout(turnstileCheckTimer.value)
    turnstileCheckTimer.value = null
  }
}

const removeCaptcha = () => {
  clearTurnstileTimer()

  if (window.turnstile && captchaWidgetId.value) {
    try {
      window.turnstile.remove(captchaWidgetId.value)
    } catch (e) {
      //
    }
  }

  captchaWidgetId.value = null
  captchaToken.value = ''
  isCaptchaRendered.value = false
}

const renderCaptcha = async () => {
  captchaRenderError.value = ''
  isCaptchaLoading.value = true

  const siteKey = import.meta.env.VITE_TURNSTILE_SITE_KEY

  if (!siteKey) {
    captchaRenderError.value = 'VITE_TURNSTILE_SITE_KEY belum diisi di file .env frontend'
    isCaptchaLoading.value = false
    return
  }

  if (!window.turnstile) {
    captchaRenderError.value = 'Script Turnstile belum berhasil dimuat'
    isCaptchaLoading.value = false
    return
  }

  await nextTick()

  if (!captchaContainer.value) {
    captchaRenderError.value = 'Container captcha belum tersedia'
    isCaptchaLoading.value = false
    return
  }

  removeCaptcha()

  try {
    captchaWidgetId.value = window.turnstile.render(captchaContainer.value, {
      sitekey: siteKey,
      theme: darkmode.isDark ? 'dark' : 'light',
      callback: (token: string) => {
        captchaToken.value = token
        captchaRenderError.value = ''
      },
      'expired-callback': () => {
        captchaToken.value = ''
      },
      'error-callback': () => {
        captchaToken.value = ''
        captchaRenderError.value = 'Captcha gagal dimuat. Coba refresh halaman.'
      },
    })

    isCaptchaRendered.value = true
    isCaptchaLoading.value = false
  } catch (e: any) {
    captchaRenderError.value = 'Captcha gagal dirender. Periksa SITE KEY Turnstile.'
    isCaptchaLoading.value = false
  }
}

const startCaptchaFallbackCheck = (attempt = 0) => {
  clearTurnstileTimer()

  if (window.turnstile) {
    renderCaptcha()
    return
  }

  if (attempt >= 80) {
    isCaptchaLoading.value = false
    captchaRenderError.value = 'Script captcha tidak berhasil dimuat'
    return
  }

  const delay = attempt < 10 ? 120 : 250

  turnstileCheckTimer.value = window.setTimeout(() => {
    startCaptchaFallbackCheck(attempt + 1)
  }, delay)
}

const initCaptcha = async () => {
  captchaRenderError.value = ''
  isCaptchaLoading.value = true

  if (window.turnstile) {
    await renderCaptcha()
    return
  }

  startCaptchaFallbackCheck()
}

const manualRetryCaptcha = async () => {
  captchaToken.value = ''
  captchaRenderError.value = ''
  isCaptchaLoading.value = true
  await initCaptcha()
}

const resetCaptcha = () => {
  captchaToken.value = ''
  if (window.turnstile && captchaWidgetId.value) {
    try {
      window.turnstile.reset(captchaWidgetId.value)
    } catch (e) {
      //
    }
  }
}

window.onTurnstileLoad = async () => {
  clearTurnstileTimer()
  await renderCaptcha()
}

const sanitizeWa628 = (value: string) => {
  if (!value) return ''
  return value.replace(/\D/g, '')
}

const isValidWa628 = (value: string) => {
  return /^628[0-9]{6,15}$/.test(value)
}

const onInputForgotWa = (e: Event) => {
  const target = e.target as HTMLInputElement
  let val = sanitizeWa628(target.value)
  forgotForm.nowa = val
}

const handleLogin = () => {
  isError.value = false
  error.value = ''

  if (namaUser.value == '') {
    error.value = 'Username Required'
    isError.value = true
    return
  }

  if (kataSandi.value == '') {
    error.value = 'Password Required'
    isError.value = true
    return
  }

  if (!captchaToken.value) {
    error.value = 'Captcha wajib diisi'
    isError.value = true
    return
  }

  if (!isLoading.value) {
    isLoading.value = true
    notif.dismissAll()

    authApi
      .post('auth/login', {
        namaUser: namaUser.value,
        kataSandi: kataSandi.value,
        captcha_token: captchaToken.value,
      })
      .then(({ data }) => {
        isLoading.value = false

        if (data.metaData.code == 200) {
          let res = data.response
          toast.add({
            severity: 'success',
            summary: 'Info',
            detail: `Welcome back, ${res.data.pegawai.namaLengkap}`,
            life: 3000,
            group: 'br',
          })

          userSession.setUser(res.data)
          userSession.setToken(res.token)
          userSession.setUserData(res.data)

          goToAuthenticatedRoute(redirect || {
            name: defaultRouteName(res.data),
          })
        } else {
          notif.error(data.metaData.message)
          resetCaptcha()
        }
      })
      .catch((err) => {
        isLoading.value = false
        const message =
          typeof err.response !== 'undefined'
            ? err.response.data.metaData?.message || err.response.data.message
            : err.message

        notif.error(message)
        resetCaptcha()
      })
  }
}

const changeUser = (e: any) => {
  if (e != '') {
    error.value = ''
    isError.value = false
  }
}

const video = ref<HTMLVideoElement | null>(null)
const isRecognizing = ref(false)
const message = ref('Arahkan wajah ke kamera')

const loadModels = async () => {
  if (faceModelsLoaded.value || faceModelsLoading.value) return

  try {
    faceModelsLoading.value = true
    await faceapi.nets.tinyFaceDetector.loadFromUri('/models')
    await faceapi.nets.faceLandmark68Net.loadFromUri('/models')
    await faceapi.nets.faceRecognitionNet.loadFromUri('/models')
    await faceapi.nets.faceExpressionNet.loadFromUri('/models')
    faceModelsLoaded.value = true
  } finally {
    faceModelsLoading.value = false
  }
}

const preloadFaceModelsInBackground = () => {
  const run = () => {
    loadModels().catch(() => {
      //
    })
  }

  if ('requestIdleCallback' in window && typeof window.requestIdleCallback === 'function') {
    window.requestIdleCallback(run, { timeout: 5000 })
  } else {
    setTimeout(run, 3500)
  }
}

onMounted(async () => {
  await nextTick()
  await initCaptcha()
  preloadFaceModelsInBackground()
})

onBeforeUnmount(() => {
  removeCaptcha()
  clearTurnstileTimer()

  if (window.onTurnstileLoad) {
    delete window.onTurnstileLoad
  }
})

watch(
  () => darkmode.isDark,
  async () => {
    if (window.turnstile) {
      await renderCaptcha()
    }
  }
)

const startCamera = async () => {
  const stream = await navigator.mediaDevices.getUserMedia({ video: true })
  if (video.value) video.value.srcObject = stream as any
}

const stopCamera = () => {
  if (video.value && video.value.srcObject) {
    const tracks = (video.value.srcObject as MediaStream).getTracks()
    tracks.forEach((t) => t.stop())
    video.value!.srcObject = null
  }
}

const captureFace = async () => {
  videoRecog.value = true
  message.value = 'Menyiapkan model wajah...'

  await loadModels()
  await startCamera()

  if (!video.value) return

  isRecognizing.value = true
  message.value = 'Mendeteksi wajah...'

  video.value.removeEventListener('play', onPlayHandler)
  video.value.addEventListener('play', onPlayHandler)

  if (!video.value.paused && !video.value.ended) {
    onPlayHandler()
  }
}

const onPlayHandler = () => {
  if (!video.value) return

  const oldCanvas = video.value.parentElement?.querySelector('canvas')
  if (oldCanvas) {
    oldCanvas.remove()
  }

  const canvas = faceapi.createCanvasFromMedia(video.value)
  canvas.style.position = 'absolute'
  canvas.style.left = `${video.value.offsetLeft}px`
  canvas.style.top = `${video.value.offsetTop}px`
  canvas.style.pointerEvents = 'none'
  video.value.parentElement?.appendChild(canvas)

  const displaySize = { width: video.value.videoWidth, height: video.value.videoHeight }
  faceapi.matchDimensions(canvas, displaySize)

  const ctx = canvas.getContext('2d')
  const intervalId = setInterval(async () => {
    if (!video.value || !ctx) return

    const detections = await faceapi
      .detectAllFaces(
        video.value,
        new faceapi.TinyFaceDetectorOptions({ inputSize: 160, scoreThreshold: 0.5 })
      )
      .withFaceLandmarks()
      .withFaceDescriptors()

    const resizedDetections = faceapi.resizeResults(detections, displaySize)
    ctx.clearRect(0, 0, canvas.width, canvas.height)
    faceapi.draw.drawDetections(canvas, resizedDetections)

    if (detections.length > 0) {
      message.value = 'Wajah terdeteksi, autentikasi...'
      clearInterval(intervalId)
      stopCamera()
      videoRecog.value = false

      const descriptor = Array.from(detections[0].descriptor)
      await sendDescriptorToBackend(descriptor)
    } else {
      message.value = 'Wajah tidak terdeteksi...'
    }
  }, 200)
}

const sendDescriptorToBackend = async (descriptor: any) => {
  try {
    const { data } = await authApi.post('auth/login-face', { descriptor })
    if (data.metaData.code === 200) {
      const res = data.response
      toast.add({
        severity: 'success',
        summary: 'Login Wajah Berhasil',
        detail: res.data.pegawai.namaLengkap,
        life: 3000,
      })

      userSession.setUser(res.data)
      userSession.setToken(res.token)
      userSession.setUserData(res.data)

      goToAuthenticatedRoute({ name: defaultRouteName(res.data) })
    } else {
      notif.error(data.metaData.message)
    }
  } catch (err: any) {
    notif.error('Login wajah gagal: ' + (err.response?.data?.metaData?.message || err.message))
  }
}

const forgotVisible = ref(false)
const forgotStep = ref<ForgotStep>('identify')
const forgotLoading = ref(false)
const resendLoading = ref(false)
const forgotError = ref('')
const otpVerified = ref(false)

const forgotForm = reactive({
  email: '',
  nowa: '',
  channel: 'email',
  user_id: null as number | null,
  purpose: 'reset_password',
  otp: '',
  password: '',
  password_confirmation: '',
})

const openForgotModal = () => {
  forgotVisible.value = true
  forgotStep.value = 'identify'
  forgotError.value = ''
  otpVerified.value = false

  forgotForm.email = ''
  forgotForm.nowa = ''
  forgotForm.channel = 'email'
  forgotForm.user_id = null
  forgotForm.purpose = 'reset_password'
  forgotForm.otp = ''
  forgotForm.password = ''
  forgotForm.password_confirmation = ''
}

const closeForgotModal = () => {
  forgotVisible.value = false
  forgotStep.value = 'identify'
  forgotError.value = ''
  otpVerified.value = false

  forgotForm.email = ''
  forgotForm.nowa = ''
  forgotForm.channel = 'email'
  forgotForm.user_id = null
  forgotForm.purpose = 'reset_password'
  forgotForm.otp = ''
  forgotForm.password = ''
  forgotForm.password_confirmation = ''
}

const requestForgotOtp = async () => {
  forgotError.value = ''

  if (!forgotForm.email) {
    forgotError.value = 'Email wajib diisi'
    return
  }
  if (!forgotForm.nowa) {
    forgotError.value = 'No. HP wajib diisi'
    return
  }

  forgotForm.nowa = sanitizeWa628(forgotForm.nowa)

  if (!isValidWa628(forgotForm.nowa)) {
    forgotError.value =
      'No. WhatsApp harus diawali 628 dan hanya boleh angka. Contoh: 6281234567890'
    return
  }

  try {
    forgotLoading.value = true
    const { data } = await authApi.post('auth/forgot-password/request-otp', {
      email: forgotForm.email,
      nowa: forgotForm.nowa,
      channel: forgotForm.channel,
    })

    if (data.status === 200) {
      forgotForm.user_id = data.result.user_id
      forgotForm.purpose = data.result.purpose || 'reset_password'
      forgotForm.channel = data.result.channel || forgotForm.channel
      forgotStep.value = 'otp'

      toast.add({
        severity: 'success',
        summary: 'OTP Terkirim',
        detail: data.message || 'OTP berhasil dikirim',
        life: 3000,
      })
    } else {
      forgotError.value = data.message || 'Gagal mengirim OTP'
    }
  } catch (err: any) {
    forgotError.value = err.response?.data?.message || err.message || 'Gagal mengirim OTP'
  } finally {
    forgotLoading.value = false
  }
}

const resendForgotOtp = async () => {
  forgotError.value = ''

  if (!forgotForm.user_id) {
    forgotError.value = 'User belum ditemukan'
    return
  }

  try {
    resendLoading.value = true
    const { data } = await authApi.post('auth/otp/resend', {
      user_id: forgotForm.user_id,
      channel: forgotForm.channel,
      purpose: forgotForm.purpose,
    })

    if (data.status === 200) {
      toast.add({
        severity: 'success',
        summary: 'OTP Dikirim Ulang',
        detail: data.message || 'OTP berhasil dikirim ulang',
        life: 3000,
      })
    } else {
      forgotError.value = data.message || 'Gagal resend OTP'
    }
  } catch (err: any) {
    forgotError.value = err.response?.data?.message || err.message || 'Gagal resend OTP'
  } finally {
    resendLoading.value = false
  }
}

const verifyForgotOtp = async () => {
  forgotError.value = ''

  if (!forgotForm.user_id) {
    forgotError.value = 'User tidak ditemukan'
    return
  }
  if (!forgotForm.otp) {
    forgotError.value = 'Kode OTP wajib diisi'
    return
  }

  try {
    forgotLoading.value = true
    const { data } = await authApi.post('auth/forgot-password/verify-otp', {
      user_id: forgotForm.user_id,
      code: forgotForm.otp,
    })

    if (data.status === 200) {
      otpVerified.value = true
      forgotStep.value = 'reset'

      toast.add({
        severity: 'success',
        summary: 'OTP Valid',
        detail: data.message || 'OTP valid, silakan buat password baru',
        life: 3000,
      })
    } else {
      forgotError.value = data.message || 'OTP tidak valid'
    }
  } catch (err: any) {
    forgotError.value = err.response?.data?.message || err.message || 'OTP tidak valid'
  } finally {
    forgotLoading.value = false
  }
}

const submitNewPassword = async () => {
  forgotError.value = ''

  if (!forgotForm.password) {
    forgotError.value = 'Password baru wajib diisi'
    return
  }
  if (!forgotForm.password_confirmation) {
    forgotError.value = 'Konfirmasi password wajib diisi'
    return
  }
  if (forgotForm.password !== forgotForm.password_confirmation) {
    forgotError.value = 'Konfirmasi password tidak sama'
    return
  }
  if (!forgotForm.user_id || !forgotForm.otp) {
    forgotError.value = 'Data reset password tidak lengkap'
    return
  }

  try {
    forgotLoading.value = true
    const { data } = await authApi.post('auth/forgot-password/reset', {
      user_id: forgotForm.user_id,
      code: forgotForm.otp,
      password: forgotForm.password,
      password_confirmation: forgotForm.password_confirmation,
    })

    if (data.status === 200) {
      toast.add({
        severity: 'success',
        summary: 'Berhasil',
        detail: data.message || 'Password berhasil diperbarui',
        life: 3000,
      })

      closeForgotModal()
    } else {
      forgotError.value = data.message || 'Gagal reset password'
    }
  } catch (err: any) {
    forgotError.value = err.response?.data?.message || err.message || 'Gagal reset password'
  } finally {
    forgotLoading.value = false
  }
}
</script>

<template>
  <div class="modern-login">
    <div class="underlay h-hidden-mobile h-hidden-tablet-p"></div>

    <div class="columns is-gapless is-vcentered">
      <div class="column is-relative is-8 h-hidden-mobile h-hidden-tablet-p">
        <div class="hero is-fullheight is-image">
          <div class="hero-body">
            <div class="container">
              <div class="columns">
                <div class="column">
                  <img class="hero-image" src="/@src/assets/illustrations/login/loginview.webp" alt="" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="column is-4 is-relative">
        <RouterLink :to="{ name: 'index' }" class="top-logo">
          <LogoRS />
        </RouterLink>

        <label
          class="dark-mode ml-auto"
          tabindex="0"
          @keydown.space.prevent="(e) => (e.target as HTMLLabelElement).click()"
        >
          <input type="checkbox" :checked="!darkmode.isDark" @change="darkmode.onChange" />
          <span></span>
        </label>

        <div class="is-form">
          <div class="hero-body">
            <div class="form-text" style="text-align: center" :class="[step !== 'login' && 'is-hidden']">
              <h2>U-LAB</h2>
              <p>Login to your Account</p>
            </div>

            <form :class="[step !== 'login' && 'is-hidden']" class="login-wrapper" @submit.prevent="handleLogin">
              <VMessage color="danger" v-show="isError">{{ error }}</VMessage>

              <VField>
                <VControl icon="lnil lnil-envelope autv-icon">
                  <VLabel class="auth-label">Email or Username</VLabel>
                  <VInput
                    type="text"
                    autocomplete="current-password"
                    v-model="namaUser"
                    @input="changeUser(($event.target as HTMLInputElement).value)"
                  />
                </VControl>
              </VField>

              <VField>
                <VControl icon="lnil lnil-lock-alt autv-icon">
                  <VLabel class="auth-label">Password</VLabel>
                  <Password
                    v-model="kataSandi"
                    toggleMask
                    placeholder=""
                    class="is-rounded w-100 is-login-pass"
                    @input="changeUser(($event.target as HTMLInputElement).value)"
                  />
                </VControl>
              </VField>

              <div class="mt-4">
                <div class="captcha-wrap">
                  <div class="captcha-box" :class="{ 'is-ready': isCaptchaRendered }">
                    <div v-if="isCaptchaLoading" class="captcha-skeleton">
                      <div class="skeleton-checkbox"></div>
                      <div class="skeleton-lines">
                        <span class="line line-1"></span>
                        <span class="line line-2"></span>
                      </div>
                      <div class="skeleton-badge"></div>
                    </div>

                    <div
                      ref="captchaContainer"
                      class="captcha-container"
                      :class="{ 'is-hidden-until-ready': isCaptchaLoading && !isCaptchaRendered }"
                    ></div>
                  </div>
                </div>

                <small v-if="!captchaRenderError && isCaptchaLoading" class="captcha-hint">
                  Menyiapkan captcha...
                </small>

                <small v-if="captchaRenderError" class="captcha-error">
                  {{ captchaRenderError }}
                </small>

                <div v-if="captchaRenderError" class="captcha-retry-wrap">
                  <VButton color="warning" outlined size="small" @click="manualRetryCaptcha">
                    Muat Ulang Captcha
                  </VButton>
                </div>
              </div>

              <div class="forgot-wrap">
                <a href="javascript:void(0)" class="forgot-link" @click="openForgotModal">
                  Lupa Password <span>(khusus customer)</span>
                </a>
              </div>

              <div class="button-wrap has-help">
                <VButton
                  id="login-button"
                  icon="feather:arrow-right"
                  :loading="isLoading"
                  color="info"
                  type="submit"
                  size="big"
                  rounded
                  raised
                  bold
                >
                  Login
                </VButton>
                <span>
                  Or
                  <RouterLink :to="{ name: 'auth-signup-1' }">Create</RouterLink>
                  an account.
                </span>
              </div>
            </form>

            <!--
            <div style="text-align:center; margin:32px 0;">
              <VButton color="info" size="big" rounded @click="captureFace">
                <i class="fas fa-camera"></i> Login dengan Wajah
              </VButton>
            </div>
            -->
          </div>
        </div>
      </div>
    </div>

    <Dialog v-model:visible="videoRecog" modal header="Face Recognition" :style="{ width: '620px' }">
      <div style="text-align: center; position: relative">
        <video
          ref="video"
          autoplay
          playsinline
          style="border-radius: 10px; width: 520px; margin-bottom: 10px"
        ></video>
        <div style="margin: 8px 0">{{ message }}</div>
      </div>
    </Dialog>

    <Dialog
      v-model:visible="forgotVisible"
      modal
      :closable="true"
      header="Lupa Password (Khusus Customer)"
      :style="{ width: '560px' }"
      @hide="closeForgotModal"
    >
      <div class="forgot-password-modal">
        <div class="forgot-steps">
          <div class="step-item" :class="{ active: forgotStep === 'identify' }">1. Verifikasi Data</div>
          <div class="step-item" :class="{ active: forgotStep === 'otp' }">2. Verifikasi OTP</div>
          <div class="step-item" :class="{ active: forgotStep === 'reset' }">3. Password Baru</div>
        </div>

        <div v-if="forgotError" class="forgot-error">
          {{ forgotError }}
        </div>

        <div v-if="forgotStep === 'identify'" class="forgot-body">
          <p class="forgot-desc">
            Masukkan email customer dan nomor HP yang terdaftar. Sistem akan mengecek keduanya di tabel customer.
          </p>

          <VField>
            <VLabel>Email Customer</VLabel>
            <VControl>
              <VInput v-model="forgotForm.email" type="email" placeholder="Masukkan email customer" />
            </VControl>
          </VField>

          <VField>
            <VLabel>No. HP / WhatsApp Terdaftar</VLabel>
            <VControl>
              <VInput
                :model-value="forgotForm.nowa"
                type="text"
                inputmode="numeric"
                maxlength="18"
                placeholder="Contoh: 6281234567890"
                @input="onInputForgotWa"
              />
            </VControl>
            <small class="wa-hint">
              Gunakan format <strong>628...</strong>, tanpa tanda <strong>+</strong>, tanpa spasi, dan tanpa awalan
              <strong>0</strong>.
            </small>
          </VField>

          <VField>
            <VLabel>Kirim OTP Melalui</VLabel>
            <VControl>
              <div class="channel-wrap">
                <label class="channel-option">
                  <input type="radio" value="email" v-model="forgotForm.channel" />
                  <span>Email</span>
                </label>

                <label class="channel-option">
                  <input type="radio" value="wa" v-model="forgotForm.channel" />
                  <span>WhatsApp</span>
                </label>
              </div>
            </VControl>
          </VField>

          <div class="forgot-actions">
            <VButton color="default" outlined @click="closeForgotModal">Batal</VButton>
            <VButton color="info" :loading="forgotLoading" @click="requestForgotOtp"> Kirim OTP </VButton>
          </div>
        </div>

        <div v-if="forgotStep === 'otp'" class="forgot-body">
          <p class="forgot-desc">
            OTP telah dikirim melalui <strong>{{ forgotForm.channel === 'wa' ? 'WhatsApp' : 'Email' }}</strong>.
            Masukkan kode OTP untuk melanjutkan.
          </p>

          <VField>
            <VLabel>Kode OTP</VLabel>
            <VControl>
              <VInput v-model="forgotForm.otp" type="text" placeholder="Masukkan 6 digit OTP" maxlength="6" />
            </VControl>
          </VField>

          <div class="forgot-actions between">
            <div class="left-actions">
              <VButton color="default" outlined @click="forgotStep = 'identify'"> Kembali </VButton>

              <VButton color="warning" :loading="resendLoading" outlined @click="resendForgotOtp">
                Resend OTP
              </VButton>
            </div>

            <VButton color="info" :loading="forgotLoading" @click="verifyForgotOtp"> Verifikasi OTP </VButton>
          </div>
        </div>

        <div v-if="forgotStep === 'reset'" class="forgot-body">
          <p class="forgot-desc">OTP sudah valid. Sekarang buat password baru untuk akun customer.</p>

          <VField>
            <VLabel>Password Baru</VLabel>
            <VControl>
              <Password v-model="forgotForm.password" toggleMask class="w-100 forgot-pass" />
            </VControl>
          </VField>

          <VField>
            <VLabel>Konfirmasi Password Baru</VLabel>
            <VControl>
              <Password v-model="forgotForm.password_confirmation" toggleMask class="w-100 forgot-pass" />
            </VControl>
          </VField>

          <div class="forgot-actions">
            <VButton color="default" outlined @click="forgotStep = 'otp'"> Kembali </VButton>

            <VButton color="success" :loading="forgotLoading" @click="submitNewPassword">
              Simpan Password Baru
            </VButton>
          </div>
        </div>
      </div>
    </Dialog>
  </div>
</template>

<style lang="scss" scoped>
.container {
  text-align: center;
}

video {
  width: 100%;
  border-radius: 10px;
  margin-bottom: 10px;
}

button {
  padding: 10px 15px;
  background: #007bff;
  color: white;
  border: none;
  cursor: pointer;
}

.captcha-wrap {
  display: flex;
  justify-content: center;
  margin-top: 8px;
  margin-bottom: 8px;
  min-height: 78px;
}

.captcha-box {
  position: relative;
  width: 100%;
  max-width: 320px;
  min-height: 74px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.captcha-container {
  width: 100%;
  display: flex;
  justify-content: center;
}

.is-hidden-until-ready {
  visibility: hidden;
  height: 0;
  overflow: hidden;
}

.captcha-skeleton {
  width: 100%;
  min-height: 74px;
  border-radius: 12px;
  border: 1px solid var(--fade-grey);
  background: linear-gradient(90deg, #f4f6f8 25%, #eef1f4 37%, #f4f6f8 63%);
  background-size: 400% 100%;
  animation: captchaShimmer 1.15s infinite linear;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
}

.skeleton-checkbox {
  width: 22px;
  height: 22px;
  border-radius: 6px;
  background: rgba(0, 0, 0, 0.08);
  flex: 0 0 22px;
}

.skeleton-lines {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.skeleton-lines .line {
  display: block;
  height: 10px;
  border-radius: 999px;
  background: rgba(0, 0, 0, 0.08);
}

.skeleton-lines .line-1 {
  width: 70%;
}

.skeleton-lines .line-2 {
  width: 44%;
}

.skeleton-badge {
  width: 44px;
  height: 30px;
  border-radius: 8px;
  background: rgba(0, 0, 0, 0.08);
  flex: 0 0 44px;
}

.captcha-hint {
  display: block;
  color: var(--muted-grey);
  text-align: center;
  font-size: 0.83rem;
  margin-top: -2px;
}

.captcha-error {
  display: block;
  color: #dc3545;
  text-align: center;
}

.captcha-retry-wrap {
  margin-top: 8px;
  display: flex;
  justify-content: center;
}

@keyframes captchaShimmer {
  0% {
    background-position: 100% 0;
  }
  100% {
    background-position: -100% 0;
  }
}

:deep(.is-login-pass .p-password-input) {
  width: 100%;
  padding-top: 14px;
  height: 60px;
  border-radius: 10px;
  padding-left: 55px;
}

:deep(.forgot-pass .p-password-input) {
  width: 100%;
  height: 48px;
  border-radius: 10px;
}

.modern-login {
  position: relative;
  background: var(--white);
  min-height: 100vh;

  .column {
    &.is-relative {
      position: relative;
    }
  }

  .hero {
    &.has-background-image {
      position: relative;

      .hero-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: #5d4298 !important;
        opacity: 0.6;
      }
    }
  }

  .underlay {
    display: block;
    position: absolute;
    top: 0;
    left: 0;
    width: 66.6%;
    height: 100%;
    background-image: url('/@src/assets/illustrations/login/iris2.webp');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    z-index: 0;
  }

  .dark-mode {
    position: absolute;
    top: -64px;
    right: 38px;
    transform: scale(0.6);
    z-index: 2;
  }

  .top-logo {
    position: absolute;
    top: -70px;
    left: 0;
    right: 0;
    margin: 0 auto;
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1;

    img {
      display: block;
      width: 100%;
      max-width: 70px;
      margin: 0 auto;
    }

    svg {
      height: 50px;
      width: 50px;
    }
  }

  .is-image {
    position: relative;
    border-right: 1px solid var(--fade-grey);

    .hero-image {
      position: relative;
      z-index: 2;
      display: block;
      margin: -80px auto 0;
      width: min(60%, 980px);
      max-width: 60%;
      height: auto;
      max-height: calc(100vh - 140px);
      min-width: 0;
      min-height: 0;
      object-fit: contain;
    }
  }

  .is-form {
    position: relative;
    max-width: 420px;
    margin: 0 auto;

    form {
      animation: fadeInLeft 0.5s;
    }

    .form-text {
      padding: 0 10px;
      animation: fadeInLeft 0.5s;

      h2 {
        font-family: var(--font-alt);
        font-weight: bold;
        font-size: 3rem;
        color: var(--info);
      }

      p {
        color: var(--muted-grey);
        margin-top: 0px;
      }
    }

    .recover-text {
      font-size: 0.9rem;
      color: var(--dark-text);
    }

    .login-wrapper {
      padding: 30px 20px;

      .control {
        position: relative;
        width: 100%;
        margin-top: 16px;

        .input {
          padding-top: 14px;
          height: 60px;
          border-radius: 10px;
          padding-left: 55px;
          transition: all 0.3s;

          &:focus {
            background: var(--fade-grey-light-6);
            border-color: var(--placeholder);

            ~ .auth-label,
            ~ .autv-icon i {
              color: var(--muted-grey);
            }
          }
        }

        .error-text {
          color: var(--danger);
          font-size: 0.8rem;
          display: none;
          padding: 2px 6px;
        }

        .auth-label {
          position: absolute;
          top: 6px;
          left: 55px;
          font-size: 0.8rem;
          color: var(--dark-text);
          font-weight: 500;
          z-index: 2;
          transition: all 0.3s;
        }

        .autv-icon,
        :deep(.autv-icon) {
          position: absolute;
          top: 0;
          left: 0;
          height: 60px;
          width: 60px;
          display: flex;
          justify-content: center;
          align-items: center;
          font-size: 24px;
          color: var(--placeholder);
          transition: all 0.3s;
        }

        &.has-validation {
          .validation-icon {
            position: absolute;
            top: 0;
            right: 0;
            height: 60px;
            width: 60px;
            display: none;
            justify-content: center;
            align-items: center;

            .icon-wrapper {
              height: 20px;
              width: 20px;
              display: flex;
              justify-content: center;
              align-items: center;
              border-radius: var(--radius-rounded);

              svg {
                height: 10px;
                width: 10px;
                stroke-width: 3px;
                color: var(--white);
              }
            }

            &.is-success {
              .icon-wrapper {
                background: var(--success);
              }
            }

            &.is-error {
              .icon-wrapper {
                background: var(--danger);
              }
            }
          }

          &.has-success {
            .validation-icon {
              &.is-success {
                display: flex;
              }

              &.is-error {
                display: none;
              }
            }
          }

          &.has-error {
            .input {
              border-color: var(--danger);
            }

            .error-text {
              display: block;
            }

            .validation-icon {
              &.is-error {
                display: flex;
              }

              &.is-success {
                display: none;
              }
            }
          }
        }

        &.is-flex {
          display: flex;
          align-items: center;

          a {
            display: block;
            margin-left: auto;
            color: var(--muted-grey);
            font-weight: 500;
            font-size: 0.9rem;
            transition: color 0.3s;

            &:hover,
            &:focus {
              color: var(--info);
            }
          }

          .remember-me {
            font-size: 0.9rem;
            color: var(--muted-grey);
            font-weight: 500;
          }
        }
      }

      .forgot-wrap {
        margin-top: 14px;
        display: flex;
        justify-content: flex-end;

        .forgot-link {
          font-size: 0.92rem;
          color: var(--info);
          font-weight: 600;
          text-decoration: none;

          span {
            font-weight: 500;
            opacity: 0.85;
          }

          &:hover {
            text-decoration: underline;
          }
        }
      }

      .button-wrap {
        margin: 40px 0;

        &.has-help {
          display: flex;
          align-items: center;

          > span {
            margin-left: 12px;
            font-family: var(--font);

            a {
              color: var(--info);
              font-weight: 500;
              padding: 0 2px;
            }
          }
        }

        .button {
          height: 46px;
          width: 140px;
          margin-left: 6px;

          &:first-child {
            &:hover {
              opacity: 0.8;
            }
          }
        }
      }
    }
  }
}

.forgot-password-modal {
  .forgot-steps {
    display: flex;
    gap: 8px;
    margin-bottom: 18px;
    flex-wrap: wrap;

    .step-item {
      padding: 8px 12px;
      border-radius: 999px;
      background: var(--fade-grey-light-3);
      color: var(--muted-grey);
      font-size: 0.85rem;
      font-weight: 600;

      &.active {
        background: var(--info);
        color: white;
      }
    }
  }

  .forgot-error {
    background: rgba(255, 56, 96, 0.12);
    color: var(--danger);
    border: 1px solid rgba(255, 56, 96, 0.22);
    border-radius: 10px;
    padding: 10px 12px;
    margin-bottom: 16px;
    font-size: 0.92rem;
  }

  .forgot-body {
    .forgot-desc {
      margin-bottom: 16px;
      color: var(--muted-grey);
      line-height: 1.6;
      font-size: 0.95rem;
    }
  }

  .channel-wrap {
    display: flex;
    gap: 16px;
    align-items: center;
    margin-top: 6px;

    .channel-option {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      cursor: pointer;
      font-weight: 500;
    }
  }

  .forgot-actions {
    margin-top: 22px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;

    &.between {
      justify-content: space-between;
      align-items: center;
    }

    .left-actions {
      display: flex;
      gap: 10px;
    }
  }
}

.remember-toggle {
  width: 65px;
  display: block;
  position: relative;
  cursor: pointer;
  font-size: 22px;
  user-select: none;
  transform: scale(0.9);

  input {
    position: absolute;
    opacity: 0;
    cursor: pointer;

    &:checked ~ .toggler {
      border-color: var(--info);

      .active,
      .inactive {
        transform: translateX(100%) rotate(360deg);
      }

      .active {
        opacity: 1;
      }

      .inactive {
        opacity: 0;
      }
    }
  }

  .toggler {
    position: relative;
    display: block;
    height: 34px;
    width: 61px;
    border: 2px solid var(--placeholder);
    border-radius: 100px;
    transition: all 0.3s;

    .active,
    .inactive {
      position: absolute;
      top: 2px;
      left: 2px;
      height: 26px;
      width: 26px;
      border-radius: var(--radius-rounded);
      background: black;
      display: flex;
      justify-content: center;
      align-items: center;
      transform: translateX(0) rotate(0);
      transition: all 0.3s ease;

      svg {
        color: var(--white);
        height: 14px;
        width: 14px;
        stroke-width: 3px;
      }
    }

    .inactive {
      background: var(--placeholder);
      border-color: var(--placeholder);
      opacity: 1;
      z-index: 1;
    }

    .active {
      background: var(--info);
      border-color: var(--info);
      opacity: 0;
      z-index: 0;
    }
  }
}

@media only screen and (max-width: 767px) {
  .modern-login {
    .top-logo {
      top: 30px;
    }

    .dark-mode {
      top: 36px;
      right: 44px;
    }

    .is-form {
      padding-top: 100px;
    }
  }

  .forgot-password-modal {
    .forgot-actions {
      flex-direction: column;

      &.between {
        align-items: stretch;
      }

      .left-actions {
        display: flex;
        flex-direction: column;
      }
    }
  }
}

@media only screen and (min-width: 768px) and (max-width: 1024px) and (orientation: portrait) {
  .modern-login {
    .top-logo {
      svg {
        height: 60px;
        width: 60px;
      }
    }

    .dark-mode {
      top: -58px;
      right: 30%;
    }

    .columns {
      display: flex;
      height: 100vh;
    }
  }
}

.is-dark {
  .modern-login {
    background: var(--dark-sidebar);

    .underlay {
      background-image: url('/@src/assets/illustrations/login/iris2.webp');
      background-blend-mode: multiply;
      background-color: var(--dark-sidebar-light-10);
    }

    .is-image {
      border-color: var(--dark-sidebar-light-10);
    }

    .is-form {
      .form-text {
        h2 {
          color: var(--info);
        }
      }

      .login-wrapper {
        .control {
          &.is-flex {
            a:hover {
              color: var(--info);
            }
          }

          .input {
            background: var(--dark-sidebar-light-4);

            &:focus {
              border-color: var(--info);

              ~ .autv-icon {
                i {
                  color: var(--info);
                }
              }
            }
          }

          .auth-label {
            color: var(--light-text);
          }
        }

        .button-wrap {
          &.has-help {
            span {
              color: var(--light-text);

              a {
                color: var(--info);
              }
            }
          }
        }
      }
    }
  }

  .captcha-skeleton {
    border-color: var(--dark-sidebar-light-12);
    background: linear-gradient(
      90deg,
      rgba(255, 255, 255, 0.04) 25%,
      rgba(255, 255, 255, 0.08) 37%,
      rgba(255, 255, 255, 0.04) 63%
    );
    background-size: 400% 100%;
  }

  .skeleton-checkbox,
  .skeleton-lines .line,
  .skeleton-badge {
    background: rgba(255, 255, 255, 0.12);
  }

  .captcha-hint {
    color: var(--light-text);
    opacity: 0.75;
  }

  .forgot-password-modal {
    .forgot-steps {
      .step-item {
        background: var(--dark-sidebar-light-6);
        color: var(--light-text);

        &.active {
          background: var(--info);
          color: #fff;
        }
      }
    }

    .forgot-desc {
      color: var(--light-text);
    }
  }

  .remember-toggle {
    input {
      &:checked + .toggler {
        border-color: var(--info);

        > span {
          background: var(--info);
        }
      }
    }

    .toggler {
      border-color: var(--dark-sidebar-light-12);

      > span {
        background: var(--dark-sidebar-light-12);
      }
    }
  }
}
</style>
