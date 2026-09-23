<script setup lang="ts">
import { ref, reactive, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useHead } from '@vueuse/head'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useDarkmode } from '/@src/stores/darkmode'
import { useNotyf } from '/@src/composable/useNotyf'
import Password from 'primevue/password'
import AuthIrisStage from '/@src/components/partials/auth/AuthIrisStage.vue'
import IrisBrand from '/@src/components/partials/landing/IrisBrand.vue'

const router = useRouter()
const darkmode = useDarkmode()
const notif = useNotyf()

/* ===================== State ===================== */
const step = ref<0 | 1>(0)         // 0=Signup, 1=OTP
const isLoading = ref(false)
const isError = ref(false)
const errorMsg = ref('')

const loginuser = reactive({
  name: '',
  nowa: '',
  email: '',
  password: '',
  password_confirmation: '',
  channel: 'email' as 'email' | 'wa',
})
const waSubscriber = ref('')

const pendingVerify = reactive({
  user_id: null as number | null,
  channel: 'email' as 'email' | 'wa',
  purpose: 'register',
  expires_at: '',
})

const otpCode = ref('')
const resendCooldown = ref(0)
let cooldownTimer: any = null

/* ===================== Meta ===================== */
useHead({ title: 'Daftar Customer IRIS — Intelligent Real-time Insight System' })

/* ===================== Helpers ===================== */
const isNowaValid = computed(() => /^8\d{8,12}$/.test(waSubscriber.value))

const onNowaInput = (raw: string | number) => {
  let val = String(raw ?? '')
  val = val.replace(/[^\d]/g, '')
  if (val.startsWith('62')) val = val.slice(2)
  else if (val.startsWith('0')) val = val.slice(1)
  if (val && !val.startsWith('8')) val = ''
  waSubscriber.value = val.slice(0, 13)
  loginuser.nowa = waSubscriber.value ? `62${waSubscriber.value}` : ''
}
const canSubmitSignup = computed(() => {
  const base = !!loginuser.name && !!loginuser.email && !!loginuser.password && !!loginuser.password_confirmation
  return base && isNowaValid.value
})
const startCooldown = (sec = 60) => {
  resendCooldown.value = sec
  if (cooldownTimer) clearInterval(cooldownTimer)
  cooldownTimer = setInterval(() => {
    resendCooldown.value--
    if (resendCooldown.value <= 0) clearInterval(cooldownTimer)
  }, 1000)
}

/** Ambil user_id dari berbagai kemungkinan bentuk respons backend */
const pickUserId = (d: any): number | null => {
  const cands = [
    d?.result?.user_id,
    d?.response?.result?.user_id,      // bentuk yg kamu pakai
    d?.result?.data?.id,
    d?.response?.data?.id,
    d?.result?.data?.data?.id,
    d?.id,
  ]
  for (const v of cands) {
    if (v !== undefined && v !== null && String(v).match(/^\d+$/)) return Number(v)
  }
  return null
}

const handleSignup = async () => {
  isError.value = false
  errorMsg.value = ''

  if (!canSubmitSignup.value) {
    isError.value = true
    errorMsg.value = 'Lengkapi data dan ketik nomor WhatsApp mulai dari angka 8.'
    return
  }
  if (loginuser.password !== loginuser.password_confirmation) {
    isError.value = true
    errorMsg.value = 'Password dan Confirm Password tidak cocok.'
    return
  }

  isLoading.value = true
  try {
    const { data } = await axios.post(
      `${import.meta.env.VITE_API_BASE_URL}auth/register`,
      { loginuser }
    )

    // 1. Cek apakah statusnya 200 (Sukses)
    const isSuccess =
      data?.status === 200 ||
      data?.metaData?.code === 200 ||
      data?.response?.status === 200

    if (isSuccess) {
      const resultNode = data?.result ?? data?.response?.result ?? null
      const userId = resultNode?.user_id ?? pickUserId(data)

      if (!userId) {
        isError.value = true
        errorMsg.value = 'Registrasi sukses, tapi user_id tidak ditemukan.'
        return
      }

      pendingVerify.user_id = userId
      pendingVerify.channel = resultNode?.channel ?? loginuser.channel
      pendingVerify.purpose = resultNode?.purpose ?? 'register'
      pendingVerify.expires_at = resultNode?.expires_at ?? ''

      step.value = 1
      await nextTick()
        ; (document.getElementById('otpInput') as HTMLInputElement)?.focus()
      startCooldown(60)
      notif.success('OTP telah dikirim.')
    } else {
      // 2. Handle jika API return 400 tapi masuk ke blok 'try' (tergantung config axios)
      isError.value = true
      // Ambil pesan spesifik: "Email sudah digunakan"
      errorMsg.value = data?.response?.result || data?.metaData?.message || 'Registrasi gagal.'
    }
  } catch (e: any) {
    isError.value = true

    // 3. Handle error dari Axios (Status 400 masuk ke sini secara default)
    const serverResponse = e.response?.data

    if (serverResponse) {
      // Coba ambil pesan dari struktur response backend kamu
      errorMsg.value =
        serverResponse.response?.result || // Mengambil "Email sudah digunakan"
        serverResponse.metaData?.message ||
        serverResponse.message ||
        'Terjadi kesalahan pada server.'
    } else {
      errorMsg.value = e.message || 'Koneksi internet bermasalah.'
    }
  } finally {
    isLoading.value = false
  }
}

const verifyOtp = async () => {
  if (!pendingVerify.user_id || isNaN(Number(pendingVerify.user_id))) {
    notif.error('user_id belum ada. Ulangi proses registrasi.')
    return
  }
  if (!otpCode.value || otpCode.value.length !== 6) {
    notif.warning('Masukkan 6 digit OTP.')
    return
  }

  isLoading.value = true
  try {
    // FormData supaya Laravel $request->input(...) terbaca pasti
    const fd = new FormData()
    fd.append('user_id', String(pendingVerify.user_id))
    fd.append('code', otpCode.value)
    fd.append('purpose', pendingVerify.purpose)

    const { data } = await axios.post(
      `${import.meta.env.VITE_API_BASE_URL}auth/otp/verify`,
      fd
    )
    if (data.status === 200) {
      notif.success('Verifikasi berhasil. Silakan login.')
      router.push({ name: 'auth-login' })
    } else {
      notif.error(data.message || 'Verifikasi gagal.')
    }
  } catch (e: any) {
    notif.error(e.response?.data?.message || e.message)
  } finally {
    isLoading.value = false
  }
}

const resendOtp = async () => {
  if (resendCooldown.value > 0) return
  if (!pendingVerify.user_id || isNaN(Number(pendingVerify.user_id))) {
    notif.error('user_id belum ada. Ulangi proses registrasi.')
    return
  }
  try {
    const fd = new FormData()
    fd.append('user_id', String(pendingVerify.user_id))
    fd.append('channel', pendingVerify.channel)
    fd.append('purpose', pendingVerify.purpose)

    const { data } = await axios.post(
      `${import.meta.env.VITE_API_BASE_URL}auth/otp/resend`,
      fd
    )
    if (data.status === 200) {
      notif.success('OTP dikirim ulang.')
      startCooldown(60)
      pendingVerify.expires_at = data.expires_at
    } else {
      notif.error(data.message || 'Gagal kirim ulang.')
    }
  } catch (e: any) {
    notif.error(e.response?.data?.message || e.message)
  }
}

onMounted(() => {
  window.scrollTo({ top: 0, behavior: 'smooth' })
})

onBeforeUnmount(() => {
  if (cooldownTimer) clearInterval(cooldownTimer)
})
</script>

<template>
  <div class="auth-wrapper-inner iris-signup columns is-gapless">
    <div class="column is-5">
      <div class="hero is-fullheight is-white">
        <div class="hero-heading">
          <label class="dark-mode ml-auto" tabindex="0"
            @keydown.space.prevent="(e) => (e.target as HTMLLabelElement).click()">
            <input type="checkbox" :checked="!darkmode.isDark" @change="darkmode.onChange" />
            <span></span>
          </label>
          <div class="auth-logo">
            <RouterLink :to="{ name: 'index' }">
              <IrisBrand />
            </RouterLink>
          </div>
        </div>

        <div class="hero-body">
          <div class="container">
            <div class="columns">
              <div class="column is-12">
                <div class="auth-content">
                  <span class="auth-eyebrow">CUSTOMER SELF-SERVICE</span>
                  <h2>Buat akun IRIS</h2>
                  <p>Sudah punya akun?
                    <RouterLink :to="{ name: 'auth-login' }">Masuk di sini</RouterLink>
                  </p>
                </div>

                <transition name="fade">
                  <div v-if="isError" class="notification is-danger is-light mb-4">
                    {{ errorMsg }}
                  </div>
                </transition>

                <div class="auth-form-wrapper">
                  <form v-if="step === 0" @submit.prevent="handleSignup" class="glass-card">
                    <div id="signup-form" class="login-form">
                      <VField>
                        <VControl icon="feather:user">
                          <VInput class="is-lg-input" type="text" placeholder="Nama Lengkap" v-model="loginuser.name"
                            autocomplete="name" />
                        </VControl>
                      </VField>

                      <VField>
                        <div class="wa-input-wrap">
                          <i class="lnil lnil-phone wa-input-icon" aria-hidden="true"></i>
                          <span class="wa-country-prefix">62</span>
                          <span class="wa-prefix-divider" aria-hidden="true"></span>
                          <VInput class="is-lg-input wa-subscriber-input" type="tel" inputmode="numeric"
                            maxlength="13" placeholder="81210000284" :modelValue="waSubscriber"
                            @update:modelValue="onNowaInput" autocomplete="tel-national"
                            aria-label="Nomor WhatsApp setelah awalan 62"
                            :class="{ 'is-danger': waSubscriber && !isNowaValid }" />
                        </div>
                        <p class="help is-info wa-help">
                          Awalan <b>62</b> sudah terisi. Ketik langsung mulai dari <b>8</b>.
                          Contoh: <b>81210000284</b>
                        </p>
                        <p v-if="waSubscriber && !isNowaValid" class="help is-danger">
                          Nomor harus dimulai angka 8 dan terdiri dari 9–13 digit.
                        </p>
                      </VField>

                      <VField>
                        <VControl icon="feather:mail">
                          <VInput class="is-lg-input" type="email" placeholder="Email" v-model="loginuser.email"
                            autocomplete="email" />
                        </VControl>
                      </VField>

                      <VField>
                        <label class="label is-size-7 has-text-grey mb-1">Password</label>
                        <div class="pv-wrapper">
                          <Password v-model="loginuser.password" toggleMask promptLabel="Masukkan password"
                            weakLabel="Lemah" mediumLabel="Sedang" strongLabel="Kuat"
                            inputStyle="width:100%;height:56px;border-radius:12px;padding-left:44px;font-size:1rem;"
                            :feedback="true" />
                          <i class="lnil lnil-lock-alt pv-icon"></i>
                        </div>
                        <p class="help is-info">
                          Minimal 8 karakter, campur huruf, angka, dan simbol.
                        </p>
                      </VField>

                      <VField>
                        <label class="label is-size-7 has-text-grey mb-1">Confirm Password</label>
                        <div class="pv-wrapper">
                          <Password v-model="loginuser.password_confirmation" toggleMask :feedback="false"
                            inputStyle="width:100%;height:56px;border-radius:12px;padding-left:44px;font-size:1rem;" />
                          <i class="lnil lnil-lock-alt pv-icon"></i>
                        </div>
                      </VField>

                      <div class="field mt-3">
                        <label class="label is-size-7 has-text-grey mb-2">Kirim OTP via</label>
                        <div class="otp-channel">
                          <label class="otp-chip otp-chip--email" :class="{ 'is-active': loginuser.channel === 'email' }">
                            <input type="radio" value="email" v-model="loginuser.channel" />
                            <img src="/brands/gmail.png" alt="" aria-hidden="true" />
                            <span>Email</span>
                            <span class="otp-check" aria-hidden="true">&#10003;</span>
                          </label>

                          <label class="otp-chip otp-chip--wa" :class="{ 'is-active': loginuser.channel === 'wa' }">
                            <input type="radio" value="wa" v-model="loginuser.channel" />
                            <img src="/brands/whatsapp.png" alt="" aria-hidden="true" />
                            <span>WhatsApp</span>
                            <span class="otp-check" aria-hidden="true">&#10003;</span>
                          </label>
                        </div>
                      </div>

                      <div class="login mt-4">
                        <VButton type="submit" color="info" bold fullwidth raised :loading="isLoading"
                          :disabled="!canSubmitSignup || isLoading">
                          Buat akun customer
                        </VButton>
                      </div>
                    </div>
                  </form>

                  <div v-else class="glass-card">
                    <div class="has-text-centered mb-4">
                      <h3 class="title is-4 mb-2">Verifikasi Akun</h3>
                      <p class="is-size-7">
                        Kode OTP dikirim ke
                        <b v-if="pendingVerify.channel === 'email'">{{ loginuser.email }}</b>
                        <b v-else>{{ loginuser.nowa }}</b>.
                        Berlaku 10 menit.
                      </p>
                    </div>

                    <VField>
                      <VControl icon="feather:key">
                        <VInput id="otpInput" class="is-lg-input otp-input" type="text" maxlength="6"
                          placeholder="Masukkan 6 digit OTP" v-model="otpCode" />
                      </VControl>
                    </VField>

                    <div class="buttons mt-3">
                      <VButton color="info" bold fullwidth :loading="isLoading" @click="verifyOtp">
                        Verifikasi
                      </VButton>
                      <VButton color="info" outlined fullwidth :disabled="resendCooldown > 0" @click="resendOtp">
                        <template v-if="resendCooldown > 0">Kirim ulang dalam {{ resendCooldown }} dtk</template>
                        <template v-else>Kirim Ulang OTP</template>
                      </VButton>
                    </div>

                    <p class="has-text-centered is-size-7 mt-2">
                      Salah email/nomor? <RouterLink :to="{ name: 'auth-signup-1' }">Daftar ulang</RouterLink>
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="column login-column is-7 is-hidden-mobile iris-signup-stage">
      <AuthIrisStage mode="signup" />
    </div>
  </div>
</template>

<style lang="scss">
/* Background iris.png untuk kolom kanan */
.hero-banner-custom {
  background-image: url('/@src/assets/illustrations/login/iris2.webp');
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  position: relative;
}

/* Memastikan gambar register tampil manis di atas iris */
.register-img {
  position: relative;
  z-index: 2;
  max-width: 100%;
  margin: 0 auto;
}

.is-lg-input,
.login-form .control .input {
  height: 56px !important;
  border-radius: 12px !important;
  font-size: 1rem !important;
}

.no-spinner {

  &::-webkit-outer-spin-button,
  &::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
  }

  -moz-appearance: textfield;
}

.wa-input-wrap {
  position: relative;
}

.wa-input-icon,
.wa-country-prefix,
.wa-prefix-divider {
  position: absolute;
  top: 50%;
  z-index: 3;
  transform: translateY(-50%);
}

.wa-input-icon {
  left: 16px;
  color: var(--placeholder);
  font-size: 20px;
}

.wa-country-prefix {
  left: 49px;
  color: var(--dark-text);
  font-size: 1rem;
  font-weight: 700;
}

.wa-prefix-divider {
  left: 78px;
  width: 1px;
  height: 26px;
  background: var(--fade-grey-dark-3);
}

.wa-subscriber-input.input,
.wa-subscriber-input input.input {
  padding-left: 94px !important;
}

.wa-help {
  line-height: 1.45;
}

.glass-card {
  background: rgba(255, 255, 255, 0.75);
  backdrop-filter: blur(10px);
  border-radius: 16px;
  padding: 22px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.6);
}

.is-dark .glass-card {
  background: rgba(20, 22, 26, 0.6);
  border-color: rgba(255, 255, 255, 0.08);
}

/* PrimeVue Password: icon kiri seperti VInput */
.pv-wrapper {
  position: relative;
}

.pv-wrapper .pv-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--placeholder);
  font-size: 20px;
  z-index: 2;
}

/* Samakan look PrimeVue input */
.p-inputtext {
  border-radius: 12px !important;
  height: 56px !important;
  padding-left: 44px !important;
  font-size: 1rem !important;
}

/* OTP input lebih center */
.otp-input.input,
.otp-input input.input {
  text-align: center !important;
  letter-spacing: 4px;
  font-weight: 600;
}

/* Chip selector untuk channel OTP */
.otp-channel {
  display: flex;
  gap: 10px;
  flex-wrap: nowrap;
}

.otp-chip {
  position: relative;
  display: flex;
  flex: 1 1 0;
  align-items: center;
  gap: 8px;
  min-height: 58px;
  padding: 10px 38px 10px 14px;
  border: 1px solid var(--fade-grey-dark-3);
  border-radius: 16px;
  cursor: pointer;
  user-select: none;
  transition: .2s ease;
  color: var(--dark-text);
  background: var(--white);
}

.otp-chip input {
  display: none;
}

.otp-chip img {
  width: 27px;
  height: 27px;
  object-fit: contain;
}

.otp-check {
  position: absolute;
  top: 9px;
  right: 10px;
  display: none;
  width: 20px;
  height: 20px;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  color: #fff;
  font-size: 12px;
  font-weight: 800;
}

.otp-chip.is-active .otp-check {
  display: flex;
}

.otp-chip--email.is-active {
  background: #fff5f4;
  border-color: #ea4335;
  color: var(--dark-text);
}

.otp-chip--email.is-active .otp-check {
  background: #ea4335;
}

.otp-chip--wa.is-active {
  background: #f0fdf4;
  border-color: #16a34a;
  color: var(--dark-text);
}

.otp-chip--wa.is-active .otp-check {
  background: #16a34a;
}

.is-dark .otp-chip {
  background: var(--dark-sidebar-light-2);
  border-color: var(--dark-sidebar-light-10);
  color: var(--light-text);
}

.is-dark .otp-chip--email.is-active {
  background: rgba(234, 67, 53, 0.14);
  border-color: #ea4335;
  color: var(--light-text);
}

.is-dark .otp-chip--wa.is-active {
  background: rgba(22, 163, 74, 0.14);
  border-color: #22c55e;
  color: var(--light-text);
}

/* Header & layout kecil */
.auth-wrapper-inner .hero-heading {
  display: flex;
  align-items: center;
  padding: 16px 20px 0;
}

.auth-content h2 {
  font-weight: 800;
  font-size: 28px;
  margin-bottom: 4px;
}

.auth-content p {
  color: var(--muted-grey);
}

.auth-form-wrapper {
  margin-top: 12px;
}

/* Responsif */
@media (max-width: 1023px) {
  .glass-card {
    margin-bottom: 24px;
  }
}

@media (max-width: 767px) {
  .auth-wrapper-inner > .column.is-5 {
    width: 100%;
    flex: none;
  }

  .auth-wrapper-inner .hero-heading {
    max-width: 100%;
    padding-right: 18px;
    padding-left: 18px;
  }

  .auth-content,
  .auth-form-wrapper {
    max-width: 100% !important;
  }

  .glass-card {
    padding: 18px;
  }
}

/* IRIS customer registration shell */
.iris-signup {
  min-height: 100vh;
  align-items: stretch;
  color: #102b4e;
  background: #f7fafc;
  font-family: 'DM Sans', Arial, sans-serif;

  > .column.is-5 {
    width: 43%;
    flex: none;
    background: #fff;
    border-right: 1px solid #e4edf3;
  }

  > .column.is-7 {
    width: 57%;
    flex: none;
  }

  .hero.is-white {
    min-height: 100vh;
    background: #fff;
  }

  .hero-heading {
    max-width: 520px;
    padding: 27px 28px 0;
    margin-inline: auto;
    justify-content: flex-start;
  }

  .hero-heading .auth-logo {
    justify-content: flex-start;
  }

  .hero-heading .auth-logo a {
    color: #102b4e;
  }

  .hero-heading .auth-logo :is(.iris-brand) {
    display: inline-flex;
  }

  .hero-heading .auth-logo .iris-brand-mark {
    width: 31px;
    height: 31px;
  }

  .hero-heading .auth-logo .iris-brand-mark img {
    width: 48px;
    left: -8px;
  }

  .hero-heading .auth-logo .iris-brand-name {
    font-size: 27px;
  }

  .hero-heading .dark-mode {
    top: 24px;
    right: 28px;
  }

  .hero-body {
    align-items: flex-start;
    padding: 35px 28px 50px;
  }

  .hero-body > .container {
    width: 100%;
    max-width: 460px;
  }

  .auth-content,
  .auth-form-wrapper {
    max-width: 430px;
  }

  .auth-content {
    margin: 0 auto 20px;
    text-align: left;
  }

  .auth-eyebrow {
    display: block;
    margin-bottom: 13px;
    color: #198caf;
    font-size: 8px;
    font-weight: 700;
    letter-spacing: 1.8px;
  }

  .auth-content h2 {
    margin: 0 0 8px;
    color: #102b4e;
    font-family: Manrope, Arial, sans-serif;
    font-size: clamp(31px, 2.3vw, 40px);
    font-weight: 600;
    line-height: 1.15;
    letter-spacing: -1.4px;
  }

  .auth-content p {
    color: #718695;
    font-size: 13px;
  }

  .auth-content a {
    color: #078cc0;
    font-weight: 700;
  }

  .glass-card {
    padding: 25px;
    border: 1px solid #e1ebf1;
    border-radius: 13px;
    background: #fbfdfe;
    box-shadow: 0 18px 50px #1538580c;
    backdrop-filter: none;
  }

  .login-form .field:not(:last-child) {
    margin-bottom: 15px;
  }

  .is-lg-input,
  .login-form .control .input,
  .p-inputtext {
    height: 54px !important;
    border-color: #d7e3eb !important;
    border-radius: 10px !important;
    color: #102b4e;
    background: #fff;
    box-shadow: none !important;
  }

  .is-lg-input:focus,
  .login-form .control .input:focus,
  .p-inputtext:focus {
    border-color: #34b9d8 !important;
    box-shadow: 0 0 0 3px #35bad814 !important;
  }

  .help.is-info {
    color: #43839c !important;
    font-size: 10px;
  }

  .otp-chip {
    min-height: 54px;
    border-radius: 10px;
  }

  .login .button {
    min-height: 50px;
    border-radius: 9px;
    background: #102f52;
  }

  .iris-signup-stage {
    position: relative;
    min-height: 100vh;
    background: #f4f9fc;
  }
}

.is-dark .iris-signup {
  background: #071827;

  > .column.is-5,
  .hero.is-white {
    background: #091e31;
    border-color: #183850;
  }

  .hero-heading .auth-logo a,
  .auth-content h2 {
    color: #eaf6fb;
  }

  .glass-card {
    background: #0d263a;
    border-color: #1b4059;
  }
}

@media (max-width: 1023px) {
  .iris-signup {
    > .column.is-5 {
      width: 52%;
    }

    > .column.is-7 {
      width: 48%;
    }
  }
}

@media (max-width: 767px) {
  .iris-signup {
    > .column.is-5 {
      width: 100%;
      border-right: 0;
    }

    .hero-heading {
      padding: 22px 18px 0;
    }

    .hero-body {
      padding: 30px 17px 35px;
    }

    .auth-content {
      margin: 0 auto 20px !important;
      text-align: left !important;
    }

    .glass-card {
      padding: 18px;
    }
  }
}
</style>
