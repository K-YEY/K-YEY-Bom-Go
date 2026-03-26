<script lang="ts" setup>
import UserInvoiceTable from './UserInvoiceTable.vue';

interface Props {
  userData: {
    id: number
    account_type: number
    login_sessions: any[]
  }
}

const props = defineProps<Props>()

const formatDate = (date: string) => {
  return new Date(date).toLocaleString()
}
</script>

<template>
  <VRow>
    <VCol cols="12">
      <!-- 👉 User Activity timeline -->
      <VCard title="Recent Activity & Sessions">
        <VCardText>
          <VTimeline
            side="end"
            align="start"
            line-inset="8"
            truncate-line="start"
            density="compact"
          >
            <VTimelineItem
              v-for="session in props.userData.login_sessions"
              :key="session.id"
              :dot-color="session.is_active ? 'success' : 'secondary'"
              size="x-small"
            >
              <div class="d-flex justify-space-between align-center gap-2 flex-wrap mb-2">
                <span class="app-timeline-title">
                  Login from {{ session.browser }} ({{ session.platform }})
                </span>
                <span class="app-timeline-meta">{{ formatDate(session.login_at) }}</span>
              </div>

              <div class="app-timeline-text mt-1">
                IP: {{ session.ip_address }} | Location: {{ session.country }}, {{ session.city }}
              </div>
              
              <VChip
                v-if="session.is_current"
                label
                color="primary"
                size="small"
                class="mt-2"
              >
                Current Session
              </VChip>
            </VTimelineItem>
            
            <VTimelineItem
              v-if="!props.userData.login_sessions?.length"
              dot-color="warning"
              size="x-small"
            >
              <div class="app-timeline-title">No recent login sessions found</div>
            </VTimelineItem>
          </VTimeline>
        </VCardText>
      </VCard>
    </VCol>

    <VCol cols="12">
      <UserInvoiceTable :user-data="props.userData" />
    </VCol>
  </VRow>
</template>
