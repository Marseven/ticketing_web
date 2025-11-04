<template>
  <div class="event-detail-page bg-white min-h-screen">
    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="flex items-center justify-center min-h-screen">
        <div class="animate-spin rounded-full h-16 w-16 border-4 border-blue-900 border-t-yellow-500"></div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-container">
      <div class="flex items-center justify-center min-h-screen bg-gray-50 px-4">
        <div class="text-center bg-white p-8 md:p-12 rounded-2xl shadow-lg max-w-md w-full">
          <div class="text-red-600 text-lg md:text-xl mb-6 font-semibold">{{ error }}</div>
          <button @click="loadEvent" class="w-full md:w-auto bg-blue-900 text-white px-8 py-3 rounded-xl font-bold hover:bg-yellow-500 hover:text-blue-900 transition-all duration-200">
            Réessayer
          </button>
        </div>
      </div>
    </div>

    <!-- Event Content -->
    <div v-else-if="event" class="event-content">
      <!-- Event Image Hero (Mobile: full width, Desktop: container with rounded corners) -->
      <section class="relative md:px-4 md:py-6">
        <div class="max-w-7xl mx-auto">
          <div class="w-full h-64 md:h-96 lg:h-[500px] overflow-hidden md:rounded-2xl">
            <img
              v-if="eventImageUrl"
              :src="eventImageUrl"
              :alt="event.title"
              class="w-full h-full object-cover"
              @error="handleImageError"
            />
            <div v-else class="w-full h-full bg-gradient-to-br from-blue-900 to-blue-800 flex items-center justify-center">
              <svg class="w-24 h-24 md:w-32 md:h-32 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
            </div>
          </div>
        </div>
      </section>

      <!-- Event Info Section -->
      <section class="bg-white">
        <div class="max-w-7xl mx-auto px-4 py-6 md:py-8">
          <!-- Event Title -->
          <h1 class="text-2xl md:text-4xl lg:text-5xl font-bold text-blue-900 mb-3 md:mb-4 leading-tight">
            {{ event.title }}
          </h1>

          <!-- Event Meta Info (Mobile: Stack, Desktop: Row) -->
          <div class="space-y-2 md:space-y-0 md:flex md:flex-wrap md:gap-6 mb-4 md:mb-6">
            <!-- Date -->
            <div class="flex items-center gap-3 text-gray-700">
              <div class="bg-blue-100 p-2 rounded-lg flex-shrink-0">
                <svg class="w-5 h-5 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
              </div>
              <span class="font-semibold text-sm md:text-base">{{ formatFullDate(eventDate) }}</span>
            </div>

            <!-- Time -->
            <div class="flex items-center gap-3 text-gray-700">
              <div class="bg-blue-100 p-2 rounded-lg flex-shrink-0">
                <svg class="w-5 h-5 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <span class="font-semibold text-sm md:text-base">{{ formatTime(eventDate) }}</span>
            </div>
          </div>

          <!-- Organizer Info (if available) -->
          <div v-if="event.organizer" class="mb-4 pb-4 border-b border-gray-200">
            <p class="text-sm text-gray-600">Organisé par</p>
            <p class="font-bold text-blue-900">{{ event.organizer.name || event.organizer.organization_name }}</p>
          </div>

          <!-- Desktop: Two Column Layout -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
            <!-- Main Content (Left Column on Desktop) -->
            <div class="lg:col-span-2 space-y-4">
              <!-- Description - Mobile Only (before tickets) -->
              <div class="md:hidden bg-white p-4 rounded-2xl border border-gray-200">
                <h2 class="text-lg font-bold text-blue-900 mb-3 flex items-center gap-2">
                  <div class="bg-blue-100 p-2 rounded-lg">
                    <svg class="w-5 h-5 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                  </div>
                  À propos de cet événement
                </h2>
                <div class="text-gray-700 leading-snug space-y-2 text-sm">
                  <p v-for="(paragraph, index) in descriptionParagraphs" :key="index">
                    {{ paragraph }}
                  </p>
                  <div v-if="!descriptionParagraphs.length" class="text-gray-500 italic">
                    Aucune description disponible pour cet événement.
                  </div>
                </div>
              </div>

              <!-- Ticket Types Section -->
              <div class="bg-gray-50 p-4 md:p-6 rounded-2xl">
                <h3 class="text-lg md:text-xl font-bold text-blue-900 mb-3 flex items-center gap-2">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                  </svg>
                  Types de tickets
                </h3>

                <div v-if="event.ticket_types && event.ticket_types.length > 0" class="space-y-2">
                  <div
                    v-for="ticketType in event.ticket_types"
                    :key="ticketType.id"
                    class="bg-white p-4 rounded-xl border-2 border-gray-200 hover:border-blue-900 transition-all duration-200"
                  >
                    <div class="flex items-start justify-between gap-4">
                      <div class="flex-1 min-w-0">
                        <h4 class="font-bold text-blue-900 mb-1 text-base md:text-lg">{{ ticketType.name }}</h4>
                        <p v-if="ticketType.description" class="text-sm text-gray-600 mb-2 line-clamp-2">{{ ticketType.description }}</p>
                        <div class="flex items-center gap-2 text-xs md:text-sm">
                          <span class="text-gray-500">Places disponibles:</span>
                          <span class="font-semibold px-2 py-1 rounded-lg" :class="getQuantityDisplayCount(ticketType) > 10 ? 'bg-green-100 text-green-700' : getQuantityDisplayCount(ticketType) > 0 ? 'bg-orange-100 text-orange-700' : 'bg-red-100 text-red-700'">
                            {{ getQuantityDisplay(ticketType) }}
                          </span>
                        </div>
                      </div>

                      <div class="text-right flex-shrink-0">
                        <div class="text-xl md:text-2xl font-bold text-blue-900">
                          <span v-if="ticketType.price > 0">{{ formatPrice(ticketType.price) }}</span>
                          <span v-else class="text-green-600">GRATUIT</span>
                        </div>
                        <div class="text-xs text-gray-500">XAF</div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Fallback if no ticket types -->
                <div v-else class="bg-white p-4 rounded-xl border-2 border-gray-200">
                  <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                      <h4 class="font-bold text-blue-900 mb-1">Billet standard</h4>
                      <p class="text-sm text-gray-600 mb-2">Accès général à l'événement</p>
                      <div class="flex items-center gap-2 text-sm">
                        <span class="text-gray-500">Places disponibles:</span>
                        <span class="font-semibold px-2 py-1 rounded-lg" :class="availableTickets > 20 ? 'bg-green-100 text-green-700' : availableTickets > 0 ? 'bg-orange-100 text-orange-700' : 'bg-red-100 text-red-700'">
                          {{ availableTickets > 0 ? availableTickets : 'Complet' }}
                        </span>
                      </div>
                    </div>

                    <div class="text-right">
                      <div class="text-2xl font-bold text-blue-900">
                        <span v-if="minPrice > 0">{{ formatPrice(minPrice) }}</span>
                        <span v-else class="text-green-600">GRATUIT</span>
                      </div>
                      <div class="text-xs text-gray-500">XAF</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Description - Desktop Only (after tickets) -->
              <div class="hidden md:block bg-white p-4 md:p-6 rounded-2xl border border-gray-200">
                <h2 class="text-lg md:text-xl font-bold text-blue-900 mb-3 flex items-center gap-2">
                  <div class="bg-blue-100 p-2 rounded-lg">
                    <svg class="w-5 h-5 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                  </div>
                  À propos de cet événement
                </h2>
                <div class="text-gray-700 leading-snug space-y-2 text-sm md:text-base">
                  <p v-for="(paragraph, index) in descriptionParagraphs" :key="index">
                    {{ paragraph }}
                  </p>
                  <div v-if="!descriptionParagraphs.length" class="text-gray-500 italic">
                    Aucune description disponible pour cet événement.
                  </div>
                </div>
              </div>

              <!-- Schedule Section (if available) -->
              <ScheduleSection v-if="event.schedules && event.schedules.length > 1" :schedules="event.schedules" />

              <!-- Venue Section -->
              <VenueSection :event="event" />

              <!-- Similar Events (Desktop only, mobile shows at bottom) -->
              <div v-if="similarEvents.length > 0" class="hidden md:block bg-white p-6 rounded-2xl border border-gray-200">
                <h2 class="text-xl font-bold text-blue-900 mb-4 flex items-center gap-2">
                  <div class="bg-blue-100 p-2 rounded-lg">
                    <svg class="w-5 h-5 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                  </div>
                  Événements similaires
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <EventCard
                    v-for="similarEvent in similarEvents.slice(0, 4)"
                    :key="similarEvent.id"
                    :event="similarEvent"
                    class="transform hover:scale-105 transition-all duration-300"
                  />
                </div>
              </div>
            </div>

            <!-- Sidebar (Right Column on Desktop) -->
            <div class="lg:col-span-1 space-y-4">
              <!-- Share Card -->
              <ShareCard :event="event" />

              <!-- Favorite Button (Desktop) -->
              <div class="hidden lg:block">
                <FavoriteButton :eventId="event.id" class="w-full" />
              </div>
            </div>
          </div>

          <!-- Similar Events (Mobile only) -->
          <div v-if="similarEvents.length > 0" class="md:hidden mt-6 bg-white p-4 rounded-2xl border border-gray-200">
            <h2 class="text-lg font-bold text-blue-900 mb-3 flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
              </svg>
              Événements similaires
            </h2>

            <div class="space-y-2">
              <EventCard
                v-for="similarEvent in similarEvents.slice(0, 3)"
                :key="similarEvent.id"
                :event="similarEvent"
              />
            </div>
          </div>
        </div>
      </section>
    </div>

    <!-- Fixed Bottom Action Bar (Mobile Only) -->
    <div v-if="event && !loading && !error" class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-4 md:hidden z-40 shadow-lg">
      <div class="flex items-center gap-3">
        <FavoriteButton :eventId="event.id" />

        <button
          v-if="canPurchaseTickets"
          @click="goToBooking"
          class="ticket-pulse-btn flex-1 bg-blue-900 text-white px-5 py-3 rounded-xl text-sm font-bold hover:bg-yellow-500 hover:text-blue-900 transition-all duration-200 shadow-lg flex items-center justify-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
          </svg>
          <span v-if="minPrice > 0">Prendre un ticket - {{ formatPrice(minPrice) }} XAF</span>
          <span v-else>Prendre un ticket gratuit</span>
        </button>

        <div v-else class="flex-1 px-5 py-3 rounded-xl font-bold text-sm flex items-center justify-center gap-2"
             :class="isEventPassed ? 'bg-gray-500 text-white' : 'bg-red-600 text-white'">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
          </svg>
          {{ isEventPassed ? 'Terminé' : 'Complet' }}
        </div>
      </div>
    </div>

    <!-- Desktop: Action Button in Content (not fixed) -->
    <div v-if="event && !loading && !error" class="hidden md:block">
      <div class="max-w-7xl mx-auto px-4 pb-8">
        <div class="flex items-center gap-4 justify-center lg:justify-start">
          <button
            v-if="canPurchaseTickets"
            @click="goToBooking"
            class="ticket-pulse-btn bg-blue-900 text-white px-10 py-4 rounded-xl text-lg font-bold hover:bg-yellow-500 hover:text-blue-900 transition-all duration-200 shadow-lg transform hover:scale-105 flex items-center justify-center gap-3"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
            </svg>
            <span v-if="minPrice > 0">
              Prendre un ticket à partir de {{ formatPrice(minPrice) }} XAF
            </span>
            <span v-else>Prendre un ticket gratuit</span>
          </button>

          <div v-else class="px-10 py-4 rounded-xl font-bold text-lg flex items-center justify-center gap-3"
               :class="isEventPassed ? 'bg-gray-500 text-white' : 'bg-red-600 text-white'">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            {{ isEventPassed ? 'Événement terminé' : 'Événement complet' }}
          </div>
        </div>
      </div>
    </div>

    <!-- Add bottom padding for mobile fixed button -->
    <div class="h-24 md:hidden"></div>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useEventsStore } from '../stores/events'
import EventCard from '../components/EventCard.vue'
import FavoriteButton from '../components/FavoriteButton.vue'
import ScheduleSection from '../components/ScheduleSection.vue'
import VenueSection from '../components/VenueSection.vue'
import ShareCard from '../components/ShareCard.vue'

export default {
  name: 'EventDetail',
  components: {
    EventCard,
    FavoriteButton,
    ScheduleSection,
    VenueSection,
    ShareCard
  },
  setup() {
    const route = useRoute()
    const router = useRouter()
    const eventsStore = useEventsStore()

    // State
    const event = ref(null)
    const similarEvents = ref([])
    const loading = ref(true)
    const error = ref(null)
    const imageError = ref(false)

    // Computed properties
    const eventImageUrl = computed(() => {
      if (imageError.value || !event.value) return null

      let imageUrl = event.value.image || event.value.image_url || event.value.image_file

      if (!imageUrl || imageUrl.trim() === '') {
        return null
      }

      if (imageUrl.startsWith('http://') || imageUrl.startsWith('https://')) {
        return imageUrl
      }

      if (imageUrl.startsWith('/')) {
        return window.location.origin + imageUrl
      }

      if (!imageUrl.includes('/')) {
        return `${window.location.origin}/storage/images/events/${imageUrl}`
      }

      return imageUrl
    })

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
      if (isEventPassed.value) return 0

      if (event.value?.ticket_types && event.value.ticket_types.length > 0) {
        return event.value.ticket_types.reduce((total, ticketType) => {
          if (ticketType.remaining_quantity !== undefined && ticketType.remaining_quantity !== null) {
            return total + Math.max(0, ticketType.remaining_quantity)
          }
          if (ticketType.available_quantity !== undefined && ticketType.available_quantity !== null) {
            const sold = ticketType.sold_quantity || 0
            return total + Math.max(0, ticketType.available_quantity - sold)
          }
          return total
        }, 0)
      }
      return 1000
    })

    const canPurchaseTickets = computed(() => {
      return !isEventPassed.value && availableTickets.value > 0
    })

    const descriptionParagraphs = computed(() => {
      if (!event.value?.description) return []
      return event.value.description.split('\n').filter(p => p.trim())
    })

    // Methods
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

        // Load similar events
        if (event.value?.category) {
          const eventsData = await eventsStore.getEventsByCategory(event.value.category)
          similarEvents.value = eventsData.events
            .filter(e => e.slug !== event.value.slug)
            .slice(0, 6)
        } else {
          const allEventsData = await eventsStore.fetchEvents()
          similarEvents.value = allEventsData.events
            .filter(e => e.slug !== event.value.slug)
            .slice(0, 6)
        }

      } catch (err) {
        error.value = err.message || 'Erreur lors du chargement de l\'événement'
      } finally {
        loading.value = false
      }
    }

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

    const handleImageError = () => {
      console.warn('EventDetail - Erreur de chargement de l\'image')
      imageError.value = true
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
      event,
      similarEvents,
      loading,
      error,
      eventImageUrl,
      eventDate,
      minPrice,
      isEventPassed,
      availableTickets,
      canPurchaseTickets,
      descriptionParagraphs,
      formatFullDate,
      formatTime,
      formatPrice,
      goToBooking,
      loadEvent,
      getQuantityDisplayCount,
      getQuantityDisplay,
      handleImageError
    }
  }
}
</script>

<style scoped>
/* Fade transition for menu */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

/* Line clamp utility */
.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Smooth scrolling */
html {
  scroll-behavior: smooth;
}

/* Animations pour le bouton "Prendre un ticket" */
@keyframes bounce-gentle {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-4px);
  }
}

@keyframes ring-pulse {
  0% {
    opacity: 0;
    transform: scale(0.7);
  }
  40% {
    opacity: 0.6;
    transform: scale(1.15);
  }
  100% {
    opacity: 0;
    transform: scale(1.3);
  }
}

.ticket-pulse-btn {
  animation: bounce-gentle 2.5s ease-in-out infinite;
  position: relative;
}

.ticket-pulse-btn::after {
  content: '';
  position: absolute;
  inset: -5px;
  border: 2px solid rgba(250, 181, 17, 0.8);
  border-radius: 0.75rem;
  opacity: 0;
  transform: scale(0.7);
  will-change: transform, opacity;
  animation: ring-pulse 2.4s ease-out infinite;
  pointer-events: none;
}
</style>
