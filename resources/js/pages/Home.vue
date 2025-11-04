<template>
  <div class="home bg-white min-h-screen">
    <!-- Hero Section -->
    <section class="relative min-h-[500px] md:min-h-[600px] flex flex-col justify-between">
      <!-- Background Media (Image or Video) -->
      <div class="absolute inset-0">
        <!-- Video -->
        <video
          v-if="heroBanner && heroBanner.type === 'video'"
          :src="heroBanner.media_url"
          autoplay
          loop
          muted
          class="w-full h-full object-cover"
        ></video>
        <!-- Image -->
        <img
          v-else
          :src="heroBanner?.media_url || 'https://images.unsplash.com/photo-1540575467063-178a50c2df87'"
          :alt="heroBanner?.title || 'Événements'"
          class="w-full h-full object-cover"
        />
        <div class="absolute inset-0 bg-black bg-opacity-60"></div>
      </div>

      <!-- Content Centré -->
      <div class="relative z-10 w-full flex-1 flex items-center justify-center px-4 py-8">
        <div class="w-full max-w-2xl mx-auto text-center">
          <!-- Search Bar (75% de largeur et centré) -->
          <div class="mb-8 flex justify-center">
            <div class="relative w-3/4">
              <input
                type="text"
                v-model="searchQuery"
                @keyup.enter="searchEvents"
                placeholder="| Chercher un événement"
                class="w-full px-6 py-3 rounded-lg text-base bg-white/90 text-gray-800 placeholder-gray-600 focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-500"
              />
              <button
                @click="searchEvents"
                class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-blue-950 text-white p-2 rounded-lg hover:bg-yellow-500 hover:text-blue-950 transition-colors"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
              </button>
            </div>
          </div>

          <!-- Slogan (taille réduite) -->
          <h1 class="text-xl md:text-2xl lg:text-3xl font-bold text-white mb-6 uppercase">
            SE PROCURER UN TICKET<br>
            N'A JAMAIS ÉTÉ AUSSI SIMPLE !
          </h1>

          <!-- CTA Button (réduit) -->
          <div>
            <router-link
              to="/organizer-choice"
              class="inline-block bg-blue-950 text-white px-6 py-2 rounded-lg text-xs font-semibold hover:bg-opacity-90 transition-colors shadow-lg"
            >
              Créateur d'événements
            </router-link>
          </div>
        </div>
      </div>

      <!-- Links en bas -->
      <div class="relative z-10 w-full px-4 pb-6">
        <div class="flex items-center justify-between w-full max-w-md mx-auto text-white text-xs">
          <router-link
            to="/retrieve-ticket"
            class="underline hover:text-yellow-500 transition-colors font-semibold"
          >
            Récupérer mon ticket perdu...
          </router-link>

          <a
            href="https://wa.me/237"
            target="_blank"
            class="flex items-center gap-1.5 bg-transparent border border-gray-400 px-3 py-1.5 rounded-full hover:bg-white/10 transition-colors"
          >
            <span class="text-xs font-semibold">Nous contacter</span>
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
              <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
            </svg>
          </a>
        </div>
      </div>
    </section>

    <!-- Categories Section -->
    <section class="py-6 bg-gray-50">
      <div class="px-4 max-w-7xl mx-auto">
        <h3 class="text-lg font-bold text-gray-800 mb-4 text-center">Filtrer par catégorie</h3>
        <!-- Container with vertical scroll -->
        <div class="max-h-40 overflow-y-auto scrollbar-thin scrollbar-thumb-blue-950 scrollbar-track-gray-200">
          <div class="flex flex-wrap gap-2 justify-center">
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
      </div>
    </section>

    <!-- Events Section -->
    <section class="py-8 md:py-12 bg-white">
      <div class="px-4 max-w-7xl mx-auto">
        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-4 border-blue-950 border-t-yellow-500"></div>
        </div>

        <template v-else>
          <!-- Espace publicitaire -->
          <div class="mb-8 md:mb-12">
            <BannerCarousel
              position="home-top"
              :auto-play="true"
              :interval="4000"
              :show-nav-buttons="true"
            />
          </div>

          <!-- Événements en cours -->
          <div v-if="upcomingEvents.length > 0" class="mb-12">
            <h2 class="text-xl md:text-3xl font-bold text-center text-blue-950 mb-6 md:mb-8">
              Tous les événements en cours
            </h2>
            <!-- Mobile: Métadonnées sur image -->
            <div class="space-y-3 md:hidden">
              <router-link
                v-for="event in upcomingEvents"
                :key="event.id"
                :to="`/events/${event.slug}`"
                class="block bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-shadow"
              >
                <div class="h-40 bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 relative overflow-hidden">
                  <img
                    :src="event.cover_image || 'https://via.placeholder.com/400x240'"
                    :alt="event.title"
                    class="w-full h-full object-cover"
                  />
                  <div class="absolute inset-0 bg-blue-900/50"></div>

                  <!-- Contenu sur l'image -->
                  <div class="absolute inset-0 p-3 text-white flex flex-col justify-between">
                    <div>
                      <div class="bg-yellow-500/20 backdrop-blur-sm rounded-lg px-2 py-0.5 mb-1 text-yellow-500 font-bold uppercase text-xs inline-block">
                        {{ event.category?.name || 'ÉVÉNEMENT' }}
                      </div>
                      <h3 class="font-bold text-base leading-tight line-clamp-2">
                        {{ event.title }}
                      </h3>
                    </div>

                    <div>
                      <div class="flex items-end justify-between">
                        <div>
                          <p class="text-xs leading-tight mb-1">
                            {{ formatDate(event.event_date) }}
                          </p>
                          <p class="text-xs text-gray-200 leading-tight">
                            {{ event.venue?.name || 'Lieu à définir' }}
                          </p>
                        </div>
                        <router-link
                          :to="`/checkout/${event.slug}`"
                          class="bg-yellow-500 text-blue-950 px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-yellow-400 transition-colors flex items-center gap-1"
                          @click.stop
                        >
                          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                          </svg>
                          Prendre un ticket
                        </router-link>
                      </div>
                    </div>
                  </div>
                </div>
              </router-link>
            </div>

            <!-- Desktop: Style classique -->
            <div class="hidden md:grid md:grid-cols-2 lg:grid-cols-3 md:gap-6">
              <router-link
                v-for="event in upcomingEvents"
                :key="event.id"
                :to="`/events/${event.slug}`"
                class="block bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-shadow"
              >
                <div class="relative pb-[60%]">
                  <img
                    :src="event.cover_image || 'https://via.placeholder.com/400x240'"
                    :alt="event.title"
                    class="absolute inset-0 w-full h-full object-cover"
                  />
                </div>
                <div class="p-4">
                  <h3 class="font-bold text-blue-950 text-base line-clamp-2 mb-2">
                    {{ event.title }}
                  </h3>
                  <p class="text-sm text-gray-600 mb-1">
                    {{ formatDate(event.event_date) }}
                  </p>
                  <p class="text-sm text-gray-500">
                    {{ event.venue?.name || 'Lieu à définir' }}
                  </p>
                </div>
              </router-link>
            </div>
          </div>

          <!-- Événements passés -->
          <div v-if="pastEvents.length > 0" class="mb-8">
            <h2 class="text-xl md:text-3xl font-bold text-center text-gray-600 mb-6 md:mb-8">
              Événements passés
            </h2>
            <!-- Mobile: Métadonnées sur image -->
            <div class="space-y-3 md:hidden opacity-75">
              <router-link
                v-for="event in pastEvents"
                :key="event.id"
                :to="`/events/${event.slug}`"
                class="block bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-shadow grayscale"
              >
                <div class="h-40 bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 relative overflow-hidden">
                  <img
                    :src="event.cover_image || 'https://via.placeholder.com/400x240'"
                    :alt="event.title"
                    class="w-full h-full object-cover"
                  />
                  <div class="absolute inset-0 bg-blue-900/50"></div>
                  <div class="absolute top-2 right-2 bg-gray-600 text-white px-2 py-1 rounded-lg text-xs font-semibold">
                    Terminé
                  </div>

                  <!-- Contenu sur l'image -->
                  <div class="absolute inset-0 p-3 text-white flex flex-col justify-between">
                    <div>
                      <div class="bg-yellow-500/20 backdrop-blur-sm rounded-lg px-2 py-0.5 mb-1 text-yellow-500 font-bold uppercase text-xs inline-block">
                        {{ event.category?.name || 'ÉVÉNEMENT' }}
                      </div>
                      <h3 class="font-bold text-base leading-tight line-clamp-2">
                        {{ event.title }}
                      </h3>
                    </div>

                    <div>
                      <p class="text-xs leading-tight mb-1">
                        {{ formatDate(event.event_date) }}
                      </p>
                      <p class="text-xs text-gray-200 leading-tight">
                        {{ event.venue?.name || 'Lieu à définir' }}
                      </p>
                    </div>
                  </div>
                </div>
              </router-link>
            </div>

            <!-- Desktop: Style classique -->
            <div class="hidden md:grid md:grid-cols-2 lg:grid-cols-3 md:gap-6 opacity-75">
              <router-link
                v-for="event in pastEvents"
                :key="event.id"
                :to="`/events/${event.slug}`"
                class="block bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-shadow"
              >
                <div class="relative pb-[60%]">
                  <img
                    :src="event.cover_image || 'https://via.placeholder.com/400x240'"
                    :alt="event.title"
                    class="absolute inset-0 w-full h-full object-cover grayscale"
                  />
                  <div class="absolute top-2 right-2 bg-gray-600 text-white px-3 py-1 rounded-full text-xs font-semibold">
                    Terminé
                  </div>
                </div>
                <div class="p-4">
                  <h3 class="font-bold text-gray-700 text-base line-clamp-2 mb-2">
                    {{ event.title }}
                  </h3>
                  <p class="text-sm text-gray-500 mb-1">
                    {{ formatDate(event.event_date) }}
                  </p>
                  <p class="text-sm text-gray-400">
                    {{ event.venue?.name || 'Lieu à définir' }}
                  </p>
                </div>
              </router-link>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="upcomingEvents.length === 0 && pastEvents.length === 0" class="text-center py-12">
            <p class="text-gray-500 mb-4">Aucun événement disponible pour le moment.</p>
            <button
              @click="filterByCategory('all')"
              class="bg-blue-950 text-white px-6 py-3 rounded-lg hover:bg-yellow-500 hover:text-blue-950 transition-colors"
            >
              Voir tous les événements
            </button>
          </div>
        </template>
      </div>
    </section>

    <!-- Bannières Publicitaires -->
    <section class="py-8 md:py-12 bg-gray-50">
      <div class="px-4 max-w-6xl mx-auto">
        <BannerCarousel
          position="home"
          :auto-play="true"
          :interval="5000"
          :show-nav-buttons="true"
        />
      </div>
    </section>

  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import BannerCarousel from '../components/BannerCarousel.vue'

export default {
  name: 'Home',
  components: {
    BannerCarousel
  },
  setup() {
    const router = useRouter()

    const searchQuery = ref('')
    const selectedCategory = ref('all')
    const events = ref([])
    const categories = ref([])
    const loading = ref(true)
    const heroBanner = ref(null)

    // Filtered events
    const filteredEvents = computed(() => {
      if (selectedCategory.value === 'all') {
        return events.value
      }
      return events.value.filter(event => {
        const eventCategoryId = event.category?.id || event.category_id
        return eventCategoryId === parseInt(selectedCategory.value)
      })
    })

    // Séparer les événements en cours des événements passés
    const upcomingEvents = computed(() => {
      const now = new Date()
      const upcoming = filteredEvents.value.filter(event => {
        if (!event.event_date) return true // Si pas de date, considérer comme à venir
        const eventDate = new Date(event.event_date)
        return eventDate >= now
      })

      // Trier par popularité (du plus au moins populaire)
      // Calculer la popularité basée sur les tickets vendus
      return upcoming.sort((a, b) => {
        const getPopularity = (event) => {
          // Utiliser sold_quantity ou tickets_sold ou calculer à partir des ticket_types
          if (event.sold_quantity !== undefined) {
            return event.sold_quantity
          }
          if (event.tickets_sold !== undefined) {
            return event.tickets_sold
          }
          // Calculer à partir des types de tickets
          if (event.ticket_types && Array.isArray(event.ticket_types)) {
            return event.ticket_types.reduce((total, type) => {
              return total + (type.sold_quantity || type.sold || 0)
            }, 0)
          }
          return 0
        }

        return getPopularity(b) - getPopularity(a) // Ordre décroissant
      })
    })

    const pastEvents = computed(() => {
      const now = new Date()
      return filteredEvents.value.filter(event => {
        if (!event.event_date) return false // Si pas de date, ne pas mettre dans passés
        const eventDate = new Date(event.event_date)
        return eventDate < now
      })
    })

    // Methods
    const loadEvents = async () => {
      try {
        loading.value = true
        const response = await fetch('/api/client/events', {
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          }
        })

        if (!response.ok) throw new Error('Erreur de chargement')

        const data = await response.json()
        events.value = data.events || data.data || []

        // Debug: Afficher le premier événement pour vérifier le format des données
        if (events.value.length > 0) {
          console.log('Premier événement chargé:', events.value[0])
        }
      } catch (error) {
        console.error('Erreur:', error)
        events.value = []
      } finally {
        loading.value = false
      }
    }

    const loadCategories = async () => {
      try {
        const response = await fetch('/api/client/categories', {
          headers: { 'Accept': 'application/json' }
        })

        if (!response.ok) throw new Error('Erreur de chargement')

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
        console.error('Erreur:', error)
        categories.value = [
          { id: 'all', name: 'Tous' },
          { id: '1', name: 'Concerts/Shows' },
          { id: '2', name: 'Cinéma/Théâtre/Conférence/Expo' },
          { id: '3', name: 'Sports' }
        ]
      }
    }

    const loadHeroBanner = async () => {
      try {
        const response = await fetch('/api/hero-banners/active', {
          headers: { 'Accept': 'application/json' }
        })

        if (response.ok) {
          const data = await response.json()
          if (data.success && data.data) {
            heroBanner.value = data.data
          }
        } else if (response.status !== 404) {
          // Logger uniquement si ce n'est pas une 404 (pas de hero banner)
          console.error('Erreur lors du chargement du hero banner:', response.status)
        }
        // Si 404, c'est normal, on utilise l'image par défaut en silence
      } catch (error) {
        console.error('Erreur lors du chargement du hero banner:', error)
        // En cas d'erreur, on garde heroBanner à null pour utiliser l'image par défaut
      }
    }

    const searchEvents = () => {
      if (searchQuery.value.trim()) {
        router.push({
          path: '/events',
          query: { search: searchQuery.value.trim() }
        })
      } else {
        router.push('/events')
      }
    }

    const filterByCategory = (categoryId) => {
      selectedCategory.value = categoryId.toString()
    }

    const formatDate = (dateString) => {
      if (!dateString) {
        return 'Date à confirmer'
      }

      try {
        const date = new Date(dateString)

        // Vérifier si la date est valide
        if (isNaN(date.getTime())) {
          return 'Date à confirmer'
        }

        return date.toLocaleDateString('fr-FR', {
          weekday: 'long',
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        })
      } catch (error) {
        console.error('Erreur de formatage de date:', error)
        return 'Date à confirmer'
      }
    }

    // Lifecycle
    onMounted(() => {
      loadEvents()
      loadCategories()
      loadHeroBanner()
    })

    return {
      searchQuery,
      selectedCategory,
      events,
      categories,
      loading,
      heroBanner,
      filteredEvents,
      upcomingEvents,
      pastEvents,
      searchEvents,
      filterByCategory,
      formatDate
    }
  }
}
</script>

<style scoped>
.home {
  font-family: 'Inter', 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Custom scrollbar for categories */
.scrollbar-thin::-webkit-scrollbar {
  width: 6px;
}

.scrollbar-thumb-blue-950::-webkit-scrollbar-thumb {
  background-color: #172554;
  border-radius: 3px;
}

.scrollbar-track-gray-200::-webkit-scrollbar-track {
  background-color: #e5e7eb;
  border-radius: 3px;
}

/* Firefox scrollbar */
.scrollbar-thin {
  scrollbar-width: thin;
  scrollbar-color: #172554 #e5e7eb;
}
</style>
