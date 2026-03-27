<script setup lang="ts">
const reviewData = [
  {
    desc: 'من أفضل المنصات التي تعاملت معها، سهولة في الاستخدام ودقة في التحصيل المالي.',
    img: 'tabler-user',
    name: 'محمود جاسم',
    pos: 'صاحب متجر ملابس',
    rating: 5,
  },
  {
    desc: 'الـ API الخاص بهم سهل جداً، ربطت متجري في دقائق وبدأت الشحن فوراً.',
    img: 'tabler-user',
    name: 'سارة هاني',
    pos: 'تاجرة إلكترونية',
    rating: 5,
  },
  {
    desc: 'نظام المرتجعات بالباركود وفر علينا الكثير من الوقت والمجهود يومياً.',
    img: 'tabler-user',
    name: 'محمد زين',
    pos: 'مدير عمليات',
    rating: 4,
  },
]
</script>

<template>
  <div id="customers-review">
    <VContainer>
      <div class="customers-review">
        <!-- 👉 Headers  -->
        <div class="headers d-flex justify-center flex-column align-center flex-wrap">
          <VChip
            label
            color="primary"
            class="mb-4"
            size="small"
          >
            آراء العملاء
          </VChip>
          <h4 class="d-flex align-center text-h4 mb-1 flex-wrap justify-center text-center">
            ماذا يقول 
            <div class="position-relative ms-2">
              <div class="section-title">
                شركاء نجاحنا
              </div>
            </div>
            عن تجربتهم معنا
          </h4>
          <p class="text-center text-body-1 mt-2 mb-10">
            أكثر من 500 متجر يعتمدون على Shipya يومياً.
          </p>
        </div>

        <VRow class="mb-12">
          <VCol
            v-for="(review, index) in reviewData"
            :key="index"
            cols="12"
            md="4"
          >
            <VCard flat border class="h-100 pa-8">
              <VCardText class="pa-0">
                <VRating :model-value="review.rating" readonly color="warning" class="mb-4" />
                <p class="text-body-1 mb-8" style="min-block-size: 80px;">
                  "{{ review.desc }}"
                </p>
                <div class="d-flex align-center gap-3">
                  <VAvatar color="primary" variant="tonal">
                    <VIcon :icon="review.img" />
                  </VAvatar>
                  <div>
                    <div class="text-h6">{{ review.name }}</div>
                    <div class="text-caption">{{ review.pos }}</div>
                  </div>
                </div>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>

        <VDivider class="mb-12" />

        <VRow class="justify-center gap-12 align-center opacity-50 grayscale-icons">
          <VIcon icon="tabler-brand-shopee" size="48" />
          <VIcon icon="tabler-brand-amazon" size="48" />
          <VIcon icon="tabler-brand-delivery" size="48" />
          <VIcon icon="tabler-brand-mailchimp" size="48" />
        </VRow>
      </div>
    </VContainer>
  </div>
</template>

<style lang="scss" scoped>
.customers-review {
  margin-block: 5.25rem;
}

.section-title {
  font-size: 24px;
  font-weight: 800;
  line-height: 36px;
}

.grayscale-icons {
  filter: grayscale(100%);
}
</style>
