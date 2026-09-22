<route lang="yaml">
    meta:
      requiresAuth: true
</route>

<template>
    <div class="columns">
        <div class="column is-12 form-layout is-stacked">
            <div class="form-outer">
                <div :class="[isStuck && 'is-stuck']" class="form-header stuck-header">
                    <div class="form-header-inner">
                        <div class="left">
                            <h3>Rekam Data Pegawai</h3>
                        </div>
                        <div class="right">
                            <div class="buttons">
                                <VButton icon="lnir lnir-arrow-left rem-100" @click="back()" light dark-outlined>
                                    Cancel
                                </VButton>
                                <VButton icon="feather:save" type="submit" color="primary" raised
                                    @click="simpanPegawai()" :loading="isSimpan">
                                    Save
                                </VButton>
                            </div>
                        </div>
                    </div>
                </div>
                <div></div>
                <div class="form-body p-4">
                    <VTabs slider selected="Pribadi" :tabs="[
                        { label: 'Data Pribadi', value: 'Pribadi' },
                        { label: 'Status Kepegawain', value: 'Kepegawaian' },
                    ]">
                        <template #tab="{ activeValue }">
                            <p v-if="activeValue === 'Pribadi'">
                            <div class="columns is-multiline">
                                <div class="column is-12">
                                    <div class="columns is-multiline">
                                        <div class="column is-12">
                                            <VCard>
                                                <div class="columns is-multiline">
                                                    
                                                    <div class="column is-12 has-text-centered">
                                                        
                                                        <div v-if="item.filenameFoto && !fileFoto" class="mb-4">
                                                            <figure class="image is-128x128 is-inline-block">
                                                                <img class="is-rounded" 
                                                                     :src="`/berkas-mutu/${item.filenameFoto}`"
                                                                     alt="Foto Pegawai" 
                                                                     style="height: 128px; width: 128px; object-fit: cover; border: 3px solid #e0e0e0;"
                                                                     @error.once="(event) => onceImageErrored(event, '128x128')"
                                                                />
                                                            </figure>
                                                            <p class="help is-info mt-2">Foto Saat Ini</p>
                                                        </div>

                                                        <VField>
                                                            <VControl>
                                                                <VFilePond 
                                                                    name="profile_filepond"
                                                                    class="profile-filepond"
                                                                    :chunk-retry-delays="[500, 1000, 3000]"
                                                                    label-idle="<i class='lnil lnil-cloud-upload'></i> <br> <span class='filepond--label-action'> Upload Foto Baru </span>"
                                                                    :accepted-file-types="['image/png', 'image/jpeg', 'image/gif']"
                                                                    :image-preview-height="140"
                                                                    :image-resize-target-width="140"
                                                                    :image-resize-target-height="140"
                                                                    image-crop-aspect-ratio="1:1"
                                                                    style-panel-layout="compact circle"
                                                                    style-load-indicator-position="center bottom"
                                                                    style-progress-indicator-position="right bottom"
                                                                    style-button-remove-item-position="left bottom"
                                                                    style-button-process-item-position="right bottom"
                                                                    @addfile="onAddFile" 
                                                                    @removefile="onRemoveFile" 
                                                                />
                                                            </VControl>
                                                        </VField>
                                                    </div>
                                                    <div class="column is-4">
                                                        <VField>
                                                            <VLabel>Nama Lengkap</VLabel>
                                                            <VControl icon="feather:user">
                                                                <VInput type="text" v-model="item.namalengkap"
                                                                    placeholder="Nama Lengkap" class="is-rounded" />
                                                            </VControl>
                                                        </VField>
                                                    </div>
                                                    <div class="column is-4">
                                                        <VField>
                                                            <VLabel> NIK </VLabel>
                                                            <VControl icon="feather:credit-card">
                                                                <VInput type="number" v-model="item.nik"
                                                                    placeholder="NIK" class="is-rounded" />
                                                            </VControl>
                                                        </VField>
                                                    </div>
                                                    <div class="column is-4">
                                                        <VField>
                                                            <VLabel> Tempat Lahir </VLabel>
                                                            <VControl icon="feather:home">
                                                                <VInput type="text" v-model="item.tempatlahir"
                                                                    placeholder="Tempat Lahir" class="is-rounded" />
                                                            </VControl>
                                                        </VField>
                                                    </div>
                                                    <div class="column is-4">
                                                        <VDatePicker v-model="item.tgllahir" color="green" trim-weeks
                                                            mode="date">
                                                            <template #default="{ inputValue, inputEvents }">
                                                                <VField>
                                                                    <VLabel>Tanggal Lahir</VLabel>
                                                                    <VControl icon="feather:calendar">
                                                                        <VInput type="text" placeholder="Pilih Tanggal"
                                                                            class="is-rounded" :value="inputValue"
                                                                            v-on="inputEvents" :disabled="disTanggal" />
                                                                    </VControl>
                                                                </VField>
                                                            </template>
                                                        </VDatePicker>
                                                    </div>
                                                    <div class="column is-4">
                                                        <VField class="is-rounded-select is-autocomplete-select">
                                                            <VLabel>Pendidikan Terakhir</VLabel>
                                                            <VControl icon="feather:search">
                                                                <Multiselect mode="single"
                                                                    v-model="item.objectpendidikanterakhirfk"
                                                                    :options="d_pendidikan"
                                                                    placeholder="Pilih Pendidikan" :searchable="true"
                                                                    :disabled="disabledRuangan" />
                                                            </VControl>
                                                        </VField>
                                                    </div>
                                                    <div class="column is-4">
                                                        <VField>
                                                            <VLabel> Gelar Depan </VLabel>
                                                            <VControl icon="feather:credit-card">
                                                                <VInput type="text" v-model="item.gelardepan"
                                                                    placeholder="Gelar Depan" class="is-rounded" />
                                                            </VControl>
                                                        </VField>
                                                    </div>
                                                    <div class="column is-4">
                                                        <VField>
                                                            <VLabel> Gelar Belakang </VLabel>
                                                            <VControl icon="feather:credit-card">
                                                                <VInput type="text" v-model="item.gelarbelakang"
                                                                    placeholder="Gelar Belakang" class="is-rounded" />
                                                            </VControl>
                                                        </VField>
                                                    </div>
                                                    <div class="column is-4">
                                                        <VField class="is-rounded-select is-autocomplete-select">
                                                            <VLabel>Jenis Kelamin</VLabel>
                                                            <VControl icon="feather:search">
                                                                <Multiselect mode="single"
                                                                    v-model="item.objectjeniskelaminfk"
                                                                    :options="d_jeniskelamin" placeholder="Pilih"
                                                                    :searchable="true" :disabled="disabledRuangan" />
                                                            </VControl>
                                                        </VField>
                                                    </div>
                                                    <div class="column is-4">
                                                        <VField class="is-rounded-select is-autocomplete-select">
                                                            <VLabel>Agama</VLabel>
                                                            <VControl icon="feather:search">
                                                                <Multiselect mode="single" v-model="item.objectagamafk"
                                                                    :options="d_agama" placeholder="Pilih"
                                                                    :searchable="true" :disabled="disabledRuangan" />
                                                            </VControl>
                                                        </VField>
                                                    </div>
                                                </div>
                                            </VCard>
                                        </div>

                                        <div class="column is-12">
                                            <Fieldset legend="Data Alamat" :toggleable="true" :collapsed="collapsedOps">
                                                <div class="columns is-multiline">

                                                    <div class="column is-12">
                                                        <VField>
                                                            <VLabel> Alamat </VLabel>
                                                            <VControl icon="feather:home">
                                                                <VInput type="text" v-model="item.alamat"
                                                                    placeholder="Alamat Domisili" class="is-rounded" />
                                                            </VControl>
                                                        </VField>
                                                    </div>
                                                    <div class="column is-4">
                                                        <VField>
                                                            <VLabel> Kode Pos </VLabel>
                                                            <VControl icon="feather:home">
                                                                <VInput type="number" v-model="item.kodepos"
                                                                    placeholder="Kode Pos" class="is-rounded" />
                                                            </VControl>
                                                        </VField>
                                                    </div>
                                                    <div class="column is-4">
                                                        <VField>
                                                            <VLabel> Nomor Telpon </VLabel>
                                                            <VControl icon="feather:phone-call">
                                                                <VInput type="number" v-model="item.nohandphone"
                                                                    placeholder="No. Telepon" class="is-rounded" />
                                                            </VControl>
                                                        </VField>
                                                    </div>
                                                    <div class="column is-4">
                                                        <VField>
                                                            <VLabel> Email </VLabel>
                                                            <VControl icon="feather:layers">
                                                                <VInput type="text" v-model="item.email"
                                                                    placeholder="Email" class="is-rounded" />
                                                            </VControl>
                                                        </VField>
                                                    </div>
                                                </div>
                                            </Fieldset>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </p>
                            <p v-else-if="activeValue === 'Kepegawaian'">
                            <div class="column is-12">
                                <div class="columns is-multiline">
                                    <div class="column is-4">
                                        <VField>
                                            <VLabel> NID </VLabel>
                                            <VControl icon="feather:credit-card">
                                                <VInput type="text" v-model="item.nid" placeholder="NID"
                                                    class="is-rounded" />
                                            </VControl>
                                        </VField>
                                    </div>
                                    <div class="column is-4">
                                        <VField class="is-rounded-select is-autocomplete-select">
                                            <VLabel>Status Pegawai</VLabel>
                                            <VControl icon="feather:search">
                                                <Multiselect mode="single" v-model="item.statuspegawaifk"
                                                    :options="d_statuspegawai" placeholder="Pilih" :searchable="true"
                                                    :disabled="disabledRuangan" />
                                            </VControl>
                                        </VField>
                                    </div>
                                    <div class="column is-4">
                                        <VDatePicker v-model="item.tglmasuk" color="green" trim-weeks mode="dateTime">
                                            <template #default="{ inputValue, inputEvents }">
                                                <VField>
                                                    <VLabel>Tanggal Masuk</VLabel>
                                                    <VControl icon="feather:calendar">
                                                        <VInput type="text" placeholder="Pilih Tanggal Masuk"
                                                            class="is-rounded" :value="inputValue" v-on="inputEvents"
                                                            :disabled="disTanggal" />
                                                    </VControl>
                                                </VField>
                                            </template>
                                        </VDatePicker>
                                    </div>
                                    <div class="column is-4">
                                        <VDatePicker v-model="item.tglkeluar" color="green" trim-weeks mode="dateTime">
                                            <template #default="{ inputValue, inputEvents }">
                                                <VField>
                                                    <VLabel>Tanggal Keluar</VLabel>
                                                    <VControl icon="feather:calendar">
                                                        <VInput type="text" placeholder="Pilih Tanggal Keluar"
                                                            class="is-rounded" :value="inputValue" v-on="inputEvents"
                                                            :disabled="disTanggal" />
                                                    </VControl>
                                                </VField>
                                            </template>
                                        </VDatePicker>
                                    </div>
                                    <div class="column is-4">
                                        <VField class="is-rounded-select is-autocomplete-select">
                                            <VLabel>Jabatan</VLabel>
                                            <VControl icon="feather:search">
                                                <Multiselect mode="single" v-model="item.jabatan1fk"
                                                    :options="d_jabatan" placeholder="Pilih" :searchable="true"
                                                    :disabled="disabledRuangan" />
                                            </VControl>
                                        </VField>
                                    </div>
                                    <div class="column is-4">
                                        <VField class="is-rounded-select is-autocomplete-select">
                                            <VLabel>Jenis Pegawai</VLabel>
                                            <VControl icon="feather:search">
                                                <Multiselect mode="single" v-model="item.objectjenispegawaifk"
                                                    :options="d_jenispegawai" placeholder="Pilih" :searchable="true"
                                                    :disabled="disabledRuangan" />
                                            </VControl>
                                        </VField>
                                    </div>
                                </div>
                            </div>

                            </p>
                            <p v-else="activeValue === 'Jabatan'"> -</p>
                        </template>
                    </VTabs>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useWindowScroll } from '@vueuse/core'
import { useApi } from '/@src/composable/useApi'
import {
    ref,
    computed,
    onMounted,
    reactive,
} from 'vue'
import { useRoute } from 'vue-router'
import { useHead } from '@vueuse/head'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import Fieldset from 'primevue/fieldset'
import * as H from '/@src/utils/appHelper'
// Import helper error image sesuai contoh user
import { onceImageErrored } from '/@src/utils/via-placeholder'

const TITLE_PAGE = 'Pegawai'

useHead({
    title: `${TITLE_PAGE} - ULAB`,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
let ID_PEGAWAI = useRoute().query.id as string

let item: any = reactive({
    header: {}
})
const { y } = useWindowScroll()
const isStuck = computed(() => {
    return y.value > 30
})

const d_pendidikan: any = ref([])
const d_jeniskelamin: any = ref([])
const d_agama: any = ref([])
const d_statuspegawai: any = ref([])
const d_jabatan: any = ref([])
const d_jenispegawai: any = ref([])

// Variabel untuk menghandle Foto
const fileFoto: any = ref(null)       // Menyimpan file baru yang diupload user
const collapsedOps: any = ref(true)
const isSimpan: any = ref(false)
const disabledRuangan: any = ref(false)
const disTanggal: any = ref(false)

async function onInit() {
    item.loading = false;
    if (ID_PEGAWAI) {
        try {
            const response: any = await useApi().get(`sysadmin/pegawai-by-id?id=${ID_PEGAWAI}`);
            const pegawai = response.pegawai;

            item.id = pegawai.id;
            item.namalengkap = pegawai.namalengkap;
            item.nik = pegawai.nik;
            item.nid = pegawai.nid;
            item.tempatlahir = pegawai.tempatlahir;
            item.tgllahir = pegawai.tgllahir;
            item.objectpendidikanterakhirfk = pegawai.objectpendidikanterakhirfk;
            item.gelardepan = pegawai.gelardepan;
            item.gelarbelakang = pegawai.gelarbelakang;
            item.objectjeniskelaminfk = pegawai.objectjeniskelaminfk;
            item.objectagamafk = pegawai.objectagamafk;
            item.alamat = pegawai.alamat;
            item.kodepos = pegawai.kodepos;
            item.nohandphone = pegawai.nohandphone;
            item.email = pegawai.email;
            item.statuspegawaifk = pegawai.statuspegawaifk;
            item.tglmasuk = pegawai.tglmasuk;
            item.tglkeluar = pegawai.tglkeluar;
            item.jabatan1fk = pegawai.jabatan1fk;
            item.objectjenispegawaifk = pegawai.objectjenispegawaifk;
            
            // Simpan nama file foto ke variabel item agar bisa diakses di template
            if (pegawai.filenameFoto) {
                item.filenameFoto = pegawai.filenameFoto;
            }

        } catch (error) {
            console.error('Gagal ambil data pegawai', error);
        }
    }
    loadDrop();
}

async function loadDrop() {
    const response = await useApi().get(`/sysadmin/master-pegawai-dropdown`)

    d_pendidikan.value = response.pendidikan.map((e: any) => {
        return { label: e.pendidikan, value: e.id }
    })
    d_jeniskelamin.value = response.jeniskelamin.map((e: any) => {
        return { label: e.jeniskelamin, value: e.id }
    })
    d_agama.value = response.agama.map((e: any) => {
        return { label: e.agama, value: e.id }
    })
    d_jabatan.value = response.namajabatanulab.map((e: any) => {
        return { label: e.namajabatanulab, value: e.id }
    })
    d_jenispegawai.value = response.jenispegawai.map((e: any) => {
        return { label: e.jenispegawai, value: e.id }
    })
    d_statuspegawai.value = response.statuspegawai.map((e: any) => {
        return { label: e.statuspegawai, value: e.id }
    })
}


const simpanPegawai = async () => {
    if (!item.namalengkap) {
        H.alert('error', 'Nama Lengkap tidak boleh kosong')
        return
    }
    if (!item.tgllahir) {
        H.alert('error', 'Tanggal Lahir tidak boleh kosong')
        return
    }
    if (!item.tempatlahir) {
        H.alert('error', 'Tempat Lahir tidak boleh kosong')
        return
    }
   
    var objSave =
    {
        'datapegawai': {
            'id': item.id ? item.id : '',
            'namalengkap': item.namalengkap,
            'nama': item.nama ? item.nama : null,
            'nik': item.nik ? item.nik : '',
            'nid': item.nid ? item.nid : '',
            'objectjeniskelaminfk': item.objectjeniskelaminfk ? item.objectjeniskelaminfk : null,
            'objectagamafk': item.objectagamafk ? item.objectagamafk : null,
            'gelardepan': item.gelardepan ? item.gelardepan : null,
            'gelarbelakang': item.gelarbelakang ? item.gelarbelakang : null,
            'tempatlahir': item.tempatlahir,
            'nohandphone': item.nohandphone ? item.nohandphone : null,
            'kodepos': item.kodepos ? item.kodepos : null,
            'alamat': item.alamat ? item.alamat : null,
            'tgllahir': item.tgllahir ? H.formatDate(item.tgllahir, 'YYYY-MM-DD') : null,
            'tglmasuk': item.tglmasuk ? H.formatDate(item.tglmasuk, 'YYYY-MM-DD') : null,
            'email': item.email ? item.email : null,
            'statuspegawaifk': item.statuspegawaifk ? item.statuspegawaifk : null,
            'jabatan1fk': item.jabatan1fk ? item.jabatan1fk : null,
            'objectjenispegawaifk': item.objectjenispegawaifk ? item.objectjenispegawaifk : null,
            'objectpendidikanterakhirfk': item.objectpendidikanterakhirfk ? item.objectpendidikanterakhirfk : null,
        },
    }
    isSimpan.value = true
    await useApi()
        .post(`/sysadmin/save-pegawai`, objSave)
        .then(
            (response: any) => {
                
                // Upload foto HANYA jika fileFoto ada isinya (user upload baru)
                if (fileFoto.value != null) {
                    const formData = new FormData()
                    formData.append('id', response.data.id)
                    formData.append('file', fileFoto.value)
                    useApi().postNoMessage('/sysadmin/save-pegawai-foto', formData)
                        .then(() => {
                             isSimpan.value = false
                             window.history.back()
                        })
                        .catch(() => {
                             isSimpan.value = false
                             window.history.back()
                        })
                } else {
                    isSimpan.value = false
                    window.history.back()
                }
            },
            (error) => {
                isSimpan.value = false
            }
        )
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

const onRemoveFile = (error: any, fileInfo: any) => {
    if (error) {
        console.error(error)
        return
    }
    // Jika user remove file dari FilePond, reset variable fileFoto
    fileFoto.value = null
}


function back() {
    window.history.back()
}
onInit()

onMounted(() => { })
</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/components/forms-outer';
@import '/@src/scss/custom/config';

.tabs-wrapper.is-triple-slider .tabs li a,
.tabs-wrapper-alt.is-triple-slider .tabs li a {
    color: hsl(0deg, 0%, 4%);
    font-family: var(--font);
    font-weight: 400;
    height: 40px;
    border-bottom: none;
    position: relative;
    z-index: 5;
}

.field>label {
    font-family: var(--font);
    font-size: 0.9rem;
    color: hsl(0deg, 0%, 4%) !important;
    font-weight: 400;
}
</style>