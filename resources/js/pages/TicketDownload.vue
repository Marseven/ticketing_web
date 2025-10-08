<template>
  <div class="ticket-download-page font-primea min-h-screen">
    
    <!-- Desktop/Tablet Layout -->
    <div class="hidden md:block bg-gray-50 min-h-screen">
      <div class="container mx-auto px-4 py-12">
        <div class="max-w-6xl mx-auto">
          
          <!-- Header Desktop -->
          <div class="text-center mb-12">
            <img src="/images/logo.png" alt="Primea" class="h-16 mx-auto mb-6" />
            <h1 class="text-4xl font-bold text-primea-blue mb-4">Votre ticket électronique</h1>
            <p class="text-lg text-gray-600">Téléchargez ou capturez votre ticket pour l'événement</p>
          </div>

          <!-- État de chargement -->
          <div v-if="loading" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primea-blue"></div>
            <p class="mt-4 text-gray-600">Chargement du ticket...</p>
          </div>

          <!-- Message d'erreur -->
          <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-primea-xl p-6 text-center">
            <div class="flex items-center justify-center mb-4">
              <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-red-800 mb-2">Ticket introuvable</h3>
            <p class="text-red-600 mb-4">{{ error }}</p>
            <button @click="loadTicket" class="bg-primea-blue text-white px-6 py-2 rounded-primea-lg hover:bg-primea-yellow hover:text-primea-blue transition-colors">
              Réessayer
            </button>
          </div>

          <!-- Contenu du ticket -->
          <div v-else-if="ticket" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Colonne gauche - Ticket -->
            <div class="lg:col-span-2 flex justify-center">
              <TicketComponent :ticket="ticket" size="large" />
            </div>

            <!-- Colonne droite - Instructions -->
            <div class="lg:col-span-1 space-y-6">
              <!-- Instructions -->
              <div class="bg-white rounded-primea-xl shadow-primea p-6">
                <h3 class="text-xl font-bold text-primea-blue mb-4">Comment utiliser votre ticket</h3>
                <div class="space-y-4 text-gray-600">
                  <div class="flex items-start gap-3">
                    <div class="rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold mt-0.5 flex-shrink-0" style="background-color: #272d63; color: white;">1</div>
                    <div>
                      <p class="font-semibold text-gray-800">Téléchargez votre ticket</p>
                      <p class="text-sm">Cliquez sur le bouton de téléchargement pour sauvegarder votre ticket en PDF</p>
                    </div>
                  </div>
                  <div class="flex items-start gap-3">
                    <div class="rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold mt-0.5 flex-shrink-0" style="background-color: #272d63; color: white;">2</div>
                    <div>
                      <p class="font-semibold text-gray-800">Présentez le QR code</p>
                      <p class="text-sm">À l'entrée, montrez le QR code pour validation</p>
                    </div>
                  </div>
                  <div class="flex items-start gap-3">
                    <div class="rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold mt-0.5 flex-shrink-0" style="background-color: #fab511; color: #272d63;">!</div>
                    <div>
                      <p class="font-semibold text-red-600">Important</p>
                      <p class="text-sm">Ce ticket est personnel et à usage unique. Il ne peut être ni vendu ni donné</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Actions -->
              <div class="bg-white rounded-primea-xl shadow-primea p-6">
                <h3 class="text-xl font-bold text-primea-blue mb-4">Actions</h3>
                <div class="space-y-3">
                  <button 
                    @click="downloadTicket"
                    class="w-full py-3 px-6 rounded-primea-lg font-semibold transition-all duration-200 shadow-primea flex items-center justify-center gap-2"
                    style="background-color: #fab511; color: #272d63;"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Télécharger le ticket PDF
                  </button>
                  <button 
                    @click="goBack"
                    class="w-full border-2 border-primea-blue text-primea-blue py-3 px-6 rounded-primea-lg font-semibold hover:bg-primea-blue hover:text-white transition-all duration-200"
                  >
                    Retour aux événements
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Mobile Layout -->
    <div class="md:hidden bg-gray-100 min-h-screen py-8 px-4">
      <div class="max-w-md mx-auto">
        
        <!-- État de chargement mobile -->
        <div v-if="loading" class="text-center py-12">
          <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primea-blue"></div>
          <p class="mt-4 text-gray-600">Chargement du ticket...</p>
        </div>

        <!-- Message d'erreur mobile -->
        <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-primea-xl p-6 text-center">
          <div class="flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-red-800 mb-2">Ticket introuvable</h3>
          <p class="text-red-600 mb-4">{{ error }}</p>
          <button @click="loadTicket" class="bg-primea-blue text-white px-6 py-2 rounded-primea-lg hover:bg-primea-yellow hover:text-primea-blue transition-colors">
            Réessayer
          </button>
        </div>

        <!-- Contenu mobile -->
        <template v-else-if="ticket">
        
        <!-- En-tête mobile -->
        <div class="flex items-center justify-between mb-8">
          <button @click="goBack" class="text-gray-600 hover:text-gray-800">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
          </button>
          
          <div class="text-center">
            <img src="/images/logo.png" alt="Primea" class="h-8 mx-auto mb-2" />
            <div class="text-center">
              <div class="text-lg font-bold text-primea-blue">La Billetterie</div>
              <div class="text-xs text-gray-500">Simple, Rapide et Sécurisée</div>
            </div>
          </div>
          
          <button class="text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
          </button>
        </div>

        <!-- Instructions mobile -->
        <div class="text-center mb-8">
          <h1 class="text-lg font-medium text-gray-700 mb-2">
            Téléchargez ou capturez l'écran
          </h1>
          <p class="text-gray-600">
            pour conserver votre ticket
          </p>
        </div>

        <!-- Section VOTRE TICKET -->
        <div class="text-center mb-6">
          <h2 class="text-lg font-bold text-primea-blue">VOTRE TICKET</h2>
        </div>

        <!-- Ticket mobile avec le composant -->
        <div class="mb-6">
          <TicketComponent :ticket="ticket" size="small" />
        </div>

        <!-- Bouton Télécharger mobile -->
        <div class="mb-8">
          <button 
            @click="downloadTicket"
            class="w-full py-3 px-6 rounded-primea-lg font-semibold transition-colors duration-200"
            style="background-color: #fab511; color: #272d63;"
          >
            Télécharger
          </button>
        </div>

        <!-- Espace pub mobile -->
        <div class="bg-gray-200 rounded-primea-lg p-8 text-center mb-6">
          <div class="text-xl text-gray-400 font-light mb-4">ESPACE PUB</div>
          <a href="#" class="text-primea-blue text-sm hover:text-primea-yellow">En savoir plus...</a>
        </div>

        <!-- Logo et réseaux sociaux mobile -->
        <div class="text-center">
          <img src="/images/logo.png" alt="Primea" class="h-8 mx-auto mb-4" />
          <div class="flex justify-center space-x-4">
            <a href="#" class="text-green-500 hover:text-green-600">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"/>
              </svg>
            </a>
            <a href="#" class="text-blue-600 hover:text-blue-700">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
              </svg>
            </a>
            <a href="#" class="text-pink-500 hover:text-pink-600">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.174-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.347-.09.375-.293 1.199-.332 1.363-.053.225-.172.271-.402.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.357-.629-2.746-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24.009 12.017 24.009c6.624 0 11.99-5.367 11.99-11.988C24.007 5.367 18.641.001.012.001z"/>
              </svg>
            </a>
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

    // États réactifs
    const ticket = ref(null)
    const loading = ref(true)
    const error = ref('')

    // Charger les données du ticket depuis l'API
    const loadTicket = async () => {
      try {
        loading.value = true
        error.value = ''

        const ticketCode = route.params.id

        // Utiliser directement le code du ticket (format: TKT-XXXXXXXX)
        const response = await ticketService.getTicket(ticketCode)
        
        if (response.data?.ticket) {
          // Transformer les données de l'API pour correspondre au format attendu
          const apiTicket = response.data.ticket

          // Construire l'URL complète de l'image
          const imageUrl = apiTicket.event.image_url
            ? (apiTicket.event.image_url.startsWith('http')
                ? apiTicket.event.image_url
                : `${window.location.origin}${apiTicket.event.image_url}`)
            : 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=800'

          ticket.value = {
            id: apiTicket.id,
            reference: apiTicket.code,
            event: {
              id: apiTicket.event.id,
              title: apiTicket.event.title,
              date: apiTicket.schedule?.starts_at ?
                    new Date(apiTicket.schedule.starts_at.split('/').reverse().join('-')).toISOString() :
                    '2025-07-27T20:00:00',
              venue_name: apiTicket.event.venue_name || 'Entre Nous Bar',
              image: imageUrl,
              time: apiTicket.schedule?.door_time ?
                    new Date(apiTicket.schedule.door_time.split('/').reverse().join('-')).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) :
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
        console.error('Erreur lors du chargement du ticket:', err)
        error.value = err.response?.data?.message || 'Erreur lors du chargement du ticket'
        
        // Fallback vers des données simulées si l'API échoue
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

    // Méthodes
    const formatPrice = (price) => {
      if (!price) return '10.000'
      return new Intl.NumberFormat('fr-FR').format(price)
    }

    const downloadTicket = async () => {
      try {
        // En mode réel, on pourrait appeler une API pour générer le PDF
        // Pour l'instant, simulation du téléchargement
        const element = document.createElement('a')
        element.href = 'data:application/pdf;base64,JVBERi0xLjMKJf////8KMSAwIG9iago8PAovVHlwZSAvQ2F0YWxvZwovT3V0bGluZXMgMiAwIFIKL1BhZ2VzIDMgMCBSCj4+CmVuZG9iago='
        element.download = `ticket-${ticket.value?.reference || 'primea'}.pdf`
        document.body.appendChild(element)
        element.click()
        document.body.removeChild(element)
      } catch (err) {
        console.error('Erreur lors du téléchargement:', err)
        alert('Erreur lors du téléchargement du ticket')
      }
    }

    const goBack = () => {
      router.back()
    }

    // Charger le ticket au montage du composant
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
/* Variables CSS Primea */
:root {
  --primea-blue: #272d63;
  --primea-yellow: #fab511;
  --primea-white: #ffffff;
  --primea-blue-dark: #1a1e47;
  --primea-yellow-dark: #e09f0e;
  --font-primary: 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.ticket-download-page {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

/* Classes Primea */
.font-primea {
  font-family: var(--font-primary);
}

.text-primea-blue {
  color: var(--primea-blue);
}

.text-primea-yellow {
  color: var(--primea-yellow);
}

.bg-primea-blue {
  background-color: var(--primea-blue);
}

.bg-primea-yellow {
  background-color: var(--primea-yellow);
}

.hover\:bg-primea-yellow:hover {
  background-color: var(--primea-yellow) !important;
}

.hover\:text-primea-blue:hover {
  color: var(--primea-blue) !important;
}

.hover\:text-primea-yellow:hover {
  color: var(--primea-yellow);
}

.bg-primea-gradient {
  background: linear-gradient(135deg, var(--primea-blue) 0%, var(--primea-blue-dark) 100%);
}

.rounded-primea {
  border-radius: 12px;
}

.rounded-primea-lg {
  border-radius: 16px;
}

.rounded-primea-xl {
  border-radius: 20px;
}

.shadow-primea {
  box-shadow: 0 4px 20px rgba(39, 45, 99, 0.1);
}

.shadow-primea-lg {
  box-shadow: 0 8px 30px rgba(39, 45, 99, 0.15);
}

/* Container */
.container {
  max-width: 1200px;
}

/* Transitions */
.transition-colors {
  transition: color 0.2s ease-in-out, background-color 0.2s ease-in-out, border-color 0.2s ease-in-out;
}

.transition-all {
  transition: all 0.2s ease-in-out;
}

/* Hover effects */
button:hover {
  transform: translateY(-1px);
}

/* QR Code styling */
.qr-code {
  image-rendering: pixelated;
  image-rendering: -moz-crisp-edges;
  image-rendering: crisp-edges;
}
</style>