<template>
  <div class="ticket-download-page min-h-screen bg-gray-50">

    <!-- Mobile Header -->
    <div class="md:hidden bg-blue-900 px-4 py-6 mb-6">
      <div class="flex items-center justify-between mb-4">
        <button @click="goBack" class="text-white hover:text-yellow-500 transition-colors">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>
        <img src="/images/logo_white.png" alt="Logo" class="h-10" />
        <div class="w-6"></div>
      </div>
      <h1 class="text-xl font-bold text-white text-center">Votre ticket électronique</h1>
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

            <!-- Footer with Social Icons -->
            <div class="text-center">
              <img src="/images/logo.png" alt="Logo" class="h-8 mx-auto mb-4" />
              <div class="flex justify-center space-x-4">
                <a href="#" class="text-green-500 hover:text-green-600 transition-colors">
                  <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"/>
                  </svg>
                </a>
                <a href="#" class="text-blue-600 hover:text-blue-700 transition-colors">
                  <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"/>
                  </svg>
                </a>
                <a href="#" class="text-pink-500 hover:text-pink-600 transition-colors">
                  <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                  </svg>
                </a>
                <a href="#" class="text-gray-800 hover:text-gray-900 transition-colors">
                  <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                  </svg>
                </a>
              </div>
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
