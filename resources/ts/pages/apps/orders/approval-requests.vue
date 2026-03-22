<script setup lang="ts">
import ApprovalRequestList from '@/views/apps/orders/ApprovalRequestList.vue';
</script>

<template>
  <div>
    <VRow>
      <VCol cols="12">
        <ApprovalRequestList />
      </VCol>
    </VRow>
  </div>
</template>

<route lang="yaml">
meta:
  action: view
  subject: order
</route>
