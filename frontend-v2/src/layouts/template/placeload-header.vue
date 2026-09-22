<template>
    <div class="block-green">
        <div class="flex-list-inner mb-4">
            <div class="flex-table-item grid-item mb-4" v-for="key in 1" :key="key">
                <VFlexTableCell :column="{ grow: true, media: true }">
                    <VPlaceloadAvatar size="medium" />
                    <VPlaceloadText :lines="2" width="30%" last-line-width="20%" class="mx-2" />
                </VFlexTableCell>
                <VFlexTableCell>
                    <VPlaceload width="100%" height="53px" class="mx-1 mt-2" />
                </VFlexTableCell>
                <VFlexTableCell>
                    <VPlaceload width="10%" height="10px" class="mx-1 mt-1" />
                </VFlexTableCell>
                <VFlexTableCell :column="{ align: 'end' }">
                    <VPlaceload width="10%" class="mx-1" />
                </VFlexTableCell>
            </div>
        </div>
    </div>
</template>