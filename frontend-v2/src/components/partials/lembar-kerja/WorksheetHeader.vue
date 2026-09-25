<template>
  <div class="column worksheet-header-wrap">
    <div class="business-dashboard hr-dashboard">
      <div class="columns is-multiline">
        <div class="column is-12 p-0">
          <div class="block-header worksheet-header">
            <div class="worksheet-photo-wrap">
              <img
                class="worksheet-photo"
                :src="imageUrl"
                :alt="`Foto ${item?.namaproduk || 'alat'}`"
                @error="onImageError"
              />
            </div>
            <div class="worksheet-summary">
              <h3 class="worksheet-tool-name">{{ item?.namaproduk || '-' }}</h3>
              <div class="worksheet-identity">
                <div class="identity-item">
                  <span>Merk</span>
                  <strong>{{ item?.namamerk || '-' }}</strong>
                </div>
                <div class="identity-item">
                  <span>Tipe</span>
                  <strong>{{ item?.namatipe || '-' }}</strong>
                </div>
                <div class="identity-item">
                  <span>S/N</span>
                  <strong>{{ item?.namaserialnumber || '-' }}</strong>
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
import { computed } from 'vue'
import { publicFileUrl } from '/@src/utils/publicFileUrl'

const props = defineProps<{ item: Record<string, any> }>()

const imageUrl = computed(() => props.item?.fotoproduk
  ? publicFileUrl('produk', props.item.fotoproduk)
  : '/images/other/no_image.jpg')

const onImageError = (event: Event) => {
  const image = event.target as HTMLImageElement
  if (image.dataset.fallbackApplied === 'true') return
  image.dataset.fallbackApplied = 'true'
  image.src = '/images/other/no_image.jpg'
}
</script>

<style scoped lang="scss">
.worksheet-header-wrap {
  padding-bottom: 0;
}

.worksheet-header {
  display: grid !important;
  grid-template-columns: 148px minmax(0, 1fr);
  align-items: center;
  gap: 32px;
  min-height: 190px;
  padding: 22px 34px !important;
}

.worksheet-photo-wrap {
  width: 148px;
  height: 148px;
  padding: 8px;
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.94);
  box-shadow: 0 8px 22px rgba(36, 48, 72, 0.16);
}

.worksheet-photo {
  width: 100%;
  height: 100%;
  border-radius: 12px;
  object-fit: contain;
}

.worksheet-summary {
  min-width: 0;
}

.worksheet-tool-name {
  margin: 0;
  color: #fff !important;
  font-size: clamp(1.85rem, 2.6vw, 2.85rem) !important;
  font-weight: 800 !important;
  line-height: 1.12;
  overflow-wrap: anywhere;
}

.worksheet-identity {
  display: flex;
  flex-wrap: wrap;
  gap: 18px 54px;
  min-width: 0;
  margin-top: 22px;
}

.identity-item {
  display: grid;
  gap: 4px;
  min-width: min(250px, 100%);
}

.identity-item span,
.identity-item strong {
  color: #fff !important;
}

.identity-item span {
  font-size: 0.82rem;
  font-weight: 600;
  opacity: 0.82;
}

.identity-item strong {
  font-size: 1.08rem;
  font-weight: 700;
  overflow-wrap: anywhere;
}

@media (max-width: 900px) {
  .worksheet-header {
    grid-template-columns: 108px minmax(0, 1fr);
    gap: 18px;
    padding: 20px !important;
  }

  .worksheet-photo-wrap {
    width: 108px;
    height: 108px;
  }

  .worksheet-identity {
    gap: 14px 30px;
    margin-top: 16px;
  }
}

@media (max-width: 520px) {
  .worksheet-header {
    grid-template-columns: 1fr;
  }

  .worksheet-photo-wrap {
    width: 104px;
    height: 104px;
  }

  .worksheet-identity {
    display: grid;
    gap: 12px;
  }
}
</style>
