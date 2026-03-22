<script setup lang="ts">
import { useApi } from '@/composables/useApi';

interface Props {
  isDialogVisible: boolean
  selectedOrders: any[]
  metadata?: any
}

const props = defineProps<Props>()
const emit = defineEmits(['update:isDialogVisible', 'statusUpdated'])

const statusData = ref({
  status: 'DELIVERED',
  reason: '',
  refused_reason_id: null as number | null,
  total_amount: null as number | null,
})

const isLoading = ref(false)
const reasonsLoading = ref(false)
const reasonsData = ref<any>(null)
const allReasons = computed(() => Array.isArray(reasonsData.value) ? reasonsData.value : (reasonsData.value?.data || []))

const fetchReasons = async () => {
  if (props.metadata?.refused_reasons) {
    reasonsData.value = props.metadata.refused_reasons
    return
  }
  reasonsLoading.value = true
  try {
    const { data } = await useApi<any>('/refused-reasons').get().json()
    reasonsData.value = data.value
  } catch (e) { console.error(e) }
  reasonsLoading.value = false
}

onMounted(fetchReasons)

const filteredReasons = computed(() => {
  return allReasons.value.filter((r: any) => r.is_active && r.status === statusData.value.status)
})

const onSubmit = async () => {
  const validOrders = props.selectedOrders.filter(o => !['DELIVERED', 'UNDELIVERED', 'CANCELLED'].includes(o.status))
  
  if (!validOrders.length) {
    alert('لا يوجد أوردرات قابلة للتعديل (تم استثناء الأوردرات المسلمة أو المرتجعة بالفعل)')
    return
  }

  isLoading.value = true
  const payload = {
    order_ids: validOrders.map(o => o.id),
    status: statusData.value.status,
    reason: statusData.value.reason,
    refused_reason_id: statusData.value.refused_reason_id,
    total_amount: statusData.value.total_amount,
  }

  try {
    const { error } = await useApi('/orders/bulk-change-status').patch(payload).json()
    if (!error.value) {
      emit('statusUpdated')
      emit('update:isDialogVisible', false)
    }
  } catch (e) { console.error(e) }
  isLoading.value = false
}
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    max-width="500"
    @update:model-value="val => emit('update:isDialogVisible', val)"
  >
    <VCard :title="`Bulk Update Status (${props.selectedOrders.length} items)`" :loading="isLoading">
      <VCardText>
        <VRow>
          <VCol cols="12">
            <AppSelect
              v-model="statusData.status"
              label="New Status"
              :items="[
                { title: 'Out for delivery', value: 'OUT_FOR_DELIVERY' },
                { title: 'Delivered', value: 'DELIVERED' },
                { title: 'On hold', value: 'HOLD' },
                { title: 'Undelivered', value: 'UNDELIVERED' },
              ]"
              @update:model-value="statusData.refused_reason_id = null"
            />
          </VCol>

          <VCol cols="12" v-if="filteredReasons.length">
            <AppSelect
              v-model="statusData.refused_reason_id"
              label="Reason (Tag)"
              :items="filteredReasons"
              item-title="reason"
              item-value="id"
              clearable
            />
          </VCol>

          <VCol cols="12">
            <AppTextField
              v-model="statusData.reason"
              label="Note / Custom Reason"
              placeholder="Enter details..."
            />
          </VCol>
          
          <VCol cols="12" v-if="statusData.refused_reason_id && filteredReasons.find((r: any) => r.id === statusData.refused_reason_id)?.is_edit_amount">
            <AppTextField
              v-model="statusData.total_amount"
              label="Force Total Amount (Optional)"
              type="number"
              hint="Only use if you want to override all selected orders amounts"
              persistent-hint
            />
          </VCol>
        </VRow>
      </VCardText>

      <VCardActions class="pb-6 px-6">
        <VSpacer />
        <VBtn color="secondary" variant="tonal" @click="emit('update:isDialogVisible', false)">Cancel</VBtn>
        <VBtn variant="elevated" color="primary" @click="onSubmit" :loading="isLoading">Apply Bulk Status</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
