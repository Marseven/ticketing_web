<template>
  <div class="order-management p-6 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 mb-2">Suivi des Commandes</h1>
          <p class="text-gray-600">Gérer et suivre toutes les commandes de la plateforme</p>
        </div>
        <button @click="exportOrders" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
          <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
          </svg>
          Exporter
        </button>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="p-2 bg-blue-100 rounded-lg">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm text-gray-600">Total Commandes</p>
            <p class="text-2xl font-bold text-blue-600">{{ stats.total_orders || 0 }}</p>
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
            <p class="text-sm text-gray-600">Confirmées</p>
            <p class="text-2xl font-bold text-green-600">{{ stats.confirmed_orders || 0 }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="p-2 bg-yellow-100 rounded-lg">
            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm text-gray-600">En Attente</p>
            <p class="text-2xl font-bold text-yellow-600">{{ stats.pending_orders || 0 }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="p-2 bg-purple-100 rounded-lg">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm text-gray-600">Revenus Total</p>
            <p class="text-2xl font-bold text-purple-600">{{ formatAmount(stats.total_revenue || 0) }} XAF</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
      <h2 class="text-xl font-bold mb-4">Filtres</h2>
      <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
          <input v-model="filters.search" @input="debouncedSearch" type="text" 
                 placeholder="Référence ou email..." class="w-full border rounded-lg px-3 py-2">
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
          <select v-model="filters.status" @change="loadOrders" class="w-full border rounded-lg px-3 py-2">
            <option value="">Tous les statuts</option>
            <option value="pending">En attente</option>
            <option value="confirmed">Confirmée</option>
            <option value="cancelled">Annulée</option>
            <option value="refunded">Remboursée</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Organisateur</label>
          <select v-model="filters.organizer_id" @change="loadOrders" class="w-full border rounded-lg px-3 py-2">
            <option value="">Tous les organisateurs</option>
            <option v-for="organizer in organizers" :key="organizer.id" :value="organizer.id">
              {{ organizer.name }}
            </option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Événement</label>
          <select v-model="filters.event_id" @change="loadOrders" class="w-full border rounded-lg px-3 py-2">
            <option value="">Tous les événements</option>
            <option v-for="event in events" :key="event.id" :value="event.id">
              {{ event.title }}
            </option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Date de début</label>
          <input v-model="filters.date_from" @change="loadOrders" type="date" 
                 class="w-full border rounded-lg px-3 py-2">
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Date de fin</label>
          <input v-model="filters.date_to" @change="loadOrders" type="date" 
                 class="w-full border rounded-lg px-3 py-2">
        </div>
      </div>
      
      <div class="flex justify-between items-center mt-4">
        <button @click="resetFilters" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
          Réinitialiser les filtres
        </button>
        
        <div class="text-sm text-gray-600">
          {{ pagination ? `${pagination.total} commande(s) trouvée(s)` : '' }}
        </div>
      </div>
    </div>

    <!-- Orders List -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-6 border-b">
        <h2 class="text-xl font-bold">Liste des Commandes</h2>
      </div>
      
      <div v-if="loading" class="p-8 text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-4 text-gray-600">Chargement...</p>
      </div>
      
      <div v-else-if="orders.length === 0" class="p-8 text-center text-gray-500">
        Aucune commande trouvée
      </div>
      
      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Référence</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Événement</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tickets</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="order in orders" :key="order.id">
              <td class="px-6 py-4 text-sm font-mono text-blue-600">
                <button @click="viewOrderDetails(order)" class="hover:text-blue-800">
                  #{{ order.reference }}
                </button>
              </td>
              <td class="px-6 py-4 text-sm text-gray-900">
                <div>
                  <div class="font-medium">{{ order.customer_name }}</div>
                  <div class="text-gray-500">{{ order.customer_email }}</div>
                </div>
              </td>
              <td class="px-6 py-4 text-sm text-gray-900">
                <div>
                  <div class="font-medium">{{ order.event?.title || '-' }}</div>
                  <div class="text-gray-500">{{ order.event?.organizer?.name || '-' }}</div>
                </div>
              </td>
              <td class="px-6 py-4 text-sm text-gray-900">
                <div class="space-y-1">
                  <div v-for="item in order.items" :key="item.id" class="text-xs">
                    {{ item.quantity }}x {{ item.ticket_type_name }}
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ formatAmount(order.total_amount) }} XAF</td>
              <td class="px-6 py-4 text-sm">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" 
                      :class="getStatusBadgeClass(order.status)">
                  {{ getStatusName(order.status) }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-500">{{ formatDateTime(order.created_at) }}</td>
              <td class="px-6 py-4 text-sm space-x-2">
                <button @click="viewOrderDetails(order)" class="text-blue-600 hover:text-blue-900">
                  Détails
                </button>
                <button v-if="order.status === 'pending'" @click="updateOrderStatus(order, 'confirmed')" 
                        class="text-green-600 hover:text-green-900">
                  Confirmer
                </button>
                <button v-if="['pending', 'confirmed'].includes(order.status)" @click="updateOrderStatus(order, 'cancelled')" 
                        class="text-red-600 hover:text-red-900">
                  Annuler
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <div v-if="pagination && pagination.last_page > 1" class="p-6 border-t">
        <div class="flex justify-between items-center">
          <div class="text-sm text-gray-700">
            Affichage {{ pagination.from }} à {{ pagination.to }} sur {{ pagination.total }} commandes
          </div>
          <div class="flex space-x-2">
            <button @click="changePage(pagination.current_page - 1)" 
                    :disabled="pagination.current_page <= 1"
                    class="px-3 py-1 border rounded disabled:opacity-50">
              Précédent
            </button>
            <span class="px-3 py-1 text-sm text-gray-600">
              Page {{ pagination.current_page }} sur {{ pagination.last_page }}
            </span>
            <button @click="changePage(pagination.current_page + 1)" 
                    :disabled="pagination.current_page >= pagination.last_page"
                    class="px-3 py-1 border rounded disabled:opacity-50">
              Suivant
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Order Details Modal -->
    <div v-if="showDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-4xl max-h-screen overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-bold">Détails de la Commande</h3>
          <button @click="showDetailsModal = false" class="text-gray-600 hover:text-gray-800">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        
        <div v-if="selectedOrder" class="space-y-6">
          <!-- Order Info -->
          <div class="grid grid-cols-2 gap-6">
            <div class="space-y-4">
              <div>
                <h4 class="text-md font-bold mb-3">Informations Commande</h4>
                <div class="space-y-2">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Référence</label>
                    <p class="text-sm font-mono text-gray-900">#{{ selectedOrder.reference }}</p>
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Statut</label>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" 
                          :class="getStatusBadgeClass(selectedOrder.status)">
                      {{ getStatusName(selectedOrder.status) }}
                    </span>
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Date de création</label>
                    <p class="text-sm text-gray-900">{{ formatDateTime(selectedOrder.created_at) }}</p>
                  </div>
                  
                  <div v-if="selectedOrder.confirmed_at">
                    <label class="block text-sm font-medium text-gray-700">Date de confirmation</label>
                    <p class="text-sm text-gray-900">{{ formatDateTime(selectedOrder.confirmed_at) }}</p>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="space-y-4">
              <div>
                <h4 class="text-md font-bold mb-3">Informations Client</h4>
                <div class="space-y-2">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Nom</label>
                    <p class="text-sm text-gray-900">{{ selectedOrder.customer_name }}</p>
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <p class="text-sm text-gray-900">{{ selectedOrder.customer_email }}</p>
                  </div>
                  
                  <div v-if="selectedOrder.customer_phone">
                    <label class="block text-sm font-medium text-gray-700">Téléphone</label>
                    <p class="text-sm text-gray-900">{{ selectedOrder.customer_phone }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Event Info -->
          <div v-if="selectedOrder.event" class="border-t pt-6">
            <h4 class="text-md font-bold mb-3">Événement</h4>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Titre</label>
                <p class="text-sm text-gray-900">{{ selectedOrder.event.title }}</p>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Organisateur</label>
                <p class="text-sm text-gray-900">{{ selectedOrder.event.organizer?.name || '-' }}</p>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Lieu</label>
                <p class="text-sm text-gray-900">{{ selectedOrder.event.venue?.name || '-' }}</p>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Date événement</label>
                <p class="text-sm text-gray-900">{{ selectedOrder.event.schedules?.[0] ? formatDateTime(selectedOrder.event.schedules[0].starts_at) : '-' }}</p>
              </div>
            </div>
          </div>

          <!-- Order Items -->
          <div class="border-t pt-6">
            <h4 class="text-md font-bold mb-3">Articles Commandés</h4>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-2 text-left">Type de billet</th>
                    <th class="px-4 py-2 text-left">Prix unitaire</th>
                    <th class="px-4 py-2 text-left">Quantité</th>
                    <th class="px-4 py-2 text-left">Total</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                  <tr v-for="item in selectedOrder.items" :key="item.id">
                    <td class="px-4 py-2">{{ item.ticket_type_name }}</td>
                    <td class="px-4 py-2">{{ formatAmount(item.unit_price) }} XAF</td>
                    <td class="px-4 py-2">{{ item.quantity }}</td>
                    <td class="px-4 py-2 font-medium">{{ formatAmount(item.total_price) }} XAF</td>
                  </tr>
                </tbody>
                <tfoot class="bg-gray-50">
                  <tr>
                    <td colspan="3" class="px-4 py-2 font-bold text-right">Total</td>
                    <td class="px-4 py-2 font-bold">{{ formatAmount(selectedOrder.total_amount) }} XAF</td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>

          <!-- Tickets -->
          <div v-if="selectedOrder.tickets && selectedOrder.tickets.length > 0" class="border-t pt-6">
            <h4 class="text-md font-bold mb-3">Tickets Générés</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
              <div v-for="ticket in selectedOrder.tickets" :key="ticket.id" 
                   class="border rounded-lg p-3 text-sm">
                <div class="flex justify-between items-start mb-2">
                  <span class="font-mono text-blue-600">{{ ticket.code }}</span>
                  <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" 
                        :class="getTicketStatusClass(ticket.status)">
                    {{ getTicketStatusName(ticket.status) }}
                  </span>
                </div>
                <div class="text-gray-600">
                  <p>{{ ticket.ticketType?.name }}</p>
                  <p v-if="ticket.used_at">Utilisé: {{ formatDateTime(ticket.used_at) }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="border-t pt-6 flex justify-end space-x-3">
            <button v-if="selectedOrder.status === 'pending'" @click="updateOrderStatus(selectedOrder, 'confirmed')" 
                    class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
              Confirmer la Commande
            </button>
            <button v-if="['pending', 'confirmed'].includes(selectedOrder.status)" @click="updateOrderStatus(selectedOrder, 'cancelled')" 
                    class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
              Annuler la Commande
            </button>
            <button @click="showDetailsModal = false" 
                    class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
              Fermer
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue'

export default {
  name: 'OrderManagement',
  setup() {
    // État réactif
    const loading = ref(false)
    const showDetailsModal = ref(false)
    const selectedOrder = ref(null)
    
    const orders = ref([])
    const organizers = ref([])
    const events = ref([])
    const pagination = ref(null)
    
    const stats = reactive({
      total_orders: 0,
      confirmed_orders: 0,
      pending_orders: 0,
      total_revenue: 0
    })
    
    const filters = reactive({
      search: '',
      status: '',
      organizer_id: '',
      event_id: '',
      date_from: '',
      date_to: '',
      page: 1
    })

    let searchTimeout = null

    // Méthodes
    const loadOrders = async () => {
      loading.value = true
      try {
        const queryParams = new URLSearchParams()
        Object.keys(filters).forEach(key => {
          if (filters[key] && filters[key] !== '') {
            queryParams.append(key, filters[key])
          }
        })
        
        const response = await fetch(`/api/v1/admin/orders?${queryParams}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        const data = await response.json()
        if (data.success) {
          orders.value = data.data.orders.data
          pagination.value = {
            current_page: data.data.orders.current_page,
            last_page: data.data.orders.last_page,
            from: data.data.orders.from,
            to: data.data.orders.to,
            total: data.data.orders.total
          }
          
          // Calculer les stats
          const allOrders = data.data.orders.data
          stats.total_orders = pagination.value.total
          stats.confirmed_orders = allOrders.filter(o => o.status === 'confirmed').length
          stats.pending_orders = allOrders.filter(o => o.status === 'pending').length
          stats.total_revenue = allOrders
            .filter(o => o.status === 'confirmed')
            .reduce((sum, o) => sum + o.total_amount, 0)
        }
      } catch (error) {
        console.error('Erreur chargement commandes:', error)
      } finally {
        loading.value = false
      }
    }

    const loadOrganizers = async () => {
      try {
        const response = await fetch('/api/v1/admin/organizers', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        const data = await response.json()
        if (data.success) {
          organizers.value = data.data.organizers.data || data.data.organizers
        }
      } catch (error) {
        console.error('Erreur chargement organisateurs:', error)
      }
    }

    const loadEvents = async () => {
      try {
        const response = await fetch('/api/v1/admin/events', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        const data = await response.json()
        if (data.success) {
          events.value = data.data.events.data || data.data.events
        }
      } catch (error) {
        console.error('Erreur chargement événements:', error)
      }
    }

    const debouncedSearch = () => {
      clearTimeout(searchTimeout)
      searchTimeout = setTimeout(() => {
        filters.page = 1
        loadOrders()
      }, 500)
    }

    const changePage = (page) => {
      if (page >= 1 && page <= pagination.value.last_page) {
        filters.page = page
        loadOrders()
      }
    }

    const resetFilters = () => {
      Object.assign(filters, {
        search: '',
        status: '',
        organizer_id: '',
        event_id: '',
        date_from: '',
        date_to: '',
        page: 1
      })
      loadOrders()
    }

    const viewOrderDetails = async (order) => {
      try {
        const response = await fetch(`/api/v1/admin/orders/${order.id}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        const data = await response.json()
        if (data.success) {
          selectedOrder.value = data.data.order
          showDetailsModal.value = true
        }
      } catch (error) {
        console.error('Erreur chargement détails commande:', error)
      }
    }

    const updateOrderStatus = async (order, newStatus) => {
      if (!confirm(`Êtes-vous sûr de vouloir ${newStatus === 'confirmed' ? 'confirmer' : 'annuler'} cette commande ?`)) {
        return
      }

      try {
        const response = await fetch(`/api/v1/admin/orders/${order.id}/status`, {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ status: newStatus })
        })
        
        const data = await response.json()
        if (data.success) {
          alert(`Commande ${newStatus === 'confirmed' ? 'confirmée' : 'annulée'} avec succès`)
          loadOrders()
          if (showDetailsModal.value) {
            selectedOrder.value.status = newStatus
          }
        } else {
          alert(data.message || 'Erreur lors de la mise à jour')
        }
      } catch (error) {
        console.error('Erreur mise à jour statut commande:', error)
        alert('Erreur technique')
      }
    }

    const exportOrders = async () => {
      try {
        const queryParams = new URLSearchParams()
        Object.keys(filters).forEach(key => {
          if (filters[key] && filters[key] !== '' && key !== 'page') {
            queryParams.append(key, filters[key])
          }
        })
        
        const response = await fetch(`/api/v1/admin/orders/export?${queryParams}`, {
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
          a.download = `commandes-${new Date().toISOString().split('T')[0]}.csv`
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
        pending: 'En attente',
        confirmed: 'Confirmée',
        cancelled: 'Annulée',
        refunded: 'Remboursée'
      }
      return names[status] || status
    }

    const getStatusBadgeClass = (status) => {
      const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        confirmed: 'bg-green-100 text-green-800',
        cancelled: 'bg-red-100 text-red-800',
        refunded: 'bg-purple-100 text-purple-800'
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
      loadOrders()
      loadOrganizers()
      loadEvents()
    })

    return {
      // État
      loading,
      showDetailsModal,
      selectedOrder,
      orders,
      organizers,
      events,
      pagination,
      stats,
      filters,
      
      // Méthodes
      loadOrders,
      loadOrganizers,
      loadEvents,
      debouncedSearch,
      changePage,
      resetFilters,
      viewOrderDetails,
      updateOrderStatus,
      exportOrders,
      
      // Utilitaires
      formatAmount,
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
.order-management {
  font-family: 'Inter', sans-serif;
}
</style>