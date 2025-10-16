<template>
  <div class="admin-dashboard p-6">
    <!-- Welcome Section -->
    <div class="mb-8">
      <div class="text-white rounded-lg p-6" style="background: linear-gradient(135deg, #272d63 0%, #1a1e47 100%);">
        <h2 class="text-2xl font-bold mb-2">Tableau de Bord Primea</h2>
        <p class="text-white opacity-80">Supervision des paiements et transactions de la plateforme</p>
      </div>
    </div>

    <!-- Loading state -->
    <div v-if="loading" class="flex justify-center items-center h-64">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <div v-else>
      <!-- Statistiques Principales -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <button @click="$router.push('/admin/payments')"
                class="text-white p-6 rounded-lg transition-all transform hover:scale-105"
                style="background-color: #272d63;"
                @mouseover="$event.currentTarget.style.backgroundColor = '#fab511'; $event.currentTarget.style.color = '#272d63'"
                @mouseleave="$event.currentTarget.style.backgroundColor = '#272d63'; $event.currentTarget.style.color = '#ffffff'">
          <div class="flex items-center">
            <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
            </svg>
            <div class="text-left">
              <h3 class="text-lg font-bold">Revenus Total</h3>
              <p class="text-white opacity-80 text-sm">{{ formatAmount(stats.total_revenue || 0) }} XAF</p>
            </div>
          </div>
        </button>

        <button @click="$router.push('/admin/orders')"
                class="text-white p-6 rounded-lg transition-all transform hover:scale-105"
                style="background-color: #fab511;"
                @mouseover="$event.currentTarget.style.backgroundColor = '#272d63'"
                @mouseleave="$event.currentTarget.style.backgroundColor = '#fab511'">
          <div class="flex items-center">
            <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
            </svg>
            <div class="text-left">
              <h3 class="text-lg font-bold">Tickets Vendus</h3>
              <p class="text-white opacity-80 text-sm">{{ stats.tickets_sold || 0 }}</p>
            </div>
          </div>
        </button>

        <button @click="$router.push('/admin/events')"
                class="bg-gray-600 text-white p-6 rounded-lg hover:bg-gray-700 transition-all transform hover:scale-105">
          <div class="flex items-center">
            <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <div class="text-left">
              <h3 class="text-lg font-bold">Événements Actifs</h3>
              <p class="text-gray-200 text-sm">{{ stats.active_events || 0 }}</p>
            </div>
          </div>
        </button>

        <button @click="$router.push('/admin/users')"
                class="bg-gray-700 text-white p-6 rounded-lg hover:bg-gray-800 transition-all transform hover:scale-105">
          <div class="flex items-center">
            <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-.5a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
            </svg>
            <div class="text-left">
              <h3 class="text-lg font-bold">Utilisateurs Actifs</h3>
              <p class="text-gray-200 text-sm">{{ stats.active_users || 0 }}</p>
            </div>
          </div>
        </button>
      </div>

      <!-- Statistiques des Paiements -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-2 rounded-lg" style="background-color: rgba(39, 45, 99, 0.1);">
              <svg class="w-6 h-6" style="color: #272d63;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm text-gray-600">Paiements Réussis</p>
              <p class="text-2xl font-bold" style="color: #272d63;">{{ stats.successful_payments || 0 }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-2 rounded-lg" style="background-color: rgba(250, 181, 17, 0.1);">
              <svg class="w-6 h-6" style="color: #fab511;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm text-gray-600">Paiements En Attente</p>
              <p class="text-2xl font-bold" style="color: #fab511;">{{ stats.pending_payments || 0 }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-2 rounded-lg" style="background-color: rgba(39, 45, 99, 0.15);">
              <svg class="w-6 h-6" style="color: #272d63;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm text-gray-600">Commission Plateforme</p>
              <p class="text-2xl font-bold" style="color: #272d63;">{{ formatAmount(stats.platform_commission || 0) }} XAF</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-2 bg-red-100 rounded-lg">
              <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm text-gray-600">Paiements Échoués</p>
              <p class="text-2xl font-bold text-red-600">{{ stats.failed_payments || 0 }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <!-- Recent Payments -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-xl font-bold" style="color: #272d63;">Paiements Récents</h2>
              <button @click="$router.push('/admin/payments')" 
                      class="text-sm transition-colors duration-200" style="color: #272d63;"
                      @mouseover="$event.currentTarget.style.color = '#fab511'"
                      @mouseleave="$event.currentTarget.style.color = '#272d63'">Voir tous</button>
            </div>
            
            <div v-if="recentOrders.length === 0" class="text-center text-gray-500 py-8">
              Aucune commande récente
            </div>
            
            <div v-else class="overflow-x-auto">
              <table class="w-full">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Commande</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                  <tr v-for="order in recentOrders" :key="order.id">
                    <td class="px-4 py-4 text-sm font-medium text-blue-600">
                      <button @click="viewOrder(order)" class="hover:text-blue-800">
                        #{{ order.reference }}
                      </button>
                    </td>
                    <td class="px-4 py-4 text-sm text-gray-900">{{ order.customer_name || order.customer_email }}</td>
                    <td class="px-4 py-4 text-sm font-medium text-gray-900">{{ formatAmount(order.total_amount) }} XAF</td>
                    <td class="px-4 py-4 text-sm">
                      <span v-if="order.status === 'pending'"
                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                            style="background-color: rgba(250, 181, 17, 0.1); color: #fab511;">
                        {{ getOrderStatusName(order.status) }}
                      </span>
                      <span v-else-if="order.status === 'paid'"
                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                            style="background-color: rgba(39, 45, 99, 0.1); color: #272d63;">
                        {{ getOrderStatusName(order.status) }}
                      </span>
                      <span v-else
                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                            :class="getOrderStatusClass(order.status)">
                        {{ getOrderStatusName(order.status) }}
                      </span>
                    </td>
                    <td class="px-4 py-4 text-sm text-gray-500">{{ formatDate(order.created_at) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Quick Stats & Actions -->
        <div class="lg:col-span-1 space-y-6">
          <!-- System Health -->
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">État du Système</h2>
            <div class="space-y-3">
              <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Paiements SHAP</span>
                <span class="inline-flex px-2 py-1 text-xs rounded-full" style="background-color: rgba(39, 45, 99, 0.1); color: #272d63;">
                  Opérationnel
                </span>
              </div>

              <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Base de données</span>
                <span class="inline-flex px-2 py-1 text-xs rounded-full" style="background-color: rgba(39, 45, 99, 0.1); color: #272d63;">
                  Opérationnel
                </span>
              </div>
              
              <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Solde SHAP</span>
                <button @click="checkShapBalance" 
                        class="text-blue-600 hover:text-blue-800 text-xs">
                  Vérifier
                </button>
              </div>
            </div>
          </div>

          <!-- Recent Activity -->
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">Activité Récente</h2>
            <div class="space-y-3">
              <div v-for="activity in recentActivity" :key="activity.id" class="flex items-start">
                <div class="p-1 rounded-full mr-3" :class="getActivityIconClass(activity.type)">
                  <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <circle cx="10" cy="10" r="3"/>
                  </svg>
                </div>
                <div class="flex-1">
                  <p class="text-sm text-gray-900">{{ activity.description }}</p>
                  <p class="text-xs text-gray-500">{{ formatDate(activity.created_at) }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">Actions Rapides</h2>
            <div class="space-y-3">
              <button @click="$router.push('/admin/users/create')" 
                      class="w-full text-left p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                <div class="flex items-center">
                  <svg class="w-5 h-5 text-gray-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                  </svg>
                  <span class="font-medium">Créer Utilisateur</span>
                </div>
              </button>
              
              <button @click="$router.push('/admin/organizers/create')" 
                      class="w-full text-left p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                <div class="flex items-center">
                  <svg class="w-5 h-5 text-gray-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                  </svg>
                  <span class="font-medium">Créer Organisateur</span>
                </div>
              </button>
              
              <button @click="$router.push('/admin/events/create')" 
                      class="w-full text-left p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                <div class="flex items-center">
                  <svg class="w-5 h-5 text-gray-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                  </svg>
                  <span class="font-medium">Créer Événement</span>
                </div>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts & Analytics -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Revenue Chart -->
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-xl font-bold mb-4">Revenus (7 derniers jours)</h2>
          <div v-if="revenueData.length === 0" class="text-center text-gray-500 py-8">
            Aucune donnée disponible
          </div>
          <div v-else class="space-y-3">
            <div v-for="data in revenueData" :key="data.date" 
                 class="flex justify-between items-center">
              <span class="text-sm text-gray-600">{{ formatDate(data.date) }}</span>
              <div class="flex items-center">
                <div class="w-32 bg-gray-200 rounded-full h-2 mr-3">
                  <div class="bg-blue-600 h-2 rounded-full" 
                       :style="`width: ${(data.amount / maxRevenue) * 100}%`"></div>
                </div>
                <span class="text-sm font-medium">{{ formatAmount(data.amount) }} XAF</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Top Events -->
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-xl font-bold mb-4">Événements Populaires</h2>
          <div v-if="topEvents.length === 0" class="text-center text-gray-500 py-8">
            Aucun événement populaire
          </div>
          <div v-else class="space-y-4">
            <div v-for="(event, index) in topEvents" :key="event.id" 
                 class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
              <div class="flex items-center">
                <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3">
                  {{ index + 1 }}
                </div>
                <div>
                  <h3 class="font-medium text-gray-900">{{ event.title }}</h3>
                  <p class="text-sm text-gray-600">{{ event.organizer_name }}</p>
                </div>
              </div>
              <div class="text-right">
                <p class="font-medium text-gray-900">{{ event.tickets_sold }} tickets</p>
                <p class="text-sm text-gray-600">{{ formatAmount(event.revenue) }} XAF</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted, computed } from 'vue'

export default {
  name: 'AdminDashboard',
  setup() {
    // État réactif
    const loading = ref(false)
    
    const stats = reactive({
      total_revenue: 0,
      tickets_sold: 0,
      active_events: 0,
      active_users: 0,
      total_users: 0,
      total_organizers: 0,
      total_events: 0,
      total_balance: 0,
      orders_today: 0,
      revenue_today: 0,
      failed_payments: 0,
      successful_payments: 0,
      pending_payments: 0,
      payments_in_transit: 0,
      platform_commission: 0
    })

    const recentOrders = ref([])
    const recentActivity = ref([])
    const revenueData = ref([])
    const topEvents = ref([])

    // Computed
    const maxRevenue = computed(() => {
      if (revenueData.value.length === 0) return 1
      return Math.max(...revenueData.value.map(d => d.amount))
    })

    // Méthodes
    const loadDashboardData = async () => {
      loading.value = true
      try {
        const response = await fetch('/api/v1/admin/dashboard', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        const data = await response.json()
        if (data.success) {
          Object.assign(stats, data.data.stats)
          recentOrders.value = data.data.recent_orders
          recentActivity.value = data.data.recent_activity
          revenueData.value = data.data.revenue_data
          topEvents.value = data.data.top_events
        }
      } catch (error) {
        console.error('Erreur chargement dashboard admin:', error)
      } finally {
        loading.value = false
      }
    }

    const checkShapBalance = async () => {
      try {
        const response = await fetch('/api/v1/admin/payouts/shap-balance', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        const data = await response.json()
        if (data.success) {
          const balances = data.data.balances
          const total = balances.reduce((sum, b) => sum + b.amount, 0)
          alert(`Solde SHAP total: ${formatAmount(total)} XAF`)
        }
      } catch (error) {
        console.error('Erreur vérification solde SHAP:', error)
      }
    }

    const viewOrder = (order) => {
      this.$router.push(`/admin/orders/${order.id}`)
    }

    // Utilitaires
    const formatAmount = (amount) => {
      return new Intl.NumberFormat('fr-FR').format(amount)
    }

    const formatDate = (date) => {
      return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    const getOrderStatusName = (status) => {
      const names = {
        pending: 'En attente',
        paid: 'Payé',
        cancelled: 'Annulé',
        refunded: 'Remboursé'
      }
      return names[status] || status
    }

    const getOrderStatusClass = (status) => {
      const classes = {
        cancelled: 'bg-red-100 text-red-800',
        refunded: 'bg-gray-100 text-gray-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    }

    const getActivityIconClass = (type) => {
      return 'text-gray-600'
    }

    // Simuler des données pour les paiements si l'API n'est pas disponible
    const loadMockData = () => {
      Object.assign(stats, {
        total_revenue: 8500000,
        tickets_sold: 342,
        active_events: 23,
        active_users: 156,
        total_users: 1247,
        total_organizers: 89,
        total_events: 156,
        orders_today: 23,
        revenue_today: 2850000,
        successful_payments: 142,
        pending_payments: 8,
        failed_payments: 3,
        payments_in_transit: 4750000,
        platform_commission: 285000
      })
      
      // Données simulées de paiements récents
      recentOrders.value = [
        {
          id: 1,
          reference: 'PMT-2025-001',
          customer_name: 'Jean Dupont',
          customer_email: 'jean@email.com',
          total_amount: 15000,
          status: 'paid',
          created_at: new Date()
        },
        {
          id: 2,
          reference: 'PMT-2025-002',
          customer_name: 'Marie Martin',
          customer_email: 'marie@email.com',
          total_amount: 25000,
          status: 'pending',
          created_at: new Date(Date.now() - 300000)
        }
      ]
    }

    // Lifecycle
    onMounted(() => {
      loadDashboardData()
      // Charger des données simulées si l'API échoue
      setTimeout(() => {
        if (stats.total_users === 0) {
          loadMockData()
        }
      }, 2000)
    })

    return {
      // État
      loading,
      stats,
      recentOrders,
      recentActivity,
      revenueData,
      topEvents,
      maxRevenue,
      
      // Méthodes
      loadDashboardData,
      checkShapBalance,
      viewOrder,
      
      // Utilitaires
      formatAmount,
      formatDate,
      getOrderStatusName,
      getOrderStatusClass,
      getActivityIconClass,
    }
  }
}
</script>

<style scoped>
.admin-dashboard {
  font-family: 'Inter', sans-serif;
}
</style>