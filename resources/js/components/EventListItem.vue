<template>
  <article class="card-elegant p-6 flex flex-col md:flex-row gap-6 group hover:shadow-lg transition-all duration-300 cursor-pointer" @click="goToEvent">
    <!-- Image -->
    <div class="md:w-48 h-32 md:h-auto overflow-hidden rounded-lg flex-shrink-0">
      <img 
        v-if="event.image_url" 
        :src="event.image_url" 
        :alt="event.title" 
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
      />
      <div 
        v-else 
        class="w-full h-full bg-gradient-to-br from-blue-100 to-yellow-100 flex items-center justify-center"
      >
        <svg class="w-8 h-8 primea-text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a4 4 0 118 0v4m-4 8a4 4 0 11-8 0 4 4 0 018 0z"/>
        </svg>
      </div>
    </div>

    <!-- Contenu -->
    <div class="flex-1 flex flex-col justify-between">
      <div>
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-2">
          <h3 class="font-bold text-xl primea-text-blue mb-1 md:mb-0">
            <button @click.stop="goToEvent" class="hover:underline text-left">
              {{ event.title }}
            </button>
          </h3>
          <span 
            v-if="event.category" 
            class="bg-yellow-400 primea-text-blue px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wide self-start"
          >
            {{ event.category }}
          </span>
        </div>

        <div class="flex flex-wrap gap-4 mb-3 text-sm text-gray-500">
          <div class="flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a4 4 0 118 0v4m-4 8a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            {{ formatDate(eventDate) }}
          </div>
          <div class="flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ formatTime(eventDate) }}
          </div>
          <div class="flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            {{ event.venue_name }}, {{ event.venue_city }}
          </div>
        </div>

        <p class="text-gray-600 text-sm mb-4">
          {{ truncateText(event.description, 200) }}
        </p>
      </div>

      <div class="flex items-center justify-between">
        <div class="flex items-center gap-6">
          <div class="font-bold text-lg primea-text-blue">
            <span v-if="minPrice > 0">
              À partir de {{ formatPrice(minPrice) }} FCFA
            </span>
            <span v-else class="text-green-600">Gratuit</span>
          </div>
          
          <!-- Places restantes -->
          <div v-if="availableTickets < 20 && availableTickets > 0" class="text-amber-600 text-sm font-medium">
            {{ availableTickets }} place{{ availableTickets > 1 ? 's' : '' }} restante{{ availableTickets > 1 ? 's' : '' }}
          </div>
          <div v-else-if="availableTickets === 0" class="text-red-600 text-sm font-medium">Complet</div>
        </div>

        <button 
          @click.stop="goToEvent"
          :class="[
            'btn-gradient-primary px-6 py-2 rounded-lg',
            { 'opacity-50 cursor-not-allowed': availableTickets === 0 }
          ]"
          :disabled="availableTickets === 0"
        >
          {{ availableTickets === 0 ? 'Complet' : 'Voir détails' }}
        </button>
      </div>
    </div>
  </article>
</template>

<script>
import { computed } from 'vue'
import { useRouter } from 'vue-router'

export default {
  name: 'EventListItem',
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
        return new Date(props.event.schedules[0].start_date)
      }
      return null
    })

    const minPrice = computed(() => {
      if (props.event.ticket_types && props.event.ticket_types.length > 0) {
        return Math.min(...props.event.ticket_types.map(t => t.price))
      }
      return 0
    })

    const availableTickets = computed(() => {
      if (props.event.ticket_types && props.event.ticket_types.length > 0) {
        return props.event.ticket_types.reduce((total, ticketType) => {
          return total + (ticketType.quantity - (ticketType.sold || 0))
        }, 0)
      }
      return 100 // Valeur par défaut si pas d'info
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
      availableTickets,
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
.card-elegant {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
}

.card-elegant:hover {
  transform: translateY(-4px) scale(1.02);
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
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
}

.btn-gradient-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(39, 45, 99, 0.3);
}

.primea-text-blue {
  color: #272d63;
}

.primea-text-gray-400 {
  color: #9ca3af;
}

.flex {
  display: flex;
}

.flex-col {
  flex-direction: column;
}

.flex-1 {
  flex: 1;
}

.flex-shrink-0 {
  flex-shrink: 0;
}

.justify-between {
  justify-content: space-between;
}

.items-center {
  align-items: center;
}

.gap-6 {
  gap: 1.5rem;
}

.gap-4 {
  gap: 1rem;
}

.gap-1 {
  gap: 0.25rem;
}

.rounded-lg {
  border-radius: 0.5rem;
}

.overflow-hidden {
  overflow: hidden;
}

@media (min-width: 768px) {
  .md\:flex-row {
    flex-direction: row;
  }
  
  .md\:w-48 {
    width: 12rem;
  }
  
  .md\:h-auto {
    height: auto;
  }
  
  .md\:mb-0 {
    margin-bottom: 0;
  }
  
  .md\:items-center {
    align-items: center;
  }
  
  .md\:justify-between {
    justify-content: space-between;
  }
}

:root {
  --primea-blue: #272d63;
  --primea-white: #ffffff;
  --primea-blue-dark: #1a1e47;
  --font-primary: 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
</style>