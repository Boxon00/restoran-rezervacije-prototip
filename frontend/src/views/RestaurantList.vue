<template>
  <div class="restaurant-list-page">
    <aside class="filters">
      <h2>Filteri</h2>
      <label>Grad
        <input v-model="filters.city" type="text" placeholder="Niš" />
      </label>
      <label>Tip kuhinje
        <select v-model="filters.cuisine">
          <option value="">Sve</option>
          <option v-for="c in cuisines" :key="c" :value="c">{{ c }}</option>
        </select>
      </label>
      <label>Pretraga po nazivu
        <input v-model="filters.search" type="text" placeholder="npr. Ribica" />
      </label>
      <button class="btn-outline" @click="resetFilters">Resetuj filtere</button>
    </aside>

    <main class="results">
      <h1>Restorani</h1>
      <p v-if="loading">Učitavanje...</p>
      <p v-else-if="!restaurants.length">Nema restorana koji odgovaraju filterima.</p>
      <div v-else class="grid">
        <RouterLink v-for="r in restaurants" :key="r.id" :to="`/restaurants/${r.id}`" class="row-card">
          <div class="thumb" :style="{ backgroundImage: `url(${r.cover_image || placeholder})` }"></div>
          <div class="info">
            <h3>{{ r.name }}</h3>
            <p>{{ r.cuisine_type }} kuhinja</p>
            <p class="rating">★ {{ Number(r.avg_rating).toFixed(1) }} ({{ r.rating_count }} ocena)</p>
            <p class="address">{{ r.city }}, {{ r.address }}</p>
          </div>
          <span class="btn-primary small">Detalji i rezervacija</span>
        </RouterLink>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../api'

const route = useRoute()
const restaurants = ref([])
const loading = ref(true)
const placeholder = '/images/restaurant-placeholder.jpg'
const cuisines = ['srpska', 'italijanska', 'riblja', 'balkanska', 'mediteranska', 'internacionalna']
const filters = ref({
  city: route.query.city || '',
  cuisine: '',
  search: '',
})

async function load() {
  loading.value = true
  try {
    const params = {}
    if (filters.value.city) params.city = filters.value.city
    if (filters.value.cuisine) params.cuisine = filters.value.cuisine
    if (filters.value.search) params.search = filters.value.search
    const { data } = await api.get('/restaurants', { params })
    restaurants.value = data.data || data
  } finally {
    loading.value = false
  }
}

function resetFilters() {
  filters.value = { city: '', cuisine: '', search: '' }
}

watch(filters, load, { deep: true })
onMounted(load)
</script>
