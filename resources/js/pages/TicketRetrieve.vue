<template>
  <div class="ticket-retrieve-page font-primea relative overflow-hidden min-h-screen">
    <!-- Image de fond avec overlay -->
    <div class="absolute inset-0">
      <img 
        src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80" 
        alt="Événements" 
        class="w-full h-full object-cover opacity-40" 
      />
      <div class="absolute inset-0 bg-primea-blue/60"></div>
    </div>

    <!-- Contenu principal -->
    <div class="relative z-10 min-h-screen py-12 px-4">
      <div class="max-w-2xl mx-auto">
        
        <!-- Logo Primea -->
        <div class="text-center mb-8 animate-fade-in">
          <img src="/images/logo_white.png" alt="Primea" class="h-16 mx-auto mb-6" />
        </div>
        
        <!-- En-tête -->
        <div class="text-center mb-12 animate-fade-in">
          <h1 class="text-3xl md:text-4xl font-bold text-white mb-4 font-primea">
            Vous avez perdu votre ticket ?
          </h1>
          <p class="text-xl text-white/90 mb-8 font-primea">
            Ne vous inquiétez pas
          </p>
          <p class="text-white/80 font-primea">
            Utilisez votre référence de transaction pour retrouver votre ticket
          </p>
        </div>

        <!-- Formulaire de recherche -->
        <div class="bg-white/95 backdrop-blur-sm rounded-primea-xl shadow-primea-lg p-8 animate-slide-up">
          <form @submit.prevent="searchTicket" class="space-y-6">
            
            <!-- Champ de référence -->
            <div>
              <label for="reference" class="block text-lg font-semibold text-primea-blue mb-3 font-primea">
                Entrez votre référence de transaction
              </label>
              <input
                type="text"
                id="reference"
                v-model="searchForm.reference"
                placeholder="Ex: TKT-2024-ABC123"
                class="w-full px-6 py-4 text-lg border-2 border-gray-200 rounded-primea-lg focus:ring-2 focus:ring-primea-yellow focus:border-primea-blue transition-all duration-200 font-primea bg-white/90"
              />
              <p class="mt-2 text-sm text-gray-600 font-primea">
                Vous pouvez utiliser votre ID de transaction ou le numéro de téléphone utilisé pour l'achat
              </p>
            </div>

            <!-- Méthode de recherche alternative -->
            <div class="border-t border-primea-blue/20 pt-6">
              <h4 class="font-semibold text-primea-blue mb-4 font-primea">Ou rechercher par :</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <!-- Numéro de téléphone -->
                <div>
                  <label for="phone" class="block text-sm font-semibold text-primea-blue mb-2 font-primea">
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
                  <label for="email" class="block text-sm font-semibold text-primea-blue mb-2 font-primea">
                    Adresse email
                  </label>
                  <input 
                    type="email"
                    id="email"
                    v-model="searchForm.email"
                    placeholder="votre@email.com"
                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-primea-lg focus:ring-2 focus:ring-primea-yellow focus:border-primea-blue transition-all duration-200 font-primea bg-white/90"
                  />
                </div>
              </div>
            </div>

            <!-- Message d'erreur -->
            <div v-if="error" class="bg-red-50 border border-red-200 rounded-primea-lg p-4">
              <div class="flex items-center">
                <svg class="w-5 h-5 text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-red-600 text-sm font-primea">{{ error }}</p>
              </div>
            </div>

            <!-- Message de succès -->
            <div v-if="success" class="bg-green-50 border border-green-200 rounded-primea-lg p-4">
              <div class="flex items-center">
                <svg class="w-5 h-5 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <p class="text-green-600 text-sm font-primea">{{ success }}</p>
              </div>
            </div>

            <!-- Bouton Rechercher -->
            <button 
              type="submit"
              :disabled="loading || (!searchForm.reference && !searchForm.phone && !searchForm.email)"
              class="w-full text-white py-4 px-6 rounded-primea-lg text-lg font-bold transition-all duration-200 shadow-primea-lg transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none font-primea"
              :style="{ backgroundColor: '#272d63', color: '#ffffff' }"
              @mouseover="if (!loading && (searchForm.reference || searchForm.phone || searchForm.email)) { $event.currentTarget.style.backgroundColor='#fab511'; $event.currentTarget.style.color='#272d63'; }"
              @mouseleave="if (!loading && (searchForm.reference || searchForm.phone || searchForm.email)) { $event.currentTarget.style.backgroundColor='#272d63'; $event.currentTarget.style.color='#ffffff'; }"
            >
              <span v-if="loading" class="flex items-center justify-center pointer-events-none">
                <svg class="w-6 h-6 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/>
                  <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" class="opacity-75"/>
                </svg>
                Recherche en cours...
              </span>
              <span v-else class="pointer-events-none">
                <svg class="w-6 h-6 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Rechercher mon ticket
              </span>
            </button>

          </form>
        </div>

        <!-- Tickets trouvés -->
        <div v-if="foundTickets.length > 0" class="mt-8">
          <h3 class="text-xl font-bold text-white mb-6 font-primea">Tickets trouvés :</h3>
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

        <!-- Aide -->
        <div class="mt-12 text-center">
          <h4 class="font-semibold text-white mb-4 font-primea">Besoin d'aide ?</h4>
          <p class="text-sm text-white/80 mb-6 font-primea">
            Si vous ne trouvez pas votre ticket, contactez notre support
          </p>
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="tel:+24107123456" class="flex items-center justify-center px-6 py-3 bg-green-600 text-white rounded-primea-lg hover:bg-green-700 transition-colors duration-200 shadow-primea font-primea font-semibold">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
              </svg>
              Appeler le support
            </a>
            <a href="mailto:support@primea.com" class="flex items-center justify-center px-6 py-3 border-2 border-white/30 text-white rounded-primea-lg hover:bg-white/10 transition-all duration-200 shadow-primea font-primea font-semibold">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
              </svg>
              Envoyer un email
            </a>
          </div>
        </div>

        <!-- Retour à l'accueil -->
        <div class="text-center mt-8">
          <router-link 
            to="/"
            class="text-white/90 hover:text-primea-yellow font-semibold text-sm transition-colors duration-200 font-primea bg-white/10 backdrop-blur-sm px-4 py-2 rounded-primea border border-white/20 hover:border-primea-yellow"
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

    // État réactif
    const loading = ref(false)
    const error = ref('')
    const success = ref('')
    const foundTickets = ref([])

    const searchForm = ref({
      reference: '',
      phone: '',
      email: ''
    })

    // Méthodes
    const searchTicket = async () => {
      try {
        loading.value = true
        error.value = ''
        success.value = ''

        // Validation
        if (!searchForm.value.reference && !searchForm.value.phone && !searchForm.value.email) {
          throw new Error('Veuillez remplir au moins un champ de recherche')
        }

        // Utiliser l'API pour rechercher les tickets
        const response = await ticketService.searchTickets({
          reference: searchForm.value.reference,
          phone: searchForm.value.phone,
          email: searchForm.value.email
        })

        if (response.data.data?.tickets) {
          // Filtrer uniquement les tickets avec commande payée
          const paidTickets = response.data.data.tickets.filter(apiTicket =>
            apiTicket.order?.status === 'paid'
          )

          if (paidTickets.length === 0) {
            throw new Error('Aucun ticket payé trouvé. Veuillez d\'abord finaliser le paiement.')
          }

          // Transformer les données de l'API
          foundTickets.value = paidTickets.map(apiTicket => ({
            id: apiTicket.id,
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
            qrCode: null, // Sera généré si nécessaire
            status: apiTicket.status,
            buyer_name: apiTicket.buyer?.name,
            buyer_email: apiTicket.buyer?.email,
            issued_at: apiTicket.issued_at,
            used_at: apiTicket.used_at
          }))

          success.value = `${foundTickets.value.length} ticket(s) trouvé(s) !`
        } else if (response.data.data?.ticket) {
          // Cas d'un seul ticket trouvé
          const apiTicket = response.data.data.ticket

          // Vérifier si le ticket est payé
          if (apiTicket.order?.status !== 'paid') {
            throw new Error('Ce ticket n\'a pas encore été payé. Veuillez finaliser le paiement.')
          }
          foundTickets.value = [{
            id: apiTicket.id,
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
            qrCode: null,
            status: apiTicket.status,
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
        // Extraire le message du serveur si disponible
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
      // Rediriger vers la page de téléchargement
      router.push(`/ticket/${ticket.id}/download`)
    }

    const viewTicket = (ticket) => {
      // Rediriger vers la page de visualisation
      router.push(`/ticket/${ticket.id}`)
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
/* Variables CSS Primea */
:root {
  --primea-blue: #272d63;
  --primea-yellow: #fab511;
  --primea-white: #ffffff;
  --primea-blue-dark: #1a1e47;
  --primea-yellow-dark: #e09f0e;
  --font-primary: 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Couleurs de texte Primea */
.text-primea-blue {
  color: var(--primea-blue);
}

.text-primea-yellow {
  color: var(--primea-yellow);
}

/* Couleurs de fond Primea */
.bg-primea-blue {
  background-color: var(--primea-blue);
}

.bg-primea-yellow {
  background-color: var(--primea-yellow);
}

/* States hover Primea */
.hover\:text-primea-yellow:hover {
  color: var(--primea-yellow);
}

.hover\:border-primea-yellow:hover {
  border-color: var(--primea-yellow);
}

/* Focus states */
.focus\:ring-primea-yellow:focus {
  --tw-ring-color: var(--primea-yellow);
}

.focus\:border-primea-blue:focus {
  border-color: var(--primea-blue);
}

/* Coins arrondis Primea */
.rounded-primea {
  border-radius: 12px;
}

.rounded-primea-lg {
  border-radius: 16px;
}

.rounded-primea-xl {
  border-radius: 20px;
}

/* Ombres Primea */
.shadow-primea {
  box-shadow: 0 4px 20px rgba(39, 45, 99, 0.1);
}

.shadow-primea-lg {
  box-shadow: 0 8px 30px rgba(39, 45, 99, 0.15);
}

/* Police Primea */
.font-primea {
  font-family: var(--font-primary);
}

/* Animations */
.animate-spin {
  animation: spin 1s linear infinite;
}

.animate-fade-in {
  animation: fadeIn 0.8s ease-out;
}

.animate-slide-up {
  animation: slideUp 0.6s ease-out;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

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

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Transitions */
.transition-all {
  transition: all 0.2s ease-in-out;
}

.transition-colors {
  transition: color 0.2s ease-in-out, background-color 0.2s ease-in-out, border-color 0.2s ease-in-out;
}

/* Styles pour les inputs focus */
input:focus {
  outline: none;
}

/* Spacing utilities */
.space-y-4 > * + * {
  margin-top: 1rem;
}

.space-y-6 > * + * {
  margin-top: 1.5rem;
}

/* Responsive grid */
@media (min-width: 768px) {
  .grid-cols-1.md\:grid-cols-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
</style>