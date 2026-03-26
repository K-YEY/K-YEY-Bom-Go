<script setup lang="ts">
import ClientPlanEditDialog from '@/components/dialogs/ClientPlanEditDialog.vue'
import ShipperCommissionEditDialog from '@/components/dialogs/ShipperCommissionEditDialog.vue'
import UpdateUserRoleDialog from '@/components/dialogs/UpdateUserRoleDialog.vue'
import { avatarText } from '@core/utils/formatters'

interface Props {
  userData: {
    id: number
    name: string
    username: string
    role: string
    roles?: any[]
    email: string
    status: string | boolean
    is_blocked: boolean
    avatar: string
    account_type: number
    phone: string
    created_at: string
    shipper?: {
      commission_rate: string
    }
    client?: {
      plan_id: number
      plan?: { title: string }
      address: string
    }
  }
}

const props = defineProps<Props>()
const emit = defineEmits(['update'])

const isUserInfoEditDialogVisible = ref(false)
const isUpdateRoleDialogVisible = ref(false)
const isChangePlanDialogVisible = ref(false)
const isUpdateCommissionDialogVisible = ref(false)

const onRoleUpdateSuccess = () => {
  emit('update')
}

// 👉 Role variant resolver
const resolveUserRoleVariant = (role: string) => {
  if (role === 'client') return { color: 'success', icon: 'tabler-user' }
  if (role === 'shipper') return { color: 'info', icon: 'tabler-truck' }
  if (role === 'admin') return { color: 'error', icon: 'tabler-server-2' }
  return { color: 'primary', icon: 'tabler-user' }
}

const refInputEl = ref<HTMLElement>()

const changeAvatar = (file: Event) => {
  const fileReader = new FileReader()
  const { files } = file.target as HTMLInputElement

  if (files && files.length) {
    fileReader.readAsDataURL(files[0])
    fileReader.onload = async () => {
      if (typeof fileReader.result === 'string') {
        // Send to server
        const formData = new FormData()
        formData.append('avatar', files[0])
        formData.append('_method', 'PUT')

        try {
          await $api(`/users/${props.userData.id}`, {
            method: 'POST', // Use POST with _method PUT for file upload
            body: formData,
          })
          emit('update')
        } catch (e) {
          console.error(e)
        }
      }
    }
  }
}

const resetAvatar = async () => {
  try {
    await $api(`/users/${props.userData.id}`, {
      method: 'PUT',
      body: { avatar: null },
    })
    emit('update')
  } catch (e) {
    console.error(e)
  }
}

const onUserInfoUpdate = async (updatedData: any) => {
  try {
    await $api(`/users/${props.userData.id}`, {
      method: 'PUT',
      body: updatedData,
    })
    emit('update')
    isUserInfoEditDialogVisible.value = false
    isChangePlanDialogVisible.value = false
    isUpdateCommissionDialogVisible.value = false
  } catch (e) {
    console.error(e)
  }
}

const suspendUser = async () => {
  try {
    await $api(`/users/${props.userData.id}/toggle-block`, {
      method: 'POST',
    })
    emit('update')
  } catch (e) {
    console.error(e)
  }
}
</script>

<template>
  <VRow>
    <!-- SECTION User Details -->
    <VCol cols="12">
      <VCard v-if="props.userData">
        <VCardText class="text-center pt-12">
          <!-- 👉 Avatar -->
          <div class="position-relative d-inline-block">
            <VAvatar
              rounded
              :size="120"
              :color="!props.userData.avatar ? 'primary' : undefined"
              :variant="!props.userData.avatar ? 'tonal' : undefined"
            >
              <VImg
                v-if="props.userData.avatar"
                :src="props.userData.avatar"
              />
              <span
                v-else
                class="text-5xl font-weight-medium"
              >
                {{ avatarText(props.userData.name) }}
              </span>
            </VAvatar>
            
            <!-- Upload Button -->
            <VBtn
              icon="tabler-camera"
              variant="elevated"
              color="primary"
              size="x-small"
              class="position-absolute"
              style="bottom: -10px; right: -10px;"
              @click="refInputEl?.click()"
            />
            <input
              ref="refInputEl"
              type="file"
              name="file"
              accept=".jpeg,.png,.jpg,GIF"
              hidden
              @input="changeAvatar"
            >
          </div>

          <!-- 👉 User name -->
          <h5 class="text-h5 mt-4">
            {{ props.userData.name }}
          </h5>

          <div class="d-flex align-center justify-center gap-2 mt-2">
            <!-- 👉 Role chip -->
            <VChip
              label
              :color="resolveUserRoleVariant(props.userData.role).color"
              size="small"
              class="text-capitalize"
            >
              {{ props.userData.role }}
            </VChip>

            <!-- Status chip -->
            <VChip
              label
              :color="props.userData.is_blocked ? 'error' : 'success'"
              size="small"
            >
              {{ props.userData.is_blocked ? 'Blocked' : 'Active' }}
            </VChip>
          </div>
        </VCardText>

        <VCardText>
          <!-- 👉 Details -->
          <h5 class="text-h5">
            Details
          </h5>

          <VDivider class="my-4" />

          <!-- 👉 User Details list -->
          <VList class="card-list mt-2">
            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  Username:
                  <div class="d-inline-block text-body-1">
                    {{ props.userData.username }}
                  </div>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  Phone:
                  <div class="d-inline-block text-body-1">
                    {{ props.userData.phone || 'N/A' }}
                  </div>
                </h6>
              </VListItemTitle>
            </VListItem>

            <!-- Project Specific Fields -->
            <template v-if="props.userData.account_type === 1">
              <VListItem>
                <VListItemTitle>
                  <h6 class="text-h6">
                    Shipping Plan:
                    <div class="d-inline-block text-body-1 text-primary">
                      {{ props.userData.client?.plan?.title || 'No Plan' }}
                    </div>
                  </h6>
                </VListItemTitle>
              </VListItem>
              <VListItem>
                <VListItemTitle>
                  <h6 class="text-h6">
                    Address:
                    <div class="d-inline-block text-body-1">
                      {{ props.userData.client?.address || 'N/A' }}
                    </div>
                  </h6>
                </VListItemTitle>
              </VListItem>
            </template>

            <template v-else-if="props.userData.account_type === 2">
              <VListItem>
                <VListItemTitle>
                  <h6 class="text-h6">
                    Commission Rate:
                    <div class="d-inline-block text-body-1 text-info">
                      {{ props.userData.shipper?.commission_rate }} EGP
                    </div>
                  </h6>
                </VListItemTitle>
              </VListItem>
            </template>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  Created At:
                  <div class="d-inline-block text-body-1">
                    {{ new Date(props.userData.created_at).toLocaleDateString() }}
                  </div>
                </h6>
              </VListItemTitle>
            </VListItem>
          </VList>
        </VCardText>

        <!-- 👉 Edit and Suspend button -->
        <VCardText class="d-flex justify-center gap-x-4">
          <VBtn
            variant="elevated"
            @click="isUserInfoEditDialogVisible = true"
          >
            Edit Profile
          </VBtn>

          <VBtn
            variant="tonal"
            color="success"
            @click="isUpdateRoleDialogVisible = true"
          >
            Update Role
          </VBtn>

          <VBtn
            variant="tonal"
            :color="props.userData.is_blocked ? 'success' : 'error'"
            @click="suspendUser"
          >
            {{ props.userData.is_blocked ? 'Unblock User' : 'Block User' }}
          </VBtn>
        </VCardText>
      </VCard>
    </VCol>
    <!-- !SECTION -->

    <!-- SECTION Current Plan (If Client) -->
    <VCol v-if="props.userData.account_type === 1" cols="12">
      <VCard color="primary" variant="tonal">
        <VCardText>
          <div class="d-flex justify-space-between align-center">
            <div>
              <h6 class="text-h6 mb-1">Current Active Plan</h6>
              <h4 class="text-h4 text-primary">{{ props.userData.client?.plan?.title || 'Standard Plan' }}</h4>
            </div>
            <VAvatar size="48" color="primary" variant="elevated">
              <VIcon icon="tabler-premium-rights" size="28" />
            </VAvatar>
          </div>
          
          <VBtn
            block
            variant="elevated"
            color="primary"
            class="mt-6"
            @click="isChangePlanDialogVisible = true"
          >
            Change Shipping Plan
          </VBtn>
        </VCardText>
      </VCard>
    </VCol>

    <!-- SECTION Shipper Metrics (If Shipper) -->
    <VCol v-if="props.userData.account_type === 2" cols="12">
      <VCard color="info" variant="tonal">
        <VCardText>
          <div class="d-flex justify-space-between align-center">
            <div>
              <h6 class="text-h6 mb-1">Commission Per Order</h6>
              <h4 class="text-h4 text-info">{{ props.userData.shipper?.commission_rate }} EGP</h4>
            </div>
            <VAvatar size="48" color="info" variant="elevated">
              <VIcon icon="tabler-currency-dollar" size="28" />
            </VAvatar>
          </div>
          
          <VBtn
            block
            variant="elevated"
            color="info"
            class="mt-6"
            @click="isUpdateCommissionDialogVisible = true"
          >
            Update Commission
          </VBtn>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>

  <!-- 👉 Edit user info dialog -->
  <UserInfoEditDialog
    v-model:is-dialog-visible="isUserInfoEditDialogVisible"
    :user-data="{
      id: props.userData.id,
      name: props.userData.name,
      username: props.userData.username,
      phone: props.userData.phone,
      account_type: props.userData.account_type,
      commission_rate: props.userData.shipper?.commission_rate,
      plan_id: props.userData.client?.plan_id,
      address: props.userData.client?.address,
      is_blocked: props.userData.is_blocked
    }"
    @submit="onUserInfoUpdate"
  />

  <UpdateUserRoleDialog
    v-model:is-dialog-visible="isUpdateRoleDialogVisible"
    :user="{
      id: props.userData.id,
      name: props.userData.name,
      roles: props.userData.roles || [],
    }"
    @success="onRoleUpdateSuccess"
  />

  <ClientPlanEditDialog
    v-model:is-dialog-visible="isChangePlanDialogVisible"
    :plan-id="props.userData.client?.plan_id"
    :address="props.userData.client?.address"
    @submit="onUserInfoUpdate"
  />

  <ShipperCommissionEditDialog
    v-model:is-dialog-visible="isUpdateCommissionDialogVisible"
    :commission-rate="props.userData.shipper?.commission_rate"
    @submit="onUserInfoUpdate"
  />
</template>

<style lang="scss" scoped>
.card-list {
  --v-card-list-gap: 0.5rem;
}

.text-capitalize {
  text-transform: capitalize !important;
}
</style>
