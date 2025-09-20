<template>
  <div class="event-create min-h-screen" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%)">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <!-- Header -->
      <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 mb-2">Créer un Événement</h1>
          <p class="text-gray-600">Configurez tous les détails de votre événement</p>
        </div>
        <button @click="$router.go(-1)" class="text-gray-600 hover:text-gray-800">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
          </svg>
        </button>
      </div>
    </div>

    <!-- Form -->
    <form @submit.prevent="createEvent" class="max-w-4xl">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Basic Information -->
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">Informations de Base</h2>
            
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Titre de l'événement *</label>
                <input v-model="eventForm.title" type="text" required 
                       class="w-full border rounded-lg px-3 py-2" 
                       placeholder="Concert de Jazz, Spectacle de Théâtre...">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea v-model="eventForm.description" rows="4" 
                          class="w-full border rounded-lg px-3 py-2" 
                          placeholder="Décrivez votre événement en détails..."></textarea>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Catégorie *</label>
                  <select v-model="eventForm.category_id" required class="w-full border rounded-lg px-3 py-2">
                    <option value="">Sélectionner une catégorie</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">
                      {{ category.name }}
                    </option>
                  </select>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Lieu *</label>
                  <select v-model="eventForm.venue_id" required class="w-full border rounded-lg px-3 py-2">
                    <option value="">Sélectionner un lieu</option>
                    <option v-for="venue in venues" :key="venue.id" :value="venue.id">
                      {{ venue.name }} - {{ venue.city }}
                    </option>
                  </select>
                </div>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Image de couverture</label>
                <input type="file" @change="handleImageUpload" accept="image/*" 
                       class="w-full border rounded-lg px-3 py-2">
                <p class="text-xs text-gray-500 mt-1">Formats acceptés: JPG, PNG, WebP. Taille max: 5MB</p>
              </div>
            </div>
          </div>

          <!-- Event Schedules -->
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-xl font-bold">Programmation</h2>
              <button type="button" @click="addSchedule" 
                      class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">
                Ajouter une séance
              </button>
            </div>
            
            <div v-if="eventForm.schedules.length === 0" class="text-center text-gray-500 py-8">
              Aucune séance programmée. Cliquez sur "Ajouter une séance" pour commencer.
            </div>
            
            <div v-else class="space-y-4">
              <div v-for="(schedule, index) in eventForm.schedules" :key="index" 
                   class="border rounded-lg p-4 bg-gray-50">
                <div class="flex justify-between items-start mb-3">
                  <h3 class="font-medium">Séance {{ index + 1 }}</h3>
                  <button type="button" @click="removeSchedule(index)" 
                          class="text-red-600 hover:text-red-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                  </button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date et heure de début *</label>
                    <input v-model="schedule.starts_at" type="datetime-local" required 
                           class="w-full border rounded-lg px-3 py-2">
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date et heure de fin *</label>
                    <input v-model="schedule.ends_at" type="datetime-local" required 
                           class="w-full border rounded-lg px-3 py-2">
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Ticket Types -->
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-xl font-bold">Types de Billets</h2>
              <button type="button" @click="addTicketType" 
                      class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">
                Ajouter un type
              </button>
            </div>
            
            <div v-if="eventForm.ticketTypes.length === 0" class="text-center text-gray-500 py-8">
              Aucun type de billet défini. Cliquez sur "Ajouter un type" pour commencer.
            </div>
            
            <div v-else class="space-y-4">
              <div v-for="(ticketType, index) in eventForm.ticketTypes" :key="index" 
                   class="border rounded-lg p-4 bg-gray-50">
                <div class="flex justify-between items-start mb-3">
                  <h3 class="font-medium">{{ ticketType.name || `Type ${index + 1}` }}</h3>
                  <button type="button" @click="removeTicketType(index)" 
                          class="text-red-600 hover:text-red-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                  </button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom du type *</label>
                    <input v-model="ticketType.name" type="text" required 
                           placeholder="VIP, Standard, Étudiant..." 
                           class="w-full border rounded-lg px-3 py-2">
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prix (XAF) *</label>
                    <input v-model.number="ticketType.price" type="number" min="0" required 
                           class="w-full border rounded-lg px-3 py-2">
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Capacité *</label>
                    <input v-model.number="ticketType.capacity" type="number" min="1" required 
                           class="w-full border rounded-lg px-3 py-2">
                  </div>
                </div>
                
                <div class="mt-3">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                  <textarea v-model="ticketType.description" rows="2" 
                            placeholder="Avantages inclus, restrictions..." 
                            class="w-full border rounded-lg px-3 py-2"></textarea>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">
          <!-- Preview -->
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">Aperçu</h2>
            
            <div class="space-y-3">
              <div>
                <h3 class="font-medium text-gray-900">{{ eventForm.title || 'Titre de l\'événement' }}</h3>
                <p class="text-sm text-gray-600">{{ eventForm.description || 'Description...' }}</p>
              </div>
              
              <div v-if="selectedCategory">
                <span class="inline-flex px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">
                  {{ selectedCategory.name }}
                </span>
              </div>
              
              <div v-if="selectedVenue" class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                {{ selectedVenue.name }}
              </div>
              
              <div v-if="eventForm.schedules.length > 0">
                <h4 class="text-sm font-medium text-gray-700 mb-1">Prochaines séances:</h4>
                <div class="space-y-1">
                  <div v-for="(schedule, index) in eventForm.schedules.slice(0, 3)" :key="index" 
                       class="text-xs text-gray-600">
                    {{ formatDateTime(schedule.starts_at) }}
                  </div>
                  <div v-if="eventForm.schedules.length > 3" class="text-xs text-gray-500">
                    +{{ eventForm.schedules.length - 3 }} autre(s) séance(s)
                  </div>
                </div>
              </div>
              
              <div v-if="eventForm.ticketTypes.length > 0">
                <h4 class="text-sm font-medium text-gray-700 mb-1">Tarifs:</h4>
                <div class="space-y-1">
                  <div v-for="type in eventForm.ticketTypes" :key="type.name" 
                       class="flex justify-between text-xs">
                    <span>{{ type.name }}</span>
                    <span class="font-medium">{{ formatAmount(type.price) }} XAF</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">Publication</h2>
            
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                <select v-model="eventForm.status" class="w-full border rounded-lg px-3 py-2">
                  <option value="draft">Brouillon</option>
                  <option value="published">Publié</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">
                  Les brouillons ne sont pas visibles par le public
                </p>
              </div>
              
              <div class="pt-4 border-t">
                <button type="submit" :disabled="creating" 
                        class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 disabled:opacity-50">
                  {{ creating ? 'Création en cours...' : 'Créer l\'événement' }}
                </button>
              </div>
            </div>
          </div>

          <!-- Summary -->
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">Résumé</h2>
            
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-600">Séances:</span>
                <span class="font-medium">{{ eventForm.schedules.length }}</span>
              </div>
              
              <div class="flex justify-between">
                <span class="text-gray-600">Types de billets:</span>
                <span class="font-medium">{{ eventForm.ticketTypes.length }}</span>
              </div>
              
              <div class="flex justify-between">
                <span class="text-gray-600">Capacité totale:</span>
                <span class="font-medium">{{ totalCapacity }}</span>
              </div>
              
              <div class="flex justify-between">
                <span class="text-gray-600">Prix minimum:</span>
                <span class="font-medium">{{ formatAmount(minPrice) }} XAF</span>
              </div>
              
              <div class="flex justify-between">
                <span class="text-gray-600">Prix maximum:</span>
                <span class="font-medium">{{ formatAmount(maxPrice) }} XAF</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'

export default {
  name: 'EventCreate',
  setup() {
    // État réactif
    const creating = ref(false)
    const categories = ref([])
    const venues = ref([])
    
    const eventForm = reactive({
      title: '',
      description: '',
      category_id: '',
      venue_id: '',
      status: 'draft',
      image_url: '',
      schedules: [],
      ticketTypes: []
    })

    // Computed
    const selectedCategory = computed(() => {
      return categories.value.find(cat => cat.id == eventForm.category_id)
    })

    const selectedVenue = computed(() => {
      return venues.value.find(venue => venue.id == eventForm.venue_id)
    })

    const totalCapacity = computed(() => {
      return eventForm.ticketTypes.reduce((total, type) => total + (type.capacity || 0), 0)
    })

    const minPrice = computed(() => {
      if (eventForm.ticketTypes.length === 0) return 0
      return Math.min(...eventForm.ticketTypes.map(type => type.price || 0))
    })

    const maxPrice = computed(() => {
      if (eventForm.ticketTypes.length === 0) return 0
      return Math.max(...eventForm.ticketTypes.map(type => type.price || 0))
    })

    // Méthodes
    const loadCategories = async () => {
      try {
        const response = await fetch('/api/client/categories', {
          headers: {
            'Accept': 'application/json'
          }
        })
        
        const data = await response.json()
        if (response.ok) {
          categories.value = data
        }
      } catch (error) {
        console.error('Erreur chargement catégories:', error)
      }
    }

    const loadVenues = async () => {
      try {
        // Cette route devrait exister ou être créée
        const response = await fetch('/api/v1/venues', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        const data = await response.json()
        if (data.success) {
          venues.value = data.data.venues
        }
      } catch (error) {
        console.error('Erreur chargement lieux:', error)
      }
    }

    const addSchedule = () => {
      eventForm.schedules.push({
        starts_at: '',
        ends_at: ''
      })
    }

    const removeSchedule = (index) => {
      eventForm.schedules.splice(index, 1)
    }

    const addTicketType = () => {
      eventForm.ticketTypes.push({
        name: '',
        price: '',
        capacity: '',
        description: ''
      })
    }

    const removeTicketType = (index) => {
      eventForm.ticketTypes.splice(index, 1)
    }

    const handleImageUpload = (event) => {
      const file = event.target.files[0]
      if (file) {
        // Ici vous pouvez implémenter l'upload vers un service de stockage
        // Pour l'instant, on simule juste une URL
        eventForm.image_url = URL.createObjectURL(file)
      }
    }

    const createEvent = async () => {
      // Validation de base
      if (eventForm.schedules.length === 0) {
        alert('Veuillez ajouter au moins une séance')
        return
      }

      if (eventForm.ticketTypes.length === 0) {
        alert('Veuillez ajouter au moins un type de billet')
        return
      }

      creating.value = true
      try {
        const response = await fetch('/api/v1/events', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(eventForm)
        })
        
        const data = await response.json()
        if (data.success) {
          alert('Événement créé avec succès!')
          this.$router.push(`/organizer/events/${data.data.event.id}/edit`)
        } else {
          alert(data.message || 'Erreur lors de la création')
        }
      } catch (error) {
        console.error('Erreur création événement:', error)
        alert('Erreur technique')
      } finally {
        creating.value = false
      }
    }

    // Utilitaires
    const formatAmount = (amount) => {
      return new Intl.NumberFormat('fr-FR').format(amount)
    }

    const formatDateTime = (datetime) => {
      if (!datetime) return ''
      return new Date(datetime).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    // Lifecycle
    onMounted(() => {
      loadCategories()
      loadVenues()
      
      // Ajouter des éléments par défaut
      addSchedule()
      addTicketType()
    })

    return {
      // État
      creating,
      categories,
      venues,
      eventForm,
      selectedCategory,
      selectedVenue,
      totalCapacity,
      minPrice,
      maxPrice,
      
      // Méthodes
      addSchedule,
      removeSchedule,
      addTicketType,
      removeTicketType,
      handleImageUpload,
      createEvent,
      
      // Utilitaires
      formatAmount,
      formatDateTime,
    }
  }
}
</script>

<style scoped>
.event-create {
  font-family: 'Inter', sans-serif;
}
</style>