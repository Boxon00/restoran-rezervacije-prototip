<template>
  <div class="auth-page">
    <form class="auth-card" @submit.prevent="submit">
      <h1>Registracija</h1>
      <label>Ime i prezime
        <input v-model="form.name" type="text" required />
      </label>
      <label>Email
        <input v-model="form.email" type="email" required autocomplete="email" />
      </label>
      <label>Telefon
        <input v-model="form.phone" type="tel" />
      </label>
      <label>Lozinka
        <input v-model="form.password" type="password" required minlength="8" autocomplete="new-password" />
      </label>
      <label>Potvrda lozinke
        <input v-model="form.password_confirmation" type="password" required minlength="8" />
      </label>
      <p v-if="errors.length" class="error">
        <span v-for="e in errors" :key="e">{{ e }}<br /></span>
      </p>
      <button type="submit" class="btn-primary" :disabled="loading">
        {{ loading ? 'Kreiranje naloga...' : 'Registruj se' }}
      </button>
      <p class="switch">Već imate nalog? <RouterLink to="/login">Prijavite se</RouterLink></p>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../store/auth'

const auth = useAuthStore()
const router = useRouter()
const form = ref({ name: '', email: '', phone: '', password: '', password_confirmation: '' })
const errors = ref([])
const loading = ref(false)

async function submit() {
  errors.value = []
  loading.value = true
  try {
    await auth.register(form.value)
    router.push('/')
  } catch (e) {
    const respErrors = e.response?.data?.errors
    errors.value = respErrors ? Object.values(respErrors).flat() : ['Registracija nije uspela. Pokušajte ponovo.']
  } finally {
    loading.value = false
  }
}
</script>
