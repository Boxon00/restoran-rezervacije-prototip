<template>
  <div class="auth-page">
    <form class="auth-card" @submit.prevent="submit">
      <h1>Prijava</h1>
      <label>Email
        <input v-model="form.email" type="email" required autocomplete="email" />
      </label>
      <label>Lozinka
        <input v-model="form.password" type="password" required autocomplete="current-password" />
      </label>
      <p v-if="error" class="error">{{ error }}</p>
      <button type="submit" class="btn-primary" :disabled="loading">
        {{ loading ? 'Prijavljivanje...' : 'Prijavi se' }}
      </button>
      <p class="switch">Nemate nalog? <RouterLink to="/register">Registrujte se</RouterLink></p>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../store/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const form = ref({ email: '', password: '' })
const error = ref('')
const loading = ref(false)

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(form.value.email, form.value.password)
    router.push(route.query.redirect || '/')
  } catch (e) {
    error.value = e.response?.data?.message || 'Prijava nije uspela. Proverite podatke.'
  } finally {
    loading.value = false
  }
}
</script>
