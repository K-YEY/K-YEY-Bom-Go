import type { RouteRecordRaw } from 'vue-router/auto'


// 👉 Redirects
export const redirects: RouteRecordRaw[] = [
  {
    path: '/pages/user-profile',
    name: 'pages-user-profile',
    redirect: () => ({ name: 'pages-user-profile-tab', params: { tab: 'profile' } }),
  },
  {
    path: '/pages/account-settings',
    name: 'pages-account-settings',
    redirect: () => ({ name: 'pages-account-settings-tab', params: { tab: 'account' } }),
  },
]

// 👉 Custom Routes
export const routes: RouteRecordRaw[] = []


