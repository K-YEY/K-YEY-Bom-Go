<script setup lang="ts">
interface GroupedPrice {
  price: number
  governorates: string[]
}

interface Plan {
  id: number
  name: string
  order_count: number
  grouped_prices: GroupedPrice[]
}

const { data } = await useApi<any>('/landing-page')
const plans = computed<Plan[]>(() => data.value?.plans ?? [])

const getPlanIcon = (index: number) => {
  if (index === 0) return 'tabler-at'
  if (index === 1) return 'tabler-plane'
  return 'tabler-rocket'
}
</script>

<template>
  <div id="pricing-plan">
    <VContainer>
      <div class="pricing-plans">
        <!-- 👉 Headers  -->
        <div class="headers d-flex justify-center flex-column align-center flex-wrap">
          <VChip
            label
            color="primary"
            class="mb-4"
            size="small"
          >
            خطط الشحن
          </VChip>
          <h4 class="d-flex align-center text-h4 mb-1 flex-wrap justify-center text-center">
            تغطية واسعة و 
            <div class="position-relative ms-2">
              <div class="section-title">
                أسعار مرنة
              </div>
            </div>
            تناسب حجم أعمالك
          </h4>
          <p class="text-center text-body-1 mt-2">
            اختر الخطة المناسبة لك وابدأ في شحن منتجاتك بكل سهولة وأمان.
          </p>
        </div>

        <VRow class="mt-8 justify-center">
          <VCol
            v-for="(plan, index) in plans"
            :key="plan.id"
            cols="12"
            md="4"
          >
            <VCard flat border class="h-100">
              <VCardText class="pa-8 pt-12 d-flex flex-column h-100">
                <VIcon
                  :icon="getPlanIcon(index)"
                  size="88"
                  color="primary"
                  class="mx-auto mb-8"
                />
                <h4 class="text-h4 text-center mb-2">
                  {{ plan.name }}
                </h4>
                <div class="text-center text-primary font-weight-bold mb-6">
                  دعم {{ plan.order_count }} أوردر شهرياً
                </div>

                <v-divider class="mb-6"></v-divider>

                <div class="flex-grow-1">
                  <div 
                    v-for="group in plan.grouped_prices" 
                    :key="group.price"
                    class="mb-4"
                  >
                    <div class="d-flex align-center justify-space-between mb-1">
                      <span class="text-h6 text-primary">{{ group.price }} ج.م</span>
                      <VChip size="x-small" label variant="tonal" color="secondary">السعر</VChip>
                    </div>
                    <div class="text-caption text-medium-emphasis">
                      {{ group.governorates.join(', ') }}
                    </div>
                  </div>
                </div>

                <VBtn
                  block
                  variant="elevated"
                  color="primary"
                  class="mt-8"
                  :to="{ name: 'pages-authentication-login-v1' }"
                >
                  ابدأ الآن
                </VBtn>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>
      </div>
    </VContainer>
  </div>
</template>

<style lang="scss" scoped>
#pricing-plan {
  border-radius: 3.75rem;
  background-color: rgb(var(--v-theme-background));
}

.pricing-plans {
  margin-block: 5.25rem;
}

@media (max-width: 600px) {
  .pricing-plans {
    margin-block: 4rem;
  }
}

.section-title {
  font-size: 24px;
  font-weight: 800;
  line-height: 36px;
  position: relative;
}
</style>
