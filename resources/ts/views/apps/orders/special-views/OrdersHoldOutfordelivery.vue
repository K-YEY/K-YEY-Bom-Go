<template>
  <OrderList :status-filter="['HOLD', 'OUT_FOR_DELIVERY']" :fixed-filters="{ approval_status: ['APPROVED', 'PENDING'] }" title=" HOLD و OUT_FOR_DELIVERY" />
</template>

<script setup lang="ts">
import OrderList from '../OrderList.vue';
</script>

