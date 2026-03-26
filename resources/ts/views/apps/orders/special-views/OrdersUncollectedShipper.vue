<template>
  <OrderList status="DELIVERED" :fixed-filters="{ is_shipper_collected: 0 }" title="أوردرات Uncollected Shipper" />
</template>

<script setup lang="ts">
import OrderList from '../OrderList.vue';
</script>

