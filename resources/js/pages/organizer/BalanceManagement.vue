<template>
  <div class="balance-management p-6 min-h-screen" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%)">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 mb-2">Gestion des Soldes & Payouts</h1>
          <p class="text-gray-600">Consultez vos soldes et gérez vos demandes de payout</p>
        </div>
        <button @click="$router.push('/organizer/payouts/history')" 
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
          <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
          </svg>
          Historique Payouts
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
          <div class="mb-4 p-3 bg-gray-50 rounded-lg">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-medium text-gray-700">Payout Automatique</span>
              <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                    :class="balance.auto_payout_enabled ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                {{ balance.auto_payout_enabled ? 'Activé' : 'Désactivé' }}
              </span>
            </div>
            
            <div v-if="balance.auto_payout_enabled" class="text-sm text-gray-600">
              <p>Seuil: {{ formatAmount(balance.auto_payout_threshold) }} XAF</p>
              <p v-if="balance.payout_phone_number">Téléphone: {{ balance.payout_phone_number }}</p>
            </div>
          </div>

          <!-- Actions -->
          <div class="space-y-3">
            <button @click="requestPayout(balance)" 
                    :disabled="balance.balance < 1000"
                    class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
              <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
              </svg>
              {{ balance.balance < 1000 ? 'Solde insuffisant (min 1000 XAF)' : 'Demander un Payout' }}
            </button>
            
            <button @click="configureAutoPayout(balance)" 
                    class="w-full bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-700">
              <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
              Configurer Auto-Payout
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
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-bold mb-4">Demande de Payout</h3>
        
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
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50">
              {{ requestingPayout ? 'Demande en cours...' : 'Demander le Payout' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Auto Payout Config Modal -->
    <div v-if="showConfigModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-bold mb-4">Configuration Auto-Payout - {{ getGatewayName(configForm.gateway) }}</h3>
        
        <form @submit.prevent="saveAutoPayoutConfig">
          <div class="space-y-4">
            <div>
              <label class="flex items-center space-x-2">
                <input type="checkbox" v-model="configForm.auto_payout_enabled" 
                       class="rounded border-gray-300">
                <span class="text-sm font-medium text-gray-700">Activer le payout automatique</span>
              </label>
            </div>
            
            <div v-if="configForm.auto_payout_enabled">
              <label class="block text-sm font-medium text-gray-700 mb-2">Seuil de payout (XAF)</label>
              <input v-model.number="configForm.auto_payout_threshold" type="number" min="1000" 
                     class="w-full border rounded-lg px-3 py-2">
              <p class="text-xs text-gray-500 mt-1">Montant minimum pour déclencher un payout automatique</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Numéro de téléphone</label>
              <input v-model="configForm.payout_phone_number" type="tel" maxlength="9" pattern="[0-9]{9}" 
                     placeholder="074123456" class="w-full border rounded-lg px-3 py-2">
              <p class="text-xs text-gray-500 mt-1">Numéro pour recevoir les payouts automatiques</p>
            </div>
          </div>
          
          <div class="flex justify-end space-x-3 mt-6">
            <button type="button" @click="showConfigModal = false" 
                    class="px-4 py-2 text-gray-600 hover:text-gray-800">
              Annuler
            </button>
            <button type="submit" :disabled="savingConfig"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50">
              {{ savingConfig ? 'Enregistrement...' : 'Enregistrer' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted, computed } from 'vue'

export default {
  name: 'BalanceManagement',
  setup() {
    // État réactif
    const loading = ref(false)
    const requestingPayout = ref(false)
    const savingConfig = ref(false)
    const showPayoutModal = ref(false)
    const showConfigModal = ref(false)
    
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

    const configForm = reactive({
      gateway: '',
      auto_payout_enabled: false,
      auto_payout_threshold: 10000,
      payout_phone_number: '',
    })

    // Computed
    const totalBalance = computed(() => {
      return balances.value.reduce((total, balance) => total + balance.balance, 0)
    })

    // Méthodes
    const loadBalances = async () => {
      loading.value = true
      try {
        const response = await fetch('/api/v1/organizer/balances', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        const data = await response.json()
        if (data.success) {
          balances.value = data.data.balances
        }
      } catch (error) {
        console.error('Erreur chargement soldes:', error)
      } finally {
        loading.value = false
      }
    }

    const loadRecentPayouts = async () => {
      try {
        const response = await fetch('/api/v1/organizer/payouts?limit=10', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        const data = await response.json()
        if (data.success) {
          recentPayouts.value = data.data.payouts.data || data.data.payouts
          stats.pending_payouts = recentPayouts.value.filter(p => ['pending', 'processing'].includes(p.status)).length
          stats.total_payouts = recentPayouts.value.reduce((sum, p) => sum + p.amount, 0)
        }
      } catch (error) {
        console.error('Erreur chargement payouts récents:', error)
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
        const response = await fetch('/api/v1/organizer/payouts', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(payoutForm)
        })
        
        const data = await response.json()
        if (data.success) {
          alert('Demande de payout envoyée avec succès')
          showPayoutModal.value = false
          loadBalances()
          loadRecentPayouts()
        } else {
          alert(data.message || 'Erreur lors de la demande')
        }
      } catch (error) {
        console.error('Erreur demande payout:', error)
        alert('Erreur technique')
      } finally {
        requestingPayout.value = false
      }
    }

    const configureAutoPayout = (balance) => {
      Object.assign(configForm, {
        gateway: balance.gateway,
        auto_payout_enabled: balance.auto_payout_enabled,
        auto_payout_threshold: balance.auto_payout_threshold,
        payout_phone_number: balance.payout_phone_number || '',
      })
      showConfigModal.value = true
    }

    const saveAutoPayoutConfig = async () => {
      savingConfig.value = true
      try {
        const response = await fetch(`/api/v1/organizer/auto-payout-config`, {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(configForm)
        })
        
        const data = await response.json()
        if (data.success) {
          alert('Configuration mise à jour avec succès')
          showConfigModal.value = false
          loadBalances()
        } else {
          alert(data.message || 'Erreur lors de la mise à jour')
        }
      } catch (error) {
        console.error('Erreur sauvegarde config:', error)
        alert('Erreur technique')
      } finally {
        savingConfig.value = false
      }
    }

    const getRecentPayouts = (gateway) => {
      return recentPayouts.value.filter(p => p.gateway === gateway).slice(0, 3)
    }

    const viewPayoutDetails = (payout) => {
      alert(`Détails du payout #${payout.reference}\nMontant: ${formatAmount(payout.amount)} XAF\nStatut: ${getPayoutStatusName(payout.status)}`)
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

    return {
      // État
      loading,
      requestingPayout,
      savingConfig,
      showPayoutModal,
      showConfigModal,
      balances,
      recentPayouts,
      stats,
      payoutForm,
      configForm,
      totalBalance,
      
      // Méthodes
      loadBalances,
      loadRecentPayouts,
      requestPayout,
      submitPayoutRequest,
      configureAutoPayout,
      saveAutoPayoutConfig,
      getRecentPayouts,
      viewPayoutDetails,
      
      // Utilitaires
      formatAmount,
      formatDate,
      formatDateTime,
      getGatewayName,
      getGatewayBadgeClass,
      getPayoutStatusName,
      getPayoutStatusClass,
    }
  }
}
</script>

<style scoped>
.balance-management {
  font-family: 'Inter', sans-serif;
}
</style>