<template>
    <div class="columns is-multiline">
        <div class="column is-4">
            <div v-if="pasien.noregistrasi == undefined"
                :class="[isaktif == true ? 'block-header' : 'block-header nonaktif']" style=" height:200px !important">
                <div class="left">
                    <div class="current-user">
                        <VAvatarIcon class="avatar-wrapper is-warning" :picture="pasien.jeniskelamin.toUpperCase() ==
                            'PEREMPUAN' ? '/images/avatars/svg/vuero-4.svg' : '/images/avatars/svg/vuero-1.svg'" squared
                            :badge="(pasien.jeniskelamin.toUpperCase() == 'PEREMPUAN' ? 'fas fa-venus' : 'fas fa-mars')" />
                        <VTag color="primary" :label="pasien.umur" style=" background-color: #116863;" />
                    </div>
                </div>
                <div class="right">
                    <h3 class="emr">{{ pasien.namapasien }}</h3>
                    <div class="job-card-subtitle-2">
                        <i class="fas fa-notes-medical" aria-hidden="true"></i>
                        <span class="block-text">{{ pasien.noidentitas }}</span>
                    </div>
                    <div class="job-card-subtitle-2">
                        <i class="iconify" data-icon="feather:activity" aria-hidden="true"></i>
                        <span class="block-text">{{ pasien.nocm }}</span>
                    </div>
                    <div class="job-card-subtitle-2">
                        <i class="iconify" data-icon="feather:map-pin" aria-hidden="true"></i>
                        <span class="block-text">{{ pasien.tempatlahir + ", " + pasien.tgllahir }}</span>
                    </div>
                    <div class="job-card-subtitle-2">
                        <i class="iconify" data-icon="feather:phone" aria-hidden="true"></i>
                        <span class="block-text">{{ pasien.nohp }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="column is-8">

            <VCard custom="card-green" :class="[isaktif == true ? '' : 'nonaktif']" style=" height:200px !important">
                <h3 class="title is-5 mb-4">Info Registrasi</h3>
                <div class="columns is-multiline" v-if="pasien.registrasi == undefined">
                    <div class="column is-12">
                        <VField>
                            <VPlaceload height="15px" />
                            <VPlaceload height="20px" class="mt-2" />
                        </VField>
                    </div>
                    <div class="column is-12">
                        <VField>
                            <VPlaceload height="15px" />
                            <VPlaceload height="20px" class="mt-2" />
                        </VField>
                    </div>
                </div>
                <div class="columns is-multiline" v-if="pasien.registrasi != undefined">
                    <div class="column is-4">
                        <VField>
                            <VLabelText>No Registrasi</VLabelText>
                            <VLabel>
                                <i aria-hidden="true" class="bulet fas fa-circle"></i>
                                {{ pasien.registrasi.noregistrasi }}
                            </VLabel>

                        </VField>
                    </div>

                    <div class="column is-4">
                        <VField>
                            <VLabelText>Tgl Masuk</VLabelText>
                            <VLabel>
                                <i aria-hidden="true" class="bulet fas fa-circle"></i>
                                {{ pasien.registrasi.tglregistrasi }}
                            </VLabel>
                        </VField>
                    </div>
                    <div class="column is-4">
                        <VField>
                            <VLabelText>Ruangan</VLabelText>
                            <VLabel>
                                <i aria-hidden="true" class="bulet fas fa-circle"></i>
                                {{ pasien.registrasi.namaruangan }}
                            </VLabel>
                        </VField>
                    </div>
                    <div class="column is-4">
                        <VField>
                            <VLabelText>Kelas</VLabelText>
                            <VLabel>
                                <i aria-hidden="true" class="bulet fas fa-circle"></i>
                                {{ pasien.registrasi.namakelas }}
                            </VLabel>
                        </VField>
                    </div>
                    <div class="column is-4">
                        <VField>
                            <VLabelText>Tipe Pembiayaan</VLabelText>
                            <VLabel>
                                <i aria-hidden="true" class="bulet fas fa-circle"></i>
                                {{ pasien.registrasi.kelompokpasien + ' / ' + pasien.registrasi.namarekanan }}
                            </VLabel>
                        </VField>
                    </div>
                    <div class="column is-4">
                        <VField>
                            <VLabelText>DPJP </VLabelText>
                            <VLabel>
                                <i aria-hidden="true" class="bulet fas fa-circle"></i>
                                {{ pasien.registrasi.dokter }}
                            </VLabel>
                        </VField>
                    </div>
                </div>
            </VCard>
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
console.log(props.pasien)
// export default {
//     props: {
//         pasien: {}
//     },
//     setup(props: any) {
//     }
// }
</script>
<style lang="scss">
.hr-dashboard .block-header {
    display: flex;
    border-radius: 16px;
    padding: 32px !important;
    background: #1f8658 !important;
    font-family: var(--font);
    box-shadow: var(--primary-box-shadow);
}

.is-dark {
    .hr-dashboard .block-header {
        background: var(--dark-sidebar) !important;
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
.v-avatar .avatar.is-squared {
    position: relative;
    display: block;
    width: 62px;
    height: 62px;
    border-radius: var(--radius-rounded);
    z-index: 2;
    max-width: 100%;
}

.v-avatar .avatar.is-squared {
    position: relative;
    display: block;
    width: 62px;
    height: 62px;
    border-radius: var(--radius-rounded);
    z-index: 2;
    
    margin: 4px;
    max-width: 100%;
}

.v-avatar .avatar.is-squared {
    border-radius: 10px !important;
}
.hr-dashboard .block-header .left .current-user .v-avatar {
    margin-bottom: 1rem;
}
.hr-dashboard .block-header .left {
    width: 20%;
}


.hr-dashboard .block-header .left {
    display: flex;
    justify-content: center;
    align-items: center;
}

.hr-dashboard .block-header .left {
    width: 20%;
    padding-left: 20px;
}
.hr-dashboard .block-header {
    display: flex;
    border-radius: 16px;
    padding: 32px !important;
    background: #1f8658 !important;
    font-family: var(--font);
    box-shadow: var(--primary-box-shadow);
}
.avatar-wrapper:after {
    content: "";
    position: absolute;
    top: calc(50% - 12px);
    left: calc(50% - 12px);
    height: 24px;
    width: 24px;
    border-radius: var(--radius-rounded);
    background: var(--white);
    animation: wave 1.6s infinite;
    animation-duration: 2s;
    transform-origin: center center;
    z-index: 0;
}

.card-green .field > label {
    color: var(--white) !important;
}

.card-green .field > label {
    color: var(--white) !important;
}
.is-dark{

    .field > label {
    width: 250px;
}

.field > label {
    text-overflow: ellipsis;
    overflow: hidden;
    width: 160px;
    height: 1.2rem;
    white-space: nowrap;
}

.field > label {
    font-family: var(--font);
    font-size: 0.9rem;
    color: var(--light-text) !important;
    font-weight: 400;
}
}

</style>