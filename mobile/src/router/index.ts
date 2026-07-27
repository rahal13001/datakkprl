import { createRouter, createWebHistory } from '@ionic/vue-router'
import type { RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useAppConfigStore } from '@/stores/appConfig'
import TabsPage from '@/pages/TabsPage.vue'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    redirect: '/tabs/dashboard',
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('@/pages/LoginPage.vue'),
    meta: { public: true },
  },
  {
    path: '/update-required',
    name: 'update-required',
    component: () => import('@/pages/UpdateRequiredPage.vue'),
    meta: { public: true },
  },
  {
    path: '/tabs',
    component: TabsPage,
    children: [
      { path: '', redirect: '/tabs/dashboard' },
      {
        path: 'dashboard',
        name: 'dashboard',
        component: () => import('@/pages/DashboardPage.vue'),
      },
      {
        path: 'requests',
        name: 'requests',
        component: () => import('@/pages/RequestsPage.vue'),
      },
      {
        path: 'notifications',
        name: 'notifications',
        component: () => import('@/pages/NotificationsPage.vue'),
      },
      {
        path: 'profile',
        name: 'profile',
        component: () => import('@/pages/ProfilePage.vue'),
      },
    ],
  },
  {
    path: '/requests/:ticket',
    name: 'request-detail',
    component: () => import('@/pages/RequestDetailPage.vue'),
  },
  {
    path: '/feedback',
    name: 'feedback',
    component: () => import('@/pages/FeedbackPage.vue'),
  },
  {
    path: '/feedback/satisfaction/:id',
    name: 'satisfaction-detail',
    component: () => import('@/pages/FeedbackPage.vue'),
  },
  {
    path: '/feedback/public/:id',
    name: 'public-feedback-detail',
    component: () => import('@/pages/FeedbackPage.vue'),
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

function firstAuthorizedRoute(auth: ReturnType<typeof useAuthStore>) {
  if (auth.can('dashboard', 'view')) return { name: 'dashboard' }
  if (auth.can('clients', 'list')) return { name: 'requests' }
  return { name: 'notifications' }
}

router.beforeEach((to) => {
  const auth = useAuthStore()
  const appConfig = useAppConfigStore()
  if (appConfig.unavailable && to.name !== 'update-required') {
    return { name: 'update-required' }
  }
  if (!appConfig.unavailable && to.name === 'update-required') {
    return { name: auth.authenticated ? 'dashboard' : 'login' }
  }
  if (!to.meta.public && !auth.authenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }
  if (to.name === 'login' && auth.authenticated) {
    return firstAuthorizedRoute(auth)
  }
  if (to.name === 'dashboard' && !auth.can('dashboard', 'view')) {
    return firstAuthorizedRoute(auth)
  }
  if ((to.name === 'requests' || to.name === 'request-detail') && !auth.can('clients', 'list')) {
    return firstAuthorizedRoute(auth)
  }
  if (
    to.name === 'feedback' &&
    !auth.can('satisfaction_surveys', 'list') &&
    !auth.can('public_feedback', 'list')
  ) {
    return firstAuthorizedRoute(auth)
  }
  if (to.name === 'satisfaction-detail' && !auth.can('satisfaction_surveys', 'view')) {
    return firstAuthorizedRoute(auth)
  }
  if (to.name === 'public-feedback-detail' && !auth.can('public_feedback', 'view')) {
    return firstAuthorizedRoute(auth)
  }
  return true
})

export default router
