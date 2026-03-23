<template>
  <OrderList :status-filter="['UNCOLLECTED_CLIENT']" title="أوردرات Uncollected Client" />
</template>

<script setup lang="ts">
import OrderList from './OrderList.vue';
</script>
