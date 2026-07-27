import type { CapacitorConfig } from '@capacitor/cli'

const config: CapacitorConfig = {
  appId: 'com.timurbersinar.servicekkprl',
  appName: 'ServiceKKPRL',
  webDir: 'dist',
  server: {
    androidScheme: 'https',
  },
  plugins: {
    PushNotifications: {
      presentationOptions: ['sound', 'alert', 'banner', 'list'],
    },
  },
}

export default config
