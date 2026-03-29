<script setup lang="ts">
import { useApi } from '@/composables/useApi';
import { ref, watch } from 'vue';

interface Props {
  isDialogVisible: boolean
  order: any
}

const props = defineProps<Props>()
const emit = defineEmits(['update:isDialogVisible'])

const historyLogs = ref<any[]>([])
const isFetching = ref(false)

const fetchHistory = async () => {
  if (!props.order?.id) return
  isFetching.value = true
  try {
    const { data } = await useApi(`/orders/${props.order.id}/history`).get().json()
    historyLogs.value = data.value?.data || data.value || []
  } catch (e) {
    console.error(e)
  }
  isFetching.value = false
}

watch(() => props.isDialogVisible, (val) => {
  if (val) fetchHistory()
})

const close = () => {
  emit('update:isDialogVisible', false)
}

const statusColors: any = {
  OUT_FOR_DELIVERY: 'primary',
  DELIVERED: 'success',
  HOLD: 'warning',
  UNDELIVERED: 'error',
}
const resolveStatusColor = (status: string) => statusColors[status] || 'secondary'

const getLogIcon = (log: any) => {
  if (log.action === 'created') return 'tabler-circle-plus'
  if (log.new_values?.status) return 'tabler-circle-check'
  return 'tabler-point'
}

const getLogLabel = (log: any) => {
  if (log.action === 'created') return 'إنشاء الطلب'
  if (log.new_values?.status) return 'تغيير الحالة'
  if (log.new_values?.is_shipper_collected) return 'تحصيل مالي'
  return 'تحديث'
}

const getLogMessage = (log: any) => {
  if (log.action === 'created') return 'تم تسجيل الأوردر بنجاح'
  if (log.new_values?.status) return `تغيرت الحالة إلى ${log.new_values.status}`
  if (log.message) return log.message
  return 'تحديث بيانات'
}
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    width="900"
    @update:model-value="close"
  >
    <VCard v-if="props.order">
      <!-- 👉 Header -->
      <VCardTitle class="d-flex align-center justify-space-between pa-4 bg-light-primary">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-package" color="primary" />
          <span class="text-h6 font-weight-bold">تفاصيل الشحنة #{{ props.order.code }}</span>
          <VChip :color="resolveStatusColor(props.order.status)" size="small" class="ms-2">
            {{ props.order.status }}
          </VChip>
        </div>
        <VBtn icon variant="text" size="small" @click="close">
          <VIcon icon="tabler-x" />
        </VBtn>
      </VCardTitle>

      <VCardText class="pa-0">
        <!-- 🛤️ Timeline Top -->
        <div class="pa-6 border-bottom">
          <div class="text-subtitle-1 font-weight-bold mb-4 d-flex align-center gap-2">
            <VIcon icon="tabler-route" size="20" color="secondary" />
            تتبع الشحنة (Timeline)
          </div>
          
          <div v-if="isFetching" class="text-center py-4">
            <VProgressCircular indeterminate color="primary" />
          </div>
          <div v-else-if="!historyLogs.length" class="text-center text-disabled py-4">
            لا توجد سجلات تتبع حالياً
          </div>
          <VTimeline 
            v-else
            direction="horizontal" 
            side="end" 
            density="compact"
            truncate-line="both"
            line-thickness="2"
            class="v-timeline--variant-outlined custom-horizontal-timeline"
          >
            <VTimelineItem
              v-for="log in historyLogs.slice().reverse()"
              :key="log.id"
              size="small"
              :dot-color="statusColors[log.new_values?.status] || 'primary'"
              fill-dot
            >
              <template #icon>
                <VIcon :icon="getLogIcon(log)" size="12" color="white" />
              </template>
              <div class="text-center" style="inline-size: 120px;">
                <div class="text-caption font-weight-bold text-truncate" :title="getLogLabel(log)">{{ getLogLabel(log) }}</div>
                <div class="text-xs text-disabled">{{ new Date(log.created_at).toLocaleDateString('ar-EG') }}</div>
              </div>
            </VTimelineItem>
          </VTimeline>
        </div>

        <!-- 📄 Details Grid -->
        <div class="pa-6 bg-var-theme-background">
          <VRow>
            <!-- 👤 Customer Info -->
            <VCol cols="12" md="4">
              <VCard variant="outlined" elevation="0" class="h-100">
                <VCardText>
                  <div class="d-flex align-center gap-2 mb-4 border-bottom pb-2">
                    <VIcon icon="tabler-user" color="info" />
                    <span class="font-weight-bold">بيانات المستلم</span>
                  </div>
                  <div class="d-flex flex-column gap-3">
                    <div class="d-flex justify-space-between">
                      <span class="text-disabled">الاسم:</span>
                      <span class="font-weight-medium">{{ props.order.receiver_name }}</span>
                    </div>
                    <div class="d-flex justify-space-between">
                      <span class="text-disabled">الموبايل:</span>
                      <span class="font-weight-medium">{{ props.order.phone }}</span>
                    </div>
                    <div v-if="props.order.phone_2" class="d-flex justify-space-between">
                      <span class="text-disabled">موبايل 2:</span>
                      <span class="font-weight-medium">{{ props.order.phone_2 }}</span>
                    </div>
                    <div class="d-flex flex-column gap-1">
                      <span class="text-disabled">العنوان:</span>
                      <span class="text-sm font-weight-medium bg-light-info pa-2 rounded mt-1">
                        {{ props.order.governorate?.name }} - {{ props.order.city?.name }}<br>
                        {{ props.order.address }}
                      </span>
                    </div>
                  </div>
                </VCardText>
              </VCard>
            </VCol>

            <!-- 💰 Financial Info -->
            <VCol cols="12" md="4">
              <VCard variant="outlined" elevation="0" class="h-100">
                <VCardText>
                  <div class="d-flex align-center gap-2 mb-4 border-bottom pb-2">
                    <VIcon icon="tabler-receipt-2" color="success" />
                    <span class="font-weight-bold">البيانات المالية</span>
                  </div>
                  <div class="d-flex flex-column gap-3">
                    <div class="d-flex justify-space-between">
                      <span class="text-disabled">إجمالي الأوردر:</span>
                      <span class="font-weight-bold text-primary">{{ props.order.total_amount }} ج.م</span>
                    </div>
                    <div class="d-flex justify-space-between">
                      <span class="text-disabled">مبلغ التحصيل (COD):</span>
                      <span class="font-weight-bold text-success">{{ props.order.cod_amount }} ج.م</span>
                    </div>
                    <VDivider />
                    <div class="d-flex justify-space-between">
                      <span class="text-disabled">سعر الشحن:</span>
                      <span>{{ props.order.shipping_fee }} ج.م</span>
                    </div>
                    <div class="d-flex justify-space-between">
                      <span class="text-disabled">عمولة المندوب:</span>
                      <span>{{ props.order.commission_amount }} ج.م</span>
                    </div>
                    <div class="d-flex justify-space-between">
                      <span class="text-disabled">صافي الشركة:</span>
                      <span>{{ props.order.company_amount }} ج.م</span>
                    </div>
                  </div>
                </VCardText>
              </VCard>
            </VCol>

            <!-- 🚚 Logistics & Other -->
            <VCol cols="12" md="4">
              <VCard variant="outlined" elevation="0" class="h-100">
                <VCardText>
                  <div class="d-flex align-center gap-2 mb-4 border-bottom pb-2">
                    <VIcon icon="tabler-truck-delivery" color="warning" />
                    <span class="font-weight-bold">معلومات الشحن</span>
                  </div>
                  <div class="d-flex flex-column gap-3">
                    <div class="d-flex justify-space-between">
                      <span class="text-disabled">العميل:</span>
                      <span class="font-weight-medium">{{ props.order.client?.name }}</span>
                    </div>
                    <div class="d-flex justify-space-between">
                      <span class="text-disabled">المندوب:</span>
                      <span class="font-weight-medium">{{ props.order.shipper?.name || 'غير محدد' }}</span>
                    </div>
                    <div class="d-flex justify-space-between">
                      <span class="text-disabled">المحتوى:</span>
                      <span>{{ props.order.shipping_content?.name || '-' }}</span>
                    </div>
                    <div class="d-flex justify-space-between">
                      <span class="text-disabled">متاح فتح الطرد:</span>
                      <VChip v-if="props.order.allow_open" color="success" size="x-small">نعم</VChip>
                      <VChip v-else color="error" size="x-small">لا</VChip>
                    </div>
                    <div class="mt-2">
                       <span class="text-xs text-disabled">تاريخ الإنشاء:</span>
                       <div class="text-xs">{{ new Date(props.order.created_at).toLocaleString('ar-EG') }}</div>
                    </div>
                  </div>
                </VCardText>
              </VCard>
            </VCol>
          </VRow>

          <!-- 📝 Note Section -->
          <VCard v-if="props.order.order_note || props.order.latest_status_note" variant="tonal" color="secondary" class="mt-4">
            <VCardText class="pa-3">
              <div v-if="props.order.order_note" class="mb-2">
                <span class="font-weight-bold text-xs">ملاحظة الأوردر:</span>
                <p class="mb-0 text-sm">{{ props.order.order_note }}</p>
              </div>
              <div v-if="props.order.latest_status_note">
                <span class="font-weight-bold text-xs">آخر ملاحظة حالة:</span>
                <p class="mb-0 text-sm text-info">{{ props.order.latest_status_note }}</p>
              </div>
            </VCardText>
          </VCard>
        </div>
      </VCardText>

      <VCardText class="d-flex justify-end gap-2 pa-4 bg-light-primary">
        <VBtn variant="tonal" color="secondary" @click="close">إغلاق</VBtn>
        <VBtn prepend-icon="tabler-printer" @click="$emit('print', props.order.id)">طباعة البوليسة</VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style lang="scss" scoped>
.italic { font-style: italic; }
.custom-horizontal-timeline {
  :deep(.v-timeline-divider__dot) {
    background: white !important;
  }
}
</style>
