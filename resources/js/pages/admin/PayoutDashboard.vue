<template>
  <div class="payout-dashboard p-6 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">Gestion des Payouts</h1>
      <p class="text-gray-600">Administration des paiements automatiques et manuels</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="p-2 bg-blue-100 rounded-lg">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm text-gray-600">Total Payouts</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.total_payouts || 0 }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="p-2 bg-green-100 rounded-lg">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm text-gray-600">Réussis</p>
            <p class="text-2xl font-bold text-green-600">{{ stats.successful_payouts || 0 }}</p>
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
            <p class="text-sm text-gray-600">En cours</p>
            <p class="text-2xl font-bold text-yellow-600">{{ stats.pending_payouts || 0 }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="p-2 bg-purple-100 rounded-lg">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm text-gray-600">Automatiques</p>
            <p class="text-2xl font-bold text-purple-600">{{ stats.automatic_payouts || 0 }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Actions rapides -->
    <div class="flex flex-wrap gap-4 mb-8">
      <button 
        @click="openCreatePayoutModal"
        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
      >
        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        Nouveau Payout Manuel
      </button>
      
      <button 
        @click="checkAllPending"
        :disabled="checkingAll"
        class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50"
      >
        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
        </svg>
        {{ checkingAll ? 'Vérification...' : 'Vérifier tous les payouts' }}
      </button>
      
      <button 
        @click="loadShapBalance"
        :disabled="loadingShapBalance"
        class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors disabled:opacity-50"
      >
        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
        </svg>
        {{ loadingShapBalance ? 'Chargement...' : 'Solde SHAP' }}
      </button>
    </div>

    <!-- Solde SHAP -->
    <div v-if="shapBalance.length > 0" class="mb-8">
      <h2 class="text-xl font-bold mb-4">Solde SHAP par Opérateur</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div v-for="balance in shapBalance" :key="balance.payment_system_name" 
             class="bg-white rounded-lg shadow p-4">
          <h3 class="font-semibold text-gray-900">{{ balance.payment_system_displayed_name }}</h3>
          <p class="text-2xl font-bold text-green-600">{{ formatAmount(balance.amount) }} XAF</p>
          <p class="text-sm text-gray-500">Dernière MAJ: {{ balance.last_updated }}</p>
        </div>
      </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
      <h2 class="text-xl font-bold mb-4">Filtres</h2>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
          <select v-model="filters.status" @change="loadPayouts" class="w-full border rounded-lg px-3 py-2">
            <option value="">Tous</option>
            <option value="pending">En attente</option>
            <option value="processing">En cours</option>
            <option value="success">Réussi</option>
            <option value="failed">Échoué</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Gateway</label>
          <select v-model="filters.gateway" @change="loadPayouts" class="w-full border rounded-lg px-3 py-2">
            <option value="">Tous</option>
            <option value="airtelmoney">Airtel Money</option>
            <option value="moovmoney">Moov Money</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
          <select v-model="filters.is_automatic" @change="loadPayouts" class="w-full border rounded-lg px-3 py-2">
            <option value="">Tous</option>
            <option value="true">Automatique</option>
            <option value="false">Manuel</option>
          </select>
        </div>
        
        <div class="flex items-end">
          <button @click="resetFilters" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
            Réinitialiser
          </button>
        </div>
      </div>
    </div>

    <!-- Liste des payouts -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-6 border-b">
        <h2 class="text-xl font-bold">Liste des Payouts</h2>
      </div>
      
      <div v-if="loading" class="p-8 text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-4 text-gray-600">Chargement...</p>
      </div>
      
      <div v-else-if="payouts.length === 0" class="p-8 text-center text-gray-500">
        Aucun payout trouvé
      </div>
      
      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Organisateur</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gateway</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="payout in payouts" :key="payout.id">
              <td class="px-6 py-4 text-sm text-gray-900">#{{ payout.id }}</td>
              <td class="px-6 py-4 text-sm text-gray-900">{{ payout.organizer?.name }}</td>
              <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ formatAmount(payout.amount) }} XAF</td>
              <td class="px-6 py-4 text-sm text-gray-900">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" 
                      :class="getGatewayBadgeClass(payout.gateway)">
                  {{ getGatewayName(payout.gateway) }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" 
                      :class="getStatusBadgeClass(payout.status)">
                  {{ getStatusName(payout.status) }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-900">
                <span class="inline-flex px-2 py-1 text-xs rounded-full" 
                      :class="payout.is_automatic ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'">
                  {{ payout.is_automatic ? 'Auto' : 'Manuel' }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(payout.created_at) }}</td>
              <td class="px-6 py-4 text-sm space-x-2">
                <button @click="checkPayoutStatus(payout)" 
                        :disabled="payout.checking"
                        class="text-blue-600 hover:text-blue-900 disabled:opacity-50">
                  {{ payout.checking ? 'Vérif...' : 'Vérifier' }}
                </button>
                <button @click="viewPayoutDetails(payout)" class="text-green-600 hover:text-green-900">
                  Détails
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Création Payout Manuel -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-bold mb-4">Nouveau Payout Manuel</h3>
        
        <form @submit.prevent="createPayout">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Organisateur</label>
              <select v-model="newPayout.organizer_id" required class="w-full border rounded-lg px-3 py-2">
                <option value="">Sélectionner un organisateur</option>
                <option v-for="organizer in organizers" :key="organizer.id" :value="organizer.id">
                  {{ organizer.name }}
                </option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Gateway</label>
              <select v-model="newPayout.gateway" required class="w-full border rounded-lg px-3 py-2">
                <option value="">Sélectionner</option>
                <option value="airtelmoney">Airtel Money</option>
                <option value="moovmoney">Moov Money</option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Montant (XAF)</label>
              <input v-model.number="newPayout.amount" type="number" min="100" required 
                     class="w-full border rounded-lg px-3 py-2">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Numéro de téléphone</label>
              <input v-model="newPayout.phone_number" type="tel" maxlength="9" pattern="[0-9]{9}" required 
                     placeholder="074123456" class="w-full border rounded-lg px-3 py-2">
            </div>
          </div>
          
          <div class="flex justify-end space-x-3 mt-6">
            <button type="button" @click="showCreateModal = false" 
                    class="px-4 py-2 text-gray-600 hover:text-gray-800">
              Annuler
            </button>
            <button type="submit" :disabled="creatingPayout"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50">
              {{ creatingPayout ? 'Création...' : 'Créer le Payout' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue'

export default {
  name: 'PayoutDashboard',
  setup() {
    // État réactif
    const loading = ref(false)
    const checkingAll = ref(false)
    const loadingShapBalance = ref(false)
    const showCreateModal = ref(false)
    const creatingPayout = ref(false)
    
    const payouts = ref([])
    const organizers = ref([])
    const shapBalance = ref([])
    const stats = reactive({
      total_payouts: 0,
      successful_payouts: 0,
      pending_payouts: 0,
      automatic_payouts: 0,
    })
    
    const filters = reactive({
      status: '',
      gateway: '',
      is_automatic: '',
    })
    
    const newPayout = reactive({
      organizer_id: '',
      gateway: '',
      amount: '',
      phone_number: '',
    })

    // Méthodes
    const loadPayouts = async () => {
      loading.value = true
      try {
        const queryParams = new URLSearchParams()
        if (filters.status) queryParams.append('status', filters.status)
        if (filters.gateway) queryParams.append('gateway', filters.gateway)
        if (filters.is_automatic) queryParams.append('is_automatic', filters.is_automatic)
        
        const response = await fetch(`/api/v1/admin/payouts?${queryParams}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        const data = await response.json()
        if (data.success) {
          payouts.value = data.data.payouts
        }
      } catch (error) {
        console.error('Erreur chargement payouts:', error)
      } finally {
        loading.value = false
      }
    }

    const loadStats = async () => {
      try {
        const response = await fetch('/api/v1/admin/payouts/stats', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        const data = await response.json()
        if (data.success) {
          Object.assign(stats, data.data.stats)
        }
      } catch (error) {
        console.error('Erreur chargement stats:', error)
      }
    }

    const loadShapBalance = async () => {
      loadingShapBalance.value = true
      try {
        const response = await fetch('/api/v1/admin/payouts/shap-balance', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        const data = await response.json()
        if (data.success) {
          shapBalance.value = data.data.balances
        }
      } catch (error) {
        console.error('Erreur chargement solde SHAP:', error)
      } finally {
        loadingShapBalance.value = false
      }
    }

    const checkAllPending = async () => {
      checkingAll.value = true
      try {
        const response = await fetch('/api/v1/admin/payouts/check-all-pending', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        const data = await response.json()
        if (data.success) {
          alert(`Vérification terminée: ${data.data.results.length} payouts vérifiés`)
          loadPayouts() // Recharger la liste
        }
      } catch (error) {
        console.error('Erreur vérification batch:', error)
      } finally {
        checkingAll.value = false
      }
    }

    const checkPayoutStatus = async (payout) => {
      payout.checking = true
      try {
        const response = await fetch(`/api/v1/admin/payouts/${payout.id}/check-status`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        const data = await response.json()
        if (data.success) {
          alert(`Statut: ${data.data.current_status}`)
          loadPayouts() // Recharger la liste
        }
      } catch (error) {
        console.error('Erreur vérification payout:', error)
      } finally {
        payout.checking = false
      }
    }

    const openCreatePayoutModal = () => {
      showCreateModal.value = true
      loadOrganizers()
    }

    const loadOrganizers = async () => {
      try {
        // Cette route devrait exister ou être créée
        const response = await fetch('/api/v1/admin/organizers', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        const data = await response.json()
        if (data.success) {
          organizers.value = data.data.organizers
        }
      } catch (error) {
        console.error('Erreur chargement organisateurs:', error)
      }
    }

    const createPayout = async () => {
      creatingPayout.value = true
      try {
        const response = await fetch('/api/v1/admin/payouts', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(newPayout)
        })
        
        const data = await response.json()
        if (data.success) {
          alert('Payout créé avec succès')
          showCreateModal.value = false
          Object.assign(newPayout, {
            organizer_id: '',
            gateway: '',
            amount: '',
            phone_number: '',
          })
          loadPayouts()
          loadStats()
        } else {
          alert(data.message || 'Erreur lors de la création')
        }
      } catch (error) {
        console.error('Erreur création payout:', error)
        alert('Erreur technique')
      } finally {
        creatingPayout.value = false
      }
    }

    const resetFilters = () => {
      Object.assign(filters, {
        status: '',
        gateway: '',
        is_automatic: '',
      })
      loadPayouts()
    }

    const viewPayoutDetails = (payout) => {
      alert(`Détails du payout #${payout.id}\nRéférence: ${payout.reference}\nMontant: ${formatAmount(payout.amount)} XAF`)
    }

    // Utilitaires
    const formatAmount = (amount) => {
      return new Intl.NumberFormat('fr-FR').format(amount)
    }

    const formatDate = (date) => {
      return new Date(date).toLocaleDateString('fr-FR', {
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
        processing: 'En cours',
        success: 'Réussi',
        failed: 'Échoué',
        cancelled: 'Annulé'
      }
      return names[status] || status
    }

    const getStatusBadgeClass = (status) => {
      const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        processing: 'bg-blue-100 text-blue-800',
        success: 'bg-green-100 text-green-800',
        failed: 'bg-red-100 text-red-800',
        cancelled: 'bg-gray-100 text-gray-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
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

    // Lifecycle
    onMounted(() => {
      loadPayouts()
      loadStats()
    })

    return {
      // État
      loading,
      checkingAll,
      loadingShapBalance,
      showCreateModal,
      creatingPayout,
      payouts,
      organizers,
      shapBalance,
      stats,
      filters,
      newPayout,
      
      // Méthodes
      loadPayouts,
      loadStats,
      loadShapBalance,
      checkAllPending,
      checkPayoutStatus,
      openCreatePayoutModal,
      createPayout,
      resetFilters,
      viewPayoutDetails,
      
      // Utilitaires
      formatAmount,
      formatDate,
      getStatusName,
      getStatusBadgeClass,
      getGatewayName,
      getGatewayBadgeClass,
    }
  }
}
</script>

<style scoped>
.payout-dashboard {
  font-family: 'Inter', sans-serif;
}
</style>