
<script  lang="ts">
export default {
    props: {
        name: String,
        color: String,
        icon: String,
        status: Boolean,
    },
    setup(props: any) {

    }
}

</script>
<template>
    <div class="stock">
        <div class="stock-logo" :class="color">
            <i aria-hidden="true" :class="icon"></i>
        </div>
        <div class="stock-info">
            <div class="stock-name">{{name}}</div>
        </div>
        <div class="stock-value">
            <div class="status-icon is-success" v-if="status">
                <i aria-hidden="true" class="fas fa-check"></i>
            </div>
            <div class="status-icon is-danger" v-else>
                <i aria-hidden="true" class="fas fa-times"></i>
            </div>
        </div>
    </div>
</template>
    

    