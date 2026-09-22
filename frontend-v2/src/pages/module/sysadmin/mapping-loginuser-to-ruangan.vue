<template>
    <div class="page-content-inner">
        <div class="is-navbar">
            <div class="form-layout">
                <div class="form-outer">
                    <div :class="[isStuck && 'is-stuck']" class="form-header stuck-header">
                        <div class="form-header-inner">
                            <div class="left">
                                <h3>Mapping Login User To Ruangan</h3>
                            </div>
                            <div class="right">
                                <div class="buttons">
                                    <VButton icon="lnir lnir-arrow-left rem-100"
                                        :to="{ name: 'module-sysadmin-master-tambah-login-user' }" light dark-outlined>
                                        Batal
                                    </VButton>
                                    <VButton type="button" icon="feather:save" :loading="isLoading" color="primary"
                                        raised @click="saveMapLoginUserToRuangan()"> Simpan
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
                                        <VLabel>Nama User</VLabel>
                                        <VControl icon="feather:search">
                                            <Multiselect mode="single" v-model="item.namauser" :options="d_LoginUser"
                                                placeholder="Pilih data" :searchable="true" :attrs="{ id }"
                                                @select="handleChangeRuangan(item.namauser)" />
                                        </VControl>
                                    </VField>
                                </div>

                                <Divider align="center">
                                    <span class="p-tag">Ruangan</span>
                                </Divider>
                                <div class="column is-12">
                                    <VCard>

                                        <div class="columns is-multiline">
                                        </div>
                                        <div class="column is-6">
                                            <VField>
                                                <h3 class="title is-6 mb-2 mr-1"> Pilih Ruangan </h3>
                                                <VControl icon="feather:search">
                                                    <input v-model="filters" class="input custom-text-filter"
                                                        placeholder="Search..." />
                                                </VControl>
                                            </VField>
                                            <p> Data terpilih <b>{{ jumlahCeklis }}</b></p>
                                        </div>

                                        <div class="columns is-multiline p-2" style="max-height:500px;overflow: auto;">
                                            <div class="column is-6" :key="items.id"
                                                v-for="items in dataSourcefiltered">
                                                <VField>
                                                    <VControl raw subcontrol>
                                                        <VCheckbox v-model="checkboxRuangan[items.id]" :value="items.id"
                                                            :label="items.namaruangan" color="info" square
                                                            :class="checkboxRuangan[items.id] == true ? 'is-solid' : ''"
                                                            @change="clikRuangan()" />
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
    title: 'Map Login User To Ruangan - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
let ID_RUANGAN = useRoute().query.id as string
let ID_USER = useRoute().query.objectloginuserfk as string
let ID_RUANGAN_SET = ref()
const date = ref(new Date())
const item: any = ref({})
const checkboxRuangan: any = ref([])
const items: any = ref([])
const dataSource: any = ref([])
let d_LoginUser: any = ref([])
let d_Ruangan: any = ref([])
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
        return d_Ruangan.value
    }
    return d_Ruangan.value.filter((items: any) => {
        return (
            items.namaruangan.match(new RegExp(filters.value, 'i'))
        )
    })
})

async function listDropdown() {
    const response = await useApi().get(
        `/sysadmin/master-map-loginuser-to-ruangan-dropdown`)
    d_LoginUser.value = response.namauser.map((e: any) => { return { label: e.namauser, value: e.id } })
    d_Ruangan.value = response.namaruangan
    item.namaruangan = response.namaruangan[0].id

    if (ID_RUANGAN) {
        ID_RUANGAN_SET.value = ID_RUANGAN
        ruanganByID(ID_RUANGAN)
    }
    if (ID_USER) {
        item.value.namaruangan = ID_USER
        handleChangeRuangan(ID_USER)
    }
}

async function ruanganByID(id: any) {
    const { data: detail } = await useApi().get(`/sysadmin/master-map-loginuser-to-ruangan?id=${id}`)
    item.value.namauser = detail[0].objectloginuserfk
    item.value.arrRuangan = detail[0].objectruanganfk
}



async function saveMapLoginUserToRuangan() {
    if (!item.value.namauser) { H.alert('warning', 'Nama User harus di pilih'); return }
    if (checkboxRuangan.value.length == 0) { H.alert('warning', 'Ruangan harus di pilih'); return }
    let arrRuangan = []
    let objectK = Object.keys(checkboxRuangan.value)
    for (let x = 0; x < objectK.length; x++) {
        const element = objectK[x];
        if (checkboxRuangan.value[element] == true) {
            arrRuangan.push({ 'ruanganfk': element })
        }

    }
    let json = {
        'objectloginuserfk': item.value.namauser,
        'detail': arrRuangan

    }
    console.log(json)
    isLoading.value = true
    await useApi().post(
        `/sysadmin/save-map-loginuser-to-ruangan`, json).then((response: any) => {
            isLoading.value = false

        }).catch((e: any) => {
            isLoading.value = false

        })

}
function clikRuangan() {
    jumlahCeklis.value = 0
    let objectK = Object.keys(checkboxRuangan.value)
    let jumlah = 0
    for (let x = 0; x < objectK.length; x++) {
        const element = objectK[x];
        if (checkboxRuangan.value[element] == true) {
            jumlah = jumlah + 1
        }
    }
    jumlahCeklis.value = jumlah

}


async function fetchListModul() {
    d_Ruangan.value.loading = true
    let searchQuery = `&q=`
    let namaruangan = ''
    if (item.qnama) namaruangan = `&namaruangan=${item.qnama}`
    const { data: MapLoginUserToRuangan } = await useApi().get(`/sysadmin/master-map-loginuser-to-ruangan?offset=rows=${currentPage.value.rows}${objectruanganfk}${objectloginuserfk}`)
    for (let x = 0; x < MapLoginUserToRuangan.length; x++) {
        const element = MapLoginUserToRuangan[x];
        let ini = element.namaruangan.split(' ')
        let init = element.namaruangan.substr(0, 1)
        if (ini.length > 1) {
            init = init + ini[1].substr(0, 1)
        }
        element.initials = init
    }
    d_Ruangan.value.total = MapLoginUserToRuangan.length
}


function resetForm() {

}

function handleChangeRuangan(e: any) {
    checkboxRuangan.value = []
    jumlahCeklis.value = 0
    useApi().get(
        `/sysadmin/master-map-loginuser-to-ruangan?objectloginuserfk=${e}`).then((response: any) => {
            isLoading.value = false
            jumlahCeklis.value = response.data.length
            for (let x = 0; x < response.data.length; x++) {
                const element = response.data[x];
                checkboxRuangan.value[element.objectruanganfk] = true
            }

        }).catch((e: any) => {
            isLoading.value = false
        })

    checkboxRuangan.value.total = checkboxRuangan.value.length
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
