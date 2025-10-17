<template>
  <div class="home bg-white min-h-screen">
    <!-- Hero Section -->
    <section class="relative min-h-[500px] md:min-h-[600px] flex items-center">
      <!-- Background Image -->
      <div class="absolute inset-0">
        <img
          src="https://images.unsplash.com/photo-1540575467063-178a50c2df87"
          alt="Événements"
          class="w-full h-full object-cover"
        />
        <div class="absolute inset-0 bg-black bg-opacity-60"></div>
      </div>

      <!-- Content -->
      <div class="relative z-10 w-full px-4 py-12 md:py-20">
        <div class="max-w-4xl mx-auto text-center">
          <!-- Search Bar -->
          <div class="mb-8">
            <div class="relative">
              <input
                type="text"
                v-model="searchQuery"
                @keyup.enter="searchEvents"
                placeholder="| Chercher un événement"
                class="w-full px-6 py-4 rounded-lg text-base bg-white/90 text-gray-800 placeholder-gray-600 focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-500"
              />
              <button
                @click="searchEvents"
                class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-blue-950 text-white p-3 rounded-lg hover:bg-yellow-500 hover:text-blue-950 transition-colors"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
              </button>
            </div>
          </div>

          <!-- Slogan -->
          <h1 class="text-2xl md:text-4xl lg:text-5xl font-bold text-white mb-8 leading-tight uppercase">
            SE PROCURER UN TICKET<br>
            N'A JAMAIS ÉTÉ AUSSI SIMPLE !
          </h1>

          <!-- CTA Button -->
          <div class="mb-6">
            <router-link
              to="/organizer/login"
              class="inline-block bg-blue-950 text-white px-8 py-4 rounded-lg text-base font-bold hover:bg-yellow-500 hover:text-blue-950 transition-colors shadow-lg"
            >
              Créateur d'événements
            </router-link>
          </div>

          <!-- Links -->
          <div class="flex flex-col sm:flex-row items-center justify-center gap-4 text-white">
            <router-link
              to="/ticket-retrieve"
              class="text-sm underline hover:text-yellow-500 transition-colors"
            >
              Récupérer mon ticket perdu...
            </router-link>

            <a
              href="https://wa.me/237"
              target="_blank"
              class="flex items-center gap-2 bg-white/10 backdrop-blur-sm px-4 py-2 rounded-lg hover:bg-white/20 transition-colors"
            >
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
              </svg>
              <span class="text-sm">Nous contacter</span>
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- Categories Section -->
    <section class="py-6 bg-gray-50">
      <div class="px-4 max-w-7xl mx-auto">
        <h3 class="text-lg font-bold text-blue-950 mb-4 text-center">Filtrer par catégorie</h3>
        <!-- Container with vertical scroll -->
        <div class="max-h-40 overflow-y-auto scrollbar-thin scrollbar-thumb-blue-950 scrollbar-track-gray-200">
          <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-4 pr-2">
            <button
              v-for="category in categories"
              :key="category.id"
              @click="filterByCategory(category.id)"
              :class="[
                'py-4 px-3 rounded-lg text-xs md:text-sm font-bold transition-all',
                selectedCategory === category.id
                  ? 'bg-blue-950 text-white shadow-lg scale-105'
                  : 'bg-white text-blue-950 border-2 border-blue-950 hover:bg-yellow-500 hover:border-yellow-500'
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
        <h2 class="text-xl md:text-3xl font-bold text-center text-blue-950 mb-6 md:mb-8">
          Tous les événements en cours
        </h2>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-4 border-blue-950 border-t-yellow-500"></div>
        </div>

        <!-- Events Grid -->
        <div v-else-if="filteredEvents.length > 0" class="space-y-4 md:space-y-0 md:grid md:grid-cols-2 lg:grid-cols-3 md:gap-6">
          <router-link
            v-for="event in filteredEvents"
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
              <h3 class="font-bold text-blue-950 text-sm md:text-base line-clamp-2 mb-2">
                {{ event.title }}
              </h3>
              <p class="text-xs text-gray-600 mb-1">
                {{ formatDate(event.event_date) }}
              </p>
              <p class="text-xs text-gray-500">
                {{ event.venue?.name || 'Lieu à définir' }}
              </p>
            </div>
          </router-link>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-12">
          <p class="text-gray-500 mb-4">Aucun événement disponible pour le moment.</p>
          <button
            @click="filterByCategory('all')"
            class="bg-blue-950 text-white px-6 py-3 rounded-lg hover:bg-yellow-500 hover:text-blue-950 transition-colors"
          >
            Voir tous les événements
          </button>
        </div>
      </div>
    </section>

    <!-- Espace Pub -->
    <section class="py-12 bg-white w-full flex justify-center">
      <div
        class="w-96 h-32 bg-zinc-300 rounded-[10px] flex flex-col items-center justify-center cursor-pointer hover:opacity-90 transition-opacity bg-cover bg-center relative overflow-hidden"
        :style="adBannerStyle"
      >
        <!-- Overlay pour assurer la lisibilité du texte avec image de fond -->
        <div v-if="adBannerImage" class="absolute inset-0 bg-black bg-opacity-30 rounded-[10px]"></div>

        <!-- Contenu de la bannière -->
        <div class="relative z-10 text-center">
          <h2 class="text-2xl font-bold mb-2" :class="adBannerImage ? 'text-white' : 'text-gray-600'">
            ESPACE PUB
          </h2>
          <a href="#" class="text-sm hover:underline" :class="adBannerImage ? 'text-white' : 'text-blue-600'">
            En savoir plus...
          </a>
        </div>
      </div>
    </section>

  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

export default {
  name: 'Home',
  setup() {
    const router = useRouter()

    const searchQuery = ref('')
    const selectedCategory = ref('all')
    const events = ref([])
    const categories = ref([])
    const loading = ref(true)
    // Image de fond pour la bannière pub (configurable)
    // Pour configurer: adBannerImage.value = 'https://example.com/banner.jpg'
    // Ou charger depuis une API/base de données
    const adBannerImage = ref('')

    // Exemple: définir une image par défaut (décommenter pour activer)
    // adBannerImage.value = 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30'

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

    // Style pour la bannière pub avec image de fond configurable
    const adBannerStyle = computed(() => {
      if (adBannerImage.value) {
        return {
          backgroundImage: `url(${adBannerImage.value})`,
          backgroundSize: 'cover',
          backgroundPosition: 'center'
        }
      }
      return {}
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
    })

    return {
      searchQuery,
      selectedCategory,
      events,
      categories,
      loading,
      filteredEvents,
      adBannerImage,
      adBannerStyle,
      searchEvents,
      filterByCategory,
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
