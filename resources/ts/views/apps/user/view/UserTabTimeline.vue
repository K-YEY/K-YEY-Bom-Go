<script setup lang="ts">
interface Props {
  userData: {
    id: number
  }
}

const props = defineProps<Props>()

const logs = ref<any[]>([])
const isFetching = ref(false)

const fetchLogs = async () => {
  isFetching.value = true
  try {
    const res = await $api('/activity-logs', {
      params: { user_id: props.userData.id, itemsPerPage: 20 }
    })
    logs.value = res.data || []
  } catch (e) {
    console.error(e)
  }
  isFetching.value = false
}

onMounted(() => {
  fetchLogs()
})

const getIcon = (action: string) => {
  if (action === 'created') return 'tabler-circle-plus'
  if (action === 'updated') return 'tabler-edit'
  if (action === 'deleted') return 'tabler-trash'
  return 'tabler-circle-check'
}

const getColor = (action: string) => {
  if (action === 'created') return 'success'
  if (action === 'updated') return 'info'
  if (action === 'deleted') return 'error'
  return 'primary'
}

const getLabel = (log: any) => {
  const model = log.model_type?.split('\\').pop() || 'Record'
  return `${log.action} ${model}`
}
</script>

<template>
  <VCard title="User Activity Timeline">
    <VCardText>
      <VTimeline
        side="end"
        align="start"
        truncate-line="both"
        density="compact"
        class="v-timeline-density-compact"
      >
        <VTimelineItem
          v-for="log in logs"
          :key="log.id"
          :dot-color="getColor(log.action)"
          size="x-small"
        >
          <template #icon>
            <VIcon
              :icon="getIcon(log.action)"
              size="12"
            />
          </template>

          <div class="d-flex justify-space-between align-center flex-wrap gap-2 mb-1">
            <span class="app-timeline-title">
              {{ getLabel(log) }}
            </span>
            <span class="app-timeline-meta">{{ new Date(log.created_at).toLocaleString() }}</span>
          </div>

          <p class="app-timeline-text mb-2">
            {{ log.message }}
          </p>

          <div v-if="log.new_values && Object.keys(log.new_values).length" class="d-inline-flex align-center bg-light rounded pa-2">
            <VIcon icon="tabler-info-circle" size="16" class="me-2" />
            <span class="text-xs">Data Updated</span>
          </div>
        </VTimelineItem>
      </VTimeline>

      <div v-if="!logs.length && !isFetching" class="text-center py-4 text-disabled">
        No activity logs found for this user.
      </div>
      
      <div v-if="isFetching" class="text-center py-4">
        <VProgressCircular indeterminate color="primary" />
      </div>
    </VCardText>
  </VCard>
</template>
