<template>
  <div class="event-detail-page font-primea bg-gray-50 min-h-screen">
    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="flex items-center justify-center min-h-screen">
        <div class="animate-spin rounded-full h-32 w-32 border-b-2 border-primea-blue"></div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-container">
      <div class="flex items-center justify-center min-h-screen bg-gray-50">
        <div class="text-center bg-white p-12 rounded-primea-xl shadow-primea-lg">
          <div class="text-red-600 text-xl mb-6 font-semibold">{{ error }}</div>
          <button @click="loadEvent" class="bg-primea-blue text-white px-8 py-3 rounded-primea-lg font-bold hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200">
            Réessayer
          </button>
        </div>
      </div>
    </div>

    <!-- Event Content -->
    <div v-else-if="event" class="event-content">

      <!-- Hero Section simplifiée -->
      <section class="bg-white py-12">
        <div class="container mx-auto px-4">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            
            <!-- Image de l'événement -->
            <div class="order-2 lg:order-1">
              <div class="rounded-primea-xl overflow-hidden shadow-primea-lg">
                <img 
                  v-if="event.image_url" 
                  :src="event.image_url" 
                  :alt="event.title" 
                  class="w-full h-96 object-cover"
                />
                <div v-else class="w-full h-96 bg-primea-gradient flex items-center justify-center">
                  <PhotoIcon class="w-24 h-24 text-white/50" />
                </div>
              </div>
            </div>

            <!-- Informations de l'événement -->
            <div class="order-1 lg:order-2">
              <h1 class="text-4xl md:text-5xl font-bold text-primea-blue mb-6 leading-tight">
                {{ event.title }}
              </h1>
              
              <!-- Métadonnées principales sur une ligne -->
              <div class="flex flex-wrap gap-8 mb-8 text-gray-600">
                <div class="flex items-center gap-3">
                  <div class="bg-primea-blue/10 p-2 rounded-primea">
                    <CalendarIcon class="w-5 h-5 text-primea-blue" />
                  </div>
                  <span class="font-semibold">{{ formatFullDate(eventDate) }}</span>
                </div>
                
                <div class="flex items-center gap-3">
                  <div class="bg-primea-blue/10 p-2 rounded-primea">
                    <ClockIcon class="w-5 h-5 text-primea-blue" />
                  </div>
                  <span class="font-semibold">{{ formatTime(eventDate) }}</span>
                </div>
                
                <div class="flex items-center gap-3">
                  <div class="bg-primea-blue/10 p-2 rounded-primea">
                    <MapPinIcon class="w-5 h-5 text-primea-blue" />
                  </div>
                  <span class="font-semibold">{{ event.venue_name }}, {{ event.venue_city }}</span>
                </div>
              </div>

              <!-- Types de tickets et prix -->
              <div class="bg-primea-blue/5 p-6 rounded-primea-xl mb-8">
                <h3 class="text-lg font-bold text-primea-blue mb-4">Types de tickets</h3>
                
                <div v-if="event.ticket_types && event.ticket_types.length > 0" class="space-y-3">
                  <div 
                    v-for="ticketType in event.ticket_types" 
                    :key="ticketType.id"
                    class="flex items-center justify-between p-4 bg-white rounded-primea border border-gray-200 hover:border-primea-blue transition-all duration-200"
                  >
                    <div class="flex-1">
                      <h4 class="font-semibold text-primea-blue">{{ ticketType.name }}</h4>
                      <p v-if="ticketType.description" class="text-sm text-gray-600">{{ ticketType.description }}</p>
                      <div class="flex items-center gap-4 mt-2 text-sm">
                        <span class="text-gray-500">
                          Places disponibles: 
                          <span class="font-semibold" :class="getQuantityDisplayCount(ticketType) > 10 ? 'text-green-600' : getQuantityDisplayCount(ticketType) > 0 ? 'text-orange-500' : 'text-red-600'">
                            {{ getQuantityDisplay(ticketType) }}
                          </span>
                        </span>
                      </div>
                    </div>
                    
                    <div class="text-right">
                      <div class="text-2xl font-bold text-primea-blue">
                        <span v-if="ticketType.price > 0">{{ formatPrice(ticketType.price) }} FCFA</span>
                        <span v-else class="text-green-600">GRATUIT</span>
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Fallback si pas de types de tickets -->
                <div v-else class="flex items-center justify-between p-4 bg-white rounded-primea border border-gray-200">
                  <div>
                    <h4 class="font-semibold text-primea-blue">Billet standard</h4>
                    <p class="text-sm text-gray-600">Accès général à l'événement</p>
                    <div class="text-sm text-gray-500 mt-2">
                      Places disponibles: 
                      <span class="font-semibold" :class="availableTickets > 20 ? 'text-green-600' : availableTickets > 0 ? 'text-orange-500' : 'text-red-600'">
                        {{ availableTickets > 0 ? availableTickets : 'Complet' }}
                      </span>
                    </div>
                  </div>
                  
                  <div class="text-right">
                    <div class="text-2xl font-bold text-primea-blue">
                      <span v-if="minPrice > 0">{{ formatPrice(minPrice) }} FCFA</span>
                      <span v-else class="text-green-600">GRATUIT</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Boutons de réservation et favoris -->
              <div class="flex items-center gap-4 mb-8">
                <button 
                  v-if="canPurchaseTickets"
                  @click="goToBooking" 
                  class="flex-1 text-white px-8 py-4 rounded-primea-lg text-lg font-bold hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200 shadow-primea-lg transform hover:scale-105 flex items-center justify-center gap-2"
                  style="background-color: #272d63;"
                >
                  <TicketIcon class="w-6 h-6" />
                  <span v-if="minPrice > 0">
                    Réserver dès {{ formatPrice(minPrice) }} FCFA
                  </span>
                  <span v-else>Réserver gratuitement</span>
                </button>
                
                <div v-else class="flex-1 px-8 py-4 rounded-primea-lg font-bold text-lg flex items-center justify-center gap-2"
                     :class="isEventPassed ? 'bg-gray-500 text-white' : 'bg-red-600 text-white'">
                  <ExclamationCircleIcon class="w-6 h-6" />
                  {{ isEventPassed ? 'Événement terminé' : 'Événement complet' }}
                </div>
                
                <FavoriteButton :eventId="event.id" />
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Section du contenu principal -->
      <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Contenu principal -->
            <div class="lg:col-span-2 space-y-8">
              
              <!-- Description -->
              <div class="bg-white p-8 rounded-primea-xl shadow-primea">
                <h2 class="text-2xl font-bold text-primea-blue mb-6 flex items-center gap-3">
                  <div class="bg-primea-blue/10 p-2 rounded-primea">
                    <InformationCircleIcon class="w-6 h-6 text-primea-blue" />
                  </div>
                  À propos de cet événement
                </h2>
                <div class="text-gray-700 leading-relaxed space-y-4">
                  <p v-for="paragraph in descriptionParagraphs" :key="paragraph" class="text-lg">
                    {{ paragraph }}
                  </p>
                  <div v-if="!descriptionParagraphs.length" class="text-gray-500 italic">
                    Aucune description disponible pour cet événement.
                  </div>
                </div>
              </div>

              <!-- Programme / Planning -->
              <ScheduleSection v-if="event.schedules && event.schedules.length > 1" :schedules="event.schedules" />

              <!-- Lieu et accès -->
              <VenueSection :event="event" />

              <!-- Section événements similaires avec carrousel -->
              <div v-if="similarEvents.length > 0" class="bg-white p-8 rounded-primea-xl shadow-primea">
                <h2 class="text-2xl font-bold text-primea-blue mb-6 flex items-center gap-3">
                  <div class="bg-primea-blue/10 p-2 rounded-primea">
                    <HeartIcon class="w-6 h-6 text-primea-blue" />
                  </div>
                  Événements similaires
                </h2>
                
                <!-- Carrousel d'événements -->
                <div class="relative">
                  <!-- Navigation précédent -->
                  <button 
                    v-if="similarEvents.length > 2"
                    @click="previousSlide"
                    class="absolute left-0 top-1/2 transform -translate-y-1/2 -translate-x-4 z-10 bg-primea-blue text-white p-2 rounded-full hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200 shadow-primea"
                  >
                    <ChevronLeftIcon class="w-5 h-5" />
                  </button>

                  <!-- Container du carrousel -->
                  <div class="overflow-hidden rounded-primea-lg">
                    <div 
                      class="flex transition-transform duration-500 ease-in-out"
                      :style="{ transform: `translateX(-${currentSlide * 100}%)` }"
                    >
                      <!-- Slides avec 2 événements par slide -->
                      <div 
                        v-for="(slide, slideIndex) in slidesData" 
                        :key="slideIndex"
                        class="w-full flex-shrink-0"
                      >
                        <div class="grid grid-cols-2 gap-4 px-2">
                          <EventCard 
                            v-for="similarEvent in slide" 
                            :key="similarEvent.id" 
                            :event="similarEvent"
                            class="transform hover:scale-105 transition-all duration-300"
                          />
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Navigation suivant -->
                  <button 
                    v-if="similarEvents.length > 2"
                    @click="nextSlide"
                    class="absolute right-0 top-1/2 transform -translate-y-1/2 translate-x-4 z-10 bg-primea-blue text-white p-2 rounded-full hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200 shadow-primea"
                  >
                    <ChevronRightIcon class="w-5 h-5" />
                  </button>

                  <!-- Indicateurs de slides -->
                  <div v-if="slidesData.length > 1" class="flex justify-center mt-6 space-x-2">
                    <button
                      v-for="(slide, index) in slidesData"
                      :key="index"
                      @click="currentSlide = index"
                      :class="[
                        'w-3 h-3 rounded-full transition-all duration-200',
                        index === currentSlide 
                          ? 'bg-primea-blue scale-125' 
                          : 'bg-gray-300 hover:bg-primea-yellow'
                      ]"
                    ></button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1 space-y-6">
              <!-- Partage -->
              <ShareCard :event="event" />
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useEventsStore } from '../stores/events'
import EventCard from '../components/EventCard.vue'
import FavoriteButton from '../components/FavoriteButton.vue'
import EventInfoCard from '../components/EventInfoCard.vue'
import ScheduleSection from '../components/ScheduleSection.vue'
import VenueSection from '../components/VenueSection.vue'
import ShareCard from '../components/ShareCard.vue'
import { 
  PhotoIcon,
  CalendarIcon,
  ClockIcon,
  MapPinIcon,
  TicketIcon,
  ExclamationCircleIcon,
  InformationCircleIcon,
  HeartIcon,
  ChevronLeftIcon,
  ChevronRightIcon
} from '@heroicons/vue/24/outline'

export default {
  name: 'EventDetail',
  components: {
    EventCard,
    FavoriteButton,
    EventInfoCard,
    ScheduleSection,
    VenueSection,
    ShareCard,
    PhotoIcon,
    CalendarIcon,
    ClockIcon,
    MapPinIcon,
    TicketIcon,
    ExclamationCircleIcon,
    InformationCircleIcon,
    HeartIcon,
    ChevronLeftIcon,
    ChevronRightIcon
  },
  setup() {
    const route = useRoute()
    const router = useRouter()
    const eventsStore = useEventsStore()
    
    // État réactif
    const event = ref(null)
    const similarEvents = ref([])
    const loading = ref(true)
    const error = ref(null)
    const currentSlide = ref(0)

    // Computed properties
    const eventDate = computed(() => {
      if (event.value?.schedules && event.value.schedules.length > 0) {
        const dateStr = event.value.schedules[0].starts_at
        if (dateStr) {
          return new Date(dateStr)
        }
      }
      return null
    })

    const minPrice = computed(() => {
      if (event.value?.ticket_types && event.value.ticket_types.length > 0) {
        const prices = event.value.ticket_types.map(t => parseFloat(t.price) || 0).filter(price => price > 0)
        return prices.length > 0 ? Math.min(...prices) : 0
      }
      return 0
    })

    const isEventPassed = computed(() => {
      if (!eventDate.value) return false
      return new Date() > eventDate.value
    })

    const availableTickets = computed(() => {
      // Si l'événement est passé, considérer qu'il n'y a plus de tickets disponibles
      if (isEventPassed.value) return 0
      
      if (event.value?.ticket_types && event.value.ticket_types.length > 0) {
        return event.value.ticket_types.reduce((total, ticketType) => {
          // Utiliser remaining_quantity si disponible, sinon calculer
          if (ticketType.remaining_quantity !== undefined && ticketType.remaining_quantity !== null) {
            return total + Math.max(0, ticketType.remaining_quantity)
          }
          // Utiliser available_quantity et sold_quantity
          if (ticketType.available_quantity !== undefined && ticketType.available_quantity !== null) {
            const sold = ticketType.sold_quantity || 0
            return total + Math.max(0, ticketType.available_quantity - sold)
          }
          return total
        }, 0)
      }
      return 1000 // Valeur par défaut élevée si pas d'info
    })

    const canPurchaseTickets = computed(() => {
      return !isEventPassed.value && availableTickets.value > 0
    })

    const descriptionParagraphs = computed(() => {
      if (!event.value?.description) return []
      return event.value.description.split('\n').filter(p => p.trim())
    })

    // Computed pour organiser les événements similaires en slides de 2
    const slidesData = computed(() => {
      const slides = []
      for (let i = 0; i < similarEvents.value.length; i += 2) {
        slides.push(similarEvents.value.slice(i, i + 2))
      }
      return slides
    })

    // Méthodes
    const formatFullDate = (date) => {
      if (!date) return 'Date à confirmer'
      return date.toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
      })
    }

    const formatTime = (date) => {
      if (!date) return 'Heure à confirmer'
      return date.toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    const formatPrice = (price) => {
      return new Intl.NumberFormat('fr-FR').format(price)
    }

    const goToBooking = () => {
      // Rediriger vers la page de checkout avec le slug de l'événement
      router.push(`/checkout/${route.params.slug}`)
    }

    const loadEvent = async () => {
      const eventSlug = route.params.slug
      if (!eventSlug) return

      try {
        loading.value = true
        error.value = null
        
        const data = await eventsStore.fetchEvent(eventSlug)
        event.value = data.event
        
        // Charger les événements similaires
        if (event.value?.category) {
          const eventsData = await eventsStore.getEventsByCategory(event.value.category)
          similarEvents.value = eventsData.events
            .filter(e => e.slug !== event.value.slug)
            .slice(0, 5)
        } else {
          // Si pas de catégorie, charger tous les événements et filtrer
          const allEventsData = await eventsStore.fetchEvents()
          similarEvents.value = allEventsData.events
            .filter(e => e.slug !== event.value.slug)
            .slice(0, 5)
        }
        
      } catch (err) {
        error.value = err.message || 'Erreur lors du chargement de l\'événement'
      } finally {
        loading.value = false
      }
    }

    const handleTicketBooked = (bookingData) => {
      // Gérer la réservation réussie
      console.log('Tickets réservés:', bookingData)
      // Rediriger vers la page de confirmation ou afficher un message de succès
    }

    // Méthodes pour l'affichage des quantités
    const getQuantityDisplayCount = (ticketType) => {
      if (ticketType.remaining_quantity !== undefined && ticketType.remaining_quantity !== null) {
        return Math.max(0, ticketType.remaining_quantity)
      }
      if (ticketType.available_quantity !== undefined && ticketType.available_quantity !== null) {
        const sold = ticketType.sold_quantity || 0
        return Math.max(0, ticketType.available_quantity - sold)
      }
      return 0
    }

    const getQuantityDisplay = (ticketType) => {
      const count = getQuantityDisplayCount(ticketType)
      if (ticketType.available_quantity === null) {
        return 'Illimitées'
      }
      return count > 0 ? count : 'Complet'
    }

    // Méthodes du carrousel
    const nextSlide = () => {
      if (currentSlide.value < slidesData.value.length - 1) {
        currentSlide.value++
      } else {
        currentSlide.value = 0 // Retour au début
      }
    }

    const previousSlide = () => {
      if (currentSlide.value > 0) {
        currentSlide.value--
      } else {
        currentSlide.value = slidesData.value.length - 1 // Aller à la fin
      }
    }

    // Watchers
    watch(() => route.params.slug, () => {
      if (route.params.slug) {
        loadEvent()
      }
    })

    // Lifecycle
    onMounted(() => {
      loadEvent()
    })

    return {
      // État
      event,
      similarEvents,
      loading,
      error,
      currentSlide,
      
      // Computed
      eventDate,
      minPrice,
      isEventPassed,
      availableTickets,
      canPurchaseTickets,
      descriptionParagraphs,
      slidesData,
      
      // Méthodes
      formatFullDate,
      formatTime,
      formatPrice,
      goToBooking,
      loadEvent,
      handleTicketBooked,
      getQuantityDisplayCount,
      getQuantityDisplay,
      nextSlide,
      previousSlide
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

/* Fond dégradé Primea */
.bg-primea-gradient {
  background: linear-gradient(135deg, var(--primea-blue) 0%, var(--primea-blue-dark) 100%);
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
  background-color: var(--primea-blue) !important;
}

.bg-primea-yellow {
  background-color: var(--primea-yellow) !important;
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

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

/* Transitions */
.transition-all {
  transition: all 0.2s ease-in-out;
}

.transition-colors {
  transition: color 0.2s ease-in-out;
}

/* Style pour le bouton de réservation avec hover visible */
button.hover\:bg-primea-yellow:hover {
  background-color: #fab511 !important;
  color: #272d63 !important;
}

button.hover\:bg-primea-yellow:hover svg {
  color: #272d63 !important;
}

/* Responsive classes */
.container {
  max-width: 1200px;
  margin: 0 auto;
}

.grid {
  display: grid;
}

.grid-cols-1 {
  grid-template-columns: repeat(1, minmax(0, 1fr));
}

@media (min-width: 1024px) {
  .lg\:grid-cols-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  
  .lg\:grid-cols-3 {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
  
  .lg\:col-span-2 {
    grid-column: span 2 / span 2;
  }
  
  .lg\:col-span-1 {
    grid-column: span 1 / span 1;
  }
  
  .lg\:order-1 {
    order: 1;
  }
  
  .lg\:order-2 {
    order: 2;
  }
}

.gap-4 {
  gap: 1rem;
}

.gap-8 {
  gap: 2rem;
}

.gap-12 {
  gap: 3rem;
}

.space-y-4 > * + * {
  margin-top: 1rem;
}

.space-y-6 > * + * {
  margin-top: 1.5rem;
}

.space-y-8 > * + * {
  margin-top: 2rem;
}

/* Utility classes */
.flex {
  display: flex;
}

.items-center {
  align-items: center;
}

.justify-center {
  justify-content: center;
}

.justify-between {
  justify-content: space-between;
}

.min-h-screen {
  min-height: 100vh;
}

.sticky {
  position: sticky;
}

.top-0 {
  top: 0;
}

.z-50 {
  z-index: 50;
}

.order-1 {
  order: 1;
}

.order-2 {
  order: 2;
}
</style>