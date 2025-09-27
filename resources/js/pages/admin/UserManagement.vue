<template>
  <div class="user-management p-6 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 mb-2">Gestion des Utilisateurs</h1>
          <p class="text-gray-600">Créer, modifier et gérer tous les utilisateurs de la plateforme</p>
        </div>
        <button @click="openCreateModal" 
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
          <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
          Créer Utilisateur
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
      <h2 class="text-xl font-bold mb-4">Filtres</h2>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
          <input v-model="filters.search" @input="debouncedSearch" type="text" 
                 placeholder="Nom ou email..." class="w-full border rounded-lg px-3 py-2">
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Rôle</label>
          <select v-model="filters.role" @change="loadUsers" class="w-full border rounded-lg px-3 py-2">
            <option value="">Tous les rôles</option>
            <option value="admin">Administrateur</option>
            <option value="organizer">Organisateur</option>
            <option value="client">Client</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
          <select v-model="filters.status" @change="loadUsers" class="w-full border rounded-lg px-3 py-2">
            <option value="">Tous les statuts</option>
            <option value="active">Actif</option>
            <option value="inactive">Inactif</option>
          </select>
        </div>
        
        <div class="flex items-end">
          <button @click="resetFilters" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
            Réinitialiser
          </button>
        </div>
      </div>
    </div>

    <!-- Users List -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-6 border-b">
        <h2 class="text-xl font-bold">Liste des Utilisateurs</h2>
      </div>
      
      <div v-if="loading" class="p-8 text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-4 text-gray-600">Chargement...</p>
      </div>
      
      <div v-else-if="users.length === 0" class="p-8 text-center text-gray-500">
        Aucun utilisateur trouvé
      </div>
      
      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Utilisateur</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rôles</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Inscription</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="user in users" :key="user.id">
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <div class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">
                    {{ user.name.charAt(0).toUpperCase() }}
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                    <div class="text-sm text-gray-500">ID: {{ user.id }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 text-sm text-gray-900">{{ user.email }}</td>
              <td class="px-6 py-4 text-sm">
                <div class="space-y-1">
                  <template v-if="user.roles && user.roles.length > 0">
                    <span v-for="role in user.roles" :key="role.id" 
                          class="inline-flex px-2 py-1 text-xs font-semibold rounded-full mr-1"
                          :class="{
                            'bg-red-100 text-red-800': role.slug === 'admin',
                            'bg-green-100 text-green-800': role.slug === 'organizer',
                            'bg-gray-100 text-gray-800': role.slug === 'client'
                          }">
                      {{ getRoleName(role.slug) }}
                    </span>
                  </template>
                  <span v-else class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                    Aucun rôle
                  </span>
                </div>
              </td>
              <td class="px-6 py-4 text-sm">
                <button @click="toggleUserStatus(user)" 
                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full cursor-pointer"
                        :class="user.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                  {{ user.status === 'active' ? 'Actif' : 'Inactif' }}
                </button>
              </td>
              <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(user.created_at) }}</td>
              <td class="px-6 py-4 text-sm space-x-2">
                <button @click="editUser(user)" class="text-blue-600 hover:text-blue-900">
                  Modifier
                </button>
                <button @click="viewUserDetails(user)" class="text-green-600 hover:text-green-900">
                  Détails
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create/Edit User Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-bold mb-4">{{ editingUser ? 'Modifier' : 'Créer' }} Utilisateur</h3>
        
        <form @submit.prevent="saveUser">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Nom complet *</label>
              <input v-model="userForm.name" type="text" required 
                     class="w-full border rounded-lg px-3 py-2">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
              <input v-model="userForm.email" type="email" required 
                     class="w-full border rounded-lg px-3 py-2">
            </div>
            
            <div v-if="!editingUser" class="bg-blue-50 p-4 rounded-lg">
              <p class="text-sm text-blue-800">
                <svg class="w-5 h-5 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
                Un email sera envoyé à l'utilisateur pour qu'il définisse son mot de passe
              </p>
            </div>
            
            <div v-if="editingUser">
              <label class="block text-sm font-medium text-gray-700 mb-2">Nouveau mot de passe</label>
              <input v-model="userForm.password" type="password" 
                     placeholder="Laisser vide pour ne pas changer"
                     class="w-full border rounded-lg px-3 py-2">
            </div>
            
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">Rôles</label>
              
              <label class="flex items-center">
                <input type="checkbox" v-model="userForm.is_admin" class="rounded border-gray-300">
                <span class="ml-2 text-sm text-gray-700">Administrateur</span>
              </label>
              
              <label class="flex items-center">
                <input type="checkbox" v-model="userForm.is_organizer" class="rounded border-gray-300">
                <span class="ml-2 text-sm text-gray-700">Organisateur</span>
              </label>
            </div>
            
            <div v-if="editingUser">
              <label class="flex items-center">
                <input type="checkbox" v-model="userForm.is_active" class="rounded border-gray-300">
                <span class="ml-2 text-sm text-gray-700">Compte actif</span>
              </label>
            </div>
          </div>
          
          <div class="flex justify-end space-x-3 mt-6">
            <button type="button" @click="showModal = false" 
                    class="px-4 py-2 text-gray-600 hover:text-gray-800">
              Annuler
            </button>
            <button type="submit" :disabled="saving"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50">
              {{ saving ? 'Enregistrement...' : (editingUser ? 'Mettre à jour' : 'Créer') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- User Details Modal -->
    <div v-if="showDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-lg">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-bold">Détails de l'utilisateur</h3>
          <button @click="showDetailsModal = false" class="text-gray-600 hover:text-gray-800">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        
        <div v-if="selectedUser" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Nom</label>
              <p class="text-sm text-gray-900">{{ selectedUser.name }}</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Email</label>
              <p class="text-sm text-gray-900">{{ selectedUser.email }}</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Statut</label>
              <p class="text-sm">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                      :class="selectedUser.deleted_at ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'">
                  {{ selectedUser.deleted_at ? 'Inactif' : 'Actif' }}
                </span>
              </p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Rôles</label>
              <div class="space-y-1">
                <template v-if="selectedUser.roles && selectedUser.roles.length > 0">
                  <span v-for="role in selectedUser.roles" :key="role.id" 
                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full mr-1"
                        :class="{
                          'bg-red-100 text-red-800': role.slug === 'admin',
                          'bg-green-100 text-green-800': role.slug === 'organizer',
                          'bg-gray-100 text-gray-800': role.slug === 'client'
                        }">
                    {{ getRoleName(role.slug) }}
                  </span>
                </template>
                <span v-else class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                  Aucun rôle
                </span>
              </div>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Date d'inscription</label>
              <p class="text-sm text-gray-900">{{ formatDate(selectedUser.created_at) }}</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Email vérifié</label>
              <p class="text-sm text-gray-900">
                {{ selectedUser.email_verified_at ? 'Oui' : 'Non' }}
              </p>
            </div>
          </div>
          
          <div v-if="selectedUser.is_organizer && selectedUser.organizers_count > 0">
            <label class="block text-sm font-medium text-gray-700">Organisateurs</label>
            <p class="text-sm text-gray-900">{{ selectedUser.organizers_count }} organisateur(s)</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue'

export default {
  name: 'UserManagement',
  setup() {
    // État réactif
    const loading = ref(false)
    const saving = ref(false)
    const showModal = ref(false)
    const showDetailsModal = ref(false)
    const editingUser = ref(null)
    const selectedUser = ref(null)
    
    const users = ref([])
    
    const filters = reactive({
      search: '',
      role: '',
      status: '',
    })
    
    const userForm = reactive({
      name: '',
      email: '',
      password: '',
      is_admin: false,
      is_organizer: false,
      is_active: true,
    })

    let searchTimeout = null

    // Méthodes
    const loadUsers = async () => {
      loading.value = true
      try {
        const queryParams = new URLSearchParams()
        if (filters.search) queryParams.append('search', filters.search)
        if (filters.role) queryParams.append('role', filters.role)
        if (filters.status) queryParams.append('status', filters.status)
        
        const response = await fetch(`/api/v1/admin/users?${queryParams}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
          }
        })
        
        const data = await response.json()
        if (data.success) {
          users.value = data.data.users.data || data.data.users
        }
      } catch (error) {
        console.error('Erreur chargement utilisateurs:', error)
      } finally {
        loading.value = false
      }
    }

    const debouncedSearch = () => {
      clearTimeout(searchTimeout)
      searchTimeout = setTimeout(() => {
        loadUsers()
      }, 500)
    }

    const openCreateModal = () => {
      editingUser.value = null
      Object.assign(userForm, {
        name: '',
        email: '',
        password: '',
        is_admin: false,
        is_organizer: false,
        is_active: true,
      })
      showModal.value = true
    }

    const editUser = (user) => {
      editingUser.value = user
      Object.assign(userForm, {
        name: user.name,
        email: user.email,
        password: '',
        is_admin: user.roles?.some(role => role.slug === 'admin') || false,
        is_organizer: user.roles?.some(role => role.slug === 'organizer') || false,
        is_active: user.status === 'active',
      })
      showModal.value = true
    }

    const saveUser = async () => {
      saving.value = true
      try {
        const url = editingUser.value 
          ? `/api/v1/admin/users/${editingUser.value.id}`
          : '/api/v1/admin/users'
        
        const method = editingUser.value ? 'PUT' : 'POST'
        
        // Pour la création, on n'envoie pas de mot de passe
        const payload = {...userForm}
        if (!editingUser.value) {
          delete payload.password
        }
        
        const response = await fetch(url, {
          method,
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
          },
          body: JSON.stringify(payload)
        })
        
        const data = await response.json()
        if (data.success) {
          Toast.fire({
            icon: 'success',
            title: data.message || (editingUser.value ? 'Utilisateur mis à jour avec succès' : 'Utilisateur créé avec succès')
          })
          showModal.value = false
          loadUsers()
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: data.message || 'Erreur lors de la sauvegarde'
          })
        }
      } catch (error) {
        console.error('Erreur sauvegarde utilisateur:', error)
        Swal.fire({
          icon: 'error',
          title: 'Erreur technique',
          text: 'Une erreur est survenue lors de la sauvegarde'
        })
      } finally {
        saving.value = false
      }
    }

    const toggleUserStatus = async (user) => {
      const result = await confirmAction(
        'Changer le statut de l\'utilisateur',
        `Êtes-vous sûr de vouloir ${user.status === 'active' ? 'désactiver' : 'activer'} cet utilisateur ?`,
        user.status === 'active' ? 'Désactiver' : 'Activer'
      )
      
      if (!result.isConfirmed) {
        return
      }

      try {
        const response = await fetch(`/api/v1/admin/users/${user.id}/toggle-status`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
          }
        })
        
        const data = await response.json()
        if (data.success) {
          Toast.fire({
            icon: 'success',
            title: data.message || 'Statut mis à jour avec succès'
          })
          loadUsers()
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: data.message || 'Erreur lors du changement de statut'
          })
        }
      } catch (error) {
        console.error('Erreur toggle statut:', error)
        Swal.fire({
          icon: 'error',
          title: 'Erreur technique',
          text: 'Une erreur est survenue lors du changement de statut'
        })
      }
    }

    const viewUserDetails = (user) => {
      selectedUser.value = user
      showDetailsModal.value = true
    }

    const resetFilters = () => {
      Object.assign(filters, {
        search: '',
        role: '',
        status: '',
      })
      loadUsers()
    }

    // Utilitaires
    const formatDate = (date) => {
      return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    const getRoleName = (slug) => {
      const roleNames = {
        'admin': 'Administrateur',
        'organizer': 'Organisateur',
        'client': 'Client'
      }
      return roleNames[slug] || slug
    }

    // Lifecycle
    onMounted(() => {
      loadUsers()
    })

    return {
      // État
      loading,
      saving,
      showModal,
      showDetailsModal,
      editingUser,
      selectedUser,
      users,
      filters,
      userForm,
      
      // Méthodes
      loadUsers,
      debouncedSearch,
      openCreateModal,
      editUser,
      saveUser,
      toggleUserStatus,
      viewUserDetails,
      resetFilters,
      
      // Utilitaires
      formatDate,
      getRoleName,
    }
  }
}
</script>

<style scoped>
.user-management {
  font-family: 'Inter', sans-serif;
}
</style>