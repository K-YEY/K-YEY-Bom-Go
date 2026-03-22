<script setup lang="ts">
import { useApi } from '@/composables/useApi'

const route = useRoute()
const idsText = (route.query as any).ids || ''
const ids = idsText.split(',').filter((id: string) => id !== '')

const labelsData = ref<any[]>([])
const settings = ref<any>(null)
const isLoading = ref(true)

const fetchData = async () => {
  if (!ids.length) return
  
  try {
    const settingsRes = await useApi<any>('/settings').get().json()
    settings.value = settingsRes.data.value

    // Fetch all labels in parallel (backend currently only has single fetch)
    // We'll perform multiple requests or check if backend supports bulk
    const labelRequests = ids.map((id: string) => useApi<any>(`/orders/${id}/shipping-label`).get().json())
    const results = await Promise.all(labelRequests)
    
    labelsData.value = results.map((r: any) => r.data.value).filter((d: any) => d !== null)
    isLoading.value = false

    setTimeout(() => {
      window.print()
    }, 1500)
  } catch (e) {
    console.error('Bulk Print Fetch Error:', e)
    isLoading.value = false
  }
}

onMounted(fetchData)

definePage({
  meta: {
    layout: 'blank',
  },
})
</script>

<template>
  <div v-if="!isLoading && labelsData.length" class="bulk-print-wrapper" dir="rtl">
    <div v-for="(data, index) in labelsData" :key="index" class="shipping-card-container">
      <div class="shipping-card">
        <!-- Top Section -->
        <div class="d-flex justify-space-between align-center mb-6 header-row pb-4">
          <div class="qr-barcode">
            <img :src="`https://quickchart.io/barcode?type=code128&text=${data.code}&height=50&width=200&scale=2`" alt="barcode" class="barcode-image" />
          </div>
          <div class="text-center flex-grow-1 px-4">
            <h1 class="receipt-title mb-1">بوليصة شحن</h1>
            <div class="receipt-no">رقم: {{ data.code }}</div>
          </div>
          <div class="company-info text-right">
            <div class="d-flex align-center gap-2 justify-end mb-1">
              <span class="company-name text-nowrap">{{ settings?.site_identity?.site_name || 'اسم الشركة' }}</span>
              <VAvatar v-if="settings?.site_logos?.site_logo_512_light" rounded size="36">
                <VImg :src="settings.site_logos.site_logo_512_light" />
              </VAvatar>
            </div>
            <div class="text-xs">{{ settings?.site_identity?.site_phone }}</div>
          </div>
        </div>

        <div class="content-rows mt-4">
          <div class="dotted-row mb-4">
            <span class="label">اسم الراسل:</span>
            <span class="value">{{ data.client_name }}</span>
            <span class="label ms-4">الهاتف:</span>
            <span class="value">{{ data.client_phone || '—' }}</span>
          </div>
          <div class="dotted-row mb-4">
            <span class="label">اسم المستلم:</span>
            <span class="value text-h5 font-weight-black">{{ data.receiver_name }}</span>
          </div>
          <div class="dotted-row mb-4">
            <span class="label">العنوان / الوجهة:</span>
            <span class="value">{{ data.address }}</span>
          </div>
          <div class="dotted-row mb-4">
            <span class="label">رقم الهاتف:</span>
            <span class="value text-h5 font-weight-bold">{{ data.receiver_phones_text }}</span>
          </div>
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
          <div class="dotted-row mb-4">
            <span class="label">ملاحظات:</span>
            <span class="value font-weight-bold">
              {{ data.allow_open ? 'مسموح بالفتح ومعاينة' : 'ممنوع الفتح قبل الاستلام' }}
            </span>
          </div>
        </div>

        <!-- Footer removed as per user request -->
      </div>
    </div>
  </div>
  <div v-else class="pa-20 text-center">
    <VProgressCircular indeterminate color="primary" />
    <div class="mt-4">تحضير البوالص للطباعة...</div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap');

.bulk-print-wrapper {
  background: #f5f5f5;
  padding: 2mm;
  font-family: 'Cairo', sans-serif;
}

.shipping-card-container {
  background: white;
  width: 100%;
  max-width: 180mm;
  margin: 10px auto;
  padding: 10px;
}
.shipping-card-container:not(:last-child) {
  page-break-after: always;
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
  .bulk-print-wrapper {
     background: transparent;
     padding: 0;
  }
  body { margin: 0 !important; }
  .shipping-card-container {
    max-width: none;
    width: 100%;
    margin: 0;
    padding: 0;
    page-break-after: always;
  }
  .shipping-card {
    border-width: 3px;
    height: 98vh;
  }
}
</style>
