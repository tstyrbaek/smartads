import { createApp } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import App from './App.vue'
import BrandPage from './pages/BrandPage.vue'
import AdPage from './pages/AdPage.vue'
import AdsPage from './pages/AdsPage.vue'
import CreateAdPage from './pages/CreateAdPage.vue'
import LoginPage from './pages/LoginPage.vue'
import SelectCompanyPage from './pages/SelectCompanyPage.vue'
import ProfilePage from './pages/ProfilePage.vue'
import { authToken } from './lib/api'
import './styles.css'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/login', component: LoginPage },
    { path: '/select-company', component: SelectCompanyPage },
    { path: '/profile', component: ProfilePage },
    { path: '/', redirect: '/ads' },
    { path: '/company', component: BrandPage },
    { path: '/ads', component: AdsPage },
    { path: '/ads/new', component: CreateAdPage },
    { path: '/ad', redirect: '/ads/new' },
    { path: '/ad/legacy', component: AdPage },
  ],
})

router.beforeEach((to) => {
  const isPublic = to.path === '/login'
  const hasToken = Boolean(authToken.value)

  if (!isPublic && !hasToken) {
    return { path: '/login' }
  }

  if (to.path === '/login' && hasToken) {
    return { path: '/' }
  }

  return true
})

createApp(App).use(router).mount('#app')
