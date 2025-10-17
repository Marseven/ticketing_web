<template>
  <div class="ticket-download-page min-h-screen bg-gray-50">

    <!-- Mobile Title -->
    <div class="md:hidden px-4 pt-6 pb-4">
      <h1 class="text-xl font-bold text-blue-900 text-center">Votre ticket électronique</h1>
    </div>

    <!-- Desktop Header -->
    <div class="hidden md:block bg-white shadow-sm">
      <div class="container mx-auto px-4 py-8 md:py-12">
        <div class="max-w-6xl mx-auto">
          <div class="text-center">
            <img src="/images/logo.png" alt="Logo" class="h-16 mx-auto mb-6" />
            <h1 class="text-3xl md:text-4xl font-bold text-blue-900 mb-4">Votre ticket électronique</h1>
            <p class="text-lg text-gray-600">Téléchargez ou capturez votre ticket pour l'événement</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-6 md:py-12">
      <div class="max-w-6xl mx-auto">

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-12">
          <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-900"></div>
          <p class="mt-4 text-gray-600">Chargement du ticket...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="bg-red-50 border-2 border-red-200 rounded-2xl md:rounded-3xl p-6 text-center max-w-md mx-auto">
          <div class="flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-red-800 mb-2">Ticket introuvable</h3>
          <p class="text-red-600 mb-4">{{ error }}</p>
          <button @click="loadTicket" class="bg-blue-900 text-white px-6 py-3 rounded-xl hover:bg-yellow-500 hover:text-blue-900 transition-colors font-semibold">
            Réessayer
          </button>
        </div>

        <!-- Ticket Content -->
        <template v-else-if="ticket">

          <!-- Mobile Layout -->
          <div class="md:hidden max-w-md mx-auto">
            <!-- Instructions -->
            <div class="text-center mb-6">
              <h2 class="text-base font-medium text-gray-700 mb-2">
                Téléchargez ou capturez l'écran
              </h2>
              <p class="text-sm text-gray-600">
                pour conserver votre ticket
              </p>
            </div>

            <!-- Section Title -->
            <div class="text-center mb-6">
              <h2 class="text-lg font-bold text-blue-900">VOTRE TICKET</h2>
            </div>

            <!-- Ticket Display -->
            <div class="mb-6">
              <TicketComponent :ticket="ticket" size="small" />
            </div>

            <!-- Download Button -->
            <div class="mb-8">
              <button
                @click="downloadTicket"
                class="w-full bg-yellow-500 text-blue-900 py-4 px-6 rounded-xl font-bold transition-all duration-200 shadow-lg hover:bg-yellow-600 transform hover:scale-105"
              >
                Télécharger
              </button>
            </div>

            <!-- Ad Space -->
            <div class="bg-gray-200 rounded-2xl p-8 text-center mb-6">
              <div class="text-xl text-gray-400 font-light mb-4">ESPACE PUB</div>
              <a href="#" class="text-blue-900 text-sm hover:text-yellow-500 font-semibold">En savoir plus...</a>
            </div>
          </div>

          <!-- Desktop Layout -->
          <div class="hidden md:grid md:grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Ticket Column -->
            <div class="lg:col-span-2 flex justify-center items-start">
              <TicketComponent :ticket="ticket" size="large" />
            </div>

            <!-- Instructions & Actions Column -->
            <div class="lg:col-span-1 space-y-6">

              <!-- Instructions Card -->
              <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-blue-900 mb-4">Comment utiliser votre ticket</h3>
                <div class="space-y-4 text-gray-600">
                  <div class="flex items-start gap-3">
                    <div class="rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold bg-blue-900 text-white flex-shrink-0">1</div>
                    <div>
                      <p class="font-semibold text-gray-800">Téléchargez votre ticket</p>
                      <p class="text-sm">Cliquez sur le bouton de téléchargement pour sauvegarder votre ticket en PDF</p>
                    </div>
                  </div>
                  <div class="flex items-start gap-3">
                    <div class="rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold bg-blue-900 text-white flex-shrink-0">2</div>
                    <div>
                      <p class="font-semibold text-gray-800">Présentez le QR code</p>
                      <p class="text-sm">À l'entrée, montrez le QR code pour validation</p>
                    </div>
                  </div>
                  <div class="flex items-start gap-3">
                    <div class="rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold bg-yellow-500 text-blue-900 flex-shrink-0">!</div>
                    <div>
                      <p class="font-semibold text-red-600">Important</p>
                      <p class="text-sm">Ce ticket est personnel et à usage unique. Il ne peut être ni vendu ni donné</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Actions Card -->
              <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-blue-900 mb-4">Actions</h3>
                <div class="space-y-3">
                  <button
                    @click="downloadTicket"
                    class="w-full bg-yellow-500 text-blue-900 py-3 px-6 rounded-xl font-bold transition-all duration-200 shadow-lg hover:bg-yellow-600 transform hover:scale-105 flex items-center justify-center gap-2"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Télécharger le ticket PDF
                  </button>
                  <button
                    @click="goBack"
                    class="w-full border-2 border-blue-900 text-blue-900 py-3 px-6 rounded-xl font-semibold hover:bg-blue-900 hover:text-white transition-all duration-200"
                  >
                    Retour aux événements
                  </button>
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import TicketComponent from '../components/TicketComponent.vue'
import { ticketService } from '../services/api.js'

export default {
  name: 'TicketDownload',
  components: {
    TicketComponent
  },
  setup() {
    const route = useRoute()
    const router = useRouter()

    // Reactive state
    const ticket = ref(null)
    const loading = ref(true)
    const error = ref('')

    // Load ticket data from API
    const loadTicket = async () => {
      try {
        loading.value = true
        error.value = ''

        const ticketCode = route.params.id

        // Use ticket code directly (format: TKT-XXXXXXXX)
        const response = await ticketService.getTicket(ticketCode)

        if (response.data?.ticket) {
          // Transform API data to expected format
          const apiTicket = response.data.ticket

          // Image URL comes from accessor that builds the full URL
          const imageUrl = apiTicket.event.image_url || 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=800'

          // Convert date from French format (dd/mm/yyyy HH:mm:ss) to ISO
          let isoDate = '2025-07-27T20:00:00'
          if (apiTicket.schedule?.starts_at) {
            try {
              // Format: "19/10/2025 07:26:00"
              const [datePart, timePart] = apiTicket.schedule.starts_at.split(' ')
              const [day, month, year] = datePart.split('/')
              isoDate = `${year}-${month}-${day}T${timePart}`
            } catch (e) {
              console.error('Error parsing date:', e)
            }
          }

          ticket.value = {
            id: apiTicket.id,
            reference: apiTicket.code,
            event: {
              id: apiTicket.event.id,
              title: apiTicket.event.title,
              date: isoDate,
              venue_name: apiTicket.event.venue_name || 'Entre Nous Bar',
              image: imageUrl,
              time: apiTicket.schedule?.door_time ?
                    (() => {
                      try {
                        const [datePart, timePart] = apiTicket.schedule.door_time.split(' ')
                        const [hours, minutes] = timePart.split(':')
                        return `${hours}H${minutes}`
                      } catch {
                        return '13H'
                      }
                    })() :
                    '13H'
            },
            ticketType: apiTicket.ticket_type?.name || 'Standard',
            price: apiTicket.ticket_type?.price || 10000,
            qrCode: `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(apiTicket.code)}`,
            status: apiTicket.status,
            buyer_name: apiTicket.buyer?.name,
            buyer_email: apiTicket.buyer?.email,
            issued_at: apiTicket.issued_at,
            used_at: apiTicket.used_at
          }
        }
      } catch (err) {
        console.error('Error loading ticket:', err)
        error.value = err.response?.data?.message || 'Erreur lors du chargement du ticket'

        // Fallback to mock data if API fails
        ticket.value = {
          id: route.params.id,
          reference: 'TKT-2024-ABC123',
          event: {
            title: "L'OISEAU RARE",
            date: '2025-07-27T20:00:00',
            venue_name: 'Entre Nous Bar',
            image: 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=800',
            time: '13H'
          },
          ticketType: 'Standard',
          price: 10000,
          status: 'issued'
        }
      } finally {
        loading.value = false
      }
    }

    // Computed properties
    const formatEventDate = computed(() => {
      if (!ticket.value?.event?.date) return 'DIMANCHE 27 JUILLET 2025'

      const date = new Date(ticket.value.event.date)
      return date.toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
      }).toUpperCase()
    })

    // Methods
    const formatPrice = (price) => {
      if (!price) return '10.000'
      return new Intl.NumberFormat('fr-FR').format(price)
    }

    const downloadTicket = async () => {
      try {
        const ticketCode = route.params.id

        // Open PDF download link in new tab
        window.open(`/api/v1/tickets/${ticketCode}/pdf`, '_blank')
      } catch (err) {
        console.error('Download error:', err)
        alert('Erreur lors du téléchargement du ticket')
      }
    }

    const goBack = () => {
      router.back()
    }

    // Load ticket on component mount
    onMounted(() => {
      loadTicket()
    })

    return {
      ticket,
      loading,
      error,
      formatEventDate,
      formatPrice,
      downloadTicket,
      goBack,
      loadTicket
    }
  }
}
</script>

<style scoped>
/* Animations */
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

/* Transitions */
* {
  transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}

/* Touch-friendly on mobile */
@media (max-width: 768px) {
  input,
  button {
    font-size: 16px; /* Prevents zoom on iOS */
  }
}

/* Container */
.container {
  max-width: 1200px;
}

/* Hover effects */
button:hover {
  transform: translateY(-1px);
}
</style>
