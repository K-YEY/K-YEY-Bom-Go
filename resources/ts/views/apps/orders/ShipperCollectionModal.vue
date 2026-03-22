

// دالة لتغيير حالة المجموعة (status)
const changeStatus = async (newStatus: string) => {
  if (!formData.value.shipper_user_id) {
    errorMessages.value = ['Please select a shipper first.']
    return
  }
  loading.value = true
  errorMessages.value = []
  try {
    // هنا يجب أن يكون لديك collectionId أو طريقة لتحديد المجموعة
    // هذا مثال توضيحي: عدل collectionId حسب السياق الفعلي
    const collectionId = formData.value.collection_id || 1
    const { data, error } = await useApi(`/shipper-collections/${collectionId}`).patch({
      status: newStatus
    }).json()
    if (error.value) {
      errorMessages.value = [error.value.message || 'Failed to change status']
    } else {
      errorMessages.value = ['Status updated successfully!']
    }
  } catch (e) {
    errorMessages.value = ['An error occurred while changing status.']
  }
  loading.value = false
}
<script setup lang="ts">import { useApi } from '@/composables/useApi';


interface Props {
  isDialogVisible: boolean
}

const props = defineProps<Props>()
const emit = defineEmits(['update:isDialogVisible', 'collectionCreated'])

const loading = ref(false)
const fetchingOrders = ref(false)
const errorMessages = ref<string[]>([])

const formData = ref({
  shipper_user_id: null as number | null,
  collection_date: new Date().toISOString().substr(0, 10),
})

const selectedOrders = ref<any[]>([])

// 👉 Shippers
const { data: shippersData } = await useApi<any>('/shippers').get().json()
const shippers = computed(() => {
  const data = Array.isArray(shippersData.value) ? shippersData.value : []
  return data.map((s: any) => ({
    ...s,
    name: s.user?.name || 'Unknown',
    user_id: s.user_id || s.id // Ensure we have the user_id for selection
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
  const { data } = await useApi<any>(`/shipper-collections/eligible-orders?shipper_user_id=${formData.value.shipper_user_id}&per_page=100`).get().json()
  eligibleOrders.value = data.value?.data || []
  fetchingOrders.value = false
}

watch(() => formData.value.shipper_user_id, fetchEligibleOrders)

// 👉 Calculations
// يدعم selectedOrders كأرقام أو كائنات
const totalAmount = computed(() => {
  if (!selectedOrders.value.length) return 0
  let sum = 0
  if (typeof selectedOrders.value[0] === 'number') {
    sum = selectedOrders.value.reduce((acc, id) => {
      const order = eligibleOrders.value.find(o => o.id === id)
      const val = Number(order?.total_amount)
      return acc + (isNaN(val) ? 0 : val)
    }, 0)
  } else {
    sum = selectedOrders.value.reduce((acc, order) => {
      const val = Number(order?.total_amount)
      return acc + (isNaN(val) ? 0 : val)
    }, 0)
  }
  return parseFloat(sum.toFixed(2))
})
const netAmount = computed(() => {
  if (!selectedOrders.value.length) return 0
  let sum = 0
  if (typeof selectedOrders.value[0] === 'number') {
    sum = selectedOrders.value.reduce((acc, id) => {
      const order = eligibleOrders.value.find(o => o.id === id)
      const val = Number(order?.collection_amount)
      return acc + (isNaN(val) ? 0 : val)
    }, 0)
  } else {
    sum = selectedOrders.value.reduce((acc, order) => {
      const val = Number(order?.collection_amount)
      return acc + (isNaN(val) ? 0 : val)
    }, 0)
  }
  return parseFloat(sum.toFixed(2))
})

const headers = [
  { title: 'Order ID', key: 'id' },
  { title: 'Client', key: 'client_name' },
  { title: 'Amount', key: 'total_amount' },
  { title: 'Fees', key: 'commission_amount' },
  { title: 'Net', key: 'collection_amount' },
]

const onSubmit = async () => {
  if (!formData.value.shipper_user_id || selectedOrders.value.length === 0) {
    errorMessages.value = ['Please select a shipper and at least one order.']
    return
  }

  loading.value = true
  errorMessages.value = []

  try {
    // Convert order ids to unique integers
    console.log('selectedOrders:', selectedOrders.value)
    // إذا كانت selectedOrders عبارة عن أرقام مباشرة (IDs)
    const orderIds = Array.from(new Set(selectedOrders.value)).map(id => Number(id)).filter(id => Number.isInteger(id))
    console.log('orderIds to send:', orderIds)
    const { data, error } = await useApi('/shipper-collections').post({
      shipper_user_id: formData.value.shipper_user_id,
      collection_date: formData.value.collection_date,
      order_ids: orderIds,
    }).json()

    if (error.value) {
      // Debug: log all possible error details
      console.log('API Error (raw):', error.value)
      if (error.value.data) {
        console.log('API Error data:', error.value.data)
      }
      if (error.value.data?.errors) {
        console.log('API Error data.errors:', error.value.data.errors)
      }
      if (error.value.response) {
        console.log('API Error response:', error.value.response)
      }
      // Try to extract Laravel validation errors
      if (error.value.data && error.value.data.errors) {
        // Flatten all error messages into a single array
        errorMessages.value = Object.values(error.value.data.errors).flat()
      } else if (error.value.message) {
        errorMessages.value = [`Failed to create collection: ${error.value.message}`]
      } else {
        errorMessages.value = ['Failed to create collection']
      }
    } else {
      emit('collectionCreated')
      emit('update:isDialogVisible', false)
      // Reset form
      formData.value.shipper_user_id = null
      selectedOrders.value = []
    }
  } catch (e) {
    // Try to extract error messages from exception
    if (e?.response?.data?.errors) {
      errorMessages.value = Object.values(e.response.data.errors).flat()
    } else {
      errorMessages.value = ['An error occurred.']
    }
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
    <VCard title="Create Shipper Collection">
      <VCardText>
        <VAlert
          v-if="errorMessages.length"
          type="error"
          variant="tonal"
          closable
          class="mb-4"
        >
          <ul class="ms-4 mb-0">
            <li v-for="msg in errorMessages" :key="msg">{{ msg }}</li>
          </ul>
        </VAlert>

        <VRow>
          <VCol cols="12" md="6">
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
          <VCol cols="12" md="6">
            <AppTextField
              v-model="formData.collection_date"
              label="Collection Date"
              type="date"
            />
          </VCol>
        </VRow>

        <VDivider class="my-6" />

        <div class="d-flex justify-space-between align-center mb-4">
          <div class="text-h6">Eligible Orders</div>
          <div v-if="selectedOrders.length > 0" class="text-primary font-weight-bold">
            Selected: {{ selectedOrders.length }} | Total: {{ totalAmount }} EGP | Net: {{ netAmount }} EGP
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
          <template #item.total_amount="{ item }">
            {{ item.total_amount }} EGP
          </template>
          <template #item.commission_amount="{ item }">
            <span class="text-error">{{ item.commission_amount }} EGP</span>
          </template>
          <template #item.collection_amount="{ item }">
            <span class="text-success font-weight-bold">{{ item.collection_amount }} EGP</span>
          </template>
        </VDataTable>
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
          :loading="loading"
          :disabled="selectedOrders.length === 0"
          @click="onSubmit"
        >
          Create Collection
        </VBtn>
        <!-- زر تغيير الحالة مخفي في شاشة الإنشاء -->
      </VCardActions>
    </VCard>
  </VDialog>
</template>
