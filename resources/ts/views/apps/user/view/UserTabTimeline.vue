<script setup lang="ts">
interface Props {
  userData: {
    id: number
  }
}

const props = defineProps<Props>()

const logs = ref<any[]>([])
const isFetching = ref(false)
const totalLogs = ref(0)
const itemsPerPage = ref(10)
const page = ref(1)

const fetchLogs = async () => {
  isFetching.value = true
  try {
    const res = await $api('/activity-logs', {
      params: { 
        user_id: props.userData.id, 
        itemsPerPage: itemsPerPage.value,
        page: page.value
      }
    })
    logs.value = res.data || []
    totalLogs.value = res.total || 0
  } catch (e) {
    // 
  }
  isFetching.value = false
}

watch([page, itemsPerPage], fetchLogs)

const headers = [
  { title: 'Action', key: 'action' },
  { title: 'Model', key: 'model_type' },
  { title: 'Message', key: 'message' },
  { title: 'Date', key: 'created_at' },
]

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

onMounted(() => {
  fetchLogs()
})
</script>

<template>
  <VCard title="User Activity History">
    <VCardText>
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="headers"
        :items="logs"
        :items-length="totalLogs"
        :loading="isFetching"
        class="text-no-wrap"
      >
        <template #item.action="{ item }: { item: any }">
          <VChip
            :color="getColor(item.action)"
            size="small"
            label
            class="text-capitalize"
          >
            <VIcon start :icon="getIcon(item.action)" size="14" />
            {{ item.action }}
          </VChip>
        </template>

        <template #item.model_type="{ item }: { item: any }">
          <span class="font-weight-medium">
            {{ item.model_type?.split('\\').pop() }}
          </span>
        </template>

        <template #item.created_at="{ item }: { item: any }">
          <span class="text-xs text-disabled">
            {{ new Date(item.created_at).toLocaleString() }}
          </span>
        </template>

        <template #bottom>
          <TablePagination
            v-model:page="page"
            :items-per-page="itemsPerPage"
            :total-items="totalLogs"
          />
        </template>
      </VDataTableServer>
    </VCardText>
  </VCard>
</template>
