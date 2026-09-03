<template>
  <div class="home">
    <section class="hero">
      <div class="hero-copy">
        <h1>Rezerviši svoj sto <span class="accent">u najboljim restoranima grada</span></h1>
        <p>Pretraži restorane, izaberi termin i potvrdi rezervaciju za manje od minuta.</p>
        <RouterLink to="/restaurants" class="btn-primary">Pronađi restoran →</RouterLink>
      </div>
      <form class="quick-search" @submit.prevent="goSearch">
        <h2>Brza pretraga</h2>
        <label>Lokacija
          <input v-model="search.city" type="text" placeholder="Niš, centar" />
        </label>
        <label>Datum
          <input v-model="search.date" type="date" :min="today" />
        </label>
        <label>Broj gostiju
          <input v-model.number="search.guests" type="number" min="1" max="20" />
        </label>
        <button type="submit" class="btn-gold">Pretraži</button>
      </form>
    </section>

    <section class="recommended">
      <h2>Preporučeni restorani</h2>
      <p v-if="loading">Učitavanje...</p>
      <div v-else class="cards">
        <RouterLink
          v-for="r in restaurants" :key="r.id"
          :to="`/restaurants/${r.id}`" class="card"
        >
          <div class="card-image" :style="{ backgroundImage: `url(${r.cover_image || placeholder})` }"></div>
          <div class="card-body">
            <h3>{{ r.name }}</h3>
            <p>{{ r.cuisine_type }} · ★ {{ Number(r.avg_rating).toFixed(1) }}</p>
          </div>
        </RouterLink>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api'

const router = useRouter()
const restaurants = ref([])
const loading = ref(true)
const placeholder = '/images/restaurant-placeholder.jpg'
const today = new Date().toISOString().slice(0, 10)
const search = ref({ city: '', date: '', guests: 2 })

async function load() {
  try {
    const { data } = await api.get('/restaurants')
    restaurants.value = (data.data || data).slice(0, 6)
  } finally {
    loading.value = false
  }
}

function goSearch() {
  router.push({ path: '/restaurants', query: { ...search.value } })
}

onMounted(load)
</script>
