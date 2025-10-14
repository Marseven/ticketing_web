<template>
  <div class="roles-page p-6 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-primea-blue mb-2">Gestion des Rôles</h1>
          <p class="text-gray-600">Créer et gérer les rôles personnalisés</p>
        </div>
        <button @click="openCreateModal"
                class="bg-primea-blue text-white px-4 py-2 rounded-lg hover:bg-blue-800">
          <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
          Créer un Rôle
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
      <h2 class="text-xl font-bold mb-4">Filtres</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
          <select v-model="filters.type" @change="loadRoles" class="w-full border rounded-lg px-3 py-2">
            <option value="">Tous les types</option>
            <option value="system">Système</option>
            <option value="organizer">Organisateur</option>
            <option value="custom">Personnalisé</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
          <select v-model="filters.status" @change="loadRoles" class="w-full border rounded-lg px-3 py-2">
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

    <!-- Roles List -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-6 border-b">
        <h2 class="text-xl font-bold">Liste des Rôles</h2>
        <p class="text-sm text-gray-600 mt-1">Total: {{ pagination.total }} rôle(s)</p>
      </div>

      <div v-if="loading" class="p-8 text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primea-blue mx-auto"></div>
        <p class="mt-4 text-gray-600">Chargement...</p>
      </div>

      <div v-else-if="roles.length === 0" class="p-8 text-center text-gray-500">
        Aucun rôle trouvé
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Niveau</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Utilisateurs</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Privilèges</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="role in roles" :key="role.id" class="hover:bg-gray-50">
              <td class="px-6 py-4">
                <div>
                  <div class="text-sm font-medium text-gray-900">{{ role.name }}</div>
                  <div class="text-xs text-gray-500 font-mono">{{ role.slug }}</div>
                </div>
              </td>
              <td class="px-6 py-4 text-sm">
                <span :class="getTypeClass(role.type)" class="px-2 py-1 rounded-full text-xs font-medium">
                  {{ formatType(role.type) }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-900">
                <span class="bg-gray-100 px-2 py-1 rounded text-xs font-medium">
                  Niveau {{ role.level }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-900">
                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium">
                  {{ role.users_count || 0 }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-900">
                <button @click="managePrivileges(role)"
                        class="bg-purple-100 text-purple-800 px-2 py-1 rounded-full text-xs font-medium hover:bg-purple-200">
                  {{ role.privileges_count || 0 }} privilège(s)
                </button>
              </td>
              <td class="px-6 py-4 text-sm">
                <span v-if="role.is_active" class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium">
                  Actif
                </span>
                <span v-else class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs font-medium">
                  Inactif
                </span>
              </td>
              <td class="px-6 py-4 text-sm">
                <div class="flex items-center space-x-2">
                  <button @click="viewDetails(role)"
                          class="text-blue-600 hover:text-blue-800"
                          title="Voir les détails">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </button>
                  <button v-if="role.type !== 'system'"
                          @click="openEditModal(role)"
                          class="text-green-600 hover:text-green-800"
                          title="Modifier">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button v-if="role.type !== 'system'"
                          @click="confirmDelete(role)"
                          class="text-red-600 hover:text-red-800"
                          title="Supprimer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.total > pagination.per_page" class="p-6 border-t">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-700">
            Affichage de {{ pagination.from }} à {{ pagination.to }} sur {{ pagination.total }} résultats
          </div>
          <div class="flex space-x-2">
            <button @click="goToPage(pagination.current_page - 1)"
                    :disabled="pagination.current_page === 1"
                    class="px-4 py-2 border rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50">
              Précédent
            </button>
            <button @click="goToPage(pagination.current_page + 1)"
                    :disabled="pagination.current_page === pagination.last_page"
                    class="px-4 py-2 border rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50">
              Suivant
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showFormModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b">
          <div class="flex items-center justify-between">
            <h3 class="text-xl font-bold text-primea-blue">
              {{ isEditMode ? 'Modifier le Rôle' : 'Créer un Rôle' }}
            </h3>
            <button @click="closeFormModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <form @submit.prevent="submitForm" class="p-6">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Type d'utilisateur *</label>
              <select v-model="formData.user_type_id" @change="loadPrivilegesForUserType" required
                      class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-primea-blue">
                <option value="">Sélectionner un type</option>
                <option v-for="userType in userTypes" :key="userType.id" :value="userType.id">
                  {{ userType.label }}
                </option>
              </select>
              <p class="text-xs text-gray-500 mt-1">Les privilèges disponibles dépendent du type d'utilisateur</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Nom du rôle *</label>
              <input v-model="formData.name" type="text" required
                     class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-primea-blue"
                     placeholder="Ex: Modérateur">
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
              <textarea v-model="formData.description" rows="3"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-primea-blue"
                        placeholder="Description du rôle"></textarea>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Niveau (0-100) *</label>
              <input v-model.number="formData.level" type="number" min="0" max="100" required
                     class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-primea-blue">
              <p class="text-xs text-gray-500 mt-1">Plus le niveau est élevé, plus le rôle a de priorité</p>
            </div>

            <div v-if="isEditMode">
              <label class="flex items-center">
                <input v-model="formData.is_active" type="checkbox" class="mr-2">
                <span class="text-sm font-medium text-gray-700">Actif</span>
              </label>
            </div>

            <!-- Privilèges Section -->
            <div v-if="formData.user_type_id" class="border-t pt-4">
              <label class="block text-sm font-medium text-gray-700 mb-3">
                Privilèges ({{ formData.privilege_ids.length }} sélectionné(s))
              </label>

              <div v-if="loadingPrivileges" class="text-center py-4">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primea-blue mx-auto"></div>
                <p class="mt-2 text-sm text-gray-600">Chargement...</p>
              </div>

              <div v-else-if="availablePrivileges.length === 0" class="text-center py-4 text-gray-500">
                Aucun privilège disponible pour ce type d'utilisateur
              </div>

              <div v-else>
                <div class="mb-3">
                  <input v-model="privilegeSearch" type="text" placeholder="Rechercher un privilège..."
                         class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>

                <div class="space-y-2 max-h-80 overflow-y-auto border rounded-lg p-3">
                  <div v-for="privilege in filteredAvailablePrivileges" :key="privilege.id"
                       class="flex items-start p-2 hover:bg-gray-50 rounded">
                    <input type="checkbox"
                           :id="`priv-form-${privilege.id}`"
                           :checked="formData.privilege_ids.includes(privilege.id)"
                           @change="togglePrivilegeInForm(privilege.id)"
                           class="mt-1 mr-3">
                    <label :for="`priv-form-${privilege.id}`" class="flex-1 cursor-pointer">
                      <div class="font-medium text-sm">{{ privilege.name }}</div>
                      <div class="text-xs text-gray-500">{{ privilege.description || privilege.slug }}</div>
                    </label>
                    <div class="flex gap-1 ml-2">
                      <span class="bg-purple-100 text-purple-800 px-2 py-0.5 rounded text-xs">
                        {{ privilege.module }}
                      </span>
                      <span class="bg-indigo-100 text-indigo-800 px-2 py-0.5 rounded text-xs">
                        {{ privilege.action }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-6 flex justify-end space-x-3">
            <button type="button" @click="closeFormModal"
                    class="px-4 py-2 border rounded-lg hover:bg-gray-50">
              Annuler
            </button>
            <button type="submit" :disabled="submitting"
                    class="bg-primea-blue text-white px-4 py-2 rounded-lg hover:bg-blue-800 disabled:opacity-50">
              {{ submitting ? 'En cours...' : (isEditMode ? 'Modifier' : 'Créer') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Details Modal -->
    <div v-if="showDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b">
          <div class="flex items-center justify-between">
            <h3 class="text-xl font-bold text-primea-blue">Détails du Rôle</h3>
            <button @click="closeDetailsModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <div v-if="selectedRole" class="p-6">
          <div class="grid grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
              <p class="text-gray-900 font-medium">{{ selectedRole.name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
              <p class="text-gray-900 font-mono bg-gray-100 px-3 py-1 rounded">{{ selectedRole.slug }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
              <span :class="getTypeClass(selectedRole.type)" class="px-3 py-1 rounded-full text-sm font-medium">
                {{ formatType(selectedRole.type) }}
              </span>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Niveau</label>
              <span class="bg-gray-100 px-3 py-1 rounded text-sm font-medium">
                Niveau {{ selectedRole.level }}
              </span>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Utilisateurs</label>
              <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                {{ selectedRole.users_count || 0 }}
              </span>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
              <span v-if="selectedRole.is_active" class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                Actif
              </span>
              <span v-else class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-medium">
                Inactif
              </span>
            </div>
          </div>

          <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
            <p class="text-gray-900">{{ selectedRole.description || 'Aucune description' }}</p>
          </div>

          <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Privilèges associés ({{ selectedRole.privileges?.length || 0 }})</label>
            <div v-if="selectedRole.privileges && selectedRole.privileges.length > 0" class="flex flex-wrap gap-2 mt-2">
              <span v-for="privilege in selectedRole.privileges" :key="privilege.id"
                    class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-sm">
                {{ privilege.name }}
              </span>
            </div>
            <p v-else class="text-gray-500">Aucun privilège associé</p>
          </div>
        </div>

        <div class="p-6 border-t bg-gray-50">
          <button @click="closeDetailsModal" class="w-full bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
            Fermer
          </button>
        </div>
      </div>
    </div>

    <!-- Privileges Management Modal -->
    <div v-if="showPrivilegesModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b">
          <div class="flex items-center justify-between">
            <h3 class="text-xl font-bold text-primea-blue">
              Gérer les Privilèges - {{ selectedRole?.name }}
            </h3>
            <button @click="closePrivilegesModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <div class="p-6">
          <div v-if="loadingPrivileges" class="text-center py-8">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primea-blue mx-auto"></div>
            <p class="mt-4 text-gray-600">Chargement des privilèges...</p>
          </div>

          <div v-else>
            <div class="mb-4">
              <input v-model="privilegeSearch" type="text" placeholder="Rechercher un privilège..."
                     class="w-full border rounded-lg px-3 py-2">
            </div>

            <div class="space-y-4 max-h-96 overflow-y-auto">
              <div v-for="privilege in filteredPrivileges" :key="privilege.id"
                   class="flex items-center p-3 border rounded-lg hover:bg-gray-50">
                <input type="checkbox"
                       :id="`priv-${privilege.id}`"
                       :checked="selectedPrivilegeIds.includes(privilege.id)"
                       @change="togglePrivilege(privilege.id)"
                       class="mr-3">
                <label :for="`priv-${privilege.id}`" class="flex-1 cursor-pointer">
                  <div class="font-medium">{{ privilege.name }}</div>
                  <div class="text-xs text-gray-500">{{ privilege.description || privilege.slug }}</div>
                </label>
                <div class="flex gap-2">
                  <span class="bg-purple-100 text-purple-800 px-2 py-1 rounded text-xs">
                    {{ privilege.module }}
                  </span>
                  <span class="bg-indigo-100 text-indigo-800 px-2 py-1 rounded text-xs">
                    {{ privilege.action }}
                  </span>
                </div>
              </div>
            </div>

            <div class="mt-4 text-sm text-gray-600">
              {{ selectedPrivilegeIds.length }} privilège(s) sélectionné(s)
            </div>
          </div>
        </div>

        <div class="p-6 border-t bg-gray-50 flex justify-end space-x-3">
          <button @click="closePrivilegesModal"
                  class="px-4 py-2 border rounded-lg hover:bg-gray-50">
            Annuler
          </button>
          <button @click="savePrivileges" :disabled="submitting"
                  class="bg-primea-blue text-white px-4 py-2 rounded-lg hover:bg-blue-800 disabled:opacity-50">
            {{ submitting ? 'Enregistrement...' : 'Enregistrer' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'Roles',

  data() {
    return {
      roles: [],
      allPrivileges: [],
      availablePrivileges: [],
      userTypes: [],
      loading: false,
      loadingPrivileges: false,
      submitting: false,
      showFormModal: false,
      showDetailsModal: false,
      showPrivilegesModal: false,
      isEditMode: false,
      selectedRole: null,
      privilegeSearch: '',
      selectedPrivilegeIds: [],
      filters: {
        type: '',
        status: '',
      },
      formData: {
        name: '',
        description: '',
        user_type_id: '',
        type: 'custom',
        level: 10,
        is_active: true,
        privilege_ids: [],
      },
      pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 20,
        total: 0,
        from: 0,
        to: 0,
      },
    };
  },

  computed: {
    filteredPrivileges() {
      if (!this.privilegeSearch) return this.allPrivileges;
      const search = this.privilegeSearch.toLowerCase();
      return this.allPrivileges.filter(p =>
        p.name.toLowerCase().includes(search) ||
        p.slug.toLowerCase().includes(search) ||
        p.module.toLowerCase().includes(search) ||
        p.action.toLowerCase().includes(search)
      );
    },
    filteredAvailablePrivileges() {
      if (!this.privilegeSearch) return this.availablePrivileges;
      const search = this.privilegeSearch.toLowerCase();
      return this.availablePrivileges.filter(p =>
        p.name.toLowerCase().includes(search) ||
        p.slug.toLowerCase().includes(search) ||
        p.module.toLowerCase().includes(search) ||
        p.action.toLowerCase().includes(search)
      );
    },
  },

  mounted() {
    this.loadRoles();
    this.loadUserTypes();
  },

  methods: {
    async loadRoles(page = 1) {
      this.loading = true;
      try {
        const params = {
          page,
          per_page: this.pagination.per_page,
          ...this.filters,
        };

        const response = await axios.get('/api/v1/admin/roles', { params });

        if (response.data.success) {
          this.roles = response.data.data.data;
          this.pagination = {
            current_page: response.data.data.current_page,
            last_page: response.data.data.last_page,
            per_page: response.data.data.per_page,
            total: response.data.data.total,
            from: response.data.data.from,
            to: response.data.data.to,
          };
        }
      } catch (error) {
        console.error('Erreur chargement rôles:', error);
        this.$swal.fire({
          icon: 'error',
          title: 'Erreur',
          text: 'Impossible de charger les rôles',
        });
      } finally {
        this.loading = false;
      }
    },

    async loadUserTypes() {
      try {
        const response = await axios.get('/api/v1/admin/user-types', {
          params: { per_page: 100 }
        });

        if (response.data.success) {
          this.userTypes = response.data.data.data;
        }
      } catch (error) {
        console.error('Erreur chargement types utilisateurs:', error);
      }
    },

    async loadAllPrivileges() {
      this.loadingPrivileges = true;
      try {
        const response = await axios.get('/api/v1/admin/privileges', {
          params: { per_page: 1000 }
        });

        if (response.data.success) {
          this.allPrivileges = response.data.data.data;
        }
      } catch (error) {
        console.error('Erreur chargement privilèges:', error);
        this.$swal.fire({
          icon: 'error',
          title: 'Erreur',
          text: 'Impossible de charger les privilèges',
        });
      } finally {
        this.loadingPrivileges = false;
      }
    },

    async loadPrivilegesForUserType() {
      if (!this.formData.user_type_id) {
        this.availablePrivileges = [];
        return;
      }

      this.loadingPrivileges = true;
      try {
        const response = await axios.get('/api/v1/admin/privileges', {
          params: {
            per_page: 1000,
            user_type_id: this.formData.user_type_id
          }
        });

        if (response.data.success) {
          this.availablePrivileges = response.data.data.data;
        }
      } catch (error) {
        console.error('Erreur chargement privilèges:', error);
        this.$swal.fire({
          icon: 'error',
          title: 'Erreur',
          text: 'Impossible de charger les privilèges',
        });
      } finally {
        this.loadingPrivileges = false;
      }
    },

    togglePrivilegeInForm(privilegeId) {
      const index = this.formData.privilege_ids.indexOf(privilegeId);
      if (index > -1) {
        this.formData.privilege_ids.splice(index, 1);
      } else {
        this.formData.privilege_ids.push(privilegeId);
      }
    },

    resetFilters() {
      this.filters = {
        type: '',
        status: '',
      };
      this.loadRoles();
    },

    goToPage(page) {
      if (page >= 1 && page <= this.pagination.last_page) {
        this.loadRoles(page);
      }
    },

    async openCreateModal() {
      this.isEditMode = false;
      this.formData = {
        name: '',
        description: '',
        user_type_id: '',
        type: 'custom',
        level: 10,
        is_active: true,
        privilege_ids: [],
      };
      this.availablePrivileges = [];
      this.privilegeSearch = '';
      this.showFormModal = true;
    },

    async openEditModal(role) {
      this.isEditMode = true;
      this.selectedRole = role;
      this.formData = {
        name: role.name,
        description: role.description,
        user_type_id: role.user_type_id,
        level: role.level,
        is_active: role.is_active,
        privilege_ids: role.privileges?.map(p => p.id) || [],
      };
      this.privilegeSearch = '';
      this.showFormModal = true;

      // Charger les privilèges pour le type d'utilisateur
      if (role.user_type_id) {
        await this.loadPrivilegesForUserType();
      }
    },

    closeFormModal() {
      this.showFormModal = false;
      this.selectedRole = null;
    },

    async submitForm() {
      this.submitting = true;
      try {
        const url = this.isEditMode
          ? `/api/v1/admin/roles/${this.selectedRole.id}`
          : '/api/v1/admin/roles';

        const method = this.isEditMode ? 'put' : 'post';

        // Préparer les données
        const payload = {
          name: this.formData.name,
          description: this.formData.description,
          user_type_id: this.formData.user_type_id,
          type: this.formData.type,
          level: this.formData.level,
          is_active: this.formData.is_active,
          privilege_ids: this.formData.privilege_ids,
        };

        const response = await axios[method](url, payload);

        if (response.data.success) {
          this.$swal.fire({
            icon: 'success',
            title: 'Succès',
            text: response.data.message,
            timer: 2000,
          });
          this.closeFormModal();
          this.loadRoles(this.pagination.current_page);
        }
      } catch (error) {
        console.error('Erreur soumission formulaire:', error);
        this.$swal.fire({
          icon: 'error',
          title: 'Erreur',
          text: error.response?.data?.message || 'Une erreur est survenue',
        });
      } finally {
        this.submitting = false;
      }
    },

    viewDetails(role) {
      this.selectedRole = role;
      this.showDetailsModal = true;
    },

    closeDetailsModal() {
      this.showDetailsModal = false;
      this.selectedRole = null;
    },

    async managePrivileges(role) {
      this.selectedRole = role;
      this.selectedPrivilegeIds = role.privileges?.map(p => p.id) || [];
      this.privilegeSearch = '';
      this.showPrivilegesModal = true;
      await this.loadAllPrivileges();
    },

    closePrivilegesModal() {
      this.showPrivilegesModal = false;
      this.selectedRole = null;
      this.selectedPrivilegeIds = [];
    },

    togglePrivilege(privilegeId) {
      const index = this.selectedPrivilegeIds.indexOf(privilegeId);
      if (index > -1) {
        this.selectedPrivilegeIds.splice(index, 1);
      } else {
        this.selectedPrivilegeIds.push(privilegeId);
      }
    },

    async savePrivileges() {
      this.submitting = true;
      try {
        const response = await axios.post(
          `/api/v1/admin/roles/${this.selectedRole.id}/privileges`,
          { privilege_ids: this.selectedPrivilegeIds }
        );

        if (response.data.success) {
          this.$swal.fire({
            icon: 'success',
            title: 'Succès',
            text: 'Privilèges mis à jour avec succès',
            timer: 2000,
          });
          this.closePrivilegesModal();
          this.loadRoles(this.pagination.current_page);
        }
      } catch (error) {
        console.error('Erreur sauvegarde privilèges:', error);
        this.$swal.fire({
          icon: 'error',
          title: 'Erreur',
          text: error.response?.data?.message || 'Une erreur est survenue',
        });
      } finally {
        this.submitting = false;
      }
    },

    confirmDelete(role) {
      this.$swal.fire({
        title: 'Supprimer ce rôle ?',
        html: `
          <p>Voulez-vous vraiment supprimer le rôle <strong>${role.name}</strong> ?</p>
          ${role.users_count > 0 ? '<p class="text-red-600 mt-2">⚠️ Ce rôle est assigné à ' + role.users_count + ' utilisateur(s)</p>' : ''}
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler',
      }).then((result) => {
        if (result.isConfirmed) {
          this.deleteRole(role);
        }
      });
    },

    async deleteRole(role) {
      try {
        const response = await axios.delete(`/api/v1/admin/roles/${role.id}`);

        if (response.data.success) {
          this.$swal.fire({
            icon: 'success',
            title: 'Supprimé',
            text: response.data.message,
            timer: 2000,
          });
          this.loadRoles(this.pagination.current_page);
        }
      } catch (error) {
        console.error('Erreur suppression rôle:', error);
        this.$swal.fire({
          icon: 'error',
          title: 'Erreur',
          text: error.response?.data?.message || 'Impossible de supprimer ce rôle',
        });
      }
    },

    getTypeClass(type) {
      const classes = {
        'system': 'bg-red-100 text-red-800',
        'organizer': 'bg-yellow-100 text-yellow-800',
        'custom': 'bg-green-100 text-green-800',
      };
      return classes[type] || 'bg-gray-100 text-gray-800';
    },

    formatType(type) {
      const types = {
        'system': 'Système',
        'organizer': 'Organisateur',
        'custom': 'Personnalisé',
      };
      return types[type] || type;
    },
  },
};
</script>
