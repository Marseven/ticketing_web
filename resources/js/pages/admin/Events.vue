<template>
  <div class="admin-events min-h-screen bg-gray-100 font-primea">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 w-64 bg-primea-blue text-white transform transition-transform duration-300 ease-in-out z-30"
         :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }">
      
      <div class="flex items-center justify-between p-4 border-b border-primea-blue-dark">
        <div class="flex items-center space-x-3">
          <img src="/images/logo_white.png" alt="Primea" class="h-8" />
          <span class="font-bold text-lg font-primea">Administration</span>
        </div>
        <button @click="toggleSidebar" class="lg:hidden text-white hover:text-gray-300">
          <XMarkIcon class="w-6 h-6" />
        </button>
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

          <router-link 
            to="/admin/users"
            class="flex items-center px-4 py-3 text-blue-200 hover:text-white hover:bg-primea-yellow hover:text-primea-blue rounded-primea-lg mb-2 transition-colors font-primea"
          >
            <UsersIcon class="w-5 h-5 mr-3" />
            Utilisateurs
          </router-link>

          <router-link 
            to="/admin/events"
            class="flex items-center px-4 py-3 text-white bg-primea-yellow text-primea-blue rounded-primea-lg mb-2 font-primea font-semibold"
          >
            <CalendarIcon class="w-5 h-5 mr-3" />
            Événements
          </router-link>

          <router-link 
            to="/admin/transactions"
            class="flex items-center px-4 py-3 text-blue-200 hover:text-white hover:bg-primea-yellow hover:text-primea-blue rounded-primea-lg mb-2 transition-colors font-primea"
          >
            <CreditCardIcon class="w-5 h-5 mr-3" />
            Transactions
          </router-link>

          <router-link 
            to="/admin/reports"
            class="flex items-center px-4 py-3 text-blue-200 hover:text-white hover:bg-primea-yellow hover:text-primea-blue rounded-primea-lg mb-2 transition-colors font-primea"
          >
            <ChartBarIcon class="w-5 h-5 mr-3" />
            Rapports
          </router-link>

          <router-link 
            to="/admin/settings"
            class="flex items-center px-4 py-3 text-blue-200 hover:text-white hover:bg-primea-yellow hover:text-primea-blue rounded-primea-lg mb-2 transition-colors font-primea"
          >
            <CogIcon class="w-5 h-5 mr-3" />
            Paramètres
          </router-link>
        </div>
      </nav>

      <!-- User info -->
      <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-primea-blue-dark">
        <div class="flex items-center">
          <img 
            src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=40&h=40&fit=crop&crop=face&auto=format" 
            alt="Admin" 
            class="w-8 h-8 rounded-full mr-3"
          />
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
            <h1 class="text-2xl font-bold text-primea-blue font-primea">Gestion des événements</h1>
          </div>

          <div class="flex items-center space-x-4">
            <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-medium">
              {{ stats.pendingApprovals }} en attente
            </span>
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
                <CalendarIcon class="w-8 h-8 text-primea-blue" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Total événements</dt>
                  <dd class="text-2xl font-bold text-gray-900">{{ stats.totalEvents }}</dd>
                </dl>
                <div class="text-sm text-green-600 font-medium">+{{ stats.newThisMonth }} ce mois</div>
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
                  <dt class="text-sm font-medium text-gray-500 truncate">Événements actifs</dt>
                  <dd class="text-2xl font-bold text-gray-900">{{ stats.activeEvents }}</dd>
                </dl>
                <div class="text-sm text-green-600 font-medium">{{ ((stats.activeEvents/stats.totalEvents)*100).toFixed(1) }}% du total</div>
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
                  <dd class="text-2xl font-bold text-gray-900">{{ stats.pendingApprovals }}</dd>
                </dl>
                <div class="text-sm text-yellow-600 font-medium">Nécessitent validation</div>
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
                  <dt class="text-sm font-medium text-gray-500 truncate">Rejetés</dt>
                  <dd class="text-2xl font-bold text-gray-900">{{ stats.rejectedEvents }}</dd>
                </dl>
                <div class="text-sm text-red-600 font-medium">Ce mois</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Filtres et recherche -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
          <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
              <div class="relative">
                <input 
                  v-model="searchQuery"
                  type="text" 
                  placeholder="Titre, organisateur..."
                  class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-primea-blue focus:border-primea-blue"
                />
                <MagnifyingGlassIcon class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" />
              </div>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
              <select 
                v-model="filterStatus"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primea-blue focus:border-primea-blue"
              >
                <option value="">Tous</option>
                <option value="pending">En attente</option>
                <option value="approved">Approuvé</option>
                <option value="rejected">Rejeté</option>
                <option value="cancelled">Annulé</option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Catégorie</label>
              <select 
                v-model="filterCategory"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primea-blue focus:border-primea-blue"
              >
                <option value="">Toutes</option>
                <option value="music">Musique</option>
                <option value="sport">Sport</option>
                <option value="theater">Théâtre</option>
                <option value="conference">Conférence</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
              <select 
                v-model="filterDate"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primea-blue focus:border-primea-blue"
              >
                <option value="">Toutes</option>
                <option value="today">Aujourd'hui</option>
                <option value="week">Cette semaine</option>
                <option value="month">Ce mois</option>
                <option value="upcoming">À venir</option>
                <option value="past">Passés</option>
              </select>
            </div>
            
            <div class="flex items-end">
              <button 
                @click="resetFilters"
                class="w-full px-4 py-2 text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
              >
                Réinitialiser
              </button>
            </div>
          </div>
        </div>

        <!-- Actions rapides pour les événements en attente -->
        <div v-if="pendingEvents.length > 0" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
          <div class="flex">
            <div class="flex-shrink-0">
              <ExclamationTriangleIcon class="h-5 w-5 text-yellow-400" />
            </div>
            <div class="ml-3">
              <p class="text-sm text-yellow-700 font-medium">
                {{ pendingEvents.length }} événement(s) en attente d'approbation
              </p>
              <div class="mt-2">
                <div class="flex space-x-2">
                  <button
                    @click="approveAll"
                    class="text-sm bg-green-600 text-white px-3 py-1 rounded-lg hover:bg-green-700"
                  >
                    Tout approuver
                  </button>
                  <button
                    @click="reviewPending"
                    class="text-sm bg-yellow-600 text-white px-3 py-1 rounded-lg hover:bg-yellow-700"
                  >
                    Examiner
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Liste des événements -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Événement
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Organisateur
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Date
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Statut
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Billets vendus
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Revenue
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="event in filteredEvents" :key="event.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <img class="h-12 w-12 rounded-lg object-cover" :src="event.image" :alt="event.title" />
                      <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900">{{ event.title }}</div>
                        <div class="text-sm text-gray-500">{{ event.category }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">{{ event.organizer }}</div>
                    <div class="text-sm text-gray-500">{{ event.organizer_email }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <div>{{ formatDate(event.date) }}</div>
                    <div class="text-gray-500">{{ formatTime(event.date) }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="getEventStatusClass(event.status)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                      {{ getEventStatusText(event.status) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ event.tickets_sold }}/{{ event.tickets_total }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ formatPrice(event.revenue) }} FCFA
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div class="flex space-x-2">
                      <button 
                        @click="viewEvent(event)"
                        class="text-primea-blue hover:text-primea-yellow transition-colors"
                        title="Voir détails"
                      >
                        <EyeIcon class="w-5 h-5" />
                      </button>
                      <button 
                        v-if="event.status === 'pending'"
                        @click="approveEvent(event)"
                        class="text-green-600 hover:text-green-900 transition-colors"
                        title="Approuver"
                      >
                        <CheckCircleIcon class="w-5 h-5" />
                      </button>
                      <button 
                        v-if="event.status === 'pending'"
                        @click="rejectEvent(event)"
                        class="text-red-600 hover:text-red-900 transition-colors"
                        title="Rejeter"
                      >
                        <XCircleIcon class="w-5 h-5" />
                      </button>
                      <button 
                        @click="editEvent(event)"
                        class="text-yellow-600 hover:text-yellow-900 transition-colors"
                        title="Modifier"
                      >
                        <PencilIcon class="w-5 h-5" />
                      </button>
                    </div>
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
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { 
  HomeIcon,
  UsersIcon,
  CreditCardIcon,
  ChartBarIcon,
  CogIcon,
  ArrowRightOnRectangleIcon,
  XMarkIcon,
  Bars3Icon,
  CheckCircleIcon,
  XCircleIcon,
  ClockIcon,
  MagnifyingGlassIcon,
  EyeIcon,
  PencilIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'
import CalendarIcon from '@/components/icons/CalendarIcon.vue'

export default {
  name: 'AdminEvents',
  components: {
    HomeIcon,
    UsersIcon,
    CalendarIcon,
    CreditCardIcon,
    ChartBarIcon,
    CogIcon,
    ArrowRightOnRectangleIcon,
    XMarkIcon,
    Bars3Icon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    MagnifyingGlassIcon,
    EyeIcon,
    PencilIcon,
    ExclamationTriangleIcon
  },
  setup() {
    const router = useRouter()
    const sidebarOpen = ref(window.innerWidth >= 1024)
    const searchQuery = ref('')
    const filterStatus = ref('')
    const filterCategory = ref('')
    const filterDate = ref('')

    // Données statiques
    const stats = ref({
      totalEvents: 156,
      activeEvents: 23,
      pendingApprovals: 5,
      rejectedEvents: 3,
      newThisMonth: 12
    })

    const events = ref([
      {
        id: 1,
        title: "L'OISEAU RARE",
        category: 'Musique',
        organizer: 'MR GILLES',
        organizer_email: 'gilles@email.com',
        status: 'approved',
        date: new Date('2025-07-27T20:00:00'),
        tickets_sold: 85,
        tickets_total: 200,
        revenue: 850000,
        image: 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=100&h=100&fit=crop'
      },
      {
        id: 2,
        title: 'Concert Jazz Night',
        category: 'Musique',
        organizer: 'Jazz Club Libreville',
        organizer_email: 'jazz@club.com',
        status: 'pending',
        date: new Date('2025-08-15T19:30:00'),
        tickets_sold: 0,
        tickets_total: 150,
        revenue: 0,
        image: 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=100&h=100&fit=crop'
      },
      {
        id: 3,
        title: 'Festival Arts',
        category: 'Culture',
        organizer: 'Arts Center',
        organizer_email: 'contact@artscenter.ci',
        status: 'approved',
        date: new Date('2025-09-10T14:00:00'),
        tickets_sold: 162,
        tickets_total: 300,
        revenue: 2430000,
        image: 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=100&h=100&fit=crop'
      },
      {
        id: 4,
        title: 'Tournoi Football',
        category: 'Sport',
        organizer: 'FC Libreville',
        organizer_email: 'fc@libreville.ga',
        status: 'pending',
        date: new Date('2025-08-20T16:00:00'),
        tickets_sold: 0,
        tickets_total: 500,
        revenue: 0,
        image: 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=100&h=100&fit=crop'
      },
      {
        id: 5,
        title: 'Conférence Tech',
        category: 'Conférence',
        organizer: 'TechCI',
        organizer_email: 'info@techci.org',
        status: 'rejected',
        date: new Date('2025-07-30T09:00:00'),
        tickets_sold: 0,
        tickets_total: 100,
        revenue: 0,
        image: 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=100&h=100&fit=crop'
      }
    ])

    // Computed properties
    const filteredEvents = computed(() => {
      let filtered = events.value

      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(event => 
          event.title.toLowerCase().includes(query) ||
          event.organizer.toLowerCase().includes(query) ||
          event.category.toLowerCase().includes(query)
        )
      }

      if (filterStatus.value) {
        filtered = filtered.filter(event => event.status === filterStatus.value)
      }

      if (filterCategory.value) {
        filtered = filtered.filter(event => event.category.toLowerCase() === filterCategory.value)
      }

      // Filtrage par date pourrait être ajouté ici

      return filtered
    })

    const pendingEvents = computed(() => {
      return events.value.filter(event => event.status === 'pending')
    })

    // Méthodes
    const toggleSidebar = () => {
      sidebarOpen.value = !sidebarOpen.value
    }

    const resetFilters = () => {
      searchQuery.value = ''
      filterStatus.value = ''
      filterCategory.value = ''
      filterDate.value = ''
    }

    const getEventStatusClass = (status) => {
      const classes = {
        approved: 'bg-green-100 text-green-800',
        pending: 'bg-yellow-100 text-yellow-800',
        rejected: 'bg-red-100 text-red-800',
        cancelled: 'bg-gray-100 text-gray-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    }

    const getEventStatusText = (status) => {
      const texts = {
        approved: 'Approuvé',
        pending: 'En attente',
        rejected: 'Rejeté',
        cancelled: 'Annulé'
      }
      return texts[status] || 'Inconnu'
    }

    const formatDate = (date) => {
      return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      })
    }

    const formatTime = (date) => {
      return date.toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    const formatPrice = (price) => {
      return new Intl.NumberFormat('fr-FR').format(price)
    }

    const viewEvent = (event) => {
      console.log('View event:', event)
    }

    const approveEvent = (event) => {
      event.status = 'approved'
      console.log('Approved event:', event.title)
    }

    const rejectEvent = (event) => {
      event.status = 'rejected'
      console.log('Rejected event:', event.title)
    }

    const editEvent = (event) => {
      console.log('Edit event:', event)
    }

    const approveAll = () => {
      pendingEvents.value.forEach(event => {
        event.status = 'approved'
      })
      console.log('Approved all pending events')
    }

    const reviewPending = () => {
      filterStatus.value = 'pending'
      console.log('Reviewing pending events')
    }

    const logout = () => {
      router.push('/login')
    }

    // Lifecycle
    onMounted(() => {
      const handleResize = () => {
        sidebarOpen.value = window.innerWidth >= 1024
      }
      window.addEventListener('resize', handleResize)
    })

    return {
      sidebarOpen,
      searchQuery,
      filterStatus,
      filterCategory,
      filterDate,
      stats,
      events,
      filteredEvents,
      pendingEvents,
      toggleSidebar,
      resetFilters,
      getEventStatusClass,
      getEventStatusText,
      formatDate,
      formatTime,
      formatPrice,
      viewEvent,
      approveEvent,
      rejectEvent,
      editEvent,
      approveAll,
      reviewPending,
      logout
    }
  }
}
</script>

<style scoped>
* {
  font-family: 'Inter', 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.admin-events {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}
</style>