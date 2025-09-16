<template>
  <div class="home font-primea">
    <!-- Hero Section avec thème Primea -->
    <section class="hero-section relative overflow-hidden bg-primea-gradient min-h-[600px]">
      <!-- Image de fond avec overlay -->
      <div class="absolute inset-0">
        <img 
          src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80" 
          alt="Événement musical" 
          class="w-full h-full object-cover opacity-40" 
        />
        <div class="absolute inset-0 bg-primea-blue/60"></div>
      </div>

      <div class="relative z-10 max-w-7xl mx-auto px-4 py-20 md:py-32">
        <div class="text-center max-w-5xl mx-auto">
          <!-- Logo Primea -->
          <div class="mb-12 animate-fade-in">
            <img src="/images/logo_white.png" alt="Primea" class="h-20 mx-auto mb-6" />
          </div>

          <!-- Titre principal avec thème Primea -->
          <h1 class="text-4xl md:text-6xl font-bold text-white mb-12 leading-tight animate-slide-up">
            DÉCOUVREZ LES MEILLEURS<br>
            <span class="text-primea-yellow">ÉVÉNEMENTS</span><br>
            EN UN CLIC !
          </h1>

          <!-- Barre de recherche avec design Primea -->
          <div class="max-w-2xl mx-auto mb-16 animate-slide-up" style="animation-delay: 0.2s;">
            <form @submit.prevent="searchEvents" class="relative">
              <input 
                type="text"
                v-model="searchQuery"
                placeholder="Rechercher un événement..."
                class="w-full px-8 py-5 rounded-primea-lg text-lg bg-white/95 backdrop-blur-sm text-primea-blue placeholder-gray-500 focus:outline-none focus:bg-white focus:shadow-primea-lg transition-all font-primea font-medium border-2 border-transparent focus:border-primea-yellow"
              />
              <button 
                type="submit"
                class="absolute right-3 top-1/2 transform -translate-y-1/2 bg-primea-yellow text-primea-blue p-3 rounded-primea hover:bg-primea-blue hover:text-white transition-all duration-200 shadow-primea"
              >
                <MagnifyingGlassIcon class="w-6 h-6" />
              </button>
            </form>
          </div>

          <!-- Boutons d'actions -->
          <div class="flex flex-col sm:flex-row gap-6 justify-center items-center animate-slide-up" style="animation-delay: 0.4s;">
            <router-link 
              to="/events"
              class="bg-primea-yellow text-primea-blue px-10 py-4 rounded-primea-lg text-lg font-bold hover:bg-white hover:text-primea-blue transition-all duration-200 shadow-primea-lg transform hover:scale-105"
            >
              Explorer les événements
            </router-link>
            <router-link 
              to="/organizer/register"
              class="bg-transparent border-2 border-primea-yellow text-primea-yellow px-10 py-4 rounded-primea-lg text-lg font-bold hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200"
            >
              Créer un événement
            </router-link>
          </div>
        </div>
      </div>
    </section>
    
    <!-- Section Catégories avec thème Primea -->
    <section class="py-16 bg-gray-50">
      <div class="max-w-7xl mx-auto px-4">
        <!-- Titre de section -->
        <div class="text-center mb-12">
          <h2 class="text-3xl md:text-4xl font-bold text-primea-blue mb-4 font-primea">
            Explorez par catégorie
          </h2>
          <p class="text-lg text-gray-600 font-primea">
            Trouvez l'événement parfait selon vos goûts
          </p>
        </div>

        <!-- Filtres de catégorie avec design Primea -->
        <div class="flex flex-wrap justify-center gap-4 mb-16">
          <button 
            v-for="category in categories" 
            :key="category.id"
            @click="filterByCategory(category.id)"
            :class="[
              'px-8 py-4 rounded-primea-lg text-base font-bold transition-all duration-200 transform hover:scale-105 font-primea shadow-primea',
              selectedCategory === category.id 
                ? 'bg-primea-blue text-white shadow-primea-lg' 
                : 'bg-white text-primea-blue hover:bg-primea-yellow hover:text-primea-blue border-2 border-primea-blue'
            ]"
          >
            {{ category.name }}
          </button>
        </div>

        <!-- Grid d'événements avec design Primea -->
        <div class="max-w-7xl mx-auto">
          <div v-if="loading" class="flex justify-center py-20">
            <div class="animate-spin rounded-full h-16 w-16 border-4 border-primea-blue border-t-primea-yellow"></div>
          </div>

          <div v-else-if="filteredEvents.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <EventCard 
              v-for="event in filteredEvents.slice(0, 6)" 
              :key="event.id || event.slug" 
              :event="event"
              class="transform hover:scale-105 transition-all duration-300 hover:shadow-primea-lg"
            />
          </div>

          <div v-else class="text-center py-20">
            <div class="mb-6">
              <ExclamationTriangleIcon class="w-16 h-16 text-gray-400 mx-auto" />
            </div>
            <p class="text-gray-500 mb-4 font-primea text-lg">Aucun événement trouvé pour cette catégorie.</p>
            <button 
              @click="filterByCategory('all')"
              class="bg-primea-blue text-white px-6 py-3 rounded-primea font-primea font-semibold hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200"
            >
              Voir tous les événements
            </button>
          </div>

          <!-- Bouton voir plus -->
          <div class="text-center mt-16" v-if="filteredEvents.length > 0">
            <router-link 
              to="/events"
              class="bg-primea-yellow text-primea-blue px-12 py-4 rounded-primea-lg text-lg font-bold hover:bg-primea-blue hover:text-white transition-all duration-200 inline-block shadow-primea-lg transform hover:scale-105 font-primea"
            >
              Voir tous les événements
            </router-link>
          </div>
        </div>
      </div>
    </section>
    
    <!-- Section Événements populaires avec thème Primea -->
    <section class="py-16 bg-white">
      <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-12">
          <h2 class="text-3xl md:text-4xl font-bold text-primea-blue mb-4 font-primea">
            Événements populaires
          </h2>
          <p class="text-lg text-gray-600 font-primea">
            Découvrez les événements qui font le buzz
          </p>
        </div>
        
        <div class="max-w-7xl mx-auto">
          <div v-if="loading" class="flex justify-center py-20">
            <div class="animate-spin rounded-full h-16 w-16 border-4 border-primea-blue border-t-primea-yellow"></div>
          </div>

          <div v-else-if="featuredEvents.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <EventCard 
              v-for="event in featuredEvents.slice(0, 3)" 
              :key="event.id || event.slug" 
              :event="event"
              class="transform hover:scale-105 transition-all duration-300 hover:shadow-primea-lg"
            />
          </div>

          <div v-else class="text-center py-20">
            <div class="mb-6">
              <ExclamationTriangleIcon class="w-16 h-16 text-gray-400 mx-auto" />
            </div>
            <p class="text-gray-500 mb-4 font-primea text-lg">Aucun événement populaire pour le moment.</p>
            <router-link 
              to="/events"
              class="bg-primea-blue text-white px-6 py-3 rounded-primea font-primea font-semibold hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200"
            >
              Voir tous les événements
            </router-link>
          </div>
        </div>
        
        <div class="text-center mt-12" v-if="featuredEvents.length > 0">
          <router-link 
            to="/events"
            class="bg-primea-yellow text-primea-blue px-12 py-4 rounded-primea-lg text-lg font-bold hover:bg-primea-blue hover:text-white transition-all duration-200 inline-block shadow-primea-lg transform hover:scale-105 font-primea"
          >
            Voir tous les événements
          </router-link>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useEventsStore } from '../stores/events'
import EventCard from '../components/EventCard.vue'
import { MagnifyingGlassIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

export default {
  name: 'Home',
  components: {
    EventCard,
    MagnifyingGlassIcon,
    ExclamationTriangleIcon
  },
  setup() {
    const router = useRouter()
    const eventsStore = useEventsStore()

    // État réactif
    const searchQuery = ref('')
    const selectedCategory = ref('all')
    const events = ref([])
    const loading = ref(true)

    // Catégories selon la maquette
    const categories = ref([
      { id: 'all', name: 'Tous' },
      { id: 'concerts', name: 'Concerts/Shows' },
      { id: 'cinema', name: 'Cinéma/Théâtre/Conférence/Expo' },
      { id: 'sports', name: 'Sports' }
    ])

    // Événements filtrés
    const filteredEvents = computed(() => {
      if (selectedCategory.value === 'all') {
        return events.value
      }
      
      // Mapper les catégories de la maquette aux vraies catégories
      const categoryMapping = {
        'concerts': ['concert', 'festival'],
        'cinema': ['theater', 'conference', 'cinema'],
        'sports': ['sport']
      }
      
      const realCategories = categoryMapping[selectedCategory.value] || []
      return events.value.filter(event => 
        realCategories.includes(event.category)
      )
    })

    // Événements populaires (featured)
    const featuredEvents = computed(() => {
      return events.value.filter(event => event.is_featured).slice(0, 3)
    })

    // Méthodes
    const loadEvents = async () => {
      try {
        loading.value = true
        const data = await eventsStore.fetchEvents()
        events.value = data.events || []
      } catch (error) {
        console.error('Erreur lors du chargement des événements:', error)
      } finally {
        loading.value = false
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
      selectedCategory.value = categoryId
    }

    // Lifecycle
    onMounted(() => {
      loadEvents()
    })

    return {
      searchQuery,
      selectedCategory,
      events,
      loading,
      categories,
      filteredEvents,
      featuredEvents,
      searchEvents,
      filterByCategory
    }
  }
}
</script>