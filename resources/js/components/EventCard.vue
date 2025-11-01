<template>
  <article v-if="event && event.title" class="event-card-modern group cursor-pointer" @click="goToEvent">
    <!-- Image de l'événement -->
    <div class="event-image">
      <img
        v-if="eventImageUrl"
        :src="eventImageUrl"
        :alt="event.title"
        loading="lazy"
        class="w-full h-full object-cover"
        @error="handleImageError"
      />
      <div
        v-else
        class="w-full h-full bg-gradient-to-br from-blue-100 to-yellow-100 flex items-center justify-center"
      >
        <CalendarIcon size="2xl" class="primea-text-gray-400" />
      </div>
      
      <!-- Badge de catégorie -->
      <div v-if="event.category?.name || event.category_name" class="absolute top-4 left-4">
        <span class="bg-yellow-400 primea-text-blue px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wide">
          {{ event.category?.name || event.category_name || 'Événement' }}
        </span>
      </div>
      
      <!-- Prix et bouton réserver -->
      <div class="absolute bottom-4 left-4 right-4 flex items-end justify-between">
        <div class="bg-black/70 backdrop-blur-sm rounded-lg px-3 py-2 text-white">
          <div v-if="minPrice > 0">
            <div class="text-xs text-gray-300">À partir de</div>
            <div class="text-lg font-bold">{{ formatPrice(minPrice) }} FCFA</div>
          </div>
          <div v-else class="text-lg font-bold text-green-400">Gratuit</div>
        </div>

        <button
          v-if="canPurchase"
          @click.stop="goToCheckout"
          class="bg-yellow-500 text-blue-950 px-4 py-2 rounded-lg text-sm font-bold hover:bg-yellow-400 transition-colors flex items-center gap-1 shadow-lg"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
          </svg>
          Prendre un ticket
        </button>
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
      <div class="flex flex-wrap items-center gap-2 md:gap-4 mb-3 text-sm text-gray-500">
        <div class="flex items-center gap-1 whitespace-nowrap">
          <CalendarIcon size="sm" />
          <span>{{ formatDate(eventDate) }}</span>
        </div>
        <div class="flex items-center gap-1 whitespace-nowrap">
          <ClockIcon class="w-4 h-4" />
          <span>{{ formatTime(eventDate) }}</span>
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
        <span v-else-if="event.venue_name">{{ event.venue_name }}, {{ event.venue_city }}</span>
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
          @click.stop="handleReserveClick"
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
import { computed, ref } from 'vue'
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
    const imageError = ref(false)

    // Computed properties
    const eventImageUrl = computed(() => {
      if (imageError.value) return null

      // Le modèle Event retourne déjà l'URL complète dans le champ 'image' via l'accessor
      // Priorité: image (accessor) > image_url > image_file
      let imageUrl = props.event.image || props.event.image_url || props.event.image_file

      console.log('EventCard - Image URL pour:', props.event.title, 'Image:', imageUrl)

      if (!imageUrl || imageUrl.trim() === '') {
        console.log('EventCard - Aucune image trouvée')
        return null
      }

      // Si c'est déjà une URL complète (commence par http:// ou https://), on la retourne telle quelle
      if (imageUrl.startsWith('http://') || imageUrl.startsWith('https://')) {
        console.log('EventCard - URL complète détectée:', imageUrl)
        return imageUrl
      }

      // Si c'est un chemin relatif commençant par /
      if (imageUrl.startsWith('/')) {
        const fullUrl = window.location.origin + imageUrl
        console.log('EventCard - Chemin relatif converti:', fullUrl)
        return fullUrl
      }

      // Si c'est un nom de fichier dans le storage
      if (!imageUrl.includes('/')) {
        const fullUrl = `${window.location.origin}/storage/images/events/${imageUrl}`
        console.log('EventCard - Nom de fichier converti:', fullUrl)
        return fullUrl
      }

      console.log('EventCard - URL retournée telle quelle:', imageUrl)
      return imageUrl
    })

    const eventDate = computed(() => {
      // Essayer plusieurs sources de dates
      let dateStr = null;
      
      // 1. Vérifier dans schedules
      if (props.event.schedules && props.event.schedules.length > 0) {
        const schedule = props.event.schedules[0];
        dateStr = schedule.starts_at;
      }
      
      // 2. Vérifier dans next_schedule (ajouté par l'API)
      if (!dateStr && props.event.next_schedule) {
        dateStr = props.event.next_schedule.starts_at;
      }
      
      if (!dateStr) {
        return null;
      }

      try {
        // Gérer différents formats de date
        let date;
        
        // Format ISO (2024-03-15T20:00:00.000000Z)
        if (dateStr.includes('T') || dateStr.includes('Z')) {
          date = new Date(dateStr);
        }
        // Format français (27/07/2025 20:00:00)
        else if (dateStr.includes('/')) {
          const [datePart, timePart] = dateStr.split(' ');
          const [day, month, year] = datePart.split('/');
          date = new Date(`${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}T${timePart || '00:00:00'}`);
        }
        // Format SQL (2024-03-15 20:00:00)
        else if (dateStr.includes('-')) {
          // Remplacer l'espace par T pour le format ISO
          const isoString = dateStr.replace(' ', 'T');
          date = new Date(isoString);
        }
        else {
          date = new Date(dateStr);
        }

        // Vérifier que la date est valide
        if (isNaN(date.getTime())) {
          console.warn('Date invalide pour l\'événement:', props.event.title, 'Date reçue:', dateStr);
          return null;
        }

        return date;
      } catch (error) {
        console.error('Erreur lors du parsing de la date:', error, 'Date reçue:', dateStr);
        return null;
      }
    })

    const minPrice = computed(() => {
      // Utiliser les prix pré-calculés par l'API si disponibles
      if (props.event.min_price !== undefined && props.event.min_price !== null && props.event.min_price > 0) {
        return props.event.min_price;
      }
      
      // Sinon calculer à partir des types de tickets
      const ticketTypes = props.event.ticket_types || props.event.ticketTypes
      if (ticketTypes && ticketTypes.length > 0) {
        const prices = ticketTypes
          .map(t => {
            const price = parseFloat(t.price);
            return isNaN(price) ? 0 : price;
          })
          .filter(price => price > 0);
        
        if (prices.length > 0) {
          return Math.min(...prices);
        }
      }
      
      return 0; // Gratuit si aucun prix trouvé
    })

    const isEventPassed = computed(() => {
      if (!eventDate.value) return false
      return new Date() > eventDate.value
    })

    const availableTickets = computed(() => {
      // Si l'événement est passé, considérer qu'il n'y a plus de tickets disponibles
      if (isEventPassed.value) return 0
      
      const ticketTypes = props.event.ticket_types || props.event.ticketTypes
      if (ticketTypes && ticketTypes.length > 0) {
        const totalAvailable = ticketTypes.reduce((total, ticketType) => {
          // Utiliser remaining_quantity si disponible (calculé par l'API)
          if (ticketType.remaining_quantity !== undefined && ticketType.remaining_quantity !== null) {
            return total + Math.max(0, ticketType.remaining_quantity);
          }
          // Utiliser available_quantity si disponible
          if (ticketType.available_quantity !== undefined && ticketType.available_quantity !== null) {
            const sold = ticketType.sold_quantity || 0;
            return total + Math.max(0, ticketType.available_quantity - sold);
          }
          // Sinon utiliser l'ancien calcul avec quantity
          const available = (ticketType.quantity || 0);
          const sold = (ticketType.sold_quantity || ticketType.sold || 0);
          return total + Math.max(0, available - sold);
        }, 0);
        
        // Si tous les ticket types ont available_quantity à null (quantité illimitée), retourner une valeur élevée
        const hasLimitedQuantity = ticketTypes.some(t => 
          t.available_quantity !== null && t.available_quantity !== undefined
        );
        
        if (!hasLimitedQuantity) {
          return 1000; // Quantité illimitée - afficher "Réserver"
        }
        
        return totalAvailable;
      }
      return 1000 // Valeur par défaut si pas d'info - permettre la réservation
    })

    const buttonText = computed(() => {
      if (isEventPassed.value) return 'Événement passé'
      if (availableTickets.value === 0) return 'Complet'
      return 'Acheter un ticket'
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
      if (!text || typeof text !== 'string') return ''
      if (text.length <= maxLength) return text
      return text.substr(0, maxLength).trim() + '...'
    }

    const goToEvent = () => {
      router.push(`/events/${props.event.slug}`)
    }

    const goToCheckout = () => {
      console.log('goToCheckout called with event:', props.event)
      console.log('Event slug:', props.event.slug)
      
      // Utiliser le slug s'il existe, sinon créer un slug basé sur l'ID ou le titre
      let slug = props.event.slug
      if (!slug) {
        if (props.event.id) {
          // Mapper les IDs vers des slugs connus
          const idToSlug = {
            1: 'concert-jazz-etoiles',
            2: 'oiseau-rare',
            3: 'festival-arts-culture'
          }
          slug = idToSlug[props.event.id] || `event-${props.event.id}`
        } else if (props.event.title) {
          // Créer un slug basé sur le titre
          slug = props.event.title
            .toLowerCase()
            .replace(/[^a-z0-9\s]/g, '')
            .replace(/\s+/g, '-')
            .slice(0, 50)
        } else {
          console.error('Event slug, ID and title are all missing!', props.event)
          alert('Erreur: Impossible de réserver - informations manquantes')
          return
        }
      }
      
      console.log('Using slug:', slug)
      router.push(`/checkout/${slug}`)
    }

    const handleReserveClick = (event) => {
      console.log('handleReserveClick called')
      event.preventDefault()
      event.stopPropagation()

      if (!canPurchase.value) {
        console.log('Cannot purchase - button disabled')
        return
      }

      goToCheckout()
    }

    const handleImageError = (event) => {
      console.warn('EventCard - Erreur de chargement image pour:', props.event.title)
      console.warn('EventCard - URL qui a échoué:', event?.target?.src)
      imageError.value = true
    }

    return {
      eventImageUrl,
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
      goToEvent,
      goToCheckout,
      handleReserveClick,
      handleImageError
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
  --font-primary: 'Inter', 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
</style>