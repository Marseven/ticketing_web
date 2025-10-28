<template>
  <div class="admin-users min-h-screen bg-gray-100 font-primea">
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
            class="flex items-center px-4 py-3 text-white bg-primea-yellow text-primea-blue rounded-primea-lg mb-2 font-primea font-semibold"
          >
            <UsersIcon class="w-5 h-5 mr-3" />
            Utilisateurs
          </router-link>

          <router-link 
            to="/admin/events"
            class="flex items-center px-4 py-3 text-blue-200 hover:text-white hover:bg-primea-yellow hover:text-primea-blue rounded-primea-lg mb-2 transition-colors font-primea"
          >
            <CalendarIcon size="md" class="mr-3" />
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
            <h1 class="text-2xl font-bold text-primea-blue font-primea">Gestion des utilisateurs</h1>
          </div>

          <div class="flex items-center space-x-4">
            <button class="bg-primea-blue text-white px-4 py-2 rounded-primea-lg hover:bg-primea-yellow hover:text-primea-blue transition-all">
              <UserPlusIcon class="w-5 h-5 inline mr-2" />
              Nouvel utilisateur
            </button>
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
                <UsersIcon class="w-8 h-8 text-primea-blue" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Total utilisateurs</dt>
                  <dd class="text-2xl font-bold text-gray-900">{{ stats.totalUsers }}</dd>
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
                  <dt class="text-sm font-medium text-gray-500 truncate">Utilisateurs actifs</dt>
                  <dd class="text-2xl font-bold text-gray-900">{{ stats.activeUsers }}</dd>
                </dl>
                <div class="text-sm text-green-600 font-medium">{{ (stats.activeUsers/stats.totalUsers*100).toFixed(1) }}%</div>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-primea-yellow">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <UserIcon class="w-8 h-8 text-primea-yellow" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Organisateurs</dt>
                  <dd class="text-2xl font-bold text-gray-900">{{ stats.organizers }}</dd>
                </dl>
                <div class="text-sm text-blue-600 font-medium">{{ stats.pendingOrganizers }} en attente</div>
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
                  <dt class="text-sm font-medium text-gray-500 truncate">Comptes bloqués</dt>
                  <dd class="text-2xl font-bold text-gray-900">{{ stats.blockedUsers }}</dd>
                </dl>
                <div class="text-sm text-red-600 font-medium">Nécessitent attention</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Filtres et recherche -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
              <div class="relative">
                <input 
                  v-model="searchQuery"
                  type="text" 
                  placeholder="Nom, email..."
                  class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-primea-blue focus:border-primea-blue"
                />
                <MagnifyingGlassIcon class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" />
              </div>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
              <select 
                v-model="filterType"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primea-blue focus:border-primea-blue"
              >
                <option value="">Tous</option>
                <option value="client">Clients</option>
                <option value="organizer">Organisateurs</option>
                <option value="admin">Administrateurs</option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
              <select 
                v-model="filterStatus"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primea-blue focus:border-primea-blue"
              >
                <option value="">Tous</option>
                <option value="active">Actif</option>
                <option value="inactive">Inactif</option>
                <option value="blocked">Bloqué</option>
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

        <!-- Liste des utilisateurs -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Utilisateur
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Type
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Statut
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Inscrit le
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Dernière activité
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <img class="h-10 w-10 rounded-full" :src="user.avatar" :alt="user.name" />
                      <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                        <div class="text-sm text-gray-500">{{ user.email }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="getUserTypeClass(user.type)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                      {{ getUserTypeText(user.type) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="getUserStatusClass(user.status)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                      {{ getUserStatusText(user.status) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ formatDate(user.created_at) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ formatRelativeTime(user.last_activity) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div class="flex space-x-2">
                      <button 
                        @click="viewUser(user)"
                        class="text-primea-blue hover:text-primea-yellow transition-colors"
                        title="Voir détails"
                      >
                        <EyeIcon class="w-5 h-5" />
                      </button>
                      <button 
                        @click="editUser(user)"
                        class="text-green-600 hover:text-green-900 transition-colors"
                        title="Modifier"
                      >
                        <PencilIcon class="w-5 h-5" />
                      </button>
                      <button 
                        @click="toggleUserStatus(user)"
                        :class="user.status === 'blocked' ? 'text-green-600 hover:text-green-900' : 'text-red-600 hover:text-red-900'"
                        class="transition-colors"
                        :title="user.status === 'blocked' ? 'Débloquer' : 'Bloquer'"
                      >
                        <CheckCircleIcon v-if="user.status === 'blocked'" class="w-5 h-5" />
                        <XCircleIcon v-else class="w-5 h-5" />
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
import CalendarIcon from '../../components/icons/CalendarIcon.vue'
import { 
  HomeIcon, 
  UsersIcon, 
  CreditCardIcon, 
  ChartBarIcon, 
  CogIcon,
  MagnifyingGlassIcon,
  UserPlusIcon,
  PencilIcon,
  TrashIcon,
  CheckCircleIcon,
  XCircleIcon,
  ArrowRightOnRectangleIcon,
  Bars3Icon,
  EyeIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'

export default {
  name: 'AdminUsers',
  components: {
    CalendarIcon,
    HomeIcon,
    UsersIcon,
    CreditCardIcon,
    ChartBarIcon,
    CogIcon,
    MagnifyingGlassIcon,
    UserPlusIcon,
    PencilIcon,
    TrashIcon,
    CheckCircleIcon,
    XCircleIcon,
    ArrowRightOnRectangleIcon,
    Bars3Icon,
    EyeIcon,
    XMarkIcon
  },
  setup() {
    const router = useRouter()
    const sidebarOpen = ref(window.innerWidth >= 1024)
    const searchQuery = ref('')
    const filterType = ref('')
    const filterStatus = ref('')

    // Données statiques
    const stats = ref({
      totalUsers: 1247,
      activeUsers: 1156,
      organizers: 42,
      pendingOrganizers: 3,
      blockedUsers: 5,
      newThisMonth: 47
    })

    const users = ref([
      {
        id: 1,
        name: 'Jean Dupont',
        email: 'jean.dupont@email.com',
        type: 'client',
        status: 'active',
        avatar: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=40&h=40&fit=crop&crop=face&auto=format',
        created_at: new Date('2024-01-15'),
        last_activity: new Date(Date.now() - 2 * 60 * 60 * 1000)
      },
      {
        id: 2,
        name: 'Marie Martin',
        email: 'marie.martin@email.com',
        type: 'organizer',
        status: 'active',
        avatar: 'https://images.unsplash.com/photo-1494790108755-2616b612b390?w=40&h=40&fit=crop&crop=face&auto=format',
        created_at: new Date('2024-02-20'),
        last_activity: new Date(Date.now() - 5 * 60 * 1000)
      },
      {
        id: 3,
        name: 'Pierre Durand',
        email: 'pierre.durand@email.com',
        type: 'client',
        status: 'blocked',
        avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=40&h=40&fit=crop&crop=face&auto=format',
        created_at: new Date('2024-03-10'),
        last_activity: new Date(Date.now() - 24 * 60 * 60 * 1000)
      },
      {
        id: 4,
        name: 'Sophie Leroy',
        email: 'sophie.leroy@email.com',
        type: 'admin',
        status: 'active',
        avatar: 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=40&h=40&fit=crop&crop=face&auto=format',
        created_at: new Date('2023-12-01'),
        last_activity: new Date(Date.now() - 30 * 60 * 1000)
      }
    ])

    // Computed properties
    const filteredUsers = computed(() => {
      let filtered = users.value

      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(user => 
          user.name.toLowerCase().includes(query) ||
          user.email.toLowerCase().includes(query)
        )
      }

      if (filterType.value) {
        filtered = filtered.filter(user => user.type === filterType.value)
      }

      if (filterStatus.value) {
        filtered = filtered.filter(user => user.status === filterStatus.value)
      }

      return filtered
    })

    // Méthodes
    const toggleSidebar = () => {
      sidebarOpen.value = !sidebarOpen.value
    }

    const resetFilters = () => {
      searchQuery.value = ''
      filterType.value = ''
      filterStatus.value = ''
    }

    const getUserTypeClass = (type) => {
      const classes = {
        client: 'bg-blue-100 text-blue-800',
        organizer: 'bg-purple-100 text-purple-800',
        admin: 'bg-red-100 text-red-800'
      }
      return classes[type] || 'bg-gray-100 text-gray-800'
    }

    const getUserTypeText = (type) => {
      const texts = {
        client: 'Client',
        organizer: 'Organisateur',
        admin: 'Admin'
      }
      return texts[type] || 'Inconnu'
    }

    const getUserStatusClass = (status) => {
      const classes = {
        active: 'bg-green-100 text-green-800',
        inactive: 'bg-yellow-100 text-yellow-800',
        blocked: 'bg-red-100 text-red-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    }

    const getUserStatusText = (status) => {
      const texts = {
        active: 'Actif',
        inactive: 'Inactif',
        blocked: 'Bloqué'
      }
      return texts[status] || 'Inconnu'
    }

    const formatDate = (date) => {
      return date.toLocaleDateString('fr-FR')
    }

    const formatRelativeTime = (date) => {
      const now = new Date()
      const diffInMs = now - date
      const diffInMinutes = Math.floor(diffInMs / (1000 * 60))
      const diffInHours = Math.floor(diffInMs / (1000 * 60 * 60))
      const diffInDays = Math.floor(diffInMs / (1000 * 60 * 60 * 24))

      if (diffInMinutes < 60) {
        return `Il y a ${diffInMinutes} min`
      } else if (diffInHours < 24) {
        return `Il y a ${diffInHours}h`
      } else {
        return `Il y a ${diffInDays}j`
      }
    }

    const viewUser = (user) => {
      console.log('View user:', user)
      // Navigation vers page détail utilisateur
    }

    const editUser = (user) => {
      console.log('Edit user:', user)
      // Navigation vers page édition utilisateur
    }

    const toggleUserStatus = (user) => {
      if (user.status === 'blocked') {
        user.status = 'active'
      } else {
        user.status = 'blocked'
      }
      console.log('Toggle status for user:', user)
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
      filterType,
      filterStatus,
      stats,
      users,
      filteredUsers,
      toggleSidebar,
      resetFilters,
      getUserTypeClass,
      getUserTypeText,
      getUserStatusClass,
      getUserStatusText,
      formatDate,
      formatRelativeTime,
      viewUser,
      editUser,
      toggleUserStatus,
      logout
    }
  }
}
</script>

<style scoped>
* {
  font-family: 'Inter', 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.admin-users {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}
</style>