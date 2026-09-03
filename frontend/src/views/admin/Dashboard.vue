<template>
  <AdminLayout>
  <div class="admin-dashboard">
    <h1>Pregled sistema</h1>

    <div class="kpi-row">
      <div class="kpi-card">
        <span>{{ summary.total_reservations ?? '-' }}</span>
        <label>Ukupno rezervacija</label>
      </div>
      <div class="kpi-card">
        <span>{{ summary.confirmed ?? '-' }}</span>
        <label>Potvrđene</label>
      </div>
      <div class="kpi-card">
        <span>{{ summary.cancellation_rate ?? '-' }}%</span>
        <label>Stopa otkazivanja</label>
      </div>
      <div class="kpi-card">
        <span>{{ summary.top_restaurants?.[0]?.restaurant?.name ?? '-' }}</span>
        <label>Najtraženiji restoran</label>
      </div>
    </div>

    <div class="panel">
      <h2>Rezervacije po satu</h2>
      <BarChart :labels="hourLabels" :data="hourData" />
    </div>

    <div class="quick-links">
      <RouterLink to="/admin/restaurants" class="btn-outline">Upravljaj restoranima</RouterLink>
      <RouterLink to="/admin/analytics" class="btn-outline">Analitički dashboard</RouterLink>
    </div>
  </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '../../components/AdminLayout.vue'
import { ref, computed, onMounted } from 'vue'
import api from '../../api'
import BarChart from '../../components/BarChart.vue'

const summary = ref({})

async function load() {
  const { data } = await api.get('/admin/analytics/summary')
  summary.value = data
}

const hourLabels = computed(() => (summary.value.reservations_by_hour || []).map(r => r.hour + 'h'))
const hourData = computed(() => (summary.value.reservations_by_hour || []).map(r => r.total))

onMounted(load)
</script>
