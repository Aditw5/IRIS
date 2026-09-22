<template>
  <div class="column">
    <!-- HEADER ATAS -->
    <VCard class="doc-detail-header">
      <div class="doc-detail-header__left">
        <button class="back-btn" type="button" @click="goBack">
          <i class="iconify" data-icon="feather:arrow-left"></i>
        </button>

        <div class="doc-detail-header__text">
          <h3 class="doc-detail-title">
            {{ item.namaisidokumen || 'Detail Dokumen' }}
          </h3>
          <p class="doc-detail-sub">
            <!-- pakai alias nomordokumen dari backend: kodeexternal / reportdisplay -->
            {{ item.nomordokumen || '-' }}
          </p>
        </div>
      </div>

      <div class="doc-detail-header__right">
        <VTag rounded color="success" v-if="item.revisike">
          Revisi {{ pad2(item.revisike) }}
        </VTag>
      </div>
    </VCard>

    <!-- BODY -->
    <div class="columns is-variable is-4 doc-detail-layout">
      <!-- LEFT: PDF VIEWER -->
      <div class="column is-9-desktop is-12-tablet">
        <VCard class="doc-viewer-card">
          <div v-if="isLoading" class="doc-loading">
            <VIconBox color="primary" size="medium" rounded>
              <i class="iconify" data-icon="feather:file-text"></i>
            </VIconBox>
            <p>Memuat dokumen...</p>
          </div>

          <template v-else>
            <div v-if="fileUrl" class="doc-viewer-wrapper">
              <iframe :src="fileUrlWithParams" class="doc-viewer-iframe" frameborder="0"></iframe>
            </div>

            <VPlaceholderPage v-else title="Dokumen tidak ditemukan"
              subtitle="Link file dokumen belum tersedia atau tidak valid." class="my-6">
              <template #image>
                <img class="light-image" :src="H.assets().iconNotFound_rev" alt="" />
                <img class="dark-image" src="/@src/assets/illustrations/placeholders/search-4-dark.svg" alt="" />
              </template>
            </VPlaceholderPage>
          </template>
        </VCard>
      </div>

      <!-- RIGHT: METADATA -->
      <div class="column is-3-desktop is-12-tablet">
        <!-- Info dasar -->
        <VCard class="doc-meta-card">
          <h4 class="doc-meta-title">Informasi Dokumen</h4>

          <div class="doc-meta-row">
            <span class="meta-label">Nama Dokumen</span>
            <span class="meta-value">
              {{ item.namaisidokumen || '-' }}
            </span>
          </div>

          <!-- <div class="doc-meta-row">
            <span class="meta-label">Nomor Dokumen</span>
            <span class="meta-value">
              {{ item.nomordokumen || '-' }}
            </span>
          </div> -->

          <div class="doc-meta-row">
            <span class="meta-label">Revisi</span>
            <span class="meta-value">
              {{ pad2(item.revisike ?? 1) }}
            </span>
          </div>

          <div class="doc-meta-row">
            <span class="meta-label">Tanggal Revisi</span>
            <span class="meta-value">
              {{ formatDate(item.tglrevisi) }}
            </span>
          </div>

          <div class="doc-meta-row">
            <span class="meta-label">Fungsi</span>
            <span class="meta-value">
              {{ item.fungsi || '-' }}
            </span>
          </div>

          <div class="doc-meta-row">
            <span class="meta-label">Keterangan</span>
            <span class="meta-value">
              {{ item.keterangan || '-' }}
            </span>
          </div>
        </VCard>

        <!-- Hit statistik & info tambahan -->
        <VCard class="doc-meta-card mt-4">
          <h4 class="doc-meta-title">Statistik & Lainnya</h4>

          <div class="doc-meta-row">
            <span class="meta-label">
              <i class="iconify" data-icon="feather:eye"></i>
              Dilihat
            </span>
            <span class="meta-value">
              {{ formatCount(item.__hits ?? item.dilihat ?? 0) }}x
            </span>
          </div>
<!-- 
          <div class="doc-meta-row">
            <span class="meta-label">
              <i class="iconify" data-icon="feather:user"></i>
              Pembuat (namaexternal)
            </span>
            <span class="meta-value">
              {{ item.pembuat || '-' }}
            </span>
          </div>

          <div class="doc-meta-row">
            <span class="meta-label">
              <i class="iconify" data-icon="feather:link-2"></i>
              URL Form
            </span>
            <span class="meta-value">
              {{ item.alamaturlform || '-' }}
            </span>
          </div> -->

          <div class="doc-meta-row">
            <span class="meta-label">
              Created At
            </span>
            <span class="meta-value">
              {{ formatDate(item.created_at) }}
            </span>
          </div>

          <div class="doc-meta-row">
            <span class="meta-label">
              Updated At
            </span>
            <span class="meta-value">
              {{ formatDate(item.updated_at) }}
            </span>
          </div>
        </VCard>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useHead } from '@vueuse/head'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import { useApi } from '/@src/composable/useApi'
import * as H from '/@src/utils/appHelper'
import moment from 'moment'

useHead({ title: 'Detail Dokumen - ' + import.meta.env.VITE_PROJECT })
useViewWrapper().setPageTitle('Detail Dokumen')
useViewWrapper().setFullWidth(false)

const route = useRoute()
const router = useRouter()

const item: any = ref({})
const fileUrl = ref<string>('') // url PDF / DOCX viewer
const isLoading = ref<boolean>(false)

const formatDate = (d?: string | null) => {
  if (!d) return '-'
  return moment(d).isValid() ? moment(d).format('DD MMMM YYYY') : '-'
}

const pad2 = (n: any) => String(n ?? 1).padStart(2, '0')

const formatCount = (n: number | string | null | undefined) => {
  const v = Number(n || 0)
  if (v >= 1000) return (v / 1000).toFixed(1).replace('.0', '') + 'k'
  return String(v)
}

const fileUrlWithParams = computed(() => {
  if (!fileUrl.value) return ''
  return fileUrl.value + '#toolbar=1&navpanes=1&scrollbar=1'
})



const fetchDetail = async () => {
  const id = (route.query.id as string) || ''
  if (!id) return
  isLoading.value = true
  try {
    const res = await useApi().get(`/sysadmin/viewer-isi-dokumen-detail?id=${id}`)
    const row = res
    console.log(res)

    row.__hits = Number(row.dilihat ?? 0)
    row.revisike = Number(row.revisike ?? 1)

    item.value = { ...row }
    fileUrl.value = String(row.file_url || '')

    isLoading.value = false
  } catch (e) {
    item.value = []
  } finally {
    isLoading.value = false
  }
}


const goBack = () => {
  router.back()
}

onMounted(() => {
  fetchDetail()
})
</script>
<style lang="scss">
@import '/@src/scss/abstracts/all';

.doc-detail-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 18px;

  .doc-detail-header__left {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .back-btn {
    width: 36px;
    height: 36px;
    border-radius: 999px;
    border: 1px solid var(--fade-grey-dark-3);
    background: var(--white);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;

    .iconify {
      font-size: 18px;
      color: var(--dark-text);
    }

    &:hover {
      background: var(--primary);
      border-color: var(--primary);

      .iconify {
        color: #fff;
      }
    }
  }

  .doc-detail-header__text {
    display: flex;
    flex-direction: column;
  }

  .doc-detail-title {
    font-size: 1.1rem;
    font-weight: 600;
  }

  .doc-detail-sub {
    font-size: 0.85rem;
    color: var(--light-text);
  }
}

.doc-detail-layout {
  margin-top: 16px;
}

.doc-viewer-card {
  padding: 0;
  min-height: 480px;
  display: flex;
  align-items: stretch;
}

.doc-viewer-wrapper {
  width: 100%;
  height: calc(100vh - 220px); // supaya mirip LIS, hampir penuh layar
  max-height: 900px;
}

.doc-viewer-iframe {
  width: 100%;
  height: 100%;
  border: none;
}

.doc-loading {
  width: 100%;
  min-height: 320px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  color: var(--light-text);
}

/* SIDEBAR META */
.doc-meta-card {
  padding: 14px 16px;

  .doc-meta-title {
    font-size: 0.95rem;
    font-weight: 600;
    margin-bottom: 10px;
  }

  .doc-meta-row {
    display: flex;
    flex-direction: column;
    margin-bottom: 8px;

    .meta-label {
      font-size: 0.78rem;
      color: var(--light-text);
      display: inline-flex;
      align-items: center;
      gap: 6px;

      .iconify {
        font-size: 14px;
        color: var(--primary);
      }
    }

    .meta-value {
      font-size: 0.88rem;
      color: var(--dark-text);
      font-weight: 500;
      word-break: break-word;
    }
  }
}

/* Dark mode */
.is-dark {
  .doc-detail-header {
    @include vuero-card--dark;
  }

  .doc-meta-card {
    @include vuero-card--dark;
  }

  .back-btn {
    background: var(--dark-sidebar-light-6);
    border-color: var(--dark-sidebar-light-12);

    .iconify {
      color: var(--dark-dark-text);
    }

    &:hover {
      background: var(--primary);
      border-color: var(--primary);

      .iconify {
        color: #fff;
      }
    }
  }

  .doc-viewer-card {
    @include vuero-card--dark;
  }
}

@media (max-width: 1023px) {
  .doc-viewer-wrapper {
    height: 60vh;
  }
}
</style>
