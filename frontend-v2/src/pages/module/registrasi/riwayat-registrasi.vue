<template>
    <div class="columns is-multiline">
        <div class="column is-12" v-if="isLoadingPasien">
            <div class="block-green">
                <div class="flex-list-inner mb-4">
                    <div class="flex-table-item grid-item mb-4" v-for="key in 1" :key="key">
                        <VFlexTableCell :column="{ grow: true, media: true }">
                            <VPlaceloadAvatar size="medium" />
                            <VPlaceloadText :lines="2" width="30%" last-line-width="20%" class="mx-2" />
                        </VFlexTableCell>
                        <VFlexTableCell>
                            <VPlaceload width="100%" height="70px" class="mx-1 mt-2" />
                        </VFlexTableCell>
                        <VFlexTableCell>
                            <VPlaceload width="10%" height="20px" class="mx-1 mt-1" />
                        </VFlexTableCell>
                        <VFlexTableCell :column="{ align: 'end' }">
                            <VPlaceload width="10%" class="mx-1" />
                        </VFlexTableCell>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <div class="page-content-inner">
        <div class="has-navbar-spacing">
            <div class="business-dashboard hr-dashboard">
                <div class="columns is-multiline">

                    <div class="column is-12" v-if="!isLoadingPasien">
                        <div class="block-header">
                            <div class="left">
                                <div class="current-user">
                                  
                                    <VAvatar size="medium"
                                        :picture="mitra.jeniskelamin ==
                                        'PEREMPUAN' ? '/images/avatars/svg/vuero-4.svg' : '/images/avatars/svg/vuero-1.svg'" squared />
                                    <h3>{{ mitra.namaperusahaan }}</h3>
                           
                                </div>
                            </div>
                            <div class="center">
                                <div class="columns">
                                    <div class="column">
                                        <h4 class="block-heading">No HP</h4>
                                        <p style="color: white;"> {{ mitra.nohp }}</p>
                                        <h4 class="block-heading">Tgl Daftar </h4>
                                        <p style="color: white;"> {{ mitra.tgldaftar }}</p>
                                    </div>
                                    <div class="column">
                                        <h4 class="block-heading">Alamat </h4>
                                        <p style="color: white;"> {{ mitra.alamatktr }}</p>
                                        <h4 class="block-heading">Email</h4>
                                        <p style="color: white;"> {{ mitra.email }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="column is-2" v-if="dataSourceRiwayat.loading">
                        <div class="list-view list-view-v1">
                            <div class="list-view-inner">
                                <div v-for="key in 10" :key="key" class="list-view-item mb-10">
                                    <VAvatarStack class="is-pushed-mobile">
                                        <VPlaceload class="mx-2 h-hidden-tablet-p mt-3" />
                                        <VPlaceloadAvatar size="small" class="mx-1" />
                                    </VAvatarStack>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="column is-10" v-if="dataSourceRiwayat.loading">
                        <div class="list-view list-view-v1">
                            <div class="list-view-inner">
                                <!--Item-->
                                <div v-for="key in 10" :key="key" class="list-view-item">
                                    <VPlaceloadWrap>
                                        <VPlaceloadAvatar size="medium" />
                                        <VPlaceloadText last-line-width="60%" class="mx-2" />
                                        <VPlaceload class="mx-2" disabled />
                                        <VPlaceload class="mx-2 h-hidden-tablet-p" />
                                        <VPlaceload class="mx-2 h-hidden-tablet-p" />
                                        <VPlaceload class="mx-2" />
                                    </VPlaceloadWrap>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="timeline-wrapper" v-if="dataSourceRiwayat.length > 0">
        <div class="timeline-header"></div>
        <div class="timeline-wrapper-inner">
            <div class="timeline-container">
                <div class="timeline-item is-unread" v-for="(items, index)  in dataSourceRiwayat" :key="items.norec">
                    <div class="date">
                        <span>{{ H.formatDateIndo(items.tglregistrasi) }}</span>
                    </div>
                    <div :class="'dot is-' + listColor[index + 1]"></div>
                    <div class="content-wrap">
                        <div class="content-box">
                            <div class="status"></div>
                            <div class="box-text">
                                <div class="meta-text">
                                    <p>
                                        <span>{{ items.jenisorder }}</span> dengan jumlah alat <a>{{ items.jumlah_detailalat ?items.jumlah_detailalat : '-'}}</a>
                                    </p>
                                    <span>No Pendaftaran : {{ items.nopendaftaran }}</span><br>
                                    <span>Tanggal Selesai : </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="load-more-wrap has-text-centered">
                <VButton dark-outlined>Load More</VButton>
            </div>
        </div>
    </div>
    <VCard radius="rounded" class="mt-2" v-if="dataSourceRiwayat.length === 0">
        <VPlaceholderPage title="We couldn't find any matching results." subtitle="Too bad. Looks like we couldn't find any matching results for the
        search terms you've entered. Please try different search terms or
        criteria." larger>
            <template #image>
                <img class="light-image" src="/@src/assets/illustrations/placeholders/search-4.svg" alt="" />
                <img class="dark-image" src="/@src/assets/illustrations/placeholders/search-4-dark.svg" alt="" />
            </template>
        </VPlaceholderPage>
    </VCard>
</template>
<script setup lang="ts">
import { useApi } from '/@src/composable/useApi'
import { ref, computed, watch } from 'vue'
import { useHead } from '@vueuse/head'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import { useRoute, useRouter } from 'vue-router'
import { todoList3, todoList4 } from '/@src/data/widgets/list/todoList'
import { useThemeColors } from '/@src/composable/useThemeColors'
import * as H from '/@src/utils/appHelper'
useHead({
    title: 'Riwayat Registrasi - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
let ID_MITRA = useRoute().query.nomitrafk as string
let mitra: any = ref({})
let isLoadingPasien: any = ref(false)
const dataSourceRiwayat: any = ref([])
let listColor: any = ref(Object.keys(useThemeColors()))
function headerPasien() {
    isLoadingPasien.value = true
    useApi().get(`/registrasi/mitra-registrasi?id=${ID_MITRA}`).then((response: any) => {
        mitra.value = response.mitra
        isLoadingPasien.value = false
    }).catch((e: any) => {
        isLoadingPasien.value = false
    })
}
function riwayatPasien() {
    dataSourceRiwayat.value.loading = true
    useApi().get(
        `/registrasi/riwayat-registrasi?id=${ID_MITRA}`).then((response: any) => {
            for (let x = 0; x < response.length; x++) {
                const element = response[x];
                element.no = x + 1
            }
            dataSourceRiwayat.value = response
            dataSourceRiwayat.value.loading = false
        }).catch((e: any) => {

        })
}
headerPasien()
riwayatPasien()

const data = [
    {
        type: 'messages',
        count: 5,
        status: 'new',
    },
    {
        type: 'tasks',
        count: 3,
        status: 'pending',
    },
]

const columns = {
    type: {
        label: 'Type',
        grow: 'lg',
        media: true,
    },
    count: {
        label: 'Count',
        align: 'center',
    },
    status: 'Status',
    actions: {
        label: 'Actions',
        align: 'end',
    },
} as const
</script>


<style lang="scss" scoped>
@import '/@src/scss/abstracts/all';
@import '/@src/scss/components/forms-outer';
@import '/@src/scss/custom/config';
@import '/@src/scss/custom/timeline-css';

.list-view-v1 {
    .list-view-item {
        @include vuero-r-card;

        margin-bottom: 16px;
        padding: 16px;

        .list-view-item-inner {
            display: flex;
            align-items: center;

            .meta-left {
                margin-left: 16px;

                h3 {
                    font-family: var(--font-alt);
                    color: var(--dark-text);
                    font-weight: 600;
                    font-size: 1rem;
                    line-height: 1;
                }

                >span:not(.tag) {
                    font-size: 0.9rem;
                    color: var(--light-text);

                    svg {
                        height: 12px;
                        width: 12px;
                    }
                }
            }

            .meta-right {
                margin-left: auto;
                display: flex;
                justify-content: flex-end;
                align-items: center;

                .tags {
                    margin-right: 30px;
                    margin-bottom: 0;

                    .tag {
                        margin-bottom: 0;
                    }
                }

                .stats {
                    display: flex;
                    align-items: center;
                    margin-right: 30px;

                    .stat {
                        display: flex;
                        align-items: center;
                        flex-direction: column;
                        text-align: center;
                        color: var(--light-text);

                        >span {
                            font-family: var(--font);

                            &:first-child {
                                font-size: 1.2rem;
                                font-weight: 600;
                                color: var(--dark-text);
                                line-height: 1.4;
                            }

                            &:nth-child(2) {
                                text-transform: uppercase;
                                font-family: var(--font-alt);
                                font-size: 0.75rem;
                            }
                        }

                        svg {
                            height: 16px;
                            width: 16px;
                        }

                        i {
                            font-size: 1.4rem;
                        }
                    }

                    .separator {
                        height: 25px;
                        width: 2px;
                        border-right: 1px solid var(--fade-grey-dark-3);
                        margin: 0 16px;
                    }
                }

                .network {
                    display: flex;
                    justify-content: flex-end;
                    align-items: center;
                    min-width: 145px;

                    >span {
                        font-family: var(--font);
                        font-size: 0.9rem;
                        color: var(--light-text);
                        margin-left: 6px;
                    }
                }

                .dropdown {
                    margin-left: 30px;
                }
            }
        }
    }
}

.is-dark {
    .list-view-v1 {
        .list-view-item {
            @include vuero-card--dark;

            .list-view-item-inner {
                .meta-left {
                    h3 {
                        color: var(--dark-dark-text) !important;
                    }
                }

                .meta-right {
                    .stats {
                        .stat {
                            span {
                                &:first-child {
                                    color: var(--dark-dark-text);
                                }
                            }
                        }

                        .separator {
                            border-color: var(--dark-sidebar-light-16) !important;
                        }
                    }
                }
            }
        }
    }
}

@media only screen and (max-width: 767px) {
    .list-view-v1 {
        .list-view-item {
            .list-view-item-inner {
                position: relative;
                flex-direction: column;

                .v-avatar {
                    margin-bottom: 10px;
                }

                .meta-left {
                    margin-left: 0;
                }

                .meta-right {
                    flex-direction: column;
                    margin-left: 0;

                    .tags {
                        margin: 10px 0;
                    }

                    .stats {
                        margin: 10px 0;
                    }

                    .network {
                        margin: 10px 0 0;
                        justify-content: center;

                        >span {
                            display: none;
                        }
                    }

                    .dropdown {
                        position: absolute;
                        top: 0;
                        right: 0;
                        margin-left: 0;
                    }
                }
            }
        }
    }
}

@media only screen and (min-width: 768px) and (max-width: 1024px) and (orientation: portrait) {
    .list-view-v1 {
        display: flex;
        flex-wrap: wrap;

        .list-view-item {
            margin: 10px;
            width: calc(50% - 20px);

            .list-view-item-inner {
                position: relative;
                flex-direction: column;

                .v-avatar {
                    margin-bottom: 10px;
                }

                .meta-left {
                    margin-left: 0;
                }

                .meta-right {
                    flex-direction: column;
                    margin-left: 0;

                    .tags {
                        margin: 10px 0;
                    }

                    .stats {
                        margin: 10px 0;
                    }

                    .network {
                        margin: 10px 0 0;
                        justify-content: center;

                        >span {
                            display: none;
                        }
                    }

                    .dropdown {
                        position: absolute;
                        top: 0;
                        right: 0;
                        margin-left: 0;
                    }
                }
            }
        }
    }
}
</style>
