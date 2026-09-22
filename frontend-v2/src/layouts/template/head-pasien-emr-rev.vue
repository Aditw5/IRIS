<template>
  <div class="business-dashboard company-dashboard">
  <div class="company-header is-dark-card-bordered is-dark-bg-6">
      <div class="header-item is-dark-bordered-12">
        <div class="item-inner mt-5 is-flex">
          <div class="column is-12">
            <div class="columns is-multiline">
                <VIconButton icon="feather:arrow-left" style="color: black !important!important;" light dark-outlined @click="backPage()" />
                <span class="block-text ml-3" style="color: white;"><b>Rekam Medis</b></span>
            </div>
          </div>
        </div>
      </div>
      <div class="header-item is-dark-bordered-12">
            <div class="item-inner" style="color: white">
              <div class="is-flex px-4">
                <div class="column is-12">
                    <div class="columns is-multiline">
                        <div v-if="pasien.nocm != undefined">
                            <div class="current-user" style="display: flex; margin-left: 10px; align-items: center;">
                                <div>
                                    <VAvatarIcon class="avatar-wrapper is-warning" :picture="pasien.foto" squared
                                        :badge="(pasien.jeniskelamin.toUpperCase() == 'PEREMPUAN' ? 'fas fa-venus' : 'fas fa-mars')" v-if="pasien.isfoto" />
                                    <VAvatarIcon class="avatar-wrapper is-warning" :picture="pasien.jeniskelamin!=undefined && pasien.jeniskelamin.toUpperCase() ==
                                        'PEREMPUAN' ? '/images/avatars/svg/vuero-4.svg' : '/images/avatars/svg/vuero-1.svg'" squared
                                        :badge="(pasien.jeniskelamin.toUpperCase() == 'PEREMPUAN' ? 'fas fa-venus' : 'fas fa-mars')" v-if="!pasien.isfoto" />
                                </div>
                                <div style="margin-left: 10px; color: white;">
                                    <span class="block-text" style="font-size: 14pt;"><b>{{ pasien.namapasien }} </b><VTag v-if="selectedRegistrasi.dokterBersama" color="purple" label="Rawat Bersama" class="ml-2"/></span>
                                    <div style="font-size: 12pt; color: white;" class="is-flex">
                                        <VTag color="primary" :label="pasien.umur" style="background-color: #116863;" /> <span style="font-size: 12pt" class="ml-2"> | {{ pasien.nocm }} | {{ pasien.nohp }}</span>
                                    </div>
                                    <div class="is-flex is-space-between border-blue mt-2 mb-2 column is-12" v-if="pasien.nocm != undefined" style="background: #A0E3C5; color: white; height:10% !important">
                                            <div class="column is-10">
                                                <div class="job-card-subtitle-2">
                                                <i aria-hidden="true" class="pi pi-book"></i>
                                                <p class="block-text ml-2" style="color: var(--dark-text);font-size:12px">{{ pasien.catatan ?? 'Belum ada catatan' }}</p>
                                                </div>
                                            </div>
                                            <div class="column is-2 px-0">
                                                <VIconButton icon="feather:plus" @click="openModal" color="info" circle/>
                                            </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                  </div>
                </div>
            </div>
      </div>
      <div class="header-item is-dark-bordered-12">
        <div class="item-inner px-4" style="color: white">
          <span class="block-text" style="font-size: 14pt;"><b>Info Registrasi</b></span>
          <span class="block-text" style="font-size: 11pt;">{{ selectedRegistrasi.namaruangan }} | <VTag color="info" :label="selectedRegistrasi.namakelas" style="background-color: #116863;" /></span>
          <span class="block-text" style="font-size: 11pt;"> No. Registrasi : {{ selectedRegistrasi.noregistrasi }}</span>
          <span class="block-text" style="font-size: 11pt;"> {{ selectedRegistrasi.kelompokpasien }} {{selectedRegistrasi.kelompokpasien == 'BPJS' || selectedRegistrasi.kelompokpasien == 'BPJS Non PBI ' ? ` / ${selectedRegistrasi.nosep}`: '' }}</span>
          <span class="block-text" style="font-size: 11pt;"> DPJP: {{selectedRegistrasi.dokter}}</span>
          <span class="block-text" style="font-size: 11pt;"> Dokter Pemeriksa: {{selectedRegistrasi.dokterBersama}}<span v-for="dokter in selectedRegistrasi.dokterTambahan">{{ dokter.namalengkap }}</span></span>
          <span v-if="pasien.sitb_id" class="block-text" style="font-size: 11pt;"> No. SITB : {{ pasien.sitb_id }} </span>
        </div>
      </div>
      <div class="header-item is-dark-bordered-12">
        <div class="item-inner">
          <div class="is-flex px-4">
            <div class="column is-12">
              <div class="columns is-multiline">
                <VButton rounded color="warning" class="is-pulled-left mr-2" icon="feather:user" raised bold
                    @click="openProfil">
                    Profil
                </VButton>
                <VButton rounded color="success" class="is-pulled-left mr-2" icon="feather:external-link" raised bold
                    @click="openCare" :loading="isLoadingIcare" v-if="selectedRegistrasi.kelompokpasien == 'BPJS'">
                    I-CARE
                </VButton>
                <VButton rounded outlined color="danger" class="is-pulled-left" v-tooltip-prime.top="'TOTAL BILLING '" icon="fas fa-calculator" raised bold>
                <p>{{ selectedRegistrasi.billing ? H.formatRp(selectedRegistrasi.billing, 'Rp. ') : 'Rp.0' }}</p>
                </VButton>
                </div>
            </div>
          </div>
          <!-- {{ selectedRegistrasi }} -->
          <span class="is-flex px-4 mt-2">
            <div class="column is-12">
              <div class="columns is-multiline">
                <VButtonImg v-if="selectedRegistrasi.kelompokpasien == 'BPJS' || selectedRegistrasi.kelompokpasien == 'BPJS Non PBI '" rounded outlined :color="colorPLAFON" class="is-pulled-left" icon="/images/icons/files/bpjs-no-bg.svg" raised :loading="isFlafon" @click="openHitung" bold v-tooltip-prime.top="'PLAFON BPJS'">
                    {{ selectedRegistrasi.inacbg_totalgrouper ? H.formatRp(selectedRegistrasi.inacbg_totalgrouper, 'Rp. ') : 'Rp.0' }}
                </VButtonImg>
                <VButtonImg v-if="selectedRegistrasi.kelompokpasien == 'BPJS' || selectedRegistrasi.kelompokpasien == 'BPJS Non PBI '" rounded outlined :color="colorPLAFON" class="is-pulled-left" icon="/images/icons/files/bpjs-no-bg.svg" raised :loading="isFlafon" bold v-tooltip-prime.top="'TOPUP BPJS'">
                    {{ selectedRegistrasi.inacbg_topup ? H.formatRp(selectedRegistrasi.inacbg_topup, 'Rp. ') : 'Rp.0' }}
                </VButtonImg>
                <VButton v-if="(selectedRegistrasi.kelompokpasien == 'BPJS' || selectedRegistrasi.kelompokpasien == 'BPJS Non PBI ') && selectedRegistrasi.inacbg_totalgrouper > 0 && selectedRegistrasi.inacbg_totalgrouper + selectedRegistrasi.inacbg_topup - selectedRegistrasi.billing >= 0" style="padding:10px" rounded outlined color="white" class="is-pulled-left ml-2"  v-tooltip-prime.top="'Selisih'" icon="fas fa-arrow-alt-circle-up" raised bold>
                    {{ selectedRegistrasi.inacbg_totalgrouper ? H.formatRp(+selectedRegistrasi.inacbg_totalgrouper + +selectedRegistrasi.inacbg_topup - selectedRegistrasi.billing, 'Rp. ') : 'Rp.0' }}
                </VButton>
                <VButton v-if="(selectedRegistrasi.kelompokpasien == 'BPJS' || selectedRegistrasi.kelompokpasien == 'BPJS Non PBI ') && selectedRegistrasi.inacbg_totalgrouper > 0 && selectedRegistrasi.inacbg_totalgrouper + selectedRegistrasi.inacbg_topup - selectedRegistrasi.billing < 0" style="padding:10px" rounded outlined color="danger" class="is-pulled-right ml-2"  v-tooltip-prime.top="'Selisih'" icon="fas fa-arrow-alt-circle-down" raised bold>
                    {{ selectedRegistrasi.inacbg_totalgrouper ? H.formatRp(+selectedRegistrasi.inacbg_totalgrouper + +selectedRegistrasi.inacbg_topup - selectedRegistrasi.billing, 'Rp. ') : 'Rp.0' }}
                </VButton>
              </div>
            </div>
          </span>
        </div>
      </div>
      <div class="header-item is-dark-bordered-12">
        <div class="item-inner px-4">
          <VCard class="rounded-pad" style="background-color: #B8F2E6;" v-tooltip.bubble="'Jumlah Pasien'" @click="rajalSemua">
              <div class="inner-list-item media-flex-center">
              <VIconBox rounded :color="'success'" class="h-avatar">
                  <!-- <i class="fas fa-user-check h-fs-1" aria-hidden="true"></i> -->
                  <img alt="" src="/images/simrs/icon-pasien-emr.png" style="opacity:0.3s">
              </VIconBox>
              <div class="flex-meta is-light">
                  <a href="#">{{ listPasienRJ.total ? listPasienRJ.total : 0 }}</a>
                  <!-- <span>Pasien</span> -->
              </div>
              </div>
          </VCard>
          <VCard class="rounded-pad mt-3" style="background-color: #FFA69E;" v-tooltip.bubble="'Belum Dipanggil'" @click="rajal">
              <div class="inner-list-item media-flex-center">
              <VIconBox rounded :color="'danger'" class="h-avatar">
                  <!-- <i class="fas fa-users h-fs-1" aria-hidden="true"></i> -->
                  <img alt="" src="/images/simrs/icon-antrian-emr.png">
              </VIconBox>
              <div class="flex-meta is-light">
                  <a href="#">{{ listPasienRJ.belumpanggil ? listPasienRJ.belumpanggil : 0 }}</a>
                  <!-- <span>Antrian</span> -->
              </div>
              </div>
          </VCard>
        </div>
      </div>
    </div>
  </div>

<!-- <div class="columns is-multiline">
    <div class="column is-12">
        <div class="columns is-multiline">
            <div class="column is-1">
                <VCard custom="card-green" :class="[isaktif == true ? '' : 'nonaktif']" class="panjang">
                    <div v-if="pasien.noregistrasi == undefined" >
                        <div class="column is-12">
                            <div class="columns is-multiline">
                                    <div class="column is-12 mt-2 mb-1" >
                                        <VIconButton icon="feather:arrow-left" light dark-outlined @click="backPage()" />
                                        <span class="title-emr ml-3" style="font-size: 16px;color: white;">Rekam Medis</span>
                                        <span class="block-text ml-3 mt-5" style="font-size: 10pt; color: white;"><b>Rekam Medis</b></span>
                                    </div>
                            </div>
                        </div>
                    </div>
                </VCard>
            </div>
            <div class="column is-8">
                <VCard custom="card-green"  :class="[isaktif == true ? '' : 'nonaktif']" class="panjang" style="padding: 1% !important;">
                    <div class="column is-12">
                            <div class="columns is-multiline">
                                <div class="column is-1">
                                    <div class="columns is-multiline">
                                            <div class="column is-12 mt-2 mb-1" >
                                                <VIconButton icon="feather:arrow-left" light dark-outlined @click="backPage()" />
                                            </div>
                                    </div>
                                </div>
                                <div class="column is-5" v-if="pasien.nocm != undefined">
                                    <div class="current-user" style="display: flex; align-items: center;">
                                        <div>
                                            <VAvatarIcon class="avatar-wrapper is-warning" :picture="pasien.foto" squared
                                                :badge="(pasien.jeniskelamin.toUpperCase() == 'PEREMPUAN' ? 'fas fa-venus' : 'fas fa-mars')" v-if="pasien.isfoto" />
                                            <VAvatarIcon class="avatar-wrapper is-warning" :picture="pasien.jeniskelamin!=undefined && pasien.jeniskelamin.toUpperCase() ==
                                                'PEREMPUAN' ? '/images/avatars/svg/vuero-4.svg' : '/images/avatars/svg/vuero-1.svg'" squared
                                                :badge="(pasien.jeniskelamin.toUpperCase() == 'PEREMPUAN' ? 'fas fa-venus' : 'fas fa-mars')" v-if="!pasien.isfoto" />
                                        </div>
                                        <div style="margin-left: 10px; color: white;">
                                            <span class="block-text" style="font-size: 12pt;"><b>{{ pasien.namapasien }}</b></span><br>
                                            <VTag color="primary" :label="pasien.umur" style="background-color: #116863;" /> | <span class="block-text" style="font-size:12pt;">{{ pasien.nocm }}</span> | <span class="block-text" style="font-size:12pt;">{{ pasien.nohp }}</span><br>
                                            <div class="is-flex is-space-between border-blue mt-2 mb-2 column is-12" v-if="pasien.nocm != undefined" style="background: #A0E3C5;height:10% !important">
                                                    <div class="column is-10">
                                                        <div class="job-card-subtitle-2">
                                                        <i aria-hidden="true" class="pi pi-book"></i>
                                                        <p class="block-text ml-2 mt-1-min" style="color: var(--dark-text);font-size:12px">{{ pasien.catatan ?? 'Belum ada catatan' }}</p>
                                                        </div>
                                                    </div>
                                                    <div class="column is-2 py-1 px-0">
                                                        <VIconButton icon="feather:plus" @click="openModal" color="info" raised circle  v-tooltip.bubble="'Buat Catatan'"  > </VIconButton>
                                                    </div>
                                            </div>
                                            <span class="block-text" style="font-size:12pt;">{{ selectedRegistrasi.namaruangan }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="is-flex border-blue mt-2 mb-2 column is-3" v-if="pasien.nocm != undefined" style="background: #A0E3C5;height:10% !important">
                                        <div class="column is-10 mb-2-min">
                                            <div class="job-card-subtitle-2">
                                            <i aria-hidden="true" class="pi pi-book"></i>
                                            <p class="block-text ml-2 mt-1-min" style="color: var(--dark-text);font-size:12px">{{ pasien.catatan ?? 'Belum ada catatan' }}</p>
                                            </div>
                                        </div>
                                        <div class="column is-2 py-1 px-0">
                                            <VIconButton icon="feather:plus" @click="openModal" color="info" raised circle  v-tooltip.bubble="'Buat Catatan'"  > </VIconButton>
                                        </div>
                                </div>
                                <div class="column is-4">
                                    <div style="display: flex; flex-direction: row;">
                                        <div>
                                            <h3 class="title is-5 mb-4">Info Registrasi </h3>
                                        </div>
                                            <VTag :color="'light'" style="font-size:9.5pt; margin-left:10px; margin-top:-5px;" v-if="selectedRegistrasi.nosep == undefined && selectedRegistrasi.objectkelompokpasienlastfk == '2'"><b>SEP Belum Diinput</b></VTag>
                                    </div>
                                    <div style="color: white;">
                                      <span class="block-text" >{{ selectedRegistrasi.namaruangan }}</span> <br>
                                        <span class="block-text" > No. Registrasi : {{ selectedRegistrasi.noregistrasi }}</span> <br>
                                        <span class="block-text" > {{ selectedRegistrasi.kelompokpasien }} {{selectedRegistrasi.kelompokpasien == 'BPJS' ? ` / ${selectedRegistrasi.nosep}`: '' }}</span> <br>
                                    </div>
                                </div>
                            </div>
                    </div>
                </VCard>
            </div>
            <div class="column is-4">
                <VCard custom="card-green" :class="[isaktif == true ? '' : 'nonaktif']" class="panjang" style="padding: 4% !important;">
                    <div class="list-reg-nomobile">
                        <div class="columns is-multiline " v-if="selectedRegistrasi.kelompokpasien == 'BPJS'">
                            <div class="column is-6 mt-4-min">
                                <VButton rounded color="warning" class="is-pulled-left mr-2" icon="feather:user" raised bold
                                     @click="openProfil">
                                    Profil
                                </VButton>
                                <VButton rounded color="success" class="is-pulled-left mr-2" icon="feather:external-link" raised bold
                                    @click="openCare" :loading="isLoadingIcare" v-if="selectedRegistrasi.kelompokpasien == 'BPJS'">
                                    I-CARE
                                </VButton>
                            </div>
                            <div class="column is-3 mt-4-min">
                                <VCard class="rounded-pad" style="background-color: #B8F2E6;" v-tooltip.bubble="'Jumlah Pasien'" @click="rajalSemua">
                                    <div class="inner-list-item media-flex-center">
                                    <VIconBox rounded :color="'success'" class="h-avatar">
                                        <i class="fas fa-user-check h-fs-1" aria-hidden="true"></i>
                                        <img alt="" src="/images/simrs/icon-pasien-emr.png" style="opacity:0.3s">
                                    </VIconBox>
                                    <div class="flex-meta is-light">
                                        <a href="#">{{ listPasienRJ.total ? listPasienRJ.total : 0 }}</a>
                                        <span>Pasien</span>
                                    </div>
                                    </div>
                                </VCard>
                            </div>
                            <div class="column is-3 mt-4-min">
                                <VCard class="rounded-pad" style="background-color: #FFA69E;" v-tooltip.bubble="'Belum Dipanggil'" @click="rajal">
                                    <div class="inner-list-item media-flex-center">
                                    <VIconBox rounded :color="'danger'" class="h-avatar">
                                        <i class="fas fa-users h-fs-1" aria-hidden="true"></i>
                                        <img alt="" src="/images/simrs/icon-antrian-emr.png">
                                    </VIconBox>
                                    <div class="flex-meta is-light">
                                        <a href="#">{{ listPasienRJ.belumpanggil ? listPasienRJ.belumpanggil : 0 }}</a>
                                        <span>Antrian</span>
                                    </div>
                                    </div>
                                </VCard>
                            </div>
                        </div>
                        <div class="columns is-multiline is-flex" v-if="selectedRegistrasi.kelompokpasien == 'BPJS'">
                            <div class="column is-4 mt-4-min is-pulled-left is-marginless">
                            </div>
                            <div class="column is-4 mt-4-min is-pulled-left is-marginless" v-if="selectedRegistrasi.kelompokpasien == 'BPJS'">
                              <VButton rounded outlined color="danger" class="is-pulled-left" v-tooltip-prime.top="'TOTAL BILLING '" icon="fas fa-calculator" raised bold>
                                  {{ selectedRegistrasi.billing ? H.formatRp(selectedRegistrasi.billing, 'Rp. ') : 'Rp.0' }}
                              </VButton>
                                <VButtonImg rounded outlined :color="colorPLAFON" class="is-pulled-left" icon="/images/icons/files/bpjs-no-bg.svg" raised :loading="isFlafon" @click="openHitung" bold v-tooltip-prime.top="'PLAFON BPJS'">
                                    {{ selectedRegistrasi.inacbg_totalgrouper ? H.formatRp(selectedRegistrasi.inacbg_totalgrouper, 'Rp. ') : 'Rp.0' }}
                                </VButtonImg>
                                <VButton rounded outlined color="white" class="is-pulled-left"  v-tooltip-prime.top="'Selisih'" icon="fas fa-arrow-alt-circle-up" raised bold>
                                    {{ selectedRegistrasi.inacbg_totalgrouper ? H.formatRp(selectedRegistrasi.inacbg_totalgrouper - selectedRegistrasi.billing, 'Rp. ') : 'Rp.0' }}
                                </VButton>
                                <VButton rounded outlined color="danger" class="is-pulled-right mr-2"  v-tooltip-prime.top="'Selisih'" icon="fas fa-arrow-alt-circle-down" raised bold>
                                    {{ selectedRegistrasi.inacbg_totalgrouper ? H.formatRp(selectedRegistrasi.inacbg_totalgrouper - selectedRegistrasi.billing, 'Rp. ') : 'Rp.0' }}
                                </VButton>
                            </div>
                            <div class="column is-4 mt-4-min is-pulled-left is-marginless" v-if="selectedRegistrasi.inacbg_totalgrouper && selectedRegistrasi.inacbg_totalgrouper > selectedRegistrasi.billing">
                            </div>
                            <div class="column is-4 mt-4-min is-marginless" v-if="selectedRegistrasi.inacbg_totalgrouper && selectedRegistrasi.inacbg_totalgrouper < selectedRegistrasi.billing">
                            </div>
                        </div>
                        <div class="columns is-multiline " v-if="selectedRegistrasi.kelompokpasien != 'BPJS'">
                            <div class="column is-6 mt-4-min">
                                <VButton rounded color="warning" style="width:100%" class="is-pulled-left mr-2" icon="feather:user" raised bold
                                     @click="openProfil">
                                    Profil
                                </VButton>
                                <VButton rounded color="success" class="is-pulled-left mr-2" icon="feather:external-link" raised bold
                                    @click="openCare" :loading="isLoadingIcare" v-if="selectedRegistrasi.kelompokpasien == 'BPJS'">
                                    I-CARE
                                </VButton>
                            </div>
                            <div class="column is-6 mt-4-min">
                                <VCard class="rounded-pad" style="background-color: #B8F2E6;" v-tooltip.bubble="'Jumlah Pasien'" @click="rajalSemua">
                                    <div class="inner-list-item media-flex-center">
                                    <VIconBox rounded :color="'success'" class="h-avatar">
                                        <i class="fas fa-user-check h-fs-1" aria-hidden="true"></i>
                                        <img alt="" src="/images/simrs/icon-pasien-emr.png" style="opacity:0.3s">
                                    </VIconBox>
                                    <div class="flex-meta is-light">
                                        <span style="font-size: 13pt">{{ listPasienRJ.total ? listPasienRJ.total : 0 }}</span>
                                        <span>Pasien</span>
                                    </div>
                                    </div>
                                </VCard>
                            </div>
                            <div class="column is-6 mt-4-min is-pulled-left is-marginless">
                                <VButton style="width:100%" rounded outlined color="danger" class="is-pulled-left" v-tooltip-prime.top="'TOTAL BILLING '" icon="fas fa-calculator" raised bold>
                                    {{ selectedRegistrasi.billing ? H.formatRp(selectedRegistrasi.billing, 'Rp. ') : 'Rp.0' }}
                                </VButton>
                            </div>
                            <div class="column is-6 mt-4-min">
                                <VCard class="rounded-pad" style="background-color: #FFA69E;" v-tooltip.bubble="'Belum Dipanggil'"  @click="rajal">
                                    <div class="inner-list-item media-flex-center">
                                    <VIconBox rounded :color="'danger'" class="h-avatar">
                                        <i class="fas fa-users h-fs-1" aria-hidden="true"></i>
                                        <img alt="" src="/images/simrs/icon-antrian-emr.png">
                                    </VIconBox>
                                    <div class="flex-meta is-light">
                                        <span style="font-size: 13pt">{{ listPasienRJ.belumpanggil ? listPasienRJ.belumpanggil : 0 }}</span>
                                        <span>Antrian</span>
                                    </div>
                                    </div>
                                </VCard>
                            </div>
                        </div>
                        <div class="columns is-multiline is-flex" v-if="selectedRegistrasi.kelompokpasien != 'BPJS'">
                            <div class="column is-4 mt-4-min is-pulled-left is-marginless">
                                <VButton style="padding:10px" rounded outlined color="danger" class="is-pulled-left" v-tooltip-prime.top="'TOTAL BILLING '" icon="fas fa-calculator" raised bold>
                                    {{ selectedRegistrasi.billing ? H.formatRp(selectedRegistrasi.billing, 'Rp. ') : 'Rp.0' }}
                                </VButton>
                            </div>
                            <div class="column is-4 mt-4-min is-pulled-left is-marginless" v-if="selectedRegistrasi.kelompokpasien == 'BPJS'">
                                <VButtonImg style="padding:10px" rounded outlined :color="colorPLAFON" class="is-pulled-left" icon="/images/icons/files/bpjs-no-bg.svg" raised :loading="isFlafon" @click="openHitung" bold v-tooltip-prime.top="'PLAFON BPJS'">
                                    {{ selectedRegistrasi.inacbg_totalgrouper ? H.formatRp(selectedRegistrasi.inacbg_totalgrouper, 'Rp. ') : 'Rp.0' }}
                                </VButtonImg>
                            </div>
                            <div class="column is-4 mt-4-min is-pulled-left is-marginless" v-if="selectedRegistrasi.inacbg_totalgrouper && selectedRegistrasi.inacbg_totalgrouper > selectedRegistrasi.billing">
                                <VButton style="padding:10px" rounded outlined color="white" class="is-pulled-left"  v-tooltip-prime.top="'Selisih'" icon="fas fa-arrow-alt-circle-up" raised bold>
                                    {{ selectedRegistrasi.inacbg_totalgrouper ? H.formatRp(selectedRegistrasi.inacbg_totalgrouper - selectedRegistrasi.billing, 'Rp. ') : 'Rp.0' }}
                                </VButton>
                            </div>
                            <div class="column is-4 mt-4-min is-marginless" v-if="selectedRegistrasi.inacbg_totalgrouper && selectedRegistrasi.inacbg_totalgrouper < selectedRegistrasi.billing">
                                <VButton style="padding:10px" rounded outlined color="danger" class="is-pulled-right mr-2"  v-tooltip-prime.top="'Selisih'" icon="fas fa-arrow-alt-circle-down" raised bold>
                                    {{ selectedRegistrasi.inacbg_totalgrouper ? H.formatRp(selectedRegistrasi.inacbg_totalgrouper - selectedRegistrasi.billing, 'Rp. ') : 'Rp.0' }}
                                </VButton>
                            </div>
                        </div>
                    </div>
                </VCard>
            </div>
        </div>
    </div>
</div> -->
</template>

<script setup lang="ts">
import * as H from '/@src/utils/appHelper'
const backPage = () => {
  window.history.back()
}
const props = withDefaults(
    defineProps<{
        isaktif?: boolean
        pasien?: any
        selectedRegistrasi?: any
        listPasienRJ?: any
    }>(),
    {
        selectedRegistrasi: {},
        pasien: {},
        listPasienRJ: {},
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

const openProfil = () => {
  emits('open-profil');
};

const openCare = () => {
  emits('open-care');
};

const openHitung = () => {
  emits('open-hitung');
};

const rajal = () => {
  emits('open-rajal');
};

const rajalSemua = () => {
  emits('open-rajal-semua');
};


</script>
<style lang="scss">
.h-avatar {
  width: 30px;
  min-width: 30px;
  height: 30px;
}
.v-icon.is-success.h-avatar {
  background: #164239;
}

.v-icon.is-warning.h-avatar {
  background: #4E4425;
}

.v-icon.is-danger.h-avatar {
  background: #380D09;
}

.rounded-pad {
  padding: 0;
  border-radius: 20px;
}

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
    .list-reg-mobile {
    display: unset !important;
    }

    .list-reg-nomobile {
    display: none !important;
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

.company-dashboard {
  .company-header {
    display: flex;
    padding: 20px;
    background: var(--primary);
    border: 1px solid var(--fade-grey-dark-3);
    border-radius: var(--radius-large);
    margin-bottom: 1.5rem;

    .header-item {
      min-width: 5%;
      flex-grow: 1;
      border-right: 1px solid var(--fade-grey-dark-3);

      &:last-child {
        border-right: none;
      }

      // .item-inner {
      //   // text-align: center;

      //   .lnil,
      //   .lnir {
      //     font-size: 1.8rem;
      //     // margin-bottom: 6px;
      //     // color: var(--white);
      //   }

        span {
          display: block;
          font-family: var(--font);
          // font-weight: 600;
          // font-size: 1.6rem;
          // color: var(--white);
        }

      //   p {
      //     font-family: var(--font-alt);
      //     font-size: 0.95rem;
      //   }
      // }
    }
  }
}

</style>
