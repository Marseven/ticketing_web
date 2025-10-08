<template>
  <div class="my-orders min-h-screen bg-gray-50 font-primea">
    <div class="max-w-7xl mx-auto">

      <!-- Statistiques -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-primea-lg shadow-sm p-6 border-l-4 border-primea-blue">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <ClipboardDocumentListIcon class="w-8 h-8 text-primea-blue" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Total achats</p>
              <p class="text-2xl font-bold text-gray-900">{{ stats.totalOrders }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-primea-lg shadow-sm p-6 border-l-4 border-green-500">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <CheckCircleIcon class="w-8 h-8 text-green-500" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Confirmées</p>
              <p class="text-2xl font-bold text-gray-900">{{ stats.confirmedOrders }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-primea-lg shadow-sm p-6 border-l-4 border-primea-yellow">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <CurrencyDollarIcon class="w-8 h-8 text-primea-yellow" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Total dépensé</p>
              <p class="text-2xl font-bold text-gray-900">{{ formatPrice(stats.totalSpent) }} FCFA</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-primea-lg shadow-sm p-6 border-l-4 border-orange-500">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <ClockIcon class="w-8 h-8 text-orange-500" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">En attente</p>
              <p class="text-2xl font-bold text-gray-900">{{ stats.pendingOrders }}</p>
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
                placeholder="Rechercher par référence ou événement..."
                class="pl-10 pr-4 py-2 w-full md:w-80 border border-gray-300 rounded-primea focus:ring-primea-blue focus:border-primea-blue"
              />
            </div>
            
            <select 
              v-model="statusFilter"
              class="px-4 py-2 border border-gray-300 rounded-primea focus:ring-primea-blue focus:border-primea-blue"
            >
              <option value="">Tous les statuts</option>
              <option value="confirmed">Confirmées</option>
              <option value="pending">En attente</option>
              <option value="cancelled">Annulées</option>
              <option value="refunded">Remboursées</option>
            </select>

            <select 
              v-model="periodFilter"
              class="px-4 py-2 border border-gray-300 rounded-primea focus:ring-primea-blue focus:border-primea-blue"
            >
              <option value="">Toute période</option>
              <option value="7days">7 derniers jours</option>
              <option value="30days">30 derniers jours</option>
              <option value="3months">3 derniers mois</option>
              <option value="year">Cette année</option>
            </select>
          </div>

          <div class="flex items-center gap-2">
            <span class="text-sm text-gray-500">{{ filteredOrders.length }} achats</span>
          </div>
        </div>
      </div>

      <!-- État de chargement -->
      <div v-if="loading" class="text-center py-16">
        <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-primea-blue mx-auto mb-4"></div>
        <p class="text-gray-500">Chargement de vos achats...</p>
      </div>

      <!-- Message d'erreur -->
      <div v-else-if="error" class="text-center py-16">
        <ExclamationTriangleIcon class="w-16 h-16 text-red-400 mx-auto mb-4" />
        <h3 class="text-xl font-medium text-red-600 mb-2">Erreur</h3>
        <p class="text-gray-500 mb-6">{{ error }}</p>
        <button 
          @click="loadOrders"
          class="inline-flex items-center px-6 py-3 bg-primea-blue text-white rounded-primea hover:bg-primea-yellow hover:text-primea-blue font-semibold transition-all duration-200"
        >
          Réessayer
        </button>
      </div>

      <!-- Liste des achats -->
      <div v-else class="space-y-6">
        <div v-for="order in filteredOrders" :key="order.id" 
             class="bg-white rounded-primea-lg shadow-sm overflow-hidden hover:shadow-md transition-all duration-300">
          
          <!-- En-tête d'achat -->
          <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
              <div class="flex items-center gap-4">
                <div>
                  <p class="text-sm text-gray-500">Achat</p>
                  <p class="font-mono text-lg font-bold text-primea-blue">{{ order.reference }}</p>
                </div>
                <div class="hidden md:block w-px h-12 bg-gray-300"></div>
                <div>
                  <p class="text-sm text-gray-500">Date</p>
                  <p class="font-medium">{{ formatDate(order.orderDate) }}</p>
                </div>
                <div class="hidden md:block w-px h-12 bg-gray-300"></div>
                <div>
                  <p class="text-sm text-gray-500">Total</p>
                  <p class="font-bold text-lg">{{ formatPrice(order.total) }} FCFA</p>
                </div>
              </div>

              <div class="flex items-center gap-3">
                <span :class="getOrderStatusClass(order.status)" 
                      class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium">
                  <component :is="getOrderStatusIcon(order.status)" class="w-4 h-4 mr-1" />
                  {{ getOrderStatusText(order.status) }}
                </span>
                <button 
                  @click="toggleOrderDetails(order.id)"
                  class="text-primea-blue hover:text-primea-yellow transition-colors"
                >
                  <ChevronDownIcon :class="{'rotate-180': order.showDetails}" class="w-5 h-5 transition-transform" />
                </button>
              </div>
            </div>
          </div>

          <!-- Contenu de l'achat -->
          <div class="p-6">
            <!-- Informations de paiement -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
              <div>
                <p class="text-sm text-gray-500 mb-1">Méthode de paiement</p>
                <div class="flex items-center gap-2">
                  <component :is="getPaymentIcon(order.paymentMethod)" class="w-5 h-5" />
                  <span class="font-medium">{{ getPaymentMethodText(order.paymentMethod) }}</span>
                </div>
              </div>
              <div>
                <p class="text-sm text-gray-500 mb-1">Transaction</p>
                <p class="font-mono text-sm">{{ order.transactionId }}</p>
              </div>
              <div v-if="order.status === 'confirmed'">
                <p class="text-sm text-gray-500 mb-1">Billets générés</p>
                <p class="text-green-600 font-medium">✓ {{ order.tickets.length }} tickets disponibles</p>
              </div>
            </div>

            <!-- Liste des tickets dans l'achat -->
            <div class="space-y-4">
              <h4 class="font-semibold text-gray-900 border-b border-gray-200 pb-2">
                Billets achetés ({{ order.tickets.length }})
              </h4>
              
              <div v-for="ticket in order.tickets" :key="ticket.id" 
                   class="flex flex-col md:flex-row md:items-center gap-4 p-4 bg-gray-50 rounded-primea">
                
                <!-- Image de l'événement -->
                <div class="w-full md:w-24 h-16 rounded overflow-hidden flex-shrink-0">
                  <img
                    :src="ticket.event.image || '/images/logo.png'"
                    :alt="ticket.event.title"
                    class="w-full h-full object-cover"
                  />
                </div>

                <!-- Détails du ticket -->
                <div class="flex-1">
                  <h5 class="font-bold text-primea-blue">{{ ticket.event.title }}</h5>
                  <div class="flex items-center text-sm text-gray-600 mt-1">
                    <CalendarIcon size="sm" class="mr-1" />
                    {{ formatDate(ticket.event.date) }}
                    <span class="mx-2">•</span>
                    <ClockIcon class="w-4 h-4 mr-1" />
                    {{ formatTime(ticket.event.date) }}
                  </div>
                  <div class="flex items-center text-sm text-gray-600 mt-1">
                    <MapPinIcon class="w-4 h-4 mr-1" />
                    {{ ticket.event.venue }}
                  </div>
                </div>

                <!-- Détails du type et prix -->
                <div class="text-right">
                  <p class="font-medium">{{ ticket.type }}</p>
                  <p class="text-sm text-gray-600">Qté: {{ ticket.quantity }}</p>
                  <p class="font-bold text-primea-blue">{{ formatPrice(ticket.price * ticket.quantity) }} FCFA</p>
                </div>

                <!-- Actions -->
                <div class="flex gap-2">
                  <button 
                    v-if="order.status === 'confirmed'"
                    @click="viewTicket(ticket)"
                    class="px-3 py-2 bg-primea-blue text-white rounded text-sm hover:bg-primea-yellow hover:text-primea-blue transition-colors"
                  >
                    <EyeIcon class="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>

            <!-- Actions sur l'achat -->
            <div class="flex flex-col md:flex-row gap-3 mt-6 pt-6 border-t border-gray-200">
              <button 
                v-if="order.status === 'confirmed'"
                @click="downloadOrderReceipt(order)"
                class="px-4 py-2 bg-primea-blue text-white rounded-primea hover:bg-primea-yellow hover:text-primea-blue font-medium transition-all duration-200 flex items-center justify-center gap-2"
              >
                <ArrowDownTrayIcon class="w-4 h-4" />
                Télécharger la facture
              </button>
              
              <button 
                v-if="order.status === 'pending'"
                @click="retryPayment(order)"
                class="px-4 py-2 border-2 border-primea-blue text-primea-blue rounded-primea hover:bg-primea-blue hover:text-white font-medium transition-all duration-200 flex items-center justify-center gap-2"
              >
                <CreditCardIcon class="w-4 h-4" />
                Finaliser le paiement
              </button>

              <button 
                v-if="order.status === 'confirmed' && canCancelOrder(order)"
                @click="requestCancellation(order)"
                class="px-4 py-2 border-2 border-red-500 text-red-500 rounded-primea hover:bg-red-500 hover:text-white font-medium transition-all duration-200 flex items-center justify-center gap-2"
              >
                <XCircleIcon class="w-4 h-4" />
                Demander l'annulation
              </button>
            </div>
          </div>
        </div>

        <!-- État vide -->
        <div v-if="!loading && !error && filteredOrders.length === 0" class="text-center py-16">
          <ClipboardDocumentListIcon class="w-16 h-16 text-gray-300 mx-auto mb-4" />
          <h3 class="text-xl font-medium text-gray-500 mb-2">Aucun achat trouvé</h3>
          <p class="text-gray-400 mb-6">
            {{ searchQuery || statusFilter || periodFilter ? 'Essayez de modifier vos filtres' : 'Vous n\'avez pas encore effectué d\'achat' }}
          </p>
          <router-link 
            v-if="!searchQuery && !statusFilter && !periodFilter"
            :to="{ name: 'events' }"
            class="inline-flex items-center px-6 py-3 bg-primea-blue text-white rounded-primea hover:bg-primea-yellow hover:text-primea-blue font-semibold transition-all duration-200"
          >
            <ShoppingCartIcon class="w-5 h-5 mr-2" />
            Acheter des tickets
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
  ClipboardDocumentListIcon,
  CheckCircleIcon,
  ClockIcon,
  MagnifyingGlassIcon,
  CurrencyDollarIcon,
  MapPinIcon,
  EyeIcon,
  ArrowDownTrayIcon,
  CreditCardIcon,
  XCircleIcon,
  ChevronDownIcon,
  ShoppingCartIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'

export default {
  name: 'MyOrders',
  components: {
    CalendarIcon,
    ClipboardDocumentListIcon,
    CheckCircleIcon,
    ClockIcon,
    MagnifyingGlassIcon,
    CurrencyDollarIcon,
    MapPinIcon,
    EyeIcon,
    ArrowDownTrayIcon,
    CreditCardIcon,
    XCircleIcon,
    ChevronDownIcon,
    ShoppingCartIcon,
    ExclamationTriangleIcon
  },
  setup() {
    const router = useRouter()
    const searchQuery = ref('')
    const statusFilter = ref('')
    const periodFilter = ref('')
    const loading = ref(false)
    const error = ref(null)

    // Données réelles depuis l'API
    const orders = ref([])

    // Charger les achats depuis l'API
    const loadOrders = async () => {
      try {
        loading.value = true
        error.value = null
        const response = await ticketApiService.getMyTickets()
        const apiOrders = response.data.orders || []
        
        // Transformer les achats de l'API vers le format attendu par le composant
        orders.value = apiOrders.map(order => ({
          id: order.id,
          reference: order.order_number,
          orderDate: new Date(order.created_at.replace(/(\d{2})\/(\d{2})\/(\d{4}) (\d{2}):(\d{2}):(\d{2})/, '$3-$2-$1T$4:$5:$6')),
          status: order.status === 'paid' ? 'confirmed' : order.status,
          paymentMethod: 'airtel', // Par défaut
          transactionId: `TXN-${order.order_number}`,
          total: order.total_amount,
          showDetails: false,
          tickets: [{
            id: order.id,
            event: {
              title: order.event?.title || 'Événement sans titre',
              date: order.schedule?.starts_at ? new Date(order.schedule.starts_at.replace(/(\d{2})\/(\d{2})\/(\d{4}) (\d{2}):(\d{2}):(\d{2})/, '$3-$2-$1T$4:$5:$6')) : new Date(),
              venue: [order.event?.venue_name, order.event?.venue_city].filter(v => v && v !== 'null').join(', ') || 'Lieu à définir',
              image: order.event?.image || '/images/logo.png'
            },
            type: 'Standard',
            price: order.total_amount / (order.tickets_count || 1),
            quantity: order.tickets_count || 1
          }]
        }))
      } catch (err) {
        console.error('Erreur lors du chargement des achats:', err)
        error.value = 'Impossible de charger vos achats'
        // Garder les données de démonstration en cas d'erreur
        orders.value = [
          {
            id: 1,
            reference: 'CMD-2025-001',
            orderDate: new Date('2025-09-10T14:30:00'),
            status: 'confirmed',
            paymentMethod: 'airtel',
            transactionId: 'TXN-AM-123456789',
            total: 35000,
            showDetails: false,
            tickets: [
              {
                id: 1,
                event: {
                  title: "L'OISEAU RARE",
                  date: new Date('2025-12-25T20:00:00'),
                  venue: 'Entre Nous Bar, Libreville',
                  image: '/images/event-1.jpg'
                },
                type: 'Standard',
                price: 10000,
                quantity: 2
              }
            ]
          }
        ]
      } finally {
        loading.value = false
      }
    }

    // Charger les données au montage du composant
    onMounted(() => {
      loadOrders()
    })

    const stats = computed(() => ({
      totalOrders: orders.value.length,
      confirmedOrders: orders.value.filter(o => o.status === 'confirmed').length,
      totalSpent: orders.value.filter(o => o.status === 'confirmed').reduce((sum, o) => sum + o.total, 0),
      pendingOrders: orders.value.filter(o => o.status === 'pending').length
    }))

    const filteredOrders = computed(() => {
      return orders.value.filter(order => {
        const matchesSearch = !searchQuery.value || 
          order.reference.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
          order.tickets.some(t => t.event.title.toLowerCase().includes(searchQuery.value.toLowerCase()))
        
        const matchesStatus = !statusFilter.value || order.status === statusFilter.value
        
        let matchesPeriod = true
        if (periodFilter.value) {
          const now = new Date()
          const orderDate = order.orderDate
          switch (periodFilter.value) {
            case '7days':
              matchesPeriod = (now - orderDate) <= 7 * 24 * 60 * 60 * 1000
              break
            case '30days':
              matchesPeriod = (now - orderDate) <= 30 * 24 * 60 * 60 * 1000
              break
            case '3months':
              matchesPeriod = (now - orderDate) <= 90 * 24 * 60 * 60 * 1000
              break
            case 'year':
              matchesPeriod = orderDate.getFullYear() === now.getFullYear()
              break
          }
        }
        
        return matchesSearch && matchesStatus && matchesPeriod
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

    const getOrderStatusClass = (status) => {
      const classes = {
        confirmed: 'bg-green-100 text-green-800',
        pending: 'bg-yellow-100 text-yellow-800',
        cancelled: 'bg-red-100 text-red-800',
        refunded: 'bg-blue-100 text-blue-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    }

    const getOrderStatusIcon = (status) => {
      const icons = {
        confirmed: 'CheckCircleIcon',
        pending: 'ClockIcon',
        cancelled: 'XCircleIcon',
        refunded: 'CurrencyDollarIcon'
      }
      return icons[status] || 'ExclamationTriangleIcon'
    }

    const getOrderStatusText = (status) => {
      const texts = {
        confirmed: 'Confirmée',
        pending: 'En attente',
        cancelled: 'Annulée',
        refunded: 'Remboursée'
      }
      return texts[status] || 'Inconnu'
    }

    const getPaymentIcon = (method) => {
      // Retourne l'icône appropriée pour la méthode de paiement
      return 'CreditCardIcon' // Icône générique pour tous les moyens de paiement
    }

    const getPaymentMethodText = (method) => {
      const methods = {
        airtel: 'Airtel Money',
        moov: 'Moov Money',
        visa: 'Carte Visa',
        mastercard: 'Carte Mastercard'
      }
      return methods[method] || 'Autre'
    }

    const toggleOrderDetails = (orderId) => {
      const order = orders.value.find(o => o.id === orderId)
      if (order) {
        order.showDetails = !order.showDetails
      }
    }

    const viewTicket = (ticket) => {
      router.push(`/ticket/${ticket.id}`)
    }

    const downloadOrderReceipt = (order) => {
      console.log('Télécharger la facture:', order.reference)
      // Logique de téléchargement de facture
    }

    const retryPayment = (order) => {
      console.log('Relancer le paiement:', order.reference)
      // Logique pour relancer le paiement
    }

    const requestCancellation = (order) => {
      console.log('Demander l\'annulation:', order.reference)
      // Logique de demande d'annulation
    }

    const canCancelOrder = (order) => {
      // Logique pour déterminer si un achat peut être annulé
      // Par exemple, moins de 24h avant l'événement
      const now = new Date()
      const eventDate = new Date(Math.min(...order.tickets.map(t => t.event.date)))
      const hoursUntilEvent = (eventDate - now) / (1000 * 60 * 60)
      return hoursUntilEvent > 24
    }

    return {
      searchQuery,
      statusFilter,
      periodFilter,
      loading,
      error,
      orders,
      stats,
      filteredOrders,
      formatDate,
      formatTime,
      formatPrice,
      getOrderStatusClass,
      getOrderStatusIcon,
      getOrderStatusText,
      getPaymentIcon,
      getPaymentMethodText,
      toggleOrderDetails,
      viewTicket,
      downloadOrderReceipt,
      retryPayment,
      requestCancellation,
      canCancelOrder,
      loadOrders
    }
  }
}
</script>

<style scoped>
.my-orders {
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