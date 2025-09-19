<template>
  <div class="organizer-dashboard p-6 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">Dashboard Organisateur</h1>
      <p class="text-gray-600">Gérez vos événements, ventes et payouts</p>
    </div>

    <!-- Loading state -->
    <div v-if="loading" class="flex justify-center items-center h-64">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <div v-else>
      <!-- Quick Actions -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <button @click="$router.push('/organizer/events/create')" 
                class="bg-blue-600 text-white p-6 rounded-lg hover:bg-blue-700 transition-all transform hover:scale-105">
          <div class="flex items-center justify-center">
            <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            <div class="text-left">
              <h3 class="text-xl font-bold">Créer un Événement</h3>
              <p class="text-blue-200">Nouveau spectacle, concert...</p>
            </div>
          </div>
        </button>

        <button @click="$router.push('/organizer/balance')" 
                class="bg-green-600 text-white p-6 rounded-lg hover:bg-green-700 transition-all transform hover:scale-105">
          <div class="flex items-center justify-center">
            <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
            </svg>
            <div class="text-left">
              <h3 class="text-xl font-bold">Mon Solde</h3>
              <p class="text-green-200">{{ formatAmount(totalBalance) }} XAF</p>
            </div>
          </div>
        </button>

        <button @click="$router.push('/organizer/sales/physical')" 
                class="bg-purple-600 text-white p-6 rounded-lg hover:bg-purple-700 transition-all transform hover:scale-105">
          <div class="flex items-center justify-center">
            <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="text-left">
              <h3 class="text-xl font-bold">Ventes Physiques</h3>
              <p class="text-purple-200">Ajouter ventes manuelles</p>
            </div>
          </div>
        </button>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-2 bg-blue-100 rounded-lg">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm text-gray-600">Total Événements</p>
              <p class="text-2xl font-bold text-gray-900">{{ stats.total_events || 0 }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-2 bg-green-100 rounded-lg">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm text-gray-600">Tickets Vendus</p>
              <p class="text-2xl font-bold text-green-600">{{ stats.total_tickets || 0 }}</p>
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
              <p class="text-sm text-gray-600">Revenus Totaux</p>
              <p class="text-2xl font-bold text-yellow-600">{{ formatAmount(stats.total_revenue || 0) }} XAF</p>
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

      <!-- Balance Section -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <div class="lg:col-span-2">
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">Mes Soldes par Gateway</h2>
            <div v-if="balances.length === 0" class="text-center text-gray-500 py-8">
              Aucun solde disponible
            </div>
            <div v-else class="space-y-4">
              <div v-for="balance in balances" :key="`${balance.gateway}`" 
                   class="flex justify-between items-center p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center">
                  <div class="p-2 rounded-lg mr-4" :class="getGatewayBadgeClass(balance.gateway)">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                  </div>
                  <div>
                    <h3 class="font-semibold">{{ getGatewayName(balance.gateway) }}</h3>
                    <p class="text-sm text-gray-600">
                      Payout auto: {{ balance.auto_payout_enabled ? 'Activé' : 'Désactivé' }}
                    </p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="text-2xl font-bold text-green-600">{{ formatAmount(balance.balance) }} XAF</p>
                  <button @click="requestPayout(balance)" 
                          :disabled="balance.balance < 1000"
                          class="text-sm bg-blue-600 text-white px-3 py-1 rounded mt-2 hover:bg-blue-700 disabled:opacity-50">
                    Retirer
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="lg:col-span-1">
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">Actions Rapides</h2>
            <div class="space-y-3">
              <button @click="$router.push('/organizer/events')" 
                      class="w-full text-left p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                <div class="flex items-center">
                  <svg class="w-5 h-5 text-gray-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                  </svg>
                  <span class="font-medium">Mes Événements</span>
                </div>
              </button>
              
              <button @click="$router.push('/organizer/payments')" 
                      class="w-full text-left p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                <div class="flex items-center">
                  <svg class="w-5 h-5 text-gray-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                  </svg>
                  <span class="font-medium">Mes Paiements</span>
                </div>
              </button>
              
              <button @click="$router.push('/organizer/payouts')" 
                      class="w-full text-left p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                <div class="flex items-center">
                  <svg class="w-5 h-5 text-gray-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"></path>
                  </svg>
                  <span class="font-medium">Historique Payouts</span>
                </div>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Events -->
      <div class="bg-white rounded-lg shadow p-6 mb-8">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold">Événements Récents</h2>
          <button @click="$router.push('/organizer/events')" 
                  class="text-blue-600 hover:text-blue-800">Voir tous</button>
        </div>
        
        <div v-if="recentEvents.length === 0" class="text-center text-gray-500 py-8">
          Aucun événement récent
        </div>
        
        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Événement</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prochaine Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tickets Vendus</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="event in recentEvents" :key="event.id">
                <td class="px-4 py-4 text-sm font-medium text-gray-900">{{ event.title }}</td>
                <td class="px-4 py-4 text-sm">
                  <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" 
                        :class="getStatusBadgeClass(event.status)">
                    {{ getStatusName(event.status) }}
                  </span>
                </td>
                <td class="px-4 py-4 text-sm text-gray-900">
                  {{ event.next_schedule ? event.next_schedule.starts_at : '-' }}
                </td>
                <td class="px-4 py-4 text-sm text-gray-900">{{ event.tickets_sold }}</td>
                <td class="px-4 py-4 text-sm space-x-2">
                  <button @click="viewEventSales(event)" class="text-blue-600 hover:text-blue-900">
                    Ventes
                  </button>
                  <button @click="$router.push(`/organizer/events/${event.id}/edit`)" class="text-green-600 hover:text-green-900">
                    Modifier
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Scan Statistics Chart -->
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold mb-4">Statistiques de Scan (7 derniers jours)</h2>
        <div v-if="scanStats.length === 0" class="text-center text-gray-500 py-8">
          Aucune donnée de scan disponible
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Scans</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valides</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doublons</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invalides</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Taux de Réussite</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="stat in scanStats" :key="stat.date">
                <td class="px-4 py-4 text-sm text-gray-900">{{ formatDate(stat.date) }}</td>
                <td class="px-4 py-4 text-sm text-gray-900">{{ stat.total }}</td>
                <td class="px-4 py-4 text-sm text-green-600">{{ stat.valid }}</td>
                <td class="px-4 py-4 text-sm text-yellow-600">{{ stat.duplicate }}</td>
                <td class="px-4 py-4 text-sm text-red-600">{{ stat.invalid }}</td>
                <td class="px-4 py-4 text-sm">
                  <span class="font-semibold" :class="stat.success_rate >= 90 ? 'text-green-600' : stat.success_rate >= 75 ? 'text-yellow-600' : 'text-red-600'">
                    {{ stat.success_rate }}%
                  </span>
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
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Numéro de téléphone</label>
              <input v-model="payoutForm.phone_number" type="tel" maxlength="9" pattern="[0-9]{9}" required 
                     placeholder="074123456" class="w-full border rounded-lg px-3 py-2">
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
  </div>
</template>

<script>
import { ref, reactive, onMounted, computed } from 'vue'

export default {
  name: 'OrganizerDashboard',
  setup() {
    // État réactif
    const loading = ref(false)
    const showPayoutModal = ref(false)
    const requestingPayout = ref(false)
    
    const stats = reactive({})
    const balances = ref([])
    const recentEvents = ref([])
    const scanStats = ref([])
    
    const payoutForm = reactive({
      gateway: '',
      available_balance: 0,
      amount: '',
      phone_number: '',
    })

    // Computed
    const totalBalance = computed(() => {
      return balances.value.reduce((total, balance) => total + balance.balance, 0)
    })

    // Méthodes
    const loadDashboardData = async () => {
      loading.value = true
      try {
        const response = await fetch('/api/v1/organizer/dashboard', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        const data = await response.json()
        if (response.ok) {
          Object.assign(stats, data.stats)
          recentEvents.value = data.recent_events
          scanStats.value = data.scan_stats
        }
      } catch (error) {
        console.error('Erreur chargement dashboard:', error)
      } finally {
        loading.value = false
      }
    }

    const loadBalances = async () => {
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
          loadBalances() // Recharger les soldes
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

    const viewEventSales = (event) => {
      this.$router.push(`/organizer/events/${event.id}/sales`)
    }

    // Utilitaires
    const formatAmount = (amount) => {
      return new Intl.NumberFormat('fr-FR').format(amount)
    }

    const formatDate = (date) => {
      return new Date(date).toLocaleDateString('fr-FR')
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

    // Lifecycle
    onMounted(() => {
      loadDashboardData()
      loadBalances()
    })

    return {
      // État
      loading,
      showPayoutModal,
      requestingPayout,
      stats,
      balances,
      recentEvents,
      scanStats,
      payoutForm,
      totalBalance,
      
      // Méthodes
      loadDashboardData,
      loadBalances,
      requestPayout,
      submitPayoutRequest,
      viewEventSales,
      
      // Utilitaires
      formatAmount,
      formatDate,
      getGatewayName,
      getGatewayBadgeClass,
      getStatusName,
      getStatusBadgeClass,
    }
  }
}
</script>

<style scoped>
.organizer-dashboard {
  font-family: 'Inter', sans-serif;
}
</style>