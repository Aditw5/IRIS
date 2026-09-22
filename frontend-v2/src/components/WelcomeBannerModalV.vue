<script setup lang="ts">
import { computed, ref, watch, onUnmounted } from 'vue'

type Props = {
  modelValue: boolean
  image?: string
  images?: string[]
  title?: string
  subtitle?: string
  storageKey?: string
  persistentKey?: boolean
  interval?: number
}

const props = withDefaults(defineProps<Props>(), {
  title: 'Seputar Layanan',
  subtitle: 'Info terbaru sebelum menggunakan web',
  storageKey: 'ulab_welcome_banner_v1',
  persistentKey: true,
  interval: 5000, // Default 5 detik agar progress bar terlihat enak
  image: '',
  images: () => []
})

const emit = defineEmits<{
  (e: 'update:modelValue', v: boolean): void
  (e: 'closed'): void
}>()

// --- Logic Data Gambar ---
const activeIndex = ref(0)
const slideDirection = ref('next')
let timer: ReturnType<typeof setInterval> | null = null

const imageList = computed(() => {
  if (props.images && props.images.length > 0) return props.images
  if (props.image) return [props.image]
  return []
})

const currentImage = computed(() => {
  if (imageList.value.length === 0) return '/images/other/no_image.jpg'
  return imageList.value[activeIndex.value]
})

const isSlider = computed(() => imageList.value.length > 1)
const transitionName = computed(() => slideDirection.value === 'next' ? 'slide-left' : 'slide-right')

// --- Logic Slider ---
function nextSlide() {
  if (!isSlider.value) return
  slideDirection.value = 'next'
  activeIndex.value = (activeIndex.value + 1) % imageList.value.length
  resetTimer()
}

function prevSlide() {
  if (!isSlider.value) return
  slideDirection.value = 'prev'
  activeIndex.value = activeIndex.value === 0 
    ? imageList.value.length - 1 
    : activeIndex.value - 1
  resetTimer()
}

function startAutoSlide() {
  stopAutoSlide()
  if (isSlider.value && props.modelValue) {
    timer = setInterval(() => {
      slideDirection.value = 'next'
      activeIndex.value = (activeIndex.value + 1) % imageList.value.length
    }, props.interval)
  }
}

function stopAutoSlide() {
  if (timer) {
    clearInterval(timer)
    timer = null
  }
}

function resetTimer() {
  stopAutoSlide()
  startAutoSlide()
}

// --- Watcher & Lifecycle ---
watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    activeIndex.value = 0
    slideDirection.value = 'next'
    startAutoSlide()
  } else {
    stopAutoSlide()
  }
})

onUnmounted(() => {
  stopAutoSlide()
})

// --- Modal Logic ---
const open = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

function closeOnly() {
  open.value = false
  emit('closed')
}

function dontShowAgain() {
  if (props.persistentKey) {
    localStorage.setItem(props.storageKey, '1')
  }
  closeOnly()
}
</script>

<template>
  <VModal
    :open="open"
    :title="title"
    size="medium"
    actions="right"
    cancelLabel="Tutup"
    @close="closeOnly"
    class="welcome-banner-modal"
  >
    <template #content>
      <div class="wbm-wrap">
        <div class="wbm-image-wrap">
          <Transition :name="transitionName">
            <img :key="currentImage" :src="currentImage" alt="welcome" />
          </Transition>

          <template v-if="isSlider">
            <button class="nav-btn prev" @click.stop="prevSlide">
              <i class="iconify" data-icon="feather:chevron-left"></i>
            </button>
            <button class="nav-btn next" @click.stop="nextSlide">
              <i class="iconify" data-icon="feather:chevron-right"></i>
            </button>

            <div class="progress-container">
              <div 
                v-for="(img, idx) in imageList" 
                :key="idx"
                class="progress-track"
                @click="activeIndex = idx; resetTimer()"
              >
                <div 
                  class="progress-fill"
                  :class="{ 
                    'is-completed': idx < activeIndex,
                    'is-active': idx === activeIndex 
                  }"
                  :style="idx === activeIndex ? { animationDuration: `${interval}ms` } : {}"
                ></div>
              </div>
            </div>
          </template>
        </div>
      </div>
    </template>

    <template #action>
      <VButton color="info" outlined @click="dontShowAgain">
        Jangan tampilkan lagi
      </VButton>
    </template>
  </VModal>
</template>

<style scoped>
/* --- Modal Layout --- */
:deep(.modal-card) {
  max-width: 480px !important;
  width: 100% !important;
  max-height: 90vh !important;
  display: flex !important;
  flex-direction: column !important;
}

:deep(.modal-card-body) {
  overflow-y: auto !important;
  overflow-x: hidden !important;
  padding: 1.25rem !important;
}

.wbm-wrap {
  display: flex;
  flex-direction: column;
  gap: 15px;
  align-items: center;
}

.wbm-image-wrap {
  width: 100%;
  height: 500px;
  background: var(--widget-grey);
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid var(--fade-grey-dark-3);
  position: relative;
}

.wbm-image-wrap img {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: contain;
  display: block;
}

/* --- Navigation Buttons --- */
.nav-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background-color: rgba(255, 255, 255, 0.4); /* Lebih transparan agar elegan */
  border: none;
  border-radius: 50%;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 20;
  transition: all 0.2s ease;
  color: #111;
  backdrop-filter: blur(2px);
}

.nav-btn:hover {
  background-color: rgba(255, 255, 255, 0.9);
}

.nav-btn.prev { left: 10px; }
.nav-btn.next { right: 10px; }

/* ========================================= */
/* === PROGRESS BAR INDICATORS (Garis) ===== */
/* ========================================= */
.progress-container {
  position: absolute;
  bottom: 15px;
  left: 0;
  width: 100%;
  padding: 0 15px;
  display: flex;
  gap: 6px; /* Jarak antar garis */
  z-index: 30;
  box-sizing: border-box;
}

.progress-track {
  flex: 1; /* Agar panjangnya terbagi rata */
  height: 4px; /* Ketebalan garis */
  background-color: rgba(255, 255, 255, 0.3); /* Warna track abu transparan */
  border-radius: 2px;
  cursor: pointer;
  overflow: hidden;
  position: relative;
}

/* Area yang mengisi (berjalan) */
.progress-fill {
  height: 100%;
  width: 0%;
  background-color: #fff; /* Warna garis yang jalan (Putih) */
  border-radius: 2px;
}

/* Jika slide sudah lewat, garis penuh */
.progress-fill.is-completed {
  width: 100%;
}

/* Jika slide sedang aktif, jalankan animasi */
.progress-fill.is-active {
  width: 0%;
  animation-name: progressLoading;
  animation-timing-function: linear; /* Linear agar jalannya konstan */
  animation-fill-mode: forwards;
}

/* Definisi animasi jalan dari 0 ke 100 */
@keyframes progressLoading {
  from { width: 0%; }
  to { width: 100%; }
}

/* ========================================= */
/* === TRANSISI GAMBAR (Slide Effect) ====== */
/* ========================================= */
.slide-left-enter-active,
.slide-left-leave-active,
.slide-right-enter-active,
.slide-right-leave-active {
  transition: transform 0.5s ease-out;
}

.slide-left-enter-from { transform: translateX(100%); }
.slide-left-leave-to { transform: translateX(-100%); }

.slide-right-enter-from { transform: translateX(-100%); }
.slide-right-leave-to { transform: translateX(100%); }

/* Mobile Responsive */
@media (max-width: 767px) {
  .wbm-image-wrap { height: 400px; }
}
</style>