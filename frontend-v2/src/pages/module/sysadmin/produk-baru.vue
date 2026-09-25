<template>
    <div class="page-content-inner">
        <div class="is-navbar">
            <div class="form-layout">
                <div class="form-outer">
                    <div :class="[isStuck && 'is-stuck']" class="form-header stuck-header">
                        <div class="form-header-inner">
                            <div class="left">
                                <h3>Alat</h3>
                            </div>
                            <div class="right">
                                <div class="buttons">
                                    <VButton type="button" icon="lnir lnir-arrow-left rem-100"
                                        color="info" outlined @click="kembali">
                                        Cancel
                                    </VButton>
                                    <VButton type="button" icon="feather:save" :loading="isLoading" color="info"
                                        raised @click="saveProduk()" v-if="!isRegistrasi"> Save
                                    </VButton>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-body">
                        <!--Fieldset-->
                        <div class="form-fieldset">
                            <div class="columns is-multiline">
                                <div class="column is-4">
                                    <VField>
                                        <VLabel class="required-field">Nama Alat</VLabel>
                                        <VControl icon="feather:briefcase">
                                            <VInput type="text" v-model="item.namaproduk" placeholder="Nama Alat"
                                                class="is-rounded_Z is-uppercase" />
                                        </VControl>
                                    </VField>
                                </div>
                                <div class="column is-4">
                                    <VField>
                                        <VLabel class="required-field">Merk</VLabel>
                                        <VControl icon="feather:bookmark">
                                            <VInput type="text" v-model="item.namamerk" placeholder="Nama Alat"
                                                class="is-rounded_Z is-uppercase" />
                                        </VControl>
                                    </VField>
                                </div>
                                <div class="column is-4">
                                    <VField>
                                        <VLabel class="required-field">Tipe</VLabel>
                                        <VControl icon="feather:bookmark">
                                            <VInput type="text" v-model="item.namatipe" placeholder="Nama Alat"
                                                class="is-rounded_Z is-uppercase" />
                                        </VControl>
                                    </VField>
                                </div>
                                <div class="column is-4">
                                    <VField>
                                        <VLabel class="required-field">Serial Number</VLabel>
                                        <VControl icon="feather:bookmark">
                                            <VInput type="text" v-model="item.namaserialnumber" placeholder="Nama Alat"
                                                class="is-rounded_Z is-uppercase" />
                                        </VControl>
                                    </VField>
                                </div>
                                <div class="column is-4">
                                    <VField>
                                        <VLabel>Alat Milik</VLabel>
                                        <VControl>
                                            <AutoComplete v-model="item.mitrafk" :suggestions="d_mitra"
                                                @complete="fetchMitra($event)" :optionLabel="'label'" :dropdown="true"
                                                :minLength="3" class="is-input" :appendTo="'body'"
                                                :loadingIcon="'pi pi-spinner'" :field="'label'"
                                                placeholder="ketik untuk mencari..." />
                                        </VControl>
                                    </VField>
                                </div>
                                <div v-if="item.namaproduk" class="column is-12 fieldset-heading mt-2"
                                    style="text-align: center;">
                                    <h4 style="text-align: center;">
                                        Foto Alat <span class="photo-required">*</span>
                                    </h4>
                                </div>
                                <div class="column is-12" v-if="item.namaproduk">
                                    <div v-if="photoPreviewUrl" class="product-photo-preview">
                                        <img :src="photoPreviewUrl" alt="Foto alat"
                                            @error="handlePhotoPreviewError" />
                                        <div class="product-photo-preview-copy">
                                            <span>{{ isFile(fileMitra) ? 'Foto baru' : 'Foto tersimpan' }}</span>
                                            <strong>{{ photoDisplayName }}</strong>
                                            <small>
                                                {{ isFile(fileMitra)
                                                    ? 'Foto ini akan digunakan setelah data disimpan.'
                                                    : 'Foto sebelumnya tetap digunakan jika tidak diganti.' }}
                                            </small>
                                        </div>
                                    </div>
                                    <div v-else class="product-photo-empty">
                                        <i aria-hidden="true" class="iconify" data-icon="feather:image"></i>
                                        <div>
                                            <strong>Foto alat belum tersedia</strong>
                                            <span>Foto wajib dipilih atau diambil sebelum data disimpan.</span>
                                        </div>
                                    </div>

                                    <FileUpload mode="advanced" name="demo"
                                        accept="image/jpeg,image/jpg,image/png,image/webp" :maxFileSize="10000000"
                                        outlined :showUploadButton="false" :showCancelButton="false"
                                        :invalidFileTypeMessage="'{0}: File harus berupa JPG, JPEG, PNG, atau WEBP.'"
                                        :invalidFileSizeMessage="'Ukuran maksimal berkas adalah {1}'"
                                        :chooseLabel="photoPreviewUrl ? 'Ganti Foto' : 'Pilih Foto'"
                                        @select="onSelect($event)" @clear="clearSelectedPhoto"
                                        @remove="clearSelectedPhoto" class="is-rounded w-100 product-photo-upload" />

                                    <div class="product-photo-actions">
                                        <VButton type="button" icon="feather:camera" color="info" raised
                                            @click="openCamera">
                                            Buka Kamera
                                        </VButton>
                                        <VButton type="button" v-if="photoPreviewUrl" icon="feather:eye"
                                            color="info" outlined @click="previewFile">
                                            Lihat Foto
                                        </VButton>
                                        <VButton type="button" v-if="isCameraActive" icon="feather:aperture"
                                            color="info" raised @click="takePhoto">
                                            Ambil Foto
                                        </VButton>
                                    </div>

                                    <video ref="video" width="500" height="300" autoplay style="display: none;"></video>
                                    <canvas ref="canvas" style="display: none;"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { useWindowScroll } from '@vueuse/core'
import { useApi } from '/@src/composable/useApi'
import { h, reactive, onUnmounted, ref, computed, onMounted, defineComponent, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useHead } from '@vueuse/head'
import * as H from '/@src/utils/appHelper'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import AutoComplete from 'primevue/autocomplete';
import { useToaster } from '/@src/composable/toaster'
import FileUpload from 'primevue/fileupload';
import { resolvePublicFileUrl } from '/@src/utils/publicFileUrl'

useHead({
    title: 'Master Alat - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
let ID_PRODUK = useRoute().query.id as string
let ID_PRODUK_SET = ref()
const date = ref(new Date())
const item: any = ref([])
const dataSource: any = ref([])
let isLoading = ref(false)
let isRegistrasi = ref(false)
const { y } = useWindowScroll()
const router = useRouter()
const isStuck = computed(() => {
    return y.value > 30
})
const fileMitra: any = ref()
const video: any = ref(null);
const canvas: any = ref(null);
const isCameraActive = ref(false);
const d_mitra = ref([])
const selectedPhotoPreviewUrl = ref('')
const photoPreviewError = ref(false)

const isFile = (obj: any): obj is File => {
    return obj instanceof File ||
        (obj && typeof obj === 'object' && 'name' in obj && 'size' in obj && 'type' in obj);
};

const existingPhotoUrl = computed(() => {
    const photo = String(item.value?.gambaralat || '').trim()
    if (!photo) return ''
    return resolvePublicFileUrl(photo, 'produk')
})

const photoPreviewUrl = computed(() => {
    if (photoPreviewError.value) return ''
    return selectedPhotoPreviewUrl.value || existingPhotoUrl.value
})

const photoDisplayName = computed(() => {
    if (isFile(fileMitra.value)) return fileMitra.value.name
    return String(item.value?.gambaralat || 'Foto alat')
})

const hasRequiredPhoto = computed(() => {
    return !photoPreviewError.value &&
        (isFile(fileMitra.value) || Boolean(String(item.value?.gambaralat || '').trim()))
})

const revokeSelectedPhotoPreview = () => {
    if (selectedPhotoPreviewUrl.value) {
        URL.revokeObjectURL(selectedPhotoPreviewUrl.value)
        selectedPhotoPreviewUrl.value = ''
    }
}

const setSelectedPhoto = (file: File) => {
    revokeSelectedPhotoPreview()
    fileMitra.value = file
    selectedPhotoPreviewUrl.value = URL.createObjectURL(file)
    photoPreviewError.value = false
}

const clearSelectedPhoto = () => {
    revokeSelectedPhotoPreview()
    fileMitra.value = null
    photoPreviewError.value = false
}

const handlePhotoPreviewError = () => {
    photoPreviewError.value = true
}


const fetchMitra = async (filter: any) => {
    await useApi().get(
        `general/dropdown/mitra_m?select=id,namaperusahaan&param_search=namaperusahaan&query=${filter.query}&limit=10`
    ).then((response) => {
        d_mitra.value = response
    })
}


const openCamera = () => {
    if (isMobileDevice()) {
        const videoInput = document.createElement('input');
        videoInput.type = 'file';
        videoInput.accept = 'image/*';
        videoInput.capture = 'environment';

        videoInput.onchange = async (event) => {
            const file = (event.target as HTMLInputElement).files?.[0];
            if (file) {
                const nama_detail = item.value.namaproduk;
                const imageFileName = `${nama_detail}.jpeg`;

                const renamedFile = new File([file], imageFileName, { type: "image/jpeg" });
                setSelectedPhoto(renamedFile);
                console.log("Gambar berhasil diambil dari kamera (mobile):", fileMitra.value);
            }
        };

        videoInput.click();
    } else {
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: "environment" } }
            })
                .then((stream) => {
                    if (video.value) {
                        video.value.srcObject = stream;
                        video.value.style.display = 'block';
                        isCameraActive.value = true;
                    }
                })
                .catch((err) => {
                    console.error("Gagal mengakses kamera:", err);
                });
        }
    }
};

const takePhoto = () => {
    if (video.value) {
        const canvas = document.createElement('canvas');
        canvas.width = video.value.videoWidth;
        canvas.height = video.value.videoHeight;
        const context = canvas.getContext('2d');
        if (context) {
            context.drawImage(video.value, 0, 0, canvas.width, canvas.height);
            canvas.toBlob((blob) => {
                if (blob) {
                    const nama_detail = item.value.namaproduk;
                    const imageFileName = `${nama_detail}.jpeg`;

                    const imageFile = new File([blob], imageFileName, { type: "image/jpeg" });
                    setSelectedPhoto(imageFile);
                    console.log("Foto tersimpan sebagai Gambar:", fileMitra.value);
                }
                stopCamera();
            }, "image/jpeg", 1.0);
        }
    }
};

const stopCamera = () => {
    if (video.value && video.value.srcObject) {
        const stream = video.value.srcObject;
        const tracks = stream.getTracks();
        tracks.forEach((track: MediaStreamTrack) => track.stop());
        video.value.srcObject = null;
        video.value.style.display = 'none';
        isCameraActive.value = false;
    }
};

const previewFile = () => {
    if (photoPreviewUrl.value) {
        window.open(photoPreviewUrl.value, "_blank", "noopener,noreferrer");
    } else {
        alert("Tidak ada file yang bisa dibuka.");
    }
};

const isMobileDevice = () => {
    return /Mobi|Android/i.test(navigator.userAgent);
};

const onSelect = async (filez: any) => {
    const file = filez.files[0];
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

    if (file.size > 10000000) {
        H.alert('error', 'Maksimal file size adalah 10 MB');
        return;
    }

    if (!allowedTypes.includes(file.type)) {
        H.alert('error', 'File harus berupa JPG, JPEG, PNG, atau WEBP');
        fileMitra.value = null;
        return;
    }

    setSelectedPhoto(file);
};


const d_Statusenabled = [
    {
        name: 'Aktif',
        value: 'true',
    },
    {
        name: 'Tidak Aktif',
        value: 'false',
    },
]

async function produkByID() {
    isLoading.value = true

    await useApi().get(`/sysadmin/master-produk?id=${ID_PRODUK}`).then((response) => {
        let produk = response.data[0]
        item.value.namaproduk = produk.namaproduk
        item.value.namamerk = produk.namamerk
        item.value.namaserialnumber = produk.namaserialnumber
        item.value.namatipe = produk.namatipe
        item.value.statusenabled = produk.statusenabled
        item.value.mitrafk = {
            value: produk.objectmitrafk,
            label: produk.namaperusahaan
        };
        item.value.gambaralat = produk.fotoproduk;
        photoPreviewError.value = false

    })
    isLoading.value = false
}

async function saveProduk() {

    if (!item.value.namaproduk) {
        useToaster().error('Nama Alat harus di isi')
        return
    }
    if (!item.value.namamerk) {
        useToaster().error('Merk harus di isi')
        return
    }
    if (!item.value.namatipe) {
        useToaster().error('Tipe harus di isi')
        return
    }
    if (!item.value.namaserialnumber) {
        useToaster().error('Serial Number harus di isi')
        return
    }
    if (!item.value.mitrafk) {
        useToaster().error('Unit harus di isi')
        return
    }
    if (!hasRequiredPhoto.value) {
        useToaster().error('Foto Alat wajib di isi')
        return
    }

    const formData = new FormData()
    if (isFile(fileMitra.value)) {
        formData.append('fileMitra', fileMitra.value);
    } else if (item.value.gambaralat) {
        formData.append('namaFileLama', item.value.gambaralat);
    }
    formData.append('id', ID_PRODUK ?? '')
    formData.append('namaproduk', item.value.namaproduk)
    formData.append('namamerk', item.value.namamerk)
    formData.append('namatipe', item.value.namatipe)
    formData.append('namaserialnumber', item.value.namaserialnumber.trim().toUpperCase())
    formData.append('mitrafk', item.value.mitrafk.value)
    formData.append('statusenabled', item.value.statusenabled ?? true)
    isLoading.value = true
    isRegistrasi.value = false
    await useApi().post(
        `/sysadmin/save-master-produk`, formData).then((response: any) => {
            isLoading.value = false
            ID_PRODUK = response.data.id
            ID_PRODUK_SET.value = response.data.id
            isRegistrasi.value = true
            kembali()
        }).catch((e: any) => {
            isLoading.value = false
            console.clear()
            console.log(e)
        })
}

const kembali = () => {
    router.back()
}

function resetForm() {

}

onMounted(() => {
  if (ID_PRODUK && ID_PRODUK.trim() !== '') {
    produkByID()
  }
})

onUnmounted(() => {
    stopCamera()
    revokeSelectedPhotoPreview()
})

</script>
<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/components/forms-outer';

.form-layout {
    max-width: 800px;
    margin: 0 auto;
}

.form-fieldset {
    padding: 20px 0;
    max-width: 780px;
    margin: 0 auto;
}

.is-uppercase {
  text-transform: uppercase;
}

.photo-required {
    color: var(--danger);
}

.product-photo-preview {
    display: grid;
    grid-template-columns: 170px minmax(0, 1fr);
    gap: 18px;
    align-items: center;
    margin-bottom: 14px;
    padding: 14px;
    border: 1px solid #bfdbfe;
    border-radius: 14px;
    background: #f8fbff;
}

.product-photo-preview img {
    display: block;
    width: 170px;
    height: 120px;
    object-fit: contain;
    border: 1px solid #dbeafe;
    border-radius: 10px;
    background: #fff;
}

.product-photo-preview-copy {
    min-width: 0;
}

.product-photo-preview-copy span,
.product-photo-preview-copy small {
    display: block;
}

.product-photo-preview-copy span {
    color: #1477e9;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.product-photo-preview-copy strong {
    display: block;
    margin: 4px 0;
    overflow: hidden;
    color: var(--dark-text);
    font-family: var(--font);
    font-size: 14px;
    font-weight: 600;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.product-photo-preview-copy small {
    color: var(--light-text);
    font-size: 12px;
}

.product-photo-empty {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 14px;
    padding: 16px;
    color: #64748b;
    border: 1px dashed #93c5fd;
    border-radius: 12px;
    background: #f8fbff;
}

.product-photo-empty>.iconify {
    flex: 0 0 auto;
    color: #1477e9;
    font-size: 28px;
}

.product-photo-empty strong,
.product-photo-empty span {
    display: block;
}

.product-photo-empty strong {
    color: var(--dark-text);
    font-size: 13px;
    font-weight: 600;
}

.product-photo-empty span {
    margin-top: 2px;
    font-size: 12px;
}

.product-photo-upload {
    overflow: hidden;
    border: 1px solid #bfdbfe !important;
    border-radius: 12px !important;
    background: transparent !important;
}

.product-photo-upload :deep(.p-fileupload-buttonbar) {
    padding: 12px;
    background: #f8fbff;
    border: 0;
    border-bottom: 1px solid #dbeafe;
}

.product-photo-upload :deep(.p-fileupload-buttonbar .p-button) {
    color: #fff;
    background: #1477e9;
    border-color: #1477e9;
    border-radius: 9px;
    font-family: var(--font);
    font-weight: 500;
    box-shadow: 0 6px 14px rgba(20, 119, 233, 0.18);
}

.product-photo-upload :deep(.p-fileupload-buttonbar .p-button:hover) {
    background: #0f67ca;
    border-color: #0f67ca;
}

.product-photo-upload :deep(.p-fileupload-content) {
    min-height: 72px;
    padding: 14px;
    background: #fff;
    border: 0;
}

.product-photo-actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 12px;
}

.product-photo-actions :deep(.button) {
    min-height: 40px;
    border-radius: 9px;
    font-family: var(--font);
    font-weight: 500;
}

.product-photo-actions+video {
    max-width: 100%;
    height: auto;
    margin-top: 12px;
    border: 1px solid #bfdbfe;
    border-radius: 12px;
    background: #0f172a;
}

.is-dark .product-photo-preview,
.is-dark .product-photo-empty,
.is-dark .product-photo-upload :deep(.p-fileupload-buttonbar) {
    background: rgba(30, 41, 59, 0.72);
    border-color: rgba(59, 130, 246, 0.4);
}

.is-dark .product-photo-preview img,
.is-dark .product-photo-upload :deep(.p-fileupload-content) {
    background: rgba(15, 23, 42, 0.74);
    border-color: rgba(59, 130, 246, 0.32);
}

.is-dark .product-photo-preview-copy strong,
.is-dark .product-photo-empty strong {
    color: var(--dark-dark-text);
}

@media (max-width: 680px) {
    .product-photo-preview {
        grid-template-columns: 1fr;
    }

    .product-photo-preview img {
        width: 100%;
        height: 190px;
    }

    .product-photo-actions :deep(.button) {
        width: 100%;
    }
}

</style>
