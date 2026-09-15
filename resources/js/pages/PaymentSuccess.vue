<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-primea-xl shadow-primea-lg p-8 max-w-md w-full text-center">
      <!-- Loading state -->
      <div v-if="loading">
        <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-primea-blue mx-auto mb-4"></div>
        <h2 class="text-xl font-bold text-primea-blue mb-2">Vérification du paiement</h2>
        <p class="text-gray-600">Veuillez patienter...</p>
      </div>

      <!-- Error state -->
      <div v-else-if="error">
        <div class="bg-red-50 rounded-full p-4 w-16 h-16 mx-auto mb-4">
          <svg class="w-8 h-8 text-red-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </div>
        <h2 class="text-xl font-bold text-red-600 mb-2">Erreur</h2>
        <p class="text-gray-600 mb-6">{{ error }}</p>
        <button
          @click="goToCheckout"
          class="bg-primea-blue text-white px-6 py-3 rounded-primea-lg font-semibold hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200"
        >
          Réessayer
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

export default {
  name: 'PaymentSuccess',
  setup() {
    const route = useRoute()
    const router = useRouter()
    const loading = ref(true)
    const error = ref(null)

    const checkPaymentStatus = async () => {
      try {
        const reference = route.query.reference

        if (!reference) {
          throw new Error('Référence de paiement manquante')
        }

        // Récupérer les détails du paiement via l'API
        const response = await fetch(`/api/v1/payments/${reference}/status`, {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          }
        })

        const data = await response.json()

        if (!response.ok) {
          throw new Error(data.message || 'Erreur lors de la vérification du paiement')
        }

        // Vérifier le statut du paiement
        const paymentStatus = data.data?.payment?.status || data.payment?.status

        if (paymentStatus === 'completed' || paymentStatus === 'success' || paymentStatus === 'successful') {
          // Paiement réussi, récupérer la commande
          const orderReference = data.data?.payment?.order?.reference || data.payment?.order?.reference || data.order?.reference

          if (orderReference) {
            // Rediriger vers la page de succès avec la référence de commande
            router.push({
              name: 'ticket-success',
              query: { reference: orderReference }
            })
          } else {
            throw new Error('Référence de commande introuvable')
          }
        } else if (paymentStatus === 'pending' || paymentStatus === 'initiated') {
          // Paiement en attente
          error.value = 'Votre paiement est en cours de traitement. Veuillez patienter quelques instants.'
          setTimeout(checkPaymentStatus, 3000) // Réessayer après 3 secondes
        } else {
          // Paiement échoué ou expiré
          throw new Error('Le paiement a échoué ou a expiré. Veuillez réessayer.')
        }

      } catch (err) {
        console.error('Erreur vérification paiement:', err)
        error.value = err.message || 'Une erreur est survenue lors de la vérification du paiement'
        loading.value = false
      }
    }

    const goToCheckout = () => {
      router.push({ name: 'events' })
    }

    onMounted(() => {
      checkPaymentStatus()
    })

    return {
      loading,
      error,
      goToCheckout
    }
  }
}
</script>

<style scoped>
.rounded-primea-xl {
  border-radius: 20px;
}

.rounded-primea-lg {
  border-radius: 16px;
}

.shadow-primea-lg {
  box-shadow: 0 8px 30px rgba(39, 45, 99, 0.15);
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
  background-color: #fab511;
}

.hover\:text-primea-blue:hover {
  color: #272d63;
}

.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
