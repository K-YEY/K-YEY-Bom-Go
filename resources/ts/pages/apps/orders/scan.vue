<script setup lang="ts">
import { useApi } from '@/composables/useApi'

// 👉 Scanned Orders
const scannedOrders = ref<any[]>([])
const barcodeInput = ref('')
const isLoading = ref(false)
const inputRef = ref<any>(null)

// 👉 Sound Effects (From public directory)
const playSuccessSound = () => {
  const audio = new Audio('/scanner.mp3')
  audio.play().catch(e => console.error('Audio play failed:', e))
}

const playErrorSound = () => {
  const audio = new Audio('/error.mp3')
  audio.play().catch(e => console.error('Audio play failed:', e))
}

// 👉 Handle Scan
const onScan = async () => {
  const code = barcodeInput.value.trim()
  if (!code) return

  // Prevent duplicate in current session table
  if (scannedOrders.value.some(o => o.code === code || o.id.toString() === code || o.external_code === code)) {
    barcodeInput.value = ''
    playErrorSound()
    return
  }

  isLoading.value = true
  try {
    const { data, error } = await useApi<any>('/orders/scan').post({
      code: code,
      external_code: code 
    }).json()

    if (error.value) {
      playErrorSound()
    } else if (data.value?.data) {
      const order = data.value.data
      
      // Check if order is already collected by shipper
      if (order.is_shipper_collected) {
        barcodeInput.value = ''
        playErrorSound()
        alert(`Order #${order.id} is already collected by shipper and cannot be edited.`)
        return
      }

      // Add to TOP of table
      scannedOrders.value.unshift(order)
      playSuccessSound()
    }
  } catch (e) {
    playErrorSound()
  } finally {
    barcodeInput.value = ''
    isLoading.value = false
    nextTick(() => {
      inputRef.value?.focus()
    })
  }
}

// 👉 Table Headers
const headers = [
  { title: 'Order ID', key: 'id' },
  { title: 'Code', key: 'code' },
  { title: 'Receiver', key: 'receiver_name' },
  { title: 'City', key: 'city.name' },
  { title: 'Amount', key: 'total_amount' },
  { title: 'Status', key: 'status' },
  { title: 'Action', key: 'actions', sortable: false },
]

const statusColors: any = {
  OUT_FOR_DELIVERY: 'primary',
  DELIVERED: 'success',
  HOLD: 'warning',
  UNDELIVERED: 'error',
}

const removeOrder = (id: number) => {
  scannedOrders.value = scannedOrders.value.filter(o => o.id !== id)
}

const clearAll = () => {
  scannedOrders.value = []
}

// 👉 Action Logic
const actionType = ref<'status' | 'shipper' | 'view'>('view')
const selectedStatus = ref<string | null>(null)
const selectedShipper = ref<number | null>(null)
const isActionProcessing = ref(false)

// 👉 Shippers for dropdown
const { data: shippersData } = await useApi<any>('/shippers').get().json()
const shippers = computed(() => {
  const data = Array.isArray(shippersData.value) ? shippersData.value : []
  return data.map((s: any) => ({
    name: s.user?.name || 'Unknown',
    user_id: s.user_id,
  }))
})

const applyActionToAll = async () => {
  if (!scannedOrders.value.length) return
  if (actionType.value === 'view') return
  
  const orderIds = scannedOrders.value
    .filter(o => !o.is_shipper_collected)
    .map(o => o.id)

  if (orderIds.length === 0) {
    alert('No valid orders (orders collected by shipper are locked).')
    return
  }

  isActionProcessing.value = true

  try {
    if (actionType.value === 'status') {
      if (!selectedStatus.value) return
      
      const { error } = await useApi('/orders/bulk-change-status').post({
        order_ids: orderIds,
        status: selectedStatus.value,
      }).json()

      if (!error.value) {
        alert('Status updated successfully for all orders')
        clearAll()
      }
    } else if (actionType.value === 'shipper') {
      if (!selectedShipper.value) return
      
      const { error } = await useApi('/orders/bulk-change-shipper').post({
        order_ids: orderIds,
        shipper_user_id: selectedShipper.value,
      }).json()

      if (!error.value) {
        alert('Shipper updated successfully for all orders')
        clearAll()
      }
    }
  } catch (e) {
    console.error(e)
  } finally {
    isActionProcessing.value = false
  }
}

// 👉 Row actions (Approve / Reject)
const approveOrder = async (order: any) => {
  try {
    const { error } = await useApi(`/orders/${order.id}/approve`).patch().json()
    if (!error.value) {
      order.approval_status = 'APPROVED'
      playSuccessSound()
    } else {
      playErrorSound()
    }
  } catch (e) {
    playErrorSound()
  }
}

const rejectOrder = async (order: any) => {
  try {
    const { error } = await useApi(`/orders/${order.id}/reject`).patch().json()
    if (!error.value) {
      order.approval_status = 'REJECTED'
      playSuccessSound()
    } else {
      playErrorSound()
    }
  } catch (e) {
    playErrorSound()
  }
}

// Autofocus on mount
onMounted(() => {
  nextTick(() => {
    inputRef.value?.focus()
  })
})

const handleGlobalClick = () => {
  inputRef.value?.focus()
}
</script>

<template>
  <VRow>
    <!-- 👉 Header & Stats -->
    <VCol cols="12">
      <div class="d-flex align-center justify-space-between flex-wrap gap-4 mb-4">
        <div>
          <h4 class="text-h4 mb-1">Order Barcode Scanner</h4>
          <p class="text-body-1 mb-0">Scan orders to build a list and perform bulk actions.</p>
        </div>
        <div class="d-flex gap-4">
          <VCard variant="tonal" color="info" class="pa-4 text-center" style="min-inline-size: 130px;">
            <div class="text-h5 font-weight-bold">{{ scannedOrders.length }}</div>
            <div class="text-caption">Scanned Orders</div>
          </VCard>
          <VCard variant="tonal" color="success" class="pa-4 text-center" style="min-inline-size: 160px;">
            <div class="text-h5 font-weight-bold">
              {{ scannedOrders.reduce((acc, curr) => acc + Number(curr.total_amount || 0), 0).toFixed(2) }}
            </div>
            <div class="text-caption">Total Amount (EGP)</div>
          </VCard>
        </div>
      </div>
    </VCol>

    <!-- 👉 Scanner Input -->
    <VCol cols="12">
      <VCard @click="handleGlobalClick">
        <VCardText>
          <VRow align="center">
            <VCol cols="12">
              <AppTextField
                ref="inputRef"
                v-model="barcodeInput"
                placeholder="Scan Barcode Here..."
                prepend-inner-icon="tabler-scan"
                class="scanner-input"
                :loading="isLoading"
                autofocus
                @keydown.enter="onScan"
              />
              <p class="text-xs text-secondary mt-2 mb-0">
                <VIcon icon="tabler-info-circle" size="14" class="me-1" />
                Input is automatically focused after each scan.
              </p>
            </VCol>
          </VRow>
        </VCardText>
      </VCard>
    </VCol>

    <!-- 👉 Actions Card -->
    <VCol cols="12">
      <VCard title="Bulk Actions">
        <VCardText>
          <VRow align="center">
            <VCol cols="12" md="4">
              <VLabel class="mb-2">Select Action Type</VLabel>
              <VRadioGroup v-model="actionType" inline>
                <VRadio label="View Only" value="view" />
                <VRadio label="Update Status" value="status" />
                <VRadio label="Change Shipper" value="shipper" />
              </VRadioGroup>
            </VCol>

            <VCol v-if="actionType === 'status'" cols="12" md="4">
              <AppSelect
                v-model="selectedStatus"
                label="Choose Status"
                :items="['DELIVERED', 'UNDELIVERED', 'OUT_FOR_DELIVERY', 'HOLD']"
                placeholder="Select status"
                clearable
              />
            </VCol>

            <VCol v-if="actionType === 'shipper'" cols="12" md="4">
              <AppSelect
                v-model="selectedShipper"
                label="Choose Shipper (المندوب)"
                :items="shippers"
                item-title="name"
                item-value="user_id"
                placeholder="Select shipper"
                clearable
              />
            </VCol>

            <VCol cols="12" md="4" :class="actionType === 'view' ? 'offset-md-4' : ''" class="d-flex align-end justify-end gap-2">
              <VBtn 
                v-if="actionType !== 'view'"
                color="primary" 
                :loading="isActionProcessing"
                :disabled="!scannedOrders.length"
                @click="applyActionToAll"
              >
                Apply to All Scanned
              </VBtn>
              <VBtn 
                color="secondary" 
                variant="outlined" 
                :disabled="!scannedOrders.length"
                @click="clearAll"
              >
                Clear List
              </VBtn>
            </VCol>
          </VRow>
        </VCardText>
      </VCard>
    </VCol>

    <!-- 👉 Scanned Orders Table -->
    <VCol cols="12">
      <VCard>
        <VDataTable
          :headers="headers"
          :items="scannedOrders"
          item-value="id"
          class="text-no-wrap"
          :items-per-page="50"
        >
          <!-- ID -->
          <template #item.id="{ item }">
            <span class="text-primary font-weight-bold">#{{ item.id }}</span>
          </template>

          <template #item.receiver_name="{ item }">
            <div class="d-flex flex-column">
              <span class="text-high-emphasis font-weight-medium text-sm">{{ item.receiver_name }}</span>
              <span class="text-xs text-secondary">{{ item.phone }}</span>
            </div>
          </template>

          <template #item.status="{ item }">
            <VChip size="small" :color="statusColors[item.status]" variant="tonal" class="text-capitalize">
              {{ item.status.replace(/_/g, ' ') }}
            </VChip>
          </template>

          <template #item.total_amount="{ item }">
            <span class="font-weight-bold">{{ item.total_amount }} EGP</span>
          </template>

          <template #item.actions="{ item }">
            <div class="d-flex align-center gap-1">
              <template v-if="item.approval_status === 'PENDING'">
                <VBtn size="x-small" color="success" variant="elevated" @click="approveOrder(item)">
                  Approve
                </VBtn>
                <VBtn size="x-small" color="error" variant="tonal" @click="rejectOrder(item)">
                  Reject
                </VBtn>
                <VDivider vertical class="mx-1" />
              </template>
              <IconBtn size="small" color="secondary" @click="removeOrder(item.id)">
                <VIcon icon="tabler-trash" />
                <VTooltip activator="parent">Remove from list</VTooltip>
              </IconBtn>
            </div>
          </template>

          <template #no-data>
            <div class="py-12 text-center text-disabled">
              <VIcon size="64" icon="tabler-barcode-off" class="mb-4 opacity-50" />
              <div class="text-h6">No orders scanned.</div>
            </div>
          </template>
        </VDataTable>
      </VCard>
    </VCol>
  </VRow>
</template>

<style scoped>
.scanner-input :deep(.v-field__input) {
  font-size: 1.6rem !important;
  font-weight: 700;
  color: var(--v-theme-primary);
  letter-spacing: 2px;
  text-align: center;
}
</style>

<route lang="yaml">
meta:
  action: manage
  subject: order.scan.page
</route>
