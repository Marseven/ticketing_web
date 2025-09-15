<template>
  <article class="event-card-modern group cursor-pointer" @click="goToEvent">
    <!-- Image de l'événement -->
    <div class="event-image">
      <img 
        v-if="event.image_url" 
        :src="event.image_url" 
        :alt="event.title" 
        loading="lazy"
        class="w-full h-full object-cover"
      />
      <div 
        v-else 
        class="w-full h-full bg-gradient-to-br from-blue-100 to-yellow-100 flex items-center justify-center"
      >
        <CalendarIcon size="2xl" class="primea-text-gray-400" />
      </div>
      
      <!-- Badge de catégorie -->
      <div v-if="event.category?.name" class="absolute top-4 left-4">
        <span class="bg-yellow-400 primea-text-blue px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wide">
          {{ event.category.name }}
        </span>
      </div>
      
      <!-- Prix sur l'image -->
      <div class="absolute bottom-4 right-4 bg-black/70 backdrop-blur-sm rounded-lg px-3 py-2 text-white">
        <div v-if="minPrice > 0">
          <div class="text-xs text-gray-300">À partir de</div>
          <div class="text-lg font-bold">{{ formatPrice(minPrice) }} FCFA</div>
        </div>
        <div v-else class="text-lg font-bold text-green-400">Gratuit</div>
      </div>
      
      <!-- Overlay avec action rapide -->
      <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-all duration-300 flex items-center justify-center">
        <button 
          @click.stop="goToEvent"
          class="bg-primea-yellow text-primea-blue px-6 py-3 rounded-primea-lg font-bold hover:bg-white hover:text-primea-blue transition-all duration-200 shadow-primea-lg transform translate-y-4 group-hover:translate-y-0 flex items-center"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
          </svg>
          Voir détails
        </button>
      </div>
    </div>

    <!-- Contenu de la carte -->
    <div class="p-6">
      <!-- Métadonnées -->
      <div class="flex items-center gap-4 mb-3 text-sm text-gray-500">
        <div class="flex items-center gap-1">
          <CalendarIcon size="sm" />
          {{ formatDate(eventDate) }}
        </div>
        <div class="flex items-center gap-1">
          <ClockIcon class="w-4 h-4" />
          {{ formatTime(eventDate) }}
        </div>
      </div>

      <!-- Titre de l'événement -->
      <h3 class="font-bold text-xl primea-text-blue mb-2 line-clamp-2">
        <button @click="goToEvent" class="hover:underline text-left">
          {{ event.title }}
        </button>
      </h3>

      <!-- Lieu -->
      <div class="flex items-center gap-1 mb-3 text-gray-600">
        <MapPinIcon class="w-4 h-4" />
        <span v-if="event.venue">{{ event.venue.name }}, {{ event.venue.city }}</span>
        <span v-else>Lieu à confirmer</span>
      </div>

      <!-- Description courte -->
      <p class="text-gray-600 text-sm mb-4 line-clamp-3">
        {{ truncateText(event.description, 120) }}
      </p>

      <!-- Pied de carte -->
      <div class="flex items-center justify-between">
        <!-- Places restantes -->
        <div>
          <div v-if="isEventPassed" class="text-gray-500 text-sm font-medium flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Événement terminé
          </div>
          <div v-else-if="availableTickets < 20 && availableTickets > 0" class="text-amber-600 text-sm font-medium flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ availableTickets }} place{{ availableTickets > 1 ? 's' : '' }} restante{{ availableTickets > 1 ? 's' : '' }}
          </div>
          <div v-else-if="availableTickets === 0 && !isEventPassed" class="text-red-600 text-sm font-medium">Complet</div>
        </div>

        <!-- Bouton d'action -->
        <button 
          @click.stop="canPurchase ? goToEvent : null"
          :class="[
            'px-6 py-2 rounded-primea text-sm font-bold transition-all duration-200 font-primea shadow-primea border-2',
            canPurchase 
              ? 'bg-primea-blue text-white hover:bg-primea-yellow hover:text-primea-blue transform hover:scale-105 border-primea-blue hover:border-primea-yellow'
              : isEventPassed 
                ? 'bg-gray-400 text-white cursor-not-allowed border-gray-400'
                : 'bg-red-500 text-white cursor-not-allowed border-red-500'
          ]"
          :disabled="!canPurchase"
        >
          {{ buttonText }}
        </button>
      </div>
    </div>
  </article>
</template>

<script>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import CalendarIcon from './icons/CalendarIcon.vue'
import { ClockIcon, MapPinIcon } from '@heroicons/vue/24/outline'

export default {
  name: 'EventCard',
  components: {
    CalendarIcon,
    ClockIcon,
    MapPinIcon
  },
  props: {
    event: {
      type: Object,
      required: true
    }
  },
  setup(props) {
    const router = useRouter()

    // Computed properties
    const eventDate = computed(() => {
      if (props.event.schedules && props.event.schedules.length > 0) {
        // L'API retourne starts_at au format "27/07/2025 20:00:00"
        const dateStr = props.event.schedules[0].starts_at
        if (dateStr) {
          // Convertir le format français vers un format ISO
          const [datePart, timePart] = dateStr.split(' ')
          const [day, month, year] = datePart.split('/')
          return new Date(`${year}-${month}-${day}T${timePart}`)
        }
      }
      return null
    })

    const minPrice = computed(() => {
      // Vérifier d'abord ticket_types (snake_case) puis ticketTypes (camelCase)
      const ticketTypes = props.event.ticket_types || props.event.ticketTypes
      if (ticketTypes && ticketTypes.length > 0) {
        return Math.min(...ticketTypes.map(t => t.price || 0))
      }
      return 10000 // Prix par défaut pour l'affichage
    })

    const isEventPassed = computed(() => {
      if (!eventDate.value) return false
      return new Date() > eventDate.value
    })

    const availableTickets = computed(() => {
      // Si l'événement est passé, considérer qu'il n'y a plus de billets disponibles
      if (isEventPassed.value) return 0
      
      const ticketTypes = props.event.ticket_types || props.event.ticketTypes
      if (ticketTypes && ticketTypes.length > 0) {
        return ticketTypes.reduce((total, ticketType) => {
          return total + (ticketType.quantity - (ticketType.sold || 0))
        }, 0)
      }
      return 100 // Valeur par défaut si pas d'info
    })

    const buttonText = computed(() => {
      if (isEventPassed.value) return 'Événement passé'
      if (availableTickets.value === 0) return 'Complet'
      return 'Réserver'
    })

    const canPurchase = computed(() => {
      return !isEventPassed.value && availableTickets.value > 0
    })

    // Méthodes
    const formatDate = (date) => {
      if (!date) return 'Date à confirmer'
      return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      })
    }

    const formatTime = (date) => {
      if (!date) return '--:--'
      return date.toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    const formatPrice = (price) => {
      return new Intl.NumberFormat('fr-FR').format(price)
    }

    const truncateText = (text, maxLength) => {
      if (text.length <= maxLength) return text
      return text.substr(0, maxLength).trim() + '...'
    }

    const goToEvent = () => {
      router.push(`/events/${props.event.id}`)
    }

    return {
      eventDate,
      minPrice,
      isEventPassed,
      availableTickets,
      buttonText,
      canPurchase,
      formatDate,
      formatTime,
      formatPrice,
      truncateText,
      goToEvent
    }
  }
}
</script>

<style scoped>
.event-card-modern {
  position: relative;
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.event-card-modern:hover {
  transform: translateY(-8px);
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
}

.event-image {
  position: relative;
  overflow: hidden;
  height: 200px;
}

.event-image img {
  transition: transform 0.5s ease;
}

.event-card-modern:hover .event-image img {
  transform: scale(1.1);
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.btn-gradient-primary {
  background: linear-gradient(135deg, var(--primea-blue) 0%, var(--primea-blue-dark) 100%);
  color: var(--primea-white);
  border: none;
  font-family: var(--font-primary);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  position: relative;
  overflow: hidden;
}

.btn-gradient-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(39, 45, 99, 0.3);
}

.btn-gradient-secondary {
  background: linear-gradient(135deg, var(--primea-yellow) 0%, var(--primea-yellow-dark) 100%);
  color: var(--primea-blue);
  border: none;
  padding: 0.5rem 1.5rem;
  border-radius: 0.5rem;
  font-family: var(--font-primary);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  position: relative;
  overflow: hidden;
}

.btn-gradient-secondary:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(250, 181, 17, 0.3);
}

.primea-text-blue {
  color: #272d63;
}

.primea-text-white {
  color: #ffffff;
}

.primea-text-gray-400 {
  color: #9ca3af;
}

:root {
  --primea-blue: #272d63;
  --primea-yellow: #fab511;
  --primea-white: #ffffff;
  --primea-blue-dark: #1a1e47;
  --primea-yellow-dark: #e09f0e;
  --font-primary: 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
</style>