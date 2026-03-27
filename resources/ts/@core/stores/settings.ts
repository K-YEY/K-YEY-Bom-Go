import { useApi } from '@/composables/useApi'
import { layoutConfig } from '@themeConfig'
import { defineStore } from 'pinia'

export const useSettingsStore = defineStore('settings', () => {
  const appTitle = ref(layoutConfig.app.title)
  const siteLogo = ref('')

  const fetchSettings = async () => {
    try {
      const { data } = await useApi('/app-config').get().json()
      if (data.value && data.value.site_identity) {
        const siteName = data.value.site_identity.site_name
        if (siteName) {
          appTitle.value = siteName
          // Also update the static layoutConfig for legacy components
          layoutConfig.app.title = siteName
          
          if (typeof document !== 'undefined') {
            document.title = siteName
            // Update manifest title if needed, or just standard title
          }
        }
      }
    } catch (error) {
      console.error('Failed to fetch app settings:', error)
    }
  }

  return {
    appTitle,
    siteLogo,
    fetchSettings,
  }
})
