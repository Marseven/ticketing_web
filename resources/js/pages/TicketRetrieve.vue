<template>
  <div class="ticket-retrieve-page relative overflow-hidden min-h-screen bg-white md:bg-transparent">
    <!-- Background Image (Desktop only) -->
    <div class="hidden md:block absolute inset-0">
      <img
        src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80"
        alt="Événements"
        class="w-full h-full object-cover opacity-40"
      />
      <div class="absolute inset-0 bg-blue-900/60"></div>
    </div>

    <!-- Main Content -->
    <div class="relative z-10 min-h-screen md:py-12 px-4 md:px-4 py-8">
      <div class="max-w-2xl mx-auto">

        <!-- Desktop Logo -->
        <div class="hidden md:block text-center mb-8 animate-fade-in">
          <img src="/images/logo_white.png" alt="Logo" class="h-16 mx-auto mb-6" />
        </div>

        <!-- Header -->
        <div class="text-center mb-8 md:mb-12 animate-fade-in">
          <h1 class="text-2xl md:text-3xl lg:text-4xl font-bold text-blue-900 md:text-white mb-3 md:mb-4">
            Vous avez perdu votre ticket ?
          </h1>
          <p class="text-lg md:text-xl text-gray-600 md:text-white/90 mb-4 md:mb-8">
            Ne vous inquiétez pas
          </p>
          <p class="text-sm md:text-base text-gray-600 md:text-white/80">
            Utilisez votre référence du ticket pour retrouver votre ticket
          </p>
        </div>

        <!-- Search Form -->
        <div class="bg-white md:bg-white/95 md:backdrop-blur-sm rounded-2xl md:rounded-3xl shadow-lg md:shadow-2xl p-6 md:p-8">
          <form @submit.prevent="searchTicket" class="space-y-5 md:space-y-6">

            <!-- Reference Field -->
            <div>
              <label for="reference" class="block text-base md:text-lg font-semibold text-blue-900 mb-2 md:mb-3">
                Entrez votre référence du ticket
              </label>
              <input
                type="text"
                id="reference"
                v-model="searchForm.reference"
                placeholder="Ex: TKT-2024-ABC123"
                class="w-full px-4 md:px-6 py-3 md:py-4 text-base md:text-lg border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-yellow-500 focus:border-blue-900 transition-all duration-200 bg-white"
              />
              <p class="mt-2 text-xs md:text-sm text-gray-600">
                Vous pouvez utiliser votre ID de transaction ou le numéro de téléphone utilisé pour l'achat
              </p>
            </div>

            <!-- Alternative Search Methods -->
            <div class="border-t border-gray-200 pt-5 md:pt-6">
              <h4 class="font-semibold text-blue-900 mb-4 text-sm md:text-base">Ou rechercher par :</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <!-- Phone Number -->
                <div>
                  <label for="phone" class="block text-sm font-semibold text-blue-900 mb-2">
                    Numéro de téléphone
                  </label>
                  <PhoneInput
                    v-model="searchForm.phone"
                    placeholder="Numéro de téléphone"
                    :required="false"
                  />
                </div>

                <!-- Email -->
                <div>
                  <label for="email" class="block text-sm font-semibold text-blue-900 mb-2">
                    Adresse email
                  </label>
                  <input
                    type="email"
                    id="email"
                    v-model="searchForm.email"
                    placeholder="votre@email.com"
                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-yellow-500 focus:border-blue-900 transition-all duration-200 bg-white text-base"
                  />
                </div>
              </div>
            </div>

            <!-- Error Message -->
            <div v-if="error" class="bg-red-50 border-2 border-red-200 rounded-xl p-4">
              <div class="flex items-center">
                <svg class="w-5 h-5 text-red-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-red-600 text-sm font-medium">{{ error }}</p>
              </div>
            </div>

            <!-- Success Message -->
            <div v-if="success" class="bg-green-50 border-2 border-green-200 rounded-xl p-4">
              <div class="flex items-center">
                <svg class="w-5 h-5 text-green-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <p class="text-green-600 text-sm font-medium">{{ success }}</p>
              </div>
            </div>

            <!-- Search Button -->
            <button
              type="submit"
              :disabled="loading || (!searchForm.reference && !searchForm.phone && !searchForm.email)"
              class="w-full bg-blue-900 text-white py-4 px-6 rounded-xl text-base md:text-lg font-bold transition-all duration-200 shadow-lg hover:bg-yellow-500 hover:text-blue-900 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-blue-900 disabled:hover:text-white transform hover:scale-105 disabled:transform-none"
            >
              <span v-if="loading" class="flex items-center justify-center">
                <svg class="w-5 md:w-6 h-5 md:h-6 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/>
                  <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" class="opacity-75"/>
                </svg>
                Recherche en cours...
              </span>
              <span v-else class="flex items-center justify-center">
                <svg class="w-5 md:w-6 h-5 md:h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Rechercher mon ticket
              </span>
            </button>

          </form>
        </div>

        <!-- Found Tickets -->
        <div v-if="foundTickets.length > 0" class="mt-8">
          <h3 class="text-lg md:text-xl font-bold text-blue-900 md:text-white mb-4 md:mb-6">Tickets trouvés :</h3>
          <div class="space-y-4">
            <TicketCard
              v-for="ticket in foundTickets"
              :key="ticket.id"
              :ticket="ticket"
              @download="downloadTicket"
              @view="viewTicket"
            />
          </div>
        </div>

        <!-- Help Section -->
        <div class="mt-8 md:mt-12 bg-white md:bg-white/10 md:backdrop-blur-sm rounded-2xl md:rounded-3xl p-6 md:p-8 border border-gray-200 md:border-white/20">
          <h4 class="font-semibold text-blue-900 md:text-white mb-3 md:mb-4 text-base md:text-lg">Besoin d'aide ?</h4>
          <p class="text-sm text-gray-600 md:text-white/80 mb-4 md:mb-6">
            Si vous ne trouvez pas votre ticket, contactez notre support
          </p>
          <div class="flex flex-col sm:flex-row gap-3 md:gap-4">
            <a href="tel:+24107123456" class="flex items-center justify-center px-4 md:px-6 py-3 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-colors duration-200 shadow-lg font-semibold text-sm md:text-base">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
              </svg>
              Appeler le support
            </a>
            <a href="mailto:support@ticketing.com" class="flex items-center justify-center px-4 md:px-6 py-3 border-2 border-gray-300 md:border-white/30 text-blue-900 md:text-white rounded-xl hover:bg-gray-100 md:hover:bg-white/10 transition-all duration-200 shadow-lg font-semibold text-sm md:text-base">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
              </svg>
              Envoyer un email
            </a>
          </div>
        </div>

        <!-- Back to Home -->
        <div class="text-center mt-6 md:mt-8">
          <router-link
            to="/"
            class="text-blue-900 md:text-white/90 hover:text-yellow-500 md:hover:text-yellow-500 font-semibold text-sm transition-colors duration-200 bg-gray-100 md:bg-white/10 md:backdrop-blur-sm px-6 py-3 rounded-xl border border-gray-200 md:border-white/20 hover:border-yellow-500 inline-block"
          >
            ← Retourner à l'accueil
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import TicketCard from '../components/TicketCard.vue'
import PhoneInput from '../components/PhoneInput.vue'
import { ticketService } from '../services/api.js'

export default {
  name: 'TicketRetrieve',
  components: {
    TicketCard,
    PhoneInput
  },
  setup() {
    const router = useRouter()

    // Reactive state
    const loading = ref(false)
    const error = ref('')
    const success = ref('')
    const foundTickets = ref([])

    const searchForm = ref({
      reference: '',
      phone: '',
      email: ''
    })

    // Methods
    const searchTicket = async () => {
      try {
        loading.value = true
        error.value = ''
        success.value = ''

        // Validation
        if (!searchForm.value.reference && !searchForm.value.phone && !searchForm.value.email) {
          throw new Error('Veuillez remplir au moins un champ de recherche')
        }

        // Use API to search tickets
        const response = await ticketService.searchTickets({
          reference: searchForm.value.reference,
          phone: searchForm.value.phone,
          email: searchForm.value.email
        })

        if (response.data.data?.tickets) {
          // Filter only paid tickets
          const paidTickets = response.data.data.tickets.filter(apiTicket =>
            apiTicket.order?.status === 'paid'
          )

          if (paidTickets.length === 0) {
            throw new Error('Aucun ticket payé trouvé. Veuillez d\'abord finaliser le paiement.')
          }

          // Transform API data
          foundTickets.value = paidTickets.map(apiTicket => ({
            id: apiTicket.id,
            code: apiTicket.code,
            reference: apiTicket.code,
            event: {
              id: apiTicket.event.id,
              title: apiTicket.event.title,
              date: apiTicket.schedule?.starts_at || new Date().toISOString(),
              venue_name: apiTicket.event.venue_name,
              image: apiTicket.event.image_url
            },
            ticketType: apiTicket.ticket_type?.name,
            price: apiTicket.ticket_type?.price,
            currency: 'XAF',
            qrCode: `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${apiTicket.code}`,
            status: apiTicket.order?.status || 'unknown',
            buyer_name: apiTicket.buyer?.name,
            buyer_email: apiTicket.buyer?.email,
            issued_at: apiTicket.issued_at,
            used_at: apiTicket.used_at
          }))

          success.value = `${foundTickets.value.length} ticket(s) trouvé(s) !`
        } else if (response.data.data?.ticket) {
          // Single ticket found
          const apiTicket = response.data.data.ticket

          // Check if ticket is paid
          if (apiTicket.order?.status !== 'paid') {
            throw new Error('Ce ticket n\'a pas encore été payé. Veuillez finaliser le paiement.')
          }
          foundTickets.value = [{
            id: apiTicket.id,
            code: apiTicket.code,
            reference: apiTicket.code,
            event: {
              id: apiTicket.event.id,
              title: apiTicket.event.title,
              date: apiTicket.schedule?.starts_at || new Date().toISOString(),
              venue_name: apiTicket.event.venue_name,
              image: apiTicket.event.image_url
            },
            ticketType: apiTicket.ticket_type?.name,
            price: apiTicket.ticket_type?.price,
            currency: 'XAF',
            qrCode: `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${apiTicket.code}`,
            status: apiTicket.order?.status || 'unknown',
            buyer_name: apiTicket.buyer?.name,
            buyer_email: apiTicket.buyer?.email,
            issued_at: apiTicket.issued_at,
            used_at: apiTicket.used_at
          }]

          success.value = '1 ticket trouvé !'
        } else {
          foundTickets.value = []
          throw new Error('Aucun ticket trouvé avec ces informations.')
        }

      } catch (err) {
        console.error('Erreur lors de la recherche:', err)
        // Extract server message if available
        if (err.response?.data?.message) {
          error.value = err.response.data.message
        } else if (err.message) {
          error.value = err.message
        } else {
          error.value = 'Erreur lors de la recherche'
        }
        foundTickets.value = []
      } finally {
        loading.value = false
      }
    }

    const downloadTicket = (ticket) => {
      // Redirect to download page
      router.push(`/ticket/${ticket.code}/download`)
    }

    const viewTicket = (ticket) => {
      // Redirect to view page
      router.push(`/ticket/${ticket.code}`)
    }

    const clearForm = () => {
      searchForm.value = {
        reference: '',
        phone: '',
        email: ''
      }
      error.value = ''
      success.value = ''
      foundTickets.value = []
    }

    return {
      loading,
      error,
      success,
      foundTickets,
      searchForm,
      searchTicket,
      downloadTicket,
      viewTicket,
      clearForm
    }
  }
}
</script>

<style scoped>
/* Animations */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fadeIn 0.8s ease-out;
}

/* Focus states */
input:focus {
  outline: none;
}

/* Smooth transitions */
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
</style>
