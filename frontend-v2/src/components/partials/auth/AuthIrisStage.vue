<script setup lang="ts">
import { computed, ref } from 'vue'
import IrisBrand from '/@src/components/partials/landing/IrisBrand.vue'
import IrisIcon from '/@src/components/partials/landing/IrisIcon.vue'
import IrisScene from '/@src/components/partials/landing/IrisScene.vue'

const props = withDefaults(defineProps<{ mode?: 'login' | 'signup' }>(), { mode: 'login' })
const paused = ref(false)
const content = computed(() =>
  props.mode === 'signup'
    ? {
        eyebrow: 'CUSTOMER SELF-SERVICE',
        title: 'Mulai dari akun Anda.',
        text: 'Daftarkan alat, buat order kalibrasi, dan pantau setiap progres melalui ruang kerja milik Anda sendiri.',
      }
    : {
        eyebrow: 'ONE CONNECTED SYSTEM',
        title: 'Kembali ke pusat kendali.',
        text: 'Kalibrasi, repair, mutu, serta dokumen Anda tetap terhubung dalam satu sistem yang tertelusur.',
      }
)
</script>

<template>
  <section class="auth-iris-stage" :class="`is-${mode}`" aria-label="Visual ekosistem IRIS">
    <div class="stage-grid" aria-hidden="true"></div>
    <RouterLink :to="{ name: 'index' }" class="stage-brand"><IrisBrand /></RouterLink>
    <div class="stage-coordinate" aria-hidden="true">IRIS CORE / AUTH <span>CONNECTED INTELLIGENCE</span></div>
    <div class="stage-wordmark" aria-hidden="true">
      <img src="/IRIS.png" alt="" width="1024" height="1536" decoding="async" />
    </div>
    <div class="stage-halo" aria-hidden="true"></div>
    <IrisScene :paused="paused" />
    <span class="stage-badge badge-one"><IrisIcon name="shield" :size="17" /> Data terkendali</span>
    <span class="stage-badge badge-two"><IrisIcon name="calibration" :size="17" /> Proses tertelusur</span>
    <div class="stage-content">
      <span><i></i>{{ content.eyebrow }}</span>
      <h2>{{ content.title }}</h2>
      <p>{{ content.text }}</p>
      <div class="stage-capabilities">
        <span>Kalibrasi</span><span>Repair</span><span>Mutu</span><span>Cloud</span>
      </div>
    </div>
    <button class="stage-motion" type="button" :aria-pressed="paused" @click="paused = !paused">
      <IrisIcon :name="paused ? 'play' : 'pause'" :size="13" />
      {{ paused ? 'Motion off' : 'Motion on' }}
    </button>
  </section>
</template>

<style scoped>
.auth-iris-stage {
  position: sticky;
  top: 0;
  min-height: 100vh;
  overflow: hidden;
  isolation: isolate;
  color: #102b4e;
  background: #f4f9fc;
}
.stage-grid {
  position: absolute;
  inset: 0;
  z-index: -4;
  opacity: 0.42;
  background-image: linear-gradient(#0d89bb0c 1px, transparent 1px),
    linear-gradient(90deg, #0d89bb0c 1px, transparent 1px);
  background-size: 64px 64px;
  mask-image: radial-gradient(ellipse at 58% 43%, #000 20%, transparent 80%);
}
.stage-brand {
  position: absolute;
  z-index: 5;
  top: 35px;
  left: 45px;
  color: #102b4e;
}
.stage-brand :deep(.iris-brand-mark) {
  width: 32px;
  height: 32px;
}
.stage-brand :deep(.iris-brand-mark img) {
  width: 49px;
  left: -8px;
}
.stage-brand :deep(.iris-brand-name) {
  font-size: 27px;
}
.stage-coordinate {
  position: absolute;
  z-index: 4;
  top: 44px;
  right: 42px;
  display: flex;
  gap: 22px;
  color: #80a1b4;
  font: 600 7px/1 Arial, sans-serif;
  letter-spacing: 1.6px;
}
.stage-wordmark {
  position: absolute;
  z-index: -2;
  top: 44%;
  left: 50%;
  width: 92%;
  aspect-ratio: 820 / 322;
  overflow: hidden;
  transform: translate(-50%, -50%);
  opacity: 0.94;
}
.stage-wordmark img {
  position: absolute;
  width: 124.87805%;
  max-width: none;
  height: auto;
  left: -14.14634%;
  top: -232.29814%;
}
.stage-halo {
  position: absolute;
  inset: 8% 0 22%;
  z-index: 0;
  pointer-events: none;
  /* Match the landing page: softly blur the lettering behind the sharp 3D logo. */
  -webkit-backdrop-filter: blur(12px);
  backdrop-filter: blur(12px);
  -webkit-mask-image: radial-gradient(
    ellipse 27% 43% at 50% 50%,
    #000 40%,
    #000a 65%,
    transparent 100%
  );
  mask-image: radial-gradient(
    ellipse 27% 43% at 50% 50%,
    #000 40%,
    #000a 65%,
    transparent 100%
  );
}
:deep(.iris-scene) {
  z-index: 1;
  inset: 8% 8% 22%;
}
.stage-badge {
  position: absolute;
  z-index: 3;
  display: flex;
  align-items: center;
  gap: 9px;
  min-height: 42px;
  padding: 9px 13px;
  border: 1px solid #fff;
  border-radius: 9px;
  background: #fffffff2;
  box-shadow: 0 14px 35px #16446d12;
  color: #49677d;
  font: 600 10px/1.2 Arial, sans-serif;
}
.stage-badge svg {
  color: #09a8d6;
}
.badge-one {
  top: 29%;
  right: 8%;
}
.badge-two {
  top: 61%;
  left: 7%;
}
.stage-content {
  position: absolute;
  z-index: 4;
  right: 45px;
  bottom: 45px;
  width: min(395px, 48%);
}
.stage-content > span {
  display: flex;
  align-items: center;
  gap: 9px;
  color: #3484a2;
  font: 700 8px/1 Arial, sans-serif;
  letter-spacing: 1.7px;
}
.stage-content > span i {
  width: 18px;
  height: 2px;
  background: #20bbdc;
}
.stage-content h2 {
  margin: 13px 0 9px;
  font: 600 clamp(27px, 2.4vw, 39px)/1.18 Manrope, Arial, sans-serif;
  letter-spacing: -1.4px;
}
.stage-content p {
  margin: 0;
  color: #657d90;
  font: 400 12px/1.8 'DM Sans', Arial, sans-serif;
}
.stage-capabilities {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 17px;
}
.stage-capabilities span {
  padding: 6px 9px;
  border: 1px solid #d7e7ef;
  border-radius: 999px;
  color: #5c788a;
  background: #ffffffa8;
  font: 600 8px/1 Arial, sans-serif;
  letter-spacing: 0.3px;
}
.stage-motion {
  position: absolute;
  z-index: 5;
  left: 45px;
  bottom: 46px;
  display: flex;
  align-items: center;
  gap: 7px;
  min-height: 34px;
  padding: 7px 11px;
  border: 1px solid #bdd9e7;
  border-radius: 999px;
  color: #638297;
  background: #ffffffa8;
  font: 500 8px/1 Arial, sans-serif;
  cursor: pointer;
}
@media (max-width: 1180px) {
  .stage-wordmark { width: 104%; }
  .stage-halo { inset: 8% 0 24%; }
  :deep(.iris-scene) { inset: 8% 0 24%; }
  .stage-content { right: 30px; bottom: 35px; width: 52%; }
  .stage-motion { left: 30px; bottom: 35px; }
}
@media (prefers-reduced-motion: reduce) {
  .stage-motion { display: none; }
}
</style>
