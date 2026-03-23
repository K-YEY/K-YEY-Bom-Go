<template>
  <OrderList :status-filter="['UNRETURN_SHIPPER']" title="أوردرات Unreturn Shipper" />
</template>

<script setup lang="ts">
import OrderList from './OrderList.vue';
</script>
