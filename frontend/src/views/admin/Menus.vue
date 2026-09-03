<template>
  <AdminLayout>
  <div class="admin-menus">
    <div class="header-row">
      <h1>Jelovnici</h1>
      <select v-model="selectedRestaurantId" @change="load">
        <option v-for="r in restaurants" :key="r.id" :value="r.id">{{ r.name }}</option>
      </select>
      <button class="btn-primary" :disabled="!currentMenu" @click="openCreate">+ Novo jelo</button>
    </div>

    <p v-if="loading">Učitavanje...</p>
    <p v-else-if="!currentMenu">Ovaj restoran još uvek nema jelovnik.</p>
    <table v-else class="admin-table">
      <thead>
        <tr><th>Naziv</th><th>Kategorija</th><th>Cena</th><th>Broj porudžbina</th><th></th></tr>
      </thead>
      <tbody>
        <tr v-for="d in currentMenu.dishes" :key="d.id">
          <td>{{ d.name }}</td>
          <td>{{ d.category }}</td>
          <td>{{ d.price }} RSD</td>
          <td>{{ d.order_count }}</td>
          <td class="actions">
            <button class="btn-outline small" @click="openEdit(d)">Izmeni</button>
            <button class="btn-outline small danger" @click="remove(d)">Obriši</button>
          </td>
        </tr>
        <tr v-if="!currentMenu.dishes.length"><td colspan="5">Jelovnik je prazan.</td></tr>
      </tbody>
    </table>

    <form v-if="showForm" class="modal-form" @submit.prevent="save">
      <h2>{{ editing ? 'Izmena jela' : 'Novo jelo' }}</h2>
      <label>Naziv <input v-model="form.name" required /></label>
      <label>Kategorija
        <select v-model="form.category">
          <option>Predjelo</option>
          <option>Glavno jelo</option>
          <option>Dezert</option>
          <option>Piće</option>
        </select>
      </label>
      <label>Cena (RSD) <input v-model.number="form.price" type="number" min="0" step="10" required /></label>
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
const currentMenu = ref(null)
const loading = ref(true)
const showForm = ref(false)
const editing = ref(null)
const form = ref(blankForm())

function blankForm() {
  return { name: '', category: 'Glavno jelo', price: 0 }
}

async function loadRestaurants() {
  const { data } = await api.get('/restaurants')
  restaurants.value = data.data || data
  if (restaurants.value.length) selectedRestaurantId.value = restaurants.value[0].id
}

async function load() {
  if (!selectedRestaurantId.value) return
  loading.value = true
  try {
    const { data } = await api.get(`/restaurants/${selectedRestaurantId.value}`)
    currentMenu.value = (data.menus && data.menus[0]) || null
  } finally {
    loading.value = false
  }
}

function openCreate() {
  editing.value = null
  form.value = blankForm()
  showForm.value = true
}

function openEdit(d) {
  editing.value = d
  form.value = { name: d.name, category: d.category, price: d.price }
  showForm.value = true
}

async function save() {
  if (editing.value) {
    await api.put(`/dishes/${editing.value.id}`, form.value)
  } else {
    await api.post(`/menus/${currentMenu.value.id}/dishes`, form.value)
  }
  showForm.value = false
  await load()
}

async function remove(d) {
  if (!confirm(`Obrisati jelo "${d.name}"?`)) return
  await api.delete(`/dishes/${d.id}`)
  await load()
}

onMounted(async () => {
  await loadRestaurants()
  await load()
})
</script>
