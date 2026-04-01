import { defineConfig } from 'vitepress'

export default defineConfig({
  title: "K-YEY Logistics",
  description: "Official Handover & Technical Specs",
  themeConfig: {
    logo: '/logo.png',
    nav: [
      { text: 'Home', link: '/' },
      { text: 'Overview', link: '/overview/introduction' },
      { text: 'Developer (Web)', link: '/developer/setup' },
      { text: 'Mobile (Flutter)', link: '/mobile/architecture' },
    ],
    sidebar: [
      {
        text: 'Project Overview',
        items: [
          { text: 'Introduction', link: '/overview/introduction' },
          { text: 'System Architecture', link: '/overview/architecture' },
          { text: 'Glossary', link: '/overview/terminology' }
        ]
      },
      {
        text: 'User Documentation',
        collapsed: false,
        items: [
          { text: 'Getting Started', link: '/user-guide/getting-started' },
          { text: 'Orders Management', link: '/user-guide/orders' },
          { text: 'Financial Operations', link: '/user-guide/financial' },
          { text: 'Role Permissions', link: '/user-guide/permissions' }
        ]
      },
      {
        text: 'Feature Deep Dives',
        collapsed: true,
        items: [
          { text: 'Order Management', link: '/user-guide/orders' },
          { text: 'Order Statuses & Reasons', link: '/features/statuses' },
          { text: 'Financial Transitions', link: '/user-guide/financial' },
          { text: 'Scanning & Logistics', link: '/features/scanning' }
        ]
      },
      {
        text: 'Admin & Operations',
        collapsed: true,
        items: [
          { text: 'User Management', link: '/admin/user-management' },
          { text: 'System Settings', link: '/admin/system-settings' },
          { text: 'Audit Logs', link: '/admin/audit-logs' }
        ]
      },
      {
        text: 'Developer Handover (Web)',
        collapsed: false,
        items: [
          { text: 'Environment Setup', link: '/developer/setup' },
          { text: 'Backend Core', link: '/developer/backend-core' },
          { text: 'Frontend Core', link: '/developer/frontend-core' },
          { text: 'API & Flows', link: '/developer/api-design' }
        ]
      },
      {
        text: 'Mobile App (Flutter)',
        collapsed: false,
        items: [
          { text: 'App Architecture', link: '/mobile/architecture' },
          { text: 'Cubit & State', link: '/mobile/state-management' },
          { text: 'Onboarding Mobile', link: '/mobile/flutter-setup' }
        ]
      },
      {
        text: 'Maintenance',
        items: [
          { text: 'Troubleshooting', link: '/maintenance/troubleshooting' },
          { text: 'Deployment Guide', link: '/maintenance/deployment' },
          { text: 'Handover Checklist', link: '/maintenance/checklist' }
        ]
      }
    ],
    socialLinks: [
      { icon: 'github', link: 'https://github.com/your-repo' }
    ]
  }
})
