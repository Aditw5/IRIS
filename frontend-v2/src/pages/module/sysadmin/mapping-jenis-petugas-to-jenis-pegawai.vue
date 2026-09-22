<template>
    <div class="page-content-inner">
        <div class="is-navbar">
            <div class="form-layout">
                <div class="form-outer">
                    <div :class="[isStuck && 'is-stuck']" class="form-header stuck-header">
                        <div class="form-header-inner">
                            <div class="left">
                                <h3>Mapping Jenis Petugas To Jenis Pegawai
                                </h3>
                            </div>
                            <div class="right">
                                <div class="buttons">
                                    <VButton icon="lnir lnir-arrow-left rem-100"
                                        :to="{ name: 'module-sysadmin-master-map-jenis-petugas-to-jenis-pegawai' }"
                                        light dark-outlined>
                                        Cancel
                                    </VButton>
                                    <VButton type="button" icon="feather:save" :loading="isLoading" color="primary"
                                        raised @click="saveMapJenisPetugasToJenisPegawai()"> Save
                                    </VButton>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-body">
                        <!--Fieldset-->
                        <!--Fieldset-->
                        <div class="form-fieldset">
                            <div class="columns is-multiline">
                                <div class="column is-8">
                                    <VField class="cis-rounded-select_Z  is-autocomplete-select" v-slot="{ id }">
                                        <VLabel>Jenis Petugas</VLabel>
                                        <VControl icon="feather:search">
                                            <Multiselect mode="single" v-model="item.jenispetugaspe"
                                                :options="d_JenisPetugas" placeholder="Pilih data" :searchable="true"
                                                :attrs="{ id }" @select="handleChangePetugas(item.jenispetugaspe)" />
                                        </VControl>
                                    </VField>
                                </div>

                                <Divider align="center">
                                    <span class="p-tag">Jenis Pegawai</span>
                                </Divider>
                                <div class="column is-12">
                                    <VCard>

                                        <div class="columns is-multiline">
                                        </div>
                                        <div class="column is-6">
                                            <VField>
                                                <h3 class="title is-6 mb-2 mr-1"> Pilih Jenis Pegawai </h3>
                                                <VControl icon="feather:search">
                                                    <input v-model="filters" class="input custom-text-filter"
                                                        placeholder="Search..." />
                                                </VControl>
                                            </VField>
                                        </div>

                                        <div class="columns is-multiline p-2" style="max-height:500px;overflow: auto;">
                                            <div class="column is-6" :key="items.id"
                                                v-for="items in dataSourcefiltered">
                                                <VField>
                                                    <VControl raw subcontrol>
                                                        <VCheckbox v-model="checkboxJenisPegawai[items.id]"
                                                            :value="items.id" :label="items.jenispegawai" color="danger"
                                                            checked
                                                            :class="checkboxJenisPegawai[items.id] == true ? 'is-solid' : ''"
                                                            @change="clickJenisPegawai()" />
                                                    </VControl>
                                                </VField>
                                            </div>
                                        </div>

                                    </VCard>
                                </div>

                            </div>
                        </div>
                        <!--Fieldset-->
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
import Divider from 'primevue/divider';
import { useRoute, useRouter } from 'vue-router'
import { useHead } from '@vueuse/head'
import * as H from '/@src/utils/appHelper'
import Fieldset from 'primevue/fieldset';
import { useViewWrapper } from '/@src/stores/viewWrapper'
import AutoComplete from 'primevue/autocomplete';
import { useToaster } from '/@src/composable/toaster'
import { elements } from '/@src/data/landing/components'

useHead({
    title: 'Map Jenis Petugas To Jenis Pegawai - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
let ID_JENISPEGAWAI = useRoute().query.id as string
let ID_JENISPETUGASPE = useRoute().query.objectruanganfk as string
let ID_JENISPEGAWAI_SET = ref()
const date = ref(new Date())
const item: any = ref({})
const checkboxJenisPegawai: any = ref([])
const items: any = ref([])
const dataSource: any = ref([])
let d_JenisPetugas: any = ref([])
let d_Departemen: any = ref([])
let d_JenisPegawai: any = ref([])
let d_KelompokProduk: any = ref([])
let isLoading = ref(false)
let jumlahCeklis: any = ref(0)
const { y } = useWindowScroll()
const router = useRouter()
const isStuck = computed(() => {
    return y.value > 30
})
const filters = ref('')
const dataSourcefiltered = computed(() => {
    if (!filters.value) {
        return d_JenisPegawai.value
    }
    return d_JenisPegawai.value.filter((items: any) => {
        return (
            items.jenispegawai.match(new RegExp(filters.value, 'i'))
        )
    })
})

async function listDropdown() {
    const response = await useApi().get(
        `/sysadmin/master-map-jenis-petugas-to-jenis-pegawai-dropdown`)
    d_JenisPetugas.value = response.jenispetugaspe.map((e: any) => { return { label: e.jenispetugaspe, value: e.id } })
    d_JenisPegawai.value = response.jenispegawai
    item.jenispegawai = response.jenispegawai[0].id

    if (ID_JENISPEGAWAI) {
        ID_JENISPEGAWAI_SET.value = ID_JENISPEGAWAI
        petugasByID(ID_JENISPEGAWAI)
    }
    if (ID_JENISPETUGASPE) {
        d_JenisPetugas.value.forEach(element => {
            if(element.value == ID_JENISPETUGASPE ){
                item.value.jenispetugaspe = element
                console.log('sama')
                return
            }
        });
        handleChangePetugas(ID_JENISPETUGASPE)
    }
}

async function petugasByID(id: any) {
    const { data: detail } = await useApi().get(`/sysadmin/master-map-jenis-petugas-to-jenis-pegawai?id=${id}`)
    item.value.jenispetugaspe = detail[0].objectjenispetugaspefk
    item.value.arrJenisPegawai = detail[0].objectjenispegawaifk
}

async function saveMapJenisPetugasToJenisPegawai() {

    if (!item.value.jenispetugaspe) { H.alert('warning', 'Jenis Petugas harus di pilih'); return }
    if (jumlahCeklis.value .length == 0) { H.alert('warning', 'Jenis Pegawai harus di pilih'); return }
    let arrJenisPegawai = []
    let objectK = Object.keys(checkboxJenisPegawai.value)
    for (let x = 0; x < objectK.length; x++) {
        const element = objectK[x];
        if (checkboxJenisPegawai.value[element] == true) {
            arrJenisPegawai.push({ 'pegawaifk': element })
        }

    }
    let json = {
        'objectjenispetugaspefk': item.value.jenispetugaspe,
        'detail': arrJenisPegawai

    }
    console.log(json)
    isLoading.value = true
    await useApi().post(
        `/sysadmin/save-map-jenis-petugas-to-jenis-pegawai`, json).then((response: any) => {
            isLoading.value = false

        }).catch((e: any) => {
            isLoading.value = false

        })

}

function clickJenisPegawai() {
    jumlahCeklis.value = 0
    let objectK = Object.keys(checkboxJenisPegawai.value)
    let jumlah = 0
    for (let x = 0; x < objectK.length; x++) {
        const element = objectK[x];
        if (checkboxJenisPegawai.value[element] == true) {
            jumlah = jumlah + 1
        }
    }
    jumlahCeklis.value = jumlah

}


async function fetchListProduk() {
    d_JenisPegawai.value.loading = true
    let searchQuery = `&q=`
    let jenispegawai = ''
    if (item.qnama) jenispegawai = `&jenispegawai=${item.qnama}`
    const { data: MapJenisPetugasToJenisPegawai } = await useApi().get(`/sysadmin/master-map-jenis-petugas-to-jenis-pegawai?offset=rows=${currentPage.value.rows}${objectjenispegawaifk}${objectjenispetugaspefk}`)
    for (let x = 0; x < MapJenisPetugasToJenisPegawai.length; x++) {
        const element = MapJenisPetugasToJenisPegawai[x];
        let ini = element.jenispegawai.split(' ')
        let init = element.jenispegawai.substr(0, 1)
        if (ini.length > 1) {
            init = init + ini[1].substr(0, 1)
        }
        element.initials = init
    }
    d_JenisPegawai.value.total = MapJenisPetugasToJenisPegawai.length
}

function resetForm() {
}

function handleChangePetugas(e: any) {
    checkboxJenisPegawai.value = []
    jumlahCeklis.value = 0
    useApi().get(
        `/sysadmin/master-map-jenis-petugas-to-jenis-pegawai?objectjenispetugaspefk=${e}`).then((response: any) => {
            isLoading.value = false
            jumlahCeklis.value = response.data.length
            for (let x = 0; x < response.data.length; x++) {
                const element = response.data[x];
                checkboxJenisPegawai.value[element.objectjenispegawaifk] = true
            }

        }).catch((e: any) => {
            isLoading.value = false
        })

    checkboxJenisPegawai.value.total = checkboxJenisPegawai.value.length
}

listDropdown()


</script>
<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/components/forms-outer';

.form-layout {
    max-width: 800px;
    margin: 0 auto;
}


.checkbox.is-outlined.is-info.is-solid input+span::after {
    color: var(--white);
}

.form-fieldset {
    padding: 20px 0;
    max-width: 740px;
    margin: 0 auto;
}
</style>
