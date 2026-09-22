<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRegisterSW } from 'virtual:pwa-register/vue'

const { needRefresh, updateServiceWorker } = useRegisterSW({
  immediate: true,
})

const show = ref(false)

watch(needRefresh, (val) => {
  if (val) {
    show.value = true
  }
})

const onClose = () => {
  show.value = false
}

const onReload = () => {
  show.value = false
  updateServiceWorker(true)
}
</script>
<template>
  <Transition name="fade">
    <div
      v-if="show"
      class="pwa-update-overlay"
    >
      <div class="pwa-update-box">
        <p class="pwa-update-text">
          Aplikasi yang Anda pakai bukan versi terbaru.
          Klik <strong>OK</strong> untuk memuat versi terbaru.
        </p>
        <div class="buttons is-right mt-3">
          <button class="button is-light" @click="onClose">
            Nanti saja
          </button>
          <button class="button is-primary" @click="onReload">
            OK
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.pwa-update-overlay {
  position: fixed;
  inset: 0;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  pointer-events: none;
  z-index: 9999;
}

.pwa-update-box {
  pointer-events: auto;
  margin-bottom: 1.5rem;
  max-width: 420px;
  width: calc(100% - 2rem);
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
  padding: 1.25rem 1.5rem;
}

.pwa-update-text {
  margin: 0;
}

/* animasi simple */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
