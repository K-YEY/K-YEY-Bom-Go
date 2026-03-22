<script setup lang="ts">
import { useApi } from '@/composables/useApi'

const route = useRoute()
const id = (route.params as any).id

const data = ref<any>(null)
const settings = ref<any>(null)
const isLoading = ref(true)

const fetchData = async () => {
  const [orderRes, settingsRes] = await Promise.all([
    useApi<any>(`/orders/${id}/shipping-label`).get().json(),
    useApi<any>('/settings').get().json(),
  ])

  data.value = orderRes.data.value
  settings.value = settingsRes.data.value
  isLoading.value = false

  setTimeout(() => {
    window.print()
  }, 1000)
}

onMounted(fetchData)

definePage({
  meta: {
    layout: 'blank',
  },
})
</script>

<template>
  <div v-if="!isLoading && data" class="shipping-card-container" dir="rtl">
    <div class="shipping-card">
      <!-- Top Section -->
      <div class="d-flex justify-space-between align-center mb-6 header-row pb-4">
        <!-- QR/Barcode Area (Left) -->
        <div class="qr-barcode">
          <img :src="`https://quickchart.io/barcode?type=code128&text=${data.code}&height=50&width=200&scale=2`" alt="barcode" class="barcode-image" />
        </div>

        <!-- Title Area (Center) -->
        <div class="text-center flex-grow-1 px-4">
          <h1 class="receipt-title mb-1">بوليصة شحن</h1>
          <div class="receipt-no">رقم: {{ data.code }}</div>
        </div>

        <!-- Company Info (Right) -->
        <div class="company-info text-right">
          <div class="d-flex align-center gap-2 justify-end mb-1">
            <span class="company-name">{{ settings?.site_identity?.site_name || 'اسم الشركة' }}</span>
            <VAvatar v-if="settings?.site_logos?.site_logo_512_light" rounded size="36">
              <VImg :src="settings.site_logos.site_logo_512_light" />
            </VAvatar>
          </div>
          <div class="text-xs">{{ settings?.site_identity?.site_phone }}</div>
        </div>
      </div>

      <!-- Content Area -->
      <div class="content-rows mt-4">
        <!-- Row 1: Sender -->
        <div class="dotted-row mb-4">
          <span class="label">اسم الراسل:</span>
          <span class="value">{{ data.client_name }}</span>
          <span class="label ms-4">الهاتف:</span>
          <span class="value">{{ data.client_phone || '—' }}</span>
        </div>

        <!-- Row 2: Receiver (Large) -->
        <div class="dotted-row mb-4">
          <span class="label">اسم المستلم:</span>
          <span class="value text-h5 font-weight-black">{{ data.receiver_name }}</span>
        </div>

        <!-- Row 3: Address -->
        <div class="dotted-row mb-4">
          <span class="label">العنوان / الوجهة:</span>
          <span class="value">{{ data.address }}</span>
        </div>

        <!-- Row 4: Phones -->
        <div class="dotted-row mb-4">
          <span class="label">رقم الهاتف:</span>
          <span class="value text-h5 font-weight-bold">{{ data.receiver_phones_text }}</span>
        </div>

        <!-- Row 5: Financials -->
        <div class="d-flex gap-4">
          <div class="dotted-row mb-4 flex-grow-1">
            <span class="label">المبلغ المطلوب:</span>
            <span class="value font-weight-black text-h4">{{ Number(data.total_amount).toFixed(2) }} ج.م</span>
          </div>
          <div class="dotted-row mb-4" style="min-inline-size: 200px;">
            <span class="label">نوع الشحنة:</span>
            <span class="value">{{ data.shipping_content || 'طرد' }}</span>
          </div>
        </div>

        <!-- Row 6: Policy -->
        <div class="dotted-row mb-4">
          <span class="label">ملاحظات:</span>
          <span class="value font-weight-bold">
            {{ data.allow_open ? 'مسموح بالفتح والمعاينة' : 'ممنوع الفتح قبل الاستلام' }}
          </span>
        </div>
      </div>

      <!-- Footer removed as per user request -->
    </div>
  </div>
  <div v-else class="pa-20 text-center">
    <VProgressCircular indeterminate color="primary" />
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap');

.shipping-card-container {
  background: white;
  width: 100%;
  max-width: 180mm;
  margin: 10px auto;
  padding: 10px;
  font-family: 'Cairo', sans-serif;
  color: #000;
}

.shipping-card {
  border: 4px solid #000;
  padding: 24px;
}

.header-row {
  border-bottom: 2px solid #000;
}

.barcode-image {
  height: 50px;
  width: auto;
  min-width: 150px;
}

.receipt-title {
  font-size: 2rem;
  font-weight: 900;
  text-decoration: underline;
}

.receipt-no {
  font-weight: 700;
}

.company-name {
  font-weight: 900;
  font-size: 1.1rem;
}

.dotted-row {
  display: flex;
  align-items: baseline;
  border-bottom: 2px dotted #999;
  padding-bottom: 6px;
}

.label {
  font-weight: 700;
  margin-left: 10px;
  white-space: nowrap;
}

.value {
  flex-grow: 1;
}

.signature-line {
  width: 200px;
  border-top: 1px solid #000;
  padding-top: 5px;
  font-weight: 700;
}

@media print {
  @page {
    size: A5 landscape;
    margin: 3mm;
  }
  body { margin: 0 !important; cursor: none; }
  .shipping-card-container {
    max-width: none;
    width: 100%;
    margin: 0;
    padding: 0;
  }
  .shipping-card {
    border-width: 3px;
    height: 98vh;
  }
}
</style>
