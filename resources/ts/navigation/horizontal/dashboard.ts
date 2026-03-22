export default [
  {
    title: 'Dashboards',
    icon: { icon: 'tabler-smart-home' },
    children: [
      {
        title: 'Orders',
        to: 'dashboards-orders',
        icon: { icon: 'tabler-package' },
      },
      {
        title: 'Expenses',
        to: 'expenses',
        icon: { icon: 'tabler-receipt' },
        action: 'manage',
        subject: 'expense.page',
      },
    ],
  },

]
