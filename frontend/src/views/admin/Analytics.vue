<template>
  <AdminLayout>
  <div class="analytics-dashboard">
    <h1>Analitički dashboard</h1>

    <div class="kpi-row">
      <div class="kpi-card"><span>{{ summary.total_reservations }}</span><label>Ukupno rezervacija</label></div>
      <div class="kpi-card"><span>{{ summary.confirmed }}</span><label>Potvrđene</label></div>
      <div class="kpi-card"><span>{{ summary.cancellation_rate }}%</span><label>Stopa otkazivanja</label></div>
      <div class="kpi-card"><span>{{ summary.top_restaurants?.[0]?.restaurant?.name || '-' }}</span><label>Najtraženiji restoran</label></div>
    </div>

    <div class="charts-row">
      <div class="chart-box">
        <h3>Rezervacije po satu</h3>
        <BarChart :labels="hourLabels" :data="hourData" />
      </div>
      <div class="chart-box">
        <h3>Top 5 restorana</h3>
        <BarChart :labels="topRestaurantLabels" :data="topRestaurantData" horizontal />
      </div>
    </div>

    <button class="btn-primary" @click="exportCsv">⬇ Preuzmi CSV izveštaj</button>
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

async function exportCsv() {
  const response = await api.get('/admin/analytics/export', { responseType: 'blob' })
  const url = window.URL.createObjectURL(new Blob([response.data]))
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', 'rezervacije_export.csv')
  document.body.appendChild(link)
  link.click()
}

const hourLabels = computed(() => (summary.value.reservations_by_hour || []).map(r => r.hour + 'h'))
const hourData = computed(() => (summary.value.reservations_by_hour || []).map(r => r.total))
const topRestaurantLabels = computed(() => (summary.value.top_restaurants || []).map(r => r.restaurant?.name))
const topRestaurantData = computed(() => (summary.value.top_restaurants || []).map(r => r.total))

onMounted(load)
</script>
