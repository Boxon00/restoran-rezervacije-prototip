<template>
  <AdminLayout>
  <div class="admin-users">
    <div class="header-row">
      <h1>Korisnici</h1>
      <input v-model="search" placeholder="Pretraga po imenu ili email-u..." @input="debouncedLoad" />
    </div>

    <p v-if="loading">Učitavanje...</p>
    <table v-else class="admin-table">
      <thead>
        <tr><th>Ime</th><th>Email</th><th>Telefon</th><th>Uloga</th><th>Rezervacija</th><th></th></tr>
      </thead>
      <tbody>
        <tr v-for="u in users" :key="u.id">
          <td>{{ u.name }}</td>
          <td>{{ u.email }}</td>
          <td>{{ u.phone || '—' }}</td>
          <td>
            <select v-model="u.role" class="status-select" @change="updateRole(u)">
              <option value="customer">Korisnik</option>
              <option value="admin">Administrator</option>
            </select>
          </td>
          <td>{{ u.reservations_count ?? '—' }}</td>
          <td>
            <button class="btn-outline small danger" @click="remove(u)">Obriši</button>
          </td>
        </tr>
        <tr v-if="!users.length"><td colspan="6">Nema pronađenih korisnika.</td></tr>
      </tbody>
    </table>
  </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '../../components/AdminLayout.vue'
import { ref, onMounted } from 'vue'
import api from '../../api'

const users = ref([])
const search = ref('')
const loading = ref(true)
let debounceTimer = null

async function load() {
  loading.value = true
  try {
    const params = {}
    if (search.value) params.search = search.value
    const { data } = await api.get('/users', { params })
    users.value = data.data || data
  } finally {
    loading.value = false
  }
}

function debouncedLoad() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(load, 350)
}

async function updateRole(u) {
  await api.put(`/users/${u.id}`, { role: u.role })
}

async function remove(u) {
  if (!confirm(`Obrisati korisnika "${u.name}"?`)) return
  try {
    await api.delete(`/users/${u.id}`)
    await load()
  } catch (e) {
    alert(e.response?.data?.message || 'Korisnik se ne može obrisati.')
  }
}

onMounted(load)
</script>
