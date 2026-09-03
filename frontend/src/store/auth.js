import { defineStore } from 'pinia'
import api from '../api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user') || 'null'),
    token: localStorage.getItem('token') || null,
  }),
  getters: {
    isLoggedIn: (state) => !!state.token,
    isAdmin: (state) => state.user?.role === 'admin',
  },
  actions: {
    async login(email, password) {
      const { data } = await api.post('/login', { email, password })
      this.setSession(data)
    },
    async register(payload) {
      const { data } = await api.post('/register', payload)
      this.setSession(data)
    },
    setSession(data) {
      this.user = data.user
      this.token = data.token
      localStorage.setItem('user', JSON.stringify(data.user))
      localStorage.setItem('token', data.token)
    },
    async logout() {
      await api.post('/logout')
      this.user = null
      this.token = null
      localStorage.removeItem('user')
      localStorage.removeItem('token')
    },
  },
})
