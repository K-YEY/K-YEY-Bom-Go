<script setup lang="ts">
import UserBioPanel from '@/views/apps/user/view/UserBioPanel.vue'
import UserTabAccount from '@/views/apps/user/view/UserTabAccount.vue'
import UserTabBillingsPlans from '@/views/apps/user/view/UserTabBillingsPlans.vue'
import UserTabConnections from '@/views/apps/user/view/UserTabConnections.vue'
import UserTabNotifications from '@/views/apps/user/view/UserTabNotifications.vue'
import UserTabSecurity from '@/views/apps/user/view/UserTabSecurity.vue'


const route = useRoute('apps-user-view-id')

const userTab = ref(null)

const tabs = [
  { icon: 'tabler-users', title: 'Account' },
  { icon: 'tabler-history', title: 'Timeline' },
  { icon: 'tabler-lock', title: 'Security' },
  { icon: 'tabler-bookmark', title: 'Billing & Plan' },
  { icon: 'tabler-bell', title: 'Notifications' },
  { icon: 'tabler-link', title: 'Connections' },
]

// Re-fetching function
const { data: userData, execute: fetchUserData } = await useApi<any>(`/users/${route.params.id}`)
</script>

<template>
  <VRow v-if="userData">
    <VCol
      cols="12"
      md="5"
      lg="4"
    >
      <UserBioPanel
        :user-data="userData"
        @update="fetchUserData"
      />
    </VCol>

    <VCol
      cols="12"
      md="7"
      lg="8"
    >
      <VTabs
        v-model="userTab"
        class="v-tabs-pill"
      >
        <VTab
          v-for="tab in tabs"
          :key="tab.icon"
        >
          <VIcon
            :size="18"
            :icon="tab.icon"
            class="me-1"
          />
          <span>{{ tab.title }}</span>
        </VTab>
      </VTabs>

      <VWindow
        v-model="userTab"
        class="mt-6 disable-tab-transition"
        :touch="false"
      >
        <VWindowItem>
          <UserTabAccount :user-data="userData" />
        </VWindowItem>

        <VWindowItem>
          <UserTabTimeline :user-data="userData" />
        </VWindowItem>

        <VWindowItem>
          <UserTabSecurity :user-data="userData" />
        </VWindowItem>

        <VWindowItem>
          <UserTabBillingsPlans :user-data="userData" />
        </VWindowItem>

        <VWindowItem>
          <UserTabNotifications :user-data="userData" />
        </VWindowItem>

        <VWindowItem>
          <UserTabConnections :user-data="userData" />
        </VWindowItem>
      </VWindow>
    </VCol>
  </VRow>
  <div v-else>
    <VAlert
      type="error"
      variant="tonal"
    >
      Invoice with ID  {{ route.params.id }} not found!
    </VAlert>
  </div>
</template>
