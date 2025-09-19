<template>
  <div class="payment-history p-6 bg-gray-50 min-h-screen">
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
            <h1 class="text-3xl font-bold text-gray-900">Historique des Paiements</h1>
          </div>
          <p class="text-gray-600">Consultez tous les paiements reçus pour vos événements</p>
        </div>
        <button @click="exportPayments" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
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
          <div class="p-2 bg-green-100 rounded-lg">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm text-gray-600">Paiements Réussis</p>
            <p class="text-2xl font-bold text-green-600">{{ stats.successful_payments || 0 }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="p-2 bg-blue-100 rounded-lg">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm text-gray-600">Revenus Totaux</p>
            <p class="text-2xl font-bold text-blue-600">{{ formatAmount(stats.total_revenue || 0) }} XAF</p>
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
            <p class="text-2xl font-bold text-yellow-600">{{ stats.pending_payments || 0 }}</p>
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
            <p class="text-sm text-gray-600">Échoués</p>
            <p class="text-2xl font-bold text-red-600">{{ stats.failed_payments || 0 }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
      <h2 class="text-xl font-bold mb-4">Filtres</h2>
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Événement</label>
          <select v-model="filters.event_id" @change="loadPayments" class="w-full border rounded-lg px-3 py-2">
            <option value="">Tous les événements</option>
            <option v-for="event in events" :key="event.id" :value="event.id">
              {{ event.title }}
            </option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
          <select v-model="filters.status" @change="loadPayments" class="w-full border rounded-lg px-3 py-2">
            <option value="">Tous les statuts</option>
            <option value="success">Réussi</option>
            <option value="pending">En attente</option>
            <option value="failed">Échoué</option>
            <option value="cancelled">Annulé</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Gateway</label>
          <select v-model="filters.gateway" @change="loadPayments" class="w-full border rounded-lg px-3 py-2">
            <option value="">Tous les gateways</option>
            <option value="airtelmoney">Airtel Money</option>
            <option value="moovmoney">Moov Money</option>
            <option value="card">Carte bancaire</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Date de début</label>
          <input v-model="filters.date_from" @change="loadPayments" type="date" 
                 class="w-full border rounded-lg px-3 py-2">
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Date de fin</label>
          <input v-model="filters.date_to" @change="loadPayments" type="date" 
                 class="w-full border rounded-lg px-3 py-2">
        </div>
      </div>
      
      <div class="flex justify-between items-center mt-4">
        <button @click="resetFilters" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
          Réinitialiser les filtres
        </button>
        
        <div class="flex space-x-3">
          <input v-model="filters.search" @input="debouncedSearch" type="text" 
                 placeholder="Rechercher par référence ou email..." 
                 class="border rounded-lg px-3 py-2 w-64">
        </div>
      </div>
    </div>

    <!-- Payments List -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-6 border-b">
        <div class="flex justify-between items-center">
          <h2 class="text-xl font-bold">Liste des Paiements</h2>
          <div class="text-sm text-gray-600">
            {{ pagination ? `${pagination.total} paiement(s) trouvé(s)` : '' }}
          </div>
        </div>
      </div>
      
      <div v-if="loading" class="p-8 text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-4 text-gray-600">Chargement...</p>
      </div>
      
      <div v-else-if="payments.length === 0" class="p-8 text-center text-gray-500">
        Aucun paiement trouvé
      </div>
      
      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Référence</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Événement</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gateway</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="payment in payments" :key="payment.id">
              <td class="px-6 py-4 text-sm font-mono text-blue-600">
                <button @click="viewPaymentDetails(payment)" class="hover:text-blue-800">
                  #{{ payment.reference }}
                </button>
              </td>
              <td class="px-6 py-4 text-sm text-gray-900">
                <div>
                  <div class="font-medium">{{ payment.order?.event?.title || '-' }}</div>
                  <div class="text-gray-500">{{ payment.order?.reference }}</div>
                </div>
              </td>
              <td class="px-6 py-4 text-sm text-gray-900">
                <div>
                  <div class="font-medium">{{ payment.order?.customer_name || '-' }}</div>
                  <div class="text-gray-500">{{ payment.order?.customer_email || '-' }}</div>
                </div>
              </td>
              <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ formatAmount(payment.amount) }} XAF</td>
              <td class="px-6 py-4 text-sm">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" 
                      :class="getGatewayBadgeClass(payment.gateway)">
                  {{ getGatewayName(payment.gateway) }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" 
                      :class="getStatusBadgeClass(payment.status)">
                  {{ getStatusName(payment.status) }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-500">{{ formatDateTime(payment.created_at) }}</td>
              <td class="px-6 py-4 text-sm space-x-2">
                <button @click="viewPaymentDetails(payment)" class="text-blue-600 hover:text-blue-900">
                  Détails
                </button>
                <button v-if="payment.status === 'pending'" @click="checkPaymentStatus(payment)" 
                        class="text-green-600 hover:text-green-900">
                  Vérifier
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
            Affichage {{ pagination.from }} à {{ pagination.to }} sur {{ pagination.total }} paiements
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

    <!-- Payment Details Modal -->
    <div v-if="showDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-screen overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-bold">Détails du Paiement</h3>
          <button @click="showDetailsModal = false" class="text-gray-600 hover:text-gray-800">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        
        <div v-if="selectedPayment" class="space-y-6">
          <!-- Payment Info -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Référence</label>
              <p class="text-sm font-mono text-gray-900">#{{ selectedPayment.reference }}</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Montant</label>
              <p class="text-sm font-bold text-gray-900">{{ formatAmount(selectedPayment.amount) }} XAF</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Gateway</label>
              <p class="text-sm">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" 
                      :class="getGatewayBadgeClass(selectedPayment.gateway)">
                  {{ getGatewayName(selectedPayment.gateway) }}
                </span>
              </p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Statut</label>
              <p class="text-sm">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" 
                      :class="getStatusBadgeClass(selectedPayment.status)">
                  {{ getStatusName(selectedPayment.status) }}
                </span>
              </p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Date de création</label>
              <p class="text-sm text-gray-900">{{ formatDateTime(selectedPayment.created_at) }}</p>
            </div>
            
            <div v-if="selectedPayment.processed_at">
              <label class="block text-sm font-medium text-gray-700">Date de traitement</label>
              <p class="text-sm text-gray-900">{{ formatDateTime(selectedPayment.processed_at) }}</p>
            </div>
          </div>

          <!-- Order Info -->
          <div v-if="selectedPayment.order" class="border-t pt-6">
            <h4 class="text-md font-bold mb-4">Informations de la Commande</h4>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Référence commande</label>
                <p class="text-sm font-mono text-gray-900">{{ selectedPayment.order.reference }}</p>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Événement</label>
                <p class="text-sm text-gray-900">{{ selectedPayment.order.event?.title || '-' }}</p>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Client</label>
                <p class="text-sm text-gray-900">{{ selectedPayment.order.customer_name || '-' }}</p>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <p class="text-sm text-gray-900">{{ selectedPayment.order.customer_email || '-' }}</p>
              </div>
            </div>
          </div>

          <!-- Gateway Response -->
          <div v-if="selectedPayment.gateway_response" class="border-t pt-6">
            <h4 class="text-md font-bold mb-4">Réponse Gateway</h4>
            <div class="bg-gray-50 p-4 rounded-lg">
              <pre class="text-xs text-gray-700 whitespace-pre-wrap">{{ JSON.stringify(selectedPayment.gateway_response, null, 2) }}</pre>
            </div>
          </div>

          <!-- Actions -->
          <div class="border-t pt-6 flex justify-end space-x-3">
            <button v-if="selectedPayment.status === 'pending'" @click="checkPaymentStatus(selectedPayment)" 
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
              Vérifier le Statut
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
  name: 'PaymentHistory',
  setup() {
    // État réactif
    const loading = ref(false)
    const showDetailsModal = ref(false)
    const selectedPayment = ref(null)
    
    const payments = ref([])
    const events = ref([])
    const pagination = ref(null)
    
    const stats = reactive({
      successful_payments: 0,
      total_revenue: 0,
      pending_payments: 0,
      failed_payments: 0
    })
    
    const filters = reactive({
      event_id: '',
      status: '',
      gateway: '',
      date_from: '',
      date_to: '',
      search: '',
      page: 1
    })

    let searchTimeout = null

    // Méthodes
    const loadPayments = async () => {
      loading.value = true
      try {
        const queryParams = new URLSearchParams()
        Object.keys(filters).forEach(key => {
          if (filters[key] && filters[key] !== '') {
            queryParams.append(key, filters[key])
          }
        })
        
        const response = await fetch(`/api/v1/organizer/payments?${queryParams}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        const data = await response.json()
        if (data.success) {
          payments.value = data.data.payments.data
          pagination.value = {
            current_page: data.data.payments.current_page,
            last_page: data.data.payments.last_page,
            from: data.data.payments.from,
            to: data.data.payments.to,
            total: data.data.payments.total
          }
          
          // Calculer les stats
          const allPayments = data.data.payments.data
          stats.successful_payments = allPayments.filter(p => p.status === 'success').length
          stats.pending_payments = allPayments.filter(p => p.status === 'pending').length
          stats.failed_payments = allPayments.filter(p => p.status === 'failed').length
          stats.total_revenue = allPayments
            .filter(p => p.status === 'success')
            .reduce((sum, p) => sum + p.amount, 0)
        }
      } catch (error) {
        console.error('Erreur chargement paiements:', error)
      } finally {
        loading.value = false
      }
    }

    const loadEvents = async () => {
      try {
        const response = await fetch('/api/v1/organizer/events', {
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
        loadPayments()
      }, 500)
    }

    const changePage = (page) => {
      if (page >= 1 && page <= pagination.value.last_page) {
        filters.page = page
        loadPayments()
      }
    }

    const resetFilters = () => {
      Object.assign(filters, {
        event_id: '',
        status: '',
        gateway: '',
        date_from: '',
        date_to: '',
        search: '',
        page: 1
      })
      loadPayments()
    }

    const viewPaymentDetails = (payment) => {
      selectedPayment.value = payment
      showDetailsModal.value = true
    }

    const checkPaymentStatus = async (payment) => {
      try {
        const response = await fetch(`/api/v1/organizer/payments/${payment.id}/status`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        const data = await response.json()
        if (data.success) {
          alert(`Statut mis à jour: ${getStatusName(data.data.status)}`)
          loadPayments()
        } else {
          alert(data.message || 'Erreur lors de la vérification')
        }
      } catch (error) {
        console.error('Erreur vérification statut:', error)
        alert('Erreur technique')
      }
    }

    const exportPayments = async () => {
      try {
        const queryParams = new URLSearchParams()
        Object.keys(filters).forEach(key => {
          if (filters[key] && filters[key] !== '' && key !== 'page') {
            queryParams.append(key, filters[key])
          }
        })
        
        const response = await fetch(`/api/v1/organizer/payments/export?${queryParams}`, {
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
          a.download = `paiements-${new Date().toISOString().split('T')[0]}.csv`
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

    const getGatewayName = (gateway) => {
      const names = {
        airtelmoney: 'Airtel Money',
        moovmoney: 'Moov Money',
        card: 'Carte bancaire'
      }
      return names[gateway] || gateway
    }

    const getGatewayBadgeClass = (gateway) => {
      const classes = {
        airtelmoney: 'bg-red-100 text-red-800',
        moovmoney: 'bg-orange-100 text-orange-800',
        card: 'bg-blue-100 text-blue-800'
      }
      return classes[gateway] || 'bg-gray-100 text-gray-800'
    }

    const getStatusName = (status) => {
      const names = {
        success: 'Réussi',
        pending: 'En attente',
        failed: 'Échoué',
        cancelled: 'Annulé'
      }
      return names[status] || status
    }

    const getStatusBadgeClass = (status) => {
      const classes = {
        success: 'bg-green-100 text-green-800',
        pending: 'bg-yellow-100 text-yellow-800',
        failed: 'bg-red-100 text-red-800',
        cancelled: 'bg-gray-100 text-gray-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    }

    // Lifecycle
    onMounted(() => {
      loadPayments()
      loadEvents()
    })

    return {
      // État
      loading,
      showDetailsModal,
      selectedPayment,
      payments,
      events,
      pagination,
      stats,
      filters,
      
      // Méthodes
      loadPayments,
      loadEvents,
      debouncedSearch,
      changePage,
      resetFilters,
      viewPaymentDetails,
      checkPaymentStatus,
      exportPayments,
      
      // Utilitaires
      formatAmount,
      formatDateTime,
      getGatewayName,
      getGatewayBadgeClass,
      getStatusName,
      getStatusBadgeClass,
    }
  }
}
</script>

<style scoped>
.payment-history {
  font-family: 'Inter', sans-serif;
}
</style>