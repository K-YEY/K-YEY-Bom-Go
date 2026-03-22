<script setup lang="ts">
import paperPlane from '@images/front-pages/icons/paper-airplane.png'
import plane from '@images/front-pages/icons/plane.png'
import shuttleRocket from '@images/front-pages/icons/shuttle-rocket.png'

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
  if (index === 0) return paperPlane
  if (index === 1) return plane
  return shuttleRocket
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
            Shipping Plans
          </VChip>
          <h4 class="d-flex align-center text-h4 mb-1 flex-wrap justify-center text-center">
            <div class="position-relative me-2">
              <div class="section-title">
                Flexible Shipping Rates
              </div>
            </div>
            Choose your best fit
          </h4>
          <p class="text-center text-body-1 mt-2">
            Tailored plans for different business scales.
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
                <VImg
                  :src="getPlanIcon(index)"
                  width="88"
                  height="88"
                  class="mx-auto mb-8"
                />
                <h4 class="text-h4 text-center mb-2">
                  {{ plan.name }}
                </h4>
                <div class="text-center text-primary font-weight-bold mb-6">
                  {{ plan.order_count }} Orders Support
                </div>

                <v-divider class="mb-6"></v-divider>

                <div class="flex-grow-1">
                  <div 
                    v-for="group in plan.grouped_prices" 
                    :key="group.price"
                    class="mb-4"
                  >
                    <div class="d-flex align-center justify-space-between mb-1">
                      <span class="text-h6 text-primary">{{ group.price }} EGP</span>
                      <VChip size="x-small" label variant="tonal" color="secondary">Rate</VChip>
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
                  Get Started
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

.section-title::after {
  position: absolute;
  background: url("@images/front-pages/icons/section-title-icon.png") no-repeat left bottom;
  background-size: contain;
  block-size: 100%;
  content: "";
  font-weight: 700;
  inline-size: 120%;
  inset-block-end: 0;
  inset-inline-start: -10%;
  z-index: -1;
  opacity: 0.5;
}
</style>
