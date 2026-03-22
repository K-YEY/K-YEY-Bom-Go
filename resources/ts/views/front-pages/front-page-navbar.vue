<script setup lang="ts">
import { useWindowScroll } from '@vueuse/core'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { useDisplay } from 'vuetify'

import NavbarThemeSwitcher from '@/layouts/components/NavbarThemeSwitcher.vue'

const { data } = await useApi<any>('/landing-page')
const site = computed(() => data.value?.site ?? { name: 'Shipya', logo: '' })

const display = useDisplay()
const { y } = useWindowScroll()
const route = useRoute()
const sidebar = ref(false)

watch(() => display, () => {
  return display.mdAndUp ? sidebar.value = false : sidebar.value
}, { deep: true })
</script>

<template>
  <!-- 👉 Navigation drawer for mobile devices  -->
  <VNavigationDrawer
    v-model="sidebar"
    width="275"
    data-allow-mismatch
    disable-resize-watcher
  >
    <PerfectScrollbar
      :options="{ wheelPropagation: false }"
      class="h-100"
    >
      <div class="pa-4">
        <div class="d-flex align-center gap-x-2 mb-8">
          <VAvatar v-if="site.logo" :image="site.logo" size="32" />
          <VIcon v-else icon="tabler-truck" size="32" color="primary" />
          <h1 class="text-h1 font-weight-bold" style="font-size: 1.25rem;">{{ site.name }}</h1>
        </div>

        <div class="d-flex flex-column gap-y-4">
          <RouterLink
            v-for="(item, index) in ['Home', 'Features', 'Team', 'FAQ', 'Contact us']"
            :key="index"
            :to="{ name: 'front-pages-landing-page', hash: `#${item.toLowerCase().replace(' ', '-')}` }"
            class="nav-link font-weight-medium"
          >
            {{ item }}
          </RouterLink>

          <VBtn
            block
            color="primary"
            variant="elevated"
            :to="{ name: 'pages-authentication-login-v1' }"
            class="mt-4"
          >
            Login / Register
          </VBtn>
        </div>
      </div>

      <!-- Navigation drawer close icon -->
      <VIcon
        id="navigation-drawer-close-btn"
        icon="tabler-x"
        size="20"
        @click="sidebar = !sidebar"
      />
    </PerfectScrollbar>
  </VNavigationDrawer>

  <!-- 👉 Navbar for desktop devices  -->
  <div class="front-page-navbar">
    <div class="front-page-navbar">
      <VAppBar
        :color="$vuetify.theme.current.dark ? 'rgba(var(--v-theme-surface),0.38)' : 'rgba(var(--v-theme-surface), 0.38)'"
        :class="y > 10 ? 'app-bar-scrolled' : [$vuetify.theme.current.dark ? 'app-bar-dark' : 'app-bar-light', 'elevation-0']"
        class="navbar-blur"
      >
        <!-- toggle icon for mobile device -->
        <IconBtn
          id="vertical-nav-toggle-btn"
          class="ms-n3 me-2 d-inline-block d-md-none"
          @click="sidebar = !sidebar"
        >
          <VIcon
            size="26"
            icon="tabler-menu-2"
            color="rgba(var(--v-theme-on-surface))"
          />
        </IconBtn>

        <!-- Title and Landing page sections -->
        <div class="d-flex align-center">
          <RouterLink
            :to="{ name: 'front-pages-landing-page' }"
            class="d-flex align-center gap-x-2 me-6"
          >
            <VAvatar v-if="site.logo" :image="site.logo" size="28" />
            <VIcon v-else icon="tabler-truck" size="28" color="primary" />
            <h1 class="app-logo-title font-weight-bold" style="font-size: 1.25rem; color: rgba(var(--v-theme-on-surface))">
              {{ site.name }}
            </h1>
          </RouterLink>

          <!-- landing page sections -->
          <div class="text-base align-center d-none d-md-flex">
            <RouterLink
              v-for="(item, index) in ['Home', 'Features', 'Team', 'FAQ', 'Contact us']"
              :key="index"
              :to="{ name: 'front-pages-landing-page', hash: `#${item.toLowerCase().replace(' ', '-')}` }"
              class="nav-link font-weight-medium py-2 px-2 px-lg-4"
            >
              {{ item }}
            </RouterLink>
          </div>
        </div>

        <VSpacer />

        <div class="d-flex gap-x-4 align-center">
          <NavbarThemeSwitcher />

          <VBtn
            variant="elevated"
            color="primary"
            :to="{ name: 'pages-authentication-login-v1' }"
          >
            Get Started
          </VBtn>
        </div>
      </VAppBar>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.nav-link {
  text-decoration: none;
  &:not(:hover) {
    color: rgb(var(--v-theme-on-surface));
  }
  &:hover {
    color: rgb(var(--v-theme-primary));
  }
}

.front-page-navbar {
  .v-toolbar {
    max-inline-size: 1200px;
  }
}

@media (min-width: 1920px) {
  .front-page-navbar {
    .v-toolbar {
      max-inline-size: calc(1440px - 32px);
    }
  }
}

.app-bar-light {
  border: 1px solid rgba(var(--v-theme-surface), 68%);
  border-radius: 0.5rem;
  background-color: rgba(var(--v-theme-surface), 38%);
}

.app-bar-dark {
  border: 1px solid rgba(var(--v-theme-surface), 68%);
  border-radius: 0.5rem;
  background-color: rgba(255, 255, 255, 4%);
}

.app-bar-scrolled {
  border: 1px solid rgb(var(--v-theme-surface));
  border-radius: 0.5rem;
  background-color: rgb(var(--v-theme-surface)) !important;
}

.front-page-navbar::after {
  position: fixed;
  z-index: 2;
  backdrop-filter: saturate(100%) blur(6px);
  block-size: 5rem;
  content: "";
  inline-size: 100%;
}

#navigation-drawer-close-btn {
  position: absolute;
  cursor: pointer;
  inset-block-start: 1rem;
  inset-inline-end: 1rem;
}
</style>
