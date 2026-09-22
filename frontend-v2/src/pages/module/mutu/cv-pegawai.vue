<template>
  <div class="column">
    <VCard class="cv-header-card">
      <div class="cv-hero">
        <div class="cv-hero-left">
          <img src="/@src/assets/illustrations/dashboards/personal/UMRO.png" alt="UMRO Laboratory"
            class="cv-hero-logo" />
          <div class="cv-hero-text">
            <h3 class="cv-hero-title">FMMO-163-14.4.3.b-62.2 Curriculum Vitae Personel</h3>
            <p class="cv-hero-sub">Input Curriculum Vitae Pegawai</p>
          </div>
        </div>

        <div class="cv-hero-right">
          <VButton color="info" outlined icon="feather:refresh-ccw" @click="loadCv()">
            Refresh
          </VButton>
          <VButton color="warning" raised icon="feather:printer" @click="cetakCvPegawai()">
            Cetak PDF
          </VButton>
          <VButton color="primary" raised icon="feather:save" :loading="isSaving" @click="saveCv()">
            Simpan CV
          </VButton>
        </div>
      </div>
    </VCard>
  </div>

  <div class="column">
    <VCard>
      <div class="mb-4">
        <h3 class="title is-5 mb-1">I. Data Pribadi</h3>
        <p class="is-size-7 has-text-grey">Jika CV belum pernah disimpan, data akan otomatis diambil dari master
          pegawai.</p>
      </div>

      <div class="columns is-multiline">
        <div class="column is-6">
          <VField label="Nama">
            <VControl icon="feather:user">
              <VInput v-model="form.nama" placeholder="Nama lengkap" />
            </VControl>
          </VField>
        </div>

        <div class="column is-6">
          <VField label="Tempat Lahir">
            <VControl icon="feather:map-pin">
              <VInput v-model="form.tempatLahir" placeholder="Contoh: Cimahi" />
            </VControl>
          </VField>
        </div>

        <div class="column is-4">
          <VField label="Tanggal Lahir">
            <VDatePicker v-model="form.tanggalLahir" color="info">
              <template #default="{ inputValue, inputEvents }">
                <VField>
                  <VControl icon="feather:calendar">
                    <VInput :value="inputValue" class="input-calendar" v-on="inputEvents" />
                  </VControl>
                </VField>
              </template>
            </VDatePicker>
          </VField>
        </div>

        <div class="column is-4">
          <VField label="Jenis Kelamin">
            <AutoComplete v-model="form.jenisKelaminObj" :suggestions="d_jenisKelamin"
              @complete="fetchJenisKelamin($event)" :optionLabel="'label'" :dropdown="true" :minLength="0"
              class="is-input" :appendTo="'body'" :loadingIcon="'pi pi-spinner'" :field="'label'"
              placeholder="Pilih jenis kelamin..." />
          </VField>
        </div>

        <div class="column is-4">
          <VField label="Agama">
            <AutoComplete v-model="form.agamaObj" :suggestions="d_agama" @complete="fetchAgama($event)"
              :optionLabel="'label'" :dropdown="true" :minLength="0" class="is-input" :appendTo="'body'"
              :loadingIcon="'pi pi-spinner'" :field="'label'" placeholder="Pilih agama..." />
          </VField>
        </div>

        <div class="column is-4">
          <VField label="Status Pernikahan">
            <VControl>
              <div class="select is-fullwidth">
                <select v-model="form.statusPernikahan">
                  <option value="">Pilih status</option>
                  <option value="Belum Menikah">Belum Menikah</option>
                  <option value="Menikah">Menikah</option>
                  <option value="Cerai">Cerai</option>
                </select>
              </div>
            </VControl>
          </VField>
        </div>

        <div class="column is-4">
          <VField label="Warga Negara">
            <VControl icon="feather:flag">
              <VInput v-model="form.wargaNegara" placeholder="Contoh: Indonesia" />
            </VControl>
          </VField>
        </div>

        <div class="column is-4">
          <VField label="Penguasaan Bahasa">
            <VControl icon="feather:globe">
              <VInput v-model="form.penguasaanBahasa" placeholder="Contoh: Indonesia, Inggris" />
            </VControl>
          </VField>
        </div>

        <div class="column is-12">
          <VField label="Alamat KTP">
            <VControl icon="feather:home">
              <textarea class="textarea" rows="3" v-model="form.alamatKtp" placeholder="Alamat KTP"></textarea>
            </VControl>
          </VField>
        </div>

        <div class="column is-12">
          <VField label="Alamat Sekarang">
            <VControl icon="feather:map">
              <textarea class="textarea" rows="3" v-model="form.alamatSekarang"
                placeholder="Alamat sekarang"></textarea>
            </VControl>
          </VField>
        </div>

        <div class="column is-4">
          <VField label="Nomor Telepon / HP">
            <VControl icon="feather:phone">
              <VInput v-model="form.noHp" placeholder="08xxxxxxxxxx" />
            </VControl>
          </VField>
        </div>

        <div class="column is-4">
          <VField label="E-mail">
            <VControl icon="feather:mail">
              <VInput v-model="form.email" placeholder="email@contoh.com" />
            </VControl>
          </VField>
        </div>

        <div class="column is-4">
          <VField label="Akun Media Sosial">
            <VControl icon="feather:instagram">
              <VInput v-model="form.akunMediaSosial" placeholder="Contoh: instagram @namaakun" />
            </VControl>
          </VField>
        </div>

        <div class="column is-6">
          <VField label="Jabatan Saat Ini">
            <VControl icon="feather:briefcase">
              <VInput v-model="form.jabatanSaatIni" placeholder="Contoh: Technician Calibration" />
            </VControl>
          </VField>
        </div>

        <div class="column is-6">
          <VField label="Kota Tanda Tangan">
            <VControl icon="feather:map-pin">
              <VInput v-model="form.kotaTtd" placeholder="Contoh: Jakarta" />
            </VControl>
          </VField>
        </div>

        <div class="column is-4">
          <VField label="Tanggal Tanda Tangan">
            <VDatePicker v-model="form.tanggalTtd" color="info">
              <template #default="{ inputValue, inputEvents }">
                <VField>
                  <VControl icon="feather:calendar">
                    <VInput :value="inputValue" class="input-calendar" v-on="inputEvents" />
                  </VControl>
                </VField>
              </template>
            </VDatePicker>
          </VField>
        </div>

        <div class="column is-8">
          <VField label="Nama Penanda Tangan">
            <VControl icon="feather:edit-3">
              <VInput v-model="form.namaTtd" placeholder="Nama penanda tangan" />
            </VControl>
          </VField>
        </div>
      </div>
    </VCard>
  </div>

  <div class="column">
    <VCard>
      <div class="is-flex is-align-items-center is-justify-content-space-between mb-4">
        <div>
          <h3 class="title is-5 mb-1">II. Pendidikan Formal</h3>
          <p class="is-size-7 has-text-grey">Tambahkan riwayat pendidikan formal.</p>
        </div>
        <VButton color="primary" icon="feather:plus" raised @click="addPendidikanFormal()">
          Tambah
        </VButton>
      </div>

      <DataTable :value="form.pendidikanFormal" class="p-datatable-sm" responsiveLayout="stack" breakpoint="960px"
        showGridlines>
        <Column header="#" style="width:60px">
          <template #body="slotProps">{{ slotProps.index + 1 }}</template>
        </Column>

        <Column header="Tahun Mulai" style="min-width:140px">
          <template #body="slotProps">
            <InputText v-model="slotProps.data.tahunMulai" class="w-100" />
          </template>
        </Column>

        <Column header="Tahun Selesai" style="min-width:140px">
          <template #body="slotProps">
            <InputText v-model="slotProps.data.tahunSelesai" class="w-100" />
          </template>
        </Column>

        <Column header="Sekolah / Institusi / Universitas" style="min-width:280px">
          <template #body="slotProps">
            <InputText v-model="slotProps.data.institusi" class="w-100" />
          </template>
        </Column>

        <Column header="Jurusan" style="min-width:180px">
          <template #body="slotProps">
            <InputText v-model="slotProps.data.jurusan" class="w-100" />
          </template>
        </Column>

        <Column header="Jenjang Pendidikan" style="min-width:180px">
          <template #body="slotProps">
            <InputText v-model="slotProps.data.jenjang" class="w-100" />
          </template>
        </Column>

        <Column header="Aksi" style="width:100px">
          <template #body="slotProps">
            <VIconButton color="danger" icon="feather:trash-2" outlined
              @click="removePendidikanFormal(slotProps.index)" />
          </template>
        </Column>
      </DataTable>
    </VCard>
  </div>

  <div class="column">
    <VCard>
      <div class="mb-4">
        <h3 class="title is-5 mb-1">III. Pendidikan Non Formal / Training – Seminar</h3>
        <p class="is-size-7 has-text-grey">Bagian ini otomatis ditarik dari riwayat pelatihan pegawai.</p>
      </div>

      <DataTable :value="riwayatPelatihan" class="p-datatable-sm" :loading="loadingPelatihan" responsiveLayout="stack"
        breakpoint="960px" showGridlines>
        <template #empty>
          Belum ada riwayat pelatihan.
        </template>

        <Column header="#" style="width:60px">
          <template #body="slotProps">{{ slotProps.index + 1 }}</template>
        </Column>

        <Column field="tahun" header="Tahun" style="min-width:120px"></Column>
        <Column field="lembaga" header="Lembaga / Instansi" style="min-width:240px"></Column>
        <Column field="keterampilan" header="Keterampilan" style="min-width:320px"></Column>
        <!-- <Column field="status" header="Status" style="min-width:140px">
          <template #body="slotProps">
            <VTag :color="statusColor(slotProps.data.status)" rounded>{{ slotProps.data.status }}</VTag>
          </template>
        </Column> -->
      </DataTable>
    </VCard>
  </div>

  <div class="column">
    <VCard>
      <div class="is-flex is-align-items-center is-justify-content-space-between mb-4">
        <div>
          <h3 class="title is-5 mb-1">IV. Prestasi / Penghargaan</h3>
          <p class="is-size-7 has-text-grey">Tambahkan prestasi atau penghargaan pegawai.</p>
        </div>
        <VButton color="primary" icon="feather:plus" raised @click="addPrestasi()">
          Tambah
        </VButton>
      </div>

      <DataTable :value="form.prestasi" class="p-datatable-sm" responsiveLayout="stack" breakpoint="960px"
        showGridlines>
        <Column header="#" style="width:60px">
          <template #body="slotProps">{{ slotProps.index + 1 }}</template>
        </Column>

        <Column header="Tahun" style="min-width:120px">
          <template #body="slotProps">
            <InputText v-model="slotProps.data.tahun" class="w-100" />
          </template>
        </Column>

        <Column header="Lembaga / Instansi" style="min-width:240px">
          <template #body="slotProps">
            <InputText v-model="slotProps.data.lembaga" class="w-100" />
          </template>
        </Column>

        <Column header="Pencapaian" style="min-width:320px">
          <template #body="slotProps">
            <InputText v-model="slotProps.data.pencapaian" class="w-100" />
          </template>
        </Column>

        <Column header="Aksi" style="width:100px">
          <template #body="slotProps">
            <VIconButton color="danger" icon="feather:trash-2" outlined @click="removePrestasi(slotProps.index)" />
          </template>
        </Column>
      </DataTable>
    </VCard>
  </div>

  <div class="column">
    <VCard>
      <div class="is-flex is-align-items-center is-justify-content-space-between mb-4">
        <div>
          <h3 class="title is-5 mb-1">V. Riwayat Pengalaman Kerja</h3>
          <p class="is-size-7 has-text-grey">Tambahkan pengalaman kerja pegawai.</p>
        </div>
        <VButton color="primary" icon="feather:plus" raised @click="addPengalamanKerja()">
          Tambah
        </VButton>
      </div>

      <DataTable :value="form.pengalamanKerja" class="p-datatable-sm" responsiveLayout="stack" breakpoint="960px"
        showGridlines>
        <Column header="#" style="width:60px">
          <template #body="slotProps">{{ slotProps.index + 1 }}</template>
        </Column>

        <Column header="Periode Mulai" style="min-width:180px">
          <template #body="slotProps">
            <InputText v-model="slotProps.data.periodeMulai" class="w-100" placeholder="Contoh: Juli 2023" />
          </template>
        </Column>

        <Column header="Periode Selesai" style="min-width:180px">
          <template #body="slotProps">
            <InputText v-model="slotProps.data.periodeSelesai" class="w-100" placeholder="Contoh: Sekarang" />
          </template>
        </Column>

        <Column header="Instansi / Perusahaan" style="min-width:260px">
          <template #body="slotProps">
            <InputText v-model="slotProps.data.instansi" class="w-100" />
          </template>
        </Column>

        <Column header="Posisi" style="min-width:220px">
          <template #body="slotProps">
            <InputText v-model="slotProps.data.posisi" class="w-100" />
          </template>
        </Column>

        <Column header="Aksi" style="width:100px">
          <template #body="slotProps">
            <VIconButton color="danger" icon="feather:trash-2" outlined
              @click="removePengalamanKerja(slotProps.index)" />
          </template>
        </Column>
      </DataTable>
    </VCard>
  </div>

  <div class="column">
    <VCard>
      <div v-if="saveError" class="notification is-danger is-light">
        <i class="fas fa-triangle-exclamation"></i>
        <span class="ml-2">{{ saveError }}</span>
      </div>

      <div v-if="saveSuccess" class="notification is-success is-light">
        <i class="fas fa-check-circle"></i>
        <span class="ml-2">{{ saveSuccess }}</span>
      </div>

      <div class="is-flex is-justify-content-flex-end" style="gap:.75rem">
        <VButton color="info" outlined icon="feather:refresh-ccw" @click="loadCv()">
          Muat Ulang
        </VButton>
        <VButton color="primary" raised icon="feather:save" :loading="isSaving" @click="saveCv()">
          Simpan CV
        </VButton>
      </div>
    </VCard>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import AutoComplete from 'primevue/autocomplete'
import { useApi } from '/@src/composable/useApi'
import { useUserSession } from '/@src/stores/userSession'
import { useHead } from '@vueuse/head'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import moment from 'moment'
import * as H from '/@src/utils/appHelper'

useHead({ title: 'CV Pegawai - ' + import.meta.env.VITE_PROJECT })
useViewWrapper().setFullWidth(true)

const userLogin = useUserSession().getUser()
const isSaving = ref(false)
const loadingPelatihan = ref(false)
const saveError = ref('')
const saveSuccess = ref('')

const d_agama = ref([])
const d_jenisKelamin = ref([])

const form: any = ref({
  nama: '',
  tempatLahir: '',
  tanggalLahir: new Date(),
  jenisKelaminObj: null,
  agamaObj: null,
  statusPernikahan: '',
  wargaNegara: 'Indonesia',
  penguasaanBahasa: '',
  alamatKtp: '',
  alamatSekarang: '',
  noHp: '',
  email: '',
  akunMediaSosial: '',
  jabatanSaatIni: '',
  kotaTtd: '',
  tanggalTtd: new Date(),
  namaTtd: '',
  pendidikanFormal: [],
  prestasi: [],
  pengalamanKerja: [],
})

const riwayatPelatihan: any = ref([])

const createPendidikanFormalRow = () => ({
  tahunMulai: '',
  tahunSelesai: '',
  institusi: '',
  jurusan: '',
  jenjang: '',
})

const createPrestasiRow = () => ({
  tahun: '',
  lembaga: '',
  pencapaian: '',
})

const createPengalamanKerjaRow = () => ({
  periodeMulai: '',
  periodeSelesai: '',
  instansi: '',
  posisi: '',
})

const statusColor = (status: string) => {
  if (!status) return 'warning'
  const s = (status || '').toLowerCase()
  if (s.includes('setuju')) return 'success'
  if (s.includes('tolak')) return 'danger'
  return 'warning'
}

const addPendidikanFormal = () => {
  form.value.pendidikanFormal.push(createPendidikanFormalRow())
}

const removePendidikanFormal = (index: number) => {
  form.value.pendidikanFormal.splice(index, 1)
}

const addPrestasi = () => {
  form.value.prestasi.push(createPrestasiRow())
}

const removePrestasi = (index: number) => {
  form.value.prestasi.splice(index, 1)
}

const addPengalamanKerja = () => {
  form.value.pengalamanKerja.push(createPengalamanKerjaRow())
}

const removePengalamanKerja = (index: number) => {
  form.value.pengalamanKerja.splice(index, 1)
}

const fetchAgama = async (filter: any) => {
  const q = filter?.query || ''
  await useApi().get(
    `general/dropdown/agama_m?select=id,agama&param_search=agama&query=${encodeURIComponent(q)}&limit=10`
  ).then((response) => {
    d_agama.value = response
  })
}

const fetchJenisKelamin = async (filter: any) => {
  const q = filter?.query || ''
  await useApi().get(
    `general/dropdown/jeniskelamin_m?select=id,jeniskelamin&param_search=jeniskelamin&query=${encodeURIComponent(q)}&limit=10`
  ).then((response) => {
    d_jenisKelamin.value = response
  })
}

const ensureMasterLoaded = async () => {
  await fetchAgama({ query: '' })
  await fetchJenisKelamin({ query: '' })
}

const loadRiwayatPelatihan = async () => {
  loadingPelatihan.value = true
  try {
    const pegawaiId = userLogin.pegawai?.id || ''
    const res = await useApi().get(`mutu/cv-pegawai-riwayat-pelatihan?id=${pegawaiId}`)
    riwayatPelatihan.value = res || []
  } catch (e) {
    riwayatPelatihan.value = []
  } finally {
    loadingPelatihan.value = false
  }
}

const mapMasterOption = (list: any[], idKey: string | number, labelKey = 'label') => {
  return (list || []).find((e: any) => String(e?.value) === String(idKey)) || null
}

const loadCv = async () => {
  saveError.value = ''
  saveSuccess.value = ''

  await ensureMasterLoaded()

  try {
    const pegawaiId = userLogin.pegawai?.id || ''
    const res = await useApi().get(`mutu/cv-pegawai?id=${pegawaiId}`)

    if (res && res.cv) {
      form.value = {
        nama: res.cv.nama || '',
        tempatLahir: res.cv.tempatlahir || '',
        tanggalLahir: res.cv.tanggallahir ? new Date(res.cv.tanggallahir) : new Date(),
        jenisKelaminObj: res.cv.objectjeniskelaminfk
          ? mapMasterOption(d_jenisKelamin.value as any[], res.cv.objectjeniskelaminfk)
          : (res.cv.jeniskelamin ? { value: null, label: res.cv.jeniskelamin } : null),
        agamaObj: res.cv.objectagamafk
          ? mapMasterOption(d_agama.value as any[], res.cv.objectagamafk)
          : (res.cv.agama ? { value: null, label: res.cv.agama } : null),
        statusPernikahan: res.cv.statuspernikahan || '',
        wargaNegara: res.cv.warganegara || 'Indonesia',
        penguasaanBahasa: res.cv.penguasaanbahasa || '',
        alamatKtp: res.cv.alamatktp || '',
        alamatSekarang: res.cv.alamatsekarang || '',
        noHp: res.cv.nohp || '',
        email: res.cv.email || '',
        akunMediaSosial: res.cv.akunmediasosial || '',
        jabatanSaatIni: res.cv.jabatansaatini || '',
        kotaTtd: res.cv.kotattd || '',
        tanggalTtd: res.cv.tanggalttd ? new Date(res.cv.tanggalttd) : new Date(),
        namaTtd: res.cv.namattd || '',
        pendidikanFormal: res.cv.pendidikanformal || [],
        prestasi: res.cv.prestasi || [],
        pengalamanKerja: res.cv.pengalamankerja || [],
      }
    } else if (res && res.pegawai) {
      const pg = res.pegawai
      form.value = {
        nama: pg.namalengkap || '',
        tempatLahir: pg.tempatlahir || '',
        tanggalLahir: pg.tgllahir ? new Date(pg.tgllahir) : new Date(),
        jenisKelaminObj: pg.objectjeniskelaminfk
          ? mapMasterOption(d_jenisKelamin.value as any[], pg.objectjeniskelaminfk)
          : (pg.jeniskelamin ? { value: pg.objectjeniskelaminfk || null, label: pg.jeniskelamin } : null),
        agamaObj: pg.objectagamafk
          ? mapMasterOption(d_agama.value as any[], pg.objectagamafk)
          : (pg.agama ? { value: pg.objectagamafk || null, label: pg.agama } : null),
        statusPernikahan: '',
        wargaNegara: 'Indonesia',
        penguasaanBahasa: '',
        alamatKtp: pg.alamat || '',
        alamatSekarang: pg.alamat || '',
        noHp: pg.nohandphone || pg.notlp || '',
        email: pg.email || '',
        akunMediaSosial: '',
        jabatanSaatIni: pg.namajabatanulab || '',
        kotaTtd: '',
        tanggalTtd: new Date(),
        namaTtd: pg.namalengkap || '',
        pendidikanFormal: [createPendidikanFormalRow()],
        prestasi: [createPrestasiRow()],
        pengalamanKerja: [createPengalamanKerjaRow()],
      }
    } else {
      form.value.nama = userLogin.pegawai?.namalengkap || ''
      if (form.value.pendidikanFormal.length === 0) form.value.pendidikanFormal = [createPendidikanFormalRow()]
      if (form.value.prestasi.length === 0) form.value.prestasi = [createPrestasiRow()]
      if (form.value.pengalamanKerja.length === 0) form.value.pengalamanKerja = [createPengalamanKerjaRow()]
    }
  } catch (e) {
  }

  await loadRiwayatPelatihan()
}

const saveCv = async () => {
  saveError.value = ''
  saveSuccess.value = ''

  if (!form.value.nama) {
    saveError.value = 'Nama wajib diisi.'
    return
  }

  isSaving.value = true
  try {
    const pegawaiId = userLogin.pegawai?.id || ''
    await useApi().post('mutu/save-cv-pegawai', {
      pegawaifk: pegawaiId,
      nama: form.value.nama,
      tempatLahir: form.value.tempatLahir,
      tanggalLahir: form.value.tanggalLahir ? moment(form.value.tanggalLahir).format('YYYY-MM-DD') : null,
      objectjeniskelaminfk: form.value.jenisKelaminObj?.value || null,
      jenisKelamin: form.value.jenisKelaminObj?.label || '',
      objectagamafk: form.value.agamaObj?.value || null,
      agama: form.value.agamaObj?.label || '',
      statusPernikahan: form.value.statusPernikahan,
      wargaNegara: form.value.wargaNegara,
      penguasaanBahasa: form.value.penguasaanBahasa,
      alamatKtp: form.value.alamatKtp,
      alamatSekarang: form.value.alamatSekarang,
      noHp: form.value.noHp,
      email: form.value.email,
      akunMediaSosial: form.value.akunMediaSosial,
      jabatanSaatIni: form.value.jabatanSaatIni,
      kotaTtd: form.value.kotaTtd,
      tanggalTtd: form.value.tanggalTtd ? moment(form.value.tanggalTtd).format('YYYY-MM-DD') : null,
      namaTtd: form.value.namaTtd,
      pendidikanFormal: form.value.pendidikanFormal,
      prestasi: form.value.prestasi,
      pengalamanKerja: form.value.pengalamanKerja,
    })
    saveSuccess.value = 'CV pegawai berhasil disimpan.'
    await loadCv()
  } catch (e: any) {
    saveError.value = e?.response?._data?.message || 'Simpan CV gagal.'
  } finally {
    isSaving.value = false
  }
}

const cetakCvPegawai = () => {
  const pegawaiId = userLogin.pegawai?.id || ''
  H.printBlade(`mutu/cetak-cv-pegawai?pdf=true&id=${pegawaiId}`)
}

loadCv()
</script>

<style lang="scss">
.w-100 {
  width: 100%;
}

.input-calendar {
  min-width: 140px;
}

@import '/@src/scss/abstracts/all';
@import '/@src/scss/module/dashboard/customer.scss';

.cv-header-card {
  padding: 0;
  overflow: hidden;
  border-radius: 14px;
}

.cv-hero {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 18px 22px;
  background: linear-gradient(135deg, rgba(16, 185, 129, .08), rgba(59, 130, 246, .08));
  border: 1px solid rgba(0, 0, 0, .06);
}

.cv-hero-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.cv-hero-logo {
  height: 56px;
  width: auto;
  object-fit: contain;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, .08));
}

.cv-hero-text {
  line-height: 1.2;
}

.cv-hero-title {
  margin: 0;
  font-weight: 700;
  font-size: 1.35rem;
  letter-spacing: .2px;
}

.cv-hero-sub {
  margin: .2rem 0 0;
  font-size: .95rem;
  color: #6b7280;
}

.cv-hero-right {
  display: flex;
  gap: .75rem;
}

@media (max-width: 768px) {
  .cv-hero {
    flex-direction: column;
    align-items: flex-start;
    padding: 16px;
    gap: .75rem;
  }

  .cv-hero-logo {
    height: 48px;
  }

  .cv-hero-right {
    width: 100%;
    display: flex;
    flex-direction: column;
  }

  .cv-hero-right .button {
    width: 100%;
  }
}
</style>