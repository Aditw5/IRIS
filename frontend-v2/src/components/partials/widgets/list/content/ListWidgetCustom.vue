<script setup lang="ts">
import { VIconBoxColor } from '/@src/components/base/icon/VIconBox.vue'

const props = withDefaults(
    defineProps<{
        title?: string
        color?: VIconBoxColor
        icon?: string
        subtitle?: string
        straight?: boolean
        class?:string
        tooltip?:string
    }>(),
    {
        title: 'Status',
        color: 'primary',
        icon: 'feather:info',
        subtitle: 'Status Detail',
    }
)
</script>

<template>
    <div>
        <div class="inner-list-item media-flex-center" :class="class">
            <VIconBox rounded :color="color" >
                <i aria-hidden="true" class="iconify" :data-icon="icon"></i>
            </VIconBox>
            <div class="flex-meta is-light">
                <a href="#">{{ title }}</a>
                <span>{{subtitle }}</span>
            </div>
            <!-- <div v-if="topic.users" class="flex-end">
        <VAvatarStack :avatars="topic.users" size="small" :limit="3" />
      </div> -->
        </div>
    </div>
</template>
