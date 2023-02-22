<script setup>
import VFor from "./VFor.vue";
import { ref } from "vue";

const props = defineProps({
    post: Object,
    comments: Array,
});

const parents = ref(props.comments.filter((item) => !item.parent_id));

const indent = (path) => {
    return path.split(".").length;
};
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
                {{ comment.body }}
                {{ comment.path }}
            </div>
        </VFor>
    </div>
</template>
