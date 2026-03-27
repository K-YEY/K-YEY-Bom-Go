import { createApp } from 'vue'

import App from '@/App.vue'
import { registerPlugins } from '@core/utils/plugins'

// Styles
import '@core-scss/template/index.scss'
import '@styles/styles.scss'

// Create vue app
const app = createApp(App)

// Register plugins
registerPlugins(app)

// Initializing the stores with configuration data from Settings
import { useSettingsStore } from '@core/stores/settings'
const settingsStore = useSettingsStore()
settingsStore.fetchSettings()

// Mount vue app
app.mount('#app')
