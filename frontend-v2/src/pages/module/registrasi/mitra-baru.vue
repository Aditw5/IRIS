<template>
    <div class="page-content-inner">
        <div class="is-navbar">
            <div class="form-layout">
                <div class="form-outer">
                    <div :class="[isStuck && 'is-stuck']" class="form-header stuck-header">
                        <div class="form-header-inner">
                            <div class="left">
                                <h3>Unit</h3>
                            </div>
                            <div class="right">
                                <div class="buttons">
                                    <VButton type="button" icon="feather:save" :loading="isLoading" color="primary"
                                        raised @click="saveMitra()" v-if="!isRegistrasi"> Save
                                    </VButton>
                                    <VButton type="button" icon="feather:arrow-right-circle" :loading="isLoading"
                                        color="info" raised @click="registrasi()" v-if="isRegistrasi">
                                        Registrasi
                                    </VButton>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-body">
                        <!--Fieldset-->
                        <div class="form-fieldset">
                            <div class="fieldset-heading">
                                <h4>Informasi Unit</h4>
                            </div>
                            <div class="columns is-multiline">
                                <!-- <div class="column is-12 text-center">
                                    <VField>
                                        <VControl>
                                            <VFilePond v-if="files.length" v-bind:files="files" class="profile-filepond"
                                                name="profile_filepond" :chunk-retry-delays="[500, 1000, 3000]"
                                                label-idle="<i class='lnil lnil-cloud-upload'></i>"
                                                :accepted-file-types="['image/png', 'image/jpeg', 'image/gif']"
                                                :image-preview-height="140" :image-resize-target-width="140"
                                                :image-resize-target-height="140" image-crop-aspect-ratio="1:1"
                                                style-panel-layout="compact circle"
                                                style-load-indicator-position="center bottom"
                                                style-progress-indicator-position="right bottom"
                                                style-button-remove-item-position="left bottom"
                                                style-button-process-item-position="right bottom" @addfile="onAddFile"
                                                @removefile="onRemoveFile" />
                                            <VFilePond v-else class="profile-filepond" name="profile_filepond"
                                                :chunk-retry-delays="[500, 1000, 3000]"
                                                label-idle="<i class='lnil lnil-cloud-upload'></i>"
                                                :accepted-file-types="['image/png', 'image/jpeg', 'image/gif']"
                                                :image-preview-height="140" :image-resize-target-width="140"
                                                :image-resize-target-height="140" image-crop-aspect-ratio="1:1"
                                                style-panel-layout="compact circle"
                                                style-load-indicator-position="center bottom"
                                                style-progress-indicator-position="right bottom"
                                                style-button-remove-item-position="left bottom"
                                                style-button-process-item-position="right bottom" @addfile="onAddFile"
                                                @removefile="onRemoveFile" />
                                        </VControl>
                                    </VField>
                                </div> -->
                                <div class="column is-12">
                                    <VField>
                                        <VLabel class="required-field">Nama Perusahaan</VLabel>
                                        <VControl icon="feather:user">
                                            <VInput type="text" v-model="item.namaperusahaan"
                                                placeholder="Nama Perusahaan" class="is-rounded_Z" />
                                        </VControl>
                                    </VField>
                                </div>
                                <div class="column is-12">
                                    <VField>
                                        <VLabel>No HP/Ponsel Penanggung Jawab</VLabel>
                                        <VControl icon="feather:phone">
                                            <VInput type="text" v-model="item.nohp" placeholder="No HP Penanggung Jawab"
                                                class="is-rounded_Z" />
                                        </VControl>
                                    </VField>
                                </div>
                            </div>
                        </div>
                        <!--Fieldset-->
                        <div class="form-fieldset">
                            <div class="fieldset-heading">
                                <h4>Informasi Alamat </h4>
                            </div>

                            <div class="columns is-multiline">
                                <div class="column is-9">
                                    <VField>
                                        <VLabel class="required-field">Alamat Lengkap</VLabel>
                                        <VControl>
                                            <VTextarea v-model="item.alamat" rows="1" placeholder="Alamat Lengkap">
                                            </VTextarea>
                                        </VControl>
                                    </VField>
                                </div>

                                <div class="column is-3">
                                    <VField>
                                        <VLabel>RT / RW</VLabel>
                                        <VControl>
                                            <VInput type="text" v-model="item.rtrw" placeholder="RT / RW"
                                                class="is-rounded_Z" />
                                        </VControl>
                                    </VField>
                                </div>

                                <div class="column is-6">
                                    <VField class="cis-rounded-select_Z  is-autocomplete-select" v-slot="{ id }">
                                        <VLabel class="required-field">Provinsi</VLabel>
                                        <VControl icon="feather:search">
                                            <Multiselect mode="single" v-model="item.provinsi" :options="d_Provinsi"
                                                placeholder="Pilih data" :searchable="true" :attrs="{ id }"
                                                @select="changeProvinsi(item.provinsi)" />
                                        </VControl>
                                    </VField>
                                </div>
                                <div class="column is-6" v-if="item.provinsi">
                                    <VField class="cis-rounded-select_Z  is-autocomplete-select" v-slot="{ id }">
                                        <VLabel class="required-field">Kota Kabupaten</VLabel>
                                        <VControl icon="feather:search">
                                            <Multiselect mode="single" v-model="item.kotaKabupaten"
                                                :options="d_KotaKabupaten" placeholder="Pilih data" :searchable="true"
                                                :attrs="{ id }" :loading="isLoading"
                                                @select="changeKota(item.kotaKabupaten)" />
                                        </VControl>
                                    </VField>
                                </div>
                                <div class="column is-6" v-if="item.kotaKabupaten">
                                    <VField class="cis-rounded-select_Z  is-autocomplete-select" v-slot="{ id }">
                                        <VLabel class="required-field">Kecamatan</VLabel>
                                        <VControl icon="feather:search">
                                            <Multiselect mode="single" v-model="item.kecamatan" :options="d_Kecamatan"
                                                placeholder="Pilih data" :searchable="true" :attrs="{ id }"
                                                :loading="isLoading" @select="changeKecamatan(item.kecamatan)" />
                                        </VControl>
                                    </VField>
                                </div>
                                <div class="column is-6" v-if="item.kecamatan">
                                    <VField class="cis-rounded-select_Z  is-autocomplete-select" v-slot="{ id }">
                                        <VLabel>Kelurahan</VLabel>
                                        <VControl icon="feather:search">
                                            <Multiselect mode="single" v-model="item.desaKelurahan"
                                                :options="d_Kelurahan" placeholder="Pilih data" :searchable="true"
                                                :attrs="{ id }" :loading="isLoading"
                                                @select="changeDesa(item.desaKelurahan)" />
                                        </VControl>
                                    </VField>
                                </div>
                                <div class="column is-6" v-if="item.kecamatan">
                                    <VField>
                                        <VLabel>Kode Pos </VLabel>
                                        <VControl icon="feather:airplay" :loading="isLoadingKodePos">
                                            <VInput type="text" v-model="item.kodePos" placeholder="Kode Pos"
                                                class="is-rounded_Z" />
                                        </VControl>
                                    </VField>
                                </div>

                                <div class="column is-12">
                                    <VField>
                                        <VLabel>Email </VLabel>
                                        <VControl icon="feather:mail">
                                            <VInput type="email" v-model="item.email" placeholder="Email"
                                                inputmode="email" class="is-rounded_Z" />
                                        </VControl>
                                    </VField>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { useWindowScroll } from '@vueuse/core'
import { useApi } from '/@src/composable/useApi'
import { h, reactive, ref, computed, defineComponent, watch } from 'vue'
import { useUserSession } from '/@src/stores/userSession'
import { useRoute, useRouter } from 'vue-router'
import { useHead } from '@vueuse/head'
import * as H from '/@src/utils/appHelper'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import Calendar from 'primevue/calendar';
useHead({
    title: 'Mitra - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
let ID_MITRA = useRoute().query.id as string
let ID_MITRA_SET = ref()
const date = ref(new Date())
const item: any = reactive({})
let d_Negara: any = ref([])
let d_Kelurahan: any = ref([])
let d_Kecamatan: any = ref([])
let d_KotaKabupaten: any = ref([])
let d_Provinsi: any = ref([])
let isLoading = ref(false)
let isLoadingKodePos = ref(false)
let isRegistrasi = ref(false)
const files = ref([])
const fileFoto: any = ref(null)
const { y } = useWindowScroll()
const router = useRouter()
const route = useRoute()
const isStuck = computed(() => {
    return y.value > 30
})
const kelompokUser = useUserSession().getUser().kelompokUser.kelompokUser

async function fetchMitra() {
    if (ID_MITRA != undefined) {
        const response = await useApi().get(`/registrasi/mitra?&id=${ID_MITRA}`)
        item.namaperusahaan = response.data.namaperusahaan
        item.nohp = response.data.nohp
        item.alamat = response.data.alamatktr
        item.rtrw = response.data.rtrw

        item.provinsi = d_Provinsi.value.find(p => p.value === response.data.objectpropinsifk) || null
        item.kotaKabupaten = d_KotaKabupaten.value.find(k => k.value === response.data.objectkotakabupatenfk) || null
    }

}

async function listDropdown() {
    const response = await useApi().get(`/registrasi/list-mitra-dropdown`)
    d_Negara.value = response.negara.map((e: any) => { return { label: e.namanegara, value: e.id, default: e } })
    d_Provinsi.value = response.provinsi.map((e: any) => { return { label: e.namapropinsi, value: e.id, default: e } })

}

const blobToBase64 = (blob: any) => {
    return new Promise((resolve, _) => {
        const reader = new FileReader();
        reader.onloadend = () => resolve(reader.result);
        reader.readAsDataURL(blob);
    });
}

async function saveMitra() {
    if (!item.namaperusahaan) { H.alert('warning', 'Nama Perusahaan harus di isi'); return }
    if (!item.nohp) { H.alert('warning', 'No.Hp harus di isi'); return }
    if (!item.alamat) { H.alert('warning', 'Alamat harus di isi'); return }

    item.progress = cekProggress()
    let json = {
        'pasien': {
            'id': ID_MITRA ? ID_MITRA : '',
            'namaperusahaan': item.namaperusahaan,
            'nohp': item.nohp,
            'email': item.email != undefined ? item.email : null,
            'alamat': item.alamat != undefined ? item.alamat : null,
            'objectnegarafk': item.negara != undefined ? item.negara : null,
            'progress': item.progress ? item.progress : 0,
        },
        'alamat': {
            'alamatlengkap': item.alamat,
            'rtrw': item.rtrw ?? null,
            'objectpropinsifk': item.provinsi != undefined ? item.provinsi : null,
            'objectkotakabupatenfk': item.kotaKabupaten != undefined ? item.kotaKabupaten : null,
            'objectkecamatanfk': item.kecamatan != undefined ? item.kecamatan : null,
            'objectdesakelurahanfk': item.desaKelurahan != undefined ? item.desaKelurahan : null,
            'kodepos': item.kodePos ? item.kodePos : null,
        }
    }
    isLoading.value = true
    isRegistrasi.value = false
    await useApi().post(
        `/registrasi/save-mitra`, json).then(async (response: any) => {

            isLoading.value = false
            ID_MITRA = response.data.id
            ID_MITRA_SET.value = response.data.id
            isRegistrasi.value = true
            if (!ID_MITRA) {
                registrasi()
            }

        }).catch((e: any) => {
            isLoading.value = false
            console.clear()
            console.log(e)
        })
}

async function changeProvinsi(event: any) {
    d_KotaKabupaten.value = []
    let query = event == '' ? '' : event;
    isLoading.value = true

    const response = await useApi().get(
        `/registrasi/kotakabupaten?provfk=${query}`)
    isLoading.value = false

    d_KotaKabupaten.value = response.kotakabupaten.map((e: any) => { return { label: e.namakotakabupaten, value: e.id, default: e } })

}
async function changeKota(event: any) {
    d_Kecamatan.value = []
    let query = event == '' ? '' : event;
    isLoading.value = true

    const response = await useApi().get(
        `/registrasi/kecamatan?kotafk=${query}`)
    isLoading.value = false

    d_Kecamatan.value = response.kecamatan.map((e: any) => { return { label: e.namakecamatan, value: e.id, default: e } })

}
async function changeKecamatan(event: any) {
    d_Kelurahan.value = []
    let query = event == '' ? '' : event;
    isLoading.value = true

    const response = await useApi().get(
        `/registrasi/desakelurahan?kecfk=${query}`)
    isLoading.value = false

    d_Kelurahan.value = response.desa.map((e: any) => { return { label: e.namadesakelurahan, value: e.id, default: e } })

}

function changeDesa(event: any) {
    item.kodePos = event.kodepos
}

function cekProggress() {
    var countALL = 0
    var data = 0
    if (item.namaperusahaan) { data = data + 1 }
    countALL = countALL + 1
    if (item.nohp) { data = data + 1 }
    countALL = countALL + 1
    if (item.email) { data = data + 1 }
    countALL = countALL + 1
    if (item.negara) { data = data + 1 }
    countALL = countALL + 1
    if (item.alamat) { data = data + 1 }
    countALL = countALL + 1
    if (item.provinsi) { data = data + 1 }
    countALL = countALL + 1
    if (item.kotaKabupaten) { data = data + 1 }
    countALL = countALL + 1
    if (item.kecamatan) { data = data + 1 }
    countALL = countALL + 1
    if (item.desaKelurahan) { data = data + 1 }
    countALL = countALL + 1
    if (item.kodePos) { data = data + 1 }
    return data / countALL * 100
}

const registrasi = async () => {
    router.push({
        name: 'module-registrasi-registrasi-mitra',
        query: {
            nomitrafk: ID_MITRA_SET.value,
            statusmitra: "BARU",
        },
    })
}

listDropdown()
fetchMitra()

</script>
<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/components/forms-outer';

.form-layout {
    max-width: 840px;
    margin: 0 auto;
}

.form-fieldset {
    padding: 20px 0;
    max-width: 580px;
    margin: 0 auto;
}
</style>
