<script setup lang="ts">
const faqData = [
  {
    question: 'كيف يمكنني البدء مع Shipya؟',
    answer: 'يمكنك البدء بإنشاء حساب تجريبي مجاني لمدة 14 يوم، وبعدها يمكنك اختيار الخطة التي تناسب حجم أعمالك.',
  },
  {
    question: 'هل توجد عمولات خفية؟',
    answer: 'لا، نحن نعتمد على نظام اشتراك شهري واضح ولا نأخذ أي عمولات إضافية على الأوردرات إلا المذكورة في خطتك.',
  },
  {
    question: 'كيف يتم تسوية المبالغ المالية؟',
    answer: 'يتم تسوية المبالغ المحصلة من المناديب بشكل يومي، ويمكنك طلب تحويل مستحقاتك في أي وقت لتصلك خلال 24 ساعة.',
  },
  {
    question: 'هل تدعم المنصة ربط الـ API؟',
    answer: 'نعم، لدينا API متكامل وسهل الاستخدام للربط مع متجرك الإلكتروني (Shopify, WooCommerce, Salla) أو أي نظام خاص.',
  },
]
</script>

<template>
  <div id="faq">
    <VContainer>
      <!-- 👉 Header  -->
      <div class="faq-section">
        <div class="headers d-flex justify-center flex-column align-center">
          <VChip
            label
            color="primary"
            size="small"
            class="mb-4"
          >
            الأسئلة الشائعة
          </VChip>
          <h4 class="d-flex align-center text-h4 mb-1 flex-wrap justify-center">
            إليك إجابات لبعض 
            <div class="position-relative ms-2">
              <div class="section-title">
                الأسئلة المتكررة
              </div>
            </div>
          </h4>
          <p class="text-body-1 mb-0 text-center">
            تصفح الأسئلة الشائعة لتجد إجابات سريعة حول خدماتنا وكيفية العمل معنا.
          </p>
        </div>
        <VRow>
          <VCol
            cols="12"
            md="5"
          >
            <div class="pt-10 d-flex align-center justify-center h-100">
               <VIcon icon="tabler-help-circle" size="180" color="primary" style="opacity: 0.1;" />
            </div>
          </VCol>
          <VCol
            cols="12"
            md="7"
          >
            <VExpansionPanels class="pt-16">
              <VExpansionPanel
                v-for="faq in faqData"
                :key="faq.question"
              >
                <VExpansionPanelTitle>
                  {{ faq.question }}
                </VExpansionPanelTitle>
                <VExpansionPanelText>
                  {{ faq.answer }}
                </VExpansionPanelText>
              </VExpansionPanel>
            </VExpansionPanels>
          </VCol>
        </VRow>
      </div>
    </VContainer>
  </div>
</template>

<style lang="scss" scoped>
.faq-section {
  margin-block: 5.25rem;
}

@media (max-width: 600px) {
  .faq-section {
    margin-block: 4rem;
  }
}

#faq {
  border-radius: 3.75rem 3.75rem 0 0;
  background-color: rgba(var(--v-theme-background));
}

.section-title {
  font-size: 24px;
  font-weight: 800;
  line-height: 36px;
}
</style>
