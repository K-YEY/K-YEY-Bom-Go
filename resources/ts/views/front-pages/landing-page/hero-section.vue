<script setup lang="ts">
import { useApi } from '@/composables/useApi'
import heroForkliftImg from '@images/front-pages/landing-page/hero-forklift.png'
import { useTheme } from 'vuetify'

const theme = useTheme()

const activeTab = ref('trace')
const trackingID = ref('')
const trackingResult = ref<any>(null)
const isTracking = ref(false)
const trackingError = ref('')

const trackNow = async () => {
  if (!trackingID.value) return
  
  isTracking.value = true
  trackingError.value = ''
  trackingResult.value = null
  
  try {
    const { data, error } = await useApi(`/orders/track/${trackingID.value}`).get().json()
    if (error.value) {
      trackingError.value = 'الشحنة غير موجودة أو كود غير صحيح'
    } else {
      trackingResult.value = data.value
    }
  } catch (e) {
    trackingError.value = 'حدث خطأ أثناء التتبع'
  } finally {
    isTracking.value = false
  }
}

const services = [
  { title: 'خدمات المستودعات', icon: 'tabler-home', color: 'orange' },
  { title: 'الشحن البحري', icon: 'tabler-waves-ocean', color: 'blue' },
  { title: 'الشحن الجوي', icon: 'tabler-plane-tilt', color: 'red' },
  { title: 'الشحن البري', icon: 'tabler-truck', color: 'orange-darken-3' },
]

const statusColors: any = {
  OUT_FOR_DELIVERY: 'primary',
  DELIVERED: 'success',
  HOLD: 'warning',
  UNDELIVERED: 'error',
}
</script>

<template>
  <div
    id="home"
    :style="{ background: 'rgb(var(--v-theme-surface))' }"
  >
    <div id="landingHero">
      <div 
        class="landing-hero"
        :style="{ background: theme.current.value.dark ? '#25293C' : '#FFF9F5' }"
      >
        <VContainer>
          <VRow align="center">
            <!-- Left Side: Hero Text and Tracker -->
            <VCol cols="12" md="6" class="px-6">
              <h1 class="hero-title mb-2 text-start">
                دقة الخدمات اللوجستية:
              </h1>
              <h2 class="text-h3 font-weight-black mb-6" style="color: #2F2B3D;">
                حيث تلتقي الكفاءة بالخبرة
              </h2>
              <p class="mb-8 text-body-1 text-disabled" style="max-inline-size: 500px">
                من التخليص الجمركي إلى تسليم الميل الأخير، نحن نهتم بكل التفاصيل لضمان وصول شحناتك في أمان تام وبأعلى سرعة.
              </p>

              <!-- Tracking Box -->
              <VCard elevation="12" class="tracker-card rounded-xl">
                <div class="d-flex border-bottom">
                  <div 
                    class="tab-item py-4 px-6 cursor-pointer flex-fill text-center font-weight-bold"
                    :class="activeTab === 'trace' ? 'active-tab' : ''"
                    @click="activeTab = 'trace'"
                  >
                    <VIcon icon="tabler-map-pin" class="me-2" :color="activeTab === 'trace' ? '#FF5C00' : ''" />
                    تتبع الشحنة
                  </div>
                  <div 
                    class="tab-item py-4 px-6 cursor-pointer flex-fill text-center font-weight-bold"
                    :class="activeTab === 'rates' ? 'active-tab' : ''"
                    :style="activeTab === 'rates' ? {} : { backgroundColor: '#0077B6', color: 'white', borderTopRightRadius: '12px' }"
                    @click="activeTab = 'rates'"
                  >
                    <VIcon icon="tabler-currency-dollar" class="me-2" :color="activeTab === 'rates' ? '#0077B6' : 'white'" />
                    أسعار الشحن
                  </div>
                </div>
                
                <VCardText class="pa-6">
                  <div v-if="activeTab === 'trace'">
                    <label class="text-caption font-weight-bold mb-2 d-inline-block">رقم التتبع (Tracking ID)</label>
                    <VTextField
                      v-model="trackingID"
                      placeholder="أدخل رقم الشحنة هنا..."
                      variant="outlined"
                      bg-color="grey-lighten-4"
                      hide-details
                      class="mb-4"
                      @keyup.enter="trackNow"
                    />
                    <VBtn 
                      block 
                      color="#FF5C00" 
                      size="large" 
                      class="text-white font-weight-bold"
                      :loading="isTracking"
                      @click="trackNow"
                    >
                      تتبع الآن
                    </VBtn>

                    <!-- Tracking Result -->
                    <div v-if="trackingResult" class="mt-6 pa-4 rounded-lg bg-orange-lighten-5 border border-orange-lighten-3">
                      <div class="d-flex justify-space-between align-center mb-4">
                        <span class="font-weight-bold text-h6">#{{ trackingResult.code }}</span>
                        <VChip :color="statusColors[trackingResult.status] || 'secondary'" size="small">
                          {{ trackingResult.status === 'OUT_FOR_DELIVERY' ? 'خرج للتوصيل' : 
                             trackingResult.status === 'DELIVERED' ? 'تم التسليم' :
                             trackingResult.status === 'HOLD' ? 'قيد الانتظار' :
                             trackingResult.status === 'UNDELIVERED' ? 'فشل التوصيل' : trackingResult.status }}
                        </VChip>
                      </div>
                      <div class="text-body-2 mb-1">المرسل إليه: {{ trackingResult.receiver_name }}</div>
                      <div class="text-body-2 mb-4">الموقع: {{ trackingResult.governorate }} - {{ trackingResult.city }}</div>
                      
                      <div class="text-caption font-weight-bold mb-2">تاريخ الشحنة:</div>
                      <div class="timeline ps-2 border-start border-primary border-opacity-25">
                        <div v-for="(log, i) in trackingResult.history.slice(0, 3)" :key="i" class="mb-2 position-relative">
                          <div class="dot position-absolute bg-primary rounded-circle" style="inline-size: 8px; block-size: 8px; inset-inline-start: -12.5px; inset-block-start: 6px;" />
                          <div class="text-caption font-weight-bold">{{ log.activity }}</div>
                          <div class="text-xs text-disabled">{{ log.created_at }}</div>
                        </div>
                      </div>
                    </div>
                    <div v-if="trackingError" class="mt-4 text-error text-center text-caption font-weight-bold">
                       {{ trackingError }}
                    </div>
                  </div>
                  <div v-else class="text-center py-6">
                    قم بتسجيل الدخول لمشاهدة خطط الأسعار المتاحة لعملاء النظام.
                    <VBtn class="mt-4" variant="tonal" :to="{ name: 'pages-authentication-login-v1' }">تسجيل الدخول</VBtn>
                  </div>
                </VCardText>
              </VCard>
            </VCol>

            <!-- Right Side: Forklift Image -->
            <VCol cols="12" md="6" class="text-center position-relative">
               <img
                  :src="heroForkliftImg"
                  alt="Logistic Forklift"
                  class="hero-main-img"
                  style="inline-size: 100%; max-inline-size: 600px"
                >
            </VCol>
          </VRow>
        </VContainer>
      </div>
    </div>

    <!-- Services Section (Logistics Crafted to Perfection) -->
    <VContainer class="services-container py-12">
      <h3 class="text-h5 font-weight-bold mb-10 text-center text-md-start">الخدمات اللوجستية المتقنة</h3>
      <VRow>
        <VCol v-for="service in services" :key="service.title" cols="12" sm="6" md="3">
          <VCard variant="flat" class="service-card py-6 text-center border">
            <VAvatar size="48" :color="service.color" variant="tonal" class="mb-4">
              <VIcon :icon="service.icon" size="28" />
            </VAvatar>
            <div class="font-weight-bold text-subtitle-1">{{ service.title }}</div>
          </VCard>
        </VCol>
      </VRow>
    </VContainer>
  </div>
</template>

<style lang="scss" scoped>
.landing-hero {
  padding-block: 5rem 10rem;
  overflow: hidden;
}

.hero-title {
  color: #FF5C00;
  font-size: 1.25rem;
  font-weight: 700;
  letter-spacing: 1px;
  text-transform: uppercase;
}

.tracker-card {
  max-inline-size: 450px;
  background-color: white;
  
  .tab-item {
    transition: all 0.2s ease-in-out;
    border-top-left-radius: 12px;
    
    &.active-tab {
      background-color: white;
      color: #FF5C00 !important;
    }
    
    &:not(.active-tab) {
      background-color: #F4F4F4;
      color: #888;
    }
  }
}

.service-card {
  transition: all 0.3s ease;
  cursor: pointer;
  background-color: white;
  
  &:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.05);
  }
}

@media (max-width: 959px) {
  .landing-hero {
    padding-block: 3rem 5rem;
  }
  
  .tracker-card {
    max-inline-size: 100%;
    margin-block-end: 2rem;
  }
}

.timeline {
  .dot {
    z-index: 1;
  }
}
</style>
