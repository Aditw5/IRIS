<template>
  <div class="business-dashboard hr-dashboard">
    <div class="columns is-multiline">
      <div class="column is-3">
        <VField class="is-autocomplete-select" label="Ruangan">
          <VControl icon="feather:search">
            <AutoComplete v-model="item.ruanganfk" :suggestions="d_ruangan" @complete="fetchRuangan($event)"
              :optionLabel="'label'" :dropdown="true" :minLength="3" :appendTo="'body'" :loadingIcon="'pi pi-spinner'"
              :field="'label'" placeholder="ketik Ruangan" />
          </VControl>
        </VField>
      </div>
      <div class="column is-1 mt-5">
        <VIconButton type="button" color="success" class="is-rounded" rounded raised icon="fas fa-search"
          @click="fetchDataSensor()" :loading="isLoading">
        </VIconButton>
      </div>

      <!-- Loading -->
      <div class="column is-12" v-if="isLoading">
        <div class="sensor-empty">
          <VPlaceload class="mb-2" />
          <VPlaceloadText :lines="2" width="90%" last-line-width="40%" />
        </div>
      </div>

      <!-- Empty -->
      <div class="column is-12" v-else-if="!rooms.length">
        <VCard class="sensor-card">
          <div class="sensor-card__header">
            <h4 class="sensor-card__title">
              <i class="fas fa-microchip mr-2"></i> Sensor Ruangan
            </h4>
          </div>
          <div class="sensor-empty">
            <p>Tidak ada data sensor yang tersedia.</p>
          </div>
        </VCard>
      </div>

      <!-- ===================== BAGIAN 1: DATA RUANGAN ===================== -->
      <div class="column is-12" v-else>
        <VCard>
          <div class="p-4">
            <div class="is-flex is-justify-content-space-between is-align-items-center">
              <h4 class="sensor-card__title">
                <i class="fas fa-list mr-2"></i> Data Sensor per Ruangan (terbaru)
              </h4>
            </div>

            <div class="columns is-multiline mt-3">
              <div class="column is-12-tablet is-6-desktop is-4-widescreen" v-for="room in roomsSorted"
                :key="room.room_id">
                <VCard class="sensor-card">
                  <div class="sensor-card__header">
                    <div>
                      <h4 class="sensor-card__title">
                        <i class="fas fa-microchip mr-2"></i> {{ room.namaruangan ?? '-' }}
                      </h4>
                      <div class="sensor-card__meta">
                        <VTag color="primary" rounded>Terakhir: {{ formatTime(room.created_at) }}</VTag>
                      </div>
                    </div>
                  </div>

                  <div class="sensor-grid">
                    <div class="sensor-item" v-for="(m, idx) in makeItems(room)" :key="idx">
                      <div class="sensor-item__icon">
                        <i :class="m.icon"></i>
                      </div>
                      <div class="sensor-item__text">
                        <div class="sensor-item__label">{{ m.label }}</div>
                        <div class="sensor-item__value">
                          {{ m.value }} <span class="unit">{{ m.unit }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </VCard>
              </div>
            </div>
          </div>
        </VCard>
      </div>

      <!-- ===================== BAGIAN 2: GRAFIK RUANGAN ===================== -->
      <div class="column is-12" v-if="Object.keys(dataChartByRoom).length">
        <div class="p-1"></div> <!-- spacer kecil -->

        <VCard>
          <div class="p-4">
            <div class="is-flex is-justify-content-space-between is-align-items-center">
              <h4 class="sensor-card__title">
                <i class="far fa-chart-line mr-2"></i> Grafik Sensor per Ruangan (≤10 terbaru)
              </h4>
            </div>

            <!-- Loop per room -->
            <div v-for="room in roomsSorted" :key="'chart-' + room.room_id" class="mt-4">
              <div class="is-flex is-justify-content-space-between is-align-items-center mb-1">
                <strong>{{ room.namaruangan }}</strong>
                <VTag rounded>
                  {{ formatTime((dataChartByRoom[room.room_id] || []).slice(-1)[0]?.created_at) }}
                </VTag>
              </div>

              <!-- Grid 5 VCard kecil (ukuran seperti awal) -->
              <div v-if="roomHasChart(room.room_id)" class="columns is-multiline">
                <div class="column is-6" v-for="cfg in sparkConfigs(room.room_id)" :key="cfg.key">
                  <VCard class="spark-card">
                    <div class="spark-card__header">
                      <strong>{{ cfg.title }}</strong>
                      <VTag rounded>{{ cfg.unit }}</VTag>
                    </div>
                    <ApexChart :id="`apex-${room.room_id}-${cfg.key}`" type="line" height="160" :options="cfg.options"
                      :series="cfg.series" />
                  </VCard>
                </div>
              </div>

              <div v-else class="has-text-centered has-text-grey py-4">
                Tidak ada data chart untuk Room #{{ room.room_id }}
              </div>

              <hr class="my-3" />
            </div>
          </div>
        </VCard>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive, onMounted, onBeforeUnmount, watch } from 'vue'
import { useHead } from '@vueuse/head'
import dayjs from 'dayjs'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import { useApi } from '/@src/composable/useApi'
import ApexChart from 'vue3-apexcharts'
import AutoComplete from 'primevue/autocomplete';

useHead({ title: 'Dashboard Sensor - ' + import.meta.env.VITE_PROJECT })
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(false)

/** ===== Types ===== **/
type SensorRow = {
  id: number
  room_id: number | string
  namaruangan: number | string
  temperature: number | null
  humidity: number | null
  pressure: number | null
  light: number | null
  uv: number | null
  created_at: string
}

const d_ruangan = ref([])
const item = reactive({
  ruanganfk: null as any
})

/** ===== LocalStorage Key ===== **/
const LS_KEY_RUANGAN = 'dashboard_sensor_ruanganfk'

const fetchRuangan = async (filter: any) => {
  await useApi().get(
    `general/dropdown/mapruangantosensor_m?select=id,namaruangan&param_search=namaruangan&query=${filter.query}&limit=10`
  ).then((response) => {
    d_ruangan.value = response
  })
}

/** ===== State ===== **/
const isLoading = ref(true)
const rooms = ref<SensorRow[]>([]) // latest per room
const dataChartByRoom = ref<Record<string | number, SensorRow[]>>({}) // ≤10 titik per room
let refreshTimer: any = null

/** ===== Persist filter ke LocalStorage ===== **/
// Muat filter saat halaman dibuka
onMounted(() => {
  try {
    const saved = localStorage.getItem(LS_KEY_RUANGAN)
    if (saved) {
      const parsed = JSON.parse(saved)
      // pastikan minimal ada struktur object (mis. {value, label})
      if (parsed && typeof parsed === 'object') {
        item.ruanganfk = parsed
      }
    }
  } catch (_) { }
  // fetch awal
  fetchDataSensor()
  // auto-refresh tiap 1 menit
  refreshTimer = setInterval(() => {
    fetchDataSensor()
  }, 60_000)
})

onBeforeUnmount(() => {
  if (refreshTimer) clearInterval(refreshTimer)
})

// Simpan perubahan filter ke localStorage
watch(
  () => item.ruanganfk,
  (val) => {
    try {
      if (val && typeof val === 'object' && Object.keys(val).length) {
        localStorage.setItem(LS_KEY_RUANGAN, JSON.stringify(val))
      } else {
        localStorage.removeItem(LS_KEY_RUANGAN)
      }
    } catch (_) { }
  },
  { deep: true }
)

/** ===== Fetch ===== **/
const fetchDataSensor = async () => {
  try {
    // optional: tampilkan loading singkat saat refresh manual/otomatis
    isLoading.value = true

    let ruanganfk = item.ruanganfk ? `?ruanganfk=${item.ruanganfk.value}` : ''
    const res = await useApi().get(`asman/get-sensor` + ruanganfk)

    // latest per room
    rooms.value = Array.isArray(res?.data) ? res.data
      : Array.isArray(res?.data?.data) ? res.data.data
        : []

    // chart data: grouped object atau flat array
    const dc = res?.dataChart ?? res?.data?.dataChart ?? []
    if (Array.isArray(dc)) {
      const grouped: Record<string | number, SensorRow[]> = {}
      dc.forEach((row: SensorRow) => {
        const key = row.room_id as any
        if (!grouped[key]) grouped[key] = []
        grouped[key].push(row)
      })
      Object.keys(grouped).forEach(k => {
        grouped[k] = grouped[k]
          .sort((a, b) => +new Date(a.created_at) - +new Date(b.created_at)) // ASC time for X
          .slice(-10)
      })
      dataChartByRoom.value = grouped
    } else if (dc && typeof dc === 'object') {
      const normalized: Record<string | number, SensorRow[]> = {}
      Object.keys(dc).forEach(k => {
        const arr = (dc as any)[k] as SensorRow[] || []
        normalized[k] = arr
          .sort((a, b) => +new Date(a.created_at) - +new Date(b.created_at))
          .slice(-10)
      })
      dataChartByRoom.value = normalized
    } else {
      dataChartByRoom.value = {}
    }
  } finally {
    isLoading.value = false
  }
}

/** ===== Utils ===== **/
const toNum = (v: any, d = 0) => {
  const n = Number(v)
  return Number.isFinite(n) ? n.toFixed(d) : '-'
}
const formatTime = (ts?: string) => (ts ? dayjs(ts).format('DD MMM YYYY HH:mm') : '-')
const roomsSorted = computed(() => [...rooms.value]
  .sort((a, b) => String(a.room_id).localeCompare(String(b.room_id))))

/** ===== Card values ===== **/
const makeItems = (row: SensorRow) => ([
  { label: 'Suhu', icon: 'fas fa-thermometer-half', value: toNum(row.temperature, 1), unit: '°C' },
  { label: 'Kelembapan', icon: 'fas fa-tint', value: toNum(row.humidity, 1), unit: '%RH' },
  { label: 'Tekanan', icon: 'fas fa-tachometer-alt', value: toNum(row.pressure, 0), unit: 'Pa' },
  { label: 'Cahaya', icon: 'fas fa-sun', value: toNum(row.light, 0), unit: 'lux' },
  { label: 'UV Index', icon: 'fas fa-radiation', value: toNum(row.uv, 2), unit: '' },
])

/** ===== Chart helpers ===== **/
const roomHasChart = (roomId: string | number) => {
  const rows = dataChartByRoom.value?.[roomId] || []
  return rows.length > 0
}

const sparkConfigs = (roomId: string | number) => {
  const rows = dataChartByRoom.value?.[roomId] || []
  const pad = (n: number) => String(n).padStart(2, '0')

  // labels & tooltip per titik
  const labels = rows.map(r => {
    const d = new Date(r.created_at)
    return `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`
  })
  const tooltipLabels = rows.map(r => {
    const d = new Date(r.created_at)
    const tanggal = d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
    const waktu = `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`
    return `${tanggal} ${waktu}`
  })
  const seriesFrom = (key: keyof SensorRow) =>
    [{ name: String(key), data: rows.map(r => (Number.isFinite(Number(r[key])) ? Number(r[key]) : null)) }]

  const baseOptions = {
    chart: { sparkline: { enabled: true }, animations: { enabled: true }, toolbar: { show: false } },
    stroke: { width: 2, curve: 'smooth' },
    markers: { size: 0 },
    grid: { padding: { left: 0, right: 0 } },
    xaxis: { categories: labels },
    tooltip: {
      x: { formatter: (_: any, ctx: any) => tooltipLabels?.[ctx?.dataPointIndex ?? 0] || '' },
      y: { formatter: (val: number) => `${val}` }
    }
  }

  return [
    {
      key: 'temperature', title: 'Temperature', unit: '°C',
      series: seriesFrom('temperature'),
      options: { ...baseOptions, tooltip: { ...baseOptions.tooltip, y: { formatter: (v: number) => `${v} °C` } } }
    },
    {
      key: 'humidity', title: 'Humidity', unit: '%RH',
      series: seriesFrom('humidity'),
      options: { ...baseOptions, tooltip: { ...baseOptions.tooltip, y: { formatter: (v: number) => `${v} %RH` } } }
    },
    {
      key: 'pressure', title: 'Pressure', unit: 'Pa',
      series: seriesFrom('pressure'),
      options: { ...baseOptions, tooltip: { ...baseOptions.tooltip, y: { formatter: (v: number) => `${v} Pa` } } }
    },
    {
      key: 'light', title: 'Light', unit: 'lx',
      series: seriesFrom('light'),
      options: { ...baseOptions, tooltip: { ...baseOptions.tooltip, y: { formatter: (v: number) => `${v} lx` } } }
    },
    {
      key: 'uv', title: 'UV', unit: 'index',
      series: seriesFrom('uv'),
      options: { ...baseOptions, tooltip: { ...baseOptions.tooltip, y: { formatter: (v: number) => `${v} index` } } }
    },
  ]
}
</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/custom/config';
@import '/@src/scss/module/dashboard/penyelia.scss';
@import '/@src/scss/module/dashboard/bedah.scss';

.marquee-content {
  display: inline-block;
  white-space: nowrap;
  font-size: 2rem;
  font-weight: bold;
  padding-left: 100%;
  animation: marquee 10s linear infinite, blinkRGB 3s linear infinite;
}

@keyframes marquee {
  0% {
    transform: translateX(0)
  }

  100% {
    transform: translateX(-100%)
  }
}

@keyframes blinkRGB {

  0%,
  100% {
    color: #ff0000
  }

  33% {
    color: #00ff00
  }

  66% {
    color: #0000ff
  }
}

.sensor-card {
  padding: 1rem 1rem 0.75rem;

  &__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: .75rem;
  }

  &__title {
    font-weight: 700;
    font-size: 1.05rem;
    display: flex;
    align-items: center;
  }

  &__meta {
    margin-top: .25rem;
  }

  &__time {
    font-size: .85rem;
    opacity: .75;
    display: flex;
    align-items: center;
  }
}

.sensor-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: .65rem;

  @media (min-width: 420px) {
    grid-template-columns: 1fr 1fr;
  }
}

.sensor-item {
  display: flex;
  align-items: center;
  background: rgba(0, 0, 0, .03);
  border-radius: 14px;
  padding: .6rem .75rem;

  &__icon {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    background: rgba(0, 0, 0, .06);
    display: grid;
    place-items: center;
    margin-right: .6rem;

    i {
      font-size: 1rem;
    }
  }

  &__text {
    line-height: 1.1;
  }

  &__label {
    font-size: .8rem;
    opacity: .75;
    margin-bottom: .15rem;
  }

  &__value {
    font-weight: 800;
    font-size: 1.05rem;

    .unit {
      font-weight: 600;
      font-size: .85rem;
      opacity: .8;
      margin-left: .2rem;
    }
  }
}

/* VCard kecil untuk sparkline (ukuran tidak dibesarkan) */
.spark-card {
  padding: 12px;
}

.spark-card__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

.sensor-empty {
  padding: .25rem 0 .75rem;
}
</style>
