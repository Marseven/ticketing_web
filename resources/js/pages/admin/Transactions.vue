<template>
  <div class="admin-transactions min-h-screen bg-gray-100 font-primea">
    <!-- Sidebar (réutilise le même code que les autres pages) -->
    <div class="fixed inset-y-0 left-0 w-64 bg-primea-blue text-white transform transition-transform duration-300 ease-in-out z-30"
         :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }">
      
      <div class="flex items-center justify-between p-4 border-b border-primea-blue-dark">
        <div class="flex items-center space-x-3">
          <img src="/images/logo_white.png" alt="Primea" class="h-8" />
          <span class="font-bold text-lg font-primea">Administration</span>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="mt-6">
        <div class="px-4">
          <router-link 
            to="/admin/dashboard"
            class="flex items-center px-4 py-3 text-blue-200 hover:text-white hover:bg-primea-yellow hover:text-primea-blue rounded-primea-lg mb-2 transition-colors font-primea"
          >
            <HomeIcon class="w-5 h-5 mr-3" />
            Tableau de bord
          </router-link>

          <router-link to="/admin/users" class="flex items-center px-4 py-3 text-blue-200 hover:text-white hover:bg-primea-yellow hover:text-primea-blue rounded-primea-lg mb-2 transition-colors font-primea">
            <UsersIcon class="w-5 h-5 mr-3" />
            Utilisateurs
          </router-link>

          <router-link to="/admin/events" class="flex items-center px-4 py-3 text-blue-200 hover:text-white hover:bg-primea-yellow hover:text-primea-blue rounded-primea-lg mb-2 transition-colors font-primea">
            <CalendarIcon class="w-5 h-5 mr-3" />
            Événements
          </router-link>

          <router-link 
            to="/admin/transactions"
            class="flex items-center px-4 py-3 text-white bg-primea-yellow text-primea-blue rounded-primea-lg mb-2 font-primea font-semibold"
          >
            <CreditCardIcon class="w-5 h-5 mr-3" />
            Transactions
          </router-link>

          <router-link to="/admin/reports" class="flex items-center px-4 py-3 text-blue-200 hover:text-white hover:bg-primea-yellow hover:text-primea-blue rounded-primea-lg mb-2 transition-colors font-primea">
            <ChartBarIcon class="w-5 h-5 mr-3" />
            Rapports
          </router-link>

          <router-link to="/admin/settings" class="flex items-center px-4 py-3 text-blue-200 hover:text-white hover:bg-primea-yellow hover:text-primea-blue rounded-primea-lg mb-2 transition-colors font-primea">
            <CogIcon class="w-5 h-5 mr-3" />
            Paramètres
          </router-link>
        </div>
      </nav>

      <!-- User info -->
      <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-primea-blue-dark">
        <div class="flex items-center">
          <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=40&h=40&fit=crop&crop=face&auto=format" alt="Admin" class="w-8 h-8 rounded-full mr-3"/>
          <div class="flex-1">
            <p class="text-sm font-medium">Admin</p>
            <p class="text-xs text-blue-300">Super Admin</p>
          </div>
          <button @click="logout" class="text-blue-300 hover:text-primea-yellow">
            <ArrowRightOnRectangleIcon class="w-5 h-5" />
          </button>
        </div>
      </div>
    </div>

    <!-- Overlay pour mobile -->
    <div v-if="sidebarOpen" @click="toggleSidebar" class="fixed inset-0 bg-black/50 z-20 lg:hidden"></div>

    <!-- Contenu principal -->
    <div class="lg:ml-64 transition-all duration-300">
      <!-- Header -->
      <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="flex items-center justify-between px-6 py-4">
          <div class="flex items-center">
            <button @click="toggleSidebar" class="lg:hidden text-gray-500 hover:text-gray-700 mr-4">
              <Bars3Icon class="w-6 h-6" />
            </button>
            <h1 class="text-2xl font-bold text-primea-blue font-primea">Transactions et paiements</h1>
          </div>
        </div>
      </header>

      <!-- Contenu de la page -->
      <main class="p-6">
        <!-- Statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-primea-blue">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <CurrencyDollarIcon class="w-8 h-8 text-primea-blue" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Volume total</dt>
                  <dd class="text-2xl font-bold text-gray-900">{{ formatPrice(stats.totalVolume) }} FCFA</dd>
                </dl>
                <div class="text-sm text-green-600 font-medium">+{{ stats.volumeGrowth }}% ce mois</div>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <CheckCircleIcon class="w-8 h-8 text-green-500" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Transactions réussies</dt>
                  <dd class="text-2xl font-bold text-gray-900">{{ stats.successfulTransactions }}</dd>
                </dl>
                <div class="text-sm text-green-600 font-medium">{{ ((stats.successfulTransactions/stats.totalTransactions)*100).toFixed(1) }}% de réussite</div>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-primea-yellow">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <ClockIcon class="w-8 h-8 text-primea-yellow" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">En attente</dt>
                  <dd class="text-2xl font-bold text-gray-900">{{ stats.pendingTransactions }}</dd>
                </dl>
                <div class="text-sm text-yellow-600 font-medium">Nécessitent suivi</div>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-red-500">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <XCircleIcon class="w-8 h-8 text-red-500" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Échouées</dt>
                  <dd class="text-2xl font-bold text-gray-900">{{ stats.failedTransactions }}</dd>
                </dl>
                <div class="text-sm text-red-600 font-medium">{{ ((stats.failedTransactions/stats.totalTransactions)*100).toFixed(1) }}% d'échec</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Moyens de paiement -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
          <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-medium text-primea-blue mb-4 font-primea">Airtel Money</h3>
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm text-gray-600">Volume</span>
              <span class="text-sm font-medium">{{ formatPrice(45670000) }} FCFA</span>
            </div>
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm text-gray-600">Transactions</span>
              <span class="text-sm font-medium">342</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Taux de réussite</span>
              <span class="text-sm font-medium text-green-600">96.2%</span>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-medium text-primea-blue mb-4 font-primea">Moov Money</h3>
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm text-gray-600">Volume</span>
              <span class="text-sm font-medium">{{ formatPrice(38920000) }} FCFA</span>
            </div>
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm text-gray-600">Transactions</span>
              <span class="text-sm font-medium">289</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Taux de réussite</span>
              <span class="text-sm font-medium text-green-600">94.8%</span>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-medium text-primea-blue mb-4 font-primea">Cartes bancaires</h3>
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm text-gray-600">Volume</span>
              <span class="text-sm font-medium">{{ formatPrice(22150000) }} FCFA</span>
            </div>
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm text-gray-600">Transactions</span>
              <span class="text-sm font-medium">156</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Taux de réussite</span>
              <span class="text-sm font-medium text-green-600">98.1%</span>
            </div>
          </div>
        </div>

        <!-- Liste des transactions récentes -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-primea-blue font-primea">Transactions récentes</h3>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Référence</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Événement</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Moyen de paiement</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="transaction in transactions" :key="transaction.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {{ transaction.reference }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">{{ transaction.client_name }}</div>
                    <div class="text-sm text-gray-500">{{ transaction.client_email }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ transaction.event_title }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {{ formatPrice(transaction.amount) }} FCFA
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="getPaymentMethodClass(transaction.payment_method)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                      {{ getPaymentMethodText(transaction.payment_method) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="getTransactionStatusClass(transaction.status)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                      {{ getTransactionStatusText(transaction.status) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ formatDateTime(transaction.created_at) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button @click="viewTransaction(transaction)" class="text-primea-blue hover:text-primea-yellow transition-colors" title="Voir détails">
                      <EyeIcon class="w-5 h-5" />
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { 
  HomeIcon,
  UsersIcon,
  CreditCardIcon,
  ChartBarIcon,
  CogIcon,
  ArrowRightOnRectangleIcon,
  Bars3Icon,
  CheckCircleIcon,
  XCircleIcon,
  ClockIcon,
  EyeIcon,
  CurrencyDollarIcon
} from '@heroicons/vue/24/outline'
import CalendarIcon from '@/components/icons/CalendarIcon.vue'

export default {
  name: 'AdminTransactions',
  components: {
    HomeIcon,
    UsersIcon,
    CalendarIcon,
    CreditCardIcon,
    ChartBarIcon,
    CogIcon,
    ArrowRightOnRectangleIcon,
    Bars3Icon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    EyeIcon,
    CurrencyDollarIcon
  },
  setup() {
    const router = useRouter()
    const sidebarOpen = ref(window.innerWidth >= 1024)

    const stats = ref({
      totalVolume: 126750000,
      volumeGrowth: 18,
      totalTransactions: 842,
      successfulTransactions: 802,
      pendingTransactions: 12,
      failedTransactions: 28
    })

    const transactions = ref([
      {
        id: 1,
        reference: 'TXN-20250914-001',
        client_name: 'Jean Dupont',
        client_email: 'jean@email.com',
        event_title: "L'OISEAU RARE",
        amount: 10000,
        payment_method: 'airtel',
        status: 'success',
        created_at: new Date(Date.now() - 2 * 60 * 60 * 1000)
      },
      {
        id: 2,
        reference: 'TXN-20250914-002',
        client_name: 'Marie Martin',
        client_email: 'marie@email.com',
        event_title: 'Concert Jazz Night',
        amount: 15000,
        payment_method: 'moov',
        status: 'pending',
        created_at: new Date(Date.now() - 1 * 60 * 60 * 1000)
      },
      {
        id: 3,
        reference: 'TXN-20250914-003',
        client_name: 'Pierre Durand',
        client_email: 'pierre@email.com',
        event_title: 'Festival Arts',
        amount: 25000,
        payment_method: 'visa',
        status: 'failed',
        created_at: new Date(Date.now() - 30 * 60 * 1000)
      }
    ])

    const toggleSidebar = () => {
      sidebarOpen.value = !sidebarOpen.value
    }

    const formatPrice = (price) => {
      return new Intl.NumberFormat('fr-FR').format(price)
    }

    const formatDateTime = (date) => {
      return date.toLocaleString('fr-FR')
    }

    const getPaymentMethodClass = (method) => {
      const classes = {
        airtel: 'bg-red-100 text-red-800',
        moov: 'bg-orange-100 text-orange-800',
        visa: 'bg-blue-100 text-blue-800'
      }
      return classes[method] || 'bg-gray-100 text-gray-800'
    }

    const getPaymentMethodText = (method) => {
      const texts = {
        airtel: 'Airtel Money',
        moov: 'Moov Money',
        visa: 'Visa'
      }
      return texts[method] || 'Inconnu'
    }

    const getTransactionStatusClass = (status) => {
      const classes = {
        success: 'bg-green-100 text-green-800',
        pending: 'bg-yellow-100 text-yellow-800',
        failed: 'bg-red-100 text-red-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    }

    const getTransactionStatusText = (status) => {
      const texts = {
        success: 'Réussie',
        pending: 'En attente',
        failed: 'Échouée'
      }
      return texts[status] || 'Inconnu'
    }

    const viewTransaction = (transaction) => {
      console.log('View transaction:', transaction)
    }

    const logout = () => {
      router.push('/login')
    }

    onMounted(() => {
      const handleResize = () => {
        sidebarOpen.value = window.innerWidth >= 1024
      }
      window.addEventListener('resize', handleResize)
    })

    return {
      sidebarOpen,
      stats,
      transactions,
      toggleSidebar,
      formatPrice,
      formatDateTime,
      getPaymentMethodClass,
      getPaymentMethodText,
      getTransactionStatusClass,
      getTransactionStatusText,
      viewTransaction,
      logout
    }
  }
}
</script>

<style scoped>
.admin-transactions {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}
</style>