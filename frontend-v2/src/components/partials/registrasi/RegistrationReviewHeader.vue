<template>
  <section class="registration-review-header" :aria-label="contextLabel">
    <div class="header-identity">
      <span class="header-context">{{ contextLabel }}</span>

      <h1>{{ companyName || '-' }}</h1>
      <p>Ringkasan pendaftaran dan progres pengkajian alat</p>

      <div class="header-stats" aria-label="Statistik pengkajian alat">
        <div class="header-stat header-stat--total">
          <span>Total Alat</span>
          <strong>{{ total }}</strong>
        </div>
        <div class="header-stat header-stat--reviewed">
          <span>Sudah Kaji</span>
          <strong>{{ reviewed }}</strong>
        </div>
        <div class="header-stat header-stat--pending">
          <span>Belum Kaji</span>
          <strong>{{ pending }}</strong>
        </div>
      </div>
    </div>

    <div class="header-information">
      <div class="header-information__item">
        <span class="header-information__label">No. Pendaftaran</span>
        <strong>{{ registrationNumber || '-' }}</strong>
      </div>

      <div class="header-information__item">
        <span class="header-information__label">Tanggal Registrasi</span>
        <strong>{{ registrationDate || '-' }}</strong>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
withDefaults(defineProps<{
  contextLabel?: string
  companyName?: string | null
  registrationNumber?: string | null
  registrationDate?: string | null
  total?: number
  reviewed?: number
  pending?: number
}>(), {
  contextLabel: 'Pengkajian Ulang',
  companyName: '-',
  registrationNumber: '-',
  registrationDate: '-',
  total: 0,
  reviewed: 0,
  pending: 0,
})
</script>

<style scoped lang="scss">
.registration-review-header {
  display: grid;
  grid-template-columns: minmax(0, 1.4fr) minmax(300px, 0.6fr);
  gap: 2.5rem;
  align-items: center;
  margin-bottom: 1rem;
  padding: 2rem 3rem;
  border-radius: 18px;
  background: #2faaa0;
  box-shadow: 0 12px 30px rgb(47 170 160 / 20%);
  color: #fff;
}

.registration-review-header h1,
.registration-review-header p,
.registration-review-header span,
.registration-review-header strong {
  color: #fff !important;
}

.header-identity {
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.header-context {
  align-self: flex-start;
  font-family: var(--font-alt);
  font-size: 0.76rem;
  font-weight: 700;
  letter-spacing: 0.09em;
  text-transform: uppercase;
}

.header-identity h1 {
  max-width: 760px;
  margin: 0.65rem 0 0.25rem;
  font-family: var(--font-alt);
  font-size: clamp(1.55rem, 2.35vw, 2.25rem);
  font-weight: 700;
  line-height: 1.2;
}

.header-identity > p {
  margin: 0;
  font-size: 0.92rem;
}

.header-stats {
  display: flex;
  align-items: center;
  margin-top: 1.35rem;
}

.header-stat {
  position: relative;
  display: flex;
  min-width: 125px;
  flex-direction: column;
  padding: 0 1.4rem 0 1.75rem;
}

.header-stat::before {
  position: absolute;
  top: 50%;
  left: 0;
  width: 5px;
  height: 38px;
  border-radius: 3px;
  background: #bfdbfe;
  content: '';
  transform: translateY(-50%);
}

.header-stat--reviewed::before {
  background: #86efac;
}

.header-stat--pending::before {
  background: #fecaca;
}

.header-stat:first-child {
  padding-left: 1.75rem;
}

.header-stat + .header-stat {
  border-left: 1px solid rgb(255 255 255 / 45%);
}

.header-stat span {
  font-size: 0.76rem;
  font-weight: 600;
  white-space: nowrap;
}

.header-stat strong {
  margin-top: 0.18rem;
  font-family: var(--font-alt);
  font-size: 1.6rem;
  line-height: 1;
}

.header-information {
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-height: 130px;
  padding-left: 2.5rem;
  border-left: 1px solid rgb(255 255 255 / 45%);
}

.header-information__item {
  display: flex;
  flex-direction: column;
}

.header-information__item + .header-information__item {
  margin-top: 1.15rem;
  padding-top: 1.15rem;
  border-top: 1px solid rgb(255 255 255 / 30%);
}

.header-information__label {
  margin-bottom: 0.25rem;
  font-size: 0.74rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.header-information strong {
  overflow: hidden;
  font-family: var(--font-alt);
  font-size: 1.05rem;
  font-weight: 700;
  text-overflow: ellipsis;
  white-space: nowrap;
}

@media only screen and (max-width: 1023px) {
  .registration-review-header {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }

  .header-information {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    min-height: auto;
    padding: 1.25rem 0 0;
    border-top: 1px solid rgb(255 255 255 / 45%);
    border-left: none;
  }

  .header-information__item + .header-information__item {
    margin-top: 0;
    padding-top: 0;
    padding-left: 1.5rem;
    border-top: none;
    border-left: 1px solid rgb(255 255 255 / 30%);
  }
}

@media only screen and (max-width: 767px) {
  .registration-review-header {
    padding: 1.5rem;
    border-radius: 15px;
  }

  .header-stat {
    min-width: 0;
    flex: 1;
    padding: 0 0.8rem 0 1.25rem;
  }

  .header-stat::before {
    width: 4px;
    height: 32px;
  }

  .header-stat span {
    font-size: 0.68rem;
  }

  .header-stat strong {
    font-size: 1.35rem;
  }
}

@media only screen and (max-width: 480px) {
  .header-information {
    grid-template-columns: 1fr;
  }

  .header-information__item + .header-information__item {
    padding-top: 1rem;
    padding-left: 0;
    border-top: 1px solid rgb(255 255 255 / 30%);
    border-left: none;
  }
}
</style>
