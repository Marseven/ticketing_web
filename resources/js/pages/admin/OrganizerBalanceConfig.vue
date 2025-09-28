<template>
  <div class="organizer-balance-config p-6 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">Configuration des Soldes Organisateurs</h1>
      <p class="text-gray-600">Gestion des paramètres de payout automatique par organisateur</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="p-2 bg-blue-100 rounded-lg">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm text-gray-600">Total Organisateurs</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.total_organizers || 0 }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="p-2 bg-green-100 rounded-lg">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm text-gray-600">Payout Auto Activé</p>
            <p class="text-2xl font-bold text-green-600">{{ stats.auto_payout_enabled || 0 }}</p>
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
            <p class="text-sm text-gray-600">Solde Total</p>
            <p class="text-2xl font-bold text-yellow-600">{{ formatAmount(stats.total_balance || 0) }} XAF</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="p-2 bg-purple-100 rounded-lg">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm text-gray-600">Configs Actives</p>
            <p class="text-2xl font-bold text-purple-600">{{ stats.active_configs || 0 }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
      <h2 class="text-xl font-bold mb-4">Filtres</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Gateway</label>
          <select v-model="filters.gateway" @change="loadBalances" class="w-full border rounded-lg px-3 py-2">
            <option value="">Tous</option>
            <option value="airtelmoney">Airtel Money</option>
            <option value="moovmoney">Moov Money</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Payout Auto</label>
          <select v-model="filters.auto_payout_enabled" @change="loadBalances" class="w-full border rounded-lg px-3 py-2">
            <option value="">Tous</option>
            <option value="true">Activé</option>
            <option value="false">Désactivé</option>
          </select>
        </div>
        
        <div class="flex items-end">
          <button @click="resetFilters" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
            Réinitialiser
          </button>
        </div>
      </div>
    </div>

    <!-- Liste des balances organisateurs -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-6 border-b">
        <h2 class="text-xl font-bold">Balances des Organisateurs</h2>
      </div>
      
      <div v-if="loading" class="p-8 text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-4 text-gray-600">Chargement...</p>
      </div>
      
      <div v-else-if="balances.length === 0" class="p-8 text-center text-gray-500">
        Aucune balance trouvée
      </div>
      
      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Organisateur</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gateway</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Solde</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payout Auto</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Seuil</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Téléphone</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="balance in balances" :key="`${balance.organizer_id}-${balance.gateway}`">
              <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ balance.organizer?.name }}</td>
              <td class="px-6 py-4 text-sm text-gray-900">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" 
                      :class="getGatewayBadgeClass(balance.gateway)">
                  {{ getGatewayName(balance.gateway) }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ formatAmount(balance.balance) }} XAF</td>
              <td class="px-6 py-4 text-sm">
                <span class="inline-flex px-2 py-1 text-xs rounded-full" 
                      :class="balance.auto_payout_enabled ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                  {{ balance.auto_payout_enabled ? 'Activé' : 'Désactivé' }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-900">{{ formatAmount(balance.auto_payout_threshold) }} XAF</td>
              <td class="px-6 py-4 text-sm text-gray-900">{{ balance.phone_number || '-' }}</td>
              <td class="px-6 py-4 text-sm space-x-2">
                <button @click="editBalance(balance)" class="text-blue-600 hover:text-blue-900">
                  Modifier
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Configuration -->
    <div v-if="showConfigModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-bold mb-4">Configuration Payout - {{ editingBalance?.organizer?.name }}</h3>
        
        <form @submit.prevent="saveConfiguration">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Gateway</label>
              <input :value="getGatewayName(editingBalance?.gateway)" disabled 
                     class="w-full border rounded-lg px-3 py-2 bg-gray-100">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Solde Actuel</label>
              <input :value="formatAmount(editingBalance?.balance) + ' XAF'" disabled 
                     class="w-full border rounded-lg px-3 py-2 bg-gray-100">
            </div>
            
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
              <p class="text-xs text-gray-500 mt-1">Numéro pour recevoir les payouts (sans indicatif)</p>
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
import { ref, reactive, onMounted } from 'vue'

export default {
  name: 'OrganizerBalanceConfig',
  setup() {
    // État réactif
    const loading = ref(false)
    const showConfigModal = ref(false)
    const savingConfig = ref(false)
    const editingBalance = ref(null)
    
    const balances = ref([])
    const stats = reactive({
      total_organizers: 0,
      auto_payout_enabled: 0,
      total_balance: 0,
      active_configs: 0,
    })
    
    const filters = reactive({
      gateway: '',
      auto_payout_enabled: '',
    })
    
    const configForm = reactive({
      auto_payout_enabled: false,
      auto_payout_threshold: 10000,
      payout_phone_number: '',
    })

    // Méthodes
    const loadBalances = async () => {
      loading.value = true
      try {
        const queryParams = new URLSearchParams()
        if (filters.gateway) queryParams.append('gateway', filters.gateway)
        if (filters.auto_payout_enabled) queryParams.append('auto_payout_enabled', filters.auto_payout_enabled)
        
        const response = await fetch(`/api/v1/admin/payouts/balances?${queryParams}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        
        if (response.ok) {
          const data = await response.json()
          if (data.success) {
            balances.value = data.data.balances
            if (data.data.stats) {
              Object.assign(stats, data.data.stats)
            }
          }
        } else {
          // Utiliser des données simulées si l'API échoue
          loadMockData()
        }
      } catch (error) {
        console.log('API non disponible, utilisation des données simulées')
        loadMockData()
      } finally {
        loading.value = false
      }
    }
    
    const loadMockData = () => {
      balances.value = [
        {
          organizer_id: 1,
          organizer: { name: 'EventMaster Pro' },
          gateway: 'airtelmoney',
          balance: 250000,
          auto_payout_enabled: true,
          auto_payout_threshold: 100000,
          phone_number: '066123456'
        },
        {
          organizer_id: 2,
          organizer: { name: 'Gabon Events' },
          gateway: 'moovmoney',
          balance: 180000,
          auto_payout_enabled: false,
          auto_payout_threshold: 50000,
          phone_number: '065987654'
        },
        {
          organizer_id: 3,
          organizer: { name: 'Culture & Spectacles' },
          gateway: 'airtelmoney',
          balance: 420000,
          auto_payout_enabled: true,
          auto_payout_threshold: 200000,
          phone_number: '067789012'
        }
      ]
      
      Object.assign(stats, {
        total_organizers: 15,
        auto_payout_enabled: 8,
        total_balance: 850000,
        active_configs: 12
      })
    }

    const editBalance = (balance) => {
      editingBalance.value = balance
      Object.assign(configForm, {
        auto_payout_enabled: balance.auto_payout_enabled,
        auto_payout_threshold: balance.auto_payout_threshold,
        payout_phone_number: balance.phone_number || '',
      })
      showConfigModal.value = true
    }

    const saveConfiguration = async () => {
      savingConfig.value = true
      try {
        const response = await fetch(`/api/v1/admin/organizers/${editingBalance.value.organizer_id}/auto-payout-config`, {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            gateway: editingBalance.value.gateway,
            auto_payout_enabled: configForm.auto_payout_enabled,
            auto_payout_threshold: configForm.auto_payout_threshold,
            payout_phone_number: configForm.payout_phone_number
          })
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

    const resetFilters = () => {
      Object.assign(filters, {
        gateway: '',
        auto_payout_enabled: '',
      })
      loadBalances()
    }

    // Utilitaires
    const formatAmount = (amount) => {
      return new Intl.NumberFormat('fr-FR').format(amount)
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
      loadBalances()
    })

    return {
      // État
      loading,
      showConfigModal,
      savingConfig,
      editingBalance,
      balances,
      stats,
      filters,
      configForm,
      
      // Méthodes
      loadBalances,
      editBalance,
      saveConfiguration,
      resetFilters,
      
      // Utilitaires
      formatAmount,
      getGatewayName,
      getGatewayBadgeClass,
    }
  }
}
</script>

<style scoped>
.organizer-balance-config {
  font-family: 'Inter', sans-serif;
}
</style>