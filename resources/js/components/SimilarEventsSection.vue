<template>
  <div class="similar-events-section">
    <h3 class="text-2xl font-bold mb-6">Événements similaires</h3>
    
    <div v-if="events && events.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div 
        v-for="event in events" 
        :key="event.id"
        class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow cursor-pointer"
        @click="goToEvent(event.id)"
      >
        <div class="aspect-w-16 aspect-h-9">
          <img 
            v-if="event.image_url" 
            :src="event.image_url" 
            :alt="event.title"
            class="w-full h-48 object-cover"
          />
          <div v-else class="w-full h-48 bg-gradient-to-br from-primea-blue to-blue-800 flex items-center justify-center">
            <span class="text-white text-lg font-medium">{{ event.title.charAt(0) }}</span>
          </div>
        </div>
        
        <div class="p-4">
          <h4 class="font-semibold text-gray-900 mb-2 line-clamp-2">{{ event.title }}</h4>
          <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ event.description }}</p>
          
          <div class="flex items-center justify-between">
            <div class="text-sm text-gray-500">
              {{ formatDate(event.start_date) }}
            </div>
            <div class="text-primea-blue font-semibold">
              À partir de {{ event.min_price }}€
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div v-else class="text-center py-12 text-gray-600">
      Aucun événement similaire trouvé
    </div>
  </div>
</template>

<script>
import { useRouter } from 'vue-router'

export default {
  name: 'SimilarEventsSection',
  props: {
    events: {
      type: Array,
      default: () => []
    }
  },
  setup() {
    const router = useRouter()

    const goToEvent = (eventId) => {
      router.push(`/events/${eventId}`)
    }

    const formatDate = (date) => {
      return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    }

    return {
      goToEvent,
      formatDate
    }
  }
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>