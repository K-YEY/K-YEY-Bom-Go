<script setup lang="ts">
const settingsData = ref<any>({})
const activeTab = ref('site_identity')
const plansList = ref<any[]>([])

const fetchSettings = async () => {
  const response = await $api('/settings')
  settingsData.value = response
}

const fetchPlans = async () => {
  const response = await $api('/plans')
  plansList.value = response
}

const welcomePlansArray = computed({
  get: () => {
    const val = settingsData.value.plans?.welcome_plans
    if (!val) return []
    if (Array.isArray(val)) return val
    return val.split(',').map(Number)
  },
  set: (val: any[]) => {
    if (settingsData.value.plans) {
      settingsData.value.plans.welcome_plans = val.join(',')
    }
  },
})

const updateSettings = async () => {
  const flatSettings: any = {}
  Object.values(settingsData.value).forEach((group: any) => {
    Object.assign(flatSettings, group)
  })

  // Convert array to string if multiple selection used
  if (Array.isArray(flatSettings.welcome_plans)) {
    flatSettings.welcome_plans = flatSettings.welcome_plans.join(',')
  }

  try {
    await $api('/settings', {
      method: 'PUT',
      body: { settings: flatSettings },
    })
    alert('Settings updated successfully!')
    fetchSettings()
  } catch (error) {
    alert('Failed to update settings.')
  }
}

// Groups defined in model for UI organization
const tabs = [
  { title: 'Site Identity', value: 'site_identity', icon: 'tabler-info-circle' },
  { title: 'Working Hours', value: 'working_hours', icon: 'tabler-clock' },
  { title: 'Orders & Plans', value: 'orders', icon: 'tabler-shopping-cart' },
  { title: 'Themes', value: 'site_theme', icon: 'tabler-palette' },
  { title: 'Social Media', value: 'social_media', icon: 'tabler-brand-facebook' },
]

onMounted(() => {
  fetchSettings()
  fetchPlans()
})
</script>

<template>
  <VRow v-if="Object.keys(settingsData).length > 0">
    <VCol cols="12" md="3">
      <VTabs
        v-model="activeTab"
        direction="vertical"
        class="v-tabs-pill"
      >
        <VTab
          v-for="tab in tabs"
          :key="tab.value"
          :value="tab.value"
        >
          <VIcon
            start
            :icon="tab.icon"
          />
          {{ tab.title }}
        </VTab>
      </VTabs>
    </VCol>

    <VCol cols="12" md="9">
      <VCard>
        <VCardText>
          <VWindow v-model="activeTab">
            <!-- Site Identity -->
            <VWindowItem value="site_identity">
              <VRow>
                <VCol cols="12">
                  <h6 class="text-h6 mb-4">Site Information</h6>
                </VCol>
                <VCol cols="12">
                  <AppTextField v-model="settingsData.site_identity.site_name" label="Site Name" />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField v-model="settingsData.site_identity.site_email" label="Site Email" />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField v-model="settingsData.site_identity.site_phone" label="Site Phone" />
                </VCol>
                <VCol cols="12">
                  <AppTextField v-model="settingsData.site_identity.site_address" label="Site Address" />
                </VCol>
                <VCol cols="12">
                  <VSwitch
                    v-model="settingsData.site_identity.site_maintenance_mode"
                    label="Maintenance Mode"
                    true-value="true"
                    false-value="false"
                  />
                </VCol>
              </VRow>
            </VWindowItem>

            <!-- Working Hours -->
            <VWindowItem value="working_hours">
              <VRow>
                <VCol cols="12">
                  <h6 class="text-h6 mb-4">Order & Pickup Hours</h6>
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField 
                    v-model="settingsData.working_hours.working_hours_orders_start" 
                    label="Orders Start" 
                    type="time"
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField 
                    v-model="settingsData.working_hours.working_hours_orders_end" 
                    label="Orders End" 
                    type="time" 
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField 
                    v-model="settingsData.working_hours.working_hours_pickups_start" 
                    label="Pickups Start" 
                    type="time"
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField 
                    v-model="settingsData.working_hours.working_hours_pickups_end" 
                    label="Pickups End" 
                    type="time"
                  />
                </VCol>
              </VRow>
            </VWindowItem>

            <!-- Orders & Plans -->
            <VWindowItem value="orders">
              <VRow>
                <VCol cols="12">
                  <h6 class="text-h6 mb-4">Orders Config</h6>
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField v-model="settingsData.orders.order_prefix" label="Order Prefix" />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField v-model="settingsData.orders.order_digits" label="Order Digits" type="number" />
                </VCol>
                <VCol cols="12">
                  <h6 class="text-h6 mt-4 mb-4">Plans & Collections</h6>
                </VCol>
                <VCol cols="12" md="6">
                  <AppSelect
                    v-model="welcomePlansArray"
                    label="Welcome Plans"
                    :items="plansList"
                    item-title="name"
                    item-value="id"
                    multiple
                    chips
                    closable-chips
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField v-model="settingsData.collections.order_follow_up_hours" label="Follow-up Hours" type="number" />
                </VCol>
              </VRow>
            </VWindowItem>

            <!-- Themes -->
            <VWindowItem value="site_theme">
              <VRow>
                <VCol cols="12">
                  <h6 class="text-h6 mb-4">Colours (Light Mode)</h6>
                </VCol>
                <VCol cols="12" md="4">
                  <AppTextField v-model="settingsData.site_theme.site_color_primary_light" label="Primary Color" type="color" />
                </VCol>
                <VCol cols="12" md="4">
                  <AppTextField v-model="settingsData.site_theme.site_color_secondary_light" label="Secondary Color" type="color" />
                </VCol>
                <VCol cols="12" md="4">
                  <AppTextField v-model="settingsData.site_theme.site_color_text_light" label="Text Color" type="color" />
                </VCol>
                <VCol cols="12">
                  <h6 class="text-h6 mt-4 mb-4">Colours (Dark Mode)</h6>
                </VCol>
                <VCol cols="12" md="4">
                  <AppTextField v-model="settingsData.site_theme.site_color_primary_dark" label="Primary Color" type="color" />
                </VCol>
                <VCol cols="12" md="4">
                  <AppTextField v-model="settingsData.site_theme.site_color_secondary_dark" label="Secondary Color" type="color" />
                </VCol>
                <VCol cols="12" md="4">
                  <AppTextField v-model="settingsData.site_theme.site_color_text_dark" label="Text Color" type="color" />
                </VCol>
              </VRow>
            </VWindowItem>

            <!-- Social Media -->
            <VWindowItem value="social_media">
              <VRow>
                <VCol cols="12" md="6">
                  <AppTextField
                    v-model="settingsData.social_media.social_facebook"
                    label="Facebook URL"
                    prepend-inner-icon="tabler-brand-facebook"
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField
                    v-model="settingsData.social_media.social_instagram"
                    label="Instagram URL"
                    prepend-inner-icon="tabler-brand-instagram"
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField
                    v-model="settingsData.social_media.social_x"
                    label="X (Twitter) URL"
                    prepend-inner-icon="tabler-brand-x"
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField
                    v-model="settingsData.social_media.social_whatsapp"
                    label="WhatsApp Number"
                    prepend-inner-icon="tabler-brand-whatsapp"
                  />
                </VCol>
              </VRow>
            </VWindowItem>
          </VWindow>

          <VDivider class="my-6" />

          <div class="d-flex justify-end gap-4">
            <VBtn color="primary" @click="updateSettings">Save Changes</VBtn>
            <VBtn color="secondary" variant="tonal" @click="fetchSettings">Reset</VBtn>
          </div>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
  <div v-else class="text-center py-10">
     <VProgressCircular indeterminate color="primary" />
  </div>
</template>
