<template>
  <div class="my-tickets min-h-screen bg-gray-50 font-primea">
    <div class="max-w-7xl mx-auto">

      <!-- Filtres et statistiques -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-primea-lg shadow-sm p-6 border-l-4 border-primea-blue">
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

        <div class="bg-white rounded-primea-lg shadow-sm p-6 border-l-4 border-green-500">
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

        <div class="bg-white rounded-primea-lg shadow-sm p-6 border-l-4 border-primea-yellow">
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

        <div class="bg-white rounded-primea-lg shadow-sm p-6 border-l-4 border-gray-400">
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
            <span class="text-sm text-gray-500">{{ filteredTickets.length }} tickets</span>
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
          <div class="p-6">
            <div class="flex flex-col lg:flex-row lg:items-center gap-6">
              <!-- Image de l'événement -->
              <div class="w-full lg:w-48 h-32 rounded-primea overflow-hidden flex-shrink-0">
                <img 
                  :src="ticket.event.image || '/images/default-event.jpg'" 
                  :alt="ticket.event.title"
                  class="w-full h-full object-cover"
                />
              </div>

              <!-- Informations du ticket -->
              <div class="flex-1 space-y-3">
                <div class="flex items-start justify-between">
                  <div>
                    <h3 class="text-xl font-bold text-primea-blue font-primea mb-1">{{ ticket.event.title }}</h3>
                    <div class="flex items-center text-gray-600 text-sm mb-2">
                      <CalendarIcon size="sm" class="mr-1" />
                      {{ formatDate(ticket.event.date) }}
                      <span class="mx-2">•</span>
                      <ClockIcon class="w-4 h-4 mr-1" />
                      {{ formatTime(ticket.event.date) }}
                    </div>
                    <div class="flex items-center text-gray-600 text-sm">
                      <MapPinIcon class="w-4 h-4 mr-1" />
                      {{ ticket.event.venue }}
                    </div>
                  </div>

                  <!-- Statut du ticket -->
                  <div class="flex flex-col items-end gap-2">
                    <span :class="getStatusClass(ticket.status)" 
                          class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium">
                      <component :is="getStatusIcon(ticket.status)" class="w-4 h-4 mr-1" />
                      {{ getStatusText(ticket.status) }}
                    </span>
                    <span class="text-lg font-bold text-primea-blue">{{ formatPrice(ticket.price) }} FCFA</span>
                  </div>
                </div>

                <!-- Informations détaillées du ticket -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-4 border-t border-gray-200">
                  <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Référence</p>
                    <p class="font-mono text-sm font-medium">{{ ticket.reference }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Type</p>
                    <p class="text-sm font-medium">{{ ticket.type }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Acheté le</p>
                    <p class="text-sm font-medium">{{ formatDate(ticket.purchaseDate) }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">QR Code</p>
                    <p class="text-sm font-medium text-green-600">✓ Généré</p>
                  </div>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex lg:flex-col gap-3 lg:w-40">
                <button 
                  @click="viewTicket(ticket)"
                  class="flex-1 lg:w-full bg-primea-blue text-white px-4 py-2 rounded-primea hover:bg-primea-yellow hover:text-primea-blue font-semibold transition-all duration-200 flex items-center justify-center gap-2"
                >
                  <EyeIcon class="w-4 h-4" />
                  Voir le ticket
                </button>
                
                <button 
                  v-if="ticket.status === 'active'"
                  @click="downloadTicket(ticket)"
                  class="flex-1 lg:w-full border-2 border-primea-blue text-primea-blue px-4 py-2 rounded-primea hover:bg-primea-blue hover:text-white font-semibold transition-all duration-200 flex items-center justify-center gap-2"
                >
                  <ArrowDownTrayIcon class="w-4 h-4" />
                  Télécharger
                </button>
              </div>
            </div>
          </div>
        </div>

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
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import CalendarIcon from '../../components/icons/CalendarIcon.vue'
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

    // Charger les données depuis l'API
    const loadTickets = async () => {
      try {
        loading.value = true
        error.value = null
        const response = await ticketApiService.getMyTickets()
        orders.value = response.data.orders || []
        
        // Transformer les achats en tickets pour l'affichage
        tickets.value = orders.value.flatMap(order => {
          if (!order.event || !order.tickets) return []

          return order.tickets.map((ticket, index) => ({
            id: ticket.id,
            code: ticket.code,
            reference: ticket.code,
            event: {
              title: order.event.title,
              slug: order.event.slug,
              date: order.schedule?.starts_at ? new Date(order.schedule.starts_at.replace(/(\d{2})\/(\d{2})\/(\d{4}) (\d{2}):(\d{2}):(\d{2})/, '$3-$2-$1T$4:$5:$6')) : new Date(),
              venue: `${order.event.venue_name}${order.event.venue_city ? ', ' + order.event.venue_city : ''}`,
              image: order.event.image || '/images/logo.png'
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
      totalTickets: tickets.value.length,
      activeTickets: tickets.value.filter(t => t.status === 'active').length,
      upcomingEvents: tickets.value.filter(t => t.status === 'active' && new Date(t.event.date) > new Date()).length,
      expiredTickets: tickets.value.filter(t => t.status === 'expired').length
    }))

    const filteredTickets = computed(() => {
      return tickets.value.filter(ticket => {
        const matchesSearch = !searchQuery.value || 
          ticket.event.title.toLowerCase().includes(searchQuery.value.toLowerCase())
        const matchesStatus = !statusFilter.value || ticket.status === statusFilter.value
        return matchesSearch && matchesStatus
      })
    })

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

    const getStatusClass = (status) => {
      const classes = {
        active: 'bg-green-100 text-green-800',
        used: 'bg-blue-100 text-blue-800', 
        expired: 'bg-gray-100 text-gray-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    }

    const getStatusIcon = (status) => {
      const icons = {
        active: 'CheckCircleIcon',
        used: 'CheckCircleIcon',
        expired: 'XCircleIcon'
      }
      return icons[status] || 'ExclamationCircleIcon'
    }

    const getStatusText = (status) => {
      const texts = {
        active: 'Actif',
        used: 'Utilisé',
        expired: 'Expiré'
      }
      return texts[status] || 'Inconnu'
    }

    const viewTicket = (ticket) => {
      router.push(`/ticket/${ticket.code}`)
    }

    const downloadTicket = async (ticket) => {
      try {
        // Ouvrir le PDF dans un nouvel onglet
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
  font-family: 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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
</style>