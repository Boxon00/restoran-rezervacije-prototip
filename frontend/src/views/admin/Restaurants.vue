<template>
  <AdminLayout>
  <div class="admin-restaurants">
    <div class="header-row">
      <h1>Restorani</h1>
      <button class="btn-primary" @click="openCreate">+ Novi restoran</button>
    </div>

    <table class="admin-table">
      <thead>
        <tr><th>Naziv</th><th>Grad</th><th>Kuhinja</th><th>Ocena</th><th></th></tr>
      </thead>
      <tbody>
        <tr v-for="r in restaurants" :key="r.id">
          <td>{{ r.name }}</td>
          <td>{{ r.city }}</td>
          <td>{{ r.cuisine_type }}</td>
          <td>★ {{ Number(r.avg_rating).toFixed(1) }}</td>
          <td class="actions">
            <button class="btn-outline small" @click="openEdit(r)">Izmeni</button>
            <button class="btn-outline small danger" @click="remove(r)">Obriši</button>
          </td>
        </tr>
      </tbody>
    </table>

    <form v-if="showForm" class="modal-form" @submit.prevent="save">
      <h2>{{ editing ? 'Izmena restorana' : 'Novi restoran' }}</h2>
      <label>Naziv <input v-model="form.name" required /></label>
      <label>Adresa <input v-model="form.address" required /></label>
      <label>Grad <input v-model="form.city" required /></label>
      <label>Tip kuhinje <input v-model="form.cuisine_type" /></label>
      <label>Telefon <input v-model="form.phone" /></label>
      <label>Radno vreme od <input v-model="form.opening_time" type="time" required /></label>
      <label>Radno vreme do <input v-model="form.closing_time" type="time" required /></label>
      <label>Opis <textarea v-model="form.description"></textarea></label>
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
const showForm = ref(false)
const editing = ref(null)
const form = ref(blankForm())

function blankForm() {
  return { name: '', address: '', city: '', cuisine_type: '', phone: '', opening_time: '10:00', closing_time: '23:00', description: '' }
}

async function load() {
  const { data } = await api.get('/restaurants')
  restaurants.value = data.data || data
}

function openCreate() {
  editing.value = null
  form.value = blankForm()
  showForm.value = true
}

function openEdit(r) {
  editing.value = r
  form.value = { ...r }
  showForm.value = true
}

async function save() {
  if (editing.value) {
    await api.put(`/restaurants/${editing.value.id}`, form.value)
  } else {
    await api.post('/restaurants', form.value)
  }
  showForm.value = false
  await load()
}

async function remove(r) {
  if (!confirm(`Obrisati restoran "${r.name}"?`)) return
  await api.delete(`/restaurants/${r.id}`)
  await load()
}

onMounted(load)
</script>
