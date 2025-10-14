<template>
  <div class="venue-management p-6">
    <!-- Header -->
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold" style="color: #272d63;">Gestion des Lieux</h1>
        <p class="text-gray-600 mt-1">Gérez les lieux et salles d'événements</p>
      </div>
      <button @click="showCreateModal = true" 
              class="text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200"
              style="background-color: #272d63;"
              @mouseover="$event.currentTarget.style.backgroundColor = '#fab511'"
              @mouseleave="$event.currentTarget.style.backgroundColor = '#272d63'">
        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        Nouveau Lieu
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
          <input type="text" v-model="filters.search" 
                 placeholder="Nom du lieu..."
                 class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                 style="--tw-ring-color: #272d63;">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Ville</label>
          <select v-model="filters.city" 
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                  style="--tw-ring-color: #272d63;">
            <option value="">Toutes les villes</option>
            <option v-for="city in cities" :key="city" :value="city">{{ city }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
          <select v-model="filters.status" 
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                  style="--tw-ring-color: #272d63;">
            <option value="">Tous les statuts</option>
            <option value="active">Actif</option>
            <option value="inactive">Inactif</option>
          </select>
        </div>
        <div class="flex items-end">
          <button @click="loadVenues" 
                  class="w-full text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200"
                  style="background-color: #fab511;"
                  @mouseover="$event.currentTarget.style.backgroundColor = '#272d63'"
                  @mouseleave="$event.currentTarget.style.backgroundColor = '#fab511'">
            Filtrer
          </button>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center items-center h-64">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2" style="border-color: #272d63;"></div>
    </div>

    <!-- Venues Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="venue in filteredVenues" :key="venue.id" 
           class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow duration-200">
        <!-- Venue Image -->
        <div class="h-48 bg-gray-200 rounded-t-lg relative overflow-hidden">
          <img v-if="getVenueImageUrl(venue)"
               :src="getVenueImageUrl(venue)"
               :alt="venue.name"
               class="w-full h-full object-cover"
               @error="handleImageError">
          <div v-else class="w-full h-full flex items-center justify-center">
            <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
          </div>
          <!-- Status Badge -->
          <div class="absolute top-3 right-3">
            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                  :class="venue.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
              {{ venue.status === 'active' ? 'Actif' : 'Inactif' }}
            </span>
          </div>
        </div>
        
        <!-- Venue Info -->
        <div class="p-4">
          <h3 class="text-lg font-semibold mb-2" style="color: #272d63;">{{ venue.name }}</h3>
          <div class="space-y-2 text-sm text-gray-600">
            <div class="flex items-center">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
              {{ venue.address }}, {{ venue.city }}
            </div>
            <div class="flex items-center">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
              </svg>
              Capacité: {{ venue.capacity }} personnes
            </div>
            <div class="flex items-center">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
              {{ venue.events_count || 0 }} événements
            </div>
          </div>
          
          <div class="flex space-x-2 mt-4">
            <button @click="editVenue(venue)" 
                    class="flex-1 text-sm px-3 py-2 rounded transition-colors duration-200"
                    style="color: #272d63; background-color: rgba(39, 45, 99, 0.1);"
                    @mouseover="$event.currentTarget.style.backgroundColor = '#fab511'; $event.currentTarget.style.color = '#fff'"
                    @mouseleave="$event.currentTarget.style.backgroundColor = 'rgba(39, 45, 99, 0.1)'; $event.currentTarget.style.color = '#272d63'">
              Modifier
            </button>
            <button @click="deleteVenue(venue)" 
                    class="flex-1 text-sm text-red-600 hover:text-red-800 px-3 py-2 bg-red-100 hover:bg-red-200 rounded transition-colors duration-200">
              Supprimer
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="venues.length === 0 && !loading" class="text-center py-12">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">Aucun lieu</h3>
      <p class="mt-1 text-sm text-gray-500">Commencez par ajouter votre premier lieu.</p>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showCreateModal || showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-2xl mx-4 max-h-90vh overflow-y-auto">
        <h3 class="text-lg font-bold mb-4" style="color: #272d63;">
          {{ showCreateModal ? 'Nouveau Lieu' : 'Modifier le Lieu' }}
        </h3>
        
        <form @submit.prevent="showCreateModal ? createVenue() : updateVenue()">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">Nom du lieu *</label>
              <input type="text" v-model="venueForm.name" required
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                     style="--tw-ring-color: #272d63;">
            </div>
            
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
              <textarea v-model="venueForm.description" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                        style="--tw-ring-color: #272d63;"></textarea>
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">Image du lieu</label>

              <!-- Image actuelle -->
              <div v-if="venueForm.imagePreview" class="mb-3">
                <img :src="venueForm.imagePreview" alt="Prévisualisation" class="w-32 h-32 object-cover rounded-lg">
                <button type="button" @click="removeImage" class="mt-2 text-sm text-red-600 hover:text-red-800">
                  Supprimer l'image
                </button>
              </div>

              <!-- Upload d'image -->
              <input
                ref="imageInput"
                type="file"
                accept="image/*"
                @change="handleImageSelect"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                style="--tw-ring-color: #272d63;">
              <p class="text-xs text-gray-500 mt-1">PNG, JPG, GIF, WEBP jusqu'à 5MB</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Adresse *</label>
              <input type="text" v-model="venueForm.address" required
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                     style="--tw-ring-color: #272d63;">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Ville *</label>
              <input type="text" v-model="venueForm.city" required
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                     style="--tw-ring-color: #272d63;">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Code postal</label>
              <input type="text" v-model="venueForm.postal_code"
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                     style="--tw-ring-color: #272d63;">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Pays</label>
              <input type="text" v-model="venueForm.country"
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                     style="--tw-ring-color: #272d63;">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Capacité *</label>
              <input type="number" v-model="venueForm.capacity" required min="1"
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                     style="--tw-ring-color: #272d63;">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
              <select v-model="venueForm.status" 
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                      style="--tw-ring-color: #272d63;">
                <option value="active">Actif</option>
                <option value="inactive">Inactif</option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
              <input type="tel" v-model="venueForm.phone"
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                     style="--tw-ring-color: #272d63;">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
              <input type="email" v-model="venueForm.email"
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                     style="--tw-ring-color: #272d63;">
            </div>
          </div>

          <div class="flex space-x-3 mt-6">
            <button type="submit" 
                    class="flex-1 text-white py-2 px-4 rounded-lg font-medium transition-colors duration-200"
                    style="background-color: #272d63;"
                    @mouseover="$event.currentTarget.style.backgroundColor = '#fab511'"
                    @mouseleave="$event.currentTarget.style.backgroundColor = '#272d63'">
              {{ showCreateModal ? 'Créer' : 'Modifier' }}
            </button>
            <button type="button" @click="closeModals"
                    class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-lg font-medium hover:bg-gray-400 transition-colors duration-200">
              Annuler
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'

export default {
  name: 'VenueManagement',
  setup() {
    const loading = ref(false)
    const showCreateModal = ref(false)
    const showEditModal = ref(false)
    const venues = ref([])
    const editingVenue = ref(null)
    const pagination = ref(null)
    
    const filters = reactive({
      search: '',
      city: '',
      status: ''
    })
    
    const venueForm = reactive({
      name: '',
      description: '',
      address: '',
      city: '',
      postal_code: '',
      country: 'Gabon',
      capacity: 100,
      phone: '',
      email: '',
      image: {},
      imagePreview: null,
      status: 'active'
    })

    const imageInput = ref(null)

    // Computed
    const filteredVenues = computed(() => {
      let filtered = venues.value
      
      if (filters.search) {
        filtered = filtered.filter(venue => 
          venue.name.toLowerCase().includes(filters.search.toLowerCase()) ||
          venue.address.toLowerCase().includes(filters.search.toLowerCase())
        )
      }
      
      if (filters.city) {
        filtered = filtered.filter(venue => venue.city === filters.city)
      }
      
      if (filters.status) {
        filtered = filtered.filter(venue => venue.status === filters.status)
      }
      
      return filtered
    })
    
    const cities = computed(() => {
      if (!Array.isArray(venues.value) || venues.value.length === 0) {
        return []
      }
      return [...new Set(venues.value.map(venue => venue.city))].sort()
    })

    // Methods
    const loadVenues = async () => {
      loading.value = true
      try {
        const params = new URLSearchParams({
          ...(filters.search && { search: filters.search }),
          ...(filters.city && { city: filters.city }),
          ...(filters.status && { status: filters.status })
        })
        
        const response = await fetch(`/api/v1/admin/venues?${params}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        
        if (response.ok) {
          const data = await response.json()
          if (data.success) {
            // Handle paginated data structure
            const venuesData = data.data?.data || data.venues?.data || data.data || []
            venues.value = venuesData
            
            // Store pagination info if needed
            if (data.data?.total) {
              pagination.value = {
                total: data.data.total,
                per_page: data.data.per_page,
                current_page: data.data.current_page,
                last_page: data.data.last_page
              }
            }
          }
        } else {
          loadMockData()
        }
      } catch (error) {
        console.log('API non disponible, utilisation des données simulées')
        loadMockData()
      } finally {
        loading.value = false
      }
    }
    
    const loadMockData = () => {
      if (!venues.value) {
        venues.value = []
      }
      venues.value = [
        {
          id: 1,
          name: 'Centre de Conférences Mbolo',
          description: 'Centre moderne pour événements d\'entreprise',
          address: 'Avenue de l\'Indépendance',
          city: 'Libreville',
          postal_code: 'BP 123',
          country: 'Gabon',
          capacity: 500,
          phone: '+241 01 23 45 67',
          email: 'contact@mbolo.ga',
          image: 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=400',
          status: 'active',
          events_count: 15,
          created_at: new Date()
        },
        {
          id: 2,
          name: 'Salle Polyvalente Akanda',
          description: 'Espace polyvalent pour tous types d\'événements',
          address: 'Quartier Akanda',
          city: 'Akanda',
          postal_code: 'BP 456',
          country: 'Gabon',
          capacity: 300,
          phone: '+241 01 34 56 78',
          email: 'akanda@venues.ga',
          image: 'https://images.unsplash.com/photo-1511578314322-379afb476865?w=400',
          status: 'active',
          events_count: 8,
          created_at: new Date(Date.now() - 86400000)
        },
        {
          id: 3,
          name: 'Stade Omnisports',
          description: 'Grand stade pour événements sportifs et concerts',
          address: 'Zone Industrielle Owendo',
          city: 'Owendo',
          postal_code: 'BP 789',
          country: 'Gabon',
          capacity: 5000,
          phone: '+241 01 45 67 89',
          email: 'stade@owendo.ga',
          image: 'https://images.unsplash.com/photo-1459865264687-595d652de67e?w=400',
          status: 'active',
          events_count: 12,
          created_at: new Date(Date.now() - 172800000)
        },
        {
          id: 4,
          name: 'Salle des Fêtes Port-Gentil',
          description: 'Salle traditionnelle rénovée',
          address: 'Centre-ville',
          city: 'Port-Gentil',
          postal_code: 'BP 012',
          country: 'Gabon',
          capacity: 200,
          phone: '+241 01 56 78 90',
          email: 'fetes@portgentil.ga',
          image: null,
          status: 'inactive',
          events_count: 3,
          created_at: new Date(Date.now() - 259200000)
        }
      ]
    }
    
    const createVenue = async () => {
      try {
        // Créer un FormData pour l'upload de fichier
        const formData = new FormData()

        // Ajouter tous les champs du formulaire
        formData.append('name', venueForm.name)
        if (venueForm.description) formData.append('description', venueForm.description)
        formData.append('address', venueForm.address)
        formData.append('city', venueForm.city)
        if (venueForm.postal_code) formData.append('postal_code', venueForm.postal_code)
        if (venueForm.country) formData.append('country', venueForm.country)
        if (venueForm.capacity) formData.append('capacity', venueForm.capacity)
        if (venueForm.phone) formData.append('phone', venueForm.phone)
        if (venueForm.email) formData.append('email', venueForm.email)
        if (venueForm.status) formData.append('status', venueForm.status)

        // Gérer l'image
        if (venueForm.image.url) {
          formData.append('image_url', venueForm.image.url)
        } else if (venueForm.image.file) {
          // Si on a un vrai fichier, l'ajouter
          formData.append('cover_image', venueForm.image.file)
        }

        const response = await fetch('/api/v1/admin/venues', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: formData
        })
        
        if (response.ok) {
          const data = await response.json()
          if (data.success) {
            closeModals()
            loadVenues()
          }
        } else {
          // Simulation création
          const newVenue = {
            id: Date.now(),
            ...venueForm,
            events_count: 0,
            created_at: new Date()
          }
          venues.value.unshift(newVenue)
          closeModals()
        }
      } catch (error) {
        console.log('API non disponible, ajout local simulé')
        const newVenue = {
          id: Date.now(),
          ...venueForm,
          events_count: 0,
          created_at: new Date()
        }
        venues.value.unshift(newVenue)
        closeModals()
      }
    }
    
    const editVenue = (venue) => {
      editingVenue.value = venue

      // Préparer l'image preview
      let imagePreview = null
      if (venue.image_url) {
        imagePreview = venue.image_url
      } else if (venue.image_file) {
        imagePreview = `/storage/${venue.image_file}`
      }

      Object.assign(venueForm, {
        ...venue,
        image: {},
        imagePreview: imagePreview
      })
      showEditModal.value = true
    }
    
    const updateVenue = async () => {
      try {
        // Créer un FormData pour l'upload de fichier
        const formData = new FormData()

        // Ajouter tous les champs du formulaire
        formData.append('name', venueForm.name)
        if (venueForm.description) formData.append('description', venueForm.description)
        formData.append('address', venueForm.address)
        formData.append('city', venueForm.city)
        if (venueForm.postal_code) formData.append('postal_code', venueForm.postal_code)
        if (venueForm.country) formData.append('country', venueForm.country)
        if (venueForm.capacity) formData.append('capacity', venueForm.capacity)
        if (venueForm.phone) formData.append('phone', venueForm.phone)
        if (venueForm.email) formData.append('email', venueForm.email)
        if (venueForm.status) formData.append('status', venueForm.status)

        // Gérer l'image
        if (venueForm.image.url) {
          formData.append('image_url', venueForm.image.url)
        } else if (venueForm.image.file) {
          // Si on a un vrai fichier, l'ajouter
          formData.append('cover_image', venueForm.image.file)
        }

        // Laravel ne supporte pas vraiment PUT avec FormData, on utilise POST avec _method
        formData.append('_method', 'PUT')

        const response = await fetch(`/api/v1/admin/venues/${editingVenue.value.id}`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: formData
        })
        
        if (response.ok) {
          const data = await response.json()
          if (data.success) {
            closeModals()
            loadVenues()
          }
        } else {
          // Simulation mise à jour
          const index = venues.value.findIndex(v => v.id === editingVenue.value.id)
          if (index !== -1) {
            venues.value[index] = { ...venues.value[index], ...venueForm }
          }
          closeModals()
        }
      } catch (error) {
        console.log('API non disponible, mise à jour locale simulée')
        const index = venues.value.findIndex(v => v.id === editingVenue.value.id)
        if (index !== -1) {
          venues.value[index] = { ...venues.value[index], ...venueForm }
        }
        closeModals()
      }
    }
    
    const deleteVenue = async (venue) => {
      if (!confirm(`Êtes-vous sûr de vouloir supprimer le lieu "${venue.name}" ?`)) return
      
      try {
        const response = await fetch(`/api/v1/admin/venues/${venue.id}`, {
          method: 'DELETE',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        
        if (response.ok) {
          loadVenues()
        } else {
          // Simulation suppression
          venues.value = venues.value.filter(v => v.id !== venue.id)
        }
      } catch (error) {
        console.log('API non disponible, suppression locale simulée')
        venues.value = venues.value.filter(v => v.id !== venue.id)
      }
    }
    
    const closeModals = () => {
      showCreateModal.value = false
      showEditModal.value = false
      editingVenue.value = null
      Object.assign(venueForm, {
        name: '',
        description: '',
        address: '',
        city: '',
        postal_code: '',
        country: 'Gabon',
        capacity: 100,
        phone: '',
        email: '',
        image: {},
        imagePreview: null,
        status: 'active'
      })
      if (imageInput.value) {
        imageInput.value.value = ''
      }
    }

    const handleImageSelect = (event) => {
      const file = event.target.files[0]
      if (file) {
        // Validation de la taille (5MB max)
        if (file.size > 5 * 1024 * 1024) {
          alert('L\'image ne doit pas dépasser 5MB')
          return
        }

        // Stocker le fichier
        venueForm.image.file = file

        // Créer une prévisualisation
        const reader = new FileReader()
        reader.onload = (e) => {
          venueForm.imagePreview = e.target.result
        }
        reader.readAsDataURL(file)
      }
    }

    const removeImage = () => {
      venueForm.image = {}
      venueForm.imagePreview = null
      if (imageInput.value) {
        imageInput.value.value = ''
      }
    }

    const getVenueImageUrl = (venue) => {
      // Si c'est une URL externe directe
      if (venue.image_url && (venue.image_url.startsWith('http://') || venue.image_url.startsWith('https://'))) {
        return venue.image_url
      }

      // Si c'est un fichier stocké localement
      // Utiliser la même logique que dans editVenue qui fonctionne
      if (venue.image_file) {
        return `/storage/${venue.image_file}`
      }

      // Fallback sur venue.image (pour compatibilité avec les données mock)
      if (venue.image) {
        if (venue.image.startsWith('http://') || venue.image.startsWith('https://')) {
          return venue.image
        }
        return `/storage/${venue.image}`
      }

      return null
    }

    const handleImageError = (event) => {
      // En cas d'erreur de chargement, on cache l'image
      event.target.style.display = 'none'
    }

    // Lifecycle
    onMounted(() => {
      loadVenues()
    })

    return {
      loading,
      showCreateModal,
      showEditModal,
      venues,
      filters,
      venueForm,
      filteredVenues,
      cities,
      loadVenues,
      createVenue,
      editVenue,
      updateVenue,
      deleteVenue,
      closeModals,
      handleImageSelect,
      removeImage,
      getVenueImageUrl,
      handleImageError,
      imageInput
    }
  }
}
</script>

<style scoped>
.venue-management {
  font-family: 'Inter', sans-serif;
}

.max-h-90vh {
  max-height: 90vh;
}
</style>