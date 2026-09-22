<template>
    <div v-if="pasien.noregistrasi == undefined" :class="[isaktif == true ? 'block-header info' : 'block-header info nonaktif']"
        >
        <div class="is-flex">
          <div class="left" v-if="pasien.nocm != undefined" >
              <div class="current-user">
                  <VAvatarIcon class="avatar-wrapper is-warning" :picture="pasien.foto" squared
                      :badge="(pasien.jeniskelamin.toUpperCase() == 'PEREMPUAN' ? 'fas fa-venus' : 'fas fa-mars')" v-if="pasien.isfoto" />
                  <VAvatarIcon class="avatar-wrapper is-warning" :picture="pasien.jeniskelamin!=undefined && pasien.jeniskelamin.toUpperCase() ==
                      'PEREMPUAN' ? '/images/avatars/svg/vuero-4.svg' : '/images/avatars/svg/vuero-1.svg'" squared
                      :badge="(pasien.jeniskelamin.toUpperCase() == 'PEREMPUAN' ? 'fas fa-venus' : 'fas fa-mars')" v-if="!pasien.isfoto" />
                  <VTag color="primary" :label="pasien.umur" style=" background-color: #116863;" />
              </div>
          </div>
          <div class="right" style="padding:0 0 0 30px" v-if="pasien.nocm != undefined">
              <div class="job-card-subtitle-2">
                 <i aria-hidden="true"></i>
                  <span class="block-text" style="font-size:10.5pt;"><b>{{ pasien.namapasien }}</b></span>
              </div>
              <div class="job-card-subtitle-2">
                  <i class="iconify" data-icon="feather:credit-card"  aria-hidden="true"></i>
                  <span class="block-text" style="font-size:8.5pt;">{{ pasien.noidentitas }}</span>
              </div>
              <div class="job-card-subtitle-2">
                  <i class="iconify" data-icon="feather:activity" aria-hidden="true"></i>
                  <span class="block-text" style="font-size:8.5pt;">{{ pasien.nocm }}</span>
              </div>
              <div class="job-card-subtitle-2">
                  <i class="iconify" data-icon="feather:map-pin" aria-hidden="true"></i>
                  <span class="block-text" style="font-size:8.5pt;">{{ pasien.tempatlahir + ", " + pasien.tgllahir }}</span>
              </div>
              <div class="job-card-subtitle-2">
                  <i class="iconify" data-icon="feather:phone" aria-hidden="true"></i>
                  <span class="block-text" style="font-size:8.5pt;">{{ pasien.nohp }}</span>
              </div>
          </div>
        </div>
        
        <div class="is-flex border-blue mt-1 mb-2" v-if="pasien.nocm != undefined" style="background: #A0E3C5;">
          <div class="column is-10 mb-2-min">
            <div class="job-card-subtitle-2" style="justify-content: left;">
            <i aria-hidden="true" class="pi pi-book"></i>
            <p class="block-text  mt-1-min" style="color: var(--dark-text);">{{ pasien.catatan ?? 'Belum ada catatan' }}</p>
            </div>
          </div>
          <div class="column is-2 py-1 px-0">
              <VIconButton icon="feather:plus" @click="openModal" color="info" raised circle  v-tooltip.bubble="'Buat Catatan'"  > </VIconButton>
          </div>
        </div>
    </div>
    <div v-else :class="[isaktif == true ? 'block-header' : 'block-header nonaktif']">
        <div  class="left" v-if="pasien.nocm != undefined">
            <div class="current-user">
                <VAvatar class="is-head avatar-wrapper is-warning" :picture="pasien.jeniskelamin.toUpperCase() ==
                    'PEREMPUAN' ? '/images/avatars/svg/vuero-4.svg' : '/images/avatars/svg/vuero-1.svg'" squared />
                <h3>{{ pasien.namapasien }}</h3>
                <p class="block-text">{{ pasien.nocm }}</p>
            </div>
        </div>
        <div class="center" v-if="pasien.nocm != undefined">
            <div class="columns">
                <div class="column">
                    <h4 class="block-heading">NIK</h4>
                    <p class="block-text"> {{ pasien.noidentitas }}</p>
                    <h4 class="block-heading">Umur </h4>
                    <p class="block-text"> {{ pasien.umur }}</p>
                </div>
                <div class="column">
                    <h4 class="block-heading">No HP </h4>
                    <p class="block-text"> {{ pasien.nohp }}</p>
                    <h4 class="block-heading">Kelamin</h4>
                    <p class="block-text"> {{ pasien.jeniskelamin }}</p>
                </div>

            </div>
        </div>
        <div class="right" v-if="pasien.nocm != undefined">
            <div class="columns">
                <div class="column">
                    <h4 class="block-heading">Ruangan </h4>
                    <p class="block-text">{{ pasien.namaruangan }}</p>
                    <h4 class="block-heading">Tgl Registrasi</h4>
                    <p class="block-text">{{ pasien.tglregistrasi }} </p>
                </div>
                <div class="column">
                    <h4 class="block-heading">Pembiayaan</h4>
                    <p class="block-text">{{ pasien.kelompokpasien }}</p>
                    <h4 class="block-heading">Status</h4>
                    <VTag color="warning" :label="pasien.status" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">

const props = withDefaults(
    defineProps<{
        isaktif?: boolean
        pasien?: any
    }>(),
    {
        pasien: {},
        isaktif: true
    }
)
// console.log(props.pasien)
// export default {
//     props: {
//         pasien: {}
//     },
//     setup(props: any) {
//     }
// }
const emits = defineEmits();

const openModal = () => {
  emits('open-modal');
};


</script>
<style lang="scss">
.info {
  display :block !important;
  align-items:center !important;
}
.hr-dashboard .block-header {
    display: flex;
    border-radius: 16px;
    padding: 32px !important;
    // background: #1f8658 !important;
    background: var(--primary);
    font-family: var(--font);
    box-shadow: var(--primary-box-shadow);
}

.is-dark {
    .hr-dashboard .block-header {
        background: var(--dark-sidebar)!important;
    }
}

.hr-dashboard .block-header .left {
    width: 30%;
    padding-left: 20px;
}

.hr-dashboard .block-header .right {
    width: 70% !important;
    padding-left: 40px;
}

.badge-emr {
    padding: 9px 12px 10px 11px;
    border-radius: 50%;
    background: #116863;
    color: white;
    height: 30px;
    width: 30px;
}

h3.emr {

    font-family: var(--font-alt);
    font-weight: 700;
    font-size: 1.3rem;
    color: var(--white);
    line-height: 1.5;
    width: 200px !important;
    white-space: nowrap;
    overflow: hidden !important;
    text-overflow: ellipsis;
}
@media (max-width: 1144px) {
    h3.emr {
        width: 140px !important;
    }
}
.job-card-subtitle-2 {
    color: var(--subtitle-color);
    font-family: var(--font);
    font-size: 0.95rem;
    line-height: 1.6em;
    margin-top: 0;
    display: flex;
    justify-content: center;
    align-items: center;
}

.job-card-subtitle-2 i {
    color: white
}

.job-card-subtitle-2 svg {
    color: white
}

.hr-dashboard .block-header .block-text {
    margin-bottom: 0 !important;
    margin-left: 10px;
    width: 200px !important;
}

.hr-dashboard .block-header {
  height: 200px;
}
.border-blue{
  border: 1px solid var(--fade-grey-dark-3);
  border-radius :16px;
  padding :0 10px 0 10px;
  justify-content :center;
  align-items:center;
}
@media only screen and (max-width: 767px) {
  .hr-dashboard .block-header {
    flex-direction: column;
    padding: 30px;
    height: 100% !important;
    overflow: auto;
    width: 100%;
    margin-top: 20px;
  }
  h3.emr {
    width: 100%;
  }
}

</style>
