<template>
  <div id="app-shell">
    <nav class="topnav">
      <RouterLink to="/" class="brand">
        <span class="logo">R</span> Rezerviši Restoran
      </RouterLink>
      <div class="nav-links">
        <RouterLink to="/">Početna</RouterLink>
        <RouterLink to="/restaurants">Restorani</RouterLink>
        <RouterLink v-if="auth.isLoggedIn" to="/my-reservations">Moje rezervacije</RouterLink>
        <RouterLink v-if="auth.isAdmin" to="/admin">Admin panel</RouterLink>
      </div>
      <div class="nav-auth">
        <template v-if="auth.isLoggedIn">
          <span class="user-chip">{{ auth.user?.name }}</span>
          <button class="btn-outline small" @click="logout">Odjava</button>
        </template>
        <template v-else>
          <RouterLink to="/login" class="btn-outline small">Prijava</RouterLink>
          <RouterLink to="/register" class="btn-primary small">Registracija</RouterLink>
        </template>
      </div>
    </nav>

    <main class="app-content">
      <RouterView />
    </main>

    <footer class="app-footer">
      <p>© 2027 Rezerviši Restoran — master rad, Elektronski fakultet Niš</p>
    </footer>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from './store/auth'

const auth = useAuthStore()
const router = useRouter()

async function logout() {
  await auth.logout()
  router.push('/')
}
</script>
