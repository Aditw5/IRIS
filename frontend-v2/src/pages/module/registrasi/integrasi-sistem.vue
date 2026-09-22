<template>
    <div>
        <div class="tile-grid-toolbar">
            <VControl icon="feather:search">
                <input v-model="filters" class="input custom-text-filter" placeholder="Search..." />
            </VControl>
        </div>
        <div class="tile-grid tile-grid-v2">
            <!--List Empty Search Placeholder -->
            <VPlaceholderPage :class="[filteredData.length !== 0 && 'is-hidden']"
                title="We couldn't find any matching results." subtitle="Too bad. Looks like we couldn't find any matching results for the
                search terms you've entered. Please try different search terms or
                criteria." larger>
                <template #image>
                    <img class="light-image" src="/@src/assets/illustrations/placeholders/search-4.svg" alt="" />
                    <img class="dark-image" src="/@src/assets/illustrations/placeholders/search-4-dark.svg" alt="" />
                </template>
            </VPlaceholderPage>

            <!--Tile Grid v1-->
            <TransitionGroup name="list" tag="div" class="columns is-multiline">
                <!--Grid item-->
                <div v-for="item in filteredData" :key="item.id" class="column is-4" @click="goTo(item)">
                    <div class="tile-grid-item">
                        <div class="tile-grid-item-inner">
                            <img v-if="item.icon" :src="item.icon" alt="" @error.once="(event) => onceImageErrored(event, '150x150')" />
                            <!-- <i  v-if="item.fa"  class="fas fa-flask" aria-hidden="true"></i> -->
                            <div class="meta">
                                <span class="dark-inverted">{{ item.name }}</span>
                                <span>
                                    <span>{{ item.company }}</span>
                                </span>
                            </div>
                            <VIconButton color="primary" outlined spaced right circle icon="feather:log-in" />
                        </div>
                    </div>
                </div>
            </TransitionGroup>
        </div>
    </div>
</template>
<style lang="scss">
@import '/@src/scss/abstracts/all';

.tile-grid {
    .columns {
        margin-left: -0.5rem !important;
        margin-right: -0.5rem !important;
        margin-top: -0.5rem !important;
    }

    .column {
        padding: 0.5rem !important;
    }
}

.tile-grid-v2 {
    .tile-grid-item {
        @include vuero-s-card;

        border-radius: 14px;
        padding: 16px;
        cursor: pointer;

        &:hover,
        &:focus {
            border-color: var(--primary);
            box-shadow: var(--light-box-shadow);
        }

        .tile-grid-item-inner {
            display: flex;
            align-items: center;

            >img {
                display: block;
                width: 50px;
                height: 50px;
                min-width: 50px;
            }

            .meta {
                margin-left: 10px;
                line-height: 1.4;

                span {
                    display: block;
                    font-family: var(--font);

                    &:first-child {
                        color: var(--dark-text);
                        font-family: var(--font-alt);
                        font-weight: 600;
                        font-size: 1rem;
                    }

                    &:nth-child(2) {
                        display: flex;
                        align-items: center;

                        span {
                            display: inline-block;
                            color: var(--light-text);
                            font-size: 0.8rem;
                            font-weight: 400;
                        }

                        .icon-separator {
                            position: relative;
                            font-size: 4px;
                            color: var(--light-text);
                            padding: 0 6px;
                        }
                    }
                }
            }

            .dropdown {
                margin-left: auto;
            }
        }
    }
}

.is-dark {
    .tile-grid {
        .tile-grid-item {
            @include vuero-card--dark;
        }
    }

    .tile-grid-v2 {
        .tile-grid-item {
            @include vuero-card--dark;

            &:hover,
            &:focus {
                border-color: var(--primary) !important;
            }
        }
    }
}
</style>
<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import { ref, computed, watch, reactive } from 'vue'
import { useThemeColors } from '/@src/composable/useThemeColors'
import { onceImageErrored } from '/@src/utils/via-placeholder'

import { useApi } from '/@src/composable/useApi'
import { useUserSession } from '/@src/stores/userSession'
import { useHead } from '@vueuse/head'
import { useViewWrapper } from '/@src/stores/viewWrapper'
useHead({
    title: 'Integrasi Sistem - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(false)

const router = useRouter()
const files = [
    {
        id: 1,
        icon: '/images/icons/files/bpjs.svg',
        name: 'BPJS',
        company: 'Badan Penyelenggara Jaminan Sosial - Kesehatan',
        route: 'module-integrasi-sistem-bpjs-tools-dashboard-vclaim',
    },
    {
        id: 2,
        icon: '/images/icons/files/kemkes.svg',
        name: `INACBG's`,
        company: 'INA-CBG Technical Team Ministry of Health',
        route: 'module-inacbgs-inacbgs',
    },
    {
        id: 3,
        icon: '/images/icons/files/kemkes.svg',
        name: 'RS ONLINE',
        company: 'Kementerian Kesehatan - Republik Indonesia',
        route: 'module-integrasi-sistem-rs-online',
    },
    {
        id: 4,
        icon: '/images/icons/files/kemkes.svg',
        name: 'SISRUTE',
        company: 'Kementerian Kesehatan - Republik Indonesia',
        route: 'module-integrasi-sistem-sisrute',
    },
    {
        id: 5,
        icon: '/images/icons/files/kemkes.svg',
        name: 'SIRANAP',
        company: 'Kementerian Kesehatan - Republik Indonesia',
        route: 'module-integrasi-sistem-siranap',
    },
    {
        id: 6,
        icon: '/images/icons/files/satusehat-1.svg',
        name: 'SATUSEHAT',
        company: 'Kementerian Kesehatan - Republik Indonesia',
        route: 'module-integrasi-sistem-satusehat-satset-dashboard',
    },
    {
        id: 7,
        icon: '/images/icons/files/no-bg.svg',
        name: 'LIS',
        company: 'Laboratorium Information System',
        route: 'module-dashboard-laboratorium',
    },
    {
        id: 8,
        icon: '/images/icons/files/no-bg.svg',
        name: 'RIS PACS',
        company: 'Radiology Information System & Picture Archiving and Communication System',
        route: 'module-dashboard-radiologi',
    },
]
const filters = ref('')
const filteredData = computed(() => {
    if (!filters.value) {
        return files
    } else {
        return files.filter((item) => {
            return (
                item.name.match(new RegExp(filters.value, 'i')) ||
                item.company.match(new RegExp(filters.value, 'i'))
            )
        })
    }
})

function goTo(data) {
    router.push({
      name: data.route,
    })
}

</script>
