<template>
    <div class="hass-loader hass-loader-active">
        <div class="loader-wrapper" v-if="props.active">
            <div class="semipolar-spinner" :style="spinnerStyle">
                <div class="ring"></div>
                <div class="ring"></div>
                <div class="ring"></div>
                <div class="ring"></div>
                <div class="ring"></div>
            </div>
        </div>
        <slot></slot>
    </div>
</template>

<script setup lang="ts">
export interface TransLoaderProps {
    active?: boolean
}
const props = withDefaults(defineProps<TransLoaderProps>(), {
    size: undefined,
    card: undefined,
})
</script>

<style lang="scss">
.semipolar-spinner,
.semipolar-spinner * {
    box-sizing: border-box;
    z-index: 10;
}

.hass-loader {
    position: relative;
    opacity: .65;

    &.hass-loader-active {
        overflow: hidden;
        pointer-events: all;
    }

    .loader-wrapper {
        position: fixed;
        top: 0;
        left: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100%;
        width: 100%;

        transition: opacity 0.3s;
        z-index: 5;

        &.is-active {
            opacity: 1;
            pointer-events: all;

            &.is-translucent {
                opacity: 0.65;
            }
        }

        &.is-grey {
            background: var(--background-grey);
        }

        .loader {
            height: 3rem;
            width: 3rem;

            &.is-small {
                height: 2rem;
                width: 2rem;
            }

            &.is-large {
                height: 5rem;
                width: 5rem;
            }

            &.is-xl {
                height: 7rem;
                width: 7rem;
            }
        }
    }
}

.semipolar-spinner {
    height: 65px;
    width: 65px;
    position: relative;

}

.semipolar-spinner .ring {
    border-radius: 50%;
    position: absolute;
    border: calc(65px * 0.05) solid transparent;
    border-top-color: #ff1d5e;
    border-left-color: #ff1d5e;
    animation: semipolar-spinner-animation 2s infinite;
}

.semipolar-spinner .ring:nth-child(1) {
    height: calc(65px - 65px * 0.2 * 0);
    width: calc(65px - 65px * 0.2 * 0);
    top: calc(65px * 0.1 * 0);
    left: calc(65px * 0.1 * 0);
    animation-delay: calc(2000ms * 0.1 * 4);
    z-index: 5;
}

.semipolar-spinner .ring:nth-child(2) {
    height: calc(65px - 65px * 0.2 * 1);
    width: calc(65px - 65px * 0.2 * 1);
    top: calc(65px * 0.1 * 1);
    left: calc(65px * 0.1 * 1);
    animation-delay: calc(2000ms * 0.1 * 3);
    z-index: 4;
}

.semipolar-spinner .ring:nth-child(3) {
    height: calc(65px - 65px * 0.2 * 2);
    width: calc(65px - 65px * 0.2 * 2);
    top: calc(65px * 0.1 * 2);
    left: calc(65px * 0.1 * 2);
    animation-delay: calc(2000ms * 0.1 * 2);
    z-index: 3;
}

.semipolar-spinner .ring:nth-child(4) {
    height: calc(65px - 65px * 0.2 * 3);
    width: calc(65px - 65px * 0.2 * 3);
    top: calc(65px * 0.1 * 3);
    left: calc(65px * 0.1 * 3);
    animation-delay: calc(2000ms * 0.1 * 1);
    z-index: 2;
}

.semipolar-spinner .ring:nth-child(5) {
    height: calc(65px - 65px * 0.2 * 4);
    width: calc(65px - 65px * 0.2 * 4);
    top: calc(65px * 0.1 * 4);
    left: calc(65px * 0.1 * 4);
    animation-delay: calc(2000ms * 0.1 * 0);
    z-index: 1;
}

@keyframes semipolar-spinner-animation {
    50% {
        transform: rotate(360deg) scale(0.7);
    }
}
</style>