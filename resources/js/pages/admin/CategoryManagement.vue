<template>
  <div class="category-management p-6">
    <!-- Header -->
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold" style="color: #272d63;">Gestion des Catégories</h1>
        <p class="text-gray-600 mt-1">Organisez et gérez les catégories d'événements</p>
      </div>
      <button @click="showCreateModal = true" 
              class="text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200"
              style="background-color: #272d63;"
              @mouseover="$event.currentTarget.style.backgroundColor = '#fab511'"
              @mouseleave="$event.currentTarget.style.backgroundColor = '#272d63'">
        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        Nouvelle Catégorie
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
          <input type="text" v-model="filters.search" 
                 placeholder="Nom de catégorie..."
                 class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                 style="--tw-ring-color: #272d63;">
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
          <button @click="loadCategories" 
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

    <!-- Categories Table -->
    <div v-else class="bg-white rounded-lg shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead style="background-color: #f8f9fa;">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Catégorie</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Événements</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Créé le</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="category in filteredCategories" :key="category.id" class="hover:bg-gray-50">
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <div class="w-10 h-10 rounded-lg flex items-center justify-center mr-3"
                       :style="{ backgroundColor: category.color + '20', color: category.color }">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path>
                    </svg>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-gray-900">{{ category.name }}</p>
                    <p class="text-sm text-gray-500">{{ category.slug }}</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <p class="text-sm text-gray-900">{{ category.description || 'Aucune description' }}</p>
              </td>
              <td class="px-6 py-4">
                <span class="text-sm font-medium" style="color: #272d63;">{{ category.events_count || 0 }} événements</span>
              </td>
              <td class="px-6 py-4">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                      :class="category.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                  {{ category.status === 'active' ? 'Actif' : 'Inactif' }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-500">
                {{ formatDate(category.created_at) }}
              </td>
              <td class="px-6 py-4">
                <div class="flex space-x-2">
                  <button @click="editCategory(category)" 
                          class="text-sm px-3 py-1 rounded transition-colors duration-200"
                          style="color: #272d63; background-color: rgba(39, 45, 99, 0.1);"
                          @mouseover="$event.currentTarget.style.backgroundColor = '#fab511'; $event.currentTarget.style.color = '#fff'"
                          @mouseleave="$event.currentTarget.style.backgroundColor = 'rgba(39, 45, 99, 0.1)'; $event.currentTarget.style.color = '#272d63'">
                    Modifier
                  </button>
                  <button @click="deleteCategory(category)" 
                          class="text-sm text-red-600 hover:text-red-800 px-3 py-1 bg-red-100 hover:bg-red-200 rounded transition-colors duration-200">
                    Supprimer
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-if="categories.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Aucune catégorie</h3>
        <p class="mt-1 text-sm text-gray-500">Commencez par créer votre première catégorie.</p>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.total > pagination.per_page" class="mt-6 flex justify-between items-center">
      <div class="text-sm text-gray-700">
        Affichage de {{ pagination.from }} à {{ pagination.to }} sur {{ pagination.total }} catégories
      </div>
      <div class="flex space-x-2">
        <button v-for="page in paginationPages" :key="page"
                @click="changePage(page)"
                class="px-3 py-2 text-sm rounded-lg transition-colors duration-200"
                :class="page === pagination.current_page ? 'text-white' : 'text-gray-700 hover:bg-gray-100'"
                :style="page === pagination.current_page ? { backgroundColor: '#272d63' } : {}">
          {{ page }}
        </button>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showCreateModal || showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-bold mb-4" style="color: #272d63;">
          {{ showCreateModal ? 'Nouvelle Catégorie' : 'Modifier la Catégorie' }}
        </h3>
        
        <form @submit.prevent="showCreateModal ? createCategory() : updateCategory()">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Nom *</label>
              <input type="text" v-model="categoryForm.name" required
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                     style="--tw-ring-color: #272d63;">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
              <textarea v-model="categoryForm.description" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                        style="--tw-ring-color: #272d63;"></textarea>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Couleur</label>
              <input type="color" v-model="categoryForm.color"
                     class="w-full h-10 border border-gray-300 rounded-lg">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
              <select v-model="categoryForm.status" 
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                      style="--tw-ring-color: #272d63;">
                <option value="active">Actif</option>
                <option value="inactive">Inactif</option>
              </select>
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
  name: 'CategoryManagement',
  setup() {
    const loading = ref(false)
    const showCreateModal = ref(false)
    const showEditModal = ref(false)
    const categories = ref([])
    const editingCategory = ref(null)
    
    const filters = reactive({
      search: '',
      status: ''
    })
    
    const categoryForm = reactive({
      name: '',
      description: '',
      color: '#272d63',
      status: 'active'
    })
    
    const pagination = reactive({
      current_page: 1,
      per_page: 10,
      total: 0,
      from: 0,
      to: 0
    })

    // Computed
    const filteredCategories = computed(() => {
      let filtered = categories.value
      
      if (filters.search) {
        filtered = filtered.filter(cat => 
          cat.name.toLowerCase().includes(filters.search.toLowerCase()) ||
          cat.description?.toLowerCase().includes(filters.search.toLowerCase())
        )
      }
      
      if (filters.status) {
        filtered = filtered.filter(cat => cat.status === filters.status)
      }
      
      return filtered
    })
    
    const paginationPages = computed(() => {
      const pages = []
      const totalPages = Math.ceil(pagination.total / pagination.per_page)
      for (let i = 1; i <= totalPages; i++) {
        pages.push(i)
      }
      return pages
    })

    // Methods
    const loadCategories = async (page = 1) => {
      loading.value = true
      try {
        const params = new URLSearchParams({
          page: page,
          per_page: pagination.per_page,
          ...(filters.search && { search: filters.search }),
          ...(filters.status && { status: filters.status })
        })
        
        const response = await fetch(`/api/v1/admin/categories?${params}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        if (response.ok) {
          const data = await response.json()
          if (data.success) {
            categories.value = data.data.categories
            Object.assign(pagination, data.data.pagination)
          }
        } else {
          // Données simulées si API non disponible
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
      categories.value = [
        {
          id: 1,
          name: 'Concerts',
          slug: 'concerts',
          description: 'Événements musicaux et concerts live',
          color: '#272d63',
          status: 'active',
          events_count: 25,
          created_at: new Date()
        },
        {
          id: 2,
          name: 'Conférences',
          slug: 'conferences',
          description: 'Événements professionnels et éducatifs',
          color: '#fab511',
          status: 'active',
          events_count: 12,
          created_at: new Date(Date.now() - 86400000)
        },
        {
          id: 3,
          name: 'Sport',
          slug: 'sport',
          description: 'Événements sportifs et compétitions',
          color: '#10b981',
          status: 'active',
          events_count: 8,
          created_at: new Date(Date.now() - 172800000)
        },
        {
          id: 4,
          name: 'Culture',
          slug: 'culture',
          description: 'Événements culturels et artistiques',
          color: '#8b5cf6',
          status: 'inactive',
          events_count: 3,
          created_at: new Date(Date.now() - 259200000)
        }
      ]
      
      Object.assign(pagination, {
        current_page: 1,
        per_page: 10,
        total: categories.value.length,
        from: 1,
        to: categories.value.length
      })
    }
    
    const createCategory = async () => {
      try {
        const response = await fetch('/api/v1/admin/categories', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(categoryForm)
        })
        
        if (response.ok) {
          const data = await response.json()
          if (data.success) {
            closeModals()
            loadCategories()
          }
        } else {
          // Simulation création
          const newCategory = {
            id: Date.now(),
            ...categoryForm,
            slug: categoryForm.name.toLowerCase().replace(/\s+/g, '-'),
            events_count: 0,
            created_at: new Date()
          }
          categories.value.unshift(newCategory)
          closeModals()
        }
      } catch (error) {
        console.log('API non disponible, ajout local simulé')
        const newCategory = {
          id: Date.now(),
          ...categoryForm,
          slug: categoryForm.name.toLowerCase().replace(/\s+/g, '-'),
          events_count: 0,
          created_at: new Date()
        }
        categories.value.unshift(newCategory)
        closeModals()
      }
    }
    
    const editCategory = (category) => {
      editingCategory.value = category
      Object.assign(categoryForm, category)
      showEditModal.value = true
    }
    
    const updateCategory = async () => {
      try {
        const response = await fetch(`/api/v1/admin/categories/${editingCategory.value.id}`, {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(categoryForm)
        })
        
        if (response.ok) {
          const data = await response.json()
          if (data.success) {
            closeModals()
            loadCategories()
          }
        } else {
          // Simulation mise à jour
          const index = categories.value.findIndex(c => c.id === editingCategory.value.id)
          if (index !== -1) {
            categories.value[index] = { ...categories.value[index], ...categoryForm }
          }
          closeModals()
        }
      } catch (error) {
        console.log('API non disponible, mise à jour locale simulée')
        const index = categories.value.findIndex(c => c.id === editingCategory.value.id)
        if (index !== -1) {
          categories.value[index] = { ...categories.value[index], ...categoryForm }
        }
        closeModals()
      }
    }
    
    const deleteCategory = async (category) => {
      if (!confirm(`Êtes-vous sûr de vouloir supprimer la catégorie "${category.name}" ?`)) return
      
      try {
        const response = await fetch(`/api/v1/admin/categories/${category.id}`, {
          method: 'DELETE',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        if (response.ok) {
          loadCategories()
        } else {
          // Simulation suppression
          categories.value = categories.value.filter(c => c.id !== category.id)
        }
      } catch (error) {
        console.log('API non disponible, suppression locale simulée')
        categories.value = categories.value.filter(c => c.id !== category.id)
      }
    }
    
    const changePage = (page) => {
      pagination.current_page = page
      loadCategories(page)
    }
    
    const closeModals = () => {
      showCreateModal.value = false
      showEditModal.value = false
      editingCategory.value = null
      Object.assign(categoryForm, {
        name: '',
        description: '',
        color: '#272d63',
        status: 'active'
      })
    }
    
    const formatDate = (date) => {
      return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      })
    }

    // Lifecycle
    onMounted(() => {
      loadCategories()
    })

    return {
      loading,
      showCreateModal,
      showEditModal,
      categories,
      filters,
      categoryForm,
      pagination,
      filteredCategories,
      paginationPages,
      loadCategories,
      createCategory,
      editCategory,
      updateCategory,
      deleteCategory,
      changePage,
      closeModals,
      formatDate
    }
  }
}
</script>

<style scoped>
.category-management {
  font-family: 'Inter', sans-serif;
}
</style>