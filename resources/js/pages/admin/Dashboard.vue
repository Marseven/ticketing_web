<template>
  <div class="admin-dashboard min-h-screen bg-gray-100 font-primea">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 w-64 bg-primea-blue text-white transform transition-transform duration-300 ease-in-out z-30"
         :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }">
      
      <div class="flex items-center justify-between p-4 border-b border-primea-blue-dark">
        <div class="flex items-center space-x-3">
          <img src="/images/logo_white.png" alt="Primea" class="h-8" />
          <span class="font-bold text-lg font-primea">Administration</span>
        </div>
        <button @click="toggleSidebar" class="lg:hidden text-white hover:text-gray-300">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Navigation -->
      <nav class="mt-6">
        <div class="px-4">
          <router-link 
            to="/admin/dashboard"
            class="flex items-center px-4 py-3 text-white bg-primea-yellow text-primea-blue rounded-primea-lg mb-2 font-primea font-semibold"
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
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
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
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
              </svg>
            </button>
            <h1 class="text-2xl font-bold text-primea-blue font-primea">Tableau de bord</h1>
          </div>

          <div class="flex items-center space-x-4">
            <!-- Notifications -->
            <button class="relative p-2 text-gray-400 hover:text-primea-blue transition-colors">
              <BellIcon class="w-6 h-6" />
              <span class="absolute -top-1 -right-1 block h-4 w-4 rounded-full bg-red-500 text-white text-xs flex items-center justify-center font-bold">3</span>
            </button>

            <!-- Profil avec dropdown -->
            <div class="relative">
              <button 
                @click="toggleUserMenu"
                class="flex items-center space-x-2 p-2 rounded-lg hover:bg-gray-50 transition-colors"
              >
                <img 
                  src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=32&h=32&fit=crop&crop=face&auto=format" 
                  alt="Admin" 
                  class="w-8 h-8 rounded-full"
                />
                <span class="text-sm font-medium text-gray-700">Admin</span>
                <ChevronDownIcon class="w-4 h-4 text-gray-400" />
              </button>

              <!-- Dropdown menu -->
              <div 
                v-if="userMenuOpen"
                class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50"
              >
                <div class="py-1">
                  <div class="px-4 py-2 border-b border-gray-100">
                    <p class="text-sm font-medium text-gray-900">Admin</p>
                    <p class="text-xs text-gray-500">super.admin@primea.com</p>
                  </div>
                  
                  <button 
                    @click="goToProfile"
                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center"
                  >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Mon profil
                  </button>
                  
                  <button 
                    @click="goToSettings"
                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center"
                  >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Paramètres
                  </button>
                  
                  <div class="border-t border-gray-100">
                    <button 
                      @click="logout"
                      class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center"
                    >
                      <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                      </svg>
                      Se déconnecter
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </header>

      <!-- Contenu du dashboard -->
      <main class="p-6">
        <!-- Statistiques principales -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-primea-blue">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg class="w-8 h-8 text-primea-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Utilisateurs totaux</dt>
                  <dd class="text-2xl font-bold text-gray-900">{{ stats.totalUsers }}</dd>
                </dl>
                <div class="text-sm text-green-600 font-medium">+12% ce mois</div>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-primea-yellow">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <CalendarIcon size="xl" class="text-primea-yellow" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Événements actifs</dt>
                  <dd class="text-2xl font-bold text-gray-900">{{ stats.activeEvents }}</dd>
                </dl>
                <div class="text-sm text-green-600 font-medium">{{ stats.pendingApprovals }} en attente</div>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-primea-yellow">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <TicketIcon class="w-8 h-8 text-primea-yellow" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Tickets vendus</dt>
                  <dd class="text-2xl font-bold text-gray-900">{{ stats.totalTicketsSold }}</dd>
                </dl>
                <div class="text-sm text-green-600 font-medium">{{ stats.ticketsThisMonth }} ce mois</div>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-primea-blue">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <BanknotesIcon class="w-8 h-8 text-primea-blue" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Revenus totaux</dt>
                  <dd class="text-2xl font-bold text-gray-900">{{ formatPrice(stats.totalRevenue) }} FCFA</dd>
                </dl>
                <div class="text-sm text-green-600 font-medium">+18% ce mois</div>
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
          <!-- Graphique des revenus -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-lg font-medium text-primea-blue font-primea">Revenus par mois</h3>
              <select class="text-sm border border-gray-300 rounded-md px-3 py-1">
                <option>6 derniers mois</option>
                <option>12 derniers mois</option>
                <option>Cette année</option>
              </select>
            </div>
            
            <!-- Simulation d'un graphique -->
            <div class="space-y-3">
              <div v-for="(month, index) in revenueChart" :key="index" class="flex items-center">
                <div class="w-16 text-sm text-gray-600">{{ month.month }}</div>
                <div class="flex-1 mx-4">
                  <div class="bg-gray-200 rounded-full h-3">
                    <div 
                      class="bg-gradient-to-r from-primea-blue to-primea-yellow h-3 rounded-full transition-all duration-1000"
                      :style="{ width: `${month.percentage}%` }"
                    ></div>
                  </div>
                </div>
                <div class="w-24 text-sm font-medium text-gray-900 text-right">
                  {{ formatPrice(month.amount) }} FCFA
                </div>
              </div>
            </div>
          </div>

          <!-- Événements récents -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-lg font-medium text-primea-blue font-primea">Événements récents</h3>
              <router-link to="/admin/events" class="text-sm text-primea-blue hover:text-primea-yellow font-medium font-primea">
                Voir tous
              </router-link>
            </div>
            
            <div class="space-y-4">
              <div v-for="event in recentEvents" :key="event.id" class="flex items-center p-3 border border-gray-200 rounded-lg">
                <img 
                  :src="event.image" 
                  :alt="event.title"
                  class="w-12 h-12 object-cover rounded-lg mr-4"
                />
                <div class="flex-1">
                  <h4 class="text-sm font-medium text-gray-900">{{ event.title }}</h4>
                  <p class="text-xs text-gray-500">{{ event.organizer }}</p>
                  <div class="flex items-center mt-1">
                    <span :class="getEventStatusClass(event.status)" class="px-2 py-1 rounded-full text-xs font-medium">
                      {{ getEventStatusText(event.status) }}
                    </span>
                  </div>
                </div>
                <div class="text-right">
                  <p class="text-sm font-medium text-gray-900">{{ event.ticketsSold }} tickets</p>
                  <p class="text-xs text-gray-500">{{ formatPrice(event.revenue) }} FCFA</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Actions rapides et notifications -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Actions rapides -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-medium text-primea-blue mb-4 font-primea">Actions rapides</h3>
            
            <div class="space-y-3">
              <router-link 
                to="/admin/events/pending"
                class="flex items-center p-3 bg-primea-yellow-light border border-primea-yellow rounded-primea-lg hover:bg-primea-yellow transition-colors"
              >
                <div class="bg-primea-yellow p-2 rounded-full mr-3">
                  <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                </div>
                <div>
                  <p class="text-sm font-medium text-primea-blue font-primea">{{ stats.pendingApprovals }} événements</p>
                  <p class="text-xs text-gray-600">En attente d'approbation</p>
                </div>
              </router-link>

              <router-link 
                to="/admin/users/new"
                class="flex items-center p-3 bg-blue-50 border border-primea-blue rounded-primea-lg hover:bg-blue-100 transition-colors"
              >
                <div class="bg-primea-blue p-2 rounded-full mr-3">
                  <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                  </svg>
                </div>
                <div>
                  <p class="text-sm font-medium text-primea-blue font-primea">Nouveaux utilisateurs</p>
                  <p class="text-xs text-gray-600">{{ stats.newUsersToday }} aujourd'hui</p>
                </div>
              </router-link>

              <router-link 
                to="/admin/reports"
                class="flex items-center p-3 bg-green-50 border border-green-200 rounded-primea-lg hover:bg-green-100 transition-colors"
              >
                <div class="bg-green-500 p-2 rounded-full mr-3">
                  <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                  </svg>
                </div>
                <div>
                  <p class="text-sm font-medium text-green-800 font-primea">Générer rapport</p>
                  <p class="text-xs text-green-600">Revenus mensuel</p>
                </div>
              </router-link>
            </div>
          </div>

          <!-- Notifications système -->
          <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-medium text-primea-blue mb-4 font-primea">Notifications système</h3>
            
            <div class="space-y-4">
              <div v-for="notification in systemNotifications" :key="notification.id" 
                   class="flex items-start p-4 border-l-4 rounded-lg"
                   :class="getNotificationBorderClass(notification.type)">
                <div :class="getNotificationIconClass(notification.type)" class="p-1 rounded-full mr-3 mt-0.5">
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path v-if="notification.type === 'info'" fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    <path v-else-if="notification.type === 'warning'" fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    <path v-else fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                  </svg>
                </div>
                <div class="flex-1">
                  <h4 class="text-sm font-medium" :class="getNotificationTextClass(notification.type)">
                    {{ notification.title }}
                  </h4>
                  <p class="text-sm text-gray-600 mt-1">{{ notification.message }}</p>
                  <p class="text-xs text-gray-400 mt-2">{{ formatRelativeTime(notification.createdAt) }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import CalendarIcon from '../../components/icons/CalendarIcon.vue'
import { 
  HomeIcon, 
  UsersIcon, 
  CreditCardIcon, 
  ChartBarIcon, 
  CogIcon,
  BellIcon,
  ChevronDownIcon,
  TicketIcon,
  BanknotesIcon
} from '@heroicons/vue/24/outline'

export default {
  name: 'AdminDashboard',
  components: {
    CalendarIcon,
    HomeIcon,
    UsersIcon,
    CreditCardIcon,
    ChartBarIcon,
    CogIcon,
    BellIcon,
    ChevronDownIcon,
    TicketIcon,
    BanknotesIcon
  },
  setup() {
    const router = useRouter()
    const sidebarOpen = ref(window.innerWidth >= 1024)
    const userMenuOpen = ref(false)

    // Données statiques
    const stats = ref({
      totalUsers: 1247,
      activeEvents: 23,
      pendingApprovals: 5,
      totalTicketsSold: 8432,
      ticketsThisMonth: 892,
      totalRevenue: 126750000,
      newUsersToday: 12
    })

    const revenueChart = ref([
      { month: 'Jan', amount: 15000000, percentage: 60 },
      { month: 'Fév', amount: 18500000, percentage: 74 },
      { month: 'Mar', amount: 22000000, percentage: 88 },
      { month: 'Avr', amount: 25000000, percentage: 100 },
      { month: 'Mai', amount: 21000000, percentage: 84 },
      { month: 'Juin', amount: 25000000, percentage: 100 }
    ])

    const recentEvents = ref([
      {
        id: 1,
        title: "L'OISEAU RARE",
        organizer: 'MR GILLES',
        status: 'approved',
        ticketsSold: 85,
        revenue: 850000,
        image: 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=60'
      },
      {
        id: 2,
        title: 'Concert Jazz Night',
        organizer: 'Jazz Club Libreville',
        status: 'pending',
        ticketsSold: 0,
        revenue: 0,
        image: 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=60'
      },
      {
        id: 3,
        title: 'Festival Arts',
        organizer: 'Arts Center',
        status: 'approved',
        ticketsSold: 162,
        revenue: 2430000,
        image: 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=60'
      }
    ])

    const systemNotifications = ref([
      {
        id: 1,
        type: 'warning',
        title: 'Système de paiement',
        message: 'La passerelle Airtel Money est temporairement indisponible',
        createdAt: new Date(Date.now() - 30 * 60 * 1000)
      },
      {
        id: 2,
        type: 'success',
        title: 'Sauvegarde réussie',
        message: 'Sauvegarde automatique des données complétée avec succès',
        createdAt: new Date(Date.now() - 2 * 60 * 60 * 1000)
      },
      {
        id: 3,
        type: 'info',
        title: 'Mise à jour disponible',
        message: 'Une nouvelle version de la plateforme est disponible',
        createdAt: new Date(Date.now() - 6 * 60 * 60 * 1000)
      }
    ])

    // Méthodes
    const toggleSidebar = () => {
      sidebarOpen.value = !sidebarOpen.value
    }

    const toggleUserMenu = () => {
      userMenuOpen.value = !userMenuOpen.value
    }

    const goToProfile = () => {
      userMenuOpen.value = false
      router.push('/admin/profile')
    }

    const goToSettings = () => {
      userMenuOpen.value = false
      router.push('/admin/settings')
    }

    const formatPrice = (price) => {
      return new Intl.NumberFormat('fr-FR').format(price)
    }

    const formatRelativeTime = (date) => {
      const now = new Date()
      const diffInMs = now - date
      const diffInMinutes = Math.floor(diffInMs / (1000 * 60))
      const diffInHours = Math.floor(diffInMs / (1000 * 60 * 60))
      const diffInDays = Math.floor(diffInMs / (1000 * 60 * 60 * 24))

      if (diffInMinutes < 60) {
        return `Il y a ${diffInMinutes} minute${diffInMinutes > 1 ? 's' : ''}`
      } else if (diffInHours < 24) {
        return `Il y a ${diffInHours} heure${diffInHours > 1 ? 's' : ''}`
      } else {
        return `Il y a ${diffInDays} jour${diffInDays > 1 ? 's' : ''}`
      }
    }

    const getEventStatusClass = (status) => {
      const classes = {
        approved: 'bg-green-100 text-green-800',
        pending: 'bg-yellow-100 text-yellow-800',
        rejected: 'bg-red-100 text-red-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    }

    const getEventStatusText = (status) => {
      const texts = {
        approved: 'Approuvé',
        pending: 'En attente',
        rejected: 'Rejeté'
      }
      return texts[status] || 'Inconnu'
    }

    const getNotificationBorderClass = (type) => {
      const classes = {
        info: 'border-blue-400 bg-blue-50',
        warning: 'border-yellow-400 bg-yellow-50',
        success: 'border-green-400 bg-green-50',
        error: 'border-red-400 bg-red-50'
      }
      return classes[type] || 'border-gray-400 bg-gray-50'
    }

    const getNotificationIconClass = (type) => {
      const classes = {
        info: 'text-blue-400',
        warning: 'text-yellow-400',
        success: 'text-green-400',
        error: 'text-red-400'
      }
      return classes[type] || 'text-gray-400'
    }

    const getNotificationTextClass = (type) => {
      const classes = {
        info: 'text-blue-800',
        warning: 'text-yellow-800',
        success: 'text-green-800',
        error: 'text-red-800'
      }
      return classes[type] || 'text-gray-800'
    }

    const logout = () => {
      router.push('/login')
    }

    // Fermer le menu utilisateur quand on clique en dehors
    const handleClickOutside = (event) => {
      if (userMenuOpen.value && !event.target.closest('.relative')) {
        userMenuOpen.value = false
      }
    }

    // Lifecycle
    onMounted(() => {
      // Adapter le sidebar à la taille de l'écran
      const handleResize = () => {
        sidebarOpen.value = window.innerWidth >= 1024
      }
      window.addEventListener('resize', handleResize)
      
      // Écouter les clics pour fermer le menu
      document.addEventListener('click', handleClickOutside)
    })

    onUnmounted(() => {
      document.removeEventListener('click', handleClickOutside)
    })

    return {
      sidebarOpen,
      userMenuOpen,
      stats,
      revenueChart,
      recentEvents,
      systemNotifications,
      toggleSidebar,
      toggleUserMenu,
      goToProfile,
      goToSettings,
      formatPrice,
      formatRelativeTime,
      getEventStatusClass,
      getEventStatusText,
      getNotificationBorderClass,
      getNotificationIconClass,
      getNotificationTextClass,
      logout
    }
  }
}
</script>

<style scoped>
.admin-dashboard {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

/* Transitions pour la sidebar */
.transform {
  transform: var(--tw-transform);
}

.transition-transform {
  transition-property: transform;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}

.transition-all {
  transition: all 0.3s ease-in-out;
}

.transition-colors {
  transition: color 0.2s ease-in-out, background-color 0.2s ease-in-out;
}

/* Grid utilities */
.grid {
  display: grid;
}

.grid-cols-1 {
  grid-template-columns: repeat(1, minmax(0, 1fr));
}

@media (min-width: 768px) {
  .md\:grid-cols-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (min-width: 1024px) {
  .lg\:grid-cols-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  
  .lg\:grid-cols-3 {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
  
  .lg\:grid-cols-4 {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
  
  .lg\:col-span-2 {
    grid-column: span 2 / span 2;
  }
  
  .lg\:ml-64 {
    margin-left: 16rem;
  }
  
  .lg\:hidden {
    display: none;
  }
}

.gap-6 {
  gap: 1.5rem;
}

.space-x-2 > * + * {
  margin-left: 0.5rem;
}

.space-x-4 > * + * {
  margin-left: 1rem;
}

.space-y-3 > * + * {
  margin-top: 0.75rem;
}

.space-y-4 > * + * {
  margin-top: 1rem;
}

/* Classes utilitaires */
.w-64 {
  width: 16rem;
}

.w-0 {
  width: 0px;
}

.flex-shrink-0 {
  flex-shrink: 0;
}
</style>