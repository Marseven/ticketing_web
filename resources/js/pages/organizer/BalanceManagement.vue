<template>
  <div class="balance-management min-h-screen" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%)">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-primea-blue font-primea mb-2">Mon Solde</h1>
          <p class="text-gray-600 font-primea">Consultez votre solde et effectuez des demandes de versement</p>
        </div>
        <button @click="$router.push('/organizer/payouts/history')" 
                class="bg-primea-blue text-white px-4 py-2 rounded-primea hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200 font-primea">
          <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
          </svg>
          Historique Versements
        </button>
      </div>
    </div>

    <!-- Loading state -->
    <div v-if="loading" class="flex justify-center items-center h-64">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <div v-else>
      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-2 bg-green-100 rounded-lg">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm text-gray-600">Solde Total</p>
              <p class="text-2xl font-bold text-green-600">{{ formatAmount(totalBalance) }} XAF</p>
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
              <p class="text-sm text-gray-600">Payouts en Cours</p>
              <p class="text-2xl font-bold text-yellow-600">{{ stats.pending_payouts || 0 }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-2 bg-blue-100 rounded-lg">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm text-gray-600">Total Payouts</p>
              <p class="text-2xl font-bold text-blue-600">{{ formatAmount(stats.total_payouts || 0) }} XAF</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Balance Cards -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <div v-for="balance in balances" :key="balance.gateway" 
             class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center">
              <div class="p-2 rounded-lg mr-4" :class="getGatewayBadgeClass(balance.gateway)">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
              </div>
              <div>
                <h3 class="text-lg font-semibold text-gray-900">{{ getGatewayName(balance.gateway) }}</h3>
                <p class="text-sm text-gray-600">Solde disponible</p>
              </div>
            </div>
            <div class="text-right">
              <p class="text-2xl font-bold text-green-600">{{ formatAmount(balance.balance) }} XAF</p>
            </div>
          </div>

          <!-- Auto Payout Status -->
          <div class="mb-4 p-3 bg-gray-50 rounded-primea">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-medium text-gray-700 font-primea">Versement Automatique</span>
              <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                    :class="balance.auto_payout_enabled ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                {{ balance.auto_payout_enabled ? 'Activé' : 'Désactivé' }}
              </span>
            </div>
            
            <div v-if="balance.auto_payout_enabled" class="text-sm text-gray-600 font-primea">
              <p>Seuil: {{ formatAmount(balance.auto_payout_threshold) }} XAF</p>
              <p v-if="balance.payout_phone_number">Téléphone: {{ balance.payout_phone_number }}</p>
              <p class="text-xs text-gray-500 mt-1">Configuration gérée par l'administrateur</p>
            </div>
            <div v-else class="text-sm text-gray-600 font-primea">
              <p class="text-xs text-gray-500">Pour activer le versement automatique, contactez l'administrateur</p>
            </div>
          </div>

          <!-- Actions -->
          <div class="space-y-3">
            <button @click="requestPayout(balance)" 
                    :disabled="balance.balance < 1000"
                    class="w-full bg-primea-blue text-white py-2 px-4 rounded-primea hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200 font-primea disabled:opacity-50 disabled:cursor-not-allowed">
              <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
              </svg>
              {{ balance.balance < 1000 ? 'Solde insuffisant (min 1000 XAF)' : 'Demander un Versement' }}
            </button>
          </div>

          <!-- Recent Transactions for this Gateway -->
          <div class="mt-6 pt-6 border-t">
            <h4 class="text-sm font-medium text-gray-700 mb-3">Dernières transactions</h4>
            <div v-if="getRecentPayouts(balance.gateway).length === 0" class="text-sm text-gray-500">
              Aucune transaction récente
            </div>
            <div v-else class="space-y-2">
              <div v-for="payout in getRecentPayouts(balance.gateway)" :key="payout.id" 
                   class="flex justify-between items-center text-sm">
                <div>
                  <span class="font-medium">{{ formatAmount(payout.amount) }} XAF</span>
                  <span class="text-gray-500 ml-2">{{ formatDate(payout.created_at) }}</span>
                </div>
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" 
                      :class="getPayoutStatusClass(payout.status)">
                  {{ getPayoutStatusName(payout.status) }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Payouts -->
      <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b">
          <div class="flex justify-between items-center">
            <h2 class="text-xl font-bold">Payouts Récents</h2>
            <button @click="$router.push('/organizer/payouts/history')" 
                    class="text-blue-600 hover:text-blue-800 text-sm">Voir tout</button>
          </div>
        </div>
        
        <div v-if="recentPayouts.length === 0" class="p-8 text-center text-gray-500">
          Aucun payout récent
        </div>
        
        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Référence</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gateway</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="payout in recentPayouts" :key="payout.id">
                <td class="px-6 py-4 text-sm font-mono text-gray-900">#{{ payout.reference }}</td>
                <td class="px-6 py-4 text-sm">
                  <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" 
                        :class="getGatewayBadgeClass(payout.gateway)">
                    {{ getGatewayName(payout.gateway) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ formatAmount(payout.amount) }} XAF</td>
                <td class="px-6 py-4 text-sm">
                  <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" 
                        :class="getPayoutStatusClass(payout.status)">
                    {{ getPayoutStatusName(payout.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm">
                  <span class="inline-flex px-2 py-1 text-xs rounded-full" 
                        :class="payout.is_automatic ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'">
                    {{ payout.is_automatic ? 'Auto' : 'Manuel' }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">{{ formatDateTime(payout.created_at) }}</td>
                <td class="px-6 py-4 text-sm">
                  <button @click="viewPayoutDetails(payout)" class="text-blue-600 hover:text-blue-900">
                    Détails
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Payout Request Modal -->
    <div v-if="showPayoutModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-primea p-6 w-full max-w-md">
        <h3 class="text-lg font-bold text-primea-blue font-primea mb-4">Demande de Versement</h3>
        
        <form @submit.prevent="submitPayoutRequest">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Gateway</label>
              <input :value="getGatewayName(payoutForm.gateway)" disabled 
                     class="w-full border rounded-lg px-3 py-2 bg-gray-100">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Solde Disponible</label>
              <input :value="formatAmount(payoutForm.available_balance) + ' XAF'" disabled 
                     class="w-full border rounded-lg px-3 py-2 bg-gray-100">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Montant à retirer (XAF)</label>
              <input v-model.number="payoutForm.amount" type="number" 
                     :min="1000" :max="payoutForm.available_balance" required 
                     class="w-full border rounded-lg px-3 py-2">
              <p class="text-xs text-gray-500 mt-1">Montant minimum: 1,000 XAF</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Numéro de téléphone</label>
              <input v-model="payoutForm.phone_number" type="tel" maxlength="9" pattern="[0-9]{9}" required 
                     placeholder="074123456" class="w-full border rounded-lg px-3 py-2">
              <p class="text-xs text-gray-500 mt-1">Numéro sans indicatif pays</p>
            </div>
          </div>
          
          <div class="flex justify-end space-x-3 mt-6">
            <button type="button" @click="showPayoutModal = false" 
                    class="px-4 py-2 text-gray-600 hover:text-gray-800">
              Annuler
            </button>
            <button type="submit" :disabled="requestingPayout"
                    class="bg-primea-blue text-white px-4 py-2 rounded-primea hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200 font-primea disabled:opacity-50">
              {{ requestingPayout ? 'Demande en cours...' : 'Demander le Versement' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { organizerService } from '../../services/api'
import Swal from 'sweetalert2'
// État réactif
const loading = ref(false)
const requestingPayout = ref(false)
const showPayoutModal = ref(false)
    
const balances = ref([])
const recentPayouts = ref([])

const stats = reactive({
  pending_payouts: 0,
  total_payouts: 0
})

const payoutForm = reactive({
  gateway: '',
  available_balance: 0,
  amount: '',
  phone_number: '',
})

// Computed
const totalBalance = computed(() => {
  return balances.value.reduce((total, balance) => total + (parseFloat(balance.balance) || 0), 0)
})

// Méthodes
const loadBalances = async () => {
  loading.value = true
  try {
    const response = await organizerService.getBalance()
    console.log('Balance API Response:', response.data)
    
    if (response.data.success) {
      balances.value = response.data.data.balances || []
      console.log('Balances loaded:', balances.value)
      
      // Si aucun solde n'est retourné, créer des soldes par défaut
      if (balances.value.length === 0) {
        balances.value = [
          {
            gateway: 'airtelmoney',
            balance: 0,
            auto_payout_enabled: false,
            auto_payout_threshold: 10000,
            payout_phone_number: null
          },
          {
            gateway: 'moovmoney',
            balance: 0,
            auto_payout_enabled: false,
            auto_payout_threshold: 10000,
            payout_phone_number: null
          }
        ]
        console.log('Default balances created:', balances.value)
      }
    } else {
      console.error('Balance API Error:', response.data.message)
    }
  } catch (error) {
    console.error('Erreur chargement soldes:', error)
    console.error('Error details:', error.response?.data)
    
    // Créer des soldes par défaut en cas d'erreur
    balances.value = [
      {
        gateway: 'airtelmoney',
        balance: 0,
        auto_payout_enabled: false,
        auto_payout_threshold: 10000,
        payout_phone_number: null
      },
      {
        gateway: 'moovmoney',
        balance: 0,
        auto_payout_enabled: false,
        auto_payout_threshold: 10000,
        payout_phone_number: null
      }
    ]
    
    Swal.fire({
      title: 'Erreur',
      text: 'Impossible de charger les soldes. Affichage des valeurs par défaut.',
      icon: 'warning',
      confirmButtonColor: '#272d63'
    })
  } finally {
    loading.value = false
  }
}

const loadRecentPayouts = async () => {
  try {
    const response = await organizerService.getPaymentHistory()
    console.log('Payment History API Response:', response.data)
    
    if (response.data.success) {
      recentPayouts.value = response.data.data.payouts?.data || response.data.data.payouts || []
      console.log('Recent payouts loaded:', recentPayouts.value)
      stats.pending_payouts = recentPayouts.value.filter(p => ['pending', 'processing'].includes(p.status)).length
      stats.total_payouts = recentPayouts.value.reduce((sum, p) => sum + (p.amount || 0), 0)
      console.log('Stats calculated:', stats)
    } else {
      console.error('Payment History API Error:', response.data.message)
    }
  } catch (error) {
    console.error('Erreur chargement payouts récents:', error)
    console.error('Error details:', error.response?.data)
  }
}

const requestPayout = (balance) => {
  Object.assign(payoutForm, {
    gateway: balance.gateway,
    available_balance: balance.balance,
    amount: '',
    phone_number: balance.payout_phone_number || '',
  })
  showPayoutModal.value = true
}

const submitPayoutRequest = async () => {
  requestingPayout.value = true
  try {
    const response = await organizerService.requestPayout(payoutForm)
    if (response.data.success) {
      Swal.fire({
        title: 'Succès !',
        text: 'Demande de versement envoyée avec succès',
        icon: 'success',
        confirmButtonColor: '#272d63'
      })
      showPayoutModal.value = false
      loadBalances()
      loadRecentPayouts()
    } else {
      Swal.fire({
        title: 'Erreur',
        text: response.data.message || 'Erreur lors de la demande',
        icon: 'error',
        confirmButtonColor: '#272d63'
      })
    }
  } catch (error) {
    console.error('Erreur demande payout:', error)
    Swal.fire({
      title: 'Erreur technique',
      text: 'Une erreur est survenue lors de la demande',
      icon: 'error',
      confirmButtonColor: '#272d63'
    })
  } finally {
    requestingPayout.value = false
  }
}


const getRecentPayouts = (gateway) => {
  return recentPayouts.value.filter(p => p.gateway === gateway).slice(0, 3)
}

const viewPayoutDetails = (payout) => {
  Swal.fire({
    title: `Versement #${payout.reference}`,
    html: `
      <div class="text-left">
        <p><strong>Montant:</strong> ${formatAmount(payout.amount)} XAF</p>
        <p><strong>Statut:</strong> ${getPayoutStatusName(payout.status)}</p>
        <p><strong>Gateway:</strong> ${getGatewayName(payout.gateway)}</p>
        <p><strong>Date:</strong> ${formatDateTime(payout.created_at)}</p>
      </div>
    `,
    icon: 'info',
    confirmButtonColor: '#272d63'
  })
}

// Utilitaires
const formatAmount = (amount) => {
  return new Intl.NumberFormat('fr-FR').format(amount || 0)
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
    moovmoney: 'Moov Money'
  }
  return names[gateway] || gateway
}

const getGatewayBadgeClass = (gateway) => {
  const classes = {
    airtelmoney: 'bg-red-100 text-red-800',
    moovmoney: 'bg-orange-100 text-orange-800'
  }
  return classes[gateway] || 'bg-gray-100 text-gray-800'
}

const getPayoutStatusName = (status) => {
  const names = {
    pending: 'En attente',
    processing: 'En cours',
    success: 'Réussi',
    failed: 'Échoué',
    cancelled: 'Annulé'
  }
  return names[status] || status
}

const getPayoutStatusClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    processing: 'bg-blue-100 text-blue-800',
    success: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
    cancelled: 'bg-gray-100 text-gray-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

// Lifecycle
onMounted(() => {
  loadBalances()
  loadRecentPayouts()
})
</script>

<style scoped>
.balance-management {
  font-family: 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Couleurs Primea */
.text-primea-blue {
  color: #272d63;
}

.text-primea-yellow {
  color: #fab511;
}

.bg-primea-blue {
  background-color: #272d63;
}

.bg-primea-yellow {
  background-color: #fab511;
}

.hover\:bg-primea-blue:hover {
  background-color: #272d63;
}

.hover\:bg-primea-yellow:hover {
  background-color: #fab511;
}

.hover\:text-primea-blue:hover {
  color: #272d63;
}

.hover\:text-primea-yellow:hover {
  color: #fab511;
}

/* Coins arrondis Primea */
.rounded-primea {
  border-radius: 12px;
}

/* Police Primea */
.font-primea {
  font-family: 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
</style>