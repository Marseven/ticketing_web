<template>
  <div class="events-page min-h-screen bg-gray-50 font-primea">
    <!-- Desktop/Tablet Layout -->
    <div class="hidden md:block">
      <!-- Hero Section avec fond blanc -->
      <div class="bg-white py-16 mb-12 shadow-sm">
        <div class="container mx-auto px-4 text-center">
          <!-- Logo Primea -->
          <div class="mb-8">
            <img src="/images/logo.png" alt="Primea" class="h-16 mx-auto mb-4" />
          </div>
          
          <!-- Titre principal -->
          <h1 class="text-5xl md:text-6xl font-bold text-primea-blue mb-6 leading-tight">
            Découvrez nos 
            <span class="text-primea-yellow">événements</span>
          </h1>
          <p class="text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed">
            Explorez notre sélection d'événements exceptionnels et réservez vos places en quelques clics
          </p>
        </div>
      </div>

      <div class="container mx-auto px-4">
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
        
        <!-- En-tête Mobile selon maquette event.png -->
        <div class="flex items-center justify-between mb-8">
          <button @click="goBack" class="text-white hover:text-primea-yellow">
            <ChevronLeftIcon class="w-6 h-6" />
          </button>
          
          <div class="text-center">
            <img src="/images/logo_white.png" alt="Primea" class="h-8 mx-auto mb-2" />
            <div class="text-center">
              <div class="text-lg font-bold text-primea-yellow">La Billetterie</div>
              <div class="text-xs text-gray-300">Simple, Rapide et Sécurisée</div>
            </div>
          </div>
          
          <button class="text-white hover:text-primea-yellow">
            <Bars3Icon class="w-6 h-6" />
          </button>
        </div>

        <!-- Titre Mobile selon maquette -->
        <div class="text-center mb-8">
          <h1 class="text-2xl font-bold text-white mb-4">
            Tous les événements<br>
            <span class="text-primea-yellow">en cours</span>
          </h1>
        </div>

        <!-- Liste des événements Mobile selon maquette -->
        <div class="space-y-4 mb-8">
          <div 
            v-for="event in events" 
            :key="event.id"
            @click="goToEvent(event)"
            class="bg-white rounded-primea-lg shadow-primea overflow-hidden cursor-pointer hover:shadow-primea-lg transition-all duration-300 transform hover:scale-105"
          >
            <!-- Image de l'événement avec overlay comme dans la maquette -->
            <div class="h-48 bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 relative overflow-hidden">
              <img 
                v-if="event.image" 
                :src="event.image" 
                :alt="event.title"
                class="w-full h-full object-cover"
              />
              <div class="absolute inset-0 bg-primea-blue/40"></div>
              
              <!-- Contenu sur l'image selon maquette -->
              <div class="absolute inset-0 p-4 text-white">
                <div class="flex justify-between items-start mb-4">
                  <div class="text-xs">
                    <div class="bg-primea-yellow/20 backdrop-blur-sm rounded-primea px-2 py-1 mb-1 text-primea-yellow font-bold">CONCERT</div>
                    <div class="font-bold">{{ event.title }}</div>
                  </div>
                  <div class="text-right text-xs">
                    <div class="bg-primea-yellow text-primea-blue px-2 py-1 rounded-primea mb-1 font-bold">
                      {{ formatEventDate(event.date) }}
                    </div>
                    <div class="bg-red-600 text-white px-2 py-1 rounded-primea font-bold">18</div>
                  </div>
                </div>
                
                <!-- Prix selon la maquette -->
                <div class="absolute bottom-4 left-4 right-4">
                  <div class="flex justify-between items-end">
                    <div class="text-xs">
                      <div class="flex space-x-2 mb-2">
                        <div class="bg-green-500 text-white px-2 py-1 rounded-primea text-xs font-bold">
                          {{ formatPrice(event.minPrice) }}
                        </div>
                        <div class="bg-primea-blue text-white px-2 py-1 rounded-primea text-xs font-bold">
                          {{ formatPrice(event.maxPrice) }}
                        </div>
                      </div>
                      <div class="text-xs text-gray-200">{{ event.venue }}</div>
                    </div>
                    <div class="text-right">
                      <div class="text-xs text-gray-200">Animation by</div>
                      <div class="text-xs font-bold text-primea-yellow">{{ event.organizer || 'MR GILLES' }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Message si aucun événement ou erreur Mobile -->
        <div v-if="events.length === 0 && !loading" class="text-center py-12">
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

        <!-- Logo et réseaux sociaux Mobile selon maquette -->
        <div class="text-center mt-12">
          <img src="/images/logo_white.png" alt="Primea" class="h-8 mx-auto mb-4" />
          <div class="flex justify-center space-x-4">
            <a href="#" class="text-green-500 hover:text-green-400 transition-colors">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"/>
              </svg>
            </a>
            <a href="#" class="text-primea-yellow hover:text-white transition-colors">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
              </svg>
            </a>
            <a href="#" class="text-pink-400 hover:text-pink-300 transition-colors">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.174-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.347-.09.375-.293 1.199-.332 1.363-.053.225-.172.271-.402.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.357-.629-2.746-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24.009 12.017 24.009c6.624 0 11.990-5.367 11.990-11.988C24.007 5.367 18.641.001.012.001z"/>
              </svg>
            </a>
            <a href="#" class="text-white hover:text-primea-yellow transition-colors">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
              </svg>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useEventsStore } from '../stores/events'
import EventCard from '../components/EventCard.vue'
import { 
  ChevronLeftIcon, 
  Bars3Icon, 
  ExclamationTriangleIcon 
} from '@heroicons/vue/24/outline'

export default {
  name: 'Events',
  components: {
    EventCard,
    ChevronLeftIcon,
    Bars3Icon,
    ExclamationTriangleIcon
  },
  setup() {
    const router = useRouter()
    const eventsStore = useEventsStore()

    // État des événements
    const events = ref([])
    const loading = ref(true)
    const selectedCategory = ref('all')
    const categories = ref([])

    // Événements filtrés
    const filteredEvents = computed(() => {
      if (selectedCategory.value === 'all') {
        return events.value
      }
      
      return events.value.filter(event => {
        // Utiliser l'ID de la catégorie pour filtrer
        const eventCategoryId = event.category?.id || event.category_id
        return eventCategoryId === parseInt(selectedCategory.value)
      })
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
      if (!dateString) return '27'
      
      const date = new Date(dateString)
      return date.getDate().toString()
    }

    const formatPrice = (price) => {
      if (!price) return '5.000'
      return new Intl.NumberFormat('fr-FR').format(price)
    }

    const goToEvent = (event) => {
      router.push(`/events/${event.slug}`)
    }

    const goBack = () => {
      router.back()
    }

    const filterByCategory = (categoryId) => {
      selectedCategory.value = categoryId.toString()
    }

    // Lifecycle
    onMounted(() => {
      loadEvents()
      loadCategories()
    })

    return {
      events,
      loading,
      selectedCategory,
      categories,
      filteredEvents,
      formatEventDate,
      formatPrice,
      goToEvent,
      goBack,
      filterByCategory,
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