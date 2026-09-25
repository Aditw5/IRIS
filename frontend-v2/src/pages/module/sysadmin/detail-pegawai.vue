<template>
  <div class="profile-wrapper">
    <div v-if="isCustomer" class="customer-profile-back">
      <VButton icon="feather:arrow-left" type="button" color="primary" outlined raised @click="goToCustomerDashboard">
        Kembali ke Dashboard
      </VButton>
    </div>

    <div class="profile-header has-text-centered">
      <!-- FOTO -->
      <div v-if="profilePhotoUrl" class="mb-4">
        <figure class="image is-128x128 is-inline-block">
          <img class="is-rounded" :src="profilePhotoUrl" :alt="isCustomer ? 'Foto User' : 'Foto Pegawai'"
            style="height: 128px; width: 128px; object-fit: cover; border: 3px solid #e0e0e0;"
            @error.once="(event) => onceImageErrored(event, '128x128')" />
        </figure>
      </div>
      <VAvatar v-else size="xl" picture="/images/avatars/svg/vuero-1.svg"
        badge="/images/icons/flags/united-states-of-america.svg" />

      <h3 class="title is-4 is-narrow is-thin">
        {{ isCustomer ? (item.name || '-') : (item.namalengkap || '-') }}
      </h3>

      <VField>
        <VControl>
          <VFilePond name="profile_filepond" class="profile-filepond" :chunk-retry-delays="[500, 1000, 3000]"
            label-idle="<i class='lnil lnil-cloud-upload'></i> <br> <span class='filepond--label-action'> Upload Foto Baru </span>"
            :accepted-file-types="['image/png', 'image/jpeg', 'image/gif']" :image-preview-height="140"
            :image-resize-target-width="140" :image-resize-target-height="140" image-crop-aspect-ratio="1:1"
            style-panel-layout="compact circle" style-load-indicator-position="center bottom"
            style-progress-indicator-position="right bottom" style-button-remove-item-position="left bottom"
            style-button-process-item-position="right bottom" @addfile="onAddFile" @removefile="onRemoveFile" />
        </VControl>
      </VField>

      <VButton icon="feather:save" type="submit" color="primary" raised @click="simpanFotoPegawai()"
        :loading="isSimpan">
        Save Foto Baru
      </VButton>

      <VButton v-if="isCustomer" icon="feather:edit-3" type="button" color="info" raised class="ml-2"
        @click="openEditProfileModal()">
        Edit Profile
      </VButton>

      <p class="light-text"></p>

      <!-- HEADER STATS KHUSUS PEGAWAI -->
      <div v-if="!isCustomer" class="profile-stats">
        <div class="profile-stat">
          <i aria-hidden="true" class="lnil lnil-users-alt"></i>
          <span> {{ item.objectjeniskelaminfk }} </span>
        </div>
        <div class="separator"></div>
        <div class="profile-stat">
          <i aria-hidden="true" class="lnil lnil-checkmark-circle"></i>
          <span>{{ item.objectjenispegawaifk }}</span>
        </div>
        <div class="separator"></div>
        <div class="profile-stat">
          <i aria-hidden="true" class="lnil lnil-home"></i>
          <span>{{ item.objectunitkerjapegawaifk }}</span>
        </div>
      </div>

      <!-- HEADER STATS KHUSUS CUSTOMER -->
      <div v-else class="profile-stats">
        <div class="profile-stat">
          <i aria-hidden="true" class="lnil lnil-envelope"></i>
          <span>{{ item.email || '-' }}</span>
        </div>
        <div class="separator"></div>
        <div class="profile-stat">
          <i aria-hidden="true" class="lnil lnil-phone"></i>
          <span>{{ item.nowa || '-' }}</span>
        </div>
        <div class="separator"></div>
        <div class="profile-stat">
          <i aria-hidden="true" class="lnil lnil-users-alt"></i>
          <span>{{ formatEksternal(item.iseksternal) }}</span>
        </div>
      </div>
    </div>

    <div class="profile-body">
      <div class="columns">
        <!-- =========================
             CUSTOMER VIEW
        ========================== -->
        <template v-if="isCustomer">
          <div class="column is-12">
            <div class="profile-card">
              <div class="profile-card-section">
                <div class="section-title customer-section-title-action">
                  <h4>Informasi Akun</h4>
                  <VButton type="button" icon="feather:edit-3" color="info" outlined raised
                    @click="openEditProfileModal()">
                    Edit Profile
                  </VButton>
                </div>

                <div class="section-content">
                  <div class="experience-wrapper customer-experience-wrapper">
                    <div class="experience-item customer-experience-item">
                      <div class="customer-icon-box is-user">
                        <i class="fas fa-user"></i>
                      </div>
                      <div class="meta">
                        <span class="dark-inverted">Nama</span>
                        <span>{{ item.name || '-' }}</span>
                      </div>
                    </div>

                    <div class="experience-item customer-experience-item">
                      <div class="customer-icon-box is-email">
                        <i class="fas fa-envelope"></i>
                      </div>
                      <div class="meta">
                        <span class="dark-inverted">Email/Username</span>
                        <span>{{ item.email || '-' }}</span>
                      </div>
                    </div>

                    <div class="experience-item customer-experience-item">
                      <div class="customer-icon-box is-phone">
                        <i class="fab fa-whatsapp"></i>
                      </div>
                      <div class="meta">
                        <span class="dark-inverted">No. WhatsApp</span>
                        <span>{{ item.nowa || '-' }}</span>
                      </div>
                    </div>

                    <div class="experience-item customer-experience-item">
                      <div class="customer-icon-box is-jabatan">
                        <i class="fas fa-briefcase"></i>
                      </div>
                      <div class="meta">
                        <span class="dark-inverted">Jabatan</span>
                        <span>{{ item.jabatan || '-' }}</span>
                      </div>
                    </div>

                    <div class="experience-item customer-experience-item">
                      <div class="customer-icon-box is-mitra">
                        <i class="fas fa-building"></i>
                      </div>
                      <div class="meta">
                        <span class="dark-inverted">Unit / Perusahaan</span>
                        <span>{{ item.unit || '-' }}</span>
                      </div>
                    </div>

                    <div class="experience-item customer-experience-item">
                      <div class="customer-icon-box is-tipe">
                        <i class="fas fa-user-tag"></i>
                      </div>
                      <div class="meta">
                        <span class="dark-inverted">Tipe User</span>
                        <span>{{ formatEksternal(item.iseksternal) }}</span>
                      </div>
                    </div>

                    <div class="experience-item customer-experience-item">
                      <div class="customer-icon-box is-date">
                        <i class="fas fa-calendar-plus"></i>
                      </div>
                      <div class="meta">
                        <span class="dark-inverted">Tanggal Dibuat</span>
                        <span>{{ formatDate(item.created_at) }}</span>
                      </div>
                    </div>

                    <div class="experience-item customer-experience-item">
                      <div class="customer-icon-box is-date-update">
                        <i class="fas fa-calendar-check"></i>
                      </div>
                      <div class="meta">
                        <span class="dark-inverted">Terakhir Diupdate</span>
                        <span>{{ formatDate(item.updated_at) }}</span>
                      </div>
                    </div>

                    <div class="experience-item customer-experience-item">
                      <div class="customer-icon-box is-status">
                        <i class="fas fa-toggle-on"></i>
                      </div>
                      <div class="meta">
                        <span class="dark-inverted">Status Enabled</span>
                        <span>{{ item.statusenabled === true ? 'Aktif' : 'Tidak Aktif' }}</span>
                      </div>
                    </div>

                    <div class="experience-item customer-experience-item">
                      <div class="customer-icon-box is-new">
                        <i class="fas fa-user-clock"></i>
                      </div>
                      <div class="meta">
                        <span class="dark-inverted">User Baru</span>
                        <span>{{ item.isuserbaru === true ? 'Ya' : 'Tidak' }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>

        <!-- =========================
             PEGAWAI VIEW LAMA
        ========================== -->
        <template v-else>
          <div class="column is-8">
            <div class="profile-card">
              <div class="profile-card-section">
                <div class="section-title">
                  <h4>Tentang Saya</h4>
                </div>
                <div class="section-content">
                  <div class="experience-wrapper">
                    <div class="experience-item">
                      <img src="/images/pegawai/cal.svg" alt=""
                        @error.once="(event) => onceImageErrored(event, '150x150')" />
                      <div class="meta">
                        <span class="dark-inverted">Tempat Tanggal Lahir</span>
                        <span>
                          <span> {{ item.tempatlahir }}</span>
                          <i aria-hidden="true" class="fas fa-circle"></i>
                          <span>{{ item.tgllahir }}</span>
                        </span>
                      </div>
                    </div>
                    <div class="experience-item">
                      <img src="/images/pegawai/last.svg" alt=""
                        @error.once="(event) => onceImageErrored(event, '150x150')" />
                      <div class="meta">
                        <span class="dark-inverted">Pendidikan Terakhir</span>
                        <span>
                          <span> {{ item.objectpendidikanterakhirfk }}</span>
                        </span>
                      </div>
                    </div>
                    <div class="experience-item">
                      <img src="/images/pegawai/agama.svg" alt=""
                        @error.once="(event) => onceImageErrored(event, '150x150')" />
                      <div class="meta">
                        <span class="dark-inverted">Agama</span>
                        <span>
                          <span> {{ item.objectagamafk }} </span>
                        </span>
                      </div>
                    </div>
                    <div class="experience-item">
                      <img src="/images/pegawai/alamat.svg" alt=""
                        @error.once="(event) => onceImageErrored(event, '150x150')" />
                      <div class="meta">
                        <span class="dark-inverted">Alamat</span>
                        <span>
                          <span> {{ item.alamat }}</span>
                        </span>
                      </div>
                    </div>
                    <div class="experience-item">
                      <img src="/images/pegawai/telpon.svg" alt=""
                        @error.once="(event) => onceImageErrored(event, '150x150')" />
                      <div class="meta">
                        <span class="dark-inverted">No. Telepon, Email</span>
                        <span>
                          <span> {{ item.notlp }}</span>
                          <i aria-hidden="true" class="fas fa-circle"></i>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="profile-card-section">
                <div class="section-title">
                  <h4>Data Kepegawaian</h4>
                  <RouterLink :to="{ name: 'sidebar-layouts-profile-edit-skills' }">
                    <i aria-hidden="true" class="lnil lnil-pencil"></i>
                  </RouterLink>
                </div>

                <div class="section-content">
                  <div class="experience-wrapper">
                    <div class="experience-item">
                      <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                      <div class="meta">
                        <span class="dark-inverted">Tanggal Masuk</span>
                        <span> {{ item.tglmasuk }}</span>
                      </div>
                    </div>
                    <div class="experience-item">
                      <i class="fas fa-calendar-times" aria-hidden="true"></i>
                      <div class="meta">
                        <span class="dark-inverted">Tanggal Keluar</span>
                        <span>
                          <span> {{ item.tglkeluar }}</span>
                        </span>
                      </div>
                    </div>
                    <div class="experience-item">
                      <i class="fas fa-user-nurse" aria-hidden="true"></i>
                      <div class="meta">
                        <span class="dark-inverted">Status Pegawai</span>
                        <span>
                          <span> {{ item.objectstatuspegawaifk }}</span>
                        </span>
                      </div>
                    </div>
                    <div class="experience-item">
                      <i class="fas fa-user-nurse" aria-hidden="true"></i>
                      <div class="meta">
                        <span class="dark-inverted">Jabatan</span>
                        <span>
                          <span> {{ item.objectjabatanfungsionalfk }}</span>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="column">
            <div class="profile-card">
              <div class="profile-card-section no-padding">
                <div class="section-title">
                  <h4 style="margin-bottom: 1rem;"> Setting Login User </h4>
                </div>
                <div class="tile-grid tile-grid-v2">
                  <VPlaceholderPage :class="[dataLogin.length !== 0 && 'is-hidden']" title="Tidak Ada Data."
                    subtitle=" Silakan Hubungi Admin untuk Info Lebih Lanjut" larger>
                    <template #image>
                      <img class="light-image" src="/@src/assets/illustrations/placeholders/search-4.png" alt="" />
                      <img class="dark-image" src="/@src/assets/illustrations/placeholders/search-4-dark.svg" alt="" />
                    </template>
                  </VPlaceholderPage>

                  <div name="list" tag="div" class="columns is-multiline">
                    <div class="columns is-multiline p-2" style="max-height:200px;overflow: auto;">
                      <div v-for="item in dataLogin" :key="item.id" class="column is-12 p-0 pb-2 pl-2 pr-2 ">
                        <div class="tile-grid-item">
                          <div class="tile-grid-item-inner" @click="edit(item)">
                            <VAvatar size="small" picture="/images/avatars/svg/userlo.png" color="primary" bordered />
                            <div class="meta">
                              <span class="dark-inverted text-elipsis-wrap" style="width:200px !important">
                                {{ item.namauser }}
                                <i aria-hidden="true" class="lnil lnil-pencil" style="margin-left: 20px;"></i>
                              </span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="section-title">
                <h4 style="margin-bottom: 1rem;">Registrasi Login Wajah Pegawai</h4>
              </div>

              <VButton color="primary" rounded @click="openCamera">
                <i class="fas fa-camera"></i> Daftarkan Wajah
              </VButton>

              <Dialog v-model:visible="showCamera" modal header="Ambil Foto Wajah" :style="{ width: '620px' }">
                <div style="text-align:center;">
                  <video ref="video" autoplay playsinline
                    style="border-radius:10px; width:520px; margin-bottom:10px;"></video>
                  <div>
                    <VButton color="success" @click="captureFace" :loading="isLoading">Simpan Wajah</VButton>
                  </div>
                </div>
              </Dialog>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>

  <!-- MODAL EDIT PROFILE USER CUSTOMER -->
  <Dialog v-if="isCustomer" v-model:visible="modalEditProfile" modal header="Edit Profile User"
    :style="{ width: '720px' }" :breakpoints="{ '960px': '75vw', '640px': '94vw' }" class="edit-profile-dialog">
    <div class="columns is-multiline edit-profile-form">
      <div class="column is-12">
        <VField>
          <VLabel class="required-field">Nama</VLabel>
          <VControl icon="fas fa-user">
            <VInput type="text" v-model="editProfile.name" placeholder="Nama" class="is-rounded" />
          </VControl>
        </VField>
      </div>

      <div class="column is-12">
        <VField>
          <VLabel class="required-field">Email/Username</VLabel>
          <VControl icon="fas fa-envelope">
            <VInput type="email" v-model="editProfile.email" placeholder="Email" class="is-rounded" />
          </VControl>
        </VField>
      </div>

      <div class="column is-12">
        <VField>
          <VLabel class="required-field">No. WhatsApp</VLabel>
          <VControl icon="fab fa-whatsapp">
            <VInput type="text" v-model="editProfile.nowa" placeholder="Contoh: 628xxxxxxxxxx" class="is-rounded" />
          </VControl>
        </VField>
      </div>

      <div class="column is-12">
        <VField>
          <VLabel class="required-field">Jabatan</VLabel>
          <VControl icon="fas fa-briefcase">
            <VInput type="text" v-model="editProfile.jabatan" placeholder="Jabatan" class="is-rounded"
              @input="editProfile.jabatan = (editProfile.jabatan || '').toUpperCase()" />
          </VControl>
        </VField>
      </div>

      <div class="column is-12">
        <div class="readonly-info-box">
          <div>
            <span>Unit / Perusahaan</span>
            <strong>{{ item.unit || '-' }}</strong>
          </div>
          <small>Data ini tidak bisa diedit, Jika ingin diubah hubungin administrator.</small>
        </div>
      </div>
    </div>

    <template #footer>
      <VButton icon="lnir lnir-arrow-left rem-100" light dark-outlined @click="modalEditProfile = false">
        Tutup
      </VButton>
      <VButton type="button" color="info" raised icon="feather:save" @click="saveProfileUser()"
        :loading="isSavingProfile">
        Simpan Profile
      </VButton>
    </template>
  </Dialog>

  <!-- MODAL LOGIN HANYA UNTUK PEGAWAI -->
  <Dialog v-if="!isCustomer" v-model:visible="modalInput" modal
    :header="(item.id != undefined ? 'Ubah' : 'Simpan') + ' Login User'" :style="{ width: '30vw' }">
    <div class="columns is-multiline">
      <div class="column is-12">
        <VField label="Nama User">
          <VControl icon="fas fa-user">
            <VInput type="text" v-model="item.namauser" placeholder="Nama Login User" class="is-rounded" />
          </VControl>
        </VField>
      </div>
      <div class="column is-6">
        <VField label="Kata Sandi">
          <VControl icon="fas fa-address-card">
            <VInput type="password" v-model="item.katasandi" placeholder="Kata Sandi" class="is-rounded" />
          </VControl>
        </VField>
      </div>
      <div class="column is-6">
        <VField label="Ulangi Kata Sandi">
          <VControl icon="fas fa-address-card">
            <VInput type="password" v-model="item.katasandi2" placeholder="Ulangi Kata Sandi" class="is-rounded" />
          </VControl>
        </VField>
      </div>
      <div class="column is-12">
        <VField label="Nama Pegawai">
          <VControl icon="fas fa-user">
            <VInput type="text" v-model="item.namalengkap" placeholder="Nama Login User" class="is-rounded" disabled />
          </VControl>
        </VField>
      </div>
      <div class="column is-12">
        <VField label="Nama User">
          <VControl icon="fas fa-user">
            <VInput type="text" v-model="item.kelompokuser" placeholder="Nama Login User" class="is-rounded" disabled />
          </VControl>
        </VField>
      </div>
    </div>

    <template #footer>
      <VButton icon="lnir lnir-arrow-left rem-100" light dark-outlined @click="modalInput = false">
        Tutup
      </VButton>
      <VButton type="button" rounded outlined color="primary" raised icon="feather:save" @click="save()"
        :loading="isLoadingTT">
        {{ item.id != undefined ? 'Ubah' : 'Simpan' }}
      </VButton>
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { onceImageErrored } from '/@src/utils/via-placeholder'
import { useWindowScroll } from '@vueuse/core'
import { useApi } from '/@src/composable/useApi'
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useHead } from '@vueuse/head'
import * as H from '/@src/utils/appHelper'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import { useToaster } from '/@src/composable/toaster'
import Dialog from 'primevue/dialog'
import { useUserSession } from '/@src/stores/userSession'
import * as faceapi from 'face-api.js'
import { publicFileUrl } from '/@src/utils/publicFileUrl'

useHead({
  title: 'Detail Pegawai - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)

const route = useRoute()
const router = useRouter()
const userSession = useUserSession()
const toaster = useToaster()

const kelompokUser = computed(() =>
  String(userSession.getUser()?.kelompokUser?.kelompokUser ?? '').toLowerCase().trim(),
)

const isCustomer = computed(() => kelompokUser.value === 'customer')

const goToCustomerDashboard = () => {
  router.push({ name: 'module-dashboard-customer' }).catch(() => { })
}

const userId = computed(() => userSession.getUser()?.id ?? '')
const pegawaiIdSession = computed(() => userSession.getUser()?.pegawai?.id ?? '')
const ID_PEGAWAI = (route.query.id as string) || String(pegawaiIdSession.value || '')

let isLoading: any = ref(false)
let isLoadingTT: any = ref(false)
const modalInput = ref(false)
const modalEditProfile = ref(false)
const isSavingProfile = ref(false)
const dataLogin = ref<any[]>([])
const d_pegawai = ref([])
const d_kelompok = ref([])
const item: any = ref({
  aktif: true,
})
const editProfile: any = ref({
  id: '',
  name: '',
  email: '',
  nowa: '',
  jabatan: '',
})
const showCamera = ref(false)
const video = ref<HTMLVideoElement | null>(null)
const { y } = useWindowScroll()
const fileFoto: any = ref(null)
const isSimpan: any = ref(false)

const isStuck = computed(() => {
  return y.value > 30
})

const profilePhotoUrl = computed(() => {
  if (isCustomer.value) {
    return item.value?.filenameFoto
      ? publicFileUrl('berkas-user', item.value.filenameFoto)
      : ''
  }

  return item.value?.fotopegawai
    ? publicFileUrl('berkas-mutu', item.value.fotopegawai)
    : ''
})

function formatDate(val: any) {
  if (!val) return '-'
  const d = new Date(val)
  if (isNaN(d.getTime())) return val
  return d.toLocaleString('id-ID')
}

function formatEksternal(val: any) {
  if (val === true) return 'Eksternal'
  if (val === false) return 'Internal'
  return '-'
}

async function pegawaiByID(id: any) {
  const { data: pegawai } = await useApi().get(`/sysadmin/master-pegawai?id=${id}`)
  if (!pegawai || !pegawai.length) return

  item.value.namalengkap = pegawai[0].namalengkap
  item.value.objectjenispegawaifk = pegawai[0].jenispegawai
  item.value.objectjeniskelaminfk = pegawai[0].jeniskelamin
  item.value.objectagamafk = pegawai[0].agama
  item.value.alamat = pegawai[0].alamat
  item.value.tempatlahir = pegawai[0].tempatlahir
  item.value.tgllahir = pegawai[0].tgllahir
  item.value.tglmasuk = pegawai[0].tglmasuk
  item.value.tglkeluar = pegawai[0].tglkeluar
  item.value.bankrekeningnomor = pegawai[0].bankrekeningnomor
  item.value.bankrekeningatasnama = pegawai[0].bankrekeningatasnama
  item.value.bankrekeningnama = pegawai[0].bankrekeningnama
  item.value.objectjabatanfungsionalfk = pegawai[0].namajabatan
  item.value.objectstatuspegawaifk = pegawai[0].statuspegawai
  item.value.objectpendidikanterakhirfk = pegawai[0].pendidikan
  item.value.objectstatusperkawinanfk = pegawai[0].statusperkawinan
  item.value.objectunitkerjapegawaifk = pegawai[0].namaruangan
  item.value.objectkelompokjabatanfk = pegawai[0].namakelompokjabatan
  item.value.objectdetailkategorypegawaifk = pegawai[0].detailkategorypegawai
  item.value.fotopegawai = pegawai[0].filenameFoto
  item.value.notlp = pegawai[0].notlp
  item.value.email = pegawai[0].email
  item.value.unit = pegawai[0].unit
}

async function userByID(id: any) {
  const res = await useApi().get(`/sysadmin/profile-user-detail?id=${id}`)
  const data = res?.data ?? null
  if (!data) return

  item.value.id = data.id
  item.value.name = data.name
  item.value.email = data.email
  item.value.nowa = data.nowa
  item.value.kdprofile = data.kdprofile
  item.value.isuserbaru = data.isuserbaru
  item.value.mitrafk = data.mitrafk
  item.value.jabatan = data.jabatan
  item.value.unit = data.unit
  item.value.iseksternal = data.iseksternal
  item.value.filenameFoto = data.filenameFoto
  item.value.created_at = data.created_at
  item.value.updated_at = data.updated_at
  item.value.statusenabled = data.statusenabled
}

const openEditProfileModal = () => {
  editProfile.value = {
    id: item.value.id || userId.value || '',
    name: item.value.name || '',
    email: item.value.email || '',
    nowa: item.value.nowa || '',
    jabatan: item.value.jabatan || '',
  }
  modalEditProfile.value = true
}

const saveProfileUser = async () => {
  if (!editProfile.value.name) {
    toaster.error('Nama harus di isi')
    return
  }
  if (!editProfile.value.email) {
    toaster.error('Email harus di isi')
    return
  }
  if (!editProfile.value.nowa) {
    toaster.error('No. WhatsApp harus di isi')
    return
  }
  if (!editProfile.value.jabatan) {
    toaster.error('Jabatan harus di isi')
    return
  }

  const payload = {
    id: editProfile.value.id || userId.value,
    name: editProfile.value.name,
    email: editProfile.value.email,
    nowa: editProfile.value.nowa,
    jabatan: editProfile.value.jabatan,
  }

  isSavingProfile.value = true
  await useApi()
    .post('/sysadmin/save-profile-user', payload)
    .then(async () => {
      await userByID(userId.value || item.value.id)
      modalEditProfile.value = false
      toaster.success('Profile user berhasil diupdate')
    })
    .catch(() => { })
    .finally(() => {
      isSavingProfile.value = false
    })
}

const onAddFile = (error: any, fileInfo: any) => {
  if (error) {
    console.error(error)
    return
  }

  const _file = fileInfo.file as File
  if (_file) {
    fileFoto.value = _file
  }
}

const onRemoveFile = (error: any) => {
  if (error) {
    console.error(error)
    return
  }
  fileFoto.value = null
}

const simpanFotoPegawai = async () => {
  if (fileFoto.value == null) {
    H.alert('error', 'Foto tidak boleh kosong')
    return
  }

  isSimpan.value = true
  const formData = new FormData()

  if (isCustomer.value) {
    formData.append('id', String(userId.value || item.value.id || ''))
    formData.append('file', fileFoto.value)

    useApi()
      .postNoMessage('/sysadmin/save-user-foto', formData)
      .then(async () => {
        await userByID(userId.value || item.value.id)
        fileFoto.value = null
        isSimpan.value = false
        toaster.success('Foto user berhasil disimpan')
      })
      .catch(() => {
        isSimpan.value = false
      })
  } else {
    formData.append('id', ID_PEGAWAI)
    formData.append('file', fileFoto.value)

    useApi()
      .postNoMessage('/sysadmin/save-pegawai-foto', formData)
      .then(async () => {
        await pegawaiByID(ID_PEGAWAI)
        fileFoto.value = null
        isSimpan.value = false
        toaster.success('Foto pegawai berhasil disimpan')
      })
      .catch(() => {
        isSimpan.value = false
      })
  }
}

const openCamera = async () => {
  showCamera.value = true
  await loadModels()
  startCamera()
}

const loadModels = async () => {
  await Promise.all([
    faceapi.nets.tinyFaceDetector.loadFromUri('/models'),
    faceapi.nets.faceLandmark68Net.loadFromUri('/models'),
    faceapi.nets.faceRecognitionNet.loadFromUri('/models'),
  ])
}

const startCamera = async () => {
  const stream = await navigator.mediaDevices.getUserMedia({ video: true })
  if (video.value) video.value.srcObject = stream
}

const captureFace = async () => {
  if (!video.value) return

  const detection = await faceapi
    .detectSingleFace(video.value, new faceapi.TinyFaceDetectorOptions())
    .withFaceLandmarks()
    .withFaceDescriptor()

  if (!detection) {
    toaster.error('Wajah tidak terdeteksi!')
    return
  }

  const canvas = faceapi.createCanvasFromMedia(video.value)
  const ctx = canvas.getContext('2d')
  ctx?.drawImage(video.value, 0, 0, canvas.width, canvas.height)
  const imageBase64 = canvas.toDataURL('image/jpeg')

  const payload = {
    id: ID_PEGAWAI,
    image: imageBase64,
    descriptor: Array.from(detection.descriptor),
  }

  isLoading.value = true
  await useApi()
    .post('/sysadmin/save-pegawai-face', payload)
    .then(() => {
      toaster.success('Registrasi wajah berhasil!')
      showCamera.value = false
      isLoading.value = false
    })
    .catch(() => {
      toaster.error('Gagal menyimpan wajah.')
      isLoading.value = false
    })
}

const fetchPegawai = async (id: any) => {
  isLoading.value = true
  dataLogin.value = []
  await useApi()
    .get(`/sysadmin/jadwal-kerja?idpegawai=${id}`)
    .then((response) => {
      isLoading.value = false
      dataLogin.value = response.login
    })
    .catch(() => {
      isLoading.value = false
      dataLogin.value = []
    })
}

function loadDropdown() {
  d_pegawai.value = []
  useApi()
    .get(`/sysadmin/master-tambah-login-user-dropdown`)
    .then((response: any) => {
      d_pegawai.value = response.namalengkap
      d_kelompok.value = response.kelompokuser
    })
}

function add() {
  modalInput.value = true
}

function edit(e: any) {
  item.value.id = e.id
  item.value.statusenabled = e.statusenabled
  item.value.namauser = e.namauser
  item.value.kelompokuser = e.kelompokuser
  modalInput.value = true
}

async function save() {
  if (!item.value.namauser) {
    toaster.error('Nama User harus di isi')
    return
  }
  if (!item.value.katasandi) {
    toaster.error('Kata Sandi harus di isi')
    return
  }
  if (item.value.katasandi != item.value.katasandi2) {
    toaster.error('Kata Sandi tidak sama')
    return
  }

  var objSave = {
    loginuser: {
      id: item.value.id ? item.value.id : '',
      namauser: item.value.namauser,
      objectpegawaifk: useUserSession().getUser().pegawai.id,
      objectkelompokuserfk: useUserSession().getUser().kelompokUser.id,
      katasandi: item.value.katasandi,
    },
  }

  isLoadingTT.value = true
  await useApi()
    .post(`/sysadmin/save-login-user`, objSave)
    .then(
      () => {
        isLoadingTT.value = false
        clear()
      },
      () => {
        isLoadingTT.value = false
      },
    )
}

const clear = () => {
  item.value = {}
  modalInput.value = false
}

async function initPage() {
  if (isCustomer.value) {
    await userByID(userId.value)
  } else {
    loadDropdown()
    await fetchPegawai(ID_PEGAWAI)
    await pegawaiByID(ID_PEGAWAI)
  }
}

initPage()
</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/components/profile-stats';

.is-navbar {
  .profile-wrapper {
    margin-top: 30px;
  }
}

.profile-wrapper {
  max-width: 1040px;
  margin: 0 auto;

  .customer-profile-back {
    display: flex;
    justify-content: flex-start;
    margin-bottom: 16px;
  }

  .profile-header {
    text-align: center;

    >img {
      display: block;
      margin: 0 auto;
      max-width: 300px;
    }

    .v-avatar {
      margin: 0 auto 12px;
    }

    .anim-icon {
      margin-bottom: 12px;
    }

    .title {
      margin-bottom: 6px;
    }

    p {
      font-size: 1rem;
      max-width: 540px;
      margin: 0 auto;
      line-height: 1.3;
    }
  }

  .profile-body {
    padding: 10px 0 20px;

    .profile-card {
      @include vuero-s-card;
      padding: 30px;

      &:not(:last-child) {
        margin-bottom: 20px;
      }

      .profile-card-section {
        padding-bottom: 20px;

        &:not(:last-child) {
          margin-bottom: 20px;
          border-bottom: 1px solid var(--fade-grey-dark-4);
        }

        &.no-padding {
          padding-bottom: 0;
        }

        .section-title {
          display: flex;
          align-items: center;
          margin-bottom: 12px;

          h4 {
            font-family: var(--font-alt);
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            color: var(--dark-text);
            margin-right: 6px;
          }

          i {
            color: var(--primary);
          }

          .action-link {
            position: relative;
            top: -2px;
            margin-left: auto;
            text-transform: uppercase;
            font-size: 0.8rem;
          }

          .control {
            margin-left: auto;

            .form-switch {
              transform: scale(0.8);
            }
          }
        }

        .customer-section-title-action {
          justify-content: space-between;
          gap: 12px;
          flex-wrap: wrap;
        }

        .section-content {
          .description {
            font-size: 0.95rem;
          }

          .experience-wrapper {
            display: flex;
            flex-wrap: wrap;
            margin-left: -8px;
            margin-right: -8px;

            .experience-item {
              display: flex;
              align-items: center;
              width: calc(50% - 16px);
              margin: 8px;

              img {
                display: block;
                width: 50px;
                min-width: 50px;
                height: 50px;
                border-radius: var(--square-rounded);
                border: 1px solid var(--fade-grey-dark-4);
              }

              .meta {
                margin-left: 10px;

                >span {
                  font-family: var(--font);
                  display: block;

                  &:first-child {
                    font-family: var(--font-alt);
                    font-weight: 600;
                    color: var(--dark-text);
                    font-size: 0.85rem;
                  }

                  &:nth-child(2),
                  &:nth-child(3) {
                    font-size: 0.85rem;
                    color: var(--light-text);

                    i {
                      position: relative;
                      top: -2px;
                      font-size: 4px;
                      margin: 0 6px;
                    }
                  }

                  &:nth-child(3) {
                    color: var(--primary);
                  }

                  span {
                    display: inline-block;
                  }
                }
              }
            }
          }

          .customer-experience-wrapper {
            .customer-experience-item {
              align-items: center;
            }
          }

          .customer-icon-box {
            width: 52px;
            min-width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--fade-grey-dark-4);
            background: #f8fafc;

            i {
              font-size: 1.2rem;
            }

            &.is-user {
              background: rgba(59, 130, 246, 0.08);
              color: #2563eb;
            }

            &.is-email {
              background: rgba(14, 165, 233, 0.08);
              color: #0284c7;
            }

            &.is-phone {
              background: rgba(34, 197, 94, 0.08);
              color: #16a34a;
            }

            &.is-jabatan {
              background: rgba(168, 85, 247, 0.08);
              color: #9333ea;
            }

            &.is-mitra {
              background: rgba(249, 115, 22, 0.08);
              color: #ea580c;
            }

            &.is-tipe {
              background: rgba(236, 72, 153, 0.08);
              color: #db2777;
            }

            &.is-date {
              background: rgba(20, 184, 166, 0.08);
              color: #0f766e;
            }

            &.is-date-update {
              background: rgba(99, 102, 241, 0.08);
              color: #4f46e5;
            }

            &.is-status {
              background: rgba(34, 197, 94, 0.08);
              color: #15803d;
            }

            &.is-new {
              background: rgba(245, 158, 11, 0.08);
              color: #d97706;
            }
          }
        }
      }
    }
  }
}

.readonly-info-box {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  padding: 14px 16px;
  border-radius: 14px;
  background: #f8fafc;
  border: 1px dashed #cbd5e1;

  span {
    display: block;
    font-size: 11px;
    color: var(--light-text);
    text-transform: uppercase;
    font-weight: 800;
    letter-spacing: 0.06em;
  }

  strong {
    display: block;
    margin-top: 4px;
    color: var(--dark-text);
    font-size: 13px;
  }

  small {
    color: var(--light-text);
    text-align: right;
  }
}

.edit-profile-form {
  padding-top: 4px;
}

.is-dark {
  .profile-wrapper {
    .profile-header {
      .v-avatar {
        .badge {
          border-color: var(--dark-sidebar-light-6);
        }
      }
    }

    .profile-body {
      .profile-card {
        @include vuero-card--dark;

        .profile-card-section {
          border-color: var(--dark-sidebar-light-12);

          .section-title {
            h4 {
              color: var(--dark-dark-text);
            }

            i {
              color: var(--primary);
            }
          }

          .section-content {
            .experience-wrapper {
              .experience-item {
                >img {
                  border-color: var(--dark-sidebar-light-12);
                }

                .meta {
                  >span {
                    &:nth-child(3) {
                      color: var(--primary);
                    }
                  }
                }
              }
            }

            .customer-icon-box {
              border-color: var(--dark-sidebar-light-12) !important;
              background: var(--dark-sidebar-light-2) !important;
            }
          }
        }
      }
    }
  }

  .readonly-info-box {
    background: var(--dark-sidebar-light-2);
    border-color: var(--dark-sidebar-light-12);

    strong {
      color: var(--dark-dark-text);
    }
  }
}

@media only screen and (max-width: 767px) {
  .profile-wrapper {
    .profile-body {
      .profile-card {
        padding: 20px;

        .profile-card-section {
          .section-content {

            .experience-wrapper,
            .languages-wrapper,
            .recommendations-wrapper {

              .experience-item,
              .languages-item,
              .recommendations-item {
                width: calc(100% - 16px);
              }
            }
          }
        }
      }
    }
  }

  .readonly-info-box {
    flex-direction: column;
    align-items: flex-start;

    small {
      text-align: left;
    }
  }
}
</style>
