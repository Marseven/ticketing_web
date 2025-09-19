<template>
  <div class="event-sales-details p-6 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <div class="flex items-center mb-2">
            <button @click="$router.go(-1)" class="text-gray-600 hover:text-gray-800 mr-4">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
              </svg>
            </button>
            <h1 class="text-3xl font-bold text-gray-900">Ventes de l'Événement</h1>
          </div>
          <p v-if="event" class="text-gray-600">{{ event.title }}</p>
        </div>
        <div class="flex space-x-3">
          <button @click="exportSalesData" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
            </svg>
            Exporter
          </button>
          <button @click="$router.push(`/organizer/events/${eventId}/physical-sales`)" 
                  class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Ventes Physiques
          </button>
        </div>
      </div>
    </div>

    <!-- Loading state -->
    <div v-if="loading" class="flex justify-center items-center h-64">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <div v-else-if="event">
      <!-- Event Info -->
      <div class="bg-white rounded-lg shadow p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ event.title }}</h3>
            <p class="text-sm text-gray-600">{{ event.category?.name }}</p>
            <p class="text-sm text-gray-600">{{ event.venue?.name }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-600">Prochaine séance</p>
            <p class="font-medium" v-if="nextSchedule">{{ formatDateTime(nextSchedule.starts_at) }}</p>
            <p class="text-gray-500" v-else>Aucune séance programmée</p>
          </div>
          <div>
            <p class="text-sm text-gray-600">Statut</p>
            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" 
                  :class="getStatusBadgeClass(event.status)">
              {{ getStatusName(event.status) }}
            </span>
          </div>
          <div>
            <p class="text-sm text-gray-600">Date de création</p>
            <p class="font-medium">{{ formatDate(event.created_at) }}</p>
          </div>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-2 bg-blue-100 rounded-lg">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm text-gray-600">Total Tickets</p>
              <p class="text-2xl font-bold text-gray-900">{{ stats.total_tickets || 0 }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-2 bg-green-100 rounded-lg">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm text-gray-600">Tickets Vendus</p>
              <p class="text-2xl font-bold text-green-600">{{ stats.sold_tickets || 0 }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-2 bg-yellow-100 rounded-lg">
              <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm text-gray-600">Revenus</p>
              <p class="text-2xl font-bold text-yellow-600">{{ formatAmount(stats.revenue || 0) }} XAF</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-2 bg-purple-100 rounded-lg">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm text-gray-600">Taux d'Utilisation</p>
              <p class="text-2xl font-bold text-purple-600">{{ stats.usage_rate || 0 }}%</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts and Analytics -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Sales by Type -->
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-xl font-bold mb-4">Ventes par Type de Billet</h2>
          <div v-if="salesByType.length === 0" class="text-center text-gray-500 py-8">
            Aucune donnée disponible
          </div>
          <div v-else class="space-y-4">
            <div v-for="type in salesByType" :key="type.type_name" 
                 class="border rounded-lg p-4">
              <div class="flex justify-between items-center mb-2">
                <h3 class="font-medium text-gray-900">{{ type.type_name }}</h3>
                <span class="text-sm font-bold text-green-600">{{ formatAmount(type.price) }} XAF</span>
              </div>
              
              <div class="grid grid-cols-3 gap-4 text-sm">
                <div>
                  <p class="text-gray-600">Capacité</p>
                  <p class="font-medium">{{ type.total_capacity }}</p>
                </div>
                <div>
                  <p class="text-gray-600">Vendus</p>
                  <p class="font-medium text-green-600">{{ type.sold }}</p>
                </div>
                <div>
                  <p class="text-gray-600">Utilisés</p>
                  <p class="font-medium text-blue-600">{{ type.used }}</p>
                </div>
              </div>
              
              <div class="mt-3">
                <div class="flex justify-between text-xs text-gray-600 mb-1">
                  <span>Progression des ventes</span>
                  <span>{{ Math.round((type.sold / type.total_capacity) * 100) }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div class="bg-green-600 h-2 rounded-full" 
                       :style="`width: ${Math.min((type.sold / type.total_capacity) * 100, 100)}%`"></div>
                </div>
              </div>
              
              <div class="flex justify-between items-center mt-3 pt-3 border-t">
                <span class="text-sm text-gray-600">Revenus</span>
                <span class="font-bold text-gray-900">{{ formatAmount(type.revenue) }} XAF</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Sales by Day -->
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-xl font-bold mb-4">Ventes par Jour</h2>
          <div v-if="salesByDay.length === 0" class="text-center text-gray-500 py-8">
            Aucune donnée disponible
          </div>
          <div v-else class="space-y-3">
            <div v-for="day in salesByDay" :key="day.date" 
                 class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
              <div>
                <p class="font-medium text-gray-900">{{ formatDate(day.date) }}</p>
                <p class="text-sm text-gray-600">{{ day.count }} ticket(s)</p>
              </div>
              <div class="text-right">
                <p class="font-bold text-gray-900">{{ formatAmount(day.revenue) }} XAF</p>
                <div class="w-16 bg-gray-200 rounded-full h-1 mt-1">
                  <div class="bg-blue-600 h-1 rounded-full" 
                       :style="`width: ${(day.revenue / maxDailyRevenue) * 100}%`"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tickets List -->
      <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b">
          <div class="flex justify-between items-center">
            <h2 class="text-xl font-bold">Liste des Tickets</h2>
            <div class="flex space-x-3">
              <select v-model="filters.status" @change="loadEventSales" class="border rounded-lg px-3 py-2 text-sm">
                <option value="">Tous les statuts</option>
                <option value="issued">Émis</option>
                <option value="used">Utilisé</option>
                <option value="cancelled">Annulé</option>
              </select>
              
              <select v-model="filters.type" @change="loadEventSales" class="border rounded-lg px-3 py-2 text-sm">
                <option value="">Tous les types</option>
                <option v-for="type in event.ticketTypes" :key="type.id" :value="type.id">
                  {{ type.name }}
                </option>
              </select>
            </div>
          </div>
        </div>
        
        <div v-if="loadingTickets" class="p-8 text-center">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
          <p class="mt-4 text-gray-600">Chargement des tickets...</p>
        </div>
        
        <div v-else-if="tickets.length === 0" class="p-8 text-center text-gray-500">
          Aucun ticket trouvé
        </div>
        
        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prix</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date Vente</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Utilisation</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="ticket in tickets" :key="ticket.id">
                <td class="px-6 py-4 text-sm font-mono text-gray-900">{{ ticket.code }}</td>
                <td class="px-6 py-4 text-sm text-gray-900">{{ ticket.ticketType?.name }}</td>
                <td class="px-6 py-4 text-sm text-gray-900">
                  {{ ticket.order?.customer_name || ticket.order?.customer_email }}
                </td>
                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                  {{ formatAmount(ticket.ticketType?.price || 0) }} XAF
                </td>
                <td class="px-6 py-4 text-sm">
                  <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" 
                        :class="getTicketStatusClass(ticket.status)">
                    {{ getTicketStatusName(ticket.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">{{ formatDateTime(ticket.created_at) }}</td>
                <td class="px-6 py-4 text-sm text-gray-500">
                  {{ ticket.used_at ? formatDateTime(ticket.used_at) : '-' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Pagination -->
        <div v-if="pagination && pagination.last_page > 1" class="p-6 border-t">
          <div class="flex justify-between items-center">
            <div class="text-sm text-gray-700">
              Affichage {{ pagination.from }} à {{ pagination.to }} sur {{ pagination.total }} tickets
            </div>
            <div class="flex space-x-2">
              <button @click="changePage(pagination.current_page - 1)" 
                      :disabled="pagination.current_page <= 1"
                      class="px-3 py-1 border rounded disabled:opacity-50">
                Précédent
              </button>
              <button @click="changePage(pagination.current_page + 1)" 
                      :disabled="pagination.current_page >= pagination.last_page"
                      class="px-3 py-1 border rounded disabled:opacity-50">
                Suivant
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'

export default {
  name: 'EventSalesDetails',
  setup() {
    const route = useRoute()
    const eventId = route.params.eventId
    
    // État réactif
    const loading = ref(false)
    const loadingTickets = ref(false)
    const event = ref(null)
    const tickets = ref([])
    const pagination = ref(null)
    
    const stats = reactive({
      total_tickets: 0,
      sold_tickets: 0,
      used_tickets: 0,
      revenue: 0,
      usage_rate: 0
    })
    
    const salesByType = ref([])
    const salesByDay = ref([])
    
    const filters = reactive({
      status: '',
      type: '',
      page: 1
    })

    // Computed
    const nextSchedule = computed(() => {
      if (!event.value?.schedules) return null
      return event.value.schedules.find(s => new Date(s.starts_at) > new Date())
    })

    const maxDailyRevenue = computed(() => {
      if (salesByDay.value.length === 0) return 1
      return Math.max(...salesByDay.value.map(d => d.revenue))
    })

    // Méthodes
    const loadEventSales = async () => {
      loading.value = true
      try {
        const response = await fetch(`/api/v1/organizer/events/${eventId}/sales`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        const data = await response.json()
        if (data.success) {
          event.value = data.data.event
          Object.assign(stats, data.data.stats)
          salesByType.value = data.data.sales_by_type
          salesByDay.value = data.data.sales_by_day
        }
      } catch (error) {
        console.error('Erreur chargement ventes événement:', error)
      } finally {
        loading.value = false
      }
    }

    const loadTickets = async () => {
      loadingTickets.value = true
      try {
        const queryParams = new URLSearchParams()
        if (filters.status) queryParams.append('status', filters.status)
        if (filters.type) queryParams.append('type_id', filters.type)
        queryParams.append('page', filters.page)
        
        const response = await fetch(`/api/v1/organizer/events/${eventId}/tickets?${queryParams}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        const data = await response.json()
        if (data.success) {
          tickets.value = data.data.tickets.data
          pagination.value = {
            current_page: data.data.tickets.current_page,
            last_page: data.data.tickets.last_page,
            from: data.data.tickets.from,
            to: data.data.tickets.to,
            total: data.data.tickets.total
          }
        }
      } catch (error) {
        console.error('Erreur chargement tickets:', error)
      } finally {
        loadingTickets.value = false
      }
    }

    const changePage = (page) => {
      if (page >= 1 && page <= pagination.value.last_page) {
        filters.page = page
        loadTickets()
      }
    }

    const exportSalesData = async () => {
      try {
        const response = await fetch(`/api/v1/organizer/events/${eventId}/export`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        if (response.ok) {
          const blob = await response.blob()
          const url = window.URL.createObjectURL(blob)
          const a = document.createElement('a')
          a.href = url
          a.download = `ventes-${event.value?.title || 'evenement'}-${new Date().toISOString().split('T')[0]}.csv`
          document.body.appendChild(a)
          a.click()
          window.URL.revokeObjectURL(url)
          document.body.removeChild(a)
        }
      } catch (error) {
        console.error('Erreur export:', error)
        alert('Erreur lors de l\'export des données')
      }
    }

    // Utilitaires
    const formatAmount = (amount) => {
      return new Intl.NumberFormat('fr-FR').format(amount)
    }

    const formatDate = (date) => {
      return new Date(date).toLocaleDateString('fr-FR')
    }

    const formatDateTime = (datetime) => {
      return new Date(datetime).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    const getStatusName = (status) => {
      const names = {
        draft: 'Brouillon',
        published: 'Publié',
        cancelled: 'Annulé'
      }
      return names[status] || status
    }

    const getStatusBadgeClass = (status) => {
      const classes = {
        draft: 'bg-gray-100 text-gray-800',
        published: 'bg-green-100 text-green-800',
        cancelled: 'bg-red-100 text-red-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    }

    const getTicketStatusName = (status) => {
      const names = {
        issued: 'Émis',
        used: 'Utilisé',
        cancelled: 'Annulé'
      }
      return names[status] || status
    }

    const getTicketStatusClass = (status) => {
      const classes = {
        issued: 'bg-blue-100 text-blue-800',
        used: 'bg-green-100 text-green-800',
        cancelled: 'bg-red-100 text-red-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    }

    // Lifecycle
    onMounted(() => {
      loadEventSales()
      loadTickets()
    })

    return {
      // État
      loading,
      loadingTickets,
      event,
      tickets,
      pagination,
      stats,
      salesByType,
      salesByDay,
      filters,
      eventId,
      nextSchedule,
      maxDailyRevenue,
      
      // Méthodes
      loadEventSales,
      loadTickets,
      changePage,
      exportSalesData,
      
      // Utilitaires
      formatAmount,
      formatDate,
      formatDateTime,
      getStatusName,
      getStatusBadgeClass,
      getTicketStatusName,
      getTicketStatusClass,
    }
  }
}
</script>

<style scoped>
.event-sales-details {
  font-family: 'Inter', sans-serif;
}
</style>