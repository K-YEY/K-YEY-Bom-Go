import type { SearchResults } from '@db/app-bar-search/types'

interface DB {
  searchItems: SearchResults[]
}

export const db: DB = {
  searchItems: [
    {
      title: 'Operations & Tracking',
      category: 'operations',
      children: [
        { url: { name: 'dashboards-orders' }, icon: 'tabler-chart-bar', title: 'Orders Dashboard' },
        { url: { name: 'apps-orders' }, icon: 'tabler-package', title: 'Order List' },
        { url: { name: 'apps-orders-scan' }, icon: 'tabler-scan', title: 'Scan Orders' },
        { url: { name: 'apps-orders-hold-outfordelivery' }, icon: 'tabler-truck-delivery', title: 'HOLD & Out For Delivery' },
      ],
    },
    {
      title: 'Logistics',
      category: 'logistics',
      children: [
        { url: { name: 'apps-operations-pickups' }, icon: 'tabler-truck', title: 'Pickups' },
        { url: { name: 'apps-operations-visits' }, icon: 'tabler-map-pin', title: 'Visits' },
        { url: { name: 'apps-operations-material-requests' }, icon: 'tabler-box', title: 'Material Requests' },
      ],
    },
    {
      title: 'Financials',
      category: 'financials',
      children: [
        { url: { name: 'apps-orders-shipper-collections' }, icon: 'tabler-cash', title: 'Shipper Collections' },
        { url: { name: 'apps-orders-client-settlements' }, icon: 'tabler-currency-dollar', title: 'Client Settlements' },
        { url: { name: 'apps-orders-shipper-returns' }, icon: 'tabler-arrow-back', title: 'Shipper Returns' },
        { url: { name: 'apps-orders-client-returns' }, icon: 'tabler-package-export', title: 'Client Returns' },
      ],
    },
    {
      title: 'Users & CRM',
      category: 'users',
      children: [
        { url: { name: 'apps-user-clients' }, icon: 'tabler-user', title: 'Clients List' },
        { url: { name: 'apps-user-shippers' }, icon: 'tabler-truck', title: 'Shippers List' },
        { url: { name: 'apps-user-list' }, icon: 'tabler-users-group', title: 'Staff Users' },
        { url: { name: 'apps-roles' }, icon: 'tabler-lock', title: 'Roles & Permissions' },
      ],
    },
    {
      title: 'System Management',
      category: 'management',
      children: [
        { url: { name: 'apps-settings' }, icon: 'tabler-settings', title: 'System Settings' },
        { url: { name: 'apps-area' }, icon: 'tabler-map', title: 'Areas Management' },
        { url: { name: 'apps-plan' }, icon: 'tabler-file-text', title: 'Shipping Plans' },
        { url: { name: 'apps-activity-logs' }, icon: 'tabler-history', title: 'Activity Logs' },
      ],
    },
  ],
}
