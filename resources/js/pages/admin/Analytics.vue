<template>
  <div class="analytics-dashboard p-6">
    <!-- Header -->
    <div class="mb-8">
      <div class="text-white rounded-lg p-6" style="background: linear-gradient(135deg, #272d63 0%, #1a1e47 100%);">
        <h2 class="text-2xl font-bold mb-2">Analytics Avancées</h2>
        <p class="text-white opacity-80">Analyses détaillées, prédictions et exports des données de la plateforme</p>
      </div>
    </div>

    <!-- Period Selector -->
    <div class="mb-6 bg-white rounded-lg shadow p-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <label class="text-sm font-medium text-gray-700">Période d'analyse:</label>
          <select v-model="selectedPeriod" @change="loadAllData"
                  class="border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <option value="7">7 derniers jours</option>
            <option value="30">30 derniers jours</option>
            <option value="90">90 derniers jours</option>
            <option value="365">1 an</option>
          </select>
        </div>

        <!-- Export Buttons -->
        <div class="flex space-x-3">
          <button @click="exportSales"
                  class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Export Ventes
          </button>
          <button @click="exportEvents"
                  class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Export Événements
          </button>
          <button @click="exportFinancial"
                  class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Rapport Financier
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center h-64">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <div v-else>
      <!-- KPIs Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Revenue KPI -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-gray-600">Revenus</h3>
            <div class="p-2 rounded-lg" style="background-color: rgba(79, 70, 229, 0.1);">
              <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
              </svg>
            </div>
          </div>
          <div>
            <p class="text-2xl font-bold text-gray-900">{{ formatAmount(kpis.revenue?.current || 0) }} XAF</p>
            <div class="flex items-center mt-2">
              <span v-if="kpis.revenue?.trend === 'up'" class="text-green-600 text-sm font-medium flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                </svg>
                +{{ kpis.revenue?.change_percent || 0 }}%
              </span>
              <span v-else class="text-red-600 text-sm font-medium flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                </svg>
                {{ kpis.revenue?.change_percent || 0 }}%
              </span>
              <span class="text-gray-500 text-xs ml-2">vs période précédente</span>
            </div>
          </div>
        </div>

        <!-- Orders KPI -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-gray-600">Commandes</h3>
            <div class="p-2 rounded-lg bg-blue-100">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
              </svg>
            </div>
          </div>
          <div>
            <p class="text-2xl font-bold text-gray-900">{{ kpis.orders?.current || 0 }}</p>
            <div class="flex items-center mt-2">
              <span :class="kpis.orders?.trend === 'up' ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path v-if="kpis.orders?.trend === 'up'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                  <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                </svg>
                {{ kpis.orders?.trend === 'up' ? '+' : '' }}{{ kpis.orders?.change_percent || 0 }}%
              </span>
              <span class="text-gray-500 text-xs ml-2">vs période précédente</span>
            </div>
          </div>
        </div>

        <!-- Tickets KPI -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-gray-600">Billets Vendus</h3>
            <div class="p-2 rounded-lg bg-green-100">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
              </svg>
            </div>
          </div>
          <div>
            <p class="text-2xl font-bold text-gray-900">{{ kpis.tickets?.current || 0 }}</p>
            <div class="flex items-center mt-2">
              <span :class="kpis.tickets?.trend === 'up' ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path v-if="kpis.tickets?.trend === 'up'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                  <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                </svg>
                {{ kpis.tickets?.trend === 'up' ? '+' : '' }}{{ kpis.tickets?.change_percent || 0 }}%
              </span>
              <span class="text-gray-500 text-xs ml-2">vs période précédente</span>
            </div>
          </div>
        </div>

        <!-- Users KPI -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-gray-600">Nouveaux Utilisateurs</h3>
            <div class="p-2 rounded-lg bg-purple-100">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
              </svg>
            </div>
          </div>
          <div>
            <p class="text-2xl font-bold text-gray-900">{{ kpis.users?.current || 0 }}</p>
            <div class="flex items-center mt-2">
              <span :class="kpis.users?.trend === 'up' ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path v-if="kpis.users?.trend === 'up'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                  <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                </svg>
                {{ kpis.users?.trend === 'up' ? '+' : '' }}{{ kpis.users?.change_percent || 0 }}%
              </span>
              <span class="text-gray-500 text-xs ml-2">vs période précédente</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Row 1: Revenue Chart & Sales by Category -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Revenue Chart -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-bold mb-4 text-gray-900">Évolution des Revenus</h3>
          <Line v-if="revenueChartData.labels.length > 0" :data="revenueChartData" :options="lineChartOptions" />
          <div v-else class="text-center text-gray-500 py-12">Aucune donnée de revenus disponible</div>
        </div>

        <!-- Sales by Category -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-bold mb-4 text-gray-900">Ventes par Catégorie</h3>
          <Doughnut v-if="salesByCategoryData.labels.length > 0" :data="salesByCategoryData" :options="doughnutChartOptions" />
          <div v-else class="text-center text-gray-500 py-12">Aucune donnée de catégorie disponible</div>
        </div>
      </div>

      <!-- Charts Row 2: Conversion Funnel & Predictions -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Conversion Funnel -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-bold mb-4 text-gray-900">Funnel de Conversion</h3>
          <Bar v-if="conversionFunnelData.labels.length > 0" :data="conversionFunnelData" :options="barChartOptions" />
          <div v-else class="text-center text-gray-500 py-12">Aucune donnée de conversion disponible</div>

          <!-- Conversion Rates -->
          <div v-if="conversionRates" class="mt-4 pt-4 border-t border-gray-200">
            <div class="grid grid-cols-3 gap-4 text-center">
              <div>
                <p class="text-xs text-gray-600">Vue → Commande</p>
                <p class="text-lg font-bold text-blue-600">{{ conversionRates.view_to_order }}%</p>
              </div>
              <div>
                <p class="text-xs text-gray-600">Commande → Paiement</p>
                <p class="text-lg font-bold text-green-600">{{ conversionRates.order_to_paid }}%</p>
              </div>
              <div>
                <p class="text-xs text-gray-600">Conversion Globale</p>
                <p class="text-lg font-bold text-purple-600">{{ conversionRates.overall }}%</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Revenue Predictions -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">Prédictions de Revenus (30 jours)</h3>
            <span v-if="predictions.trend"
                  :class="predictions.trend === 'ascending' ? 'text-green-600' : 'text-red-600'"
                  class="text-sm font-medium px-3 py-1 rounded-full"
                  :style="predictions.trend === 'ascending' ? 'background-color: rgba(34, 197, 94, 0.1)' : 'background-color: rgba(239, 68, 68, 0.1)'">
              {{ predictions.trend === 'ascending' ? '↑ Croissance' : '↓ Décroissance' }}
            </span>
          </div>
          <Line v-if="predictionsChartData.labels.length > 0" :data="predictionsChartData" :options="predictionsChartOptions" />
          <div v-else class="text-center text-gray-500 py-12">Pas assez de données pour les prédictions</div>

          <!-- Prediction Stats -->
          <div v-if="predictions.growth_rate !== undefined" class="mt-4 pt-4 border-t border-gray-200">
            <div class="grid grid-cols-2 gap-4 text-center">
              <div>
                <p class="text-xs text-gray-600">Taux de croissance</p>
                <p class="text-lg font-bold text-indigo-600">{{ formatAmount(predictions.growth_rate) }} XAF/jour</p>
              </div>
              <div>
                <p class="text-xs text-gray-600">Qualité du modèle (R²)</p>
                <p class="text-lg font-bold text-purple-600">{{ predictions.r_squared }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Top Events Table -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold mb-4 text-gray-900">Top 10 Événements</h3>
        <div v-if="topEvents.length === 0" class="text-center text-gray-500 py-8">
          Aucun événement disponible
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Événement</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Organisateur</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Billets</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Revenus</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="(event, index) in topEvents" :key="event.id" class="hover:bg-gray-50">
                <td class="px-4 py-4">
                  <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold text-white"
                       :style="`background-color: ${index < 3 ? '#fab511' : '#272d63'}`">
                    {{ index + 1 }}
                  </div>
                </td>
                <td class="px-4 py-4">
                  <div class="flex items-center">
                    <img v-if="event.image" :src="event.image" :alt="event.title" class="w-12 h-12 rounded-lg object-cover mr-3" />
                    <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center mr-3" v-else>
                      <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                      </svg>
                    </div>
                    <div>
                      <p class="font-medium text-gray-900">{{ event.title }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-4 text-sm text-gray-600">{{ event.organizer || 'N/A' }}</td>
                <td class="px-4 py-4 text-right">
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                    {{ event.tickets_sold }} tickets
                  </span>
                </td>
                <td class="px-4 py-4 text-right font-medium text-gray-900">{{ formatAmount(event.revenue) }} XAF</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue'
import { Line, Bar, Doughnut } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js'

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler
)

export default {
  name: 'Analytics',
  components: {
    Line,
    Bar,
    Doughnut
  },
  setup() {
    const loading = ref(false)
    const selectedPeriod = ref(30)

    const kpis = reactive({})
    const revenueChartData = reactive({ labels: [], datasets: [] })
    const salesByCategoryData = reactive({ labels: [], datasets: [] })
    const conversionFunnelData = reactive({ labels: [], datasets: [] })
    const conversionRates = ref(null)
    const predictionsChartData = reactive({ labels: [], datasets: [] })
    const predictions = reactive({})
    const topEvents = ref([])

    // Chart Options
    const lineChartOptions = {
      responsive: true,
      maintainAspectRatio: true,
      aspectRatio: 2,
      plugins: {
        legend: {
          display: true,
          position: 'top',
        },
        tooltip: {
          mode: 'index',
          intersect: false,
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function(value) {
              return new Intl.NumberFormat('fr-FR').format(value) + ' XAF'
            }
          }
        }
      }
    }

    const barChartOptions = {
      responsive: true,
      maintainAspectRatio: true,
      aspectRatio: 2,
      plugins: {
        legend: {
          display: false
        }
      },
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }

    const doughnutChartOptions = {
      responsive: true,
      maintainAspectRatio: true,
      aspectRatio: 2,
      plugins: {
        legend: {
          position: 'right',
        }
      }
    }

    const predictionsChartOptions = {
      responsive: true,
      maintainAspectRatio: true,
      aspectRatio: 2,
      plugins: {
        legend: {
          display: true,
          position: 'top',
        },
        tooltip: {
          mode: 'index',
          intersect: false,
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function(value) {
              return new Intl.NumberFormat('fr-FR').format(value) + ' XAF'
            }
          }
        }
      }
    }

    // API Methods
    const loadKPIs = async () => {
      try {
        const response = await fetch(`/api/v1/admin/analytics/kpis?days=${selectedPeriod.value}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })

        const data = await response.json()
        if (data.success) {
          Object.assign(kpis, data.data)
        }
      } catch (error) {
        console.error('Erreur chargement KPIs:', error)
      }
    }

    const loadRevenueChart = async () => {
      try {
        const response = await fetch(`/api/v1/admin/analytics/revenue-chart?days=${selectedPeriod.value}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })

        if (!response.ok) {
          console.error('Erreur API revenue-chart:', response.status)
          Object.assign(revenueChartData, { labels: [], datasets: [] })
          return
        }

        const data = await response.json()
        if (data.success && data.data) {
          Object.assign(revenueChartData, data.data)
        } else {
          Object.assign(revenueChartData, { labels: [], datasets: [] })
        }
      } catch (error) {
        console.error('Erreur chargement graphique revenus:', error)
        Object.assign(revenueChartData, { labels: [], datasets: [] })
      }
    }

    const loadSalesByCategory = async () => {
      try {
        const response = await fetch(`/api/v1/admin/analytics/sales-by-category?days=${selectedPeriod.value}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })

        if (!response.ok) {
          console.error('Erreur API sales-by-category:', response.status)
          // Réinitialiser avec des valeurs par défaut
          Object.assign(salesByCategoryData, { labels: [], datasets: [] })
          return
        }

        const data = await response.json()
        if (data.success && data.data) {
          Object.assign(salesByCategoryData, data.data)
        } else {
          // Réinitialiser avec des valeurs par défaut
          Object.assign(salesByCategoryData, { labels: [], datasets: [] })
        }
      } catch (error) {
        console.error('Erreur chargement ventes par catégorie:', error)
        // Réinitialiser avec des valeurs par défaut en cas d'erreur
        Object.assign(salesByCategoryData, { labels: [], datasets: [] })
      }
    }

    const loadConversionFunnel = async () => {
      try {
        const response = await fetch(`/api/v1/admin/analytics/conversion-funnel?days=${selectedPeriod.value}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })

        if (!response.ok) {
          console.error('Erreur API conversion-funnel:', response.status)
          Object.assign(conversionFunnelData, { labels: [], datasets: [] })
          conversionRates.value = null
          return
        }

        const data = await response.json()
        if (data.success && data.data) {
          Object.assign(conversionFunnelData, data.data)
          conversionRates.value = data.data.conversion_rates || null
        } else {
          Object.assign(conversionFunnelData, { labels: [], datasets: [] })
          conversionRates.value = null
        }
      } catch (error) {
        console.error('Erreur chargement funnel:', error)
        Object.assign(conversionFunnelData, { labels: [], datasets: [] })
        conversionRates.value = null
      }
    }

    const loadPredictions = async () => {
      try {
        const response = await fetch(`/api/v1/admin/analytics/predictions?days_history=${selectedPeriod.value}&days_future=30`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })

        const data = await response.json()
        if (data.success && data.data.predictions) {
          Object.assign(predictions, data.data)

          // Prepare chart data with confidence intervals
          predictionsChartData.labels = data.data.labels
          predictionsChartData.datasets = [
            {
              label: 'Prédiction',
              data: data.data.predictions,
              borderColor: 'rgb(79, 70, 229)',
              backgroundColor: 'rgba(79, 70, 229, 0.1)',
              tension: 0.4,
              fill: false
            },
            {
              label: 'Intervalle de confiance supérieur',
              data: data.data.confidence_upper,
              borderColor: 'rgba(79, 70, 229, 0.3)',
              backgroundColor: 'rgba(79, 70, 229, 0.05)',
              borderDash: [5, 5],
              tension: 0.4,
              fill: '+1'
            },
            {
              label: 'Intervalle de confiance inférieur',
              data: data.data.confidence_lower,
              borderColor: 'rgba(79, 70, 229, 0.3)',
              backgroundColor: 'rgba(79, 70, 229, 0.05)',
              borderDash: [5, 5],
              tension: 0.4,
              fill: false
            }
          ]
        }
      } catch (error) {
        console.error('Erreur chargement prédictions:', error)
      }
    }

    const loadTopEvents = async () => {
      try {
        const response = await fetch('/api/v1/admin/analytics/top-events?limit=10', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })

        const data = await response.json()
        if (data.success) {
          topEvents.value = data.data
        }
      } catch (error) {
        console.error('Erreur chargement top events:', error)
      }
    }

    const loadAllData = async () => {
      loading.value = true
      try {
        await Promise.all([
          loadKPIs(),
          loadRevenueChart(),
          loadSalesByCategory(),
          loadConversionFunnel(),
          loadPredictions(),
          loadTopEvents()
        ])
      } finally {
        loading.value = false
      }
    }

    // Export Methods
    const exportSales = () => {
      const startDate = new Date()
      startDate.setDate(startDate.getDate() - selectedPeriod.value)
      const endDate = new Date()

      const url = `/api/v1/admin/analytics/export/sales?start_date=${startDate.toISOString().split('T')[0]}&end_date=${endDate.toISOString().split('T')[0]}`
      window.open(url, '_blank')
    }

    const exportEvents = () => {
      const startDate = new Date()
      startDate.setDate(startDate.getDate() - selectedPeriod.value)
      const endDate = new Date()

      const url = `/api/v1/admin/analytics/export/events?start_date=${startDate.toISOString().split('T')[0]}&end_date=${endDate.toISOString().split('T')[0]}`
      window.open(url, '_blank')
    }

    const exportFinancial = () => {
      const startDate = new Date()
      startDate.setDate(startDate.getDate() - selectedPeriod.value)
      const endDate = new Date()

      const url = `/api/v1/admin/analytics/export/financial?start_date=${startDate.toISOString().split('T')[0]}&end_date=${endDate.toISOString().split('T')[0]}`
      window.open(url, '_blank')
    }

    // Utilities
    const formatAmount = (amount) => {
      return new Intl.NumberFormat('fr-FR').format(amount)
    }

    // Lifecycle
    onMounted(() => {
      loadAllData()
    })

    return {
      loading,
      selectedPeriod,
      kpis,
      revenueChartData,
      salesByCategoryData,
      conversionFunnelData,
      conversionRates,
      predictionsChartData,
      predictions,
      topEvents,
      lineChartOptions,
      barChartOptions,
      doughnutChartOptions,
      predictionsChartOptions,
      loadAllData,
      exportSales,
      exportEvents,
      exportFinancial,
      formatAmount
    }
  }
}
</script>

<style scoped>
.analytics-dashboard {
  font-family: 'Inter', sans-serif;
}
</style>
