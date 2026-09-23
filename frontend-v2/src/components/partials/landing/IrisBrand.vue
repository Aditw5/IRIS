<template>
  <span class="iris-brand" aria-label="IRIS — Intelligent Real-time Insight System">
    <span class="iris-brand-mark" aria-hidden="true"
      ><img src="/IRIS_1.png" alt="" width="838" height="934"
    /></span>
    <span class="iris-brand-name" aria-hidden="true">IRIS<span class="iris-brand-dot">.</span></span>
  </span>
</template>

<style scoped>
.iris-brand {
  display: inline-flex;
  align-items: center;
  gap: 9px;
  color: inherit;
}
.iris-brand-mark {
  position: relative;
  display: block;
  width: 38px;
  height: 38px;
  overflow: hidden;
  flex-shrink: 0;
}
.iris-brand-mark img {
  position: absolute;
  width: 58px;
  height: auto;
  max-width: none;
  top: -2px;
  left: -10px;
}
.iris-brand-name {
  font: 800 32px/1 'Manrope', 'Arial', sans-serif;
  letter-spacing: -1.8px;
}
.iris-brand-dot {
  color: #23c7e8;
}
</style>
