<template>
  <div class="organizer-events min-h-screen" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%)">
      <!-- En-tête de la page -->
      <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-primea-blue font-primea">Mes Événements</h1>
              <p class="text-gray-600 text-sm font-primea">Gérez tous vos événements en un seul endroit</p>
            </div>

            <router-link
              :to="{ name: 'organizer-event-create' }"
              class="bg-primea-blue text-white px-3 py-1.5 rounded-primea hover:bg-primea-yellow hover:text-primea-blue text-sm font-semibold font-primea transition-all duration-200 shadow-primea"
            >
              <PlusIcon class="w-4 h-4 inline mr-1" />
              Nouvel événement
            </router-link>
          </div>
        </div>
      </div>

      <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
      <!-- Filtres et statistiques -->
      <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="flex flex-col md:flex-row gap-4 justify-between items-center mb-6">
          <div class="flex items-center space-x-4">
            <div class="relative">
              <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
              </svg>
              <input 
                type="text" 
                v-model="searchQuery"
                placeholder="Rechercher un événement..."
                class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
            
            <select v-model="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
              <option value="">Tous les statuts</option>
              <option value="draft">Brouillons</option>
              <option value="published">Publiés</option>
              <option value="completed">Terminés</option>
              <option value="cancelled">Annulés</option>
            </select>
          </div>

          <div class="flex items-center space-x-4">
            <div class="text-sm text-gray-600">
              {{ filteredEvents.length }} événement{{ filteredEvents.length > 1 ? 's' : '' }}
            </div>
            <div class="flex bg-gray-100 rounded-lg p-1">
              <button 
                @click="viewMode = 'grid'"
                :class="['px-3 py-2 rounded-lg transition-colors', viewMode === 'grid' ? 'bg-white shadow-sm' : '']"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
              </button>
              <button 
                @click="viewMode = 'list'"
                :class="['px-3 py-2 rounded-lg transition-colors', viewMode === 'list' ? 'bg-white shadow-sm' : '']"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Statistiques rapides -->
        <div class="flex gap-4 overflow-x-auto pb-2">
          <div class="text-center flex-shrink-0 min-w-[140px]">
            <p class="text-2xl font-bold text-blue-600">{{ stats.published }}</p>
            <p class="text-sm text-gray-600">Publiés</p>
          </div>
          <div class="text-center flex-shrink-0 min-w-[140px]">
            <p class="text-2xl font-bold text-yellow-600">{{ stats.draft }}</p>
            <p class="text-sm text-gray-600">Brouillons</p>
          </div>
          <div class="text-center flex-shrink-0 min-w-[140px]">
            <p class="text-2xl font-bold text-green-600">{{ stats.totalTicketsSold }}</p>
            <p class="text-sm text-gray-600">Tickets vendus</p>
          </div>
          <div class="text-center flex-shrink-0 min-w-[180px]">
            <p class="text-2xl font-bold text-purple-600">{{ formatPrice(stats.totalRevenue) }} XAF</p>
            <p class="text-sm text-gray-600">Revenus</p>
          </div>
        </div>
      </div>

      <!-- Liste/Grille des événements -->
      <div v-if="filteredEvents.length > 0">
        <!-- Vue grille -->
        <div v-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="event in filteredEvents" 
            :key="event.id"
            class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow"
          >
            <div class="relative">
              <img 
                v-if="hasEventImage(event)"
                :src="getEventImageUrl(event)" 
                :alt="event.title"
                class="w-full h-48 object-cover"
                @error="handleImageError"
                @load="handleImageLoad"
              />
              <div 
                v-else 
                class="w-full h-48 bg-gradient-to-br from-blue-100 to-yellow-100 flex items-center justify-center"
              >
                <CalendarIcon class="w-16 h-16 text-gray-400" />
              </div>
              <div class="absolute top-4 left-4">
                <span 
                  class="px-3 py-1 rounded-full text-xs font-semibold"
                  :class="getStatusClass(event.status)"
                >
                  {{ getStatusText(event.status) }}
                </span>
              </div>
              <div class="absolute top-4 right-4">
                <button 
                  @click="toggleFavorite(event)"
                  class="bg-white/90 p-2 rounded-full hover:bg-white transition-colors"
                >
                  <svg class="w-4 h-4" :class="event.isFavorite ? 'text-red-500' : 'text-gray-400'" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                  </svg>
                </button>
              </div>
            </div>
            
            <div class="p-6">
              <h3 class="text-lg font-bold text-gray-800 mb-2">{{ event.title }}</h3>
              <p class="text-gray-600 text-sm mb-4">{{ formatEventDate(event.date) }}</p>
              
              <div class="flex items-center justify-between mb-4">
                <div class="text-sm text-gray-600 font-primea">
                  <span class="font-medium font-primea">{{ event.tickets_sold || 0 }}</span> / {{ event.total_tickets || 0 }} tickets vendus
                </div>
                <div class="text-sm font-bold text-green-600 font-primea">
                  {{ formatPrice(event.revenue || 0) }} XAF
                </div>
              </div>
              
              <!-- Barre de progression -->
              <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                <div 
                  class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                  :style="{ width: `${Math.min(((event.ticketsSold || 0) / (event.totalTickets || 100)) * 100, 100)}%` }"
                ></div>
              </div>
              
              <div class="flex space-x-2">
                <router-link 
                  :to="{ name: 'organizer-event-detail', params: { slug: event.slug } }"
                  class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg text-center text-sm font-medium hover:bg-blue-700 transition-colors"
                >
                  Voir détails
                </router-link>
                <button 
                  @click="duplicateEvent(event)"
                  class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-300 transition-colors"
                >
                  Dupliquer
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Vue liste -->
        <div v-else class="bg-white rounded-lg shadow-md overflow-hidden">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Événement</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ventes</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Revenus</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="event in filteredEvents" :key="event.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <img 
                      v-if="hasEventImage(event)"
                      :src="getEventImageUrl(event, '60')" 
                      :alt="event.title"
                      class="w-12 h-12 object-cover rounded-lg mr-4"
                      @error="handleImageError"
                      @load="handleImageLoad"
                    />
                    <div 
                      v-else 
                      class="w-12 h-12 bg-gradient-to-br from-blue-100 to-yellow-100 flex items-center justify-center rounded-lg mr-4"
                    >
                      <CalendarIcon class="w-6 h-6 text-gray-400" />
                    </div>
                    <div>
                      <div class="text-sm font-medium text-gray-900">{{ event.title }}</div>
                      <div class="text-sm text-gray-500">{{ event.venue }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-primea">
                  {{ formatEventDate(event.date, 'short') }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span 
                    class="px-2 py-1 rounded-full text-xs font-medium font-primea"
                    :class="getStatusClass(event)"
                  >
                    {{ getStatusText(event) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-primea">
                  {{ event.tickets_sold || 0 }} / {{ event.total_tickets || 0 }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600 font-primea">
                  {{ formatPrice(event.revenue || 0) }} XAF
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                  <router-link 
                    :to="{ name: 'organizer-event-detail', params: { slug: event.slug } }"
                    class="text-blue-600 hover:text-blue-900"
                  >
                    Voir
                  </router-link>
                  <button 
                    @click="editEvent(event)"
                    class="text-yellow-600 hover:text-yellow-900"
                  >
                    Modifier
                  </button>
                  <button 
                    @click="deleteEvent(event)"
                    class="text-red-600 hover:text-red-900"
                  >
                    Supprimer
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Message aucun événement -->
      <div v-else class="text-center py-16">
        <div class="inline-block p-6 bg-gray-100 rounded-full mb-6">
          <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a4 4 0 118 0v4m-4 8a4 4 0 11-8 0 4 4 0 018 0z"/>
          </svg>
        </div>
        <h3 class="text-2xl font-bold text-gray-800 mb-2">Aucun événement trouvé</h3>
        <p class="text-gray-600 mb-6">
          <span v-if="searchQuery || statusFilter">
            Aucun événement ne correspond à vos critères de recherche.
          </span>
          <span v-else>
            Commencez par créer votre premier événement !
          </span>
        </p>
        <router-link 
          to="/organizer/events/create"
          class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors inline-block"
        >
          + Créer mon premier événement
        </router-link>
      </div>

      <!-- Pagination -->
      <div v-if="events.length > 0 && pagination.total > 1" class="mt-8 flex justify-center">
        <nav class="flex items-center space-x-2">
          <button
            @click="changePage(pagination.current - 1)"
            :disabled="pagination.current === 1"
            class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed font-primea"
          >
            Précédent
          </button>
          
          <template v-for="page in pagination.total" :key="page">
            <button
              v-if="page === 1 || page === pagination.total || (page >= pagination.current - 1 && page <= pagination.current + 1)"
              @click="changePage(page)"
              :class="[
                'px-3 py-2 text-sm font-medium rounded-lg font-primea',
                page === pagination.current
                  ? 'bg-primea-blue text-white'
                  : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'
              ]"
            >
              {{ page }}
            </button>
            <span
              v-else-if="page === pagination.current - 2 || page === pagination.current + 2"
              class="px-2 text-gray-500"
            >
              ...
            </span>
          </template>
          
          <button
            @click="changePage(pagination.current + 1)"
            :disabled="pagination.current === pagination.total"
            class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed font-primea"
          >
            Suivant
          </button>
        </nav>
      </div>
      </div>
    </div>
</template>

<script>
import { ref, computed, onMounted, reactive, watch } from 'vue'
import { useRouter } from 'vue-router'
import { organizerService } from '../../services/api'
import { PlusIcon, CalendarIcon } from '@heroicons/vue/24/outline'
import Swal from 'sweetalert2'

export default {
  name: 'OrganizerEvents',
  components: {
    PlusIcon,
    CalendarIcon
  },
  setup() {
    const router = useRouter()

    // État réactif
    const searchQuery = ref('')
    const statusFilter = ref('')
    const viewMode = ref('grid')
    const loading = ref(false)
    const events = ref([])
    const pagination = reactive({
      current: 1,
      total: 1,
      perPage: 15
    })

    // Charger les événements depuis l'API
    const loadEvents = async () => {
      loading.value = true
      try {
        const response = await organizerService.getEvents({
          page: pagination.current,
          status: statusFilter.value,
          search: searchQuery.value
        })
        
        const data = response.data.data.events
        events.value = data.data.map(event => {
          console.log('EVENT DATA:', {
            title: event.title,
            image: event.image,
            image_url: event.image_url,
            image_file: event.image_file,
            revenue_from_api: event.revenue
          })
          return {
            ...event,
            tickets_sold: event.tickets_sold_count || event.tickets?.filter(t => ['issued', 'used'].includes(t.status)).length || 0,
            total_tickets: event.total_capacity || event.ticket_types?.reduce((sum, tt) => sum + tt.capacity, 0) || 0,
            // Utiliser le revenu calculé par l'API (montant NET pour l'organisateur)
            revenue: event.revenue || 0,
            venue: event.venue?.name || '',
            date: event.event_date || event.schedules?.[0]?.starts_at,
            isFavorite: false
          }
        })
        
        pagination.current = data.current_page
        pagination.total = data.last_page
        pagination.perPage = data.per_page
      } catch (error) {
        console.error('Erreur chargement événements:', error)
        Swal.fire({
          icon: 'error',
          title: 'Erreur',
          text: 'Impossible de charger les événements'
        })
      } finally {
        loading.value = false
      }
    }

    // Computed properties
    const filteredEvents = computed(() => {
      let filtered = [...events.value]

      // Filtre par recherche
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(event => 
          event.title.toLowerCase().includes(query) ||
          event.venue.toLowerCase().includes(query)
        )
      }

      // Filtre par statut
      if (statusFilter.value) {
        filtered = filtered.filter(event => event.status === statusFilter.value)
      }

      return filtered
    })

    const stats = computed(() => {
      const published = events.value.filter(e => e.is_active && e.status !== 'cancelled').length
      const draft = events.value.filter(e => !e.is_active).length
      const totalTicketsSold = events.value.reduce((sum, e) => sum + (e.tickets_sold || 0), 0)
      const totalRevenue = events.value.reduce((sum, e) => sum + (e.revenue || 0), 0)

      return {
        published,
        draft,
        totalTicketsSold,
        totalRevenue
      }
    })

    // Méthodes
    const formatPrice = (price) => {
      return new Intl.NumberFormat('fr-FR').format(price)
    }

    const formatEventDate = (dateString, format = 'full') => {
      const date = new Date(dateString)
      
      if (format === 'short') {
        return date.toLocaleDateString('fr-FR', {
          day: 'numeric',
          month: 'short',
          year: 'numeric'
        })
      }
      
      return date.toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    const getStatusClass = (event) => {
      if (!event.is_active) return 'bg-yellow-100 text-yellow-800'
      if (event.status === 'cancelled') return 'bg-red-100 text-red-800'
      if (new Date(event.date) < new Date()) return 'bg-gray-100 text-gray-800'
      return 'bg-green-100 text-green-800'
    }

    const getStatusText = (event) => {
      if (!event.is_active) return 'Brouillon'
      if (event.status === 'cancelled') return 'Annulé'
      if (new Date(event.date) < new Date()) return 'Terminé'
      return 'Actif'
    }

    const toggleFavorite = (event) => {
      event.isFavorite = !event.isFavorite
      // TODO: Sauvegarder en base
    }

    const duplicateEvent = async (event) => {
      try {
        const newEvent = {
          title: `${event.title} (Copie)`,
          description: event.description,
          event_date: event.date,
          venue_id: event.venue_id,
          category_id: event.category_id,
          is_active: false
        }
        
        await organizerService.createEvent(newEvent)
        await loadEvents()
        
        Swal.fire({
          icon: 'success',
          title: 'Succès',
          text: 'Événement dupliqué avec succès'
        })
      } catch (error) {
        Swal.fire({
          icon: 'error',
          title: 'Erreur',
          text: 'Impossible de dupliquer l\'événement'
        })
      }
    }

    const editEvent = (event) => {
      router.push({ name: 'organizer-event-edit', params: { slug: event.slug } })
    }

    const deleteEvent = async (event) => {
      const result = await Swal.fire({
        title: 'Êtes-vous sûr ?',
        text: `Voulez-vous vraiment supprimer l'événement "${event.title}" ?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler'
      })

      if (result.isConfirmed) {
        try {
          // TODO: Implémenter la suppression via API
          // await organizerService.deleteEvent(event.id)
          await loadEvents()
          
          Swal.fire(
            'Supprimé !',
            'L\'événement a été supprimé.',
            'success'
          )
        } catch (error) {
          Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: 'Impossible de supprimer l\'événement'
          })
        }
      }
    }

    // Watchers pour les filtres
    watch([searchQuery, statusFilter], () => {
      pagination.current = 1
      loadEvents()
    }, { debounce: 300 })

    // Changer de page
    const changePage = (page) => {
      pagination.current = page
      loadEvents()
    }

    // Gestion des images
    const hasEventImage = (event) => {
      if (!event) return false
      const imageUrl = event.image_url || event.image || event.image_file
      return imageUrl && imageUrl.trim() !== ''
    }

    const getEventImageUrl = (event, size = '400') => {
      // Priorité: image_url, puis image, puis image_file
      let imageUrl = event.image_url || event.image || event.image_file
      
      console.log('Image URL pour événement:', event.title, 'URL brute:', imageUrl)
      
      if (imageUrl && imageUrl.trim() !== '') {
        // Si c'est déjà une URL complète (commence par http:// ou https://), on la retourne telle quelle
        if (imageUrl.startsWith('http://') || imageUrl.startsWith('https://')) {
          console.log('URL complète détectée:', imageUrl)
          return imageUrl
        }
        
        // Si c'est un chemin relatif, on construit l'URL complète
        if (imageUrl.startsWith('/')) {
          const baseUrl = window.location.origin
          imageUrl = baseUrl + imageUrl
          console.log('URL construite depuis chemin relatif:', imageUrl)
          return imageUrl
        }
        
        // Si c'est un nom de fichier dans le storage
        if (!imageUrl.includes('/')) {
          const baseUrl = window.location.origin
          imageUrl = `${baseUrl}/storage/images/events/${imageUrl}`
          console.log('URL construite depuis nom de fichier:', imageUrl)
          return imageUrl
        }
        
        // Sinon on retourne tel quel
        return imageUrl
      }
      
      console.log('Aucune image trouvée pour:', event.title)
      return ''
    }

    const handleImageError = (event) => {
      console.log('Erreur de chargement d\'image:', event.target.src)
      // L'image sera masquée et remplacée par le placeholder avec CalendarIcon
      event.target.style.display = 'none'
    }

    const handleImageLoad = (event) => {
      console.log('Image chargée avec succès:', event.target.src)
    }

    // Charger les données au montage
    onMounted(() => {
      loadEvents()
    })

    return {
      searchQuery,
      statusFilter,
      viewMode,
      loading,
      events,
      filteredEvents,
      stats,
      pagination,
      formatPrice,
      formatEventDate,
      getStatusClass,
      getStatusText,
      toggleFavorite,
      duplicateEvent,
      editEvent,
      deleteEvent,
      loadEvents,
      changePage,
      hasEventImage,
      getEventImageUrl,
      handleImageError,
      handleImageLoad
    }
  }
}
</script>

<style scoped>
.organizer-events {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

/* Grid utilities */
.grid {
  display: grid;
}

.grid-cols-1 {
  grid-template-columns: repeat(1, minmax(0, 1fr));
}

@media (min-width: 768px) {
  .md\:grid-cols-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  
  .md\:grid-cols-4 {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
  
  .md\:flex-row {
    flex-direction: row;
  }
}

@media (min-width: 1024px) {
  .lg\:grid-cols-3 {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

.gap-4 {
  gap: 1rem;
}

.gap-6 {
  gap: 1.5rem;
}

.space-x-2 > * + * {
  margin-left: 0.5rem;
}

.space-x-4 > * + * {
  margin-left: 1rem;
}

.transition-colors {
  transition: color 0.2s ease-in-out, background-color 0.2s ease-in-out;
}

.transition-shadow {
  transition: box-shadow 0.2s ease-in-out;
}

/* Ajout des styles Primea */
.text-primea-blue {
  color: #272d63;
}

.bg-primea-blue {
  background-color: #272d63;
}

.bg-primea-yellow {
  background-color: #fab511;
}

.text-primea-yellow {
  color: #fab511;
}

.hover\:bg-primea-yellow:hover {
  background-color: #fab511;
}

.hover\:text-primea-blue:hover {
  color: #272d63;
}

.rounded-primea {
  border-radius: 12px;
}

.shadow-primea {
  box-shadow: 0 2px 15px rgba(39, 45, 99, 0.08);
}

.font-primea {
  font-family: 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
</style>