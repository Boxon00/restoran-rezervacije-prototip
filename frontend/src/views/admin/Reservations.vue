<template>
  <AdminLayout>
  <div class="admin-reservations">
    <div class="header-row">
      <h1>Rezervacije</h1>
      <select v-model="filterRestaurantId" @change="load">
        <option :value="null">Svi restorani</option>
        <option v-for="r in restaurants" :key="r.id" :value="r.id">{{ r.name }}</option>
      </select>
    </div>

    <p v-if="loading">Učitavanje...</p>
    <table v-else class="admin-table">
      <thead>
        <tr><th>Korisnik</th><th>Restoran</th><th>Termin</th><th>Sto / gosti</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
        <tr v-for="r in reservations" :key="r.id">
          <td>{{ r.user?.name || '—' }}</td>
          <td>{{ r.restaurant?.name }}</td>
          <td>{{ formatDate(r.reservation_time) }}</td>
          <td>{{ r.table?.label }} · {{ r.guest_count }} os.</td>
          <td>
            <select v-model="r.status" class="status-select" @change="updateStatus(r)">
              <option value="pending">Na čekanju</option>
              <option value="confirmed">Potvrđena</option>
              <option value="completed">Realizovana</option>
              <option value="cancelled">Otkazana</option>
            </select>
          </td>
          <td>
            <button class="btn-outline small danger" @click="cancel(r)" v-if="r.status !== 'cancelled'">Otkaži</button>
          </td>
        </tr>
        <tr v-if="!reservations.length"><td colspan="6">Nema rezervacija.</td></tr>
      </tbody>
    </table>
  </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '../../components/AdminLayout.vue'
import { ref, onMounted } from 'vue'
import api from '../../api'

const restaurants = ref([])
const reservations = ref([])
const filterRestaurantId = ref(null)
const loading = ref(true)

function formatDate(dt) {
  return new Date(dt).toLocaleString('sr-RS', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

async function loadRestaurants() {
  const { data } = await api.get('/restaurants')
  restaurants.value = data.data || data
}

async function load() {
  loading.value = true
  try {
    const params = {}
    if (filterRestaurantId.value) params.restaurant_id = filterRestaurantId.value
    const { data } = await api.get('/reservations', { params })
    reservations.value = data.data || data
  } finally {
    loading.value = false
  }
}

async function updateStatus(r) {
  await api.put(`/reservations/${r.id}`, { status: r.status })
}

async function cancel(r) {
  if (!confirm('Otkazati ovu rezervaciju?')) return
  await api.delete(`/reservations/${r.id}`)
  r.status = 'cancelled'
}

onMounted(async () => {
  await loadRestaurants()
  await load()
})
</script>
