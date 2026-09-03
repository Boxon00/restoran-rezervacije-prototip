<template>
  <div class="restaurant-detail" v-if="restaurant">
    <div class="hero" :style="{ backgroundImage: `url(${restaurant.cover_image || placeholder})` }">
      <div class="hero-overlay">
        <h1>{{ restaurant.name }}</h1>
        <p>{{ restaurant.cuisine_type }} · {{ restaurant.address }}</p>
        <div class="rating">★ {{ Number(restaurant.avg_rating).toFixed(1) }} ({{ restaurant.rating_count }} ocena)</div>
      </div>
    </div>

    <section class="reservation-box">
      <h2>Napravi rezervaciju</h2>
      <form @submit.prevent="checkAvailability">
        <div class="field">
          <label>Datum</label>
          <input type="date" v-model="form.date" required :min="today" />
        </div>
        <div class="field">
          <label>Vreme</label>
          <select v-model="form.time" required>
            <option v-for="t in timeSlots" :key="t" :value="t">{{ t }}</option>
          </select>
        </div>
        <div class="field">
          <label>Broj gostiju</label>
          <input type="number" v-model.number="form.guests" min="1" max="20" required />
        </div>
        <button type="submit" class="btn-primary" :disabled="loading">
          {{ loading ? 'Proveravam...' : 'Proveri dostupnost' }}
        </button>
      </form>

      <div v-if="availableTables.length" class="tables-grid">
        <p>Dostupni stolovi za izabrani termin:</p>
        <button
          v-for="t in availableTables" :key="t.id"
          class="table-chip" :class="{ selected: selectedTable?.id === t.id }"
          @click="selectedTable = t"
        >
          {{ t.label }} · {{ t.capacity }} mesta
        </button>
        <button class="btn-confirm" :disabled="!selectedTable" @click="confirmReservation">
          Potvrdi rezervaciju
        </button>
      </div>
      <p v-else-if="searched" class="no-tables">Nema slobodnih stolova za izabrani termin. Pokušajte drugi termin.</p>
      <p v-if="errorMsg" class="error">{{ errorMsg }}</p>
      <p v-if="successMsg" class="success">{{ successMsg }}</p>
    </section>

    <section class="menu">
      <h2>Jelovnik</h2>
      <div v-for="menu in restaurant.menus" :key="menu.id" class="menu-block">
        <div v-for="cat in groupByCategory(menu.dishes)" :key="cat.name" class="category">
          <h3>{{ cat.name }}</h3>
          <ul>
            <li v-for="d in cat.items" :key="d.id">
              <span>{{ d.name }}</span><span>{{ d.price }} RSD</span>
            </li>
          </ul>
        </div>
      </div>
    </section>

    <section class="reviews">
      <h2>Ocene i recenzije</h2>

      <div v-if="auth.isLoggedIn" class="review-form">
        <h3>Ostavi ocenu</h3>
        <div class="field">
          <label>Ocena</label>
          <select v-model.number="reviewForm.stars">
            <option v-for="n in 5" :key="n" :value="n">{{ '★'.repeat(n) }} ({{ n }})</option>
          </select>
        </div>
        <div class="field">
          <label>Komentar</label>
          <textarea v-model="reviewForm.comment" rows="3" maxlength="1000" placeholder="Podeli svoj utisak..."></textarea>
        </div>
        <button class="btn-primary" :disabled="reviewSubmitting" @click="submitReview">
          {{ reviewSubmitting ? 'Slanje...' : 'Pošalji ocenu' }}
        </button>
        <p v-if="reviewError" class="error">{{ reviewError }}</p>
        <p v-if="reviewSuccess" class="success">{{ reviewSuccess }}</p>
      </div>
      <p v-else class="login-hint">
        <router-link to="/login">Prijavite se</router-link> da biste ostavili ocenu i komentar.
      </p>

      <div v-for="r in restaurant.ratings" :key="r.id" class="review">
        <strong>{{ r.user?.name }}</strong> — {{ '★'.repeat(r.stars) }}
        <p>{{ r.comment }}</p>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import api from '../api'
import { useAuthStore } from '../store/auth'

const route = useRoute()
const auth = useAuthStore()
const restaurant = ref(null)
const reviewForm = ref({ stars: 5, comment: '' })
const reviewSubmitting = ref(false)
const reviewError = ref('')
const reviewSuccess = ref('')
const placeholder = '/images/restaurant-placeholder.jpg'
const form = ref({ date: '', time: '19:00', guests: 2 })
const availableTables = ref([])
const selectedTable = ref(null)
const searched = ref(false)
const loading = ref(false)
const errorMsg = ref('')
const successMsg = ref('')
const today = new Date().toISOString().slice(0, 10)
const timeSlots = ['12:00', '12:30', '13:00', '18:00', '18:30', '19:00', '19:30', '20:00', '20:30', '21:00']

async function loadRestaurant() {
  const { data } = await api.get(`/restaurants/${route.params.id}`)
  restaurant.value = data
}

async function checkAvailability() {
  errorMsg.value = ''
  successMsg.value = ''
  availableTables.value = []
  selectedTable.value = null
  loading.value = true
  try {
    const { data } = await api.get(`/restaurants/${route.params.id}/availability`, {
      params: { date: form.value.date, time: form.value.time, guests: form.value.guests },
    })
    availableTables.value = data
  } finally {
    loading.value = false
    searched.value = true
  }
}

async function confirmReservation() {
  try {
    await api.post('/reservations', {
      restaurant_id: restaurant.value.id,
      table_id: selectedTable.value.id,
      reservation_time: `${form.value.date} ${form.value.time}:00`,
      guest_count: form.value.guests,
    })
    successMsg.value = 'Rezervacija je uspešno potvrđena!'
    errorMsg.value = ''
    availableTables.value = []
    selectedTable.value = null
    searched.value = false
  } catch (e) {
    errorMsg.value = e.response?.data?.errors?.table_id || 'Došlo je do greške. Pokušajte ponovo.'
  }
}

async function submitReview() {
  reviewError.value = ''
  reviewSuccess.value = ''
  reviewSubmitting.value = true
  try {
    await api.post('/ratings', {
      restaurant_id: restaurant.value.id,
      stars: reviewForm.value.stars,
      comment: reviewForm.value.comment || null,
    })
    reviewSuccess.value = 'Hvala na oceni!'
    reviewForm.value = { stars: 5, comment: '' }
    await loadRestaurant()
  } catch (e) {
    reviewError.value = e.response?.data?.message || 'Došlo je do greške. Pokušajte ponovo.'
  } finally {
    reviewSubmitting.value = false
  }
}

function groupByCategory(dishes) {
  const groups = {}
  for (const d of dishes || []) {
    groups[d.category] = groups[d.category] || []
    groups[d.category].push(d)
  }
  return Object.entries(groups).map(([name, items]) => ({ name, items }))
}

onMounted(loadRestaurant)
</script>