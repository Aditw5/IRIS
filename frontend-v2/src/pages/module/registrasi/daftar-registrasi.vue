

<template>
    <div class="page-content-inner">
        <div class="jobs-dashboard">
            <div class="jobs-dashboard-wrapper">
                <!--Search toolbar -->
                <div class="search-menu">
                    <div class="search-bar">
                        <VField class="mt-3">
                            <VDatePicker v-model="item.periode" is-range color="pink" trim-weeks>
                                <template #default="{ inputValue, inputEvents }">
                                    <VField addons>
                                        <VControl icon="feather:calendar">
                                            <VInput :value="inputValue.start" v-on="inputEvents.start" />
                                        </VControl>
                                        <VControl>
                                            <VButton static icon="feather:arrow-right" />
                                        </VControl>
                                        <VControl subcontrol icon="feather:calendar">
                                            <VInput :value="inputValue.end" v-on="inputEvents.end" />
                                        </VControl>
                                    </VField>
                                </template>
                            </VDatePicker>
                        </VField>

                    </div>
                    <div class="search-location">
                        <i class="iconify" data-icon="feather:activity"></i>
                        <input type="text" placeholder="No Registrasi" v-model="item.qnoreg" />
                    </div>

                    <div class="search-salary">
                        <i class="iconify" data-icon="feather:clipboard"></i>
                        <input type="text" placeholder="No RM" v-model="item.qnocm" />
                    </div>
                    <div class="search-job">
                        <i class="iconify" data-icon="feather:user"></i>
                        <input type="text" placeholder="Nama Pasien" v-model="item.qnama" />
                    </div>
                    <VButton color="primary" raised class="search-button" @click="fetchData()"
                        :loading="dataSource.loading">Search</VButton>
                </div>

                <!--Dashboard content -->
                <div class="main-container">
                    <!--Left Alert -->
                    <div class="search-type">
                        <div class="alert">
                            <!-- <div class="alert-title">Create Alert</div> -->
                            <!-- <div class="alert-subtitle">Create a alert now and never miss a job</div> -->
                            <div class="columns is-multiline">
                                <div class="column is-12">
                                    <VField label="Instalasi" class="is-autocomplete-select is-curved-select">
                                        <VControl icon="fas fa-building">

                                            <Multiselect mode="single" v-model="item.qdepartemen" :options="d_departemen"
                                                placeholder="Instalasi" :searchable="true" autocomplete="off"
                                                @select="changeDep(item.qdepartemen)" :close-on-select="true" />

                                        </VControl>
                                    </VField>
                                </div>

                                <div class="column is-12">
                                    <VField label="Ruangan" class="is-autocomplete-select is-curved-select">
                                        <VControl icon="fas fa-clinic-medical">
                                            <Multiselect v-model="item.qruang" :searchable="true" mode="single"
                                                :options="d_ruangan" placeholder="Ruangan" :close-on-select="true" />
                                        </VControl>
                                    </VField>
                                </div>
                            </div>

                            <!-- <VButton color="primary" fullwidth>Create</VButton> -->
                        </div>

                        <!--Left filters block -->
                        <div class="job-time">
                            <div class="job-time-title">Jenis Pembiayaan</div>
                            <div class="job-wrapper">
                                <div class="type-container" v-for="items of d_kelompokpasien">
                                    <VCheckbox alue-true="job-type-1" :label="items.kelompokpasien" color="primary"
                                        paddingless :value="items.id" v-model="checkboxProduk[items.id]"
                                        @change="clickProduk()" />
                                    <!-- <Checkbox :inputId="items.id" :name="items.id" :binary="true" v-model="items.id"
                                        class="mt-1" paddingless />
                                                                                                                            <label :for="items.id" class="ml-2 " style="font-size: 0.75rem;">{{ items.kelompokpasien
                                                                                                                            }}</label> -->
                                    <span class="job-number">{{ items.count }}</span>
                                </div>
                                <!-- <div class="type-container">
                                    <VCheckbox v-model="jobType" value-true="job-type-2" label="BPJS" color="primary"
                                        paddingless />
                                    <span class="job-number">43</span>
                                </div>
                                <div class="type-container">
                                    <VCheckbox v-model="jobType" value-true="job-type-3" label="Asuransi Lain"
                                        color="primary" paddingless />
                                    <span class="job-number">24</span>
                                </div>
                                <div class="type-container">
                                    <VCheckbox v-model="jobType" value-true="job-type-4" label="Perusahaan"
                                        color="primary" paddingless />
                                                                                                                                                            <span class="job-number">27</span>
                                                                                                                                                        </div> -->

                            </div>
                        </div>

                        <!--Left filters block -->
                        <div class="job-time">
                            <div class="job-time-title">Instalasi</div>
                            <div class="job-wrapper">
                                <div class="type-container" v-for="items of d_departemen_f">
                                    <VCheckbox v-model="item.qdepartemen" value-true="job-seniority-1"
                                        :label="items.namadepartemen" color="primary" paddingless />
                                    <span class="job-number">{{ items.count }}</span>
                                </div>

                            </div>
                        </div>


                    </div>

                    <!--Results-->
                    <div class="searched-jobs">
                        <!--Results toolbar-->
                        <div class="searched-bar">
                            <div class="searched-count">
                                Menampilkan {{ dataSource.length > 0 ? currentPage.page : 0 }} ke {{ dataSource.length >
                                    0 ? currentPage.limit : 0
                                }} dari
                                {{ dataSource.length }} entri data
                                <!-- Showing {{ dataSource.length }} data -->
                            </div>
                            <div class="searched-link">
                                <!-- <a class="action-link" tabindex="0" @click="clear()">View All</a> -->
                            </div>
                        </div>

                        <!--Results content-->
                        <div class="user-grid user-grid-v1 mt-5" v-if="dataSource.loading">
                            <div class="columns is-multiline">
                                <!--Grid item-->
                                <div v-for="key in 9" :key="key" class="column is-4">
                                    <div class="grid-item">
                                        <VPlaceloadAvatar size="big" centered class="mb-2" />

                                        <VPlaceloadText class="mb-4" width="80%" :lines="3" last-line-width="60%"
                                            centered />

                                        <div class="people">
                                            <VPlaceloadAvatar size="small" class="mx-1" />
                                            <VPlaceloadAvatar size="small" class="mx-1" />
                                            <VPlaceloadAvatar size="small" class="mx-1" />
                                            <VPlaceloadAvatar size="small" class="mx-1" />
                                        </div>

                                        <VButtons>
                                            <VButton placeload="100%" dark-outlined>loading ...</VButton>
                                            <VButton placeload="100%" dark-outlined>loading ...</VButton>
                                        </VButtons>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex-list-inner" v-else-if="dataSource.length === 0">
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
                        <div class="job-cards" v-else-if="dataSource.length > 0">
                            <div v-for="(job, index) in dataSource" :key="index" class="job-card">
                                <div style="float:right">
                                    <VDropdown icon="feather:more-vertical" spaced right>
                                        <template #content>
                                            <a href="#" role="menuitem" class="dropdown-item is-media"
                                                @click="editRegistrasi(job)">
                                                <div class="icon">
                                                    <i aria-hidden="true" class="lnil lnil-pencil-alt"></i>
                                                </div>
                                                <div class="meta">
                                                    <span>Edit</span>
                                                    <span>Edit registrasi</span>
                                                </div>
                                            </a>

                                            <a href="#" role="menuitem" class="dropdown-item is-media">
                                                <div class="icon">
                                                    <i aria-hidden="true" class="lnil lnil-eye"></i>
                                                </div>
                                                <div class="meta">
                                                    <span>Details</span>
                                                    <span>Detail registrasi</span>
                                                </div>
                                            </a>

                                            <a href="#" role="menuitem" class="dropdown-item is-media">
                                                <div class="icon">
                                                    <i aria-hidden="true" class="lnil lnil-share"></i>
                                                </div>
                                                <div class="meta">
                                                    <span>Tindakan</span>
                                                    <span>Input tindakan</span>
                                                </div>
                                            </a>

                                            <a href="#" role="menuitem" class="dropdown-item is-media">
                                                <div class="icon">
                                                    <i aria-hidden="true" class="lnil lnil-share"></i>
                                                </div>
                                                <div class="meta">
                                                    <span>Share</span>
                                                    <span>Share this profile</span>
                                                </div>
                                            </a>

                                            <hr class="dropdown-divider" />

                                            <a href="#" role="menuitem" class="dropdown-item is-media">
                                                <div class="icon">
                                                    <i aria-hidden="true" class="lnil lnil-trash-can-alt"></i>
                                                </div>
                                                <div class="meta">
                                                    <span>Remove</span>
                                                    <span>Remove from grid</span>
                                                </div>
                                            </a>
                                        </template>
                                    </VDropdown>
                                </div>
                                <div class="job-card-header">
                                    <img class="job-card-logo"
                                        :src="job.icons != null ? job.icons : '/images/ruang/no_image.png'" alt="" />
                                </div>
                                <div class="job-card-title">{{ job.namaruangan }}</div>
                                <div class="job-card-subtitle">
                                    <i class="iconify" data-icon="feather:user" aria-hidden="true"></i>
                                    <span class="ml-1"><b> {{ job.namapasien }}</b></span>
                                </div>
                                <div class="job-card-subtitle">
                                    <i class="iconify" data-icon="feather:activity" aria-hidden="true"></i>
                                    <span class="ml-1"> {{ job.noregistrasi }}</span>
                                </div>
                                <div class="job-card-subtitle">
                                    <i class="iconify" data-icon="feather:calendar" aria-hidden="true"></i>
                                    <span class="ml-1"> {{ job.tglregistrasi }}</span>
                                </div>
                                <div class="job-card-subtitle">
                                    <i class="iconify" data-icon="feather:clipboard" aria-hidden="true"></i>
                                    <span class="ml-1"> {{ job.nocm }}</span>
                                </div>

                                <div class="job-card-subtitle">
                                    <!-- <i class="iconify" data-icon="feather:credit-card" aria-hidden="true"></i> -->
                                    <!-- <span class="ml-1"> {{ job.kelompokpasien }}</span> -->
                                    <VTag color="orange" class="mt-1"
                                        :label="job.kelompokpasien + (job.nosep != null ? ' - ' + job.nosep : '')" />
                                </div>

                                <div class="job-detail-buttons">
                                    <VTags>
                                        <VTag v-for="(category, catIndex) in job.categories" :key="catIndex" color="solid"
                                            :label="category.name" curved />
                                    </VTags>
                                </div>
                                <div class="job-card-buttons">
                                    <VButtons>
                                        <VButton color="primary" icon="fas fa-stethoscope" @click="rekamMedis(job)">RME
                                        </VButton>
                                        <VButton dark-outlined raised>Detail Registrasi</VButton>

                                    </VButtons>
                                </div>
                            </div>

                            <!-- <div v-for="(job, index) in jobs" :key="index" class="job-card">
                                <div class="job-card-header">
                                    <img class="job-card-logo" :src="'/images/ruang/' + (index + 1) + '.png'" alt="" />
                                </div>
                                <div class="job-card-title">{{ job.title }}</div>
                                <div class="job-card-subtitle">
                                    {{ job.description }}
                                </div>
                                <div class="job-detail-buttons">
                                    <VTags>
                                        <VTag v-for="(category, catIndex) in job.categories" :key="catIndex"
                                            color="solid" :label="category.name" curved />
                                    </VTags>
                                </div>
                                <div class="job-card-buttons">
                                    <VButtons>
                                        <VButton color="primary" raised>Apply Now</VButton>
                                        <VButton dark-outlined>Messages</VButton>
                                    </VButtons>
                                                                                                                                                        </div>
                                                                                                                                                    </div> -->
                        </div>
                        <VFlexPagination v-if="dataSource.length > 0" v-model:current-page="currentPage.page"
                            :item-per-page="currentPage.limit" :total-items="dataSource.total < 10 ? dataSource.total : 50"
                            :max-links-displayed="10">
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
        </div>
    </div>
</template>
<script setup lang="ts">
import { useHead } from '@vueuse/head'
import { reactive, ref, computed, watch } from 'vue'
import { jobs } from '/@src/data/dashboards/jobs'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import { useApi } from '/@src/composable/useApi'
import { useRoute, useRouter } from 'vue-router'
import * as H from '/@src/utils/appHelper'
import Checkbox from 'primevue/checkbox';
export type Job = 'web-developer' | 'uiux-designer' | 'backend-developer'
useHead({
    title: 'Daftar Registrasi - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(false)
const route = useRoute()
const router = useRouter()
const tagsValue: Job[] = []
const tagsOptions = [
    { value: 'web-developer', label: 'Frontend' },
    { value: 'uiux-designer', label: 'UI/UX' },
    { value: 'backend-developer', label: 'Backend' },
]
const item: any = ref({
    periode: reactive({
        start: new Date(),
        end: new Date(),
    })
})
const jobType = ref(['job-type-2'])
const jobSeniority = ref(['job-seniority-3', 'job-seniority-4'])
const jobSalary = ref(['job-salary-5', 'job-salary-6'])
const dataSource: any = ref([])
const currentPage: any = ref({
    limit: 10,
    rows: 50
})
const checkboxProduk: any = ref([])
const listChecked: any = ref([])
const d_ListDefault: any = ref([])
const d_departemen: any = ref([])
const d_ruangan: any = ref([])
const d_ruanganTemp: any = ref([])
const d_kelompokpasien: any = ref([])
const d_departemen_f: any = ref([])
let c = H.cacheHelper().get('c_daftar-registrasi');
if (c != undefined) {
    item.value.periode.start = new Date(c[0]);
    item.value.periode.end = new Date(c[1]);
}

currentPage.value.page = computed(() => {
    try {
        return Number.parseInt(route.query.page as string) || 1
    } catch { }
    return 1
})
watch(currentPage.value, () => {
    fetchData()
})
async function fetchDropdown() {

    await useApi().get(`/registrasi/daftar-registrasi-dropdown`).then((response: any) => {
        d_departemen.value = response.departemen.map((e: any) => { return { label: e.namadepartemen, value: e.id, default: e } })
        d_ruanganTemp.value = response.ruangan
        d_ruangan.value = response.ruangan.map((e: any) => { return { label: e.namaruangan, value: e.id, more: e } })
        d_departemen_f.value = response.departemen_filter
        d_ListDefault.value = response.kelompokpasien

        d_kelompokpasien.value = response.kelompokpasien

    }).catch((e: any) => {
    })
}
function clickProduk() {

    let objectK = Object.keys(checkboxProduk.value)
    let jumlah = 0
    for (let x = 0; x < objectK.length; x++) {
        const element = objectK[x];
        if (checkboxProduk.value[element] == true) {
            for (var i = 0; i < d_ListDefault.value.length; i++) {
                const element2 = d_ListDefault.value[i];
                if (element2.id == element) {
                    for (var z = 0; z < listChecked.value.length; z++) {
                        const element3 = listChecked.value[z];
                        if (element3.id == element2.id) {
                            listChecked.value.splice(z, 1)
                        }
                    }
                    listChecked.value.push(element2)
                }
            }
        } else {
            for (var i = 0; i < d_ListDefault.value.length; i++) {
                const element2 = d_ListDefault.value[i];
                if (element2.id == element) {
                    for (var z = 0; z < listChecked.value.length; z++) {
                        const element3 = listChecked.value[z];
                        if (element3.id == element2.id) {
                            listChecked.value.splice(z, 1)
                        }
                    }
                }
            }
        }
    }
    fetchData()
}

async function fetchData() {
    dataSource.value.loading = true

    let limit: any = currentPage.value.limit
    let offset: any = route.query.page ? route.query.page : 1
    offset = (offset * limit) - limit
    let namapasien = ''
        , nocm = ''
        , dari = ''
        , sampai = ''
        , departemenfk = ''
        , ruanganfk = ''
        , dokterfk = ''
        , kelompokpasienfk = ''
        , noreg = ''

    if (item.value.periode.start) dari = `${H.formatDate(item.value.periode.start, 'YYYY-MM-DD')}`
    if (item.value.periode.end) sampai = `${H.formatDate(item.value.periode.end, 'YYYY-MM-DD')}`
    if (item.value.qnama) namapasien = `&namapasien=${item.value.qnama}`
    if (item.value.qnoreg) noreg = `&noregistrasi=${item.value.qnoreg}`
    if (item.value.qnocm) nocm = `&nocm=${item.value.qnocm}`
    if (item.value.qruang) ruanganfk = `&ruang=${item.value.qruang}`
    if (item.value.qdepartemen) departemenfk = `&dep=${item.value.qdepartemen}`

    if (listChecked.value) {
        var a = ""
        var b = ""
        for (var i = listChecked.value.length - 1; i >= 0; i--) {
            var c = listChecked.value[i].id
            b = "," + c
            a = a + b
        }
        kelompokpasienfk = a.slice(1, a.length)

    }

    const response = await useApi().get(`/registrasi/daftar-registrasi-grid?offset=${offset}&kelompokpasienfk=${kelompokpasienfk}&dari=${dari}&sampai=${sampai}&limit=${limit}&rows=${currentPage.value.rows}${namapasien}${nocm}${noreg}${ruanganfk}${departemenfk}`)

    dataSource.value.loading = false
    dataSource.value = response.data
    dataSource.value.total = response.data.length

    if (dataSource.value.length > 0) {
        for (let x = 0; x < d_kelompokpasien.value.length; x++) {
            const element = d_kelompokpasien.value[x];
            element.count = 0
            for (let xx = 0; xx < dataSource.value.length; xx++) {
                const element2 = dataSource.value[xx];
                if (element.id == element2.objectkelompokpasienlastfk) {
                    element.count = element.count + 1
                }
            }
        }

        for (let x = 0; x < d_departemen_f.value.length; x++) {
            const element = d_departemen_f.value[x];
            element.count = 0
            for (let xx = 0; xx < dataSource.value.length; xx++) {
                const element2 = dataSource.value[xx];
                if (element.id == element2.objectdepartemenfk) {
                    element.count = element.count + 1
                }
            }
        }
    }
    let c_set = {
        0: dari,
        1: sampai,
    }
    H.cacheHelper().set('c_daftar-registrasi', c_set);
}
function clear() {

}
function rekamMedis(e: any) {
    router.push({
        name: 'module-emr-profile-pasien',
        query: {
            nocmfk: e.nocmfk,
            norec_pd: e.norec,
        },
    })
}
function changeDep(e: any) {
    d_ruangan.value = []
    for (let i = 0; i < d_ruanganTemp.value.length; i++) {
        const element = d_ruanganTemp.value[i];
        if (element.objectdepartemenfk == e) {
            d_ruangan.value.push({ label: element.namaruangan, value: element.id, default: element })

        }
    }
}
function editRegistrasi(e: any) {
    router.push({
        name: 'module-registrasi-registrasi-ruangan',
        query: {
            nocmfk: e.nocmfk,
            norec_pd: e.norec,
            norec_apd: e.norec_apd,
        },
    })
}
fetchDropdown()
fetchData()


</script>
<style lang="scss">
@import '/@src/scss/abstracts/all';

.is-dark {
    .user-grid {
        .grid-item {
            @include vuero-card--dark;
        }
    }
}

.user-grid-v1 {

    .columns {
        margin-left: -0.5rem !important;
        margin-right: -0.5rem !important;
        margin-top: -0.5rem !important;
    }

    .column {
        padding: 0.5rem !important;
    }

    .grid-item {
        @include vuero-s-card;

        text-align: center;

        >.v-avatar {
            display: block;
            margin: 0 auto 4px;
        }

        h3 {
            font-family: var(--font-alt);
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-text);
        }

        p {
            font-size: 0.85rem;
        }

        .people {
            display: flex;
            justify-content: center;
            padding: 8px 0 30px;

            .v-avatar {
                margin: 0 4px;
            }
        }

        .buttons {
            display: flex;
            justify-content: space-between;

            .button {
                width: calc(50% - 4px);
                color: var(--light-text);

                &:hover,
                &:focus {
                    border-color: var(--fade-grey-dark-4);
                    color: var(--primary);
                    box-shadow: var(--light-box-shadow);
                }
            }
        }
    }
}

@media only screen and (min-width: 768px) and (max-width: 1024px) and (orientation: portrait) {
    .user-grid-v1 {
        .columns {
            display: flex;

            .column {
                min-width: 50% !important;
            }
        }
    }
}

@media only screen and (min-width: 768px) and (max-width: 1024px) and (orientation: landscape) {
    .user-grid-v1 {
        .columns {
            .column {
                min-width: 33.3% !important;
            }
        }
    }
}

:root {
    --header-bg-color: #fff;
    --search-border-color: #efefef;
    --subtitle-color: #83838e;
    --button-color: var(--white);
    --input-color: var(--subtitle-color);
}

.is-dark {
    --header-bg-color: var(--dark-sidebar-light-2);
    --search-border-color: var(--dark-sidebar-light-8);
    --input-color: var(--white);
}

.jobs-dashboard {
    display: flex;
    flex-direction: column;
    margin: 0 auto;
    overflow: hidden;

    .jobs-dashboard-wrapper {
        width: 100%;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        scroll-behavior: smooth;
        overflow: auto;
    }

    .search-menu {
        height: 56px;
        white-space: nowrap;
        display: flex;
        flex-shrink: 0;
        align-items: center;
        background-color: var(--header-bg-color);
        border-radius: 8px;
        width: 100%;
        padding-left: 0.75rem;

        >div:not(:last-of-type) {
            border-right: 1px solid var(--search-border-color);
        }

        .search-bar {
            height: 55px;
            width: 100%;
            position: relative;
            display: flex;
            align-items: center;
            padding-right: 1.5rem;

            .field {
                width: 100%;
            }

            .multiselect-tags {
                padding-left: 2.5rem;
            }
        }

        .search-location,
        .search-job,
        .search-salary {
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
                height: 100%;
                display: block;
                font-family: var(--font);
                color: var(--input-color);
                background-color: transparent;
                border: none;
            }

            svg {
                margin-right: 0.5rem;
                width: 18px;
                color: var(--primary);
                flex-shrink: 0;
            }
        }

        .search-button {
            background-color: var(--primary);
            min-width: 120px;
            height: 55px;
            border: none;
            font-weight: 500;
            font-family: var(--font);
            padding: 0 1rem;
            border-radius: 0 0.75rem 0.75rem 0;
            color: var(--button-color);
            cursor: pointer;
            margin-left: auto;
        }
    }

    .main-container {
        display: flex;
        flex-grow: 1;
        padding-top: 2rem;

        .search-type {
            width: 270px;
            display: flex;
            flex-direction: column;
            height: 100%;
            flex-shrink: 0;
        }

        .alert {
            background-color: var(--widget-grey);
            padding: 1.75rem;
            border-radius: 8px;

            .alert-title {
                font-size: 1rem;
                font-family: var(--font-alt);
                font-weight: 600;
                color: var(--dark-text);
                margin-bottom: 0.75rem;
            }

            .alert-subtitle {
                font-size: 13px;
                font-family: var(--font);
                color: var(--subtitle-color);
                margin-bottom: 1.5rem;
            }

            input {
                border-radius: 6px;
            }
        }

        .job-time {
            padding-top: 1.75rem;

            .job-wrapper {
                padding-top: 1.75rem;
            }

            .job-time-title {
                font-size: 0.95rem;
                font-family: var(--font-alt);
                font-weight: 600;
                color: var(--dark-text);
            }

            .type-container {
                display: flex;
                align-items: center;
                color: var(--subtitle-color);
                font-size: 13px;

                label {
                    margin-left: 2px;
                    display: flex;
                    align-items: center;
                    cursor: pointer;
                }

                +.type-container {
                    margin-top: 10px;
                }

                .job-number {
                    margin-left: auto;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 25px;
                    min-width: 25px;
                    background-color: var(--white);
                    color: var(--subtitle-color);
                    font-size: 0.8rem;
                    font-family: var(--font);
                    font-weight: 500;
                    padding: 0 0.25rem;
                    border-radius: 50rem;
                }
            }
        }

        .searched-jobs {
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            padding-left: 2.5rem;
        }

        .searched-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;

            .searched-count {
                font-family: var(--font-alt);
                font-size: 1rem;
                font-weight: 600;
                color: var(--dark-text);
            }
        }

        .job-cards {
            padding-top: 20px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-column-gap: 1.5rem;
            grid-row-gap: 1.5rem;

            @media screen and (max-width: 1212px) {
                grid-template-columns: repeat(2, 1fr);
            }

            @media screen and (max-width: 930px) {
                grid-template-columns: repeat(1, 1fr);
            }
        }

        .job-card {
            @include vuero-l-card;

            cursor: pointer;
            transition: 0.2s;

            &:hover,
            &:focus {
                transform: translateY(-5px);
            }

            .job-card-header {
                // display: flex;
                // align-items: flex-start;
                display: flex;
                justify-content: center;
                align-items: center;
            }

            .job-card-logo {
                width: 80px;
                height: 80px;
                margin-right: -40px;
            }

            .job-card-title {
                font-family: var(--font-alt);
                font-weight: 600;
                color: var(--dark-text);
                margin-bottom: 0.75rem;
                display: flex;
                justify-content: center;
                align-items: center;

                max-height: 42px;
                overflow: hidden;
                -webkit-box-orient: vertical;
                -webkit-line-clamp: 1;
                display: -webkit-box;
                text-align: center;
            }

            .job-card-subtitle {
                color: var(--subtitle-color);
                font-family: var(--font);
                font-size: 0.95rem;
                line-height: 1.6em;
                // margin-bottom: 1rem;
                margin-top: -5px;
                display: flex;
                justify-content: center;
                align-items: center;
            }

            .job-card-buttons {
                margin-top: 1rem;

                .buttons {
                    justify-content: space-between;

                    .v-button {
                        width: 48%;
                    }
                }
            }
        }
    }
}

.is-dark {
    .jobs-dashboard {
        .job-card {
            @include vuero-card--dark;
        }

        .main-container {
            .alert {
                @include vuero-card--dark;
            }

            .job-time {
                .job-number {
                    background: var(--dark-sidebar-light-2);
                }
            }
        }
    }
}

@media screen and (max-width: 620px) {
    .job-cards {
        grid-template-columns: repeat(1, 1fr);
    }
}

@media screen and (max-width: 730px) {
    .job-cards {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media screen and (max-width: 767px) {
    .jobs-dashboard {
        .search-menu {
            flex-direction: column;
            height: auto;
            padding: 1rem;

            >div:not(:last-of-type) {
                border-right: none;
            }

            .search-bar {
                padding: 0;
            }

            .search-location,
            .search-job,
            .search-salary {
                width: 100%;
                height: 44px;
                padding: 0;
            }

            .search-button {
                width: 100%;
                border-radius: 0.75rem;
            }
        }

        .main-container {
            .search-type {
                display: none;
            }

            .searched-jobs {
                padding-left: 0;
            }
        }
    }
}
</style>
