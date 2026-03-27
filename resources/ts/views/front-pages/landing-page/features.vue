<script setup lang="ts">
const featuresData = [
  { title: 'نظام تتبع متطور', desc: 'تتبع كل شحنة بدقة متناهية من لحظة الخروج وحتى الاستلام النهائي مع سجل كامل للتحركات.', icon: 'tabler-map-2' },
  { title: 'تسويات مالية ذكية', desc: 'إدارة كاملة للمبالغ المحصلة وتسويات العملاء والمندوبين بشكل آلي وبدون أخطاء محاسبية.', icon: 'tabler-calculator' },
  { title: 'ماسح الباركود السريع', desc: 'نظام متكامل للمسح الضوئي لمعالجة المرتجعات والتسليمات بالجملة في ثوانٍ معدودة.', icon: 'tabler-scan' },
  { title: 'تقارير تحليلية', desc: 'عرض شامل لأداء شركتك من خلال لوحة تحكم ذكية توضح نسب التسليم والمبالغ تحت التحصيل.', icon: 'tabler-chart-pie' },
  { title: 'إدارة الفروع والمناديب', desc: 'تقسيم العمل على فروع ومناطق مختلفة مع تعيين تلقائي للمناديب بناءً على الموقع الجغرافي.', icon: 'tabler-users' },
  { title: 'دعم فني متكامل', desc: 'فريق متخصص يقدم الدعم الفني لك ولعملائك لضمان استمرارية العمل بأفضل جودة.', icon: 'tabler-headset' },
]
</script>

<template>
  <VContainer id="features">
    <div class="feature-cards">
      <div class="headers d-flex justify-center flex-column align-center mb-12">
        <VChip
          label
          color="primary"
          class="mb-4"
          size="small"
        >
          مميزات المنصة
        </VChip>
        <div class="d-flex text-h4 mb-1 align-center flex-wrap justify-center">
          <div class="position-relative me-2">
            <div class="section-title">
              كل ما تحتاجه لإدارة
            </div>
          </div>
          منظومة الشحن الخاصة بك
        </div>
        <p class="text-body-1 mb-0">
          نحن لا نقدم مجرد أدوات، بل نقدم حلولاً لوجستية متكاملة لنمو أعمالك وتوسيع نطاق مبيعاتك.
        </p>
      </div>
      <VRow>
        <VCol
          v-for="(data, index) in featuresData"
          :key="index"
          cols="12"
          md="4"
          sm="6"
        >
          <div class="d-flex flex-column align-center justify-center gap-4 mx-auto">
            <VIcon
              :icon="data.icon"
              size="64"
              color="primary"
            />
            <div class="text-center">
              <h5 class="text-h5 mb-2">
                {{ data.title }}
              </h5>
              <p
                class="text-body-1 mb-6"
                style="max-inline-size: 310px;"
              >
                {{ data.desc }}
              </p>
            </div>
          </div>
        </VCol>
      </VRow>
      <VRow>
        <VCol
          cols="12"
          class="text-center mt-10"
        >
          <div class="position-relative">
            <VBtn
              :size="$vuetify.display.smAndUp ? 'large' : 'default' "
              :to="{ name: 'pages-authentication-login-v1' }"
              :active="false"
            >
              ابدأ رحلتك معنا
            </VBtn>
          </div>
        </VCol>
      </VRow>
    </div>
  </VContainer>
</template>

<style lang="scss" scoped>
.feature-cards {
  margin-block: 3.75rem;
}

.section-title::after {
  position: absolute;
  background: url("../../../assets/images/front-pages/icons/section-title-icon.png") no-repeat left bottom;
  background-size: contain;
  block-size: 100%;
  content: "";
  inline-size: 120%;
  inset-block-end: 0;
  inset-inline-start: -12%;
}

.section-title {
  font-size: 24px;
  font-weight: 800;
  line-height: 36px;
}

.feature {
  .v-avatar {
    &.v-avatar--variant-outlined {
      border: 2px solid rgba(var(--v-theme-primary), 0.32);

      &:hover {
        background: rgba(var(--v-theme-primary), 0.16);
        cursor: pointer;
      }
    }
  }
}
</style>
