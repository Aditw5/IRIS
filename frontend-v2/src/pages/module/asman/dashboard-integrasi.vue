<template>
  <div class="dashboard-container">

    <div class="custom-nav-wrapper">
      <div class="custom-nav-track">

        <button class="nav-item" :class="{ 'is-active': activeTab === 'integrasi' }" @click="activeTab = 'integrasi'">
          Dashboard Integrasi
        </button>

        <button class="nav-item" :class="{ 'is-active': activeTab === 'synergy' }" @click="activeTab = 'synergy'">
          Synergy
        </button>

        <button class="nav-item" :class="{ 'is-active': activeTab === 'penilaian-pelanggan' }"
          @click="activeTab = 'penilaian-pelanggan'">
          Penilaian Pelanggan
        </button>

        <button class="nav-item" :class="{ 'is-active': activeTab === 'dashboard-data-pbj' }"
          @click="activeTab = 'dashboard-data-pbj'">
          Dashboard Data PBJ
        </button>

        <button class="nav-item" :class="{ 'is-active': activeTab === 'monitoring-alat-surkes' }"
          @click="activeTab = 'monitoring-alat-surkes'">
          Monitoring Alat Surkes
        </button>

      </div>
    </div>

    <div class="content-area">
      <Transition name="fade" mode="out-in">
        <div v-if="activeTab === 'integrasi'" key="integrasi">
          <Integrasi />
        </div>

        <div v-else-if="activeTab === 'synergy'" key="synergy">
          <SynergyPagi />
        </div>

        <div v-else-if="activeTab === 'penilaian-pelanggan'" key="penilaian">
          <PenilaianPelanggan />
        </div>

        <div v-else-if="activeTab === 'dashboard-data-pbj'" key="dashboard-data-pbj">
          <DashboardDataPbj />
        </div>

        <div v-else-if="activeTab === 'monitoring-alat-surkes'" key="monitoring-alat-surkes">
          <MonitoringAlatSurkes />
        </div>
      </Transition>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useHead } from '@vueuse/head'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import SynergyPagi from './../laporan/synergy-pagi.vue'
import Integrasi from './dashboard-chart.vue'
import PenilaianPelanggan from './../mutu/penilaian-pelanggan.vue'
import DashboardDataPbj from './../pbj/dashboard-data-pbj.vue'
import MonitoringAlatSurkes from './../rbk/monitoring-alat-surkes.vue'

const activeTab = ref('integrasi')

useHead({
  title: 'Dashboard Integrasi - ' + import.meta.env.VITE_PROJECT,
})

useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(true)
</script>

<style scoped lang="scss">
.custom-nav-wrapper {
  display: flex;
  justify-content: center;
  width: 100%;
  margin-bottom: 24px;
  padding-top: 10px;
}

.custom-nav-track {
  display: flex;
  background-color: #f1f5f9;
  padding: 6px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  width: 100%;
  max-width: 1000px;
  gap: 8px;
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.03);
}

.nav-item {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 12px 16px;
  border: none;
  border-radius: 8px;
  background: transparent;
  cursor: pointer;
  font-family: inherit;
  font-weight: 600;
  font-size: 14px;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  color: #64748b;
  transition: all 0.3s ease;
}

.nav-item:not(.is-active):hover {
  background-color: #ffffff;
  color: #37b67b;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.nav-item.is-active {
  background-color: #37b67b;
  color: #ffffff;
  font-weight: 700;
  box-shadow: 0 4px 12px rgba(55, 182, 123, 0.35);
  transform: translateY(-1px);
}

@media (max-width: 600px) {
  .custom-nav-track {
    flex-direction: column;
    gap: 6px;
  }

  .nav-item {
    width: 100%;
    padding: 14px;
  }
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>