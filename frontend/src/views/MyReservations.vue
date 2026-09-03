<template>
  <div class="my-reservations">
    <h1>Moje rezervacije</h1>
    <p v-if="loading">Učitavanje...</p>
    <p v-else-if="!reservations.length">Nemate rezervacija. <RouterLink to="/restaurants">Pronađite restoran</RouterLink> i napravite prvu rezervaciju.</p>

    <table v-else class="reservations-table">
      <thead>
        <tr>
          <th>Restoran</th><th>Termin</th><th>Sto / gosti</th><th>Status</th><th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="r in reservations" :key="r.id">
          <td>{{ r.restaurant?.name }}</td>
          <td>{{ formatDate(r.reservation_time) }}</td>
          <td>{{ r.table?.label }} · {{ r.guest_count }} os.</td>
          <td><span class="status-chip" :class="r.status">{{ statusLabel(r.status) }}</span></td>
          <td>
            <button
              v-if="['pending','confirmed'].includes(r.status)"
              class="btn-outline small" @click="cancel(r)"
            >Otkaži</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../api'

const reservations = ref([])
const loading = ref(true)

const statusMap = {
  pending: 'Na čekanju', confirmed: 'Potvrđena', cancelled: 'Otkazana', completed: 'Realizovana',
}
function statusLabel(s) { return statusMap[s] || s }
function formatDate(dt) {
  return new Date(dt).toLocaleString('sr-RS', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/reservations')
    reservations.value = data.data || data
  } finally {
    loading.value = false
  }
}

async function cancel(reservation) {
  if (!confirm('Da li ste sigurni da želite da otkažete rezervaciju?')) return
  await api.delete(`/reservations/${reservation.id}`)
  reservation.status = 'cancelled'
}

onMounted(load)
</script>
