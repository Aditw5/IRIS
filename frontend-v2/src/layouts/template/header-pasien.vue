
<template>
    <VCard custom="card-green">
        <div class="columns is-multiline">
            <div class="column is-2">
                <div class="current-user">
                    <VAvatar size="large" :picture="pasien.jeniskelamin ==
                    'PEREMPUAN' ? '/images/avatars/svg/vuero-4.svg' : '/images/avatars/svg/vuero-1.svg'" squared />
                    <h3>{{  pasien.namapasien  }}</h3>
                </div>
            </div>
            <div class="column is-10">
                <div class="columns is-multiline">
                    <div class="column is-3">
                        <VField>
                            <VLabelLarge>No Rekam Medis</VLabelLarge>
                            <VLabelLarge class="block-text"> {{  pasien.nocm  }}</VLabelLarge>
                        </VField>
                    </div>
                    <div class="column is-3">
                        <VField>
                            <VLabelLarge>Tgl Lahir</VLabelLarge>
                            <VLabelLarge class="block-text"> {{  pasien.tgllahir  }}</VLabelLarge>
                        </VField>
                    </div>
                    <div class="column is-3">
                        <VField>
                            <VLabelLarge>NIK</VLabelLarge>
                            <VLabelLarge class="block-text"> {{  pasien.noidentitas  }}</VLabelLarge>
                        </VField>
                    </div>
                    <div class="column is-3">
                        <VField>
                            <VLabelLarge>Kelamin</VLabelLarge>
                            <VLabelLarge class="block-text"> {{  pasien.jeniskelamin  }}</VLabelLarge>
                        </VField>
                    </div>
                    <div class="column is-3">
                        <VField>
                            <VLabelLarge>No HP</VLabelLarge>
                            <VLabelLarge class="block-text"> {{  pasien.nohp  }}</VLabelLarge>
                        </VField>
                    </div>
                    <div class="column is-3">
                        <VField>
                            <VLabelLarge>Alamat</VLabelLarge>
                            <VLabelLarge class="block-text"> {{  pasien.alamatlengkap  }}</VLabelLarge>
                        </VField>
                    </div>
                    <div class="column is-3">
                        <VField>
                            <VLabelLarge>Umur</VLabelLarge>
                            <VTag color="purple" :label="pasien.umur" />
                        </VField>
                    </div>
                </div>
            </div>
        </div>
    </VCard>
</template>
<script setup lang="ts">
import { ref } from 'vue'
const pasien: any = ref({})
const isLoadingPasien: any = ref(false)
</script>
<style lang="scss">
.current-user h3 {
    font-family: var(--font-alt);
    font-weight: 700;
    font-size: 1.2rem;
    color: var(--white);
    line-height: 1.2;
    width: 160px;
    white-space: nowrap;
    overflow: hidden !important;
    text-overflow: ellipsis;
}

.current-user .v-avatar {
    margin-bottom: 1rem;
}

.card-green {
    .current-user {
        padding: 15px;
        align-items: center;
        text-align: center;
        justify-content: center;
        /* display: flex; */
    }
}
</style>