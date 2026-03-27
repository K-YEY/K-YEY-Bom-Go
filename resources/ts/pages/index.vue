<script setup lang="ts">
import Footer from '@/views/front-pages/front-page-footer.vue'
import Navbar from '@/views/front-pages/front-page-navbar.vue'
import Banner from '@/views/front-pages/landing-page/banner.vue'
import CustomersReview from '@/views/front-pages/landing-page/customers-review.vue'
import FaqSection from '@/views/front-pages/landing-page/faq-section.vue'
import Features from '@/views/front-pages/landing-page/features.vue'
import HeroSection from '@/views/front-pages/landing-page/hero-section.vue'
import OurTeam from '@/views/front-pages/landing-page/our-team.vue'
import PricingPlans from '@/views/front-pages/landing-page/pricing-plans.vue'
import ProductStats from '@/views/front-pages/landing-page/product-stats.vue'

definePage({
  meta: {
    layout: 'blank',
    unauthenticatedOnly: false,
    public: true,
  },
})
</script>

<template>
  <div class="landing-page">
    <Navbar />
    <HeroSection />
    <ProductStats />
    <Features />
    <CustomersReview />
    <OurTeam />
    <PricingPlans />
    <FaqSection />
    <Banner />
    <Footer />
  </div>
</template>

<style lang="scss">
@media (max-width: 960px) {
  .v-container {
    padding-inline: 1.5rem !important;
  }
}
</style>
