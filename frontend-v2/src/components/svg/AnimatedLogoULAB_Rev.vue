<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const props = defineProps<{
  light?: boolean
}>()

const router = useRouter()
const isLoading = ref(false)

router.beforeEach(() => {
  isLoading.value = true
})
router.afterEach(() => {
  setTimeout(() => {
    isLoading.value = false
  }, 200)
})
</script>
<template>
  <svg width="122" height="130" viewBox="0 0 122 130" fill="none" xmlns="http://www.w3.org/2000/svg"
    xmlns:xlink="http://www.w3.org/1999/xlink">
    <rect width="122" height="130" fill="url(#pattern0)" />
    <defs>
      <pattern id="pattern0" patternContentUnits="objectBoundingBox" width="1" height="1">
        <use xlink:href="#image0_1170_4" transform="scale(0.00819672 0.00769231)" />
      </pattern>
      <image id="image0_1170_4" width="122" height="130"
        xlink:href="data:image/png;base64,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" />
    </defs>
  </svg>

</template>

<style lang="scss" scoped>
.right {
  fill: var(--primary-dark-7);
}

.bottom {
  fill: var(--primary);
}

.left {
  fill: var(--primary-light-15);
}

.is-roll,
svg:hover {
  .right {
    animation: is-roll-right 8s ease infinite;
    animation-fill-mode: forwards;
    animation-delay: 0s;
  }

  .left {
    animation: is-roll-left 8s ease infinite;
    animation-fill-mode: forwards;
    animation-delay: 0s;
  }

  .bottom {
    animation: is-roll-bottom 8s ease infinite;
    animation-fill-mode: forwards;
    animation-delay: 0s;
  }
}

.is-light {
  .right {
    fill: var(--white);
    animation: none;
  }

  .bottom {
    fill: #fffc;
    animation: none;
  }

  .left {
    fill: #fffa;
    animation: none;
  }
}

@keyframes is-roll-right {
  0% {
    fill: var(--primary-dark-7);
  }

  33% {
    fill: var(--primary-light-8);
  }

  66% {
    fill: var(--primary-light-40);
  }

  100% {
    fill: var(--primary-dark-7);
  }
}

@keyframes is-roll-bottom {
  0% {
    fill: var(--primary);
  }

  33% {
    fill: var(--primary-light-24); // darken(#1ee8c0, 7%);
  }

  66% {
    fill: var(--primary-light-20);
  }

  100% {
    fill: var(--primary);
  }
}

@keyframes is-roll-left {
  0% {
    fill: var(--primary-light-15);
  }

  33% {
    fill: var(--primary-light-12); // lighten(#1ee8c0, 15%);
  }

  66% {
    fill: var(--primary-light-15);
  }

  100% {
    fill: var(--primary-light-15);
  }
}
</style>
