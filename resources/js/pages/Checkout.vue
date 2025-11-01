<template>
  <div class="checkout-page min-h-screen font-primea">
    
    <!-- Desktop/Tablet Layout -->
    <div class="hidden md:block bg-gray-50 min-h-screen">

      <div class="container mx-auto px-4 py-12">
        <div class="max-w-4xl mx-auto">

          <!-- Header Desktop -->
          <div class="mb-12">
            <div class="text-center">
              <h1 class="text-4xl font-bold text-primea-blue mb-4">Finaliser votre achat</h1>
              <p class="text-lg text-gray-600">Sélectionnez vos tickets et procédez au paiement</p>
            </div>
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
                    v-if="eventImageUrl"
                    :src="eventImageUrl"
                    :alt="event.title"
                    class="w-full h-full object-cover"
                    @error="handleImageError"
                  />
                  <div v-else class="w-full h-full bg-primea-gradient flex items-center justify-center">
                    <PhotoIcon class="w-24 h-24 text-white/50" />
                  </div>
                  <div class="absolute inset-0 bg-primea-blue/60"></div>
                  <div class="absolute inset-0 p-6 text-white flex flex-col justify-end">
                    <div class="bg-primea-yellow text-primea-blue px-3 py-1 rounded-primea text-sm font-bold w-fit mb-2">
                      {{ getCategoryName() }}
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
                    <p class="text-red-500 text-sm">Les tickets ne sont plus disponibles</p>
                  </div>
                </div>

                <div v-else class="flex justify-center space-x-2">
                  <div v-if="countdown.days > 0" class="text-center bg-gray-100 border-2 border-primea-blue rounded-primea-lg p-4 min-w-[70px]">
                    <div class="text-2xl font-bold text-primea-blue">{{ countdown.days }}</div>
                    <div class="text-xs text-gray-600">JOURS</div>
                  </div>
                  <div class="text-center bg-gray-100 border-2 border-primea-blue rounded-primea-lg p-4 min-w-[70px]">
                    <div class="text-2xl font-bold text-primea-blue">{{ countdown.hours }}</div>
                    <div class="text-xs text-gray-600">HEURES</div>
                  </div>
                  <div class="text-center bg-gray-100 border-2 border-primea-blue rounded-primea-lg p-4 min-w-[70px]">
                    <div class="text-2xl font-bold text-primea-blue">{{ countdown.minutes }}</div>
                    <div class="text-xs text-gray-600">MIN</div>
                  </div>
                  <div class="text-center bg-gray-100 border-2 border-primea-blue rounded-primea-lg p-4 min-w-[70px]">
                    <div class="text-2xl font-bold text-primea-blue">{{ countdown.seconds }}</div>
                    <div class="text-xs text-gray-600">SEC</div>
                  </div>
                </div>
              </div>

            </div>

            <!-- Colonne droite - Formulaire d'achat -->
            <div class="bg-white rounded-primea-xl shadow-primea p-8">
              <h3 class="text-2xl font-bold text-primea-blue mb-6">
                {{ isEventPassed ? 'Achat impossible' : 'Votre achat' }}
              </h3>

              <!-- Message événement passé -->
              <div v-if="isEventPassed" class="bg-red-50 border border-red-200 rounded-primea-lg p-6 text-center">
                <ExclamationCircleIcon class="w-16 h-16 text-red-500 mx-auto mb-4" />
                <h4 class="text-lg font-semibold text-red-800 mb-2">Événement terminé</h4>
                <p class="text-red-600 mb-4">Cet événement s'est déjà déroulé. Il n'est plus possible d'acheter des tickets.</p>
                <router-link 
                  to="/events" 
                  class="bg-primea-blue text-white px-6 py-2 rounded-primea hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200"
                >
                  Voir d'autres événements
                </router-link>
              </div>
              
              <form v-if="!isEventPassed" @submit.prevent="processOrder" class="space-y-6">
                
                <!-- Sélection tickets Desktop -->
                <div>
                  <label class="block text-sm font-semibold text-primea-blue mb-3">Nombre de tickets</label>
                  <input 
                    type="number"
                    v-model="orderForm.quantity"
                    min="1"
                    max="10"
                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-primea-lg focus:border-primea-blue focus:outline-none transition-colors"
                    required
                  />
                </div>

                <!-- Type de ticket Desktop -->
                <div>
                  <label class="block text-sm font-semibold text-primea-blue mb-3">Type de ticket</label>
                  <select 
                    v-model="orderForm.ticketTypeId"
                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-primea-lg focus:border-primea-blue focus:outline-none transition-colors appearance-none bg-white"
                    required
                    :disabled="eventLoading || availableTicketTypes.length === 0"
                  >
                    <option value="">Sélectionnez un type de ticket</option>
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
                    Aucun ticket disponible pour cet événement
                  </div>
                </div>

                <!-- Informations de l'acheteur (uniquement si non connecté) -->
                <div v-if="!isAuthenticated" class="bg-primea-blue/5 rounded-primea-xl p-6 space-y-4">
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
                    <label class="block text-sm font-semibold text-primea-blue mb-2">Téléphone (optionnel)</label>
                    <PhoneInput
                      v-model="orderForm.guestPhone"
                      id="guest-phone"
                      placeholder="01 23 45 67"
                      class="w-full"
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
                        type="submit"
                        :disabled="loading || !isFormValid"
                        class="bg-blue-600 text-white px-6 py-3 rounded-primea-lg font-semibold hover:bg-blue-700 transition-all duration-200 flex items-center justify-center gap-2 mx-auto disabled:opacity-50 disabled:cursor-not-allowed"
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

                <!-- USSD Push Section - Uniquement pour Mobile Money -->
                <div v-if="ussdPushActive" class="bg-blue-50 border-2 border-blue-200 rounded-primea-xl p-6">
                  <div class="text-center">
                    <!-- En attente de validation -->
                    <div v-if="paymentStatus === 'pending'" class="space-y-4">
                      <div class="flex items-center justify-center mb-4">
                        <div class="w-16 h-16 border-4 border-blue-300 border-t-blue-600 rounded-full animate-spin"></div>
                      </div>
                      
                      <h4 class="text-xl font-bold text-blue-800 mb-2">Push envoyé !</h4>
                      <p class="text-blue-700 mb-4">
                        Un push USSD a été envoyé au numéro <span class="font-semibold">{{ formatPhoneForDisplay(currentPayment?.phone) }}</span>.
                        Veuillez valider le paiement sur votre téléphone.
                      </p>
                      
                      <!-- Décompte -->
                      <div class="bg-white border border-blue-200 rounded-primea-lg p-4 mb-4">
                        <div class="text-3xl font-bold text-blue-600 mb-2">{{ formatCountdown(ussdCountdown) }}</div>
                        <div class="text-sm text-blue-500">Temps restant pour valider</div>
                        
                        <!-- Barre de progression -->
                        <div class="w-full bg-blue-200 rounded-full h-2 mt-3">
                          <div 
                            class="bg-blue-600 h-2 rounded-full transition-all duration-1000"
                            :style="{ width: (ussdCountdown / 90 * 100) + '%' }"
                          ></div>
                        </div>
                      </div>
                      
                      <p class="text-blue-600 text-sm mb-4">
                        Montant: <span class="font-semibold">{{ formatPrice(totalAmount) }} FCFA</span>
                      </p>
                      
                      <!-- Boutons d'action -->
                      <div class="flex flex-col sm:flex-row gap-3 justify-center">
                        <button
                          @click="retryUSSDPush"
                          :disabled="loading || ussdCountdown > 0"
                          class="bg-blue-600 text-white px-4 py-2 rounded-primea hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                          <span v-if="loading">Envoi...</span>
                          <span v-else>Renvoyer le push</span>
                        </button>

                        <button
                          v-if="currentPayment.payment_url && currentPayment.bill_id"
                          @click="payViaWebPage"
                          class="bg-green-600 text-white px-4 py-2 rounded-primea hover:bg-green-700 transition-colors"
                        >
                          Payer via page web
                        </button>

                        <button
                          @click="changeOperator"
                          :disabled="ussdCountdown > 0"
                          class="bg-gray-500 text-white px-4 py-2 rounded-primea hover:bg-gray-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                          Changer d'opérateur
                        </button>

                        <button
                          @click="cancelUSSDPush"
                          class="bg-red-500 text-white px-4 py-2 rounded-primea hover:bg-red-600 transition-colors"
                        >
                          Annuler
                        </button>
                      </div>
                    </div>
                    
                    <!-- Paiement réussi -->
                    <div v-else-if="paymentStatus === 'successful'" class="space-y-4">
                      <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                      </div>
                      
                      <h4 class="text-xl font-bold text-green-800 mb-2">Paiement réussi !</h4>
                      <p class="text-green-700 mb-4">Votre paiement a été validé avec succès.</p>
                      <p class="text-green-600 text-sm">Redirection en cours vers votre ticket...</p>
                    </div>
                    
                    <!-- Paiement échoué -->
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
                          @click="retryUSSDPush"
                          class="bg-blue-600 text-white px-4 py-2 rounded-primea hover:bg-blue-700 transition-colors"
                        >
                          Réessayer
                        </button>
                        
                        <button 
                          @click="changeOperator"
                          class="bg-gray-500 text-white px-4 py-2 rounded-primea hover:bg-gray-600 transition-colors"
                        >
                          Changer d'opérateur
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Bouton de paiement Desktop -->
                <button 
                  v-if="!ussdPushActive"
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
                    class="text-primea-blue hover:text-primea-yellow transition-colors underline"
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
    <div class="md:hidden bg-white min-h-screen">
      <div class="max-w-md mx-auto">

        <!-- Loading/Error States -->
        <div v-if="eventLoading" class="flex justify-center py-12 px-4">
          <div class="animate-spin rounded-full h-12 w-12 border-4 border-blue-900 border-t-yellow-500"></div>
        </div>

        <div v-else-if="eventError" class="px-4">
          <div class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
            <ExclamationCircleIcon class="w-12 h-12 text-red-500 mx-auto mb-3" />
            <p class="text-red-600 mb-4">{{ eventError }}</p>
            <button @click="loadEvent" class="bg-blue-900 text-white px-6 py-2 rounded-lg font-semibold">
              Réessayer
            </button>
          </div>
        </div>

        <!-- Event Content -->
        <div v-else-if="event" class="px-4 pb-24">
          <!-- Event Card -->
          <div class="bg-white rounded-xl shadow-md overflow-hidden mb-6">
            <div class="relative h-48">
              <img v-if="eventImageUrl" :src="eventImageUrl" :alt="event.title" class="w-full h-full object-cover" @error="handleImageError" />
              <div v-else class="w-full h-full bg-gradient-to-br from-blue-900 to-blue-800 flex items-center justify-center">
                <PhotoIcon class="w-16 h-16 text-white/30" />
              </div>
              <div class="absolute inset-0 bg-blue-900/50"></div>

              <!-- Compte à rebours en haut à droite -->
              <div v-if="!isEventPassed" class="absolute top-3 right-3 bg-blue-900/70 backdrop-blur-sm px-3 py-2 rounded-lg text-white shadow-lg">
                <div class="text-center">
                  <div class="text-xs font-semibold mb-0.5">Commence dans</div>
                  <div class="flex items-center gap-1 text-sm font-bold">
                    <span v-if="countdown.days > 0">{{ countdown.days }}j</span>
                    <span>{{ countdown.hours }}h</span>
                    <span>{{ countdown.minutes }}m</span>
                  </div>
                </div>
              </div>

              <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
                <h2 class="text-lg font-bold mb-1">{{ event.title }}</h2>
                <div class="flex items-center gap-2 text-sm text-white/90 mb-2">
                  <ClockIcon class="w-4 h-4" />
                  <span>{{ formatEventDate }}</span>
                </div>
                <div class="flex items-center justify-between gap-2">
                  <div class="flex items-center gap-2 text-sm text-white/90">
                    <MapPinIcon class="w-4 h-4" />
                    <span>{{ event.venue_name || 'Lieu à confirmer' }}</span>
                  </div>
                  <router-link
                    :to="`/events/${event.slug}`"
                    class="bg-yellow-500 text-blue-900 px-3 py-1.5 rounded-lg text-xs font-semibold hover:bg-yellow-400 transition-colors shadow-md"
                  >
                    À propos
                  </router-link>
                </div>
              </div>
            </div>
          </div>

          <!-- Event Passed Message -->
          <div v-if="isEventPassed" class="bg-red-50 border border-red-200 rounded-xl p-6 text-center mb-6">
            <ExclamationCircleIcon class="w-12 h-12 text-red-500 mx-auto mb-3" />
            <h3 class="text-lg font-semibold text-red-800 mb-2">Événement terminé</h3>
            <p class="text-red-600 text-sm mb-4">Cet événement s'est déjà déroulé.</p>
            <router-link to="/events" class="bg-blue-900 text-white px-6 py-2 rounded-lg inline-block">
              Voir d'autres événements
            </router-link>
          </div>

          <!-- Order Form -->
          <form v-if="!isEventPassed" @submit.prevent="processOrder" class="space-y-5 w-[76%] mx-auto">

            <!-- Ticket Type Selection -->
            <div>
              <label class="block text-sm font-semibold text-blue-900 mb-2">Type de ticket</label>
              <select v-model="orderForm.ticketTypeId" required
                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-900 focus:outline-none bg-white text-base"
                :disabled="availableTicketTypes.length === 0">
                <option value="">Sélectionnez un type</option>
                <option v-for="ticketType in availableTicketTypes" :key="ticketType.id" :value="ticketType.id">
                  {{ ticketType.name }} - {{ formatPrice(ticketType.price) }} XAF
                </option>
              </select>
            </div>

            <!-- Quantity -->
            <div>
              <label class="block text-sm font-semibold text-blue-900 mb-2">Nombre de tickets</label>
              <input type="number" v-model="orderForm.quantity" min="1" max="10" required
                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-900 focus:outline-none text-base" />
            </div>

            <!-- Guest Info (if not authenticated) -->
            <div v-if="!isAuthenticated" class="bg-blue-50 rounded-xl p-4 space-y-4">
              <h4 class="text-sm font-semibold text-blue-900">Vos informations</h4>
              <div>
                <label class="block text-sm font-semibold text-blue-900 mb-2">Nom complet *</label>
                <input type="text" v-model="orderForm.guestName" placeholder="Votre nom" required
                  class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-900 focus:outline-none text-base" />
              </div>
              <div>
                <label class="block text-sm font-semibold text-blue-900 mb-2">Téléphone</label>
                <PhoneInput v-model="orderForm.guestPhone" placeholder="01 23 45 67" class="w-full" />
              </div>
            </div>

            <!-- Total -->
            <div v-if="orderForm.quantity && orderForm.ticketTypeId" class="bg-blue-50 rounded-xl p-4">
              <div class="flex justify-between items-center">
                <span class="font-semibold text-blue-900">Total:</span>
                <span class="text-2xl font-bold text-blue-900">{{ formatPrice(totalAmount) }} XAF</span>
              </div>
            </div>

            <!-- Payment Methods -->
            <div>
              <label class="block text-sm font-semibold text-blue-900 mb-3">Moyen de paiement</label>

              <div class="grid grid-cols-3 gap-3 mb-4">
                <button type="button" @click="selectPaymentMethod('airtel')"
                  :class="['p-3 rounded-xl border-2 transition-all flex flex-col items-center justify-center',
                    orderForm.paymentMethod === 'airtel' ? 'border-red-500 bg-red-50' : 'border-gray-200']">
                  <img src="/images/am.png" alt="Airtel" class="h-8 w-auto" onerror="this.style.display='none'" />
                  <span class="text-xs mt-1 font-semibold text-red-600">Airtel</span>
                </button>

                <button type="button" @click="selectPaymentMethod('moov')"
                  :class="['p-3 rounded-xl border-2 transition-all flex flex-col items-center justify-center',
                    orderForm.paymentMethod === 'moov' ? 'border-orange-500 bg-orange-50' : 'border-gray-200']">
                  <img src="/images/mm.png" alt="Moov" class="h-8 w-auto" onerror="this.style.display='none'" />
                  <span class="text-xs mt-1 font-semibold text-orange-600">Moov</span>
                </button>

                <button type="button" @click="selectPaymentMethod('visa')"
                  :class="['p-3 rounded-xl border-2 transition-all flex flex-col items-center justify-center',
                    orderForm.paymentMethod === 'visa' ? 'border-blue-500 bg-blue-50' : 'border-gray-200']">
                  <img src="/images/vm.png" alt="Visa" class="h-8 w-auto" onerror="this.style.display='none'" />
                  <span class="text-xs mt-1 font-semibold text-blue-600">Visa</span>
                </button>
              </div>

              <!-- Phone Number Input for Mobile Money -->
              <div v-if="orderForm.paymentMethod === 'airtel' || orderForm.paymentMethod === 'moov'">
                <label class="block text-sm font-semibold text-blue-900 mb-2">
                  Numéro {{ orderForm.paymentMethod === 'airtel' ? 'Airtel Money' : 'Moov Money' }}
                </label>
                <input type="tel" v-model="orderForm.phoneNumber" required maxlength="9" pattern="[0-9]{9}"
                  :placeholder="orderForm.paymentMethod === 'airtel' ? '074123456' : '062123456'"
                  @input="validatePhoneNumber"
                  class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-900 focus:outline-none text-base" />
                <p v-if="phoneError" class="mt-1 text-sm text-red-600">{{ phoneError }}</p>
              </div>

              <!-- Visa Info -->
              <div v-if="orderForm.paymentMethod === 'visa'" class="bg-blue-50 rounded-xl p-4 text-center">
                <p class="text-blue-800 font-semibold mb-2">Paiement Visa sécurisé</p>
                <p class="text-blue-600 text-xs">Vous serez redirigé vers notre partenaire de paiement</p>
              </div>
            </div>

            <!-- Error Message -->
            <div v-if="error" class="bg-red-50 border-2 border-red-200 rounded-xl p-4">
              <p class="text-red-600 text-sm">{{ error }}</p>
            </div>

            <!-- USSD Push Section -->
            <div v-if="ussdPushActive" class="bg-blue-50 border-2 border-blue-200 rounded-xl p-6">
              <div class="text-center space-y-4">
                <div v-if="paymentStatus === 'pending'">
                  <div class="w-12 h-12 border-4 border-blue-300 border-t-blue-600 rounded-full animate-spin mx-auto mb-4"></div>
                  <h4 class="text-lg font-bold text-blue-800 mb-2">Push envoyé !</h4>
                  <p class="text-blue-700 text-sm mb-4">
                    Validez le paiement sur votre téléphone au {{ formatPhoneForDisplay(currentPayment?.phone) }}
                  </p>
                  <div class="bg-white rounded-xl p-4 mb-4">
                    <div class="text-2xl font-bold text-blue-600">{{ formatCountdown(ussdCountdown) }}</div>
                    <div class="text-xs text-blue-500 mt-1">Temps restant</div>
                    <div class="w-full bg-blue-200 rounded-full h-2 mt-3">
                      <div class="bg-blue-600 h-2 rounded-full transition-all" :style="{ width: (ussdCountdown / 90 * 100) + '%' }"></div>
                    </div>
                  </div>
                  <div class="space-y-2">
                    <button @click="retryUSSDPush" :disabled="loading || ussdCountdown > 0"
                      class="w-full bg-blue-600 text-white px-4 py-3 rounded-lg font-semibold disabled:opacity-50">
                      Renvoyer le push
                    </button>
                    <button v-if="currentPayment.payment_url" @click="payViaWebPage"
                      class="w-full bg-green-600 text-white px-4 py-3 rounded-lg font-semibold">
                      Payer via page web
                    </button>
                    <button @click="changeOperator" :disabled="ussdCountdown > 0"
                      class="w-full bg-gray-500 text-white px-4 py-3 rounded-lg font-semibold disabled:opacity-50">
                      Changer d'opérateur
                    </button>
                    <button @click="cancelUSSDPush" class="w-full bg-red-500 text-white px-4 py-3 rounded-lg font-semibold">
                      Annuler
                    </button>
                  </div>
                </div>

                <div v-else-if="paymentStatus === 'successful'">
                  <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                  </div>
                  <h4 class="text-lg font-bold text-green-800 mb-2">Paiement réussi !</h4>
                  <p class="text-green-700 text-sm">Redirection en cours...</p>
                </div>

                <div v-else-if="paymentStatus === 'failed' || paymentStatus === 'cancelled' || paymentStatus === 'expired'">
                  <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                  </div>
                  <h4 class="text-lg font-bold text-red-800 mb-2">Paiement échoué</h4>
                  <p class="text-red-700 text-sm mb-4">{{ paymentStatus === 'cancelled' ? 'Paiement annulé' : paymentStatus === 'expired' ? 'Délai expiré' : 'Veuillez réessayer' }}</p>
                  <div class="space-y-2">
                    <button @click="retryUSSDPush" class="w-full bg-blue-600 text-white px-4 py-3 rounded-lg font-semibold">
                      Réessayer
                    </button>
                    <button @click="changeOperator" class="w-full bg-gray-500 text-white px-4 py-3 rounded-lg font-semibold">
                      Changer d'opérateur
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Submit Button -->
            <button v-if="!ussdPushActive" type="submit" :disabled="loading || !isFormValid"
              class="w-full bg-blue-900 text-white py-4 px-6 rounded-xl text-base font-bold hover:bg-yellow-500 hover:text-blue-900 transition-all shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
              <span v-if="loading" class="flex items-center justify-center">
                <svg class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/>
                  <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" class="opacity-75"/>
                </svg>
                Traitement...
              </span>
              <span v-else>Finaliser le paiement</span>
            </button>

            <!-- Retrieve Ticket Link -->
            <div class="text-center pt-4">
              <router-link to="/retrieve-ticket" class="text-blue-900 hover:text-yellow-500 text-sm font-semibold">
                Récupérer mon ticket perdu
              </router-link>
            </div>

          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useEventsStore } from '../stores/events'
import { useAuthStore } from '../stores/auth'
import { guestService, orderService } from '../services/api'
import PhoneInput from '../components/PhoneInput.vue'
import {
  ExclamationCircleIcon,
  PhotoIcon,
  MapPinIcon,
  ClockIcon,
  ArrowTopRightOnSquareIcon
} from '@heroicons/vue/24/outline'
export default {
  name: 'Checkout',
  components: {
    PhoneInput,
    ExclamationCircleIcon,
    PhotoIcon,
    MapPinIcon,
    ClockIcon,
    ArrowTopRightOnSquareIcon
  },
  setup() {
    const route = useRoute()
    const router = useRouter()
    const eventsStore = useEventsStore()
    const authStore = useAuthStore()

    // Authentification
    const isAuthenticated = computed(() => authStore.isAuthenticated)
    const currentUser = computed(() => authStore.user)

    // État de l'événement et de l'achat
    const event = ref(null)
    const eventLoading = ref(true)
    const eventError = ref('')

    const loading = ref(false)
    const error = ref('')
    const phoneError = ref('')
    const countdownTimer = ref(null)
    const currentTime = ref(new Date()) // Temps actuel réactif

    // État du compte à rebours pour l'événement
    const countdown = ref({
      days: 0,
      hours: 0,
      minutes: 0,
      seconds: 0
    })

    // État USSD Push
    const ussdPushActive = ref(false)
    const ussdCountdown = ref(90) // 90 secondes
    const ussdTimer = ref(null)
    const paymentPollingTimer = ref(null)
    const paymentStatus = ref('')
    const currentPayment = ref(null)
    const createdOrder = ref(null) // Stocker la commande créée pour éviter les duplicatas

    // Formulaire d'achat
    const orderForm = ref({
      quantity: 1,
      ticketTypeId: '',
      paymentMethod: '',
      phoneNumber: '',
      cardNumber: '',
      expiryDate: '',
      cvv: '',
      guestName: '',
      guestPhone: ''
    })

    // État pour les erreurs d'image
    const imageError = ref(false)

    // Computed properties
    const eventImageUrl = computed(() => {
      if (imageError.value || !event.value) return null

      // Priorité: image (accessor) > image_url > image_file
      let imageUrl = event.value.image || event.value.image_url || event.value.image_file

      console.log('Checkout - Image URL:', imageUrl)

      if (!imageUrl || imageUrl.trim() === '') {
        return null
      }

      // Si c'est déjà une URL complète
      if (imageUrl.startsWith('http://') || imageUrl.startsWith('https://')) {
        return imageUrl
      }

      // Si c'est un chemin relatif commençant par /
      if (imageUrl.startsWith('/')) {
        return window.location.origin + imageUrl
      }

      // Si c'est un nom de fichier dans le storage
      if (!imageUrl.includes('/')) {
        return `${window.location.origin}/storage/images/events/${imageUrl}`
      }

      return imageUrl
    })

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

      const timeDiff = eventDate.value.getTime() - currentTime.value.getTime()

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

      // Vérifier les informations de l'invité (seulement si non connecté)
      if (!isAuthenticated.value && !orderForm.value.guestName) {
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

    const getCategoryName = () => {
      if (!event.value) return 'ÉVÉNEMENT'
      
      // Si category est un objet avec name
      if (event.value.category && typeof event.value.category === 'object' && event.value.category.name) {
        return event.value.category.name.toUpperCase()
      }
      // Si category est une chaîne
      if (event.value.category && typeof event.value.category === 'string') {
        return event.value.category.toUpperCase()
      }
      // Si category_name existe
      if (event.value.category_name && typeof event.value.category_name === 'string') {
        return event.value.category_name.toUpperCase()
      }
      
      return 'ÉVÉNEMENT'
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

        // Si l'événement n'a qu'un seul type de ticket, le sélectionner automatiquement
        if (event.value?.ticket_types && event.value.ticket_types.length === 1) {
          orderForm.value.ticketTypeId = event.value.ticket_types[0].id
        }

        // Démarrer le compte à rebours après avoir chargé l'événement
        startCountdown()

      } catch (err) {
        eventError.value = err.message || 'Erreur lors du chargement de l\'événement'
      } finally {
        eventLoading.value = false
      }
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
      // Nettoyer le timer précédent s'il existe
      if (countdownTimer.value) {
        clearInterval(countdownTimer.value)
      }

      // Mise à jour initiale
      currentTime.value = new Date()
      updateCountdown()

      // Mise à jour chaque seconde
      countdownTimer.value = setInterval(() => {
        currentTime.value = new Date() // Mettre à jour le temps actuel
        updateCountdown()

        // Arrêter le timer si l'événement est passé
        if (timeUntilEvent.value?.expired) {
          clearInterval(countdownTimer.value)
        }
      }, 1000)

      console.log('Checkout - Compte à rebours démarré')
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


    const processOrder = async () => {
      try {
        loading.value = true
        error.value = ''

        // Vérifier si l'événement est passé
        if (isEventPassed.value) {
          throw new Error('Impossible d\'acheter des tickets pour un événement terminé')
        }

        // Validation
        if (!isFormValid.value) {
          throw new Error('Veuillez remplir tous les champs requis')
        }

        // Pour Mobile Money (Airtel/Moov), utiliser E-Billing avec USSD push
        if (orderForm.value.paymentMethod === 'airtel' || orderForm.value.paymentMethod === 'moov') {
          await processEBillingPayment()
        }
        // Pour Visa/Mastercard, rediriger vers ORABANK_NG
        else if (orderForm.value.paymentMethod === 'visa') {
          await processOrabankPayment()
        }
        else {
          throw new Error('Méthode de paiement non supportée')
        }

      } catch (err) {
        console.error('Erreur lors du processus d\'achat:', err)
        if (err.response?.data?.message) {
          error.value = err.response.data.message
        } else if (err.response?.data?.errors) {
          // Mapper les erreurs de validation en messages français
          const validationErrors = err.response.data.errors
          const errorMessages = []

          for (const [field, messages] of Object.entries(validationErrors)) {
            const fieldName = {
              'guest_name': 'Nom complet',
              'guest_email': 'Email',
              'guest_phone': 'Téléphone',
              'ticket_type_id': 'Type de ticket',
              'quantity': 'Quantité',
              'phone': 'Numéro de téléphone',
              'amount': 'Montant'
            }[field] || field

            messages.forEach(msg => {
              if (msg === 'validation.required') {
                errorMessages.push(`Le champ ${fieldName} est requis`)
              } else {
                errorMessages.push(msg)
              }
            })
          }

          error.value = errorMessages.join('. ')
        } else {
          error.value = err.message || 'Erreur lors du traitement de l\'achat'
        }
      } finally {
        loading.value = false
      }
    }

    const processEBillingPayment = async () => {
      try {
        let order

        // 1. Créer l'achat seulement si pas déjà créé
        if (!createdOrder.value) {
          const orderData = {
            event_slug: event.value.slug,
            ticket_type_id: orderForm.value.ticketTypeId,
            quantity: orderForm.value.quantity,
            guest_name: isAuthenticated.value ? currentUser.value.name : orderForm.value.guestName,
            guest_phone: isAuthenticated.value ? currentUser.value.phone : (orderForm.value.guestPhone || null),
            guest_email: isAuthenticated.value ? currentUser.value.email : 'guest@primea.ga'
          }

          // Utiliser le service approprié selon l'authentification
          const orderResponse = isAuthenticated.value
            ? await orderService.createOrder(orderData)
            : await guestService.createGuestOrder(orderData)

          if (!orderResponse.data.success) {
            throw new Error(orderResponse.data.message || 'Erreur lors de la création de l\'achat')
          }

          order = orderResponse.data.data.order
          createdOrder.value = order // Stocker la commande créée
          console.log('✅ Nouvelle commande créée:', order.reference)
        } else {
          // Réutiliser la commande existante
          order = createdOrder.value
          console.log('♻️ Réutilisation de la commande existante:', order.reference)
        }

        // 2. Initier le paiement E-Billing
        const paymentData = {
          order_id: order.id,
          gateway: orderForm.value.paymentMethod === 'airtel' ? 'airtelmoney' : 'moovmoney',
          phone: orderForm.value.phoneNumber,
          amount: totalAmount.value
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

        // 3. Si E-Billing retourne une facture, envoyer le push USSD
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
              phone: orderForm.value.phoneNumber,
              gateway: orderForm.value.paymentMethod === 'airtel' ? 'airtelmoney' : 'moovmoney'
            })
          })

          const pushResult = await pushResponse.json()

          if (pushResult.success) {
            // Démarrer le processus USSD push
            startUSSDPush({
              payment_id: paymentResult.data.payment.id,
              reference: order.reference,
              phone: orderForm.value.phoneNumber,
              gateway: orderForm.value.paymentMethod === 'airtel' ? 'airtelmoney' : 'moovmoney',
              amount: totalAmount.value,
              bill_id: paymentResult.data.bill_id,
              payment_url: paymentResult.data.payment_url
            })
          } else {
            // Si le push échoue, afficher l'erreur sans redirection
            console.error('❌ Push USSD échoué:', {
              message: pushResult.message,
              error_code: pushResult.error_code,
              details: pushResult.details,
              payment_id: paymentResult.data.payment.id,
              bill_id: paymentResult.data.bill_id,
              phone: orderForm.value.phoneNumber,
              gateway: orderForm.value.paymentMethod
            })

            // Lever une erreur avec un message détaillé
            const errorMessage = pushResult.details
              ? `${pushResult.message}\nDétails: ${pushResult.details}`
              : pushResult.message || 'Erreur lors de l\'envoi du push USSD'

            throw new Error(errorMessage)
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
        let order

        // 1. Créer l'achat seulement si pas déjà créé
        if (!createdOrder.value) {
          const orderData = {
            event_slug: event.value.slug,
            ticket_type_id: orderForm.value.ticketTypeId,
            quantity: orderForm.value.quantity,
            guest_name: isAuthenticated.value ? currentUser.value.name : orderForm.value.guestName,
            guest_phone: isAuthenticated.value ? currentUser.value.phone : (orderForm.value.guestPhone || null),
            guest_email: isAuthenticated.value ? currentUser.value.email : 'guest@primea.ga'
          }

          // Utiliser le service approprié selon l'authentification
          const orderResponse = isAuthenticated.value
            ? await orderService.createOrder(orderData)
            : await guestService.createGuestOrder(orderData)

          if (!orderResponse.data.success) {
            throw new Error(orderResponse.data.message || 'Erreur lors de la création de l\'achat')
          }

          order = orderResponse.data.data.order
          createdOrder.value = order // Stocker la commande créée
          console.log('✅ Nouvelle commande créée:', order.reference)
        } else {
          // Réutiliser la commande existante
          order = createdOrder.value
          console.log('♻️ Réutilisation de la commande existante:', order.reference)
        }

        // 2. Initier le paiement ORABANK_NG pour Visa/Mastercard
        const paymentData = {
          order_id: order.id,
          gateway: 'ORABANK_NG',
          amount: totalAmount.value
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
          // Rediriger vers la page de paiement ORABANK
          window.location.href = paymentResult.data.redirect_url
        } else {
          throw new Error(paymentResult.message || 'Erreur lors de l\'initiation du paiement Visa/Mastercard')
        }

      } catch (err) {
        console.error('Erreur ORABANK:', err)
        throw err
      }
    }

    // Méthodes USSD Push
    const startUSSDPush = (paymentData) => {
      ussdPushActive.value = true
      ussdCountdown.value = 90
      paymentStatus.value = 'pending'
      currentPayment.value = paymentData
      
      // Démarrer le décompte USSD
      startUSSDCountdown()
      
      // Démarrer le polling du statut de paiement
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
          // Arrêter le polling et changer le statut en expired
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

              // Rediriger vers la page de ticket après 2 secondes
              setTimeout(() => {
                router.push(`/ticket-success?reference=${currentPayment.value.reference}`)
              }, 2000)
            } else if (status === 'failed' || status === 'cancelled' || status === 'expired') {
              paymentStatus.value = status
              clearInterval(paymentPollingTimer.value)
              clearInterval(ussdTimer.value)

              // Réinitialiser après 3 secondes
              setTimeout(() => {
                cancelUSSDPush()
              }, 3000)
            }
          }
        } catch (err) {
          console.error('Erreur lors de la vérification du statut:', err)
        }
      }, 3000) // Vérifier toutes les 3 secondes
    }

    const cancelUSSDPush = () => {
      ussdPushActive.value = false
      ussdCountdown.value = 90
      paymentStatus.value = ''
      currentPayment.value = null
      createdOrder.value = null // Réinitialiser la commande lors de l'annulation complète

      // Nettoyer les timers
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
        loading.value = true

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
          // Relancer le décompte
          ussdCountdown.value = 90
          paymentStatus.value = 'pending'
          startUSSDCountdown()
          startPaymentPolling(currentPayment.value.payment_id)
        } else {
          const errorData = await response.json()
          error.value = errorData.message || 'Erreur lors du renvoi du push'
        }
      } catch (err) {
        error.value = 'Erreur de connexion'
      } finally {
        loading.value = false
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
      // Arrêter le push USSD sans réinitialiser la commande créée
      ussdPushActive.value = false
      ussdCountdown.value = 90
      paymentStatus.value = ''
      currentPayment.value = null

      // Nettoyer les timers
      if (ussdTimer.value) {
        clearInterval(ussdTimer.value)
      }
      if (paymentPollingTimer.value) {
        clearInterval(paymentPollingTimer.value)
      }

      // Réinitialiser le formulaire pour permettre de choisir un autre opérateur
      // MAIS garder createdOrder.value pour réutiliser la commande existante
      orderForm.value.paymentMethod = ''
      orderForm.value.phoneNumber = ''

      console.log('🔄 Changement d\'opérateur - Commande conservée:', createdOrder.value?.reference)
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

    // Lifecycle
    onMounted(() => {
      loadEvent()

      // Pré-remplir les informations si l'utilisateur est connecté
      if (isAuthenticated.value && currentUser.value) {
        orderForm.value.guestName = currentUser.value.name || ''
        orderForm.value.guestPhone = currentUser.value.phone || ''
      }

      // startCountdown() est appelé dans loadEvent() après le chargement de l'événement
    })

    onUnmounted(() => {
      if (countdownTimer.value) {
        clearInterval(countdownTimer.value)
      }
      if (ussdTimer.value) {
        clearInterval(ussdTimer.value)
      }
      if (paymentPollingTimer.value) {
        clearInterval(paymentPollingTimer.value)
      }
    })

    const handleImageError = () => {
      console.warn('Checkout - Erreur de chargement de l\'image')
      imageError.value = true
    }

    return {
      // Authentification
      isAuthenticated,
      currentUser,
      // État
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
      getCategoryName,
      loadEvent,
      validatePhoneNumber,
      selectPaymentMethod,
      processOrder,
      // Variables USSD Push
      ussdPushActive,
      ussdCountdown,
      paymentStatus,
      currentPayment,
      // Méthodes USSD Push
      startUSSDPush,
      startUSSDCountdown,
      startPaymentPolling,
      cancelUSSDPush,
      retryUSSDPush,
      payViaWebPage,
      changeOperator,
      formatCountdown,
      formatPhoneForDisplay,
      // Méthodes de paiement
      processEBillingPayment,
      processOrabankPayment,
      // Image
      eventImageUrl,
      handleImageError
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
  --font-primary: 'Inter', 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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