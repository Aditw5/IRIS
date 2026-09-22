

<template>
  <div class="lifestyle-dashboard lifestyle-dashboard-v4">
    <div class="columns">
      <div class="column is-8">
        <div class="columns is-multiline">
          <!--Header-->
          <div class="column is-12">
            <div class="illustration-header-2">
              <div class="header-image">
                <img
                  src="/@src/assets/illustrations/dashboards/lifestyle/reading.svg"
                  alt=""
                />
              </div>
              <div class="header-meta">
                <h3> {{ item.namaproduk }}  </h3>
                <p> 
                  Kode : {{ item.kodebmn }} 
                </p>
                <p>
                  {{ item.deskripsiproduk}}
                </p>
                <p> {{ item.keterangan }} </p>
                
              </div>
            </div>
          </div>

          <!--Content-->
          <div class="column is-7">
            <div class="writing-stats">
              <!--Stat-->
              <div class="writing-stat">
                <span>Stok Produk</span>
                <span class="dark-inverted">209</span>
              </div>
              <!--Stat-->
              <div class="writing-stat">
                <span>Harga Terbaru</span>
                <span class="dark-inverted">863</span>
              </div>
              <!--Stat-->
              <div class="writing-stat">
                <span>Pemakaian</span>
                <span class="dark-inverted">386</span>
              </div>
            </div>

            <div class="featured-authors">
              <!--Header-->
              <div class="featured-authors-header">
                <h3 class="dark-inverted">Distribusi Produk</h3>
                <a class="action-link" tabindex="0">Lihat Data</a>
              </div>

              <div class="featured-authors-list">
                <!--Item-->
                <div class="featured-authors-item">
                  <VBlock title="Ruangan" subtitle="Software Engineer" center>
                    <template #icon>
                      <VAvatar picture="/images/avatars/svg/room.svg" squared />
                    </template>
                  </VBlock>
                </div>
                <!--Item-->
                <div class="featured-authors-item">
                  <VBlock title="Elizabeth F." subtitle="Web Developer" center>
                    <template #icon>
                      <VAvatar picture="/demo/avatars/21.jpg" squared />
                    </template>
                  </VBlock>
                </div>
                <!--Item-->
                <div class="featured-authors-item">
                  <VBlock title="Jonathan K." subtitle="UI/UX Designer" center>
                    <template #icon>
                      <VAvatar picture="/demo/avatars/32.jpg" squared />
                    </template>
      
                  </VBlock>
                </div>
                <!--Item-->
                <div class="featured-authors-item">
                  <VBlock title="Christie D." subtitle="Software Engineer" center>
                    <template #icon>
                      <VAvatar picture="/demo/avatars/38.jpg" squared />
                    </template>
              
                  </VBlock>
                </div>
              </div>
            </div>
          </div>

          <!--Content-->
          <div class="column is-5">
            <!--Updates-->
            <div class="updates">
              <!--Header-->
              <div class="updates-header">
                <h3 class="dark-inverted">Informasi Produk</h3>
              </div>

              <div class="updates-list">
                 <!--Tanggal Produksi-->
                 <div class="update-item is-dark-bordered-12">
                  <p>Tanggal Produksi</p>
                  <span class="dark-inverted"> {{ item.tglproduksi}} </span>
                </div>
                <!--Dekripsi Produk-->
                <div class="update-item is-dark-bordered-12">
                  <p>Bentuk Produk </p>
                  <span class="dark-inverted"> {{ item.objectbentukprodukfk }} </span>
                </div>
                <!--Spesifikasi-->
                <div class="update-item is-dark-bordered-12">
                  <p> Bahan Produk </p>
                  <span class="dark-inverted"> {{ item.objectbahanprodukfk }} </span>
                </div>
                <!--Update-->
                <div class="update-item is-dark-bordered-12">
                  <p> Type Produk </p>
                  <span class="dark-inverted"> {{ item.objecttypeprodukfk }}</span>
                </div>
                <!--Update-->
                <div class="update-item is-dark-bordered-12">
                  <p> Kekuatan </p>
                  <span class="dark-inverted"> {{ item.kekuatan }}</span>
                </div>
                 <!--Update-->
                 <div class="update-item is-dark-bordered-12">
                  <p> Merek Produk </p>
                  <span class="dark-inverted"> {{ item.objectmerkprodukfk }}</span>
                </div>
                  <!--Update-->
                  <div class="update-item is-dark-bordered-12">
                  <p> Detail Produk Obat </p>
                  <span class="dark-inverted"> {{ item.objectdetailobatfk }}</span>
                </div>
                
              </div>
            </div>
          </div>
        </div>
      </div>

      <!--Feed-->
      <div class="column is-4">
        <div class="articles-feed">
          <!--Header-->
          <div class="articles-feed-header">
            <h3 class="dark-inverted">Detail Produk</h3>
          </div>
          <!--Subheader-->
          <div class="articles-feed-subheader">
            <div class="selector">
              <button class="button is-selected">Tersedia</button>
            </div>
          </div>
          <!--List-->
          <div class="articles-feed-list">
            <div class="articles-feed-list-inner">
              <!--Item-->
              <div class="featured-authors">
              <!--Header-->
              <div class="featured-authors-header">   
              </div>

              <div class="featured-authors-list">
                <!--Item-->
                <div class="featured-authors-item">
                  <VBlock title="Produsen" subtitle="Software Engineer" center>
                    <template #icon>
                      <VAvatar picture="/demo/avatars/7.jpg" squared />
                    </template>
                  </VBlock>
                </div>
                <!--Item-->
                <div class="featured-authors-item">
                  <VBlock title="Kategori Produk" subtitle="Web Developer" center>
                    <template #icon>
                      <VAvatar picture="/demo/avatars/21.jpg" squared />
                    </template>
                  
                  </VBlock>
                </div>
                <!--Item-->
                <div class="featured-authors-item">
                  <VBlock title="Detail Jenis Produk" subtitle="UI/UX Designer" center>
                    <template #icon>
                      <VAvatar picture="/demo/avatars/32.jpg" squared />
                    </template>
                 
                  </VBlock>
                </div>
                <!--Item-->
                <div class="featured-authors-item">
                  <VBlock title="Detail Golongan Produk" subtitle="Software Engineer" center>
                    <template #icon>
                      <VAvatar picture="/demo/avatars/38.jpg" squared />
                    </template>
                 
                  </VBlock>
                </div>
                <div class="featured-authors-item">
                  <VBlock title="Detail Golongan Produk" subtitle="Software Engineer" center>
                    <template #icon>
                      <VAvatar picture="/demo/avatars/38.jpg" squared />
                    </template>
                 
                  </VBlock>
                </div>
              </div>
            </div>
            
              <!--Item-->
           
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { onceImageErrored } from '/@src/utils/via-placeholder'
import { useWindowScroll } from '@vueuse/core'
import { useApi } from '/@src/composable/useApi'
import { h, reactive, ref, computed, defineComponent, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useHead } from '@vueuse/head'
import * as H from '/@src/utils/appHelper'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import AutoComplete from 'primevue/autocomplete';
import { useToaster } from '/@src/composable/toaster'
import { useUserSession } from '/@src/stores/userSession'

useHead({
    title: 'Detail Produk - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
let ID_PRODUK = useRoute().query.id as string
let ID_PRODUK_SET = ref()
const date = ref(new Date())
const userLogin = useUserSession().getUser()
const item: any = ref({
    aktif: true
  }
)
const dataSource: any = ref([])

const { y } = useWindowScroll()
const router = useRouter()
const isStuck = computed(() => {
    return y.value > 30
})
 
async function DetailProdukByID(id: any) {
    const { data: produk } = await useApi().get(`/sysadmin/master-produk?id=${id}`)
    item.value.kdproduk = produk[0].kdproduk
    item.value.kdproduk_intern = produk[0].kdproduk_intern
    item.value.kdbarcode = produk[0].kdbarcode
    item.value.kodebmn = produk[0].kodebmn
    item.value.namaproduk = produk[0].namaproduk
    item.value.deskripsiproduk = produk[0].deskripsiproduk
    item.value.namaexternal = produk[0].namaexternal
    item.value.keterangan = produk[0].keterangan
    item.value.reportdisplay = produk[0].reportdisplay
    item.value.objectdetailjenisprodukfk = produk[0].detailjenisproduk
    item.value.objectkategoryprodukfk = produk[0].kategoryproduk
    item.value.objectjenisgenerikfk = produk[0].jenisgenerik
    item.value.objectlevelprodukfk = produk[0].levelproduk
    item.value.isprodukintern = produk[0].isprodukintern
    item.value.objectdepartemenfk = produk[0].namadepartemen //belum ada relasi
    item.value.objectbentukprodukfk = produk[0].namabentukproduk
    item.value.objectbahanprodukfk = produk[0].namabahanproduk // belum ada relasi
    item.value.objectkategoryprodukfk = produk[0].kategoryproduk
    item.value.objecttypeprodukfk = produk[0].typeproduk
    item.value.objectwarnaprodukfk = produk[0].objectwarnaprodukfk
    item.value.objectdetailobatfk = produk[0].objectdetailobatfk
    item.value.spesifikasi = produk[0].spesifikasi
    item.value.objectgolonganprodukfk = produk[0].objectgolonganprodukfk
    item.value.objectdetailgolonganprodukfk = produk[0].objectdetailgolonganprodukfk
    item.value.objectsatuanstandarfk = produk[0].objectsatuanstandarfk
    item.value.kekuatan = produk[0].kekuatan
    item.value.objectmerkprodukfk = produk[0].objectmerkprodukfk
    item.value.qtykalori = produk[0].qtykalori
    item.value.qtykarbohidrat = produk[0].qtykarbohidrat
    item.value.qtylemak = produk[0].qtylemak
    item.value.qtyporsi = produk[0].qtyporsi
    item.value.qtyprotein = produk[0].qtyprotein
    item.value.objectjenisperiksapenunjangfk = produk[0].objectjenisperiksapenunjangfk
    item.value.nilainormal = produk[0].nilainormal
    item.value.bahansamplefk = produk[0].bahansamplefk
    item.value.golongandarahfk = produk[0].golongandarahfk
    item.value.rhesusfk = produk[0].rhesusfk
    item.value.objectstatusprodukfk = produk[0].objectstatusprodukfk
    item.value.isarvdonasi = produk[0].isarvdonasi
    item.value.isnarkotika = produk[0].isnarkotika
    item.value.ispsikotropika = produk[0].ispsikotropika
    item.value.isonkologi = produk[0].isonkologi
    item.value.isoot = produk[0].isoot
    item.value.isprekusor = produk[0].isprekusor
    item.value.isvaksindonasi = produk[0].isvaksindonasi
    item.value.objectprodusenprodukfk = produk[0].objectprodusenprodukfk
    item.value.objectrekananfk = produk[0].objectrekananfk
  }

  DetailProdukByID(ID_PRODUK)

</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';

.lifestyle-dashboard-v4 {
  .illustration-header-2 {
    display: flex;
    align-items: center;
    padding: 10px;
    border-radius: 16px;
    background: var(--primary);
    font-family: var(--font);
    box-shadow: var(--primary-box-shadow);

    .header-image {
      position: relative;
      height: 175px;
      width: 320px;

      img {
        position: absolute;
        top: 0;
        left: -40px;
        display: block;
        pointer-events: none;
      }
    }

    .header-meta {
      margin-left: 0;
      padding-right: 30px;

      h3 {
        color: var(--smoke-white);
        font-family: var(--font-alt);
        font-weight: 700;
        font-size: 1.3rem;
        max-width: 280px;
      }

      p {
        font-weight: 400;
        color: var(--smoke-white-dark-2);
        margin-bottom: 16px;
        max-width: 320px;
      }

      .action-link {
        span {
          font-size: 0.8rem;
          text-transform: uppercase;
          margin-right: 6px;
        }

        i {
          font-size: 12px;
        }
      }
    }
  }

  .writing-stats {
    display: flex;
    margin-bottom: 1rem;
    margin-left: -8px;
    margin-right: -8px;

    .writing-stat {
      @include vuero-l-card;

      margin: 8px;
      width: calc(33.3% - 16px);
      padding: 12px;

      span {
        display: block;

        &:first-child {
          font-family: var(--font-alt);
          font-size: 0.8rem;
          font-weight: 500;
          text-transform: uppercase;
          margin-bottom: 5px;
          color: var(--light-text);
        }

        &:nth-child(2) {
          font-family: var(--font);
          font-weight: 700;
          font-size: 1.8rem;
          color: var(--dark-text);
        }
      }
    }
  }

  .featured-authors {
    @include vuero-l-card;

    padding: 20px;

    .featured-authors-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 30px;

      h3 {
        font-family: var(--font-alt);
        font-weight: 600;
        font-size: 1.1rem;
        color: var(--dark-text);
      }

      .action-link {
        font-size: 0.9rem;
      }
    }

    .featured-authors-list {
      .featured-authors-item {
        &:not(:last-child) {
          margin-bottom: 20px;
        }

        .media-flex-center {
          .flex-end {
            span {
              font-family: var(--font-alt);
              font-weight: 600;
              color: var(--dark-text);
            }
          }
        }
      }
    }
  }

  .updates {
    @include vuero-l-card;

    padding: 20px;
    margin-top: 8px;

    .updates-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 20px;

      h3 {
        font-family: var(--font-alt);
        font-weight: 600;
        font-size: 1.1rem;
        color: var(--dark-text);
      }

      .action-link {
        font-size: 0.9rem;
      }
    }

    .updates-list {
      .update-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
        padding-bottom: 16px;
        border-bottom: 1px solid var(--fade-grey-dark-3);

        &:last-child {
          margin-bottom: 0;
          border-bottom: none;
        }

        p {
          font-size: 0.9rem;
        }

        span {
          display: block;
          min-width: 60px;
          text-align: right;
          font-family: var(--font);
          font-weight: 600;
          font-size: 0.8rem;
          color: var(--dark-text);
        }
      }
    }
  }

  .articles-feed {
    background: var(--widget-grey);
    padding: 30px;
    border-radius: 12px;

    .articles-feed-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 20px;

      h3 {
        font-family: var(--font-alt);
        font-weight: 600;
        font-size: 1.1rem;
        color: var(--dark-text);
      }

      .action-link {
        font-size: 0.9rem;
      }
    }

    .articles-feed-subheader {
      margin-bottom: 20px;

      .selector {
        .button {
          font-size: 0.8rem;
          border-radius: 50px;
          margin-right: 4px;

          &.is-selected {
            background: var(--primary);
            color: var(--white);
            border-color: var(--primary);
            box-shadow: var(--primary-box-shadow);
          }
        }
      }
    }

    .articles-feed-list {
      .articles-feed-list-inner {
        .articles-feed-item {
          display: block;

          &:not(:last-child) {
            margin-bottom: 20px;
          }

          .featured-image {
            height: 180px;
            overflow: hidden;
            border-top-left-radius: 18px;
            border-top-right-radius: 18px;

            img {
              display: block;
              width: 100%;
              height: 100%;
              object-fit: cover;
            }
          }

          .featured-content {
            position: relative;
            padding: 25px;
            border-radius: 18px;
            background: var(--white);
            margin-top: -40px;
            z-index: 1;

            h4,
            p {
              margin-bottom: 10px;
            }

            h4 {
              font-family: var(--font-alt);
              font-size: 1rem;
              font-weight: 600;
              color: var(--dark-text);
            }

            .media-flex-center {
              .flex-meta {
                span {
                  font-size: 0.8rem;
                }
              }
            }
          }
        }
      }
    }
  }
}

.is-dark {
  .lifestyle-dashboard-v4 {
    .illustration-header-2 {
      background: var(--dark-sidebar);
      box-shadow: none;
    }

    .writing-stats {
      .writing-stat {
        @include vuero-card--dark;
      }
    }

    .updates,
    .featured-authors {
      @include vuero-card--dark;
    }

    .articles-feed {
      background: var(--dark-sidebar-light-8);

      .articles-feed-subheader {
        .selector {
          .button {
            &.is-selected {
              background: var(--primary) !important;
              border-color: var(--primary) !important;
              box-shadow: var(--primary-box-shadow) !important;
              color: var(--white) !important;
            }
          }
        }
      }

      .articles-feed-list {
        .articles-feed-list-inner {
          .articles-feed-item {
            .featured-content {
              background: var(--dark-sidebar);
            }
          }
        }
      }
    }
  }
}

@media only screen and (max-width: 767px) {
  .lifestyle-dashboard-v4 {
    .illustration-header-2 {
      flex-direction: column;
      text-align: center;

      .header-image {
        height: auto;
        width: 100%;

        img {
          position: relative;
          width: 100%;
          max-width: 260px;
          margin: 0 auto;
          top: 0;
          left: 0;
          margin-top: -34px;
        }
      }

      .header-meta {
        padding: 20px;

        > p {
          max-width: 280px;
          margin-left: auto;
          margin-right: auto;
        }
      }
    }

    .writing-stats {
      .writing-stat {
        text-align: center;
      }
    }
  }
}

@media only screen and (min-width: 768px) and (max-width: 1024px) and (orientation: portrait) {
  .lifestyle-dashboard-v4 {
    .articles-feed {
      .articles-feed-list {
        .articles-feed-list-inner {
          display: flex;
          flex-wrap: wrap;
          margin-left: -12px;
          margin-right: -12px;

          .articles-feed-item {
            width: calc(50% - 24px);
            margin: 12px;
          }
        }
      }
    }
  }
}

@media only screen and (min-width: 768px) and (max-width: 1024px) and (orientation: landscape) {
  .lifestyle-dashboard-v4 {
    .updates {
      .updates-list {
        .update-item {
          > span {
            display: none;
          }
        }
      }
    }

    .articles-feed {
      padding: 20px;
    }
  }
}
</style>
