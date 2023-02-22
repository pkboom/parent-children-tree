<script setup>
const props = defineProps({
    parents: Array,
    comments: Array,
});

const subComments = props.comments.filter(
    (item) => item.parent_id == props.parents[0].id
);
</script>

<template>
    <div>
        <slot :comment="parents[0]">
            {{ parents[0] }}
        </slot>
        <VFor
            v-if="subComments && subComments.length > 0"
            :parents="subComments"
            :comments="comments"
            v-slot="{ comment }"
        >
            <slot :comment="comment">
                {{ comment }}
            </slot>
        </VFor>
        <VFor
            v-if="parents.length > 1"
            :parents="parents.slice(1)"
            :comments="comments"
            v-slot="{ comment }"
        >
            <slot :comment="comment">
                {{ comment }}
            </slot>
        </VFor>
    </div>
</template>
