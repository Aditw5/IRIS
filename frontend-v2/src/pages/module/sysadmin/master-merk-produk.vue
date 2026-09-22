<template>
    <ConfirmDialog />
    <VCard>
        <div class="columns column c-title">
            <h3 class="title is-5 mb-2 ml-2" style="z-index:1">Merk Produk</h3>
        </div>

        <div class="columns is-multiline ml-4">
            <div class="column is-4">
                <img src="/images/avatars/label/merk.png" alt="Merk Produk" class="merk">
                <VCard>
                    <div class="columns is-multiline">
                        <div class="column is-6">
                            <h3 v-if="item.id" class="title is-6 mb-2 mr-1">
                                <i class="iconify" data-icon="feather:edit" aria-hidden="true"> </i>
                                Edit Data
                            </h3>
                            <h3 v-else class="title is-6 mb-2 mr-1">
                                <i class="iconify" data-icon="feather:edit" aria-hidden="true"> </i>
                                Tambah Data
                            </h3>
                        </div>
                        <div class="column is-12">
                            <VField label="Merk Produk">
                                <VControl icon="feather:bookmark">
                                    <VInput type="text" v-model="item.merkproduk" placeholder="Merk Produk"
                                        class="is-rounded" />
                                </VControl>
                            </VField>
                        </div>
                        <div class="column is-12" v-if="item.id" style="display:flex; padding: 0px;">
                            <VField class="column is-8 is-rounded-select is-autocomplete-select">
                                <VLabel>Departemen</VLabel>
                                <VControl icon="feather:search">
                                    <Multiselect mode="single" v-model="item.objectdepartemenfk" :options="d_Departemen"
                                        placeholder="Pilih data" :searchable="true" />
                                </VControl>
                            </VField>

                            <VField class="column is-4">
                                <VLabel class="ml-2">Aktivasi</VLabel>
                                <VControl class="is-pulled-right">
                                    <VSwitchBlock style="padding-top:3px" v-model="item.statusenabled" label="Aktif"
                                        color="danger" />
                                </VControl>
                            </VField>
                        </div>
                        <div class="column is-12" v-else>
                            <VField class="is-rounded-select is-autocomplete-select">
                                <VLabel>Departemen</VLabel>
                                <VControl icon="feather:search">
                                    <Multiselect mode="single" v-model="item.objectdepartemenfk" :options="d_Departemen"
                                        placeholder="Pilih data" :searchable="true" />
                                </VControl>
                            </VField>
                        </div>

                        <div class="column is-12">
                            <div v-if="item.id">
                                <VButton @click="save()" :loading="isLoadingBtn" type="button" icon="feather:edit"
                                    class="is-fullwidth mr-3" color="info" raised>
                                    Update Data
                                </VButton>
                                <VButton @click="clear()" type="button" icon="feather:x-circle"
                                    class="is-fullwidth is-outlined is-warning mt-3" raised>
                                    Batal Edit
                                </VButton>
                            </div>
                            <div v-else>
                                <VButton @click="save()" :loading="isLoadingBtn" type="button" icon="feather:save"
                                    class="is-fullwidth mr-3" color="success" raised>
                                    Simpan Data
                                </VButton>
                            </div>
                        </div>
                    </div>
                </VCard>
            </div>
            <div class="column is-8" style="margin-top: 3rem;">
                <div class="user-grid-toolbar">
                    <VControl icon="feather:search">
                        <input v-model="filters" class="input custom-text-filter" placeholder="Search..." />
                    </VControl>
                    <div class="column is-6 switch-filter">
                        <VControl>
                            <InputSwitch v-model="item.aktif" @change="fetchData()" />
                        </VControl>
                        <span>Aktif</span>
                    </div>
                    <div class="buttons ml-4">
                        <VField v-slot="{ id }" class="is-icon-select">
                            <VControl class="mr-0">
                                <Multiselect v-model="selectView" :attrs="{ id }" placeholder="Select View" label="name"
                                    :options="d_View" :searchable="true" track-by="name" mode="single"
                                    @select="changeView(selectView)" autocomplete="off">
                                    <template #singlelabel="{ value }">
                                        <div class="multiselect-single-label">
                                            <div class="select-label-icon-wrap">
                                                <i :class="value.icon"></i>
                                            </div>
                                            <span class="select-label-text">
                                                {{ value.name }}
                                            </span>
                                        </div>
                                    </template>

                                    <template #option="{ option }">
                                        <div class="select-option-icon-wrap">
                                            <i :class="option.icon"></i>
                                        </div>
                                        <span class="select-option-text">
                                            {{ option.name }}
                                        </span>
                                    </template>
                                </Multiselect>
                            </VControl>
                        </VField>
                    </div>
                </div>

                <div class="user-grid user-grid-v2" v-if="selectView == 'list'">
                    <DataTable :value="dataSourcefiltered" :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 25]"
                        :loading="isLoading" class="p-datatable-sm"
                        paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                        responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
                        currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" showGridlines>

                        <Column field="no" header="#"></Column>
                        <Column field="merkproduk" header="Merk Produk" :sortable="true"></Column>
                        <Column field="namadepartemen" header="Departemen" :sortable="true"></Column>
                        <Column field="kodeexternal" header="Kode" :sortable="true"></Column>
                        <Column field="status" header="Status Akivasi" :sortable="true"></Column>
                        <Column :exportable="false" header="Action" style="text-align: center;">
                            <template #body="slotProps">
                                <VIconButton type="button" icon="pi pi-pencil" class="mr-3" color="info" circle outlined
                                    raised v-tooltip.top="'Edit'" @click="edit(slotProps.data)">
                                </VIconButton>
                                <VIconButton v-if="item.aktif" type="button" icon="fas fa-trash" class="mr-3" color="danger"
                                    circle outlined raised v-tooltip.top="'Hapus'" @click="dialogConfirm(slotProps.data)">
                                </VIconButton>
                                <VIconButton v-else type="button" disabled icon="fas fa-trash" class="mr-3" color="danger"
                                    circle outlined raised v-tooltip.top="'Hapus'">
                                </VIconButton>
                            </template>
                        </Column>
                    </DataTable>
                </div>
                <div class="tile-grid tile-grid-v1" v-else-if="selectView == 'grid'">
                    <div class="flex-list-inner" v-if="item.itemLength == 0">
                        <VPlaceholderSection title="Not found" subtitle="There is no data that match your query."
                            class="my-6">
                            <template #image>
                                <img class="light-image" src="/@src/assets/illustrations/placeholders/search-4.svg"
                                    alt="" />
                                <img class="dark-image" src="/@src/assets/illustrations/placeholders/search-4-dark.svg"
                                    alt="" />
                            </template>
                        </VPlaceholderSection>
                    </div>
                    <TransitionGroup v-else name="list" tag="div" class="columns is-multiline">
                        <!--Grid item-->
                        <div v-for="(item, key) in dataSourcefiltered" :key="key" class="column is-6">
                            <div class="tile-grid-item">
                                <div class="tile-grid-item-inner">
                                    <VIconBox size="medium" color="warning" rounded>
                                        <i class="fas fa-envelope-open"></i>
                                    </VIconBox>

                                    <div class="meta">
                                        <span class="dark-inverted mb-2">{{ item.merkproduk }}</span>
                                        <span class="dark-inverted">{{ item.namadepartemen }}</span>

                                    </div>
                                    <VTag :color="item.status_c" :label="item.status" style="margin-left:25px" />
                                    <VDropdown icon="feather:more-vertical" spaced right>
                                        <template #content>
                                            <a role="menuitem" class="dropdown-item is-media" @click="detail(item)">
                                                <div class="icon">
                                                    <i class="iconify" data-icon="feather:bookmark" aria-hidden="true"></i>
                                                </div>
                                                <div class="meta">
                                                    <span>Detail</span>
                                                    <span>Untuk melihat data </span>
                                                </div>
                                            </a>
                                            <a role="menuitem" class="dropdown-item is-media" @click="edit(item)">
                                                <div class="icon">
                                                    <i class="iconify" data-icon="feather:edit" aria-hidden="true"></i>
                                                </div>
                                                <div class="meta">
                                                    <span>Edit</span>
                                                    <span>Untuk merubah data </span>
                                                </div>
                                            </a>
                                            <a role="menuitem" v-if="item.statusenabled == true"
                                                class="dropdown-item is-media" @click="dialogConfirm(item)">
                                                <div class="icon">
                                                    <i aria-hidden="true" class="lnil lnil-trash-can-alt"></i>
                                                </div>
                                                <div class="meta">
                                                    <span>Remove</span>
                                                    <span>Hapus Data dari Daftar</span>
                                                </div>
                                            </a>
                                        </template>
                                    </VDropdown>
                                </div>
                            </div>
                        </div>
                    </TransitionGroup>

                    <VFlexPagination v-model:current-page="currentPage.page" :item-per-page="currentPage.limit"
                        :total-items="item.itemLength < 5 ? item.itemLength : 50" :max-links-displayed="5">
                        <template #before-pagination>
                        </template>
                        <template #before-navigation>
                            <VFlex class="mr-4 mt-1" column-gap="1rem">
                                <VField>

                                </VField>
                                <VField>
                                    <VControl>
                                        <div class="select is-rounded">
                                            <select v-model="currentPage.limit">
                                                <option :value="1">1 results per page</option>
                                                <option :value="5">5 results per page</option>
                                                <option :value="10">10 results per page</option>
                                                <option :value="15">15 results per page</option>
                                                <option :value="25">25 results per page</option>
                                                <option :value="50">50 results per page</option>
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

        <VModal :open="modalDetail" title="Detail Merk Produk" noclose actions="right"
            @close="modalDetail = false, clear()">
            <template #content>
                <form class="modal-form">
                    <div class="columns is-multiline">
                        <div class="column is-12">
                            <VField label="Merk Produk">
                                <VControl icon="feather:bookmark">
                                    <VInput type="text" readonly style="cursor:pointer" v-model="item.merkproduk"
                                        class="is-rounded" />
                                </VControl>
                            </VField>
                        </div>
                        <div class="column is-12">
                            <VField label="Departemen">
                                <VControl icon="feather:bookmark">
                                    <VInput type="text" readonly style="cursor:pointer" v-model="item.namadepartemen"
                                        class="is-rounded" />
                                </VControl>
                            </VField>
                        </div>
                        <div class="column is-6">
                            <VField label="Kode External">
                                <VControl icon="feather:bookmark">
                                    <VInput type="text" readonly style="cursor:pointer" v-model="item.kodeexternal"
                                        placeholder="Kode External" class="is-rounded" />
                                </VControl>
                            </VField>
                        </div>
                        <div class="column is-6">
                            <VField label="Status Aktivasi">
                                <VControl icon="feather:bookmark">
                                    <VInput type="text" readonly style="cursor:pointer" v-model="item.status"
                                        placeholder="Norec" class="is-rounded" />
                                </VControl>
                            </VField>
                        </div>

                    </div>
                </form>
            </template>

        </VModal>
    </VCard>
</template>
  
<script  setup lang="ts">
import { useApi } from '/@src/composable/useApi'
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useConfirm } from 'primevue/useconfirm'
import { useHead } from '@vueuse/head'
import { useToaster } from '/@src/composable/toaster'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import ConfirmDialog from 'primevue/confirmdialog'
import InputSwitch from 'primevue/inputswitch'
useHead({
    title: 'Merk Produk - ' + import.meta.env.VITE_PROJECT,
})

useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(false)

const confirm = useConfirm()

const item: any = ref({
    aktif: true,
    itemLength: 0,
})
const d_Departemen = ref([])
const modalDetail = ref(false)

let dataSource: any = ref([])
const d_View = [
    {
        name: 'Grid View',
        value: 'grid',
        icon: 'fas fa-id-card-alt',
    },
    {
        name: 'List View',
        value: 'list',
        icon: 'fas fa-list',
    },
]

const selectView: any = ref()
selectView.value = 'grid'
let isLoadingBtn: any = ref(false)
let isLoading: any = ref(false)

const filters = ref('')
const route = useRoute()

const dataSourcefiltered = computed(() => {
    if (!filters.value) {
        return dataSource.value
    }

    return dataSource.value.filter((items: any) => {
        return (
            items.merkproduk.match(new RegExp(filters.value, 'i'))
        )
    })
})

const currentPage: any = ref({
    limit: 5,
    rows: 30,
})

currentPage.value.page = computed(() => {
    try {
        return Number.parseInt(route.query.page as string)
    }
    catch { }
    return 1
})

watch(currentPage.value, () => {
    fetchData()
})

const fetchData = async () => {
    isLoading.value = true
    let limit: any = currentPage.value.limit
    let offset: any = route.query.page ? route.query.page : 1
    offset = (offset * limit) - limit
    await useApi().get(`/sysadmin/master-merk-produk?statusenabled=${item.value.aktif}&limit=${limit}&offset=${offset}`).then((response) => {
        response.data.forEach((element: any, i: any) => {
            element.no = i + 1
        });
        dataSource.value = response.data
        isLoading.value = false
        item.value.itemLength = response.data.length
    })
}

const loadDropdown = async () => {
    const response = await useApi().get(`/sysadmin/master-merk-produk/select-item`)
    d_Departemen.value = response.departemen.map((e: any) => {
        return { label: e.namadepartemen, value: e.id }
    })
}

const save = async () => {
    if (!item.value.merkproduk) {
        useToaster().error('Merk Produk harus di isi')
        return
    }
    if (!item.value.objectdepartemenfk) {
        useToaster().error('Departemen harus di isi')
        return
    }
    var objSave =
    {
        'merkproduk': {
            'id': item.value.id ? item.value.id : '',
            'merkproduk': item.value.merkproduk,
            'statusenabled': item.value.statusenabled,
            'objectdepartemenfk': item.value.objectdepartemenfk,
        }
    }
    isLoadingBtn.value = true
    await useApi().post(
        `/sysadmin/master-merk-produk/save`, objSave).then((response: any) => {
            isLoadingBtn.value = false
            clear()
            fetchData()
        }, (error) => {
            isLoadingBtn.value = false
            // console.log(error)
        })
}

const deleteItem = async (e: any) => {
    isLoading.value = true
    await useApi().post(
        `/sysadmin/master-merk-produk/delete`, { 'id': e.id }).then((response: any) => {
            isLoading.value = false
            clear()
            fetchData()
        }, (error) => {
        })
}

const dialogConfirm = (e: any) => {
    confirm.require({
        message: 'Apakah anda serius menghapus data ini ?',
        header: 'Konfirmasi Hapus Data',
        icon: 'pi pi-info-circle',
        acceptClass: 'p-button-danger',
        accept: () => {
            deleteItem(e)
        },
        reject: () => { },
    })
}

const edit = (e: any) => {
    item.value.id = e.id
    item.value.merkproduk = e.merkproduk
    item.value.statusenabled = e.statusenabled
    item.value.objectdepartemenfk = e.objectdepartemenfk
}
const detail = (e: any) => {
    item.value.id = e.id
    item.value.merkproduk = e.merkproduk
    item.value.kodeexternal = e.kodeexternal
    item.value.status = e.status
    item.value.namadepartemen = e.namadepartemen
    modalDetail.value = true
}

const clear = () => {
    item.value.id = ''
    item.value.merkproduk = ''
    item.value.objectdepartemenfk = ''
}
const changeView = (e: any) => {
    selectView.value = e
}


loadDropdown()
fetchData()

</script>
<style lang="scss">
@import '/@src/scss/abstracts/all';

@import '/@src/scss/components/forms-outer';
@import '/@src/scss/custom/config';
@import '/@src/scss/module/sysadmin/master-data.scss';</style>