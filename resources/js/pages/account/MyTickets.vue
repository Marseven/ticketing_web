<template>
  <div class="my-tickets min-h-screen bg-gray-50 font-primea">
    <div class="max-w-7xl mx-auto">

      <!-- Filtres et statistiques -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-6 mb-8">
        <div class="bg-white rounded-primea-lg shadow-sm p-3 md:p-6 border-l-4 border-primea-blue">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <TicketIcon class="w-8 h-8 text-primea-blue" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Total tickets</p>
              <p class="text-2xl font-bold text-gray-900">{{ stats.totalTickets }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-primea-lg shadow-sm p-3 md:p-6 border-l-4 border-green-500">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <CheckCircleIcon class="w-8 h-8 text-green-500" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Actifs</p>
              <p class="text-2xl font-bold text-gray-900">{{ stats.activeTickets }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-primea-lg shadow-sm p-3 md:p-6 border-l-4 border-primea-yellow">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <CalendarIcon size="xl" class="text-primea-yellow" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Prochains événements</p>
              <p class="text-2xl font-bold text-gray-900">{{ stats.upcomingEvents }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-primea-lg shadow-sm p-3 md:p-6 border-l-4 border-gray-400">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <ClockIcon class="w-8 h-8 text-gray-400" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Expirés</p>
              <p class="text-2xl font-bold text-gray-900">{{ stats.expiredTickets }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filtres -->
      <div class="bg-white rounded-primea-lg shadow-sm p-6 mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div class="flex flex-col md:flex-row gap-4">
            <div class="relative">
              <MagnifyingGlassIcon class="w-5 h-5 text-gray-400 absolute left-3 top-3" />
              <input 
                v-model="searchQuery"
                type="text" 
                placeholder="Rechercher un événement..."
                class="pl-10 pr-4 py-2 w-full md:w-80 border border-gray-300 rounded-primea focus:ring-primea-blue focus:border-primea-blue"
              />
            </div>
            
            <select 
              v-model="statusFilter"
              class="px-4 py-2 border border-gray-300 rounded-primea focus:ring-primea-blue focus:border-primea-blue"
            >
              <option value="">Tous les statuts</option>
              <option value="active">Actifs</option>
              <option value="used">Utilisés</option>
              <option value="expired">Expirés</option>
            </select>
          </div>

          <div class="flex items-center gap-2">
            <span class="text-sm text-gray-500">{{ filteredTickets.length }} sur {{ stats.totalTickets }} tickets</span>
          </div>
        </div>
      </div>

      <!-- État de chargement -->
      <div v-if="loading" class="text-center py-16">
        <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-primea-blue mx-auto mb-4"></div>
        <p class="text-gray-500">Chargement de vos tickets...</p>
      </div>

      <!-- Message d'erreur -->
      <div v-else-if="error" class="text-center py-16">
        <ExclamationCircleIcon class="w-16 h-16 text-red-400 mx-auto mb-4" />
        <h3 class="text-xl font-medium text-red-600 mb-2">Erreur</h3>
        <p class="text-gray-500 mb-6">{{ error }}</p>
        <button 
          @click="loadTickets"
          class="inline-flex items-center px-6 py-3 bg-primea-blue text-white rounded-primea hover:bg-primea-yellow hover:text-primea-blue font-semibold transition-all duration-200"
        >
          Réessayer
        </button>
      </div>

      <!-- Liste des tickets -->
      <div v-else class="space-y-6">
        <!-- Billet actif avec événement à venir -->
        <div v-for="ticket in filteredTickets" :key="ticket.id"
             class="bg-white rounded-primea-lg shadow-sm overflow-hidden hover:shadow-md transition-all duration-300">
          <div class="p-4 md:p-6">
            <div class="flex flex-col md:flex-row md:items-start gap-4 md:gap-6">
              <!-- Image de l'événement -->
              <div class="w-full md:w-32 lg:w-48 h-28 md:h-24 lg:h-32 rounded-primea overflow-hidden flex-shrink-0">
                <img
                  :src="ticket.event.image || '/images/default-event.jpg'"
                  :alt="ticket.event.title"
                  class="w-full h-full object-cover"
                />
              </div>

              <!-- Informations du ticket -->
              <div class="flex-1 space-y-3 min-w-0">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                  <div class="min-w-0">
                    <h3 class="text-lg md:text-xl font-bold text-primea-blue font-primea mb-1 truncate">{{ ticket.event.title }}</h3>
                    <div class="flex flex-wrap items-center text-gray-600 text-xs md:text-sm gap-1 mb-2">
                      <span class="flex items-center">
                        <CalendarIcon size="sm" class="mr-1" />
                        {{ formatDate(ticket.event.date) }}
                      </span>
                      <span class="mx-1 hidden sm:inline">•</span>
                      <span class="flex items-center">
                        <ClockIcon class="w-4 h-4 mr-1" />
                        {{ formatTime(ticket.event.date) }}
                      </span>
                    </div>
                    <div class="flex items-center text-gray-600 text-xs md:text-sm">
                      <MapPinIcon class="w-4 h-4 mr-1 flex-shrink-0" />
                      <span class="truncate">{{ ticket.event.venue }}</span>
                    </div>
                  </div>

                  <!-- Statut du ticket -->
                  <div class="flex sm:flex-col items-center sm:items-end gap-2">
                    <span :class="getStatusClass(ticket.status)"
                          class="inline-flex items-center px-2 md:px-3 py-1 rounded-full text-xs font-medium whitespace-nowrap">
                      <component :is="getStatusIcon(ticket.status)" class="w-3 h-3 md:w-4 md:h-4 mr-1" />
                      {{ getStatusText(ticket.status) }}
                    </span>
                    <span class="text-base md:text-lg font-bold text-primea-blue whitespace-nowrap">{{ formatPrice(ticket.price) }} FCFA</span>
                  </div>
                </div>

                <!-- Informations détaillées du ticket -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 md:gap-4 pt-3 md:pt-4 border-t border-gray-200">
                  <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Référence</p>
                    <p class="font-mono text-xs md:text-sm font-medium truncate">{{ ticket.reference }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Type</p>
                    <p class="text-xs md:text-sm font-medium truncate">{{ ticket.type }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Acheté le</p>
                    <p class="text-xs md:text-sm font-medium">{{ formatDate(ticket.purchaseDate) }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">QR Code</p>
                    <p class="text-xs md:text-sm font-medium text-green-600">Généré</p>
                  </div>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex md:flex-col gap-2 md:gap-3 md:w-32 lg:w-40">
                <button
                  @click="viewTicket(ticket)"
                  class="btn-view flex-1 md:w-full px-3 md:px-4 py-2 rounded-lg font-semibold transition-all duration-200 flex items-center justify-center gap-2 text-sm"
                >
                  <EyeIcon class="w-4 h-4" />
                  <span class="hidden sm:inline">Voir le ticket</span>
                  <span class="sm:hidden">Voir</span>
                </button>

                <button
                  v-if="ticket.status === 'active'"
                  @click="downloadTicket(ticket)"
                  class="btn-download flex-1 md:w-full px-3 md:px-4 py-2 rounded-lg font-semibold transition-all duration-200 flex items-center justify-center gap-2 text-sm"
                >
                  <ArrowDownTrayIcon class="w-4 h-4" />
                  <span class="hidden sm:inline">Télécharger</span>
                  <span class="sm:hidden">PDF</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <Pagination
          v-if="pagination.last_page > 1"
          :current-page="pagination.current_page"
          :total-pages="pagination.last_page"
          @page-change="changePage"
        />

        <!-- État vide -->
        <div v-if="!loading && !error && filteredTickets.length === 0" class="text-center py-16">
          <TicketIcon class="w-16 h-16 text-gray-300 mx-auto mb-4" />
          <h3 class="text-xl font-medium text-gray-500 mb-2">Aucun ticket trouvé</h3>
          <p class="text-gray-400 mb-6">
            {{ searchQuery || statusFilter ? 'Essayez de modifier vos filtres' : 'Vous n\'avez pas encore de tickets' }}
          </p>
          <router-link 
            v-if="!searchQuery && !statusFilter"
            :to="{ name: 'events' }"
            class="inline-flex items-center px-6 py-3 bg-primea-blue text-white rounded-primea hover:bg-primea-yellow hover:text-primea-blue font-semibold transition-all duration-200"
          >
            <MagnifyingGlassIcon class="w-5 h-5 mr-2" />
            Découvrir des événements
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { ticketStatusLabel, ticketStatusBadgeClass } from '../../utils/status'
import CalendarIcon from '../../components/icons/CalendarIcon.vue'
import Pagination from '../../components/Pagination.vue'
import { ticketApiService } from '../../services/api.js'
import { 
  TicketIcon,
  CheckCircleIcon,
  ClockIcon,
  MagnifyingGlassIcon,
  MapPinIcon,
  EyeIcon,
  ArrowDownTrayIcon,
  ExclamationCircleIcon,
  XCircleIcon
} from '@heroicons/vue/24/outline'

export default {
  name: 'MyTickets',
  components: {
    CalendarIcon,
    Pagination,
    TicketIcon,
    CheckCircleIcon,
    ClockIcon,
    MagnifyingGlassIcon,
    MapPinIcon,
    EyeIcon,
    ArrowDownTrayIcon,
    ExclamationCircleIcon,
    XCircleIcon
  },
  setup() {
    const router = useRouter()
    const searchQuery = ref('')
    const statusFilter = ref('')
    const loading = ref(false)
    const error = ref(null)

    // Données réelles depuis l'API
    const orders = ref([])
    const tickets = ref([])

    // Pagination renvoyée par l'API (/api/v1/orders pagine déjà côté serveur :
    // une « page » correspond donc à un lot de commandes payées)
    const pagination = ref({
      current_page: 1,
      last_page: 1,
      per_page: 10,
      total: 0
    })

    // Statistiques calculées par l'API sur l'ensemble des tickets (pas juste la page)
    const apiStats = ref({
      total_tickets: 0,
      active_tickets: 0,
      expired_tickets: 0,
      upcoming_events: 0
    })

    // Paramètres envoyés à l'API : page courante + recherche (appliquée côté
    // serveur pour qu'elle porte sur TOUS les tickets, pas seulement la page).
    // On ne demande que les commandes payées, qui sont les seules affichées ici.
    const buildParams = () => {
      const params = {
        page: pagination.value.current_page,
        per_page: pagination.value.per_page,
        status: 'paid'
      }

      if (searchQuery.value) params.search = searchQuery.value

      return params
    }

    // Charger les données depuis l'API
    const loadTickets = async () => {
      try {
        loading.value = true
        error.value = null
        const response = await ticketApiService.getMyTickets(buildParams())
        orders.value = response.data.orders || []

        if (response.data.pagination) {
          pagination.value = { ...pagination.value, ...response.data.pagination }
        }

        if (response.data.stats) {
          apiStats.value = { ...apiStats.value, ...response.data.stats }
        }
        
        // Transformer les achats en tickets pour l'affichage
        // Ne garder que les commandes payées
        tickets.value = orders.value
          .filter(order => order.status === 'paid' || order.status === 'completed')
          .flatMap(order => {
            if (!order.event || !order.tickets) return []

            return order.tickets.map((ticket, index) => ({
              id: ticket.id,
              code: ticket.code,
              reference: ticket.code,
              event: {
                title: order.event.title,
                slug: order.event.slug,
                date: order.schedule?.starts_at ? new Date(order.schedule.starts_at.replace(/(\d{2})\/(\d{2})\/(\d{4}) (\d{2}):(\d{2}):(\d{2})/, '$3-$2-$1T$4:$5:$6')) : new Date(),
                venue: [order.event.venue_name, order.event.venue_city].filter(v => v && v !== 'null').join(', ') || 'Lieu à définir',
                image: order.event.image || '/images/logo.png?v=3'
              },
              type: ticket.ticket_type?.name || 'Standard',
              price: ticket.ticket_type?.price || (order.total_amount / order.tickets_count),
              status: order.status === 'paid' || order.status === 'completed' ? 'active' : order.status === 'cancelled' ? 'expired' : 'active',
              purchaseDate: new Date(order.created_at.replace(/(\d{2})\/(\d{2})\/(\d{4}) (\d{2}):(\d{2}):(\d{2})/, '$3-$2-$1T$4:$5:$6')),
              orderId: order.id
            }))
          })
      } catch (err) {
        console.error('Erreur lors du chargement des tickets:', err)
        error.value = 'Impossible de charger vos tickets'
        pagination.value = { ...pagination.value, current_page: 1, last_page: 1, total: 1 }
        // Garder les données de démonstration en cas d'erreur
        tickets.value = [
          {
            id: 1,
            reference: 'TK-2025-001',
            event: {
              title: "L'OISEAU RARE",
              date: new Date('2025-12-25T20:00:00'),
              venue: 'Entre Nous Bar, Libreville',
              image: '/images/event-1.jpg'
            },
            type: 'Standard',
            price: 10000,
            status: 'active',
            purchaseDate: new Date('2025-09-10T14:30:00')
          }
        ]
      } finally {
        loading.value = false
      }
    }

    // Charger les données au montage du composant
    onMounted(() => {
      loadTickets()
    })

    const stats = computed(() => ({
      totalTickets: apiStats.value.total_tickets,
      activeTickets: apiStats.value.active_tickets,
      upcomingEvents: apiStats.value.upcoming_events,
      expiredTickets: apiStats.value.expired_tickets
    }))

    // La recherche est envoyée à l'API : il ne reste ici que le filtre de
    // statut, dérivé de l'état de la commande, appliqué aux tickets de la page.
    const filteredTickets = computed(() => {
      return tickets.value.filter(ticket => {
        return !statusFilter.value || ticket.status === statusFilter.value
      })
    })

    // Changement de page : la recherche et le filtre courants sont conservés
    const changePage = (page) => {
      if (page === pagination.value.current_page) return
      pagination.value.current_page = page
      loadTickets()
      window.scrollTo({ top: 0, behavior: 'smooth' })
    }

    // Tout changement de filtre ramène à la première page
    const resetAndReload = () => {
      pagination.value.current_page = 1
      loadTickets()
    }

    let searchTimer = null
    watch(searchQuery, () => {
      clearTimeout(searchTimer)
      searchTimer = setTimeout(resetAndReload, 400)
    })

    watch(statusFilter, resetAndReload)

    const formatDate = (date) => {
      return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'long',
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

    const getStatusClass = (status) => ticketStatusBadgeClass(status)

    const getStatusIcon = (status) => {
      const icons = {
        active: 'CheckCircleIcon',
        used: 'CheckCircleIcon',
        expired: 'XCircleIcon'
      }
      return icons[status] || 'ExclamationCircleIcon'
    }

    const getStatusText = (status) => ticketStatusLabel(status)

    const viewTicket = (ticket) => {
      router.push(`/ticket/${ticket.code}`)
    }

    const downloadTicket = (ticket) => {
      // Télécharger directement le PDF
      try {
        window.open(`/api/v1/tickets/${ticket.code}/pdf`, '_blank')
      } catch (err) {
        console.error('Erreur lors du téléchargement du ticket:', err)
      }
    }

    return {
      searchQuery,
      statusFilter,
      loading,
      error,
      orders,
      tickets,
      stats,
      filteredTickets,
      pagination,
      changePage,
      formatDate,
      formatTime,
      formatPrice,
      getStatusClass,
      getStatusIcon,
      getStatusText,
      viewTicket,
      downloadTicket,
      loadTickets
    }
  }
}
</script>

<style scoped>
.my-tickets {
  background-color: #f8fafc;
}

.font-primea {
  font-family: 'Inter', 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.text-primea-blue {
  color: #272d63;
}

.bg-primea-blue {
  background-color: #272d63;
}

.text-primea-yellow {
  color: #fab511;
}

.bg-primea-yellow {
  background-color: #fab511;
}

.hover\:bg-primea-yellow:hover {
  background-color: #fab511;
}

.hover\:text-primea-blue:hover {
  color: #272d63;
}

.hover\:bg-primea-blue:hover {
  background-color: #272d63;
}

.border-primea-blue {
  border-color: #272d63;
}

.rounded-primea {
  border-radius: 12px;
}

.rounded-primea-lg {
  border-radius: 16px;
}

/* Boutons d'action */
.btn-view {
  background-color: #272d63;
  color: white;
}

.btn-view:hover {
  background-color: #fab511;
  color: #272d63;
}

.btn-download {
  border: 2px solid #fab511;
  background-color: white;
  color: #fab511;
}

.btn-download:hover {
  background-color: #fab511;
  color: #272d63;
}
</style>