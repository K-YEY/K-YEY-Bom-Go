<script setup lang="ts">
import { useApi } from '@/composables/useApi';

interface Props {
  isDialogVisible: boolean
}

const props = defineProps<Props>()
const emit = defineEmits(['update:isDialogVisible', 'settlementCreated'])

const loading = ref(false)
const fetchingOrders = ref(false)
const errorMessages = ref<string[]>([])

const formData = ref({
  client_user_id: null as number | null,
  settlement_date: new Date().toISOString().substr(0, 10),
})

const selectedOrders = ref<any[]>([])

// 👉 Clients
const { data: clientsData } = await useApi<any>('/clients?eligible_for=settlement&per_page=100').get().json()
const clients = computed(() => {
  let raw = clientsData.value
  const data = Array.isArray(raw)
    ? raw
    : (raw && Array.isArray(raw.data) ? raw.data : [])
  return data.map((c: any) => ({
    ...c,
    name: c.user?.name || 'Unknown',
    user_id: c.user_id || c.id,
  }))
})

// 👉 Eligible Orders
const eligibleOrders = ref<any[]>([])

const fetchEligibleOrders = async () => {
  if (!formData.value.client_user_id) {
    eligibleOrders.value = []
    return
  }

  fetchingOrders.value = true
  const { data } = await useApi<any>(`/client-settlements/eligible-orders?client_user_id=${formData.value.client_user_id}&per_page=100`).get().json()
  eligibleOrders.value = data.value?.data || []
  fetchingOrders.value = false
}

watch(() => formData.value.client_user_id, fetchEligibleOrders)

// 👉 Calculations
const totalAmount = computed(() => {
  if (!selectedOrders.value.length) return 0
  const sum = selectedOrders.value.reduce((acc, id) => {
    const order = eligibleOrders.value.find(o => o.id === id)
    const val = Number(order?.total_amount)
    return acc + (isNaN(val) ? 0 : val)
  }, 0)
  return parseFloat(sum.toFixed(2))
})

const totalFees = computed(() => {
  if (!selectedOrders.value.length) return 0
  const sum = selectedOrders.value.reduce((acc, id) => {
    const order = eligibleOrders.value.find(o => o.id === id)
    const val = Number(order?.shipping_fee)
    return acc + (isNaN(val) ? 0 : val)
  }, 0)
  return parseFloat(sum.toFixed(2))
})

const netAmount = computed(() => {
  if (!selectedOrders.value.length) return 0
  const sum = selectedOrders.value.reduce((acc, id) => {
    const order = eligibleOrders.value.find(o => o.id === id)
    const val = Number(order?.cod_amount)
    return acc + (isNaN(val) ? 0 : val)
  }, 0)
  return parseFloat(sum.toFixed(2))
})

const headers = [
  { title: 'Order ID', key: 'id' },
  { title: 'Receiver', key: 'receiver_name' },
  { title: 'Amount', key: 'total_amount' },
  { title: 'Fee', key: 'shipping_fee' },
  { title: 'COD', key: 'cod_amount' },
]

const onSubmit = async () => {
  if (!formData.value.client_user_id || selectedOrders.value.length === 0) {
    errorMessages.value = ['Please select a client and at least one order.']
    return
  }

  loading.value = true
  errorMessages.value = []

  try {
    const orderIds = Array.from(new Set(selectedOrders.value)).map(id => Number(id)).filter(id => Number.isInteger(id))

    const { data, error } = await useApi('/client-settlements').post({
      client_user_id: formData.value.client_user_id,
      settlement_date: formData.value.settlement_date,
      total_amount: totalAmount.value,
      number_of_orders: orderIds.length,
      fees: totalFees.value,
      order_ids: orderIds,
    }).json()

    if (error.value) {
      if (error.value.data?.errors) {
        errorMessages.value = Object.values(error.value.data.errors).flat() as string[]
      } else {
        errorMessages.value = ['Failed to create settlement']
      }
    } else {
      emit('settlementCreated')
      emit('update:isDialogVisible', false)
      formData.value.client_user_id = null
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
    <VCard title="Create Client Settlement">
      <VCardText>
        <VAlert v-if="errorMessages.length" type="error" variant="tonal" closable class="mb-4">
          <ul class="ms-4 mb-0">
            <li v-for="msg in errorMessages" :key="msg">{{ msg }}</li>
          </ul>
        </VAlert>

        <VRow>
          <VCol cols="12" md="6">
            <AppSelect
              v-model="formData.client_user_id"
              label="Select Client"
              placeholder="Choose a client"
              :items="clients"
              item-title="name"
              item-value="user_id"
              clearable
            />
          </VCol>
          <VCol cols="12" md="6">
            <AppTextField
              v-model="formData.settlement_date"
              label="Settlement Date"
              type="date"
            />
          </VCol>
        </VRow>

        <VDivider class="my-6" />

        <div class="d-flex justify-space-between align-center mb-4">
          <div class="text-h6">Eligible Orders</div>
          <div v-if="selectedOrders.length > 0" class="text-primary font-weight-bold">
            Selected: {{ selectedOrders.length }} | Total: {{ totalAmount }} EGP | Fees: {{ totalFees }} EGP | Net: {{ netAmount }} EGP
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
        >
          <template #item.receiver_name="{ item }">
            <div>{{ item.receiver_name || '-' }}</div>
            <div class="text-caption text-disabled">{{ item.phone || '' }}</div>
          </template>
          <template #item.total_amount="{ item }">
            {{ item.total_amount }} EGP
          </template>
          <template #item.shipping_fee="{ item }">
            <span class="text-error">{{ item.shipping_fee }} EGP</span>
          </template>
          <template #item.cod_amount="{ item }">
            <span class="text-success font-weight-bold">{{ item.cod_amount }} EGP</span>
          </template>
        </VDataTable>
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
          Create Settlement
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
