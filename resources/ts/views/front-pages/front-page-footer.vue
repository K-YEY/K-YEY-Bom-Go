<script setup lang="ts">

const { data } = await useApi<any>('/landing-page')
const site = computed(() => data.value?.site ?? { name: 'Shipya', logo: '', email: '', phone: '', address: '' })

const pagesList = [
  { name: 'Home', to: { name: 'front-pages-landing-page' } },
  { name: 'Pricing', to: { name: 'front-pages-landing-page', hash: '#pricing-plan' } },
  { name: 'Login', to: { name: 'pages-authentication-login-v1' } },
]
</script>

<template>
  <div class="footer">
    <div class="footer-top pt-11 bg-surface py-12">
      <VContainer>
        <VRow>
          <VCol cols="12" md="6">
            <div class="mb-4">
              <div class="app-logo mb-6 align-center d-flex gap-x-2">
                <VAvatar v-if="site.logo" :image="site.logo" size="32" />
                <VIcon v-else icon="tabler-truck" size="32" color="primary" />
                <h1 class="app-logo-title font-weight-bold" style="font-size: 1.5rem;">
                  {{ site.name }}
                </h1>
              </div>
              <p class="mb-6 opacity-75">
                The most efficient shipping management platform for modern logistics.
              </p>
              <div class="d-flex flex-column gap-y-2">
                <div class="d-flex align-center gap-x-2" v-if="site.email">
                  <VIcon icon="tabler-mail" size="18" />
                  <span>{{ site.email }}</span>
                </div>
                <div class="d-flex align-center gap-x-2" v-if="site.phone">
                  <VIcon icon="tabler-phone" size="18" />
                  <span>{{ site.phone }}</span>
                </div>
                <div class="d-flex align-center gap-x-2" v-if="site.address">
                  <VIcon icon="tabler-map-pin" size="18" />
                  <span>{{ site.address }}</span>
                </div>
              </div>
            </div>
          </VCol>

          <VCol cols="6" md="3">
            <h6 class="text-h6 mb-6">Pages</h6>
            <ul class="footer-links">
              <li v-for="link in pagesList" :key="link.name" class="mb-4">
                <RouterLink :to="link.to" class="text-decoration-none opacity-75 hover-opacity-100">
                  {{ link.name }}
                </RouterLink>
              </li>
            </ul>
          </VCol>

          <VCol cols="6" md="3">
            <h6 class="text-h6 mb-6">Resources</h6>
            <ul class="footer-links">
              <li class="mb-4"><a href="#" class="text-decoration-none opacity-75">Terms of Service</a></li>
              <li class="mb-4"><a href="#" class="text-decoration-none opacity-75">Privacy Policy</a></li>
            </ul>
          </VCol>
        </VRow>
      </VContainer>
    </div>

    <div class="footer-bottom py-4 bg-primary text-white">
      <VContainer>
        <div class="d-flex justify-space-between align-center flex-wrap gap-4">
          <div class="text-body-2">
            &copy; {{ new Date().getFullYear() }} {{ site.name }}. All rights reserved.
          </div>
          <div class="d-flex gap-x-4">
            <VIcon icon="tabler-brand-facebook" size="18" />
            <VIcon icon="tabler-brand-twitter" size="18" />
            <VIcon icon="tabler-brand-instagram" size="18" />
          </div>
        </div>
      </VContainer>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.footer-links {
  list-style: none;
  padding: 0;
  a {
    color: inherit;
    transition: opacity 0.2s;
    &:hover {
      opacity: 1 !important;
      color: rgb(var(--v-theme-primary));
    }
  }
}

.bg-surface {
  background-color: rgb(var(--v-theme-surface)) !important;
}

.bg-primary {
  background-color: rgb(var(--v-theme-primary)) !important;
}

.footer-top {
  border-radius: 60px 60px 0 0;
}
</style>
