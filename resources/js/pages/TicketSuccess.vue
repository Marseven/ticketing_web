<template>
  <div class="ticket-success-page min-h-screen bg-gray-50 font-primea">
    <div class="container mx-auto px-4 py-12">
      <div class="max-w-2xl mx-auto">

        <!-- En-tête -->
        <div class="text-center mb-8">
          <router-link to="/">
            <img src="/images/logo.png" alt="Primea" class="h-16 mx-auto mb-6" />
          </router-link>
        </div>

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
        <div v-else-if="order" class="bg-white rounded-primea-xl shadow-primea p-8">

          <!-- Success Icon -->
          <div class="text-center mb-8">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <CheckCircleIcon class="w-12 h-12 text-green-600" />
            </div>
            <h1 class="text-3xl font-bold text-primea-blue mb-2">Paiement réussi !</h1>
            <p class="text-gray-600">Votre commande a été confirmée</p>
          </div>

          <!-- Order Details -->
          <div class="bg-primea-blue/5 rounded-primea-lg p-6 mb-6">
            <h3 class="text-lg font-bold text-primea-blue mb-4">Détails de votre commande</h3>

            <div class="space-y-3">
              <div class="flex justify-between">
                <span class="text-gray-600">Référence :</span>
                <span class="font-semibold text-primea-blue">{{ order.reference }}</span>
              </div>

              <div class="flex justify-between">
                <span class="text-gray-600">Événement :</span>
                <span class="font-semibold">{{ order.event?.title || 'N/A' }}</span>
              </div>

              <div class="flex justify-between">
                <span class="text-gray-600">Quantité :</span>
                <span class="font-semibold">{{ order.quantity }} ticket(s)</span>
              </div>

              <div class="flex justify-between">
                <span class="text-gray-600">Montant payé :</span>
                <span class="font-semibold text-green-600">{{ formatPrice(order.total_amount) }} FCFA</span>
              </div>

              <div class="flex justify-between">
                <span class="text-gray-600">Date :</span>
                <span class="font-semibold">{{ formatDate(order.created_at) }}</span>
              </div>
            </div>
          </div>

          <!-- Tickets Section -->
          <div v-if="tickets && tickets.length > 0" class="mb-6">
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

          <!-- Download All Button -->
          <div class="text-center mb-6">
            <button
              v-if="tickets && tickets.length > 0"
              @click="downloadAllTickets"
              class="bg-primea-blue text-white px-8 py-3 rounded-primea-lg font-bold hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200 shadow-primea-lg transform hover:scale-105"
            >
              Télécharger tous les tickets
            </button>
          </div>

          <!-- Email Notification -->
          <div class="bg-blue-50 border border-blue-200 rounded-primea-lg p-4 mb-6 text-center">
            <p class="text-blue-800 text-sm">
              📧 Un email de confirmation avec vos tickets a été envoyé à <strong>{{ order.guest_email || 'votre adresse email' }}</strong>
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
              class="text-center bg-primea-yellow text-primea-blue px-6 py-3 rounded-primea hover:bg-yellow-500 transition-all duration-200 font-semibold"
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
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { CheckCircleIcon, ExclamationCircleIcon } from '@heroicons/vue/24/outline'

export default {
  name: 'TicketSuccess',
  components: {
    CheckCircleIcon,
    ExclamationCircleIcon
  },
  setup() {
    const route = useRoute()
    const router = useRouter()

    const loading = ref(true)
    const error = ref('')
    const order = ref(null)
    const tickets = ref([])

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

        // Récupérer la commande via l'API avec le préfixe v1
        const response = await fetch(`/api/v1/guest/orders/${reference}`, {
          headers: {
            'Accept': 'application/json'
          }
        })

        if (!response.ok) {
          throw new Error('Commande introuvable')
        }

        const data = await response.json()

        if (data.success) {
          order.value = data.data.order
          tickets.value = data.data.order.tickets || []
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
      return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
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
      loading,
      error,
      order,
      tickets,
      formatPrice,
      formatDate,
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
  font-family: 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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
