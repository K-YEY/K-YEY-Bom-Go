<script setup lang="ts">
import { useApi } from "@/composables/useApi";
import { createUrl } from "@core/composable/createUrl";
import { avatarText } from "@core/utils/formatters";

const searchQuery = ref("");
const selectedStatus = ref<string | null>(null);
const selectedApprovalStatus = ref<string | null>(null);
const selectedShipper = ref<number | null>(null);

const { can } = useAbility();

const STORAGE_KEY = "shipper-collections-visible-columns";

// Define permission mapping for columns
const columnPermissions: Record<string, string> = {
  shipper: "shipper-collection.column.shipper_user_id.view",
  collection_date: "shipper-collection.column.collection_date.view",
  number_of_orders: "shipper-collection.column.number_of_orders.view",
  total_amount: "shipper-collection.column.total_amount.view",
  net_amount: "shipper-collection.column.net_amount.view",
  status: "shipper-collection.column.status.view",
};

// 👉 Headers
const headers = [
  { title: "#ID", key: "id" },
  { title: "Shipper", key: "shipper" },
  { title: "Date", key: "collection_date" },
  { title: "Orders", key: "number_of_orders" },
  { title: "Total", key: "total_amount" },
  { title: "Fees", key: "fees" },
  { title: "Shipper Fees", key: "shipper_fees" },
  { title: "Net", key: "net_amount" },
  { title: "Status", key: "status" },
  { title: "Approval", key: "approval_status" },
  { title: "Actions", key: "actions", sortable: false, width: "180px" },
];

const visibleHeaderKeys = ref(
  JSON.parse(
    localStorage.getItem(STORAGE_KEY) ||
      JSON.stringify(headers.map((h) => h.key)),
  ),
);

const activeHeaders = computed(() => {
  return headers.filter((h) => {
    // 1. Check user manual visibility
    if (!visibleHeaderKeys.value.includes(h.key)) return false;

    // 2. Check permission
    const perm = columnPermissions[h.key];
    if (perm && !can(perm as any, "all" as any)) return false;

    return true;
  });
});

const filteredHeadersForMenu = computed(() => {
  return headers.filter((h) => {
    const perm = columnPermissions[h.key];
    return !perm || can(perm as any, "all" as any);
  });
});

watch(visibleHeaderKeys, (newVal) => {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(newVal));
});

// 👉 Fetching Collections
const {
  data: collectionsData,
  execute: fetchCollections,
  isFetching,
} = await useApi<any>(
  createUrl("/shipper-collections", {
    query: {
      status: selectedStatus,
      approval_status: selectedApprovalStatus,
      shipper_user_id: selectedShipper,
      search: searchQuery,
    },
  }),
);

const collections = computed(() => collectionsData.value || []);

const statusColors: any = {
  PENDING: "warning",
  COMPLETED: "success",
  CANCELLED: "error",
};

const approvalColors: any = {
  PENDING: "warning",
  APPROVED: "success",
  REJECTED: "error",
};

// 👉 Actions
const isDetailsDialogVisible = ref(false);
const selectedCollection = ref<any>(null);
const isCreateDialogVisible = ref(false);
const isApprovalDialogVisible = ref(false);
const approvalAction = ref<"approve" | "reject">("approve");
const approvalNote = ref("");
const processingAction = ref(false);

const viewDetails = async (id: number) => {
  const { data } = await useApi<any>(`/shipper-collections/${id}`).get().json();
  if (data.value) {
    selectedCollection.value = data.value;
    isDetailsDialogVisible.value = true;
  }
};

const openApprovalDialog = (id: number, action: "approve" | "reject") => {
  selectedCollection.value = collections.value.find((c: any) => c.id === id);
  approvalAction.value = action;
  approvalNote.value = "";
  isApprovalDialogVisible.value = true;
};

const submitApproval = async () => {
  if (!selectedCollection.value) return;

  processingAction.value = true;
  const url = `/shipper-collections/${selectedCollection.value.id}/${approvalAction.value}`;
  const { error } = await useApi(url)
    .patch({ approval_note: approvalNote.value })
    .json();

  if (!error.value) {
    isApprovalDialogVisible.value = false;
    fetchCollections();
  }
  processingAction.value = false;
};

const updateStatus = async (id: number, status: string) => {
  processingAction.value = true;
  const { error } = await useApi(`/shipper-collections/${id}`)
    .patch({ status })
    .json();

  if (!error.value) {
    fetchCollections();
  }
  processingAction.value = false;
};

// 👉 Shippers for filter
const { data: shippersData } = await useApi<any>("/shippers").get().json();
const shippers = computed(() => {
  const data = Array.isArray(shippersData.value.data)
    ? shippersData.value.data
    : [];
  return data.map((s: any) => ({
    ...s,
    name: s.user?.name || "Unknown",
  }));
});

import ShipperCollectionModal from "./ShipperCollectionModal.vue";
const printInvoice = (id: number) => {
  window.open(`/apps/orders/print/${id}?type=collection`, "_blank");
};

// 👉 Export
const selectedIds = ref<number[]>([]);

const exportCollections = async () => {
  const params: any = {};

  if (selectedIds.value.length > 0) {
    params.ids = selectedIds.value.join(",");
  } else {
    if (searchQuery.value) params.search = searchQuery.value;
    if (selectedStatus.value) params.status = selectedStatus.value;
    if (selectedApprovalStatus.value)
      params.approval_status = selectedApprovalStatus.value;
    if (selectedShipper.value) params.shipper_user_id = selectedShipper.value;
  }

  const queryParams = new URLSearchParams(params).toString();
  const token = useCookie("accessToken").value || "";
  window.open(
    `/api/shipper-collections/export?${queryParams}&token=${token}`,
    "_blank",
  );
};
</script>

<template>
  <section>
    <ShipperCollectionModal
      v-model:is-dialog-visible="isCreateDialogVisible"
      @collection-created="fetchCollections"
    />
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4 align-center">
        <div class="d-flex align-center flex-wrap gap-4 flex-grow-1">
          <div style="inline-size: 15rem">
            <AppTextField
              v-model="searchQuery"
              placeholder="Search Shipper..."
              prepend-inner-icon="tabler-search"
              clearable
            />
          </div>
          <AppSelect
            v-model="selectedShipper"
            placeholder="Select Shipper"
            :items="shippers"
            item-title="name"
            item-value="user_id"
            clearable
            style="inline-size: 15rem"
          />
          <AppSelect
            v-model="selectedStatus"
            placeholder="Status"
            :items="['PENDING', 'COMPLETED', 'CANCELLED']"
            clearable
            style="inline-size: 10rem"
          />
          <AppSelect
            v-model="selectedApprovalStatus"
            placeholder="Approval"
            :items="['PENDING', 'APPROVED', 'REJECTED']"
            clearable
            style="inline-size: 10rem"
          />
        </div>
        <div class="d-flex gap-2">
          <VBtn
            v-if="can('shipper-collection.export' as any, 'all' as any)"
            color="success"
            variant="tonal"
            prepend-icon="tabler-file-spreadsheet"
            @click="exportCollections"
          >
            Export Excel
          </VBtn>
          <VBtn
            v-if="can('shipper-collection.create' as any, 'all' as any)"
            color="primary"
            prepend-icon="tabler-plus"
            @click="isCreateDialogVisible = true"
          >
            Create Collection
          </VBtn>

          <!-- 👉 Column Visibility Toggle -->
          <VMenu :close-on-content-click="false">
            <template #activator="{ props }">
              <VBtn icon variant="tonal" color="secondary" v-bind="props">
                <VIcon icon="tabler-layout-columns" />
              </VBtn>
            </template>
            <VList class="pa-2">
              <VListItem
                v-for="h in filteredHeadersForMenu"
                :key="h.key"
                density="compact"
              >
                <VCheckbox
                  v-model="visibleHeaderKeys"
                  :value="h.key"
                  :label="h.title"
                  hide-details
                  density="compact"
                />
              </VListItem>
            </VList>
          </VMenu>
        </div>
      </VCardText>

      <VDivider />

      <VDataTable
        v-model:selected="selectedIds"
        show-select
        :items="collections"
        :headers="activeHeaders"
        :loading="isFetching"
        class="text-no-wrap"
        loading-text="تحميل البيانات..."
      >
        <!-- ID -->
        <template #item.id="{ item }: { item: any }">
          <span class="text-primary font-weight-bold">#{{ item.id }}</span>
        </template>

        <!-- Shipper -->
        <template #item.shipper="{ item }: { item: any }">
          <div class="d-flex align-center gap-x-2">
            <VAvatar size="28" color="info" variant="tonal">
              <span class="text-xs">{{
                avatarText(item.shipper?.name || "S")
              }}</span>
            </VAvatar>
            <span class="text-sm text-high-emphasis">{{
              item.shipper?.name || "-"
            }}</span>
          </div>
        </template>

        <!-- Date -->
        <template #item.collection_date="{ item }: { item: any }">
          <span class="text-sm">{{
            new Date(item.collection_date).toLocaleDateString()
          }}</span>
        </template>

        <!-- Money -->
        <template #item.total_amount="{ item }: { item: any }">
          <span class="text-base font-weight-medium"
            >{{ item.total_amount }} EGP</span
          >
        </template>
        <template #item.fees="{ item }: { item: any }">
          <span class="text-error">{{ item.fees }} EGP</span>
        </template>
        <template #item.shipper_fees="{ item }: { item: any }">
          <span class="text-base font-weight-medium"
            >{{ item.shipper_fees }} EGP</span
          >
        </template>
        <template #item.net_amount="{ item }: { item: any }">
          <span class="text-base font-weight-bold text-success"
            >{{ item.net_amount }} EGP</span
          >
        </template>

        <!-- Status -->
        <template #item.status="{ item }: { item: any }">
          <VChip
            size="small"
            :color="statusColors[item.status]"
            variant="tonal"
            class="text-capitalize"
          >
            {{ item.status }}
          </VChip>
        </template>

        <!-- Approval -->
        <template #item.approval_status="{ item }: { item: any }">
          <VChip
            size="small"
            :color="approvalColors[item.approval_status]"
            variant="tonal"
            class="text-capitalize"
          >
            {{ item.approval_status }}
          </VChip>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }: { item: any }">
          <div class="d-flex gap-1 align-center">
            <!-- View Details -->
            <IconBtn
              v-if="can('shipper-collection.view' as any, 'all' as any)"
              size="small"
              @click="viewDetails(item.id)"
            >
              <VIcon icon="tabler-eye" />
              <VTooltip activator="parent">View Details</VTooltip>
            </IconBtn>

            <!-- Print Invoice -->
            <VBtn
              v-if="can('shipper-collection.view' as any, 'all' as any)"
              size="small"
              icon
              color="primary"
              variant="tonal"
              class="rounded"
              @click="printInvoice(item.id)"
            >
              <VIcon icon="tabler-file-invoice" size="20" />
              <VTooltip activator="parent">Print Invoice</VTooltip>
            </VBtn>

            <!-- Approval Status (Approve/Reject) -->
            <template
              v-if="
                item.approval_status &&
                item.approval_status.toString().toUpperCase() === 'PENDING'
              "
            >
              <IconBtn
                v-if="can('shipper-collection.approve' as any, 'all' as any)"
                size="small"
                color="success"
                @click="openApprovalDialog(item.id, 'approve')"
              >
                <VIcon icon="tabler-check" />
                <VTooltip activator="parent">Approve (Approval)</VTooltip>
              </IconBtn>
              <IconBtn
                v-if="can('shipper-collection.reject' as any, 'all' as any)"
                size="small"
                color="error"
                @click="openApprovalDialog(item.id, 'reject')"
              >
                <VIcon icon="tabler-x" />
                <VTooltip activator="parent">Reject (Approval)</VTooltip>
              </IconBtn>
            </template>

            <!-- Collection Status (Complete/Cancel) -->
            <template
              v-if="
                item.status === 'PENDING' && item.approval_status === 'APPROVED'
              "
            >
              <VDivider
                v-if="can('shipper-collection.update' as any, 'all' as any)"
                vertical
                class="mx-1"
              />
              <VBtn
                v-if="can('shipper-collection.update' as any, 'all' as any)"
                size="x-small"
                color="success"
                variant="elevated"
                :loading="processingAction"
                @click="updateStatus(item.id, 'COMPLETED')"
              >
                Complete
              </VBtn>
              <VBtn
                v-if="can('shipper-collection.update' as any, 'all' as any)"
                size="x-small"
                color="error"
                variant="outlined"
                :loading="processingAction"
                @click="updateStatus(item.id, 'CANCELLED')"
              >
                Cancel
              </VBtn>
            </template>
          </div>
        </template>
      </VDataTable>
    </VCard>

    <!-- Details Dialog -->
    <VDialog v-model="isDetailsDialogVisible" max-width="800">
      <VCard :title="`Collection Details - #${selectedCollection?.id}`">
        <VCardText>
          <VRow>
            <VCol cols="12" md="6">
              <div class="text-subtitle-2 mb-1">Total Amount</div>
              <div class="text-body-1 font-weight-bold">
                {{ selectedCollection?.total_amount }} EGP
              </div>
            </VCol>

            <VCol cols="12" md="6">
              <div class="text-subtitle-2 mb-1">COD Amount</div>
              <div class="text-body-1 font-weight-bold text-success">
                {{ selectedCollection?.net_amount }} EGP
              </div>
            </VCol>
          </VRow>

          <VDivider class="my-4" />

          <div class="text-h6 mb-2">Orders</div>
          <VTable class="text-no-wrap">
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Code</th>
                <th>EX-CODE</th>
                <th>Client</th>
                <th></th>
                <th>Amount</th>
                <th>Commission</th>
                <th>Net</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="order in selectedCollection?.orders" :key="order.id">
                <td>#{{ order.id }}</td>
                <td>{{ order.code }}</td>
                <td>{{ order.client?.name || "-" }}</td>
                <td>{{ order.pivot?.order_amount }} EGP</td>
                <td class="text-error">{{ order.pivot?.shipper_fee }} EGP</td>
                <td class="text-success font-weight-bold">
                  {{ order.pivot?.net_amount }} EGP
                </td>
              </tr>
            </tbody>
          </VTable>
        </VCardText>
        <VCardActions>
          <VBtn
            v-if="
              selectedCollection?.approval_status &&
              selectedCollection.approval_status.toString().toUpperCase() ===
                'PENDING'
            "
            color="success"
            variant="tonal"
            prepend-icon="tabler-check"
            @click="openApprovalDialog(selectedCollection.id, 'approve')"
          >
            Approve
          </VBtn>
          <VBtn
            v-if="
              selectedCollection?.approval_status &&
              selectedCollection.approval_status.toString().toUpperCase() ===
                'PENDING'
            "
            color="error"
            variant="tonal"
            prepend-icon="tabler-x"
            @click="openApprovalDialog(selectedCollection.id, 'reject')"
          >
            Reject
          </VBtn>
          <VSpacer />
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isDetailsDialogVisible = false"
            >Close</VBtn
          >
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Approval Dialog -->
    <VDialog v-model="isApprovalDialogVisible" max-width="500">
      <VCard
        :title="
          approvalAction === 'approve'
            ? 'Approve Collection'
            : 'Reject Collection'
        "
      >
        <VCardText>
          <p>
            Are you sure you want to {{ approvalAction }} collection #{{
              selectedCollection?.id
            }}?
          </p>
          <AppTextarea
            v-model="approvalNote"
            label="Note (Optional)"
            placeholder="Add a comment..."
            rows="3"
          />
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isApprovalDialogVisible = false"
            >Cancel</VBtn
          >
          <VBtn
            :color="approvalAction === 'approve' ? 'success' : 'error'"
            :loading="processingAction"
            @click="submitApproval"
          >
            {{ approvalAction === "approve" ? "Approve" : "Reject" }}
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </section>
</template>
