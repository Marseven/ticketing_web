<template>
  <div class="checkout-page min-h-screen font-primea">
    
    <!-- Desktop/Tablet Layout -->
    <div class="hidden md:block bg-gray-50 min-h-screen">
      
      <div class="container mx-auto px-4 py-12">
        <div class="max-w-4xl mx-auto">
          
          <!-- Header Desktop -->
          <div class="text-center mb-12">
            <img src="/images/logo.png" alt="Primea" class="h-16 mx-auto mb-6" />
            <h1 class="text-4xl font-bold text-primea-blue mb-4">Finaliser votre commande</h1>
            <p class="text-lg text-gray-600">Sélectionnez vos billets et procédez au paiement</p>
          </div>

          <!-- États de chargement et d'erreur -->
          <div v-if="eventLoading" class="text-center py-8">
            <div class="animate-spin rounded-full h-32 w-32 border-b-2 border-primea-blue mx-auto"></div>
            <p class="mt-4 text-gray-600">Chargement de l'événement...</p>
          </div>

          <div v-else-if="eventError" class="text-center py-8">
            <div class="bg-red-50 border border-red-200 rounded-primea-xl p-8">
              <ExclamationCircleIcon class="w-16 h-16 text-red-500 mx-auto mb-4" />
              <h3 class="text-lg font-semibold text-red-800 mb-2">Erreur de chargement</h3>
              <p class="text-red-600 mb-4">{{ eventError }}</p>
              <button 
                @click="loadEvent" 
                class="bg-primea-blue text-white px-6 py-2 rounded-primea hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200"
              >
                Réessayer
              </button>
            </div>
          </div>

          <div v-else-if="event" class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            
            <!-- Colonne gauche - Détails événement -->
            <div class="space-y-8">
              <!-- Carte événement -->
              <div class="bg-white rounded-primea-xl shadow-primea overflow-hidden">
                <div class="relative h-64">
                  <img 
                    v-if="event?.image_url" 
                    :src="event.image_url" 
                    :alt="event.title"
                    class="w-full h-full object-cover"
                  />
                  <div v-else class="w-full h-full bg-primea-gradient flex items-center justify-center">
                    <PhotoIcon class="w-24 h-24 text-white/50" />
                  </div>
                  <div class="absolute inset-0 bg-primea-blue/60"></div>
                  <div class="absolute inset-0 p-6 text-white flex flex-col justify-end">
                    <div class="bg-primea-yellow text-primea-blue px-3 py-1 rounded-primea text-sm font-bold w-fit mb-2">
                      {{ (event?.category?.name || event?.category_name || event?.category || 'ÉVÉNEMENT').toUpperCase() }}
                    </div>
                    <h2 class="text-2xl font-bold mb-2">{{ event?.title || 'Chargement...' }}</h2>
                    <p class="text-white/90">{{ formatEventDate }}</p>
                  </div>
                </div>
                
                <div class="p-6">
                  <div class="space-y-3">
                    <div class="flex items-center gap-3">
                      <MapPinIcon class="w-5 h-5 text-primea-blue" />
                      <span class="text-gray-700">{{ event?.venue_name || "Lieu à confirmer" }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                      <ClockIcon class="w-5 h-5 text-primea-blue" />
                      <span class="text-gray-700">{{ eventTime }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Compte à rebours Desktop -->
              <div class="bg-white rounded-primea-xl shadow-primea p-6">
                <h3 class="text-lg font-bold text-primea-blue mb-4 text-center">
                  {{ isEventPassed ? 'Événement terminé' : 'Temps restant pour l\'événement' }}
                </h3>
                
                <div v-if="isEventPassed" class="text-center">
                  <div class="bg-red-50 border border-red-200 rounded-primea-lg p-4">
                    <ExclamationCircleIcon class="w-12 h-12 text-red-500 mx-auto mb-2" />
                    <p class="text-red-600 font-semibold">Cet événement est terminé</p>
                    <p class="text-red-500 text-sm">Les billets ne sont plus disponibles</p>
                  </div>
                </div>

                <div v-else class="flex justify-center space-x-2">
                  <div v-if="countdown.days > 0" class="text-center bg-primea-blue text-white rounded-primea-lg p-4 min-w-[70px]">
                    <div class="text-2xl font-bold">{{ countdown.days }}</div>
                    <div class="text-xs text-primea-yellow">JOURS</div>
                  </div>
                  <div class="text-center bg-primea-blue text-white rounded-primea-lg p-4 min-w-[70px]">
                    <div class="text-2xl font-bold">{{ countdown.hours }}</div>
                    <div class="text-xs text-primea-yellow">HEURES</div>
                  </div>
                  <div class="text-center bg-primea-blue text-white rounded-primea-lg p-4 min-w-[70px]">
                    <div class="text-2xl font-bold">{{ countdown.minutes }}</div>
                    <div class="text-xs text-primea-yellow">MIN</div>
                  </div>
                  <div class="text-center bg-primea-blue text-white rounded-primea-lg p-4 min-w-[70px]">
                    <div class="text-2xl font-bold">{{ countdown.seconds }}</div>
                    <div class="text-xs text-primea-yellow">SEC</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Colonne droite - Formulaire de commande -->
            <div class="bg-white rounded-primea-xl shadow-primea p-8">
              <h3 class="text-2xl font-bold text-primea-blue mb-6">
                {{ isEventPassed ? 'Commande impossible' : 'Votre commande' }}
              </h3>

              <!-- Message événement passé -->
              <div v-if="isEventPassed" class="bg-red-50 border border-red-200 rounded-primea-lg p-6 text-center">
                <ExclamationCircleIcon class="w-16 h-16 text-red-500 mx-auto mb-4" />
                <h4 class="text-lg font-semibold text-red-800 mb-2">Événement terminé</h4>
                <p class="text-red-600 mb-4">Cet événement s'est déjà déroulé. Il n'est plus possible d'acheter des billets.</p>
                <router-link 
                  to="/events" 
                  class="bg-primea-blue text-white px-6 py-2 rounded-primea hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200"
                >
                  Voir d'autres événements
                </router-link>
              </div>
              
              <form v-if="!isEventPassed" @submit.prevent="processOrder" class="space-y-6">
                
                <!-- Sélection billets Desktop -->
                <div>
                  <label class="block text-sm font-semibold text-primea-blue mb-3">Nombre de billets</label>
                  <input 
                    type="number"
                    v-model="orderForm.quantity"
                    min="1"
                    max="10"
                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-primea-lg focus:border-primea-blue focus:outline-none transition-colors"
                    required
                  />
                </div>

                <!-- Type de billet Desktop -->
                <div>
                  <label class="block text-sm font-semibold text-primea-blue mb-3">Type de billet</label>
                  <select 
                    v-model="orderForm.ticketTypeId"
                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-primea-lg focus:border-primea-blue focus:outline-none transition-colors appearance-none bg-white"
                    required
                    :disabled="eventLoading || availableTicketTypes.length === 0"
                  >
                    <option value="">Sélectionnez un type de billet</option>
                    <option 
                      v-for="ticketType in availableTicketTypes" 
                      :key="ticketType.id" 
                      :value="ticketType.id"
                    >
                      {{ ticketType.name }} - {{ formatPrice(ticketType.price) }} FCFA 
                      ({{ getAvailableQuantityText(ticketType) }})
                    </option>
                  </select>
                  <div v-if="availableTicketTypes.length === 0 && !eventLoading" class="text-sm text-red-600 mt-1">
                    Aucun billet disponible pour cet événement
                  </div>
                </div>

                <!-- Informations de l'acheteur -->
                <div class="bg-primea-blue/5 rounded-primea-xl p-6 space-y-4">
                  <h4 class="text-lg font-semibold text-primea-blue mb-4">Vos informations</h4>
                  
                  <div>
                    <label class="block text-sm font-semibold text-primea-blue mb-2">Nom complet *</label>
                    <input 
                      type="text"
                      v-model="orderForm.guestName"
                      placeholder="Votre nom complet"
                      class="w-full px-4 py-3 border-2 border-gray-200 rounded-primea-lg focus:border-primea-blue focus:outline-none transition-colors"
                      required
                    />
                  </div>
                  
                  <div>
                    <label class="block text-sm font-semibold text-primea-blue mb-2">Email *</label>
                    <input 
                      type="email"
                      v-model="orderForm.guestEmail"
                      placeholder="votre.email@example.com"
                      class="w-full px-4 py-3 border-2 border-gray-200 rounded-primea-lg focus:border-primea-blue focus:outline-none transition-colors"
                      required
                    />
                    <p class="text-sm text-gray-500 mt-1">Vos billets vous seront envoyés par email</p>
                  </div>
                  
                  <div>
                    <label class="block text-sm font-semibold text-primea-blue mb-2">Téléphone (optionnel)</label>
                    <input 
                      type="tel"
                      v-model="orderForm.guestPhone"
                      placeholder="+241 XX XX XX XX"
                      class="w-full px-4 py-3 border-2 border-gray-200 rounded-primea-lg focus:border-primea-blue focus:outline-none transition-colors"
                    />
                  </div>
                </div>

                <!-- Total Desktop -->
                <div v-if="orderForm.quantity && orderForm.ticketTypeId" class="bg-primea-blue/5 rounded-primea-xl p-6">
                  <div class="flex justify-between items-center">
                    <span class="text-lg font-semibold text-primea-blue">Total à payer :</span>
                    <span class="text-3xl font-bold text-primea-blue">{{ formatPrice(totalAmount) }} FCFA</span>
                  </div>
                </div>

                <!-- Moyens de paiement Desktop -->
                <div>
                  <label class="block text-sm font-semibold text-primea-blue mb-4">Moyen de paiement</label>
                  
                  <div class="grid grid-cols-3 gap-4 mb-6">
                    <button 
                      type="button"
                      @click="selectPaymentMethod('airtel')"
                      :class="[
                        'p-4 rounded-primea-lg border-2 transition-all text-center flex flex-col items-center justify-center min-h-[80px]',
                        orderForm.paymentMethod === 'airtel' 
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
                        orderForm.paymentMethod === 'moov' 
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
                        orderForm.paymentMethod === 'visa' 
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

                  <!-- Champs paiement Desktop -->
                  <div v-if="orderForm.paymentMethod === 'airtel' || orderForm.paymentMethod === 'moov'">
                    <label 
                      :for="`payment-${orderForm.paymentMethod}`" 
                      class="block text-sm font-semibold text-primea-blue mb-2"
                    >
                      Numéro {{ orderForm.paymentMethod === 'airtel' ? 'Airtel Money' : 'Moov Money' }}
                    </label>
                    <input 
                      :id="`payment-${orderForm.paymentMethod}`"
                      type="tel"
                      v-model="orderForm.phoneNumber"
                      :placeholder="orderForm.paymentMethod === 'airtel' ? '074123456' : '062123456'"
                      class="w-full px-4 py-3 border-2 border-gray-200 rounded-primea-lg focus:border-primea-blue focus:outline-none transition-colors font-primea"
                      :maxlength="9"
                      pattern="[0-9]{9}"
                      required
                      @input="validatePhoneNumber"
                    />
                    <p v-if="phoneError" class="mt-2 text-sm text-red-600">{{ phoneError }}</p>
                    <p v-else class="mt-2 text-sm text-gray-500">
                      {{ orderForm.paymentMethod === 'airtel' 
                        ? 'Format: 074xxxxxx, 076xxxxxx ou 077xxxxxx' 
                        : 'Format: 060xxxxxx, 062xxxxxx, 065xxxxxx ou 066xxxxxx' }}
                    </p>
                  </div>

                  <div v-if="orderForm.paymentMethod === 'visa'" class="space-y-4">
                    <div class="bg-blue-50 border border-blue-200 rounded-primea-lg p-4 text-center">
                      <p class="text-blue-800 mb-4 font-semibold">Paiement Visa sécurisé</p>
                      <p class="text-blue-600 text-sm mb-4">Vous allez être redirigé vers notre partenaire de paiement sécurisé</p>
                      <button 
                        type="button"
                        @click="redirectToVisaPayment"
                        class="bg-blue-600 text-white px-6 py-3 rounded-primea-lg font-semibold hover:bg-blue-700 transition-all duration-200 flex items-center justify-center gap-2 mx-auto"
                      >
                        <img 
                          src="/images/visa-logo.png" 
                          alt="Visa"
                          class="h-6 w-auto"
                          onerror="this.style.display='none'"
                        />
                        Procéder au paiement Visa
                        <ArrowTopRightOnSquareIcon class="w-4 h-4" />
                      </button>
                      <p class="text-xs text-gray-500 mt-2">Paiement 100% sécurisé avec chiffrement SSL</p>
                    </div>
                  </div>
                </div>

                <!-- Message d'erreur Desktop -->
                <div v-if="error" class="bg-red-50 border-2 border-red-200 rounded-primea-lg p-4">
                  <p class="text-red-600 text-sm">{{ error }}</p>
                </div>

                <!-- Bouton de paiement Desktop -->
                <button 
                  type="submit"
                  :disabled="loading || !isFormValid"
                  class="w-full bg-primea-blue text-white py-4 px-6 rounded-primea-lg text-lg font-bold hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200 shadow-primea-lg transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
                  style="background-color: #272d63;"
                >
                  <span v-if="loading" class="flex items-center justify-center">
                    <svg class="w-6 h-6 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                      <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/>
                      <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" class="opacity-75"/>
                    </svg>
                    Traitement en cours...
                  </span>
                  <span v-else>Finaliser le paiement</span>
                </button>

                <!-- Lien récupérer ticket Desktop -->
                <div class="text-center pt-4">
                  <router-link 
                    to="/retrieve-ticket"
                    class="text-primea-blue hover:text-primea-yellow transition-colors"
                  >
                    Récupérer mon ticket perdu
                  </router-link>
                </div>

              </form>
            </div>
          </div>
        </div>
      </div>
      
    </div>

    <!-- Mobile Layout -->
    <div class="md:hidden bg-gray-100 min-h-screen py-8 px-4">
      <div class="max-w-md mx-auto">
      
        <!-- En-tête selon maquette commande.png -->
        <div class="flex items-center justify-between mb-8">
          <button @click="goBack" class="text-gray-600 hover:text-gray-800">
            <ChevronLeftIcon class="w-6 h-6" />
          </button>
          
          <div class="text-center">
            <img src="/images/logo.png" alt="Primea" class="h-8 mx-auto mb-2" />
            <div class="text-right">
              <div class="text-lg font-bold text-blue-600">La Billetterie</div>
              <div class="text-xs text-gray-500">Simple, Rapide et Sécurisée</div>
            </div>
          </div>
          
          <button class="text-gray-600">
            <Bars3Icon class="w-6 h-6" />
          </button>
        </div>

        <!-- Contenu mobile complet ici... -->
        <div class="text-center text-gray-500 py-8">
          Version mobile en cours de développement
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useEventsStore } from '../stores/events'
import { guestService } from '../services/api'
import { 
  ExclamationCircleIcon,
  PhotoIcon,
  MapPinIcon,
  ClockIcon,
  ArrowTopRightOnSquareIcon,
  ChevronLeftIcon,
  Bars3Icon
} from '@heroicons/vue/24/outline'
export default {
  name: 'Checkout',
  components: {
    ExclamationCircleIcon,
    PhotoIcon,
    MapPinIcon,
    ClockIcon,
    ArrowTopRightOnSquareIcon,
    ChevronLeftIcon,
    Bars3Icon
  },
  setup() {
    const route = useRoute()
    const router = useRouter()
    const eventsStore = useEventsStore()

    // État de l'événement et de la commande
    const event = ref(null)
    const eventLoading = ref(true)
    const eventError = ref('')

    const loading = ref(false)
    const error = ref('')
    const phoneError = ref('')
    const countdownTimer = ref(null)

    // État du compte à rebours
    const countdown = ref({
      days: 0,
      hours: 0,
      minutes: 0,
      seconds: 0
    })

    // Formulaire de commande
    const orderForm = ref({
      quantity: 1,
      ticketTypeId: '',
      paymentMethod: '',
      phoneNumber: '',
      cardNumber: '',
      expiryDate: '',
      cvv: '',
      guestName: '',
      guestEmail: '',
      guestPhone: ''
    })

    // Computed properties
    const formatEventDate = computed(() => {
      if (!event.value?.schedules || event.value.schedules.length === 0) {
        return 'Date à confirmer'
      }
      
      const date = new Date(event.value.schedules[0].starts_at)
      return date.toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
      }).toUpperCase()
    })

    const eventTime = computed(() => {
      if (!event.value?.schedules || event.value.schedules.length === 0) {
        return 'Heure à confirmer'
      }
      
      const date = new Date(event.value.schedules[0].starts_at)
      return date.toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit'
      })
    })

    const eventDate = computed(() => {
      if (!event.value?.schedules || event.value.schedules.length === 0) {
        return null
      }
      return new Date(event.value.schedules[0].starts_at)
    })

    const isEventPassed = computed(() => {
      if (!eventDate.value) return false
      return new Date() > eventDate.value
    })

    const timeUntilEvent = computed(() => {
      if (!eventDate.value) return null
      
      const now = new Date()
      const timeDiff = eventDate.value.getTime() - now.getTime()
      
      if (timeDiff <= 0) {
        return {
          days: 0,
          hours: 0,
          minutes: 0,
          seconds: 0,
          expired: true
        }
      }

      const days = Math.floor(timeDiff / (1000 * 60 * 60 * 24))
      const hours = Math.floor((timeDiff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))
      const minutes = Math.floor((timeDiff % (1000 * 60 * 60)) / (1000 * 60))
      const seconds = Math.floor((timeDiff % (1000 * 60)) / 1000)

      return {
        days,
        hours,
        minutes,
        seconds,
        expired: false
      }
    })

    const availableTicketTypes = computed(() => {
      if (!event.value?.ticket_types || isEventPassed.value) return []
      return event.value.ticket_types.filter(type => {
        // Si remaining_quantity est défini, l'utiliser
        if (type.remaining_quantity !== undefined && type.remaining_quantity !== null) {
          return type.remaining_quantity > 0;
        }
        // Sinon, si available_quantity est défini
        if (type.available_quantity !== undefined && type.available_quantity !== null) {
          const sold = type.sold_quantity || 0;
          return (type.available_quantity - sold) > 0;
        }
        // Si available_quantity est null (quantité illimitée)
        if (type.available_quantity === null) {
          return true;
        }
        // Fallback sur l'ancien calcul
        return (type.quantity - (type.sold || 0)) > 0
      })
    })

    const canPurchaseTickets = computed(() => {
      return !isEventPassed.value && availableTicketTypes.value.length > 0
    })

    const totalAmount = computed(() => {
      if (!orderForm.value.quantity || !orderForm.value.ticketTypeId) return 0
      
      const selectedTicketType = availableTicketTypes.value.find(
        type => type.id == orderForm.value.ticketTypeId
      )
      
      if (!selectedTicketType) return 0
      
      return selectedTicketType.price * orderForm.value.quantity
    })

    const isFormValid = computed(() => {
      if (!orderForm.value.quantity || !orderForm.value.ticketTypeId || !orderForm.value.paymentMethod) {
        return false
      }

      // Vérifier les informations de l'invité
      if (!orderForm.value.guestName || !orderForm.value.guestEmail) {
        return false
      }

      if (orderForm.value.paymentMethod === 'airtel' || orderForm.value.paymentMethod === 'moov') {
        return !!orderForm.value.phoneNumber && !phoneError.value
      }

      if (orderForm.value.paymentMethod === 'visa') {
        // Pour Visa, on a juste besoin de la sélection puisque le paiement se fait par redirection
        return true
      }

      return true
    })

    // Méthodes
    const formatPrice = (price) => {
      return new Intl.NumberFormat('fr-FR').format(price)
    }

    const getAvailableQuantityText = (ticketType) => {
      if (ticketType.remaining_quantity !== undefined && ticketType.remaining_quantity !== null) {
        return ticketType.remaining_quantity > 0 ? `${ticketType.remaining_quantity} disponibles` : 'Épuisé'
      }
      if (ticketType.available_quantity === null) {
        return 'Illimité'
      }
      if (ticketType.available_quantity !== undefined && ticketType.available_quantity !== null) {
        const remaining = ticketType.available_quantity - (ticketType.sold_quantity || 0)
        return remaining > 0 ? `${remaining} disponibles` : 'Épuisé'
      }
      // Fallback
      const qty = ticketType.quantity - (ticketType.sold || 0)
      return qty > 0 ? `${qty} disponibles` : 'Épuisé'
    }

    const loadEvent = async () => {
      const eventSlug = route.params.eventSlug
      if (!eventSlug) {
        eventError.value = 'ID d\'événement manquant'
        eventLoading.value = false
        return
      }

      try {
        eventLoading.value = true
        eventError.value = ''
        
        const data = await eventsStore.fetchEvent(eventSlug)
        event.value = data.event
        
        // Si l'événement n'a qu'un seul type de billet, le sélectionner automatiquement
        if (event.value?.ticket_types && event.value.ticket_types.length === 1) {
          orderForm.value.ticketTypeId = event.value.ticket_types[0].id
        }
        
      } catch (err) {
        eventError.value = err.message || 'Erreur lors du chargement de l\'événement'
      } finally {
        eventLoading.value = false
      }
    }

    const validatePhoneNumber = () => {
      const phone = orderForm.value.phoneNumber
      const method = orderForm.value.paymentMethod
      
      phoneError.value = ''
      
      if (!phone) {
        return
      }
      
      // Validation pour Airtel Money
      if (method === 'airtel') {
        const airtelPrefixes = ['074', '076', '077']
        const isValidAirtel = phone.length === 9 && 
                             /^[0-9]+$/.test(phone) &&
                             airtelPrefixes.some(prefix => phone.startsWith(prefix))
        
        if (!isValidAirtel) {
          phoneError.value = 'Le numéro Airtel Money doit faire 9 chiffres et commencer par 074, 076 ou 077'
        }
      }
      
      // Validation pour Moov Money
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

    const selectPaymentMethod = (method) => {
      orderForm.value.paymentMethod = method
      // Reset payment fields when changing method
      orderForm.value.phoneNumber = ''
      orderForm.value.cardNumber = ''
      orderForm.value.expiryDate = ''
      orderForm.value.cvv = ''
      phoneError.value = ''
    }

    const redirectToVisaPayment = () => {
      if (!isFormValid.value) {
        error.value = 'Veuillez remplir tous les champs requis avant de procéder au paiement'
        return
      }

      // Simuler la redirection vers un gateway de paiement Visa
      // Dans un vrai cas, cela redirigerait vers Stripe, PayPal, ou un autre processeur
      const paymentData = {
        eventSlug: route.params.eventSlug,
        eventTitle: event.value?.title,
        quantity: orderForm.value.quantity,
        ticketTypeId: orderForm.value.ticketTypeId,
        amount: totalAmount.value,
        currency: 'XAF', // Franc CFA Central
        returnUrl: `${window.location.origin}/checkout/success`,
        cancelUrl: `${window.location.origin}/checkout/cancel`
      }

      // Pour la démonstration, on peut soit :
      // 1. Rediriger vers une page de paiement externe simulée
      // 2. Ouvrir une popup
      // 3. Afficher un message
      
      console.log('Redirection vers paiement Visa avec:', paymentData)
      
      // Exemple de redirection (remplacer par l'URL réelle du gateway)
      const paymentUrl = `https://payment-gateway.example.com/visa-payment?` + 
        new URLSearchParams(paymentData).toString()
      
      // window.location.href = paymentUrl
      
      // Pour la démonstration, on simule juste
      alert('Redirection vers le paiement Visa sécurisé...\n' + 
            `Montant: ${formatPrice(totalAmount.value)} FCFA\n` + 
            `Événement: ${event.value?.title}`)
    }

    const updateCountdown = () => {
      const timeLeft = timeUntilEvent.value
      if (timeLeft) {
        countdown.value = {
          days: timeLeft.days,
          hours: timeLeft.hours,
          minutes: timeLeft.minutes,
          seconds: timeLeft.seconds
        }
      }
    }

    const startCountdown = () => {
      // Mise à jour initiale
      updateCountdown()
      
      // Mise à jour chaque seconde
      countdownTimer.value = setInterval(() => {
        updateCountdown()
        
        // Arrêter le timer si l'événement est passé
        if (timeUntilEvent.value?.expired) {
          clearInterval(countdownTimer.value)
        }
      }, 1000)
    }

    const processOrder = async () => {
      try {
        loading.value = true
        error.value = ''

        // Vérifier si l'événement est passé
        if (isEventPassed.value) {
          throw new Error('Impossible de commander des billets pour un événement terminé')
        }

        // Validation
        if (!isFormValid.value) {
          throw new Error('Veuillez remplir tous les champs requis')
        }

        // Préparer les données de la commande
        const orderData = {
          event_slug: event.value.slug,
          ticket_type_id: orderForm.value.ticketTypeId,
          quantity: orderForm.value.quantity,
          guest_name: orderForm.value.guestName,
          guest_email: orderForm.value.guestEmail,
          guest_phone: orderForm.value.guestPhone || null
        }

        // Créer la commande via l'API guest
        const response = await guestService.createGuestOrder(orderData)
        
        if (response.data.success) {
          const order = response.data.data.order
          // Rediriger vers la page de paiement avec la référence de commande
          router.push(`/payment/${order.reference}`)
        } else {
          throw new Error(response.data.message || 'Erreur lors de la création de la commande')
        }

      } catch (err) {
        console.error('Erreur lors du processus de commande:', err)
        if (err.response?.data?.message) {
          error.value = err.response.data.message
        } else if (err.response?.data?.errors) {
          // Afficher les erreurs de validation
          const errors = Object.values(err.response.data.errors).flat()
          error.value = errors.join(', ')
        } else {
          error.value = err.message || 'Erreur lors du traitement de la commande'
        }
      } finally {
        loading.value = false
      }
    }

    const goBack = () => {
      router.back()
    }

    // Lifecycle
    onMounted(() => {
      loadEvent()
      startCountdown()
    })

    onUnmounted(() => {
      if (countdownTimer.value) {
        clearInterval(countdownTimer.value)
      }
    })

    return {
      event,
      eventLoading,
      eventError,
      loading,
      error,
      phoneError,
      countdown,
      orderForm,
      totalAmount,
      isFormValid,
      formatEventDate,
      eventTime,
      eventDate,
      isEventPassed,
      timeUntilEvent,
      availableTicketTypes,
      canPurchaseTickets,
      formatPrice,
      getAvailableQuantityText,
      loadEvent,
      validatePhoneNumber,
      selectPaymentMethod,
      redirectToVisaPayment,
      processOrder,
      goBack
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

.checkout-page {
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

.border-primea-blue {
  border-color: var(--primea-blue);
}

.focus\:border-primea-blue:focus {
  border-color: var(--primea-blue);
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

/* Animation du loader */
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