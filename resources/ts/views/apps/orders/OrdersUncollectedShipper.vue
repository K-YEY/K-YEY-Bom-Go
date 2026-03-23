<template>
  <OrderList :status-filter="['UNCOLLECTED_SHIPPER']" title="أوردرات Uncollected Shipper" />
</template>

<script setup lang="ts">
import OrderList from './OrderList.vue';
</script>
