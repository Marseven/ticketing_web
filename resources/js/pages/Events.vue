<template>
  <div class="events-page min-h-screen bg-gray-50 font-primea">
    <!-- Desktop/Tablet Layout -->
    <div class="hidden md:block">
      <div class="container mx-auto px-4 py-12">
        <!-- Barre de recherche Desktop -->
        <div class="max-w-2xl mx-auto mb-12">
          <form @submit.prevent="performSearch" class="relative">
            <input
              type="text"
              v-model="searchQuery"
              placeholder="Rechercher un événement par titre, lieu, organisateur..."
              class="w-full px-6 py-4 rounded-primea-lg text-lg bg-white text-primea-blue placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primea-yellow shadow-primea border-2 border-transparent focus:border-primea-yellow transition-all"
            />
            <button
              type="submit"
              class="absolute right-3 top-1/2 transform -translate-y-1/2 bg-primea-yellow text-primea-blue p-3 rounded-primea hover:bg-primea-blue hover:text-white transition-all duration-200 shadow-primea"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
              </svg>
            </button>
          </form>
          <!-- Affichage du texte recherché -->
          <div v-if="searchQuery && searchQuery.trim()" class="mt-4 text-center">
            <p class="text-gray-600">
              Résultats pour: <span class="font-bold text-primea-blue">"{{ searchQuery }}"</span>
              <button
                @click="clearSearch"
                class="ml-2 text-primea-yellow hover:text-primea-blue font-semibold"
              >
                ✕ Effacer
              </button>
            </p>
          </div>
        </div>

        <!-- Section Filtres Desktop -->
        <div class="mb-12">
          <h2 class="text-2xl font-bold text-primea-blue text-center mb-8">
            Filtrer par catégorie
          </h2>
          <div class="flex flex-wrap justify-center gap-4">
            <button
              v-for="category in categories"
              :key="category.id"
              @click="filterByCategory(category.id)"
              :class="[
                'px-10 py-4 rounded-primea-lg text-lg font-bold transition-all duration-200 transform hover:scale-105 shadow-primea border-2',
                selectedCategory === category.id
                  ? 'bg-primea-blue text-white border-primea-blue shadow-primea-lg scale-105'
                  : 'bg-white text-primea-blue border-primea-blue hover:bg-primea-blue hover:text-white'
              ]"
            >
              {{ category.name }}
            </button>
          </div>
        </div>

        <!-- Grid Events Desktop -->
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-8 mb-16">
          <EventCard 
            v-for="event in filteredEvents" 
            :key="event.id" 
            :event="event"
            class="transform hover:scale-105 transition-all duration-300"
          />
        </div>

        <!-- Message si aucun événement Desktop -->
        <div v-if="filteredEvents.length === 0 && !loading" class="text-center py-20">
          <div class="bg-white rounded-primea-xl shadow-primea-lg p-12">
            <div class="mb-6">
              <ExclamationTriangleIcon class="w-16 h-16 text-primea-blue/40 mx-auto" />
            </div>
            <div v-if="eventsStore.error">
              <p class="text-red-600 mb-4 text-xl font-semibold">⚠️ Erreur de connexion</p>
              <p class="text-gray-600 mb-6">{{ eventsStore.error }}</p>
              <button 
                @click="loadEvents"
                class="bg-primea-blue text-white px-8 py-3 rounded-primea-lg font-bold hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200 shadow-primea"
              >
                Réessayer
              </button>
            </div>
            <div v-else>
              <p class="text-primea-blue mb-6 text-xl font-semibold">Aucun événement trouvé pour cette catégorie.</p>
              <button 
                @click="filterByCategory('all')"
                class="bg-primea-yellow text-primea-blue px-8 py-3 rounded-primea-lg font-bold hover:bg-primea-blue hover:text-white transition-all duration-200 shadow-primea"
              >
                Voir tous les événements
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Mobile Layout -->
    <div class="md:hidden">
      <div class="max-w-md mx-auto px-4 py-8">

        <!-- Barre de recherche Mobile -->
        <div class="mb-6">
          <form @submit.prevent="performSearch" class="relative">
            <input
              type="text"
              v-model="searchQuery"
              placeholder="Rechercher un événement..."
              class="w-full px-4 py-3 rounded-primea-lg bg-white text-primea-blue placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primea-yellow"
            />
            <button
              type="submit"
              class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-primea-yellow text-primea-blue p-2 rounded-primea"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
              </svg>
            </button>
          </form>
          <!-- Affichage du texte recherché mobile -->
          <div v-if="searchQuery && searchQuery.trim()" class="mt-3 text-center">
            <p class="text-gray-800 text-sm">
              Résultats pour: <span class="font-bold text-primea-blue">"{{ searchQuery }}"</span>
              <button
                @click="clearSearch"
                class="ml-2 text-primea-yellow hover:text-primea-blue font-semibold"
              >
                ✕
              </button>
            </p>
          </div>
        </div>

        <!-- Filtres de catégorie Mobile -->
        <div class="mb-6">
          <h3 class="text-gray-800 text-sm font-bold mb-3">Catégories</h3>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="category in categories"
              :key="category.id"
              @click="filterByCategory(category.id)"
              :class="[
                'px-4 py-2 rounded-primea text-sm font-bold transition-all duration-200',
                selectedCategory === category.id
                  ? 'bg-primea-yellow text-primea-blue'
                  : 'bg-white text-primea-blue border-2 border-primea-blue hover:bg-primea-yellow hover:text-primea-blue'
              ]"
            >
              {{ category.name }}
            </button>
          </div>
        </div>

        <!-- Liste des événements Mobile selon maquette -->
        <div class="space-y-4 mb-8">
          <div
            v-for="event in filteredEvents" 
            :key="event.id"
            @click="goToEvent(event)"
            class="bg-white rounded-primea-lg shadow-primea overflow-hidden cursor-pointer hover:shadow-primea-lg transition-all duration-300 transform hover:scale-105"
          >
            <!-- Image de l'événement avec overlay comme dans la maquette -->
            <div class="h-48 bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 relative overflow-hidden">
              <img
                v-if="event.image || event.image_url || event.image_file"
                :src="event.image_url || event.image || event.image_file"
                :alt="event.title"
                class="w-full h-full object-cover"
              />
              <div class="absolute inset-0 bg-primea-blue/40"></div>
              
              <!-- Contenu sur l'image selon maquette -->
              <div class="absolute inset-0 p-4 text-white">
                <div class="flex justify-between items-start mb-4">
                  <div class="text-xs">
                    <div class="bg-primea-yellow/20 backdrop-blur-sm rounded-primea px-2 py-1 mb-1 text-primea-yellow font-bold uppercase">
                      {{ event.category?.name || event.category_name || 'ÉVÉNEMENT' }}
                    </div>
                    <div class="font-bold">{{ event.title }}</div>
                  </div>
                  <div class="text-right text-xs">
                    <div class="bg-primea-yellow text-primea-blue px-2 py-1 rounded-primea mb-1 font-bold">
                      {{ formatEventDate(event.event_date || event.date) }}
                    </div>
                    <div v-if="event.min_age" class="bg-red-600 text-white px-2 py-1 rounded-primea font-bold">{{ event.min_age }}+</div>
                  </div>
                </div>

                <!-- Prix selon la maquette -->
                <div class="absolute bottom-4 left-4 right-4">
                  <div class="flex justify-between items-end">
                    <div class="text-xs">
                      <div class="flex space-x-2 mb-2">
                        <div v-if="event.min_price && event.min_price > 0" class="bg-green-500 text-white px-2 py-1 rounded-primea text-xs font-bold">
                          {{ formatPrice(event.min_price) }} XAF
                        </div>
                        <div v-if="event.max_price && event.max_price > 0 && event.max_price !== event.min_price" class="bg-primea-blue text-white px-2 py-1 rounded-primea text-xs font-bold">
                          {{ formatPrice(event.max_price) }} XAF
                        </div>
                      </div>
                      <div class="text-xs text-gray-200">{{ event.venue?.name || event.venue_name || 'Lieu à confirmer' }}</div>
                    </div>
                    <div class="text-right">
                      <div class="text-xs text-gray-200">Organisé par</div>
                      <div class="text-xs font-bold text-primea-yellow">{{ event.organizer?.name || 'Organisateur' }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Message si aucun événement ou erreur Mobile -->
        <div v-if="filteredEvents.length === 0 && !loading" class="text-center py-12">
          <div v-if="eventsStore.error" class="text-red-400 mb-4">
            <div class="text-lg font-semibold mb-2">⚠️ Erreur de connexion</div>
            <div class="text-sm">{{ eventsStore.error }}</div>
          </div>
          <div v-else class="text-white/80 mb-4">Aucun événement disponible pour le moment.</div>
          <router-link
            to="/"
            class="text-primea-yellow hover:text-white font-medium"
          >
            ← Retourner à l'accueil
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useEventsStore } from '../stores/events'
import EventCard from '../components/EventCard.vue'
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

export default {
  name: 'Events',
  components: {
    EventCard,
    ExclamationTriangleIcon
  },
  setup() {
    const router = useRouter()
    const route = useRoute()
    const eventsStore = useEventsStore()

    // État des événements
    const events = ref([])
    const loading = ref(true)
    const selectedCategory = ref('all')
    const categories = ref([])
    const searchQuery = ref('')

    // Événements filtrés
    const filteredEvents = computed(() => {
      let filtered = events.value

      // Filtrer par catégorie
      if (selectedCategory.value !== 'all') {
        filtered = filtered.filter(event => {
          const eventCategoryId = event.category?.id || event.category_id
          return eventCategoryId === parseInt(selectedCategory.value)
        })
      }

      // Filtrer par recherche
      if (searchQuery.value && searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase().trim()
        filtered = filtered.filter(event => {
          // Recherche dans le titre
          const titleMatch = event.title?.toLowerCase().includes(query)
          // Recherche dans la description
          const descriptionMatch = event.description?.toLowerCase().includes(query)
          // Recherche dans le lieu (venue peut être un objet ou une chaîne)
          const venueMatch = (event.venue_name?.toLowerCase().includes(query)) ||
                            (event.venue?.name?.toLowerCase().includes(query)) ||
                            (typeof event.venue === 'string' && event.venue?.toLowerCase().includes(query))
          // Recherche dans l'organisateur (organizer peut être un objet ou une chaîne)
          const organizerMatch = (event.organizer?.name?.toLowerCase().includes(query)) ||
                                 (typeof event.organizer === 'string' && event.organizer?.toLowerCase().includes(query))
          // Recherche dans la catégorie
          const categoryMatch = event.category?.name?.toLowerCase().includes(query) ||
                               event.category_name?.toLowerCase().includes(query)

          return titleMatch || descriptionMatch || venueMatch || organizerMatch || categoryMatch
        })
      }

      return filtered
    })


    // Méthodes
    const loadEvents = async () => {
      try {
        loading.value = true
        const data = await eventsStore.fetchEvents()
        events.value = data.events || []
      } catch (error) {
        console.error('Erreur lors du chargement des événements:', error)
        events.value = []
      } finally {
        loading.value = false
      }
    }

    const loadCategories = async () => {
      try {
        const response = await fetch('/api/client/categories', {
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        })

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`)
        }

        const data = await response.json()
        
        if (data.success && data.categories) {
          categories.value = [
            { id: 'all', name: 'Tous' },
            ...data.categories.map(cat => ({
              id: cat.id,
              name: cat.name,
              slug: cat.slug
            }))
          ]
        }
      } catch (error) {
        console.error('Erreur lors du chargement des catégories:', error)
        categories.value = [
          { id: 'all', name: 'Tous' },
          { id: 1, name: 'Musique' },
          { id: 2, name: 'Culture' },
          { id: 3, name: 'Gastronomie' },
          { id: 4, name: 'Sport' },
          { id: 5, name: 'Business' }
        ]
      }
    }

    const formatEventDate = (dateString) => {
      if (!dateString) return '--'

      try {
        const date = new Date(dateString)
        if (isNaN(date.getTime())) return '--'
        return date.getDate().toString().padStart(2, '0')
      } catch (error) {
        return '--'
      }
    }

    const formatPrice = (price) => {
      if (!price || price === 0) return 'Gratuit'
      return new Intl.NumberFormat('fr-FR').format(price)
    }

    const goToEvent = (event) => {
      router.push(`/events/${event.slug}`)
    }

    const filterByCategory = (categoryId) => {
      selectedCategory.value = categoryId.toString()
    }

    const performSearch = () => {
      // La recherche est automatiquement appliquée via le computed filteredEvents
      // On peut simplement mettre à jour l'URL pour refléter la recherche
      if (searchQuery.value && searchQuery.value.trim()) {
        router.push({
          path: '/events',
          query: { search: searchQuery.value.trim() }
        })
      }
    }

    const clearSearch = () => {
      searchQuery.value = ''
      router.push({ path: '/events' })
    }

    // Lifecycle
    onMounted(() => {
      // Récupérer les paramètres de l'URL
      if (route.query.search) {
        searchQuery.value = route.query.search
      }

      if (route.query.category) {
        selectedCategory.value = route.query.category.toString()
      }

      loadEvents()
      loadCategories()
    })

    // Watcher pour surveiller les changements dans l'URL
    watch(() => route.query, (newQuery) => {
      if (newQuery.category) {
        selectedCategory.value = newQuery.category.toString()
      } else {
        selectedCategory.value = 'all'
      }

      if (newQuery.search) {
        searchQuery.value = newQuery.search
      } else if (!newQuery.search && searchQuery.value) {
        searchQuery.value = ''
      }
    })

    return {
      events,
      loading,
      selectedCategory,
      categories,
      searchQuery,
      filteredEvents,
      formatEventDate,
      formatPrice,
      goToEvent,
      filterByCategory,
      performSearch,
      clearSearch,
      loadEvents,
      loadCategories,
      eventsStore
    }
  }
}
</script>

<style scoped>
/* Variables CSS */
:root {
  --primea-blue: #272d63;
  --primea-yellow: #fab511;
  --primea-white: #ffffff;
}

/* Background */
.bg-primea-gradient {
  background: linear-gradient(135deg, var(--primea-blue) 0%, #1a1f4a 100%);
}

/* Espacement des éléments */
.space-y-4 > * + * {
  margin-top: 1rem;
}

/* Animations */
.transform {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.hover\:scale-105:hover {
  transform: scale(1.05);
}

.transition-all {
  transition: all 0.2s ease-in-out;
}

.transition-colors {
  transition: color 0.2s ease-in-out;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .container {
    padding-left: 1rem;
    padding-right: 1rem;
  }
}

/* Backdrop blur support */
.backdrop-blur-sm {
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
}

/* Custom shadows */
.shadow-primea {
  box-shadow: 0 4px 20px rgba(39, 45, 99, 0.1);
}

.shadow-primea-lg {
  box-shadow: 0 8px 30px rgba(39, 45, 99, 0.15);
}

/* Rounded corners */
.rounded-primea {
  border-radius: 12px;
}

.rounded-primea-lg {
  border-radius: 16px;
}

.rounded-primea-xl {
  border-radius: 20px;
}
</style>