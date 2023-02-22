<script setup>
import VFor from "./VFor.vue";

const props = defineProps({
    post: Object,
    comments: Array,
});

const parents = props.comments.filter((item) => !item.parent_id);
</script>

<template>
    <div>
        <div class="mb-2">{{ post.id }} {{ post.body }}</div>
        <VFor
            v-if="parents.length > 0"
            :parents="parents"
            :comments="comments"
            v-slot="{ comment }"
        >
            <div :style="`margin-left: ${comment.depth}em`">
                {{ comment.path }}
                {{ comment.body }}
            </div>
        </VFor>
    </div>
</template>
