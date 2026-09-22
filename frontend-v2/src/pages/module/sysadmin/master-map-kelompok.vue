<template>
    <div class="page-content-inner">
        <div class="is-navbar">
            <div class="form-layout">
                <div class="form-outer">
                    <div :class="[isStuck && 'is-stuck']" class="form-header stuck-header">
                        <div class="form-header-inner">
                            <div class="left">
                                <h3>Mapping Kelompok Pasien</h3>
                            </div>
                            <div class="right">
                                <div class="buttons">
                                    <VButton icon="lnir lnir-arrow-left rem-100"
                                        :to="{ name: 'module-sysadmin-master-rekanan' }" light dark-outlined>
                                        Cancel
                                    </VButton>
                                    <VButton type="button" icon="feather:save" :loading="isLoading" color="primary"
                                        raised @click="saveMapKelompok()"> Save
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

                                <div class="column is-6">
                                    <VField class="cis-rounded-select_Z  is-autocomplete-select" v-slot="{ id }">
                                        <h3 class="title is-6 mb-2 mr-1"> Kelompok Pasien </h3>
                                        <VControl icon="feather:search">
                                            <Multiselect mode="single" v-model="item.kelompokpasien"
                                                :options="d_Kelompok" placeholder="Pilih data" :searchable="true"
                                                :attrs="{ id }"
                                                @select="handleChangeKelompokPasien(item.kelompokpasien)" />
                                        </VControl>
                                    </VField>
                                </div>
                              
                                <div class="column is-12 mt-0 pt-0" v-if="listChecked.length > 0">
                                    <VCard>
                                        <div class="form-section pr-0 mt-0 pt-0">
                                            <div class="form-section-inner has-padding-bottom h-700-o ">
                                                <h3 class="has-text-centered">Data Terpilih ({{ listChecked.length }})
                                                    <VIconButton circle class="mr-2 is-pulled-right" icon="fas fa-trash" color="danger"
                                                        raised bold @click="clearSelection()">
                                                    </VIconButton>
                                                </h3>
                                                <div class="columns is-multiline">
                                                    <!-- <div class="column is-12 mt-0 mb-0">
                                                        <VButton color="danger" icon="feather:x" outlined rounded
                                                            class="is-pulled-right" @click="clearSelection()"> Hapus
                                                        </VButton>
                                                    </div> -->
                                                    <div class="column is-4" v-for="items in listChecked"
                                                        :key="items.namarekanan">
                                                        <VTag color="purple" :label="items.namarekanan" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </VCard>
                                </div>
                                <div class="column is-12">
                                    <VCard>
                                        <h3 class="title is-6 mb-2 mr-1"> List Rekanan </h3>
                                        <div class="columns is-multiline">
                                            <div class="column is-6">
                                                <VField>
                                                    <VControl icon="feather:search">
                                                        <VInput type="text" v-model="filters" placeholder="Filter Nama"
                                                            class="is-rounded" />
                                                    </VControl>
                                                </VField>
                                            </div>
                                        </div>
                                        <p> Data terpilih <b>{{ listChecked.length }}</b></p>
                                        <div class="columns is-multiline p-2" style="max-height:500px;overflow: auto;">
                                            <div class="column is-6" :key="items.id"
                                                v-for="items in dataSourcefiltered">
                                                <VField>
                                                    <VControl raw subcontrol>
                                                        <VCheckbox v-model="checkboxRekanan[items.id]" :value="items.id"
                                                            :label="items.namarekanan" color="info" square
                                                            :class="checkboxRekanan[items.id] == true ? 'is-solid' : ''"
                                                            @change="clickRekanan()" />
                                                    </VControl>
                                                </VField>
                                            </div>
                                        </div>

                                    </VCard>
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
import { useRoute, useRouter } from 'vue-router'
import { useHead } from '@vueuse/head'
import * as H from '/@src/utils/appHelper'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import AutoComplete from 'primevue/autocomplete';
import { useToaster } from '/@src/composable/toaster'
import { elements } from '/@src/data/landing/components'

useHead({
    title: 'Map Kelompok Pasien - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)

const item: any = ref({})
const checkboxRekanan: any = ref([])
let d_Kelompok: any = ref([])
let d_Rekanan: any = ref([])
let isLoading = ref(false)
let jumlahCeklis: any = ref(0)
const listChecked: any = ref([])
const d_ListDefault: any = ref([])
const { y } = useWindowScroll()
const router = useRouter()
const isStuck = computed(() => {
    return y.value > 30
})
const filters = ref('')
const dataSourcefiltered = computed(() => {
    if (!filters.value) {
        return d_Rekanan.value
    }
    return d_Rekanan.value.filter((items: any) => {
        return (
            items.namarekanan.match(new RegExp(filters.value, 'i'))
        )
    })
})

async function listDropdown() {

    const response = await useApi().get(
        `/sysadmin/master-map-kelompok-dropdown`)
    d_Kelompok.value = response.kelompokpasien.map((e: any) => { return { label: e.kelompokpasien, value: e.id } })
    d_Rekanan.value = response.namarekanan
    d_ListDefault.value = response.namarekanan
    item.namarekanan = response.namarekanan[0].id
}


async function saveMapKelompok() {
    if (!item.value.kelompokpasien) { H.alert('warning', 'Kelompok Pasien harus di pilih'); return }
    if (checkboxRekanan.value.length == 0) { H.alert('warning', 'Rekanan harus di pilih'); return }
    let arrRekanan = []
    let objectK = Object.keys(checkboxRekanan.value)
    for (let x = 0; x < objectK.length; x++) {
        const element = objectK[x];
        if (checkboxRekanan.value[element] == true) {
            arrRekanan.push({ 'objectrekananfk': element })
        }

    }
    let json = {
        'objectkelompokpasienfk': item.value.kelompokpasien,
        'detail': arrRekanan

    }
    console.log(json)
    isLoading.value = true
    await useApi().post(
        `/sysadmin/save-map-kelompok`, json).then((response: any) => {
            isLoading.value = false

        }).catch((e: any) => {
            isLoading.value = false

        })

}

function clearSelection() {
    var arrobj = Object.keys(checkboxRekanan.value)
    for (let x = 0; x < arrobj.length; x++) {
        const element2 = arrobj[x];
        checkboxRekanan.value[element2] = false
    }
    clickRekanan()
}
function clickRekanan() {

    jumlahCeklis.value = 0
    let objectK = Object.keys(checkboxRekanan.value)
    let jumlah = 0

    for (let x = 0; x < objectK.length; x++) {
        const element = objectK[x];
        if (checkboxRekanan.value[element] == true) {
            for (var i = 0; i < d_ListDefault.value.length; i++) {
                const element2 = d_ListDefault.value[i];
                if (element2.id == element) {
                    for (var z = 0; z < listChecked.value.length; z++) {
                        const element3 = listChecked.value[z];
                        if (element3.namarekanan == element2.namarekanan) {
                            listChecked.value.splice(z, 1)
                        }
                    }
                    listChecked.value.push({ namarekanan: element2.namarekanan })
                }
            }
            jumlah = jumlah + 1
        } else {
            for (var i = 0; i < d_ListDefault.value.length; i++) {
                const element2 = d_ListDefault.value[i];
                if (element2.id == element) {
                    for (var z = 0; z < listChecked.value.length; z++) {
                        const element3 = listChecked.value[z];
                        if (element3.namarekanan == element2.namarekanan) {
                            listChecked.value.splice(z, 1)
                        }
                    }
                }
            }
        }
    }
    jumlahCeklis.value = jumlah

}


function handleChangeKelompokPasien(e: any) {
    checkboxRekanan.value = []
    jumlahCeklis.value = 0
    useApi().get(
        `/sysadmin/master-map-kelompok?objectkelompokpasienfk=${e}`).then((response: any) => {
            isLoading.value = false
            jumlahCeklis.value = response.data.length
            for (let x = 0; x < response.data.length; x++) {
                const element = response.data[x];
                checkboxRekanan.value[element.kdpenjaminpasien] = true
            }
            clickRekanan()
        }).catch((e: any) => {
            isLoading.value = false
        })

    checkboxRekanan.value.total = checkboxRekanan.value.length
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
    max-width: 580px;
    margin: 0 auto;
}
</style>
