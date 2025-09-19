<template>
  <div class="payment-tracking p-6 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 mb-2">Suivi des Paiements</h1>
          <p class="text-gray-600">Monitorer tous les paiements de la plateforme</p>
        </div>
        <div class="flex space-x-3">
          <button @click="refreshPayments" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Actualiser
          </button>
          <button @click="exportPayments" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
            </svg>
            Exporter
          </button>
        </div>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="p-2 bg-blue-100 rounded-lg">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm text-gray-600">Total Paiements</p>
            <p class="text-2xl font-bold text-blue-600">{{ stats.total_payments || 0 }}</p>
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
            <p class="text-sm text-gray-600">Réussis</p>
            <p class="text-2xl font-bold text-green-600">{{ stats.successful_payments || 0 }}</p>
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

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="p-2 bg-purple-100 rounded-lg">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm text-gray-600">Volume Total</p>
            <p class="text-2xl font-bold text-purple-600">{{ formatAmount(stats.total_volume || 0) }} XAF</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Gateway Analytics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
      <!-- Payment Methods Distribution -->
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold mb-4">Répartition par Gateway</h2>
        <div v-if="gatewayStats.length === 0" class="text-center text-gray-500 py-8">
          Aucune donnée disponible
        </div>
        <div v-else class="space-y-4">
          <div v-for="gateway in gatewayStats" :key="gateway.gateway" 
               class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div class="flex items-center">
              <div class="p-2 rounded-lg mr-3" :class="getGatewayBadgeClass(gateway.gateway)">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
              </div>
              <div>
                <p class="font-medium text-gray-900">{{ getGatewayName(gateway.gateway) }}</p>
                <p class="text-sm text-gray-600">{{ gateway.count }} paiement(s)</p>
              </div>
            </div>
            <div class="text-right">
              <p class="font-bold text-gray-900">{{ formatAmount(gateway.volume) }} XAF</p>
              <p class="text-sm text-gray-600">{{ Math.round((gateway.count / stats.total_payments) * 100) }}%</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Trends -->
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold mb-4">Tendances Récentes (7 derniers jours)</h2>
        <div v-if="dailyStats.length === 0" class="text-center text-gray-500 py-8">
          Aucune donnée disponible
        </div>
        <div v-else class="space-y-3">
          <div v-for="day in dailyStats" :key="day.date" 
               class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
            <div>
              <p class="font-medium text-gray-900">{{ formatDate(day.date) }}</p>
              <p class="text-sm text-gray-600">{{ day.count }} paiement(s)</p>
            </div>
            <div class="text-right">
              <p class="font-bold text-gray-900">{{ formatAmount(day.volume) }} XAF</p>
              <div class="w-16 bg-gray-200 rounded-full h-2 mt-1">
                <div class="bg-blue-600 h-2 rounded-full" 
                     :style="`width: ${Math.min((day.volume / maxDailyVolume) * 100, 100)}%`"></div>
              </div>
            </div>
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
          <label class="block text-sm font-medium text-gray-700 mb-2">Organisateur</label>
          <select v-model="filters.organizer_id" @change="loadPayments" class="w-full border rounded-lg px-3 py-2">
            <option value="">Tous les organisateurs</option>
            <option v-for="organizer in organizers" :key="organizer.id" :value="organizer.id">
              {{ organizer.name }}
            </option>
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
          <button @click="checkPendingPayments" :disabled="checkingPayments"
                  class="bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 disabled:opacity-50">
            {{ checkingPayments ? 'Vérification...' : 'Vérifier Paiements en Attente' }}
          </button>
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
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Commande</th>
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
                  <div class="font-medium">#{{ payment.order?.reference || '-' }}</div>
                  <div class="text-gray-500">{{ payment.order?.event?.title || '-' }}</div>
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
                <button v-if="payment.status === 'success'" @click="processRefund(payment)" 
                        class="text-red-600 hover:text-red-900">
                  Rembourser
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
      <div class="bg-white rounded-lg p-6 w-full max-w-4xl max-h-screen overflow-y-auto">
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
          <div class="grid grid-cols-2 gap-6">
            <div class="space-y-4">
              <div>
                <h4 class="text-md font-bold mb-3">Informations Paiement</h4>
                <div class="space-y-2">
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
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" 
                          :class="getGatewayBadgeClass(selectedPayment.gateway)">
                      {{ getGatewayName(selectedPayment.gateway) }}
                    </span>
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Statut</label>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" 
                          :class="getStatusBadgeClass(selectedPayment.status)">
                      {{ getStatusName(selectedPayment.status) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="space-y-4">
              <div>
                <h4 class="text-md font-bold mb-3">Horodatage</h4>
                <div class="space-y-2">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Créé le</label>
                    <p class="text-sm text-gray-900">{{ formatDateTime(selectedPayment.created_at) }}</p>
                  </div>
                  
                  <div v-if="selectedPayment.processed_at">
                    <label class="block text-sm font-medium text-gray-700">Traité le</label>
                    <p class="text-sm text-gray-900">{{ formatDateTime(selectedPayment.processed_at) }}</p>
                  </div>
                  
                  <div v-if="selectedPayment.gateway_transaction_id">
                    <label class="block text-sm font-medium text-gray-700">ID Transaction Gateway</label>
                    <p class="text-sm font-mono text-gray-900">{{ selectedPayment.gateway_transaction_id }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Order Info -->
          <div v-if="selectedPayment.order" class="border-t pt-6">
            <h4 class="text-md font-bold mb-3">Informations de la Commande</h4>
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
                <p class="text-sm text-gray-900">{{ selectedPayment.order.customer_name }}</p>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <p class="text-sm text-gray-900">{{ selectedPayment.order.customer_email }}</p>
              </div>
            </div>
          </div>

          <!-- Gateway Response -->
          <div v-if="selectedPayment.gateway_response" class="border-t pt-6">
            <h4 class="text-md font-bold mb-3">Réponse Gateway</h4>
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
            <button v-if="selectedPayment.status === 'success'" @click="processRefund(selectedPayment)" 
                    class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
              Rembourser
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
import { ref, reactive, onMounted, computed } from 'vue'

export default {
  name: 'PaymentTracking',
  setup() {
    // État réactif
    const loading = ref(false)
    const checkingPayments = ref(false)
    const showDetailsModal = ref(false)
    const selectedPayment = ref(null)
    
    const payments = ref([])
    const organizers = ref([])
    const pagination = ref(null)
    
    const stats = reactive({
      total_payments: 0,
      successful_payments: 0,
      pending_payments: 0,
      failed_payments: 0,
      total_volume: 0
    })
    
    const gatewayStats = ref([])
    const dailyStats = ref([])
    
    const filters = reactive({
      search: '',
      status: '',
      gateway: '',
      organizer_id: '',
      date_from: '',
      date_to: '',
      page: 1
    })

    let searchTimeout = null

    // Computed
    const maxDailyVolume = computed(() => {
      if (dailyStats.value.length === 0) return 1
      return Math.max(...dailyStats.value.map(d => d.volume))
    })

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
        
        const response = await fetch(`/api/v1/admin/payments?${queryParams}`, {
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
          
          // Mettre à jour les stats
          Object.assign(stats, data.data.stats)
          gatewayStats.value = data.data.gateway_stats || []
          dailyStats.value = data.data.daily_stats || []
        }
      } catch (error) {
        console.error('Erreur chargement paiements:', error)
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
        search: '',
        status: '',
        gateway: '',
        organizer_id: '',
        date_from: '',
        date_to: '',
        page: 1
      })
      loadPayments()
    }

    const refreshPayments = () => {
      loadPayments()
    }

    const viewPaymentDetails = async (payment) => {
      try {
        const response = await fetch(`/api/v1/admin/payments/${payment.id}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        const data = await response.json()
        if (data.success) {
          selectedPayment.value = data.data.payment
          showDetailsModal.value = true
        }
      } catch (error) {
        console.error('Erreur chargement détails paiement:', error)
      }
    }

    const checkPaymentStatus = async (payment) => {
      try {
        const response = await fetch(`/api/v1/admin/payments/${payment.id}/status`, {
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
          if (showDetailsModal.value) {
            selectedPayment.value.status = data.data.status
          }
        } else {
          alert(data.message || 'Erreur lors de la vérification')
        }
      } catch (error) {
        console.error('Erreur vérification statut:', error)
        alert('Erreur technique')
      }
    }

    const checkPendingPayments = async () => {
      if (!confirm('Vérifier tous les paiements en attente ? Cette opération peut prendre du temps.')) {
        return
      }

      checkingPayments.value = true
      try {
        const response = await fetch('/api/v1/admin/payments/check-pending', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        const data = await response.json()
        if (data.success) {
          alert(`Vérification terminée. ${data.data.updated_count} paiement(s) mis à jour.`)
          loadPayments()
        } else {
          alert(data.message || 'Erreur lors de la vérification')
        }
      } catch (error) {
        console.error('Erreur vérification paiements en attente:', error)
        alert('Erreur technique')
      } finally {
        checkingPayments.value = false
      }
    }

    const processRefund = async (payment) => {
      const reason = prompt('Raison du remboursement:')
      if (!reason) return

      if (!confirm(`Êtes-vous sûr de vouloir rembourser ce paiement de ${formatAmount(payment.amount)} XAF ?`)) {
        return
      }

      try {
        const response = await fetch(`/api/v1/admin/payments/${payment.id}/refund`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ reason })
        })
        
        const data = await response.json()
        if (data.success) {
          alert('Remboursement initié avec succès')
          loadPayments()
        } else {
          alert(data.message || 'Erreur lors du remboursement')
        }
      } catch (error) {
        console.error('Erreur remboursement:', error)
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
        
        const response = await fetch(`/api/v1/admin/payments/export?${queryParams}`, {
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
        cancelled: 'Annulé',
        refunded: 'Remboursé'
      }
      return names[status] || status
    }

    const getStatusBadgeClass = (status) => {
      const classes = {
        success: 'bg-green-100 text-green-800',
        pending: 'bg-yellow-100 text-yellow-800',
        failed: 'bg-red-100 text-red-800',
        cancelled: 'bg-gray-100 text-gray-800',
        refunded: 'bg-purple-100 text-purple-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    }

    // Lifecycle
    onMounted(() => {
      loadPayments()
      loadOrganizers()
    })

    return {
      // État
      loading,
      checkingPayments,
      showDetailsModal,
      selectedPayment,
      payments,
      organizers,
      pagination,
      stats,
      gatewayStats,
      dailyStats,
      filters,
      maxDailyVolume,
      
      // Méthodes
      loadPayments,
      loadOrganizers,
      debouncedSearch,
      changePage,
      resetFilters,
      refreshPayments,
      viewPaymentDetails,
      checkPaymentStatus,
      checkPendingPayments,
      processRefund,
      exportPayments,
      
      // Utilitaires
      formatAmount,
      formatDate,
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
.payment-tracking {
  font-family: 'Inter', sans-serif;
}
</style>