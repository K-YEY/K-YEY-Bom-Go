<template>
  <OrderList status="DELIVERED" :fixed-filters="{ is_client_settled: 0 }" title="أوردرات Uncollected Client" />
</template>

<script setup lang="ts">
import OrderList from './OrderList.vue';
</script>
