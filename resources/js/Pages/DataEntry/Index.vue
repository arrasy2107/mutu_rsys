<script setup>
import DataEntryWorkspace from './Workspace.vue';

defineOptions({ inheritAttrs: false });
</script>

<template>
    <DataEntryWorkspace v-bind="$attrs" />
</template>
