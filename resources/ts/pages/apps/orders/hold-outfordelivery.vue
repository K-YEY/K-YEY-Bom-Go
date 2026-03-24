<script setup lang="ts">
import OrdersHoldOutfordelivery from '@/views/apps/orders/OrdersHoldOutfordelivery.vue';
</script>

<template>
  <div>
    <VRow>
      <VCol cols="12">
        <OrdersHoldOutfordelivery />
      </VCol>
    </VRow>
  </div>
</template>

<route lang="yaml">
meta:
  action: manage
  subject: order.hold-outfordelivery.page
</route>
