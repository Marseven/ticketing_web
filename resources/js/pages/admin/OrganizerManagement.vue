<template>
  <div class="organizer-management p-6 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 mb-2">Gestion des Organisateurs</h1>
          <p class="text-gray-600">Créer et gérer les organisateurs de la plateforme</p>
        </div>
        <button @click="openCreateModal" 
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
          <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
          Créer Organisateur
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
          <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
          <select v-model="filters.status" @change="loadOrganizers" class="w-full border rounded-lg px-3 py-2">
            <option value="">Tous les statuts</option>
            <option value="active">Actif</option>
            <option value="inactive">Inactif</option>
            <option value="suspended">Suspendu</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Vérification</label>
          <select v-model="filters.verified" @change="loadOrganizers" class="w-full border rounded-lg px-3 py-2">
            <option value="">Tous</option>
            <option value="true">Vérifié</option>
            <option value="false">Non vérifié</option>
          </select>
        </div>
        
        <div class="flex items-end">
          <button @click="resetFilters" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
            Réinitialiser
          </button>
        </div>
      </div>
    </div>

    <!-- Organizers List -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-6 border-b">
        <h2 class="text-xl font-bold">Liste des Organisateurs</h2>
      </div>
      
      <div v-if="loading" class="p-8 text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-4 text-gray-600">Chargement...</p>
      </div>
      
      <div v-else-if="organizers.length === 0" class="p-8 text-center text-gray-500">
        Aucun organisateur trouvé
      </div>
      
      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Organisateur</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Utilisateurs</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Événements</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vérification</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="organizer in organizers" :key="organizer.id">
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <div class="w-10 h-10 bg-green-600 text-white rounded-full flex items-center justify-center font-bold">
                    {{ organizer.name.charAt(0).toUpperCase() }}
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">{{ organizer.name }}</div>
                    <div class="text-sm text-gray-500">{{ organizer.slug }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm text-gray-900">{{ organizer.contact_email }}</div>
                <div class="text-sm text-gray-500">{{ organizer.contact_phone || '-' }}</div>
              </td>
              <td class="px-6 py-4 text-sm text-gray-900">
                <div class="flex flex-wrap gap-1">
                  <span v-for="user in organizer.users" :key="user.id" 
                        class="inline-flex px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">
                    {{ user.name }}
                  </span>
                  <span v-if="organizer.users.length === 0" class="text-gray-500 text-xs">
                    Aucun utilisateur
                  </span>
                </div>
              </td>
              <td class="px-6 py-4 text-sm text-gray-900">{{ organizer.events_count || 0 }}</td>
              <td class="px-6 py-4 text-sm">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" 
                      :class="getStatusBadgeClass(organizer.status)">
                  {{ getStatusName(organizer.status) }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" 
                      :class="organizer.verified_at ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'">
                  {{ organizer.verified_at ? 'Vérifié' : 'En attente' }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm space-x-2">
                <button @click="editOrganizer(organizer)" class="text-blue-600 hover:text-blue-900">
                  Modifier
                </button>
                <button @click="viewOrganizerDetails(organizer)" class="text-green-600 hover:text-green-900">
                  Détails
                </button>
                <button @click="manageUsers(organizer)" class="text-purple-600 hover:text-purple-900">
                  Utilisateurs
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create/Edit Organizer Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-screen overflow-y-auto">
        <h3 class="text-lg font-bold mb-4">{{ editingOrganizer ? 'Modifier' : 'Créer' }} Organisateur</h3>
        
        <form @submit.prevent="saveOrganizer">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">Nom de l'organisation *</label>
              <input v-model="organizerForm.name" type="text" required 
                     class="w-full border rounded-lg px-3 py-2">
            </div>
            
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
              <textarea v-model="organizerForm.description" rows="3" 
                        class="w-full border rounded-lg px-3 py-2"></textarea>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Email de contact *</label>
              <input v-model="organizerForm.contact_email" type="email" required 
                     class="w-full border rounded-lg px-3 py-2">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
              <input ref="phoneInput" v-model="organizerForm.contact_phone" type="tel" 
                     class="w-full border rounded-lg px-3 py-2"
                     placeholder="Téléphone">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Site web (optionnel)</label>
              <input v-model="organizerForm.website_url" type="url" 
                     class="w-full border rounded-lg px-3 py-2"
                     placeholder="https://exemple.com">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Statut *</label>
              <select v-model="organizerForm.status" required class="w-full border rounded-lg px-3 py-2">
                <option value="active">Actif</option>
                <option value="inactive">Inactif</option>
                <option value="suspended">Suspendu</option>
              </select>
            </div>
            
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">Utilisateurs associés</label>
              <div class="border rounded-lg p-3 max-h-32 overflow-y-auto">
                <div v-for="user in availableUsers" :key="user.id" class="flex items-center mb-2">
                  <input type="checkbox" :value="user.id" v-model="organizerForm.user_ids" 
                         class="rounded border-gray-300">
                  <span class="ml-2 text-sm">{{ user.name }} ({{ user.email }})</span>
                </div>
                <div v-if="availableUsers.length === 0" class="text-sm text-gray-500">
                  Aucun utilisateur disponible
                </div>
              </div>
            </div>
          </div>
          
          <div class="flex justify-end space-x-3 mt-6">
            <button type="button" @click="closeModal" 
                    class="px-4 py-2 text-gray-600 hover:text-gray-800">
              Annuler
            </button>
            <button type="submit" :disabled="saving"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50">
              {{ saving ? 'Enregistrement...' : (editingOrganizer ? 'Mettre à jour' : 'Créer') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Organizer Details Modal -->
    <div v-if="showDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-2xl">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-bold">Détails de l'organisateur</h3>
          <button @click="showDetailsModal = false" class="text-gray-600 hover:text-gray-800">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        
        <div v-if="selectedOrganizer" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Nom</label>
              <p class="text-sm text-gray-900">{{ selectedOrganizer.name }}</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Slug</label>
              <p class="text-sm text-gray-900">{{ selectedOrganizer.slug }}</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Email</label>
              <p class="text-sm text-gray-900">{{ selectedOrganizer.contact_email }}</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Téléphone</label>
              <p class="text-sm text-gray-900">{{ selectedOrganizer.contact_phone || '-' }}</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Statut</label>
              <p class="text-sm">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                      :class="getStatusBadgeClass(selectedOrganizer.status)">
                  {{ getStatusName(selectedOrganizer.status) }}
                </span>
              </p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Vérification</label>
              <p class="text-sm">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                      :class="selectedOrganizer.verified_at ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'">
                  {{ selectedOrganizer.verified_at ? 'Vérifié' : 'En attente' }}
                </span>
              </p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Date de création</label>
              <p class="text-sm text-gray-900">{{ formatDate(selectedOrganizer.created_at) }}</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Événements</label>
              <p class="text-sm text-gray-900">{{ selectedOrganizer.events_count || 0 }} événement(s)</p>
            </div>
          </div>
          
          <div v-if="selectedOrganizer.description">
            <label class="block text-sm font-medium text-gray-700">Description</label>
            <p class="text-sm text-gray-900">{{ selectedOrganizer.description }}</p>
          </div>
          
          <div v-if="selectedOrganizer.website_url">
            <label class="block text-sm font-medium text-gray-700">Site web</label>
            <a :href="selectedOrganizer.website_url" target="_blank" 
               class="text-sm text-blue-600 hover:text-blue-800">
              {{ selectedOrganizer.website_url }}
            </a>
          </div>
          
          <div v-if="selectedOrganizer.users && selectedOrganizer.users.length > 0">
            <label class="block text-sm font-medium text-gray-700">Utilisateurs associés</label>
            <div class="flex flex-wrap gap-2">
              <span v-for="user in selectedOrganizer.users" :key="user.id"
                    class="inline-flex px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">
                {{ user.name }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- User Management Modal -->
    <div v-if="showUserModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-bold">Gérer les utilisateurs</h3>
          <button @click="showUserModal = false" class="text-gray-600 hover:text-gray-800">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        
        <div v-if="managingOrganizer" class="space-y-4">
          <div>
            <h4 class="font-medium text-gray-900 mb-2">{{ managingOrganizer.name }}</h4>
            <p class="text-sm text-gray-600 mb-4">Sélectionnez les utilisateurs à associer à cet organisateur</p>
          </div>
          
          <div class="border rounded-lg p-3 max-h-64 overflow-y-auto">
            <div v-for="user in availableUsers" :key="user.id" class="flex items-center justify-between py-2">
              <div>
                <span class="text-sm font-medium">{{ user.name }}</span>
                <span class="text-xs text-gray-500 block">{{ user.email }}</span>
              </div>
              <input type="checkbox" :value="user.id" v-model="userManagementForm.user_ids" 
                     class="rounded border-gray-300">
            </div>
          </div>
          
          <div class="flex justify-end space-x-3">
            <button @click="showUserModal = false" 
                    class="px-4 py-2 text-gray-600 hover:text-gray-800">
              Annuler
            </button>
            <button @click="updateOrganizerUsers" :disabled="updatingUsers"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50">
              {{ updatingUsers ? 'Mise à jour...' : 'Mettre à jour' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted, nextTick } from 'vue'
import intlTelInput from 'intl-tel-input'
import 'intl-tel-input/styles'

export default {
  name: 'OrganizerManagement',
  setup() {
    // État réactif
    const loading = ref(false)
    const saving = ref(false)
    const updatingUsers = ref(false)
    const showModal = ref(false)
    const showDetailsModal = ref(false)
    const showUserModal = ref(false)
    const editingOrganizer = ref(null)
    const selectedOrganizer = ref(null)
    const managingOrganizer = ref(null)
    
    const organizers = ref([])
    const availableUsers = ref([])
    const phoneInput = ref(null)
    let iti = null
    
    const filters = reactive({
      search: '',
      status: '',
      verified: '',
    })
    
    const organizerForm = reactive({
      name: '',
      description: '',
      contact_email: '',
      contact_phone: '',
      website_url: '',
      status: 'active',
      user_ids: [],
    })

    const userManagementForm = reactive({
      user_ids: [],
    })

    let searchTimeout = null

    // Méthodes
    const loadOrganizers = async () => {
      loading.value = true
      try {
        const queryParams = new URLSearchParams()
        if (filters.search) queryParams.append('search', filters.search)
        if (filters.status) queryParams.append('status', filters.status)
        if (filters.verified) queryParams.append('verified', filters.verified)
        
        const response = await fetch(`/api/v1/admin/organizers?${queryParams}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
          }
        })
        
        const data = await response.json()
        if (data.success) {
          organizers.value = data.data.organizers.data || data.data.organizers
        }
      } catch (error) {
        console.error('Erreur chargement organisateurs:', error)
      } finally {
        loading.value = false
      }
    }

    const loadAvailableUsers = async () => {
      try {
        // Charger uniquement les utilisateurs avec le rôle organisateur
        const response = await fetch('/api/v1/admin/users?role=organizer', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
          }
        })
        
        const data = await response.json()
        if (data.success) {
          const users = data.data.users.data || data.data.users || []
          // Filtrer pour ne garder que les utilisateurs avec le rôle organisateur
          availableUsers.value = users.filter(user => 
            user.roles && user.roles.some(role => role.slug === 'organizer')
          )
        }
      } catch (error) {
        console.error('Erreur chargement utilisateurs:', error)
      }
    }

    const debouncedSearch = () => {
      clearTimeout(searchTimeout)
      searchTimeout = setTimeout(() => {
        loadOrganizers()
      }, 500)
    }

    const openCreateModal = async () => {
      editingOrganizer.value = null
      Object.assign(organizerForm, {
        name: '',
        description: '',
        contact_email: '',
        contact_phone: '',
        website_url: '',
        status: 'active',
        user_ids: [],
      })
      await loadAvailableUsers()
      showModal.value = true
      
      // Initialiser intl-tel-input après l'ouverture du modal
      await nextTick()
      initPhoneInput()
    }

    const editOrganizer = async (organizer) => {
      editingOrganizer.value = organizer
      Object.assign(organizerForm, {
        name: organizer.name,
        description: organizer.description || '',
        contact_email: organizer.contact_email,
        contact_phone: organizer.contact_phone || '',
        website_url: organizer.website_url || '',
        status: organizer.status,
        user_ids: organizer.users ? organizer.users.map(u => u.id) : [],
      })
      await loadAvailableUsers()
      showModal.value = true
      
      // Initialiser intl-tel-input après l'ouverture du modal
      await nextTick()
      initPhoneInput()
    }

    const saveOrganizer = async () => {
      saving.value = true
      try {
        const url = editingOrganizer.value 
          ? `/api/v1/admin/organizers/${editingOrganizer.value.id}`
          : '/api/v1/admin/organizers'
        
        const method = editingOrganizer.value ? 'PUT' : 'POST'
        
        // Récupérer le numéro formaté si intl-tel-input est initialisé
        const formData = { ...organizerForm }
        if (iti && iti.isValidNumber()) {
          formData.contact_phone = iti.getNumber()
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
          body: JSON.stringify(formData)
        })
        
        const data = await response.json()
        if (data.success) {
          Toast.fire({
            icon: 'success',
            title: data.message || (editingOrganizer.value ? 'Organisateur mis à jour avec succès' : 'Organisateur créé avec succès')
          })
          showModal.value = false
          destroyPhoneInput()
          loadOrganizers()
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: data.message || 'Erreur lors de la sauvegarde'
          })
        }
      } catch (error) {
        console.error('Erreur sauvegarde organisateur:', error)
        Swal.fire({
          icon: 'error',
          title: 'Erreur technique',
          text: 'Une erreur est survenue lors de la sauvegarde'
        })
      } finally {
        saving.value = false
      }
    }

    const viewOrganizerDetails = (organizer) => {
      selectedOrganizer.value = organizer
      showDetailsModal.value = true
    }

    const manageUsers = async (organizer) => {
      managingOrganizer.value = organizer
      userManagementForm.user_ids = organizer.users ? organizer.users.map(u => u.id) : []
      await loadAvailableUsers()
      showUserModal.value = true
    }

    const updateOrganizerUsers = async () => {
      updatingUsers.value = true
      try {
        const response = await fetch(`/api/v1/admin/organizers/${managingOrganizer.value.id}`, {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
          },
          body: JSON.stringify(userManagementForm)
        })
        
        const data = await response.json()
        if (data.success) {
          Toast.fire({
            icon: 'success',
            title: 'Utilisateurs mis à jour avec succès'
          })
          showUserModal.value = false
          loadOrganizers()
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: data.message || 'Erreur lors de la mise à jour'
          })
        }
      } catch (error) {
        console.error('Erreur mise à jour utilisateurs:', error)
        Swal.fire({
          icon: 'error',
          title: 'Erreur technique',
          text: 'Une erreur est survenue lors de la mise à jour'
        })
      } finally {
        updatingUsers.value = false
      }
    }

    const resetFilters = () => {
      Object.assign(filters, {
        search: '',
        status: '',
        verified: '',
      })
      loadOrganizers()
    }

    const initPhoneInput = () => {
      if (phoneInput.value && !iti) {
        iti = intlTelInput(phoneInput.value, {
          initialCountry: 'ga', // Gabon
          preferredCountries: ['ga', 'cm', 'cg', 'ci', 'sn'], // Pays de la région
          separateDialCode: true,
          utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@25.11.1/build/js/utils.js'
        })
        
        // Écouter les changements
        phoneInput.value.addEventListener('input', () => {
          if (iti.isValidNumber()) {
            organizerForm.contact_phone = iti.getNumber()
          }
        })
        
        phoneInput.value.addEventListener('countrychange', () => {
          organizerForm.contact_phone = iti.getNumber()
        })
      }
    }

    const destroyPhoneInput = () => {
      if (iti) {
        iti.destroy()
        iti = null
      }
    }

    const closeModal = () => {
      destroyPhoneInput()
      showModal.value = false
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

    const getStatusName = (status) => {
      const names = {
        active: 'Actif',
        inactive: 'Inactif',
        suspended: 'Suspendu'
      }
      return names[status] || status
    }

    const getStatusBadgeClass = (status) => {
      const classes = {
        active: 'bg-green-100 text-green-800',
        inactive: 'bg-gray-100 text-gray-800',
        suspended: 'bg-red-100 text-red-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    }

    // Lifecycle
    onMounted(() => {
      loadOrganizers()
    })

    return {
      // État
      loading,
      saving,
      updatingUsers,
      showModal,
      showDetailsModal,
      showUserModal,
      editingOrganizer,
      selectedOrganizer,
      managingOrganizer,
      organizers,
      availableUsers,
      filters,
      organizerForm,
      userManagementForm,
      phoneInput,
      
      // Méthodes
      loadOrganizers,
      loadAvailableUsers,
      debouncedSearch,
      openCreateModal,
      editOrganizer,
      saveOrganizer,
      viewOrganizerDetails,
      manageUsers,
      updateOrganizerUsers,
      resetFilters,
      initPhoneInput,
      destroyPhoneInput,
      closeModal,
      
      // Utilitaires
      formatDate,
      getStatusName,
      getStatusBadgeClass,
    }
  }
}
</script>

<style scoped>
.organizer-management {
  font-family: 'Inter', sans-serif;
}
</style>