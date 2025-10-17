<template>
  <div class="banner-management p-6">
    <!-- Header -->
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold" style="color: #272d63;">Gestion des Bannières Publicitaires</h1>
        <p class="text-gray-600 mt-1">Gérez les bannières publicitaires affichées sur le site</p>
      </div>
      <button @click="showCreateModal = true"
              class="text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200"
              style="background-color: #272d63;"
              @mouseover="$event.currentTarget.style.backgroundColor = '#fab511'"
              @mouseleave="$event.currentTarget.style.backgroundColor = '#272d63'">
        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        Nouvelle Bannière
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
          <input type="text" v-model="filters.search"
                 placeholder="Titre de la bannière..."
                 class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                 style="--tw-ring-color: #272d63;">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Position</label>
          <select v-model="filters.position"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                  style="--tw-ring-color: #272d63;">
            <option value="">Toutes les positions</option>
            <option value="home">Page d'accueil</option>
            <option value="events">Page des événements</option>
            <option value="checkout">Page de paiement</option>
            <option value="all">Toutes les pages</option>
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
        <div class="flex items-end">
          <button @click="loadBanners"
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

    <!-- Banners Grid -->
    <div v-else>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="banner in filteredBanners" :key="banner.id"
             class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition-shadow duration-200">
          <!-- Banner Image -->
          <div class="relative h-48 bg-gray-200">
            <img v-if="banner.image_path"
                 :src="`/storage/${banner.image_path}`"
                 :alt="banner.title"
                 class="w-full h-full object-cover">
            <div v-else class="flex items-center justify-center h-full">
              <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
            </div>

            <!-- Status Badge -->
            <div class="absolute top-2 right-2">
              <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                    :class="banner.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                {{ banner.is_active ? 'Actif' : 'Inactif' }}
              </span>
            </div>

            <!-- Position Badge -->
            <div class="absolute top-2 left-2">
              <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                {{ getPositionLabel(banner.position) }}
              </span>
            </div>
          </div>

          <!-- Banner Info -->
          <div class="p-4">
            <h3 class="text-lg font-bold mb-2" style="color: #272d63;">{{ banner.title }}</h3>
            <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ banner.description || 'Aucune description' }}</p>

            <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
              <span>Ordre: {{ banner.order }}</span>
              <span>{{ formatDate(banner.created_at) }}</span>
            </div>

            <div v-if="banner.link_url" class="text-xs text-gray-500 mb-3 truncate">
              <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
              </svg>
              {{ banner.link_url }}
            </div>

            <!-- Actions -->
            <div class="flex space-x-2">
              <button @click="toggleBannerStatus(banner)"
                      class="flex-1 text-sm px-3 py-2 rounded transition-colors duration-200"
                      :class="banner.is_active ? 'bg-orange-100 text-orange-800 hover:bg-orange-200' : 'bg-green-100 text-green-800 hover:bg-green-200'">
                {{ banner.is_active ? 'Désactiver' : 'Activer' }}
              </button>
              <button @click="editBanner(banner)"
                      class="flex-1 text-sm px-3 py-2 rounded transition-colors duration-200"
                      style="color: #272d63; background-color: rgba(39, 45, 99, 0.1);"
                      @mouseover="$event.currentTarget.style.backgroundColor = '#fab511'; $event.currentTarget.style.color = '#fff'"
                      @mouseleave="$event.currentTarget.style.backgroundColor = 'rgba(39, 45, 99, 0.1)'; $event.currentTarget.style.color = '#272d63'">
                Modifier
              </button>
              <button @click="deleteBanner(banner)"
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
      <div v-if="banners.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Aucune bannière</h3>
        <p class="mt-1 text-sm text-gray-500">Commencez par créer votre première bannière publicitaire.</p>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showCreateModal || showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-bold mb-4" style="color: #272d63;">
          {{ showCreateModal ? 'Nouvelle Bannière' : 'Modifier la Bannière' }}
        </h3>

        <form @submit.prevent="showCreateModal ? createBanner() : updateBanner()">
          <div class="space-y-4">
            <!-- Image Upload -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Image * (max 2MB)
              </label>
              <div class="border-2 border-dashed border-gray-300 rounded-lg p-4">
                <input type="file" ref="imageInput" @change="handleImageUpload" accept="image/*"
                       class="hidden" id="image-upload">
                <label for="image-upload" class="cursor-pointer">
                  <div v-if="imagePreview" class="relative">
                    <img :src="imagePreview" alt="Preview" class="w-full h-48 object-cover rounded-lg">
                    <button type="button" @click.stop="removeImage"
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
                    <p class="mt-1 text-sm text-gray-600">Cliquez pour sélectionner une image</p>
                  </div>
                </label>
              </div>
            </div>

            <!-- Title -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Titre *</label>
              <input type="text" v-model="bannerForm.title" required
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                     style="--tw-ring-color: #272d63;">
            </div>

            <!-- Description -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
              <textarea v-model="bannerForm.description" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                        style="--tw-ring-color: #272d63;"></textarea>
            </div>

            <!-- Link URL -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">URL de redirection</label>
              <input type="url" v-model="bannerForm.link_url"
                     placeholder="https://..."
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                     style="--tw-ring-color: #272d63;">
            </div>

            <div class="grid grid-cols-2 gap-4">
              <!-- Position -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Position *</label>
                <select v-model="bannerForm.position" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                        style="--tw-ring-color: #272d63;">
                  <option value="home">Page d'accueil</option>
                  <option value="events">Page des événements</option>
                  <option value="checkout">Page de paiement</option>
                  <option value="all">Toutes les pages</option>
                </select>
              </div>

              <!-- Order -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Ordre</label>
                <input type="number" v-model="bannerForm.order" min="0"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                       style="--tw-ring-color: #272d63;">
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <!-- Start Date -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date de début</label>
                <input type="datetime-local" v-model="bannerForm.start_date"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                       style="--tw-ring-color: #272d63;">
              </div>

              <!-- End Date -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date de fin</label>
                <input type="datetime-local" v-model="bannerForm.end_date"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                       style="--tw-ring-color: #272d63;">
              </div>
            </div>

            <!-- Active Status -->
            <div class="flex items-center">
              <input type="checkbox" v-model="bannerForm.is_active" id="is_active"
                     class="h-4 w-4 rounded border-gray-300"
                     style="color: #272d63;">
              <label for="is_active" class="ml-2 block text-sm text-gray-700">
                Bannière active
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
  name: 'BannerManagement',
  setup() {
    const loading = ref(false)
    const uploading = ref(false)
    const showCreateModal = ref(false)
    const showEditModal = ref(false)
    const banners = ref([])
    const editingBanner = ref(null)
    const imageInput = ref(null)
    const imagePreview = ref(null)
    const imageFile = ref(null)

    const filters = reactive({
      search: '',
      position: '',
      is_active: ''
    })

    const bannerForm = reactive({
      title: '',
      description: '',
      link_url: '',
      position: 'home',
      order: 0,
      is_active: true,
      start_date: null,
      end_date: null
    })

    // Computed
    const filteredBanners = computed(() => {
      let filtered = banners.value

      if (filters.search) {
        filtered = filtered.filter(banner =>
          banner.title.toLowerCase().includes(filters.search.toLowerCase()) ||
          banner.description?.toLowerCase().includes(filters.search.toLowerCase())
        )
      }

      if (filters.position) {
        filtered = filtered.filter(banner => banner.position === filters.position)
      }

      if (filters.is_active !== '') {
        const isActive = filters.is_active === 'true'
        filtered = filtered.filter(banner => banner.is_active === isActive)
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

    const loadBanners = async () => {
      loading.value = true
      try {
        const params = new URLSearchParams()
        if (filters.position) params.append('position', filters.position)
        if (filters.is_active !== '') params.append('is_active', filters.is_active)

        const response = await fetch(`/api/v1/admin/banners?${params}`, {
          headers: getHeaders()
        })

        if (response.ok) {
          const data = await response.json()
          if (data.success) {
            banners.value = data.data
          }
        }
      } catch (error) {
        console.error('Erreur lors du chargement des bannières:', error)
      } finally {
        loading.value = false
      }
    }

    const handleImageUpload = (event) => {
      const file = event.target.files[0]
      if (file) {
        if (file.size > 2048 * 1024) {
          alert('L\'image ne doit pas dépasser 2MB')
          return
        }

        imageFile.value = file
        const reader = new FileReader()
        reader.onload = (e) => {
          imagePreview.value = e.target.result
        }
        reader.readAsDataURL(file)
      }
    }

    const removeImage = () => {
      imagePreview.value = null
      imageFile.value = null
      if (imageInput.value) {
        imageInput.value.value = ''
      }
    }

    const createBanner = async () => {
      if (!imageFile.value) {
        alert('Veuillez sélectionner une image')
        return
      }

      uploading.value = true
      try {
        const formData = new FormData()
        formData.append('image', imageFile.value)
        formData.append('title', bannerForm.title)
        formData.append('description', bannerForm.description || '')
        formData.append('link_url', bannerForm.link_url || '')
        formData.append('position', bannerForm.position)
        formData.append('order', bannerForm.order)
        formData.append('is_active', bannerForm.is_active ? '1' : '0')
        if (bannerForm.start_date) formData.append('start_date', bannerForm.start_date)
        if (bannerForm.end_date) formData.append('end_date', bannerForm.end_date)

        const response = await fetch('/api/v1/admin/banners', {
          method: 'POST',
          headers: {
            ...getHeaders(),
            // Ne pas inclure Content-Type pour FormData
          },
          body: formData
        })

        if (response.ok) {
          const data = await response.json()
          if (data.success) {
            closeModals()
            loadBanners()
          }
        }
      } catch (error) {
        console.error('Erreur lors de la création:', error)
      } finally {
        uploading.value = false
      }
    }

    const editBanner = (banner) => {
      editingBanner.value = banner
      bannerForm.title = banner.title
      bannerForm.description = banner.description || ''
      bannerForm.link_url = banner.link_url || ''
      bannerForm.position = banner.position
      bannerForm.order = banner.order
      bannerForm.is_active = banner.is_active
      bannerForm.start_date = banner.start_date ? banner.start_date.slice(0, 16) : null
      bannerForm.end_date = banner.end_date ? banner.end_date.slice(0, 16) : null
      imagePreview.value = banner.image_path ? `/storage/${banner.image_path}` : null
      showEditModal.value = true
    }

    const updateBanner = async () => {
      uploading.value = true
      try {
        const formData = new FormData()
        if (imageFile.value) {
          formData.append('image', imageFile.value)
        }
        formData.append('title', bannerForm.title)
        formData.append('description', bannerForm.description || '')
        formData.append('link_url', bannerForm.link_url || '')
        formData.append('position', bannerForm.position)
        formData.append('order', bannerForm.order)
        formData.append('is_active', bannerForm.is_active ? '1' : '0')
        if (bannerForm.start_date) formData.append('start_date', bannerForm.start_date)
        if (bannerForm.end_date) formData.append('end_date', bannerForm.end_date)

        const response = await fetch(`/api/v1/admin/banners/${editingBanner.value.id}`, {
          method: 'POST',
          headers: {
            ...getHeaders(),
            // Ne pas inclure Content-Type pour FormData
          },
          body: formData
        })

        if (response.ok) {
          const data = await response.json()
          if (data.success) {
            closeModals()
            loadBanners()
          }
        }
      } catch (error) {
        console.error('Erreur lors de la mise à jour:', error)
      } finally {
        uploading.value = false
      }
    }

    const toggleBannerStatus = async (banner) => {
      try {
        const response = await fetch(`/api/v1/admin/banners/${banner.id}/toggle-active`, {
          method: 'POST',
          headers: getHeaders()
        })

        if (response.ok) {
          const data = await response.json()
          if (data.success) {
            loadBanners()
          }
        }
      } catch (error) {
        console.error('Erreur lors du changement de statut:', error)
      }
    }

    const deleteBanner = async (banner) => {
      if (!confirm(`Êtes-vous sûr de vouloir supprimer la bannière "${banner.title}" ?`)) return

      try {
        const response = await fetch(`/api/v1/admin/banners/${banner.id}`, {
          method: 'DELETE',
          headers: getHeaders()
        })

        if (response.ok) {
          loadBanners()
        }
      } catch (error) {
        console.error('Erreur lors de la suppression:', error)
      }
    }

    const closeModals = () => {
      showCreateModal.value = false
      showEditModal.value = false
      editingBanner.value = null
      Object.assign(bannerForm, {
        title: '',
        description: '',
        link_url: '',
        position: 'home',
        order: 0,
        is_active: true,
        start_date: null,
        end_date: null
      })
      removeImage()
    }

    const getPositionLabel = (position) => {
      const labels = {
        home: 'Accueil',
        events: 'Événements',
        checkout: 'Paiement',
        all: 'Toutes les pages'
      }
      return labels[position] || position
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
      loadBanners()
    })

    return {
      loading,
      uploading,
      showCreateModal,
      showEditModal,
      banners,
      filters,
      bannerForm,
      filteredBanners,
      imageInput,
      imagePreview,
      loadBanners,
      handleImageUpload,
      removeImage,
      createBanner,
      editBanner,
      updateBanner,
      toggleBannerStatus,
      deleteBanner,
      closeModals,
      getPositionLabel,
      formatDate
    }
  }
}
</script>

<style scoped>
.banner-management {
  font-family: 'Inter', sans-serif;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
