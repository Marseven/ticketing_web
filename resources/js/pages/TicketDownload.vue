<template>
  <div class="ticket-download-page min-h-screen bg-gray-50">

    <!-- Mobile Title -->
    <div class="md:hidden px-4 pt-6 pb-4">
      <h1 class="text-xl font-bold text-primea-blue text-center">Votre ticket électronique</h1>
    </div>

    <!-- Desktop Header -->
    <div class="hidden md:block bg-white shadow-sm">
      <div class="container mx-auto px-4 py-8 md:py-12">
        <div class="max-w-6xl mx-auto">
          <div class="text-center">
            <img src="/images/logo.png" alt="Logo" class="h-16 mx-auto mb-6" />
            <h1 class="text-3xl md:text-4xl font-bold text-primea-blue mb-4">Votre ticket électronique</h1>
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
          <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primea-blue"></div>
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
          <button @click="loadTicket" class="bg-primea-blue text-white px-6 py-3 rounded-xl hover:bg-primea-yellow hover:text-primea-blue transition-colors font-semibold">
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
              <h2 class="text-lg font-bold text-primea-blue">VOTRE TICKET</h2>
            </div>

            <!-- Ticket Display -->
            <div ref="ticketMobileRef" class="mb-6 flex justify-center">
              <TicketComponent :ticket="ticket" size="small" />
            </div>

            <!-- Download & Share Buttons -->
            <div class="mb-8 space-y-3">
              <div class="grid grid-cols-2 gap-3">
                <button
                  @click="downloadTicket"
                  :disabled="downloading"
                  :class="[
                    'py-4 px-4 rounded-xl font-bold transition-all duration-200 shadow-lg flex items-center justify-center gap-2',
                    downloading
                      ? 'bg-gray-400 text-gray-600 cursor-not-allowed'
                      : 'bg-primea-yellow text-primea-blue hover:bg-primea-yellow transform hover:scale-105'
                  ]"
                >
                  <span v-if="downloading" class="flex items-center justify-center gap-2">
                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                  </span>
                  <span v-else>
                    <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    PDF
                  </span>
                </button>
                <button
                  @click="downloadTicketAsImage"
                  :disabled="downloadingImage"
                  :class="[
                    'py-4 px-4 rounded-xl font-bold transition-all duration-200 shadow-lg flex items-center justify-center gap-2',
                    downloadingImage
                      ? 'bg-gray-400 text-gray-600 cursor-not-allowed'
                      : 'bg-primea-blue text-white hover:bg-primea-yellow hover:text-primea-blue transform hover:scale-105'
                  ]"
                >
                  <span v-if="downloadingImage" class="flex items-center justify-center gap-2">
                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                  </span>
                  <span v-else>
                    <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    JPG
                  </span>
                </button>
              </div>
              <button
                @click="shareTicket"
                class="w-full py-3 px-6 rounded-xl font-semibold border-2 border-primea-blue text-primea-blue hover:bg-primea-blue hover:text-white transition-all duration-200 flex items-center justify-center gap-2"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                </svg>
                Partager le ticket
              </button>
            </div>

            <!-- Ad Space -->
            <div class="bg-gray-200 rounded-2xl p-8 text-center mb-6">
              <div class="text-xl text-gray-400 font-light mb-4">ESPACE PUB</div>
              <a href="#" class="text-primea-blue text-sm hover:text-primea-yellow font-semibold">En savoir plus...</a>
            </div>
          </div>

          <!-- Desktop Layout -->
          <div class="hidden md:grid md:grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Ticket Column -->
            <div ref="ticketDesktopRef" class="lg:col-span-2 flex justify-center items-start">
              <TicketComponent :ticket="ticket" size="large" />
            </div>

            <!-- Instructions & Actions Column -->
            <div class="lg:col-span-1 space-y-6">

              <!-- Instructions Card -->
              <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-primea-blue mb-4">Comment utiliser votre ticket</h3>
                <div class="space-y-4 text-gray-600">
                  <div class="flex items-start gap-3">
                    <div class="rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold bg-primea-blue text-white flex-shrink-0">1</div>
                    <div>
                      <p class="font-semibold text-gray-800">Téléchargez votre ticket</p>
                      <p class="text-sm">Cliquez sur le bouton de téléchargement pour sauvegarder votre ticket en PDF</p>
                    </div>
                  </div>
                  <div class="flex items-start gap-3">
                    <div class="rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold bg-primea-blue text-white flex-shrink-0">2</div>
                    <div>
                      <p class="font-semibold text-gray-800">Présentez le QR code</p>
                      <p class="text-sm">À l'entrée, montrez le QR code pour validation</p>
                    </div>
                  </div>
                  <div class="flex items-start gap-3">
                    <div class="rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold bg-primea-yellow text-primea-blue flex-shrink-0">!</div>
                    <div>
                      <p class="font-semibold text-red-600">Important</p>
                      <p class="text-sm">Ce ticket est personnel et à usage unique. Il ne peut être ni vendu ni donné</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Actions Card -->
              <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-primea-blue mb-4">Actions</h3>
                <div class="space-y-3">
                  <button
                    @click="downloadTicket"
                    :disabled="downloading"
                    :class="[
                      'w-full py-3 px-6 rounded-xl font-bold transition-all duration-200 shadow-lg flex items-center justify-center gap-2',
                      downloading
                        ? 'bg-gray-400 text-gray-600 cursor-not-allowed'
                        : 'bg-primea-yellow text-primea-blue hover:bg-primea-yellow transform hover:scale-105'
                    ]"
                  >
                    <template v-if="downloading">
                      <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      Téléchargement...
                    </template>
                    <template v-else>
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                      </svg>
                      Télécharger le ticket PDF
                    </template>
                  </button>
                  <button
                    @click="downloadTicketAsImage"
                    :disabled="downloadingImage"
                    :class="[
                      'w-full py-3 px-6 rounded-xl font-bold transition-all duration-200 shadow-lg flex items-center justify-center gap-2',
                      downloadingImage
                        ? 'bg-gray-400 text-gray-600 cursor-not-allowed'
                        : 'bg-primea-blue text-white hover:bg-primea-yellow hover:text-primea-blue transform hover:scale-105'
                    ]"
                  >
                    <template v-if="downloadingImage">
                      <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      Téléchargement...
                    </template>
                    <template v-else>
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                      </svg>
                      Télécharger en JPG
                    </template>
                  </button>
                  <button
                    @click="shareTicket"
                    class="w-full py-3 px-6 rounded-xl font-bold transition-all duration-200 shadow-lg bg-primea-blue text-white hover:bg-primea-yellow hover:text-primea-blue flex items-center justify-center gap-2"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                    </svg>
                    Partager le ticket
                  </button>
                  <button
                    @click="goBack"
                    class="w-full border-2 border-primea-blue text-primea-blue py-3 px-6 rounded-xl font-semibold hover:bg-primea-blue hover:text-white transition-all duration-200"
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
import html2canvas from 'html2canvas'
import TicketComponent from '../components/TicketComponent.vue'
import { ticketService } from '../services/api.js'
import Swal from 'sweetalert2'

export default {
  name: 'TicketDownload',
  components: {
    TicketComponent
  },
  setup() {
    const route = useRoute()
    const router = useRouter()

    // Template refs
    const ticketMobileRef = ref(null)
    const ticketDesktopRef = ref(null)

    // Reactive state
    const ticket = ref(null)
    const loading = ref(true)
    const error = ref('')
    const downloading = ref(false)
    const downloadingImage = ref(false)

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
      if (downloading.value) return

      downloading.value = true
      const ticketCode = route.params.id
      const fileName = `ticket-${ticketCode}.pdf`

      try {
        // Afficher le loading
        Swal.fire({
          title: 'Téléchargement en cours...',
          html: 'Préparation de votre ticket PDF',
          allowOutsideClick: false,
          allowEscapeKey: false,
          showConfirmButton: false,
          didOpen: () => {
            Swal.showLoading()
          }
        })

        // Télécharger le PDF via fetch
        const response = await fetch(`/api/v1/tickets/${ticketCode}/pdf`)

        if (!response.ok) {
          throw new Error('Erreur lors de la génération du PDF')
        }

        // Convertir en blob
        const blob = await response.blob()

        // Créer un lien de téléchargement
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.download = fileName
        document.body.appendChild(link)
        link.click()

        // Nettoyer
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)

        // Notification de succès
        Swal.fire({
          icon: 'success',
          title: 'Téléchargement réussi !',
          html: `
            <p class="text-gray-600 mb-2">Votre ticket a été téléchargé :</p>
            <p class="font-semibold text-primea-blue">${fileName}</p>
            <p class="text-sm text-gray-500 mt-2">Vérifiez votre dossier Téléchargements</p>
          `,
          confirmButtonColor: '#004B5E',
          confirmButtonText: 'Parfait !'
        })

      } catch (err) {
        console.error('Download error:', err)
        Swal.fire({
          icon: 'error',
          title: 'Erreur de téléchargement',
          text: 'Impossible de télécharger le ticket. Veuillez réessayer.',
          confirmButtonColor: '#004B5E',
          confirmButtonText: 'Réessayer'
        })
      } finally {
        downloading.value = false
      }
    }

    const downloadTicketAsImage = async () => {
      if (downloadingImage.value) return

      downloadingImage.value = true
      const ticketCode = route.params.id
      const fileName = `ticket-${ticketCode}.jpg`

      try {
        // Déterminer quel élément capturer (mobile ou desktop)
        const targetRef = window.innerWidth < 768 ? ticketMobileRef.value : ticketDesktopRef.value
        if (!targetRef) {
          throw new Error('Élément ticket introuvable')
        }

        const canvas = await html2canvas(targetRef, {
          scale: 2,
          useCORS: true,
          allowTaint: true,
          backgroundColor: '#ffffff'
        })

        // Convertir en JPG et télécharger
        const link = document.createElement('a')
        link.download = fileName
        link.href = canvas.toDataURL('image/jpeg', 0.95)
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)

        Swal.fire({
          icon: 'success',
          title: 'Image téléchargée !',
          html: `<p class="text-gray-600 mb-2">Votre ticket a été sauvegardé en JPG :</p><p class="font-semibold text-primea-blue">${fileName}</p>`,
          confirmButtonColor: '#004B5E',
          confirmButtonText: 'Parfait !'
        })
      } catch (err) {
        console.error('Erreur capture image:', err)
        Swal.fire({
          icon: 'error',
          title: 'Erreur',
          text: 'Impossible de générer l\'image du ticket. Veuillez réessayer.',
          confirmButtonColor: '#004B5E'
        })
      } finally {
        downloadingImage.value = false
      }
    }

    const shareTicket = async () => {
      const ticketUrl = window.location.href
      const shareData = {
        title: `Ticket - ${ticket.value?.event?.title || 'Événement'}`,
        text: `Mon ticket ${ticket.value?.ticketType || ''} pour ${ticket.value?.event?.title || 'l\'événement'}`,
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
            confirmButtonColor: '#004B5E',
            timer: 2000,
            showConfirmButton: false
          })
        } catch {
          Swal.fire({
            icon: 'info',
            title: 'Lien du ticket',
            text: ticketUrl,
            confirmButtonColor: '#004B5E'
          })
        }
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
      ticketMobileRef,
      ticketDesktopRef,
      ticket,
      loading,
      error,
      downloading,
      downloadingImage,
      formatEventDate,
      formatPrice,
      downloadTicket,
      downloadTicketAsImage,
      shareTicket,
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
