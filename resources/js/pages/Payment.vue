<template>
  <div class="payment-page min-h-screen bg-gray-50 font-primea">
    <div class="container mx-auto px-4 py-12">
      <div class="max-w-2xl mx-auto">

        <!-- Header -->
        <div class="mb-12">
          <div class="flex items-center justify-between mb-6">
            <button @click="goBack" class="flex items-center text-primea-blue hover:text-primea-yellow transition-colors">
              <ChevronLeftIcon class="w-6 h-6 mr-2" />
              <span class="font-medium">Retour</span>
            </button>

            <router-link to="/" class="flex-1 flex justify-center">
              <img src="/images/logo.png" alt="Primea" class="h-16 hover:opacity-80 transition-opacity cursor-pointer" />
            </router-link>

            <div class="w-24"></div>
          </div>

          <div class="text-center">
            <h1 class="text-4xl font-bold text-primea-blue mb-4">Finaliser le paiement</h1>
            <p class="text-lg text-gray-600">Sélectionnez votre mode de paiement</p>
          </div>
        </div>

        <!-- État de chargement -->
        <div v-if="loading" class="text-center py-16">
          <div class="animate-spin rounded-full h-32 w-32 border-b-2 border-primea-blue mx-auto"></div>
          <p class="mt-4 text-gray-600">Chargement...</p>
        </div>

        <!-- Erreur -->
        <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-primea-xl p-8 text-center">
          <ExclamationCircleIcon class="w-16 h-16 text-red-500 mx-auto mb-4" />
          <h3 class="text-lg font-semibold text-red-800 mb-2">Erreur</h3>
          <p class="text-red-600 mb-4">{{ error }}</p>
          <button
            @click="loadOrder"
            class="bg-primea-blue text-white px-6 py-2 rounded-primea hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200"
          >
            Réessayer
          </button>
        </div>

        <!-- Contenu principal -->
        <div v-else-if="order" class="space-y-8">

          <!-- Résumé de la commande -->
          <div class="bg-white rounded-primea-xl shadow-primea p-8">
            <h3 class="text-2xl font-bold text-primea-blue mb-6">Résumé de votre commande</h3>

            <div class="space-y-4 mb-6">
              <div class="flex justify-between">
                <span class="text-gray-600">Référence :</span>
                <span class="font-mono font-bold text-primea-blue">{{ order.reference }}</span>
              </div>

              <div class="flex justify-between">
                <span class="text-gray-600">Événement :</span>
                <span class="font-semibold">{{ order.event?.title || 'N/A' }}</span>
              </div>

              <div class="flex justify-between">
                <span class="text-gray-600">Nombre de billets :</span>
                <span class="font-semibold">{{ order.quantity || order.tickets_count }}</span>
              </div>

              <div class="flex justify-between items-center pt-4 border-t-2 border-gray-200">
                <span class="text-lg font-bold text-primea-blue">Total à payer :</span>
                <span class="text-3xl font-bold text-primea-blue">{{ formatPrice(order.total_amount) }} XAF</span>
              </div>
            </div>
          </div>

          <!-- Sélection du mode de paiement -->
          <div class="bg-white rounded-primea-xl shadow-primea p-8">
            <h3 class="text-2xl font-bold text-primea-blue mb-6">Mode de paiement</h3>

            <form @submit.prevent="processPayment" class="space-y-6">

              <!-- Boutons opérateurs -->
              <div class="grid grid-cols-3 gap-4 mb-6">
                <button
                  type="button"
                  @click="selectPaymentMethod('airtel')"
                  :class="[
                    'p-4 rounded-primea-lg border-2 transition-all text-center flex flex-col items-center justify-center min-h-[80px]',
                    paymentMethod === 'airtel'
                      ? 'border-red-500 bg-red-50'
                      : 'border-gray-200 hover:border-gray-300'
                  ]"
                >
                  <img
                    src="/images/am.png"
                    alt="Airtel Money"
                    class="h-8 w-auto"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='block'"
                  />
                  <div class="text-red-600 font-bold text-sm" style="display: none">airtel money</div>
                </button>

                <button
                  type="button"
                  @click="selectPaymentMethod('moov')"
                  :class="[
                    'p-4 rounded-primea-lg border-2 transition-all text-center flex flex-col items-center justify-center min-h-[80px]',
                    paymentMethod === 'moov'
                      ? 'border-orange-500 bg-orange-50'
                      : 'border-gray-200 hover:border-gray-300'
                  ]"
                >
                  <img
                    src="/images/mm.png"
                    alt="Moov Money"
                    class="h-8 w-auto"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='block'"
                  />
                  <div class="bg-orange-500 text-white px-2 py-1 rounded text-xs font-bold" style="display: none">Moov Money</div>
                </button>

                <button
                  type="button"
                  @click="selectPaymentMethod('visa')"
                  :class="[
                    'p-4 rounded-primea-lg border-2 transition-all text-center flex flex-col items-center justify-center min-h-[80px]',
                    paymentMethod === 'visa'
                      ? 'border-blue-500 bg-blue-50'
                      : 'border-gray-200 hover:border-gray-300'
                  ]"
                >
                  <img
                    src="/images/vm.png"
                    alt="Visa"
                    class="h-8 w-auto"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='block'"
                  />
                  <div class="text-blue-600 font-bold text-sm" style="display: none">VISA</div>
                </button>
              </div>

              <!-- Champ numéro de téléphone pour Mobile Money -->
              <div v-if="paymentMethod === 'airtel' || paymentMethod === 'moov'">
                <label
                  :for="`payment-${paymentMethod}`"
                  class="block text-sm font-semibold text-primea-blue mb-2"
                >
                  Numéro {{ paymentMethod === 'airtel' ? 'Airtel Money' : 'Moov Money' }}
                </label>
                <input
                  :id="`payment-${paymentMethod}`"
                  type="tel"
                  v-model="phoneNumber"
                  :placeholder="paymentMethod === 'airtel' ? '074123456' : '062123456'"
                  class="w-full px-4 py-3 border-2 border-gray-200 rounded-primea-lg focus:border-primea-blue focus:outline-none transition-colors font-primea"
                  :maxlength="9"
                  pattern="[0-9]{9}"
                  required
                  @input="validatePhoneNumber"
                />
                <p v-if="phoneError" class="mt-2 text-sm text-red-600">{{ phoneError }}</p>
                <p v-else class="mt-2 text-sm text-gray-500">
                  {{ paymentMethod === 'airtel'
                    ? 'Format: 074xxxxxx, 076xxxxxx ou 077xxxxxx'
                    : 'Format: 060xxxxxx, 062xxxxxx, 065xxxxxx ou 066xxxxxx' }}
                </p>
              </div>

              <!-- Message pour Visa -->
              <div v-if="paymentMethod === 'visa'" class="bg-blue-50 border border-blue-200 rounded-primea-lg p-4 text-center">
                <p class="text-blue-800 mb-2 font-semibold">Paiement Visa sécurisé</p>
                <p class="text-blue-600 text-sm">Vous allez être redirigé vers notre partenaire de paiement sécurisé</p>
              </div>

              <!-- Message d'erreur -->
              <div v-if="paymentError" class="bg-red-50 border-2 border-red-200 rounded-primea-lg p-4">
                <p class="text-red-600 text-sm">{{ paymentError }}</p>
              </div>

              <!-- Bouton de paiement -->
              <button
                v-if="!ussdPushActive"
                type="submit"
                :disabled="processingPayment || !isFormValid"
                class="w-full bg-primea-blue text-white py-4 px-6 rounded-primea-lg text-lg font-bold hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200 shadow-primea-lg transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
              >
                <span v-if="processingPayment" class="flex items-center justify-center">
                  <svg class="w-6 h-6 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/>
                    <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" class="opacity-75"/>
                  </svg>
                  Traitement en cours...
                </span>
                <span v-else>Finaliser le paiement</span>
              </button>

              <!-- Section USSD Push -->
              <div v-if="ussdPushActive" class="bg-blue-50 border-2 border-blue-200 rounded-primea-xl p-6">
                <div class="text-center">
                  <!-- En attente -->
                  <div v-if="paymentStatus === 'pending'" class="space-y-4">
                    <div class="flex items-center justify-center mb-4">
                      <div class="w-16 h-16 border-4 border-blue-300 border-t-blue-600 rounded-full animate-spin"></div>
                    </div>

                    <h4 class="text-xl font-bold text-blue-800 mb-2">Push envoyé !</h4>
                    <p class="text-blue-700 mb-4">
                      Un push USSD a été envoyé au numéro <span class="font-semibold">{{ formatPhoneForDisplay(currentPayment?.phone) }}</span>.
                      Veuillez valider le paiement sur votre téléphone.
                    </p>

                    <div class="bg-white border border-blue-200 rounded-primea-lg p-4 mb-4">
                      <div class="text-3xl font-bold text-blue-600 mb-2">{{ formatCountdown(ussdCountdown) }}</div>
                      <div class="text-sm text-blue-500">Temps restant pour valider</div>

                      <div class="w-full bg-blue-200 rounded-full h-2 mt-3">
                        <div
                          class="bg-blue-600 h-2 rounded-full transition-all duration-1000"
                          :style="{ width: (ussdCountdown / 90 * 100) + '%' }"
                        ></div>
                      </div>
                    </div>

                    <p class="text-blue-600 text-sm mb-4">
                      Montant: <span class="font-semibold">{{ formatPrice(order.total_amount) }} XAF</span>
                    </p>

                    <div class="flex flex-col sm:flex-row gap-3 justify-center">
                      <button
                        type="button"
                        @click="retryUSSDPush"
                        :disabled="processingPayment || ussdCountdown > 0"
                        class="bg-blue-600 text-white px-4 py-2 rounded-primea hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        <span v-if="processingPayment">Envoi...</span>
                        <span v-else>Renvoyer le push</span>
                      </button>

                      <button
                        type="button"
                        v-if="currentPayment.payment_url && currentPayment.bill_id"
                        @click="payViaWebPage"
                        class="bg-green-600 text-white px-4 py-2 rounded-primea hover:bg-green-700 transition-colors"
                      >
                        Payer via page web
                      </button>

                      <button
                        type="button"
                        @click="changeOperator"
                        :disabled="ussdCountdown > 0"
                        class="bg-gray-500 text-white px-4 py-2 rounded-primea hover:bg-gray-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        Changer d'opérateur
                      </button>

                      <button
                        type="button"
                        @click="cancelUSSDPush"
                        class="bg-red-500 text-white px-4 py-2 rounded-primea hover:bg-red-600 transition-colors"
                      >
                        Annuler
                      </button>
                    </div>
                  </div>

                  <!-- Succès -->
                  <div v-else-if="paymentStatus === 'successful'" class="space-y-4">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                      <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                      </svg>
                    </div>

                    <h4 class="text-xl font-bold text-green-800 mb-2">Paiement réussi !</h4>
                    <p class="text-green-700 mb-4">Votre paiement a été validé avec succès.</p>
                    <p class="text-green-600 text-sm">Redirection en cours...</p>
                  </div>

                  <!-- Échec -->
                  <div v-else-if="paymentStatus === 'failed' || paymentStatus === 'cancelled' || paymentStatus === 'expired'" class="space-y-4">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                      <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                      </svg>
                    </div>

                    <h4 class="text-xl font-bold text-red-800 mb-2">Paiement échoué</h4>
                    <p class="text-red-700 mb-4">
                      {{ paymentStatus === 'cancelled' ? 'Le paiement a été annulé.' :
                         paymentStatus === 'expired' ? 'Le délai de paiement a expiré.' :
                         'Le paiement a échoué. Veuillez réessayer.' }}
                    </p>

                    <div class="flex flex-col sm:flex-row gap-3 justify-center">
                      <button
                        type="button"
                        @click="retryUSSDPush"
                        class="bg-blue-600 text-white px-4 py-2 rounded-primea hover:bg-blue-700 transition-colors"
                      >
                        Réessayer
                      </button>

                      <button
                        type="button"
                        @click="changeOperator"
                        class="bg-gray-500 text-white px-4 py-2 rounded-primea hover:bg-gray-600 transition-colors"
                      >
                        Changer d'opérateur
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </form>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import {
  ExclamationCircleIcon,
  ChevronLeftIcon
} from '@heroicons/vue/24/outline'

export default {
  name: 'Payment',
  components: {
    ExclamationCircleIcon,
    ChevronLeftIcon
  },
  setup() {
    const route = useRoute()
    const router = useRouter()
    const authStore = useAuthStore()

    const loading = ref(true)
    const error = ref('')
    const order = ref(null)
    const paymentMethod = ref('')
    const phoneNumber = ref('')
    const phoneError = ref('')
    const paymentError = ref('')
    const processingPayment = ref(false)

    // USSD Push
    const ussdPushActive = ref(false)
    const ussdCountdown = ref(90)
    const ussdTimer = ref(null)
    const paymentPollingTimer = ref(null)
    const paymentStatus = ref('')
    const currentPayment = ref(null)

    const isAuthenticated = computed(() => authStore.isAuthenticated)

    const isFormValid = computed(() => {
      if (!paymentMethod.value) return false

      if (paymentMethod.value === 'airtel' || paymentMethod.value === 'moov') {
        return !!phoneNumber.value && !phoneError.value
      }

      if (paymentMethod.value === 'visa') {
        return true
      }

      return false
    })

    const formatPrice = (price) => {
      return new Intl.NumberFormat('fr-FR').format(price)
    }

    const loadOrder = async () => {
      try {
        loading.value = true
        error.value = ''

        const reference = route.params.reference
        if (!reference) {
          throw new Error('Référence de commande manquante')
        }

        // Récupérer la commande
        const response = await fetch(`/api/v1/orders/${reference}`, {
          headers: {
            'Accept': 'application/json',
            'Authorization': `Bearer ${localStorage.getItem('token') || ''}`
          }
        })

        if (!response.ok) {
          throw new Error('Commande introuvable')
        }

        const data = await response.json()
        order.value = data.data.order

        // Vérifier que la commande est bien en attente
        if (order.value.status !== 'pending') {
          error.value = 'Cette commande ne peut pas être payée (statut: ' + order.value.status + ')'
        }

      } catch (err) {
        console.error('Erreur chargement commande:', err)
        error.value = err.message || 'Erreur lors du chargement de la commande'
      } finally {
        loading.value = false
      }
    }

    const selectPaymentMethod = (method) => {
      paymentMethod.value = method
      phoneNumber.value = ''
      phoneError.value = ''
    }

    const validatePhoneNumber = () => {
      const phone = phoneNumber.value
      const method = paymentMethod.value

      phoneError.value = ''

      if (!phone) return

      if (method === 'airtel') {
        const airtelPrefixes = ['074', '076', '077']
        const isValidAirtel = phone.length === 9 &&
                             /^[0-9]+$/.test(phone) &&
                             airtelPrefixes.some(prefix => phone.startsWith(prefix))

        if (!isValidAirtel) {
          phoneError.value = 'Le numéro Airtel Money doit faire 9 chiffres et commencer par 074, 076 ou 077'
        }
      }

      if (method === 'moov') {
        const moovPrefixes = ['060', '062', '065', '066']
        const isValidMoov = phone.length === 9 &&
                           /^[0-9]+$/.test(phone) &&
                           moovPrefixes.some(prefix => phone.startsWith(prefix))

        if (!isValidMoov) {
          phoneError.value = 'Le numéro Moov Money doit faire 9 chiffres et commencer par 060, 062, 065 ou 066'
        }
      }
    }

    const processPayment = async () => {
      try {
        processingPayment.value = true
        paymentError.value = ''

        if (!isFormValid.value) {
          throw new Error('Veuillez remplir tous les champs requis')
        }

        if (paymentMethod.value === 'airtel' || paymentMethod.value === 'moov') {
          await processEBillingPayment()
        } else if (paymentMethod.value === 'visa') {
          await processOrabankPayment()
        } else {
          throw new Error('Méthode de paiement non supportée')
        }

      } catch (err) {
        console.error('Erreur paiement:', err)
        paymentError.value = err.message || 'Erreur lors du traitement du paiement'
      } finally {
        processingPayment.value = false
      }
    }

    const processEBillingPayment = async () => {
      try {
        const paymentData = {
          order_id: order.value.id,
          gateway: paymentMethod.value === 'airtel' ? 'airtelmoney' : 'moovmoney',
          phone: phoneNumber.value,
          amount: order.value.total_amount
        }

        const paymentResponse = await fetch('/api/v1/payments/initiate', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
          },
          body: JSON.stringify(paymentData)
        })

        const paymentResult = await paymentResponse.json()

        if (!paymentResult.success) {
          throw new Error(paymentResult.message || 'Erreur lors de l\'initiation du paiement')
        }

        if (paymentResult.data?.bill_id) {
          const pushResponse = await fetch('/api/v1/payments/push-ussd', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({
              payment_id: paymentResult.data.payment.id,
              bill_id: paymentResult.data.bill_id,
              phone: phoneNumber.value,
              gateway: paymentMethod.value === 'airtel' ? 'airtelmoney' : 'moovmoney'
            })
          })

          const pushResult = await pushResponse.json()

          if (pushResult.success) {
            startUSSDPush({
              payment_id: paymentResult.data.payment.id,
              reference: order.value.reference,
              phone: phoneNumber.value,
              gateway: paymentMethod.value === 'airtel' ? 'airtelmoney' : 'moovmoney',
              amount: order.value.total_amount,
              bill_id: paymentResult.data.bill_id,
              payment_url: paymentResult.data.payment_url
            })
          } else {
            throw new Error(pushResult.message || 'Erreur lors de l\'envoi du push USSD')
          }
        } else {
          throw new Error('Erreur lors de la création de la facture E-Billing')
        }

      } catch (err) {
        console.error('Erreur E-Billing:', err)
        throw err
      }
    }

    const processOrabankPayment = async () => {
      try {
        const paymentData = {
          order_id: order.value.id,
          gateway: 'ORABANK_NG',
          amount: order.value.total_amount
        }

        const paymentResponse = await fetch('/api/v1/payments/initiate', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
          },
          body: JSON.stringify(paymentData)
        })

        const paymentResult = await paymentResponse.json()

        if (paymentResult.success && paymentResult.data?.redirect_url) {
          window.location.href = paymentResult.data.redirect_url
        } else {
          throw new Error(paymentResult.message || 'Erreur lors de l\'initiation du paiement Visa/Mastercard')
        }

      } catch (err) {
        console.error('Erreur ORABANK:', err)
        throw err
      }
    }

    const startUSSDPush = (paymentData) => {
      ussdPushActive.value = true
      ussdCountdown.value = 90
      paymentStatus.value = 'pending'
      currentPayment.value = paymentData

      startUSSDCountdown()
      startPaymentPolling(paymentData.payment_id)
    }

    const startUSSDCountdown = () => {
      if (ussdTimer.value) {
        clearInterval(ussdTimer.value)
      }

      ussdTimer.value = setInterval(() => {
        ussdCountdown.value--

        if (ussdCountdown.value <= 0) {
          clearInterval(ussdTimer.value)
          clearInterval(paymentPollingTimer.value)
          paymentStatus.value = 'expired'
        }
      }, 1000)
    }

    const startPaymentPolling = (paymentId) => {
      if (paymentPollingTimer.value) {
        clearInterval(paymentPollingTimer.value)
      }

      paymentPollingTimer.value = setInterval(async () => {
        try {
          const response = await fetch(`/api/v1/payments/${paymentId}/status`, {
            headers: {
              'Accept': 'application/json',
              'Authorization': `Bearer ${localStorage.getItem('token') || ''}`
            }
          })

          if (response.ok) {
            const data = await response.json()
            const status = data.data?.payment?.status

            if (status === 'success' || status === 'successful') {
              paymentStatus.value = 'successful'
              clearInterval(paymentPollingTimer.value)
              clearInterval(ussdTimer.value)

              setTimeout(() => {
                router.push(`/ticket-success?reference=${currentPayment.value.reference}`)
              }, 2000)
            } else if (status === 'failed' || status === 'cancelled' || status === 'expired') {
              paymentStatus.value = status
              clearInterval(paymentPollingTimer.value)
              clearInterval(ussdTimer.value)

              setTimeout(() => {
                cancelUSSDPush()
              }, 3000)
            }
          }
        } catch (err) {
          console.error('Erreur vérification statut:', err)
        }
      }, 3000)
    }

    const cancelUSSDPush = () => {
      ussdPushActive.value = false
      ussdCountdown.value = 90
      paymentStatus.value = ''
      currentPayment.value = null

      if (ussdTimer.value) {
        clearInterval(ussdTimer.value)
      }
      if (paymentPollingTimer.value) {
        clearInterval(paymentPollingTimer.value)
      }
    }

    const retryUSSDPush = async () => {
      if (!currentPayment.value) return

      try {
        processingPayment.value = true

        const response = await fetch('/api/v1/payments/push-ussd', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': `Bearer ${localStorage.getItem('token') || ''}`
          },
          body: JSON.stringify({
            payment_id: currentPayment.value.payment_id,
            phone: currentPayment.value.phone,
            gateway: currentPayment.value.gateway
          })
        })

        if (response.ok) {
          ussdCountdown.value = 90
          paymentStatus.value = 'pending'
          startUSSDCountdown()
          startPaymentPolling(currentPayment.value.payment_id)
        } else {
          const errorData = await response.json()
          paymentError.value = errorData.message || 'Erreur lors du renvoi du push'
        }
      } catch (err) {
        paymentError.value = 'Erreur de connexion'
      } finally {
        processingPayment.value = false
      }
    }

    const payViaWebPage = () => {
      if (!currentPayment.value?.payment_url || !currentPayment.value?.bill_id) {
        console.error('URL de paiement manquante')
        return
      }

      const eBillingUrl = `${currentPayment.value.payment_url}?invoice=${currentPayment.value.bill_id}`
      window.location.href = eBillingUrl
    }

    const changeOperator = () => {
      ussdPushActive.value = false
      ussdCountdown.value = 90
      paymentStatus.value = ''
      currentPayment.value = null

      if (ussdTimer.value) {
        clearInterval(ussdTimer.value)
      }
      if (paymentPollingTimer.value) {
        clearInterval(paymentPollingTimer.value)
      }

      paymentMethod.value = ''
      phoneNumber.value = ''
    }

    const formatCountdown = (seconds) => {
      const mins = Math.floor(seconds / 60)
      const secs = seconds % 60
      return `${mins}:${secs.toString().padStart(2, '0')}`
    }

    const formatPhoneForDisplay = (phone) => {
      if (!phone) return ''
      return phone.replace(/(\d{3})(\d{2})(\d{2})(\d{2})/, '$1 $2 $3 $4')
    }

    const goBack = () => {
      router.back()
    }

    onMounted(() => {
      loadOrder()
    })

    onUnmounted(() => {
      if (ussdTimer.value) {
        clearInterval(ussdTimer.value)
      }
      if (paymentPollingTimer.value) {
        clearInterval(paymentPollingTimer.value)
      }
    })

    return {
      loading,
      error,
      order,
      paymentMethod,
      phoneNumber,
      phoneError,
      paymentError,
      processingPayment,
      isAuthenticated,
      isFormValid,
      ussdPushActive,
      ussdCountdown,
      paymentStatus,
      currentPayment,
      formatPrice,
      loadOrder,
      selectPaymentMethod,
      validatePhoneNumber,
      processPayment,
      startUSSDPush,
      cancelUSSDPush,
      retryUSSDPush,
      payViaWebPage,
      changeOperator,
      formatCountdown,
      formatPhoneForDisplay,
      goBack
    }
  }
}
</script>

<style scoped>
.payment-page {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
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

.text-primea-yellow {
  color: #fab511;
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

.hover\:text-primea-yellow:hover {
  color: #fab511;
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

.transition-colors {
  transition: color 0.2s ease-in-out, background-color 0.2s ease-in-out, border-color 0.2s ease-in-out;
}

.transition-all {
  transition: all 0.2s ease-in-out;
}
</style>
