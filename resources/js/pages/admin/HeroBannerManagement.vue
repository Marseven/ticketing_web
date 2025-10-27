<template>
  <div class="hero-banner-management p-6">
    <!-- Header -->
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold" style="color: #272d63;">Gestion du Hero Banner</h1>
        <p class="text-gray-600 mt-1">Gérez les images/vidéos de la bannière d'accueil</p>
      </div>
      <button @click="showCreateModal = true"
              class="text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200"
              style="background-color: #272d63;"
              @mouseover="$event.currentTarget.style.backgroundColor = '#fab511'"
              @mouseleave="$event.currentTarget.style.backgroundColor = '#272d63'">
        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        Nouveau Hero Banner
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
          <input type="text" v-model="filters.search"
                 placeholder="Titre..."
                 class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                 style="--tw-ring-color: #272d63;">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
          <select v-model="filters.type"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                  style="--tw-ring-color: #272d63;">
            <option value="">Tous les types</option>
            <option value="image">Image</option>
            <option value="video">Vidéo</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
          <select v-model="filters.is_active"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                  style="--tw-ring-color: #272d63;">
            <option value="">Tous les statuts</option>
            <option value="true">Actif</option>
            <option value="false">Inactif</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center items-center h-64">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2" style="border-color: #272d63;"></div>
    </div>

    <!-- Hero Banners Grid -->
    <div v-else>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="heroBanner in filteredHeroBanners" :key="heroBanner.id"
             class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition-shadow duration-200">
          <!-- Media Preview -->
          <div class="relative h-48 bg-gray-200">
            <img v-if="heroBanner.type === 'image' && heroBanner.media_url"
                 :src="heroBanner.media_url"
                 :alt="heroBanner.title || 'Hero Banner'"
                 class="w-full h-full object-cover">
            <video v-else-if="heroBanner.type === 'video' && heroBanner.media_url"
                   :src="heroBanner.media_url"
                   class="w-full h-full object-cover"
                   muted>
            </video>
            <div v-else class="flex items-center justify-center h-full">
              <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
            </div>

            <!-- Status Badge -->
            <div class="absolute top-2 right-2">
              <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                    :class="heroBanner.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                {{ heroBanner.is_active ? 'Actif' : 'Inactif' }}
              </span>
            </div>

            <!-- Type Badge -->
            <div class="absolute top-2 left-2">
              <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                    :class="heroBanner.type === 'image' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'">
                {{ heroBanner.type === 'image' ? 'Image' : 'Vidéo' }}
              </span>
            </div>
          </div>

          <!-- Info -->
          <div class="p-4">
            <h3 class="text-lg font-bold mb-2" style="color: #272d63;">
              {{ heroBanner.title || 'Sans titre' }}
            </h3>

            <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
              <span>Ordre: {{ heroBanner.display_order }}</span>
              <span>{{ formatDate(heroBanner.created_at) }}</span>
            </div>

            <!-- Actions -->
            <div class="flex space-x-2">
              <button @click="toggleHeroBannerStatus(heroBanner)"
                      class="flex-1 text-sm px-3 py-2 rounded transition-colors duration-200"
                      :class="heroBanner.is_active ? 'bg-orange-100 text-orange-800 hover:bg-orange-200' : 'bg-green-100 text-green-800 hover:bg-green-200'">
                {{ heroBanner.is_active ? 'Désactiver' : 'Activer' }}
              </button>
              <button @click="editHeroBanner(heroBanner)"
                      class="flex-1 text-sm px-3 py-2 rounded transition-colors duration-200"
                      style="color: #272d63; background-color: rgba(39, 45, 99, 0.1);"
                      @mouseover="$event.currentTarget.style.backgroundColor = '#fab511'; $event.currentTarget.style.color = '#fff'"
                      @mouseleave="$event.currentTarget.style.backgroundColor = 'rgba(39, 45, 99, 0.1)'; $event.currentTarget.style.color = '#272d63'">
                Modifier
              </button>
              <button @click="deleteHeroBanner(heroBanner)"
                      class="text-sm text-red-600 hover:text-red-800 px-3 py-2 bg-red-100 hover:bg-red-200 rounded transition-colors duration-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="heroBanners.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Aucun hero banner</h3>
        <p class="mt-1 text-sm text-gray-500">Commencez par créer votre premier hero banner.</p>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showCreateModal || showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-bold mb-4" style="color: #272d63;">
          {{ showCreateModal ? 'Nouveau Hero Banner' : 'Modifier le Hero Banner' }}
        </h3>

        <form @submit.prevent="showCreateModal ? createHeroBanner() : updateHeroBanner()">
          <div class="space-y-4">
            <!-- Type Selection -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Type de média *</label>
              <div class="flex space-x-4">
                <label class="flex items-center cursor-pointer">
                  <input type="radio" v-model="heroBannerForm.type" value="image" class="mr-2">
                  <span>Image</span>
                </label>
                <label class="flex items-center cursor-pointer">
                  <input type="radio" v-model="heroBannerForm.type" value="video" class="mr-2">
                  <span>Vidéo</span>
                </label>
              </div>
            </div>

            <!-- Upload Options -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Source du média *</label>
              <div class="flex space-x-4 mb-4">
                <label class="flex items-center cursor-pointer">
                  <input type="radio" v-model="uploadMode" value="file" class="mr-2">
                  <span>Télécharger un fichier</span>
                </label>
                <label class="flex items-center cursor-pointer">
                  <input type="radio" v-model="uploadMode" value="url" class="mr-2">
                  <span>URL externe</span>
                </label>
              </div>

              <!-- File Upload -->
              <div v-if="uploadMode === 'file'" class="border-2 border-dashed border-gray-300 rounded-lg p-4">
                <input type="file" ref="mediaInput" @change="handleMediaUpload"
                       :accept="heroBannerForm.type === 'image' ? 'image/*' : 'video/*'"
                       class="hidden" id="media-upload">
                <label for="media-upload" class="cursor-pointer">
                  <div v-if="mediaPreview" class="relative">
                    <img v-if="heroBannerForm.type === 'image'" :src="mediaPreview" alt="Preview"
                         class="w-full h-48 object-cover rounded-lg">
                    <video v-else :src="mediaPreview" class="w-full h-48 object-cover rounded-lg"
                           controls>
                    </video>
                    <button type="button" @click.stop="removeMedia"
                            class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                      </svg>
                    </button>
                  </div>
                  <div v-else class="text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <p class="mt-1 text-sm text-gray-600">
                      Cliquez pour sélectionner {{ heroBannerForm.type === 'image' ? 'une image' : 'une vidéo' }}
                    </p>
                    <p class="text-xs text-gray-500">Max: 10MB</p>
                  </div>
                </label>
              </div>

              <!-- URL Input -->
              <div v-else>
                <input type="url" v-model="heroBannerForm.media_url"
                       placeholder="https://..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                       style="--tw-ring-color: #272d63;">
                <p class="text-xs text-gray-500 mt-1">URL de l'image ou vidéo externe</p>
              </div>
            </div>

            <!-- Title -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Titre (optionnel)</label>
              <input type="text" v-model="heroBannerForm.title"
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                     style="--tw-ring-color: #272d63;">
            </div>

            <!-- Display Order -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Ordre d'affichage</label>
              <input type="number" v-model="heroBannerForm.display_order" min="0"
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                     style="--tw-ring-color: #272d63;">
            </div>

            <!-- Active Status -->
            <div class="flex items-center">
              <input type="checkbox" v-model="heroBannerForm.is_active" id="is_active"
                     class="h-4 w-4 rounded border-gray-300"
                     style="color: #272d63;">
              <label for="is_active" class="ml-2 block text-sm text-gray-700">
                Hero banner actif
              </label>
            </div>
          </div>

          <div class="flex space-x-3 mt-6">
            <button type="submit"
                    :disabled="uploading"
                    class="flex-1 text-white py-2 px-4 rounded-lg font-medium transition-colors duration-200 disabled:opacity-50"
                    style="background-color: #272d63;"
                    @mouseover="!uploading && ($event.currentTarget.style.backgroundColor = '#fab511')"
                    @mouseleave="!uploading && ($event.currentTarget.style.backgroundColor = '#272d63')">
              <span v-if="uploading">Envoi en cours...</span>
              <span v-else>{{ showCreateModal ? 'Créer' : 'Modifier' }}</span>
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
  name: 'HeroBannerManagement',
  setup() {
    const loading = ref(false)
    const uploading = ref(false)
    const showCreateModal = ref(false)
    const showEditModal = ref(false)
    const heroBanners = ref([])
    const editingHeroBanner = ref(null)
    const mediaInput = ref(null)
    const mediaPreview = ref(null)
    const mediaFile = ref(null)
    const uploadMode = ref('file')

    const filters = reactive({
      search: '',
      type: '',
      is_active: ''
    })

    const heroBannerForm = reactive({
      title: '',
      type: 'image',
      media_url: '',
      display_order: 0,
      is_active: true
    })

    // Computed
    const filteredHeroBanners = computed(() => {
      let filtered = heroBanners.value

      if (filters.search) {
        filtered = filtered.filter(heroBanner =>
          heroBanner.title?.toLowerCase().includes(filters.search.toLowerCase())
        )
      }

      if (filters.type) {
        filtered = filtered.filter(heroBanner => heroBanner.type === filters.type)
      }

      if (filters.is_active !== '') {
        const isActive = filters.is_active === 'true'
        filtered = filtered.filter(heroBanner => heroBanner.is_active === isActive)
      }

      return filtered
    })

    // Methods
    const getCsrfToken = () => {
      return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    }

    const getHeaders = () => {
      return {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken()
      }
    }

    const loadHeroBanners = async () => {
      loading.value = true
      try {
        const params = new URLSearchParams()
        if (filters.type) params.append('type', filters.type)
        if (filters.is_active !== '') params.append('is_active', filters.is_active)

        const response = await fetch(`/api/v1/admin/hero-banners?${params}`, {
          headers: getHeaders()
        })

        if (response.ok) {
          const data = await response.json()
          if (data.success) {
            heroBanners.value = data.data
          }
        }
      } catch (error) {
        console.error('Erreur lors du chargement des hero banners:', error)
      } finally {
        loading.value = false
      }
    }

    const handleMediaUpload = (event) => {
      const file = event.target.files[0]
      if (file) {
        if (file.size > 10240 * 1024) {
          alert('Le fichier ne doit pas dépasser 10MB')
          return
        }

        mediaFile.value = file
        const reader = new FileReader()
        reader.onload = (e) => {
          mediaPreview.value = e.target.result
        }
        reader.readAsDataURL(file)
      }
    }

    const removeMedia = () => {
      mediaPreview.value = null
      mediaFile.value = null
      if (mediaInput.value) {
        mediaInput.value.value = ''
      }
    }

    const createHeroBanner = async () => {
      if (uploadMode.value === 'file' && !mediaFile.value) {
        alert('Veuillez sélectionner un fichier')
        return
      }
      if (uploadMode.value === 'url' && !heroBannerForm.media_url) {
        alert('Veuillez fournir une URL')
        return
      }

      uploading.value = true
      try {
        const formData = new FormData()
        formData.append('type', heroBannerForm.type)
        formData.append('title', heroBannerForm.title || '')
        formData.append('display_order', heroBannerForm.display_order)
        formData.append('is_active', heroBannerForm.is_active ? '1' : '0')

        if (uploadMode.value === 'file') {
          formData.append('media_file', mediaFile.value)
        } else {
          formData.append('media_url', heroBannerForm.media_url)
        }

        const response = await fetch('/api/v1/admin/hero-banners', {
          method: 'POST',
          headers: getHeaders(),
          body: formData
        })

        if (response.ok) {
          const data = await response.json()
          if (data.success) {
            closeModals()
            loadHeroBanners()
          }
        }
      } catch (error) {
        console.error('Erreur lors de la création:', error)
      } finally {
        uploading.value = false
      }
    }

    const editHeroBanner = (heroBanner) => {
      editingHeroBanner.value = heroBanner
      heroBannerForm.title = heroBanner.title || ''
      heroBannerForm.type = heroBanner.type
      heroBannerForm.display_order = heroBanner.display_order
      heroBannerForm.is_active = heroBanner.is_active
      heroBannerForm.media_url = heroBanner.media_url || ''

      if (heroBanner.media_url && heroBanner.media_url.startsWith('http')) {
        uploadMode.value = 'url'
      } else {
        uploadMode.value = 'file'
        mediaPreview.value = heroBanner.media_url || null
      }

      showEditModal.value = true
    }

    const updateHeroBanner = async () => {
      uploading.value = true
      try {
        const formData = new FormData()
        formData.append('type', heroBannerForm.type)
        formData.append('title', heroBannerForm.title || '')
        formData.append('display_order', heroBannerForm.display_order)
        formData.append('is_active', heroBannerForm.is_active ? '1' : '0')

        if (uploadMode.value === 'file' && mediaFile.value) {
          formData.append('media_file', mediaFile.value)
        } else if (uploadMode.value === 'url') {
          formData.append('media_url', heroBannerForm.media_url)
        }

        const response = await fetch(`/api/v1/admin/hero-banners/${editingHeroBanner.value.id}`, {
          method: 'POST',
          headers: getHeaders(),
          body: formData
        })

        if (response.ok) {
          const data = await response.json()
          if (data.success) {
            closeModals()
            loadHeroBanners()
          }
        }
      } catch (error) {
        console.error('Erreur lors de la mise à jour:', error)
      } finally {
        uploading.value = false
      }
    }

    const toggleHeroBannerStatus = async (heroBanner) => {
      try {
        const response = await fetch(`/api/v1/admin/hero-banners/${heroBanner.id}/toggle-active`, {
          method: 'POST',
          headers: getHeaders()
        })

        if (response.ok) {
          const data = await response.json()
          if (data.success) {
            loadHeroBanners()
          }
        }
      } catch (error) {
        console.error('Erreur lors du changement de statut:', error)
      }
    }

    const deleteHeroBanner = async (heroBanner) => {
      if (!confirm(`Êtes-vous sûr de vouloir supprimer ce hero banner ?`)) return

      try {
        const response = await fetch(`/api/v1/admin/hero-banners/${heroBanner.id}`, {
          method: 'DELETE',
          headers: getHeaders()
        })

        if (response.ok) {
          loadHeroBanners()
        }
      } catch (error) {
        console.error('Erreur lors de la suppression:', error)
      }
    }

    const closeModals = () => {
      showCreateModal.value = false
      showEditModal.value = false
      editingHeroBanner.value = null
      Object.assign(heroBannerForm, {
        title: '',
        type: 'image',
        media_url: '',
        display_order: 0,
        is_active: true
      })
      uploadMode.value = 'file'
      removeMedia()
    }

    const formatDate = (date) => {
      if (!date) return ''
      return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      })
    }

    // Lifecycle
    onMounted(() => {
      loadHeroBanners()
    })

    return {
      loading,
      uploading,
      showCreateModal,
      showEditModal,
      heroBanners,
      filters,
      heroBannerForm,
      filteredHeroBanners,
      mediaInput,
      mediaPreview,
      uploadMode,
      loadHeroBanners,
      handleMediaUpload,
      removeMedia,
      createHeroBanner,
      editHeroBanner,
      updateHeroBanner,
      toggleHeroBannerStatus,
      deleteHeroBanner,
      closeModals,
      formatDate
    }
  }
}
</script>

<style scoped>
.hero-banner-management {
  font-family: 'Inter', 'Myriad Pro', sans-serif;
}
</style>
