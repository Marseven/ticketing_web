<template>
  <div class="admin-reports min-h-screen bg-gray-100 font-primea">
    <!-- Sidebar simplifié (même structure que les autres pages) -->
    <div class="fixed inset-y-0 left-0 w-64 bg-primea-blue text-white z-30" :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }">
      <div class="flex items-center justify-between p-4 border-b border-primea-blue-dark">
        <div class="flex items-center space-x-3">
          <img src="/images/logo_white.png" alt="Primea" class="h-8" />
          <span class="font-bold text-lg font-primea">Administration</span>
        </div>
      </div>

      <nav class="mt-6">
        <div class="px-4">
          <router-link to="/admin/dashboard" class="flex items-center px-4 py-3 text-blue-200 hover:bg-primea-yellow hover:text-primea-blue rounded-primea-lg mb-2 transition-colors font-primea">
            <HomeIcon class="w-5 h-5 mr-3" />
            Tableau de bord
          </router-link>
          <router-link to="/admin/users" class="flex items-center px-4 py-3 text-blue-200 hover:bg-primea-yellow hover:text-primea-blue rounded-primea-lg mb-2 transition-colors font-primea">
            <UsersIcon class="w-5 h-5 mr-3" />
            Utilisateurs
          </router-link>
          <router-link to="/admin/events" class="flex items-center px-4 py-3 text-blue-200 hover:bg-primea-yellow hover:text-primea-blue rounded-primea-lg mb-2 transition-colors font-primea">
            <CalendarIcon class="w-5 h-5 mr-3" />
            Événements
          </router-link>
          <router-link to="/admin/transactions" class="flex items-center px-4 py-3 text-blue-200 hover:bg-primea-yellow hover:text-primea-blue rounded-primea-lg mb-2 transition-colors font-primea">
            <CreditCardIcon class="w-5 h-5 mr-3" />
            Transactions
          </router-link>
          <router-link to="/admin/reports" class="flex items-center px-4 py-3 text-white bg-primea-yellow text-primea-blue rounded-primea-lg mb-2 font-primea font-semibold">
            <ChartBarIcon class="w-5 h-5 mr-3" />
            Rapports
          </router-link>
          <router-link to="/admin/settings" class="flex items-center px-4 py-3 text-blue-200 hover:bg-primea-yellow hover:text-primea-blue rounded-primea-lg mb-2 transition-colors font-primea">
            <CogIcon class="w-5 h-5 mr-3" />
            Paramètres
          </router-link>
        </div>
      </nav>
    </div>

    <!-- Contenu principal -->
    <div class="lg:ml-64 transition-all duration-300">
      <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="px-6 py-4">
          <h1 class="text-2xl font-bold text-primea-blue font-primea">Rapports et analytics</h1>
        </div>
      </header>

      <main class="p-6">
        <!-- Génération de rapports -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
          <div class="bg-white rounded-lg shadow-sm p-6 text-center">
            <div class="bg-primea-blue p-4 rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
              <CurrencyDollarIcon class="w-8 h-8 text-white" />
            </div>
            <h3 class="text-lg font-semibold text-primea-blue mb-2">Rapport financier</h3>
            <p class="text-gray-600 text-sm mb-4">Revenus, commissions, remboursements</p>
            <button @click="generateReport('financial')" class="bg-primea-blue text-white px-4 py-2 rounded-lg hover:bg-primea-yellow hover:text-primea-blue transition-all">
              Générer
            </button>
          </div>

          <div class="bg-white rounded-lg shadow-sm p-6 text-center">
            <div class="bg-primea-yellow p-4 rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
              <CalendarIcon class="w-8 h-8 text-primea-blue" />
            </div>
            <h3 class="text-lg font-semibold text-primea-blue mb-2">Rapport événements</h3>
            <p class="text-gray-600 text-sm mb-4">Performances, ventes, tendances</p>
            <button @click="generateReport('events')" class="bg-primea-blue text-white px-4 py-2 rounded-lg hover:bg-primea-yellow hover:text-primea-blue transition-all">
              Générer
            </button>
          </div>

          <div class="bg-white rounded-lg shadow-sm p-6 text-center">
            <div class="bg-green-500 p-4 rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
              <UsersIcon class="w-8 h-8 text-white" />
            </div>
            <h3 class="text-lg font-semibold text-primea-blue mb-2">Rapport utilisateurs</h3>
            <p class="text-gray-600 text-sm mb-4">Croissance, activité, segments</p>
            <button @click="generateReport('users')" class="bg-primea-blue text-white px-4 py-2 rounded-lg hover:bg-primea-yellow hover:text-primea-blue transition-all">
              Générer
            </button>
          </div>
        </div>

        <!-- Historique des rapports -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-primea-blue font-primea">Historique des rapports</h3>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Période</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Généré le</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="report in reports" :key="report.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ report.type }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ report.period }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatDate(report.created_at) }}</td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Prêt</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button @click="downloadReport(report)" class="text-primea-blue hover:text-primea-yellow mr-3">Télécharger</button>
                    <button @click="viewReport(report)" class="text-green-600 hover:text-green-900">Voir</button>
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
  CurrencyDollarIcon
} from '@heroicons/vue/24/outline'
import CalendarIcon from '@/components/icons/CalendarIcon.vue'

export default {
  name: 'AdminReports',
  components: {
    HomeIcon,
    UsersIcon,
    CalendarIcon,
    CreditCardIcon,
    ChartBarIcon,
    CogIcon,
    CurrencyDollarIcon
  },
  setup() {
    const router = useRouter()
    const sidebarOpen = ref(window.innerWidth >= 1024)
    
    const reports = ref([
      { id: 1, type: 'Rapport financier', period: 'Août 2025', created_at: new Date('2025-09-01') },
      { id: 2, type: 'Rapport événements', period: 'Juillet 2025', created_at: new Date('2025-08-01') },
      { id: 3, type: 'Rapport utilisateurs', period: 'Juin 2025', created_at: new Date('2025-07-01') }
    ])

    const generateReport = (type) => {
      console.log('Generating report:', type)
      // Logique de génération de rapport
    }

    const downloadReport = (report) => {
      console.log('Downloading report:', report)
    }

    const viewReport = (report) => {
      console.log('Viewing report:', report)
    }

    const formatDate = (date) => {
      return date.toLocaleDateString('fr-FR')
    }

    return {
      sidebarOpen,
      reports,
      generateReport,
      downloadReport,
      viewReport,
      formatDate
    }
  }
}
</script>

<style scoped>
.admin-reports {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}
</style>