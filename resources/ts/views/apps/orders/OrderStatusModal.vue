<script setup lang="ts">
import { useApi } from '@/composables/useApi';

interface Props {
  isDialogVisible: boolean
  order: any
  metadata?: any
}

const props = defineProps<Props>()
const emit = defineEmits(['update:isDialogVisible', 'statusUpdated'])

const statusData = ref({
  status: '',
  selectedReasonIds: [] as number[],
  customReason: '',
  total_amount: 0,
  has_return: false,
})

const reasonsLoading = ref(false)
const reasonsData = ref<any>(null)
const allReasons = computed(() => Array.isArray(reasonsData.value) ? reasonsData.value : (reasonsData.value?.data || []))

const fetchReasons = async () => {
  if (props.metadata?.refused_reasons) {
    reasonsData.value = props.metadata.refused_reasons
    return
  }
  reasonsLoading.value = true
  const { data } = await useApi<any>('/refused-reasons').get().json()
  reasonsData.value = data.value
  reasonsLoading.value = false
}

onMounted(() => {
  fetchReasons()
})

const filteredReasons = computed(() => {
  return allReasons.value.filter((r: any) => r.is_active && r.status === statusData.value.status)
})

const selectedReasons = computed(() => {
  return allReasons.value.filter((r: any) => statusData.value.selectedReasonIds.includes(r.id))
})

const showEditAmount = computed(() => {
  return selectedReasons.value.some((r: any) => r.is_edit_amount)
})

watch(() => props.order, (newVal) => {
  if (newVal) {
    statusData.value = {
      status: newVal.status,
      selectedReasonIds: [], 
      customReason: '',
      total_amount: newVal.total_amount,
      has_return: newVal.has_return || false,
    }
  }
}, { immediate: true })

const toggleReason = (reason: any) => {
  const index = statusData.value.selectedReasonIds.indexOf(reason.id)
  
  if (index > -1) {
    statusData.value.selectedReasonIds.splice(index, 1)
  } else {
    if (reason.is_clear) {
      statusData.value.selectedReasonIds = [reason.id]
    } else {
      // Remove any is_clear reasons
      statusData.value.selectedReasonIds = statusData.value.selectedReasonIds.filter(id => {
        const r = allReasons.value.find((ar: any) => ar.id === id)
        return !r?.is_clear
      })
      statusData.value.selectedReasonIds.push(reason.id)
    }
  }
}

const onSubmit = async () => {
  if (!props.order?.id) return

  const payload: any = {
    status: statusData.value.status,
    refused_reason_ids: statusData.value.selectedReasonIds,
    reason: statusData.value.customReason,
  }

  if (showEditAmount.value) {
    payload.total_amount = statusData.value.total_amount
  }

  if (statusData.value.status === 'DELIVERED') {
    payload.has_return = statusData.value.has_return
  }

  const { error } = await useApi(`/orders/${props.order.id}/change-status`).patch(payload).json()

  if (!error.value) {
    emit('statusUpdated')
    emit('update:isDialogVisible', false)
  }
}
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    max-width="600"
    @update:model-value="val => emit('update:isDialogVisible', val)"
  >
    <VCard title="Update Order Status">
      <VCardText v-if="props.order">
        <VRow>
          <VCol cols="12">
            <AppSelect
              v-model="statusData.status"
              label="Select Status"
              :items="[
                { title: 'Out for delivery', value: 'OUT_FOR_DELIVERY' },
                { title: 'Delivered', value: 'DELIVERED' },
                { title: 'On hold', value: 'HOLD' },
                { title: 'Undelivered', value: 'UNDELIVERED' },
              ]"
              @update:model-value="statusData.selectedReasonIds = []"
            />
          </VCol>

          <!-- Reasons Tags -->
          <VCol cols="12" v-if="filteredReasons.length">
            <p class="text-sm mb-2">Select Reasons:</p>
            <div class="d-flex flex-wrap gap-2">
              <VChip
                v-for="reason in filteredReasons"
                :key="reason.id"
                :color="statusData.selectedReasonIds.includes(reason.id) ? 'primary' : 'secondary'"
                :variant="statusData.selectedReasonIds.includes(reason.id) ? 'elevated' : 'tonal'"
                class="cursor-pointer"
                @click="toggleReason(reason)"
              >
                {{ reason.reason }}
                <VIcon v-if="reason.is_return" end icon="tabler-rotate" size="14" class="ms-1" />
                <VIcon v-if="reason.is_clear" end icon="tabler-trash" size="14" class="ms-1" />
                <VIcon v-if="reason.is_edit_amount" end icon="tabler-pencil" size="14" class="ms-1" />
              </VChip>
            </div>
          </VCol>

          <!-- Custom Reason -->
          <VCol cols="12">
            <AppTextField
              v-model="statusData.customReason"
              label="Custom Reason / Note"
              placeholder="Enter additional details..."
            />
          </VCol>

          <!-- Edit Amount if allowed -->
          <VCol cols="12" v-if="showEditAmount">
            <AppTextField
              v-model="statusData.total_amount"
              label="Update Total Amount"
              type="number"
            />
          </VCol>

          <!-- Has Return Toggle for Delivered -->
          <VCol cols="12" v-if="statusData.status === 'DELIVERED'">
            <div class="d-flex align-center gap-2">
              <VSwitch
                v-model="statusData.has_return"
                label="Has Return Package"
              />
              <VIcon icon="tabler-arrow-back-up" color="warning" v-if="statusData.has_return" />
            </div>
          </VCol>
        </VRow>
      </VCardText>
      <VCardActions class="pb-6 px-6">
        <VSpacer />
        <VBtn
          color="secondary"
          variant="tonal"
          @click="emit('update:isDialogVisible', false)"
        >
          Cancel
        </VBtn>
        <VBtn
           variant="elevated"
          color="primary"
          @click="onSubmit"
        >
          Update Status
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
