import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../store/auth'

const routes = [
  { path: '/', name: 'home', component: () => import('../views/Home.vue') },
  { path: '/restaurants', name: 'restaurants', component: () => import('../views/RestaurantList.vue') },
  { path: '/restaurants/:id', name: 'restaurant-detail', component: () => import('../views/RestaurantDetail.vue') },
  { path: '/login', name: 'login', component: () => import('../views/Login.vue') },
  { path: '/register', name: 'register', component: () => import('../views/Register.vue') },
  { path: '/my-reservations', name: 'my-reservations', component: () => import('../views/MyReservations.vue'), meta: { requiresAuth: true } },
  { path: '/admin', name: 'admin-dashboard', component: () => import('../views/admin/Dashboard.vue'), meta: { requiresAdmin: true } },
  { path: '/admin/restaurants', name: 'admin-restaurants', component: () => import('../views/admin/Restaurants.vue'), meta: { requiresAdmin: true } },
  { path: '/admin/tables', name: 'admin-tables', component: () => import('../views/admin/Tables.vue'), meta: { requiresAdmin: true } },
  { path: '/admin/menus', name: 'admin-menus', component: () => import('../views/admin/Menus.vue'), meta: { requiresAdmin: true } },
  { path: '/admin/reservations', name: 'admin-reservations', component: () => import('../views/admin/Reservations.vue'), meta: { requiresAdmin: true } },
  { path: '/admin/users', name: 'admin-users', component: () => import('../views/admin/Users.vue'), meta: { requiresAdmin: true } },
  { path: '/admin/analytics', name: 'admin-analytics', component: () => import('../views/admin/Analytics.vue'), meta: { requiresAdmin: true } },
]

const router = createRouter({ history: createWebHistory(), routes })

router.beforeEach((to) => {
  const auth = useAuthStore()
  if (to.meta.requiresAuth && !auth.isLoggedIn) return { name: 'login' }
  if (to.meta.requiresAdmin && !auth.isAdmin) return { name: 'home' }
  return true
})

export default router
