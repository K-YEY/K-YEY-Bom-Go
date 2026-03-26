<script setup lang="ts">
import { useApi } from '@/composables/useApi';

interface Props {
  isDialogVisible: boolean
  orderId?: number | null
  metadata?: any
}

const props = defineProps<Props>()
const emit = defineEmits(['update:isDialogVisible', 'orderSaved'])

const isFormValid = ref(false)
const refForm = ref()
const isLoading = ref(false)

const orderData = ref<any>({
  code: '',
  external_code: '',
  registered_at: new Date().toISOString().substr(0, 10),
  captain_date: null,
  receiver_name: '',
  phone: '',
  phone_2: '',
  address: '',
  governorate_id: null,
  city_id: null,
  total_amount: 0,
  shipping_fee: 0,
  commission_amount: 0,
  status: 'OUT_FOR_DELIVERY',
  shipper_user_id: null,
  client_user_id: null,
  shipping_content_id: null,
  allow_open: true,
  order_note: '',
})

// 👉 Computed Financials
const codAmount = computed(() => {
  return (Number(orderData.value.total_amount || 0) - Number(orderData.value.shipping_fee || 0)).toFixed(2)
})

const netAmount = computed(() => {
  return (Number(orderData.value.shipping_fee || 0) - Number(orderData.value.commission_amount || 0)).toFixed(2)
})

// 👉 Fetch Options
const governorates = ref<any[]>([])
const shippers = ref<any[]>([])
const clients = ref<any[]>([])
const contents = ref<any[]>([])
const plans = ref<any[]>([])

const fetchStaticData = async () => {
  // If parent provided metadata, use it and avoid network calls
  if (props.metadata && Object.keys(props.metadata).length > 0) {
    governorates.value = props.metadata.governorates || []
    plans.value = props.metadata.plans || []
    shippers.value = (props.metadata.shippers || []).map((s: any) => ({
      id: s.id,
      name: s.name,
      commission_rate: s.commission_rate
    }))
    clients.value = (props.metadata.clients || []).map((c: any) => ({
      id: c.id,
      name: c.name,
      plan_id: c.plan_id,
      shipping_content_id: c.shipping_content_id,
      shipping_fee: c.shipping_fee
    }))
    contents.value = props.metadata.contents || []
    return
  }

  try {
    const [govRes, shipRes, cliRes, conRes, planRes] = await Promise.all([
      useApi<any>('/governorates').get().json(),
      useApi<any>('/shippers').get().json(),
      useApi<any>('/clients').get().json(),
      useApi<any>('/contents').get().json(),
      useApi<any>('/plans').get().json(),
    ])

    governorates.value = govRes.data.value?.data || govRes.data.value || []
    plans.value = planRes.data.value?.data || planRes.data.value || []

    shippers.value = (shipRes.data.value?.data || shipRes.data.value || []).map((s: any) => ({
      id: s.user_id,
      name: s.user?.name || 'Unknown Shipper',
      commission_rate: s.commission_rate
    }))

    clients.value = (cliRes.data.value?.data || cliRes.data.value || []).map((c: any) => ({
      id: c.user_id,
      name: c.user?.name || 'Unknown Client',
      plan_id: c.plan_id,
      shipping_content_id: c.shipping_content_id,
      shipping_fee: c.shipping_fee
    }))

    contents.value = conRes.data.value?.data || conRes.data.value || []
  } catch (e) { console.error('Modal Static Data Fetch Error:', e) }
}

const filteredCities = computed(() => {
  const gov = governorates.value.find((g: any) => g.id === orderData.value.governorate_id)
  return gov?.cities || []
})

// 👉 Auto-fill logic based on Client/Gov/Shipper selection
watch([() => orderData.value.client_user_id, () => orderData.value.governorate_id], ([newClient, newGov]) => {
  if (props.orderId) return
  
  if (newClient) {
    const client = clients.value.find(c => c.id === newClient)
    if (client) {
      if (client.shipping_content_id && !orderData.value.shipping_content_id) orderData.value.shipping_content_id = client.shipping_content_id
      
      if (newGov && client.plan_id) {
        const plan = plans.value.find(p => p.id === client.plan_id)
        if (plan && plan.prices) {
          const priceObj = plan.prices.find((p: any) => p.governorate_id === newGov)
          if (priceObj) {
            orderData.value.shipping_fee = priceObj.price
          } else if (client.shipping_fee) {
            orderData.value.shipping_fee = client.shipping_fee
          }
        }
      }
    }
  }
})

watch(() => orderData.value.shipper_user_id, (newShipper) => {
  if (props.orderId || !newShipper) return
  const shipper = shippers.value.find(s => s.id === newShipper)
  if (shipper && shipper.commission_rate !== undefined) {
    orderData.value.commission_amount = shipper.commission_rate
  }
})

watch(() => props.orderId, async (newVal) => {
  if (newVal) {
    isLoading.value = true
    try {
      const { data } = await useApi<any>(`/orders/${newVal}`).get().json()
      if (data.value) {
        orderData.value = { ...data.value }
        if (orderData.value.registered_at) orderData.value.registered_at = orderData.value.registered_at.substr(0, 10)
        if (orderData.value.captain_date) orderData.value.captain_date = orderData.value.captain_date.substr(0, 10)
      }
    } catch (e) { console.error('Edit order fetch error:', e) }
    isLoading.value = false
  } else {
    orderData.value = {
      code: '', external_code: '', registered_at: new Date().toISOString().substr(0, 10), captain_date: null,
      receiver_name: '', phone: '', phone_2: '', address: '', governorate_id: null, city_id: null,
      total_amount: 0, shipping_fee: 0, commission_amount: 0, status: 'OUT_FOR_DELIVERY',
      shipper_user_id: null, client_user_id: null, shipping_content_id: null, allow_open: true, order_note: '',
    }
  }
}, { immediate: true })

onMounted(() => {
  fetchStaticData()
})

const onFormSubmit = async () => {
  const isValid = await refForm.value?.validate()
  if (!isValid.valid) return

  isLoading.value = true
  const method = props.orderId ? 'PUT' : 'POST'
  const url = props.orderId ? `/orders/${props.orderId}` : '/orders'

  try {
    const { error } = await useApi(url)[method.toLowerCase() as 'put' | 'post'](orderData.value).json()
    if (!error.value) {
      emit('orderSaved')
      emit('update:isDialogVisible', false)
    }
  } catch (e) { console.error('Form Submit Error:', e) }
  isLoading.value = false
}

const closeDialog = () => {
  emit('update:isDialogVisible', false)
}
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    max-width="850"
    persistent
    @update:model-value="val => emit('update:isDialogVisible', val)"
  >
    <VCard :loading="isLoading" :title="props.orderId ? 'Edit Order' : 'Add New Order'">
      <VCardText>
        <VForm
          ref="refForm"
          v-model="isFormValid"
          @submit.prevent="onFormSubmit"
        >
          <VRow>
            <!-- 1. Codes -->
            <VCol cols="12" md="6">
              <AppTextField v-model="orderData.code" label="Internal Code" disabled placeholder="Auto-generated" />
            </VCol>
            <VCol cols="12" md="6">
              <AppTextField v-model="orderData.external_code" label="External Code" placeholder="EXT-456" />
            </VCol>

            <VCol cols="12"><VDivider class="my-2" /></VCol>

            <!-- 2. Receiver Data -->
            <VCol cols="12" md="6">
              <AppTextField v-model="orderData.receiver_name" label="Receiver Name" required :rules="[(v: any) => !!v || 'Required']" />
            </VCol>
            <VCol cols="12" md="3">
              <AppTextField v-model="orderData.phone" label="Phone" required :rules="[(v: any) => !!v || 'Required']" />
            </VCol>
            <VCol cols="12" md="3">
              <AppTextField v-model="orderData.phone_2" label="Secondary Phone" />
            </VCol>

            <VCol cols="12"><VDivider class="my-2" /></VCol>

            <!-- 3. Address -->
            <VCol cols="12" md="4">
              <AppAutocomplete v-model="orderData.governorate_id" label="Governorate" :items="governorates" item-title="name" item-value="id" required :rules="[(v: any) => !!v || 'Required']" @update:model-value="orderData.city_id = null" />
            </VCol>
            <VCol cols="12" md="4">
              <AppAutocomplete v-model="orderData.city_id" label="City" :items="filteredCities" item-title="name" item-value="id" required :rules="[(v: any) => !!v || 'Required']" :disabled="!orderData.governorate_id" />
            </VCol>
            <VCol cols="12" md="4">
              <AppTextarea v-model="orderData.address" label="Street / Full Address" rows="1" auto-grow required :rules="[(v: any) => !!v || 'Required']" />
            </VCol>

            <VCol cols="12"><VDivider class="my-2" /></VCol>

            <!-- 4. Client & Shipper -->
            <VCol cols="12" md="6">
              <AppAutocomplete v-model="orderData.client_user_id" label="Client" :items="clients" item-title="name" item-value="id" required :rules="[(v: any) => !!v || 'Required']" />
            </VCol>
            <VCol cols="12" md="6">
              <AppAutocomplete v-model="orderData.shipper_user_id" label="Shipper" :items="shippers" item-title="name" item-value="id" clearable />
            </VCol>

            <VCol cols="12"><VDivider class="my-2" /></VCol>

            <!-- 5. Notes -->
            <VCol cols="12">
              <AppTextarea v-model="orderData.order_note" label="Order Notes" rows="2" />
            </VCol>

            <VCol cols="12"><VDivider class="my-2" /></VCol>

            <!-- 6. Others -->
            <VCol cols="12" md="3">
              <AppSelect v-model="orderData.status" label="Status" :items="[{ title: 'Out for delivery', value: 'OUT_FOR_DELIVERY' }, { title: 'Delivered', value: 'DELIVERED' }, { title: 'On hold', value: 'HOLD' }, { title: 'Undelivered', value: 'UNDELIVERED' }]" required :rules="[(v: any) => !!v || 'Required']" />
            </VCol>
            <VCol cols="12" md="3">
              <AppDateTimePicker v-model="orderData.captain_date" label="Assign Date" />
            </VCol>
            <VCol cols="12" md="4">
              <AppAutocomplete v-model="orderData.shipping_content_id" label="Shipping Content" :items="contents" item-title="name" item-value="id" clearable />
            </VCol>
            <VCol cols="12" md="2" class="d-flex align-center">
              <VCheckbox v-model="orderData.allow_open" label="Allow Open" hide-details />
            </VCol>

            <VCol cols="12"><VDivider class="my-4" /></VCol>

            <!-- 7. Financials (LAST) -->
            <VCol cols="12">
               <span class="text-overline mb-2 d-block">Financial Details</span>
            </VCol>
            
            <VCol cols="12" md="4">
              <AppTextField v-model="orderData.total_amount" label="TOTAL AMOUNT" type="number" required :rules="[(v: any) => !!v || 'Required']" />
            </VCol>
            <VCol cols="12" md="4">
              <AppTextField v-model="orderData.shipping_fee" label="SHIPPING FEE" type="number" />
            </VCol>
            <VCol cols="12" md="4">
              <AppTextField v-model="orderData.commission_amount" label="SHIPPER PRICE" type="number" />
            </VCol>

            <VCol cols="12" md="6">
              <AppTextField :model-value="codAmount" label="COD (Cash on Delivery)" disabled prefix="EGP" bg-color="success-lighten-5" />
            </VCol>
            <VCol cols="12" md="6">
              <AppTextField :model-value="netAmount" label="NET (Company Revenue)" disabled prefix="EGP" bg-color="info-lighten-5" />
            </VCol>
          </VRow>

          <VCardActions class="px-0 pt-6">
            <VSpacer />
            <VBtn color="secondary" variant="tonal" @click="closeDialog">Cancel</VBtn>
            <VBtn type="submit" color="primary" :loading="isLoading">{{ props.orderId ? 'Update' : 'Create' }}</VBtn>
          </VCardActions>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>
