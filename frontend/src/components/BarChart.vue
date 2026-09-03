<template>
  <canvas ref="canvasRef"></canvas>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { Chart, BarController, BarElement, CategoryScale, LinearScale, Tooltip } from 'chart.js'
Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip)

const props = defineProps({ labels: Array, data: Array, horizontal: Boolean })
const canvasRef = ref(null)
let chart = null

function render() {
  if (chart) chart.destroy()
  chart = new Chart(canvasRef.value, {
    type: 'bar',
    data: { labels: props.labels, datasets: [{ data: props.data, backgroundColor: '#e08a3e' }] },
    options: { indexAxis: props.horizontal ? 'y' : 'x', plugins: { legend: { display: false } } },
  })
}

onMounted(render)
watch(() => props.data, render)
</script>
