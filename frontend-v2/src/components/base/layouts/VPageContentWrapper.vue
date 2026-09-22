<template >
  <div class="page-content-wrapper" :class="fullWidth == true ? 'is-100': ''">
    <slot></slot>
  </div>
</template>
<script  lang="ts">
import { ref } from 'vue'
export default {
  props: {
    fullWidth: Boolean
  },
  setup(props: any) {
    let fullWidth = ref(false)
    fullWidth.value = props.fullWidth
    return fullWidth
  }
}

</script>
<style lang="scss">
/* ==========================================================================
3. Page Content
========================================================================== */

.page-content-wrapper {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;

  .page-content {
    padding: 0 40px;

    &.is-relative {
      position: relative;


      &.is-100 {
        max-width: 100% !important;
      }
    }

    &.kanban-content {
      padding: 0 20px;
    }

    &.chat-content {
      padding: 0 40px;
    }

    &.card-content {
      padding: 0 20px;
    }

    &.waterfall-content {
      overflow: hidden;
    }

    &.projects-content {
      padding: 0 40px;
    }
  }
}

.page-content-wrapper.is-100 {
  max-width: 100%;
}

// Enable this setting if you want the dashboard to be larger
@media only screen and (min-width: 1408px) {
  .page-content-wrapper {
    // max-width: 1380px;
  }
}

@media only screen and (min-device-width: 768px) and (max-device-width: 1024px) and (orientation: portrait) {
  .page-content-wrapper {
    .page-content {
      padding: 0;
    }
  }
}
</style>
