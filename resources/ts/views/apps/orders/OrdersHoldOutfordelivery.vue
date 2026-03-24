<template>
  <OrderList :status-filter="['HOLD', 'OUT_FOR_DELIVERY']" title=" HOLD و OUT_FOR_DELIVERY" />
</template>

<script setup lang="ts">
import OrderList from './OrderList.vue';
</script>
