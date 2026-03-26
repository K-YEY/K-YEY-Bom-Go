<template>
  <OrderList :fixed-filters="{ status: ['DELIVERED', 'UNDELIVERED'], is_client_settled: 0 }" title="أوردرات Uncollected Client" />
</template>

<script setup lang="ts">
import OrderList from '../OrderList.vue';
</script>

