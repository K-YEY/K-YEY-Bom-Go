<template>
  <OrderList :status-filter="['UNRETURN_CLIENT']" title="أوردرات Unreturn Client" />
</template>

<script setup lang="ts">
import OrderList from './OrderList.vue';
</script>
