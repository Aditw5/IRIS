<template>
    <div v-if="pasien.noregistrasi == undefined" :class="[isaktif == true ? 'block-header' : 'block-header nonaktif']">
        <div class="left">
            <div class="current-user">
                <VAvatar class="avatar-wrapper is-warning" :picture="pasien.jeniskelamin ==
                    'PEREMPUAN' ? '/images/avatars/svg/vuero-4.svg' : '/images/avatars/svg/vuero-1.svg'" squared />
                <h3>{{ pasien.namapasien }}</h3>
            </div>
        </div>
        <div class="center">
            <div class="columns">
                <div class="column">
                    <h4 class="block-heading">No Rekam Medis</h4>
                    <p class="block-text"> {{ pasien.nocm != null ? pasien.nocm : '-' }}</p>
                    <h4 class="block-heading">Tgl Lahir </h4>
                    <p class="block-text"> {{ pasien.tgllahir ? pasien.tgllahir : '-' }}</p>
                </div>
                <div class="column">
                    <h4 class="block-heading">NIK </h4>
                    <p class="block-text"> {{ pasien.noidentitas ? pasien.noidentitas : '-' }}</p>
                    <h4 class="block-heading">Kelamin</h4>
                    <p class="block-text"> {{ pasien.jeniskelamin ? pasien.jeniskelamin : '-' }}</p>
                </div>

            </div>
        </div>
        <div class="right">
            <div class="columns">
                <div class="column">
                    <h4 class="block-heading">No HP</h4>
                    <p class="block-text">{{ pasien.nohp }}</p>
                    <h4 class="block-heading">Umur</h4>
                    <VTag color="purple" :label="pasien.umur ? pasien.umur : '-'" />
                </div>
                <div class="column">
                    <h4 class="block-heading">Alamat</h4>
                    <p class="block-text">{{ pasien.alamatlengkap ? pasien.alamatlengkap : '-' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
    <div v-else :class="[isaktif == true ? 'block-header' : 'block-header nonaktif']">
        <div class="left">
            <div class="current-user">
                <VAvatar class="is-head avatar-wrapper is-warning" :picture="pasien.jeniskelamin ==
                    'PEREMPUAN' ? '/images/avatars/svg/vuero-4.svg' : '/images/avatars/svg/vuero-1.svg'" squared />
                <h3>{{ pasien.namapasien }}</h3>
                <p class="block-text">{{ pasien.nocm }}</p>
            </div>
        </div>
        <div class="center">
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
        <div class="right">
            <div class="columns">
                <div class="column">
                    <h4 class="block-heading">Ruangan </h4>
                    <p class="block-text">{{ pasien.namaruangan }}</p>
                    <h4 class="block-heading">Tgl Registrasi</h4>
                    <p class="block-text">{{ pasien.tglregistrasi }} </p>
                    <div v-if="pasien.rekanan != 'Diri Sendiri'">
                        <h4 class="block-heading">Kelas</h4>
                        <p class="block-text">{{ pasien.kelas }} </p>
                    </div>
                </div>
                <div class="column">
                    <h4 class="block-heading">Pembiayaan</h4>
                    <p class="block-text">{{ pasien.kelompokpasien }}</p>
                    <div v-if="pasien.rekanan != 'Diri Sendiri' && ''">
                        <h4 class="block-heading">Di Tanggung</h4>
                        <p class="block-text">{{ pasien.rekanan }} </p>
                    </div>
                    <div v-if="pasien.nmprovider != ''">
                        <h4 class="block-heading">Asuransi</h4>
                        <p class="block-text">{{ pasien.nmprovider }} </p>
                    </div>
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
        registrasi?: any
    }>(),
    {
        pasien: {},
        isaktif: true,
        registrasi: {}
    }
)
// export default {
//     props: {
//         pasien: {}
//     },
//     setup(props: any) {
//     }
// }
</script>
