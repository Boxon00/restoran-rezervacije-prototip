<template>
  <AdminLayout>
  <div class="admin-tables">
    <div class="header-row">
      <h1>Stolovi</h1>
      <select v-model="selectedRestaurantId" @change="load">
        <option v-for="r in restaurants" :key="r.id" :value="r.id">{{ r.name }}</option>
      </select>
      <button class="btn-primary" :disabled="!selectedRestaurantId" @click="openCreate">+ Novi sto</button>
    </div>

    <p v-if="loading">Učitavanje...</p>
    <table v-else class="admin-table">
      <thead>
        <tr><th>Oznaka</th><th>Kapacitet</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
        <tr v-for="t in tables" :key="t.id">
          <td>{{ t.label }}</td>
          <td>{{ t.capacity }} mesta</td>
          <td><span class="status-chip" :class="statusClass(t.status)">{{ statusLabel(t.status) }}</span></td>
          <td class="actions">
            <button class="btn-outline small" @click="openEdit(t)">Izmeni</button>
            <button class="btn-outline small danger" @click="remove(t)">Obriši</button>
          </td>
        </tr>
        <tr v-if="!tables.length"><td colspan="4">Nema stolova za izabrani restoran.</td></tr>
      </tbody>
    </table>

    <form v-if="showForm" class="modal-form" @submit.prevent="save">
      <h2>{{ editing ? 'Izmena stola' : 'Novi sto' }}</h2>
      <label>Oznaka <input v-model="form.label" required placeholder="npr. Sto 5" /></label>
      <label>Kapacitet <input v-model.number="form.capacity" type="number" min="1" max="30" required /></label>
      <label>Status
        <select v-model="form.status">
          <option value="available">Dostupan</option>
          <option value="reserved">Rezervisan</option>
          <option value="maintenance">Na održavanju</option>
        </select>
      </label>
      <div class="modal-actions">
        <button type="button" class="btn-outline" @click="showForm = false">Otkaži</button>
        <button type="submit" class="btn-primary">Sačuvaj</button>
      </div>
    </form>
  </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '../../components/AdminLayout.vue'
import { ref, onMounted } from 'vue'
import api from '../../api'

const restaurants = ref([])
const selectedRestaurantId = ref(null)
const tables = ref([])
const loading = ref(true)
const showForm = ref(false)
const editing = ref(null)
const form = ref(blankForm())

function blankForm() {
  return { label: '', capacity: 4, status: 'available' }
}

const statusLabelMap = { available: 'Dostupan', reserved: 'Rezervisan', maintenance: 'Na održavanju' }
function statusLabel(s) { return statusLabelMap[s] || s }
function statusClass(s) { return s === 'available' ? 'confirmed' : s === 'reserved' ? 'pending' : 'cancelled' }

async function loadRestaurants() {
  const { data } = await api.get('/restaurants')
  restaurants.value = data.data || data
  if (restaurants.value.length) selectedRestaurantId.value = restaurants.value[0].id
}

async function load() {
  if (!selectedRestaurantId.value) return
  loading.value = true
  try {
    const { data } = await api.get(`/restaurants/${selectedRestaurantId.value}/tables`)
    tables.value = data
  } finally {
    loading.value = false
  }
}

function openCreate() {
  editing.value = null
  form.value = blankForm()
  showForm.value = true
}

function openEdit(t) {
  editing.value = t
  form.value = { label: t.label, capacity: t.capacity, status: t.status }
  showForm.value = true
}

async function save() {
  if (editing.value) {
    await api.put(`/tables/${editing.value.id}`, form.value)
  } else {
    await api.post(`/restaurants/${selectedRestaurantId.value}/tables`, form.value)
  }
  showForm.value = false
  await load()
}

async function remove(t) {
  if (!confirm(`Obrisati sto "${t.label}"?`)) return
  try {
    await api.delete(`/tables/${t.id}`)
    await load()
  } catch (e) {
    alert(e.response?.data?.message || 'Sto se ne može obrisati (ima aktivne rezervacije).')
  }
}

onMounted(async () => {
  await loadRestaurants()
  await load()
})
</script>
