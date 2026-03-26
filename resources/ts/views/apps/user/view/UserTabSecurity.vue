<script setup lang="ts">
import { computed, ref } from 'vue'

interface Props {
  userData: {
    id: number
    login_sessions: {
      id: number
      device_type: string
      browser_name: string
      ip_address: string
      location: string
      last_activity: string
    }[]
  }
}

const props = defineProps<Props>()

const isNewPasswordVisible = ref(false)
const isConfirmPasswordVisible = ref(false)
const isTwoFactorDialogOpen = ref(false)
const smsVerificationNumber = ref('+1(968) 819-2547')

const passwordData = ref({
  password: '',
  confirmPassword: '',
})

const isSubmitting = ref(false)
const alertMessage = ref('')
const alertType = ref<'success' | 'error' | 'warning'>('warning')

const onSubmit = async () => {
  if (passwordData.value.password !== passwordData.value.confirmPassword) {
    alertMessage.value = 'Passwords do not match'
    alertType.value = 'error'
    return
  }

  isSubmitting.value = true
  try {
    // @ts-ignore
    await $api(`/users/${props.userData.id}`, {
      method: 'PUT',
      body: { password: passwordData.value.password },
    })
    alertMessage.value = 'Password updated successfully'
    alertType.value = 'success'
    passwordData.value = { password: '', confirmPassword: '' }
  } catch (e: any) {
    alertMessage.value = e.response?._data?.message || 'Failed to update password'
    alertType.value = 'error'
  } finally {
    isSubmitting.value = false
  }
}

// Recent devices Headers
const recentDeviceHeader = [
  { title: 'BROWSER', key: 'browser' },
  { title: 'DEVICE', key: 'device' },
  { title: 'LOCATION', key: 'location' },
  { title: 'RECENT ACTIVITY', key: 'activity' },
]

const resolveDeviceIcon = (platform: string) => {
  if (platform.toLowerCase().includes('windows')) return { icon: 'tabler-brand-windows', color: 'info' }
  if (platform.toLowerCase().includes('android')) return { icon: 'tabler-brand-android', color: 'success' }
  if (platform.toLowerCase().includes('apple') || platform.toLowerCase().includes('ios') || platform.toLowerCase().includes('mac')) return { icon: 'tabler-brand-apple', color: 'secondary' }
  return { icon: 'tabler-device-laptop', color: 'primary' }
}

const recentDevices = computed(() => {
  return props.userData.login_sessions.map(session => {
    const { icon, color } = resolveDeviceIcon(session.device_type)

    return {
      browser: `${session.browser_name} on ${session.device_type}`,
      icon,
      color,
      device: session.device_type,
      location: session.location,
      activity: session.last_activity,
    }
  })
})
</script>

<template>
  <VRow>
    <VCol cols="12">
      <!-- 👉 Change password -->
      <VCard title="Change Password">
        <VCardText>
          <VAlert
            v-if="alertMessage"
            closable
            variant="tonal"
            :type="alertType"
            class="mb-4"
            :text="alertMessage"
          />
          
          <VAlert
            v-else
            closable
            variant="tonal"
            color="warning"
            class="mb-4"
            title="Ensure that these requirements are met"
            text="Minimum 8 characters long"
          />

          <VForm @submit.prevent="onSubmit">
            <VRow>
              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  v-model="passwordData.password"
                  label="New Password"
                  placeholder="············"
                  :type="isNewPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isNewPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isNewPasswordVisible = !isNewPasswordVisible"
                />
              </VCol>
              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  v-model="passwordData.confirmPassword"
                  label="Confirm Password"
                  autocomplete="confirm-password"
                  placeholder="············"
                  :type="isConfirmPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isConfirmPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isConfirmPasswordVisible = !isConfirmPasswordVisible"
                />
              </VCol>

              <VCol cols="12">
                <VBtn
                  type="submit"
                  :loading="isSubmitting"
                >
                  Change Password
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>


    <VCol cols="12">
      <!-- 👉 Recent devices -->

      <VCard title="Recent devices">
        <VDivider />
        <VDataTable
          :items="recentDevices"
          :headers="recentDeviceHeader"
          hide-default-footer
          class="text-no-wrap"
        >
          <template #item.browser="{ item }">
            <div class="d-flex align-center gap-x-4">
              <VIcon
                :icon="item.icon"
                :color="item.color"
                :size="22"
              />
              <div class="text-body-1 text-high-emphasis">
                {{ item.browser }}
              </div>
            </div>
          </template>
          <!-- TODO Refactor this after vuetify provides proper solution for removing default footer -->
          <template #bottom />
        </VDataTable>
      </VCard>
    </VCol>
  </VRow>

  <!-- 👉 Enable One Time Password Dialog -->
  <TwoFactorAuthDialog
    v-model:is-dialog-visible="isTwoFactorDialogOpen"
    :sms-code="smsVerificationNumber"
  />
</template>
