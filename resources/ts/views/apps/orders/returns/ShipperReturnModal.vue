<script setup lang="ts">
import { useApi } from '@/composables/useApi';

interface Props {
  isDialogVisible: boolean
}

const props = defineProps<Props>()
const emit = defineEmits(['update:isDialogVisible', 'returnCreated'])

const loading = ref(false)
const fetchingOrders = ref(false)
const errorMessages = ref<string[]>([])

const formData = ref({
  shipper_user_id: null as number | null,
  return_date: new Date().toISOString().substr(0, 10),
  notes: '',
})

const selectedOrders = ref<any[]>([])

// 👉 Shippers
const { data: shippersData } = await useApi<any>('/shippers?eligible_for=return&per_page=100').get().json()
const shippers = computed(() => {
  let raw = shippersData.value
  const data = Array.isArray(raw)
    ? raw
    : (raw && Array.isArray(raw.data) ? raw.data : [])
  return data.map((s: any) => ({
    ...s,
    name: s.user?.name || 'Unknown',
    user_id: s.user_id || s.id,
  }))
})

// 👉 Eligible Orders
const eligibleOrders = ref<any[]>([])

const fetchEligibleOrders = async () => {
  if (!formData.value.shipper_user_id) {
    eligibleOrders.value = []
    return
  }

  fetchingOrders.value = true
  const { data } = await useApi<any>(`/shipper-returns/eligible-orders?shipper_user_id=${formData.value.shipper_user_id}&per_page=100`).get().json()
  eligibleOrders.value = data.value?.data || []
  fetchingOrders.value = false
}

watch(() => formData.value.shipper_user_id, fetchEligibleOrders)

const headers = [
  { title: 'Order ID', key: 'id' },
  { title: 'Client', key: 'client_name' },
  { title: 'Code', key: 'code' },
  { title: 'Status', key: 'status' },
]

const onSubmit = async () => {
  if (!formData.value.shipper_user_id || selectedOrders.value.length === 0) {
    errorMessages.value = ['Please select a shipper and at least one order.']
    return
  }

  loading.value = true
  errorMessages.value = []

  try {
    const orderIds = Array.from(new Set(selectedOrders.value)).map(id => Number(id)).filter(id => Number.isInteger(id))

    const { data, error } = await useApi('/shipper-returns').post({
      shipper_user_id: formData.value.shipper_user_id,
      return_date: formData.value.return_date,
      number_of_orders: orderIds.length,
      notes: formData.value.notes || null,
      order_ids: orderIds,
    }).json()

    if (error.value) {
      if (error.value.data?.errors) {
        errorMessages.value = Object.values(error.value.data.errors).flat() as string[]
      } else {
        errorMessages.value = ['Failed to create return']
      }
    } else {
      emit('returnCreated')
      emit('update:isDialogVisible', false)
      formData.value.shipper_user_id = null
      formData.value.notes = ''
      selectedOrders.value = []
    }
  } catch (e) {
    errorMessages.value = ['An error occurred.']
  }
  loading.value = false
}
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    max-width="900"
    @update:model-value="val => emit('update:isDialogVisible', val)"
  >
    <VCard title="Create Shipper Return">
      <VCardText>
        <VAlert v-if="errorMessages.length" type="error" variant="tonal" closable class="mb-4">
          <ul class="ms-4 mb-0">
            <li v-for="msg in errorMessages" :key="msg">{{ msg }}</li>
          </ul>
        </VAlert>

        <VRow>
          <VCol cols="12" md="4">
            <AppSelect
              v-model="formData.shipper_user_id"
              label="Select Shipper"
              placeholder="Choose a shipper"
              :items="shippers"
              item-title="name"
              item-value="user_id"
              clearable
            />
          </VCol>
          <VCol cols="12" md="4">
            <AppTextField
              v-model="formData.return_date"
              label="Return Date"
              type="date"
            />
          </VCol>
          <VCol cols="12" md="4">
            <AppTextField
              v-model="formData.notes"
              label="Notes (Optional)"
              placeholder="Add notes..."
            />
          </VCol>
        </VRow>

        <VDivider class="my-6" />

        <div class="d-flex justify-space-between align-center mb-4">
          <div class="text-h6">Eligible Orders</div>
          <div v-if="selectedOrders.length > 0" class="text-primary font-weight-bold">
            Selected: {{ selectedOrders.length }}
          </div>
        </div>

        <VDataTable
          v-model="selectedOrders"
          :headers="headers"
          :items="eligibleOrders"
          :loading="fetchingOrders"
          item-value="id"
          show-select
          class="text-no-wrap border rounded"
          fixed-header
          style="max-block-size: 400px;"
        />
      </VCardText>

      <VCardActions class="pb-6 px-6">
        <VSpacer />
        <VBtn color="secondary" variant="tonal" @click="emit('update:isDialogVisible', false)">
          Cancel
        </VBtn>
        <VBtn
          variant="elevated"
          color="primary"
          :loading="loading"
          :disabled="selectedOrders.length === 0"
          @click="onSubmit"
        >
          Create Return
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
