<template>
  <div class="food-delivery-dashboard">
    <div class="left">
      <!-- Header -->
      <div class="left-header">
        <div class="header-image mb-3">
          <img class="welcome-illustration" src="/@src/assets/illustrations/dashboards/lifestyle/ulabdokumen.png"
            alt="Welcome" />
        </div>
        <div class="header-meta">
          <h3>
            ULAB Information System
          </h3>
          <p>Selamat Datang , {{ userLogin.pegawai.namaLengkap }}</p>
        </div>
      </div>


      <!-- Body -->
      <div class="left-body">
        <div class="restaurants mt-5">
          <!-- Search -->
          <div class="search-menu-rad mb-2">
            <div class="search-location-rad" style="width: 100%">
              <i class="iconify" data-icon="feather:search"></i>
              <input type="text" placeholder="Cari Nama Dokumen" v-model="item.searchDataDokumen"
                @keyup.enter="fetchDataAlat()" />
            </div>
            <VButton raised class="search-button-rad" @click="fetchDataAlat()" :loading="isLoading">
              Cari Data
            </VButton>
          </div>

          <!-- Empty -->
          <VPlaceholderPage :title="H.assets().notFound" :subtitle="H.assets().notFoundSubtitle" class="my-6"
            :class="[dataIsiDokuem.length !== 0 && 'is-hidden']">
            <template #image>
              <img class="light-image" :src="H.assets().iconNotFound_rev" alt="" />
              <img class="dark-image" src="/@src/assets/illustrations/placeholders/search-4-dark.svg" alt="" />
            </template>
          </VPlaceholderPage>

          <!-- Cards -->
          <div class="restaurants-list">
            <div class="columns is-multiline is-mobile">
              <div v-for="(items, index) in dataIsiDokuem" :key="items.id || items.norec || index"
                class="column is-12-mobile is-6-tablet is-3-desktop">
                <div class="doc-card" role="button" @click="openDetail(items)" :title="items.namaisidokumen">
                  <div class="doc-card__title line-clamp-2">
                    {{ items.namaisidokumen }}
                  </div>

                  <!-- baris meta: tanggal & dilihat -->
                  <div class="doc-card__meta">
                    <span class="meta-item">
                      <i class="iconify" data-icon="feather:calendar"></i>
                      {{ formatDate(items.__tanggal) }}
                    </span>
                    <span class="dot">•</span>
                    <span class="meta-item">
                      <i class="iconify" data-icon="feather:eye"></i>
                      Dilihat {{ formatCount(items.__hits) }}x
                    </span>
                  </div>

                  <!-- baris bawah: Revisi -->
                  <div class="doc-card__rev">
                    <i class="iconify" data-icon="feather:layers"></i>
                    <span>Revisi {{ pad2(items.revisike ?? 1) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <VFlexPagination v-model:current-page="currentPage.page" :item-per-page="currentPage.limit"
            :total-items="totalData" :max-links-displayed="5">
            <template #before-navigation>
              <VFlex class="mr-4 mt-1" column-gap="1rem">
                <VField />
                <VField>
                  <VControl>
                    <div class="select is-rounded">
                      <select v-model="currentPage.limit">
                        <option :value="4">4 results per page</option>
                        <option :value="8">8 results per page</option>
                        <option :value="12">12 results per page</option>
                        <option :value="24">24 results per page</option>
                        <option :value="50">50 results per page</option>
                        <option :value="100">100 results per page</option>
                      </select>
                    </div>
                  </VControl>
                </VField>
              </VFlex>
            </template>
          </VFlexPagination>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useUserSession } from '/@src/stores/userSession'
import { useRoute, useRouter } from 'vue-router'
import { useHead } from '@vueuse/head'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import { useApi } from '/@src/composable/useApi'
import * as H from '/@src/utils/appHelper'
import moment from 'moment'

useHead({ title: 'Viewer Dokumen - ' + import.meta.env.VITE_PROJECT })
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(false)

const route = useRoute()
const router = useRouter()
const userLogin = useUserSession().getUser()

const item: any = ref({})
const isLoading = ref(false)
const currentPage = ref({ page: 1, limit: 8, rows: 50 })

const dataIsiDokuem: any = ref([])
const totalData = ref(0)

const formatDate = (d?: string | null) => {
  if (!d) return '-'
  return moment(d).isValid() ? moment(d).format('DD MMMM YYYY') : '-'
}
const formatCount = (n: number | string | null | undefined) => {
  const v = Number(n || 0)
  if (v >= 1000) return (v / 1000).toFixed(1).replace('.0', '') + 'k'
  return String(v)
}

const pad2 = (n: any) => String(n ?? 1).padStart(2, '0')

const fetchDataAlat = async () => {
  isLoading.value = true
  let search = item.value.searchDataDokumen ? `&search=${encodeURIComponent(item.value.searchDataDokumen)}` : ''
  const limit = currentPage.value.limit
  const page = currentPage.value.page
  const offset = (page - 1) * limit

  try {
    const resp = await useApi().get(
      `sysadmin/viewer-isi-dokumen?page=${page}&offset=${offset}&limit=${limit}&rows=${currentPage.value.rows}${search}`
    )

    const list = (resp?.data ?? []).map((r: any, i: number) => {
      r.no = i + 1
      r.__hits = r.dilihat ?? 0
      r.__tanggal = r.tglrevisi ?? null
      r.revisike = Number(r.revisike ?? 1)   // ★ penting
      return r
    })

    dataIsiDokuem.value = list
    totalData.value = resp?.total ?? list.length
  } catch (e) {
  } finally {
    isLoading.value = false
  }
}

const openDetail = async (e: any) => {
  const id = e.id ?? e.key ?? e.norec
  if (!id) return
  try {
    await useApi().postNoMessage('/sysadmin/hit-isi-dokumen', { id })
  } catch (err) { }

  router.push({
    name: 'module-mutu-isi-dokumen',
    query: { id },
  })
  // router.push({
  //   name: 'error-page-5',
    // query: { id },
  // })
}

/* watch pagination */
watch(
  () => currentPage.value.page,
  (nv, ov) => { if (nv !== ov) fetchDataAlat() }
)
watch(
  () => currentPage.value.limit,
  (nv, ov) => { if (nv !== ov) fetchDataAlat() }
)

onMounted(() => {
  fetchDataAlat()
})
</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/module/dashboard/customer.scss';

/* === Kartu dokumen ringkas ala LIS === */
.doc-card {
  @include vuero-l-card;
  border: 1px solid var(--fade-grey-dark-3);
  border-radius: 12px;
  padding: 14px 14px 10px;
  min-height: 120px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  background: var(--white);
  cursor: pointer;
  transition: box-shadow .2s ease, transform .08s ease;

  &:hover {
    box-shadow: var(--light-box-shadow);
    transform: translateY(-1px);
  }

  &__title {
    font-family: var(--font-alt);
    font-weight: 600;
    font-size: .95rem;
    color: var(--dark-text);
    line-height: 1.35;
    margin-bottom: 10px;
    word-break: break-word;
  }

  &__meta {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: .82rem;
    color: var(--light-text);

    .meta-item {
      display: inline-flex;
      align-items: center;
      gap: 6px;

      .iconify {
        font-size: 16px;
        color: var(--primary);
      }
    }

    .dot {
      font-size: 10px;
      color: var(--light-text);
      line-height: 1;
    }
  }
}

.doc-card__rev {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: .82rem;
  color: var(--light-text);
  margin-top: 6px;

  .iconify { font-size: 16px; color: var(--primary); }
}

.is-dark {
  .doc-card__rev { color: var(--light-text); }
}

/* Clamp 2 baris dengan ellipsis */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Search bar (tetap) */
.search-menu-rad {
  height: 56px !important;
  white-space: nowrap;
  display: flex;
  flex-shrink: 0;
  align-items: center;
  background-color: white;
  border-radius: 8px;
  width: 100%;
  padding-left: 0.75rem;

  >div:not(:last-of-type) {
    border-right: 1px solid var(--search-border-color);
  }

  .search-location-rad {
    display: flex;
    align-items: center;
    width: 50%;
    font-size: 14px;
    font-weight: 500;
    padding: 0 25px;
    height: 100%;
    font-family: var(--font);

    input {
      width: 100%;
      height: 90%;
      display: block;
      font-family: var(--font);
      color: var(--input-color);
      background-color: transparent;
      border: none;
    }

    svg {
      margin-right: .5rem;
      width: 18px;
      color: var(--primary);
      flex-shrink: 0;
    }
  }

  .search-button-rad {
    background-color: var(--primary);
    min-width: 100px;
    height: 56px !important;
    border: none;
    font-weight: 500;
    font-family: var(--font);
    padding: 0 1rem;
    border-radius: 0 0.75rem 0.75rem 0;
    color: white;
    cursor: pointer;
    margin-left: auto;
  }
}

/* Container yang sudah ada – dibiarkan, hanya minor tweak grid */
.restaurants-list {
  padding: 20px 0;
}

/* Dark mode adjust */
.is-dark {
  .doc-card {
    @include vuero-card--dark;
    background: var(--dark-sidebar-light-6);
    border-color: var(--dark-sidebar-light-12);

    &__title {
      color: var(--dark-dark-text);
    }

    &__meta .meta-item .iconify {
      color: var(--primary);
    }
  }
}

.left {
  .left-header {
    display: flex;
    align-items: center;
    padding: 10px;
    border-radius: 16px;
    background: var(--primary-light-30); // yang hijau muda
  }

  .left-header .header-image {
    width: 200px; // jangan terlalu lebar
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .left-header .welcome-illustration {
    max-width: 260px; // kontrol ukuran gambar
    max-height: 200px;
    object-fit: contain;
    margin: 0; // HAPUS efek margin yang bikin tinggi membengkak
  }

  .left-header .header-meta {
    margin-left: 0; // sesuai tampilan gambar 2
    margin-bottom: 20px;
  }
}

/* Responsif: di mobile, stack ke bawah dan center */
@media (max-width: 767px) {
  .left .left-header {
    flex-direction: column;
    text-align: center;
  }

  .left .left-header .header-image {
    width: 160px;
    height: 110px;
    margin-bottom: 8px;
  }
}
</style>
