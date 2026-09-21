<template>
  <div class="ticket-success-page min-h-screen bg-gray-50 font-primea">
    <div class="container mx-auto px-4 py-6">
      <div class="max-w-xl mx-auto">

        <!-- Loading State -->
        <div v-if="loading" class="bg-white rounded-primea-xl shadow-primea p-8 text-center">
          <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-primea-blue mx-auto mb-4"></div>
          <p class="text-gray-600">Chargement de votre commande...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="bg-white rounded-primea-xl shadow-primea p-8">
          <div class="text-center mb-6">
            <ExclamationCircleIcon class="w-16 h-16 text-red-500 mx-auto mb-4" />
            <h2 class="text-2xl font-bold text-red-800 mb-2">Erreur</h2>
            <p class="text-red-600">{{ error }}</p>
          </div>
          <div class="text-center">
            <router-link
              to="/events"
              class="inline-block bg-primea-blue text-white px-6 py-3 rounded-primea hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200"
            >
              Retour aux événements
            </router-link>
          </div>
        </div>

        <!-- Success State -->
        <div v-else-if="order" class="bg-white rounded-primea-xl shadow-primea p-5 sm:p-6">

          <!-- Confirmation compacte -->
          <div class="text-center mb-4">
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-2">
              <CheckCircleIcon class="w-7 h-7 text-green-600" />
            </div>
            <h1 class="text-xl font-bold text-primea-blue">Paiement réussi !</h1>
            <p class="text-sm text-gray-500">Présentez le QR ci-dessous à l'entrée</p>
          </div>

          <!-- Recap commande condense (ref + montant) -->
          <div class="bg-primea-blue/5 rounded-primea-lg px-4 py-3 mb-4">
            <div class="flex justify-between items-center gap-3">
              <span class="font-semibold text-primea-blue truncate">{{ order.event?.title || 'Événement' }}</span>
              <span class="text-sm font-medium text-gray-600 whitespace-nowrap">{{ order.quantity }} billet(s)</span>
            </div>
            <div class="flex justify-between items-center gap-3 mt-1">
              <span class="text-xs font-mono text-gray-500">{{ order.reference }}</span>
              <span class="font-bold text-green-600">{{ formatPrice(order.total_amount) }} XAF</span>
            </div>
          </div>

          <!-- Billet(s) : l'element telechargeable EST le ticket -->
          <div v-if="ticketCards.length > 0" class="mb-4">
            <div class="space-y-6">
              <div v-for="(t, i) in ticketCards" :key="t.id" class="space-y-3">
                <div :ref="(el) => setTicketRef(el, i)" class="rounded-primea-lg overflow-hidden shadow-primea">
                  <TicketComponent :ticket="t" size="large" />
                </div>
                <button
                  @click="downloadImage(t, i)"
                  :disabled="!imagesReady"
                  class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 rounded-primea-lg transition-colors flex items-center justify-center gap-2"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                  </svg>
                  {{ imagesReady ? 'Télécharger le billet' : 'Préparation du billet…' }}
                </button>
              </div>
            </div>
            <div v-if="ticketCards.length > 1" class="mt-4 text-center">
              <button
                @click="downloadAllImages"
                :disabled="downloadingAll"
                class="text-primea-blue hover:text-primea-yellow font-medium text-sm underline"
              >
                <span v-if="downloadingAll">Téléchargement…</span>
                <span v-else>Tout télécharger</span>
              </button>
            </div>
          </div>


          <!-- Ancienne liste (remplacée par l'affichage en ligne ci-dessus) -->
          <div v-if="false" class="mb-6">
            <h3 class="text-lg font-bold text-primea-blue mb-4">Vos tickets</h3>

            <div class="space-y-3">
              <div
                v-for="ticket in tickets"
                :key="ticket.id"
                class="bg-gray-50 border-2 border-primea-blue rounded-primea-lg p-4 hover:shadow-md transition-shadow"
              >
                <div class="flex justify-between items-center">
                  <div>
                    <p class="font-semibold text-primea-blue">{{ ticket.ticket_type?.name || 'Ticket' }}</p>
                    <p class="text-sm text-gray-600">Code: {{ ticket.code }}</p>
                  </div>
                  <div class="flex items-center gap-2">
                    <button
                      @click="shareTicket(ticket)"
                      class="border-2 border-primea-blue text-primea-blue px-3 py-2 rounded-primea text-sm hover:bg-primea-blue hover:text-white transition-all duration-200"
                      title="Partager"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                      </svg>
                    </button>
                    <router-link
                      :to="`/ticket/${ticket.code}/download`"
                      class="bg-primea-blue text-white px-4 py-2 rounded-primea text-sm hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200"
                    >
                      Télécharger
                    </router-link>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Download All Button -->
          <div class="text-center mb-6">
            <button
              v-if="false"
              @click="downloadAllTickets"
              class="bg-primea-blue text-white px-8 py-3 rounded-primea-lg font-bold hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200 shadow-primea-lg transform hover:scale-105"
            >
              Télécharger tous les tickets
            </button>
          </div>

          <!-- Email Notification -->
          <div v-if="order.guest_email && order.guest_email !== 'guest@primea.ga'" class="bg-blue-50 border border-blue-200 rounded-primea-lg p-4 mb-6 text-center">
            <p class="text-blue-800 text-sm">
              Un email de confirmation avec vos tickets a été envoyé à <strong>{{ order.guest_email }}</strong>
            </p>
          </div>

          <!-- Actions -->
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <router-link
              to="/events"
              class="text-center bg-gray-100 text-primea-blue px-6 py-3 rounded-primea hover:bg-gray-200 transition-all duration-200"
            >
              Voir d'autres événements
            </router-link>

            <router-link
              to="/"
              class="text-center bg-primea-yellow text-primea-blue px-6 py-3 rounded-primea hover:bg-primea-yellow transition-all duration-200 font-semibold"
            >
              Retour à l'accueil
            </router-link>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, nextTick, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { CheckCircleIcon, ExclamationCircleIcon } from '@heroicons/vue/24/outline'
import Swal from 'sweetalert2'
import { captureTicketBlob, saveTicketImage } from '../utils/ticketImage'
import TicketComponent from '../components/TicketComponent.vue'
import { ticketService } from '../services/api'

export default {
  name: 'TicketSuccess',
  components: {
    CheckCircleIcon,
    ExclamationCircleIcon,
    TicketComponent
  },
  setup() {
    const route = useRoute()
    const router = useRouter()
    const authStore = useAuthStore()

    const loading = ref(true)
    const error = ref('')
    const order = ref(null)
    const tickets = ref([])

    // Billets rendus en ligne (données complètes : image, QR, prix, date)
    const ticketCards = ref([])
    const ticketRefs = []
    const setTicketRef = (el, i) => { ticketRefs[i] = el }
    const downloadingId = ref(null)
    const downloadingAll = ref(false)

    const toIsoDate = (value) => {
      if (!value) return null
      if (typeof value === 'string' && value.includes('/')) {
        const [datePart, timePart] = value.split(' ')
        const [day, month, year] = datePart.split('/')
        return `${year}-${month}-${day}${timePart ? 'T' + timePart : ''}`
      }
      return value
    }

    // Charger les données complètes de chaque billet pour l'affichage inline
    const loadTicketCards = async () => {
      const cards = []
      for (const t of tickets.value) {
        try {
          const res = await ticketService.getTicket(t.code)
          const a = res.data?.ticket
          if (!a) continue
          cards.push({
            id: a.id ?? t.id,
            code: a.code,
            reference: a.code,
            event: {
              title: a.event?.title,
              image: a.event?.image_url || a.event?.image || null,
              venue_name: a.event?.venue_name,
              date: toIsoDate(a.schedule?.starts_at),
              time: ''
            },
            ticketType: a.ticket_type?.name || 'Standard',
            price: a.ticket_type?.price ?? 0,
            qrCode: a.qr_code || a.qrCode
              || `https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=${encodeURIComponent(a.code)}`
          })
        } catch (e) {
          console.error('Billet introuvable:', t.code, e)
        }
      }
      ticketCards.value = cards
    }

    const downloadPdf = async (t) => {
      if (downloadingId.value) return
      downloadingId.value = t.id
      try {
        const res = await fetch(`/api/v1/tickets/${t.code}/pdf`)
        if (!res.ok) throw new Error('PDF indisponible')
        const blob = await res.blob()
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.download = `ticket-${t.code}.pdf`
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)
      } catch (e) {
        Swal.fire({ icon: 'error', title: 'Erreur', text: 'Impossible de télécharger le PDF.', confirmButtonColor: '#272d63' })
      } finally {
        downloadingId.value = null
      }
    }

    // Images des billets préparées dès l'affichage : sur iPhone, un
    // téléchargement déclenché après une capture de plusieurs secondes sort du
    // geste de l'utilisateur et Safari l'ignore sans rien dire.
    const ticketBlobs = ref([])
    const preparingImages = ref(true)
    const imagesReady = computed(() => !preparingImages.value)

    const prepareTicketImages = async () => {
      preparingImages.value = true
      await nextTick()
      for (let i = 0; i < ticketCards.value.length; i++) {
        const el = ticketRefs[i]
        if (!el) continue
        try {
          ticketBlobs.value[i] = await captureTicketBlob(el)
        } catch (e) {
          // On laissera le clic retenter : mieux vaut un bouton actif.
          console.error('Préparation du billet impossible:', e)
        }
      }
      preparingImages.value = false
    }

    const imageError = () => {
      Swal.fire({ icon: 'error', title: 'Erreur', text: 'Impossible de générer l\'image.', confirmButtonColor: '#272d63' })
    }

    // Volontairement non-`async` : `navigator.share` doit être appelé dans le
    // geste, donc sans aucun `await` avant lui.
    const downloadImage = (t, i) => {
      const filename = `ticket-${t.code}.jpg`
      const ready = ticketBlobs.value[i]

      if (ready) {
        return saveTicketImage(ready, filename).catch(imageError)
      }

      const el = ticketRefs[i]
      if (!el) return Promise.resolve()

      return captureTicketBlob(el)
        .then((blob) => {
          ticketBlobs.value[i] = blob
          return saveTicketImage(blob, filename)
        })
        .catch(imageError)
    }

    const downloadAllPdf = async () => {
      if (downloadingAll.value || ticketCards.value.length === 0) return
      downloadingAll.value = true
      try {
        for (const t of ticketCards.value) {
          await downloadPdf(t)
          await new Promise((r) => setTimeout(r, 400)) // léger délai anti-blocage navigateur
        }
      } finally {
        downloadingAll.value = false
      }
    }

    // Télécharge chaque billet en image (l'élément du bas = le ticket)
    const downloadAllImages = async () => {
      if (downloadingAll.value || ticketCards.value.length === 0) return
      downloadingAll.value = true
      try {
        for (let i = 0; i < ticketCards.value.length; i++) {
          await downloadImage(ticketCards.value[i], i)
          await new Promise((r) => setTimeout(r, 500))
        }
      } finally {
        downloadingAll.value = false
      }
    }

    const loadOrder = async () => {
      const reference = route.query.reference

      if (!reference) {
        error.value = 'Référence de commande manquante'
        loading.value = false
        return
      }

      try {
        loading.value = true
        error.value = ''

        // Déterminer quelle API utiliser selon l'authentification
        const isAuthenticated = authStore.isAuthenticated
        const apiUrl = isAuthenticated
          ? `/api/v1/orders/${reference}`
          : `/api/v1/guest/orders/${reference}`

        const headers = {
          'Accept': 'application/json'
        }

        // Ajouter le token si l'utilisateur est authentifié
        if (isAuthenticated) {
          const token = localStorage.getItem('auth_token') || localStorage.getItem('token')
          if (token) {
            headers['Authorization'] = `Bearer ${token}`
          }
        }

        const response = await fetch(apiUrl, { headers })

        if (!response.ok) {
          throw new Error('Commande introuvable')
        }

        const data = await response.json()

        if (data.success) {
          order.value = data.data.order
          tickets.value = data.data.order.tickets || []
          await loadTicketCards()
          prepareTicketImages()
        } else {
          throw new Error(data.message || 'Erreur lors du chargement de la commande')
        }

      } catch (err) {
        console.error('Erreur lors du chargement de la commande:', err)
        error.value = err.message || 'Impossible de charger votre commande'
      } finally {
        loading.value = false
      }
    }

    const formatPrice = (price) => {
      return new Intl.NumberFormat('fr-FR').format(price)
    }

    const formatDate = (date) => {
      if (!date) return 'N/A'

      try {
        // Vérifier si la date est au format français (dd/mm/yyyy HH:mm:ss)
        if (typeof date === 'string' && date.includes('/')) {
          const [datePart, timePart] = date.split(' ')
          const [day, month, year] = datePart.split('/')
          const isoDate = `${year}-${month}-${day}${timePart ? 'T' + timePart : ''}`
          const dateObj = new Date(isoDate)

          if (isNaN(dateObj.getTime())) {
            return 'N/A'
          }

          return dateObj.toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
          })
        }

        // Sinon, traiter comme une date ISO
        const dateObj = new Date(date)
        if (isNaN(dateObj.getTime())) {
          return 'N/A'
        }

        return dateObj.toLocaleDateString('fr-FR', {
          day: 'numeric',
          month: 'long',
          year: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        })
      } catch (e) {
        console.error('Erreur parsing date:', e)
        return 'N/A'
      }
    }

    const shareTicket = async (ticket) => {
      const ticketUrl = `${window.location.origin}/ticket/${ticket.code}/download`
      const shareData = {
        title: `Ticket - ${order.value?.event?.title || 'Événement'}`,
        text: `Mon ticket ${ticket.ticket_type?.name || ''} pour ${order.value?.event?.title || 'l\'événement'}`,
        url: ticketUrl
      }

      if (navigator.share) {
        try {
          await navigator.share(shareData)
        } catch (err) {
          if (err.name !== 'AbortError') {
            console.error('Erreur de partage:', err)
          }
        }
      } else {
        try {
          await navigator.clipboard.writeText(ticketUrl)
          Swal.fire({
            icon: 'success',
            title: 'Lien copié !',
            text: 'Le lien du ticket a été copié dans le presse-papiers',
            confirmButtonColor: '#272d63',
            timer: 2000,
            showConfirmButton: false
          })
        } catch {
          Swal.fire({
            icon: 'info',
            title: 'Lien du ticket',
            text: ticketUrl,
            confirmButtonColor: '#272d63'
          })
        }
      }
    }

    const downloadAllTickets = () => {
      if (!tickets.value || tickets.value.length === 0) return

      // Ouvrir chaque ticket dans un nouvel onglet
      tickets.value.forEach((ticket, index) => {
        setTimeout(() => {
          window.open(`/ticket/${ticket.code}/download`, '_blank')
        }, index * 500) // Délai de 500ms entre chaque ouverture
      })
    }

    onMounted(() => {
      loadOrder()
    })

    return {
      authStore,
      loading,
      error,
      order,
      tickets,
      ticketCards,
      setTicketRef,
      downloadingId,
      downloadingAll,
      downloadPdf,
      downloadImage,
      imagesReady,
      downloadAllPdf,
      downloadAllImages,
      formatPrice,
      formatDate,
      shareTicket,
      downloadAllTickets
    }
  }
}
</script>

<style scoped>
.ticket-success-page {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  min-height: 100vh;
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

.bg-primea-yellow {
  background-color: #fab511;
}

.hover\:bg-primea-yellow:hover {
  background-color: #fab511 !important;
}

.hover\:text-primea-blue:hover {
  color: #272d63 !important;
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

.rounded-primea-xl {
  border-radius: 20px;
}

.shadow-primea {
  box-shadow: 0 4px 20px rgba(39, 45, 99, 0.1);
}

.shadow-primea-lg {
  box-shadow: 0 8px 30px rgba(39, 45, 99, 0.15);
}

.transition-all {
  transition: all 0.2s ease-in-out;
}

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
</style>
