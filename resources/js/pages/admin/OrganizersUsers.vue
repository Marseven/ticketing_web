<template>
  <div class="organizers-users-page p-6 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-primea-blue mb-2">Gestion des Utilisateurs Organisateurs</h1>
          <p class="text-gray-600">Créer et gérer les comptes utilisateurs organisateurs</p>
        </div>
        <button @click="openCreateModal"
                class="bg-primea-blue text-white px-4 py-2 rounded-lg hover:bg-blue-800">
          <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
          Créer un Organisateur User
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
      <h2 class="text-xl font-bold mb-4">Filtres</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
          <input v-model="filters.search" @input="debouncedSearch" type="text"
                 placeholder="Nom ou email..." class="w-full border rounded-lg px-3 py-2">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
          <select v-model="filters.status" @change="loadOrganizersUsers" class="w-full border rounded-lg px-3 py-2">
            <option value="">Tous les statuts</option>
            <option value="active">Actif</option>
            <option value="inactive">Inactif</option>
            <option value="suspended">Suspendu</option>
          </select>
        </div>

        <div class="flex items-end">
          <button @click="resetFilters" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
            Réinitialiser
          </button>
        </div>
      </div>
    </div>

    <!-- Organizers Users List -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-6 border-b">
        <h2 class="text-xl font-bold">Liste des Utilisateurs Organisateurs</h2>
        <p class="text-sm text-gray-600 mt-1">Total: {{ pagination.total }} utilisateur(s) organisateur(s)</p>
      </div>

      <div v-if="loading" class="p-8 text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primea-blue mx-auto"></div>
        <p class="mt-4 text-gray-600">Chargement...</p>
      </div>

      <div v-else-if="organizersUsers.length === 0" class="p-8 text-center text-gray-500">
        Aucun utilisateur organisateur trouvé
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Utilisateur</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Organisateurs</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rôles</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Inscription</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="organizerUser in organizersUsers" :key="organizerUser.id" class="hover:bg-gray-50">
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <div class="w-10 h-10 bg-primea-blue text-white rounded-full flex items-center justify-center font-bold">
                    {{ organizerUser.name.charAt(0).toUpperCase() }}
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">{{ organizerUser.name }}</div>
                    <div class="text-xs text-gray-500">ID: {{ organizerUser.id }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 text-sm text-gray-900">{{ organizerUser.email }}</td>
              <td class="px-6 py-4 text-sm">
                <span class="bg-orange-100 text-orange-800 px-3 py-1 rounded-full text-sm font-medium">
                  {{ organizerUser.organizers_count || 0 }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm">
                <div class="flex flex-wrap gap-1">
                  <span v-for="role in organizerUser.roles" :key="role.id"
                        class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs font-medium">
                    {{ role.name }}
                  </span>
                </div>
              </td>
              <td class="px-6 py-4 text-sm">
                <span :class="getStatusClass(organizerUser.status)" class="px-2 py-1 rounded-full text-xs font-medium">
                  {{ formatStatus(organizerUser.status) }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-600">
                {{ formatDate(organizerUser.created_at) }}
              </td>
              <td class="px-6 py-4 text-sm">
                <div class="flex items-center space-x-2">
                  <button @click="viewDetails(organizerUser)"
                          class="text-blue-600 hover:text-blue-800"
                          title="Voir les détails">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </button>
                  <button @click="openEditModal(organizerUser)"
                          class="text-green-600 hover:text-green-800"
                          title="Modifier">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button @click="toggleStatus(organizerUser)"
                          :class="organizerUser.status === 'active' ? 'text-orange-600 hover:text-orange-800' : 'text-green-600 hover:text-green-800'"
                          :title="organizerUser.status === 'active' ? 'Désactiver' : 'Activer'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                  </button>
                  <button @click="confirmDelete(organizerUser)"
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
              {{ isEditMode ? 'Modifier l\'Utilisateur Organisateur' : 'Créer un Utilisateur Organisateur' }}
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
              <label class="block text-sm font-medium text-gray-700 mb-2">Nom complet *</label>
              <input v-model="formData.name" type="text" required
                     class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-primea-blue">
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
              <input v-model="formData.email" type="email" required
                     class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-primea-blue">
            </div>

            <div v-if="isEditMode">
              <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
              <select v-model="formData.status"
                      class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-primea-blue">
                <option value="active">Actif</option>
                <option value="inactive">Inactif</option>
                <option value="suspended">Suspendu</option>
              </select>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <p class="text-sm text-blue-800">
                {{ isEditMode ? 'Les modifications seront appliquées immédiatement.' : 'Un email sera envoyé à l\'utilisateur pour définir son mot de passe.' }}
              </p>
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
            <h3 class="text-xl font-bold text-primea-blue">Détails de l'Utilisateur Organisateur</h3>
            <button @click="closeDetailsModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <div v-if="selectedOrganizerUser" class="p-6">
          <div class="grid grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
              <p class="text-gray-900 font-medium">{{ selectedOrganizerUser.name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
              <p class="text-gray-900">{{ selectedOrganizerUser.email }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
              <p class="text-gray-900">{{ selectedOrganizerUser.phone || 'N/A' }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Organisateurs</label>
              <span class="bg-orange-100 text-orange-800 px-3 py-1 rounded-full text-sm font-medium">
                {{ selectedOrganizerUser.organizers_count || 0 }}
              </span>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
              <span :class="getStatusClass(selectedOrganizerUser.status)" class="px-3 py-1 rounded-full text-sm font-medium">
                {{ formatStatus(selectedOrganizerUser.status) }}
              </span>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Date d'inscription</label>
              <p class="text-gray-900">{{ formatDate(selectedOrganizerUser.created_at) }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Email vérifié</label>
              <span v-if="selectedOrganizerUser.email_verified_at" class="text-green-600">
                ✓ Vérifié le {{ formatDate(selectedOrganizerUser.email_verified_at) }}
              </span>
              <span v-else class="text-orange-600">✗ Non vérifié</span>
            </div>
          </div>

          <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Rôles</label>
            <div class="flex flex-wrap gap-2">
              <span v-for="role in selectedOrganizerUser.roles" :key="role.id"
                    class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-medium">
                {{ role.name }}
              </span>
            </div>
          </div>
        </div>

        <div class="p-6 border-t bg-gray-50 flex justify-between">
          <button @click="resetPassword(selectedOrganizerUser)"
                  class="bg-orange-500 text-white px-4 py-2 rounded-lg hover:bg-orange-600">
            Réinitialiser le mot de passe
          </button>
          <button @click="closeDetailsModal"
                  class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
            Fermer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
  name: 'OrganizersUsers',

  data() {
    return {
      organizersUsers: [],
      loading: false,
      submitting: false,
      showFormModal: false,
      showDetailsModal: false,
      isEditMode: false,
      selectedOrganizerUser: null,
      searchTimeout: null,
      filters: {
        search: '',
        status: '',
      },
      formData: {
        name: '',
        email: '',
        status: 'active',
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

  mounted() {
    this.loadOrganizersUsers();
  },

  methods: {
    async loadOrganizersUsers(page = 1) {
      this.loading = true;
      try {
        const params = {
          page,
          per_page: this.pagination.per_page,
          ...this.filters,
        };

        const response = await axios.get('/api/v1/admin/users/organizers-users', { params });

        if (response.data.success) {
          this.organizersUsers = response.data.data.data;
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
        console.error('Erreur chargement organisateurs users:', error);
        Swal.fire({
          icon: 'error',
          title: 'Erreur',
          text: 'Impossible de charger les utilisateurs organisateurs',
        });
      } finally {
        this.loading = false;
      }
    },

    debouncedSearch() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.loadOrganizersUsers();
      }, 500);
    },

    resetFilters() {
      this.filters = {
        search: '',
        status: '',
      };
      this.loadOrganizersUsers();
    },

    goToPage(page) {
      if (page >= 1 && page <= this.pagination.last_page) {
        this.loadOrganizersUsers(page);
      }
    },

    openCreateModal() {
      this.isEditMode = false;
      this.formData = {
        name: '',
        email: '',
        status: 'active',
      };
      this.showFormModal = true;
    },

    openEditModal(organizerUser) {
      this.isEditMode = true;
      this.selectedOrganizerUser = organizerUser;
      this.formData = {
        name: organizerUser.name,
        email: organizerUser.email,
        status: organizerUser.status,
      };
      this.showFormModal = true;
    },

    closeFormModal() {
      this.showFormModal = false;
      this.selectedOrganizerUser = null;
    },

    async submitForm() {
      this.submitting = true;
      try {
        const data = { ...this.formData, is_organizer: true };

        const url = this.isEditMode
          ? `/api/v1/admin/users/${this.selectedOrganizerUser.id}`
          : '/api/v1/admin/users';

        const method = this.isEditMode ? 'put' : 'post';

        const response = await axios[method](url, data);

        if (response.data.success) {
          Swal.fire({
            icon: 'success',
            title: 'Succès',
            text: response.data.message,
            timer: 2000,
          });
          this.closeFormModal();
          this.loadOrganizersUsers(this.pagination.current_page);
        }
      } catch (error) {
        console.error('Erreur soumission formulaire:', error);
        Swal.fire({
          icon: 'error',
          title: 'Erreur',
          text: error.response?.data?.message || 'Une erreur est survenue',
        });
      } finally {
        this.submitting = false;
      }
    },

    viewDetails(organizerUser) {
      this.selectedOrganizerUser = organizerUser;
      this.showDetailsModal = true;
    },

    closeDetailsModal() {
      this.showDetailsModal = false;
      this.selectedOrganizerUser = null;
    },

    async toggleStatus(organizerUser) {
      try {
        const response = await axios.post(`/api/v1/admin/users/${organizerUser.id}/toggle-status`);

        if (response.data.success) {
          Swal.fire({
            icon: 'success',
            title: 'Succès',
            text: response.data.message,
            timer: 2000,
          });
          this.loadOrganizersUsers(this.pagination.current_page);
        }
      } catch (error) {
        console.error('Erreur changement statut:', error);
        Swal.fire({
          icon: 'error',
          title: 'Erreur',
          text: error.response?.data?.message || 'Une erreur est survenue',
        });
      }
    },

    confirmDelete(organizerUser) {
      Swal.fire({
        title: 'Supprimer cet utilisateur organisateur ?',
        html: `<p>Voulez-vous vraiment supprimer <strong>${organizerUser.name}</strong> ?</p><p class="text-sm text-gray-600 mt-2">Cette action effectue une suppression douce (soft delete). L'utilisateur peut être restauré.</p>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler',
      }).then((result) => {
        if (result.isConfirmed) {
          this.deleteOrganizerUser(organizerUser);
        }
      });
    },

    async deleteOrganizerUser(organizerUser) {
      try {
        const response = await axios.delete(`/api/v1/admin/users/${organizerUser.id}`);

        if (response.data.success) {
          Swal.fire({
            icon: 'success',
            title: 'Supprimé',
            text: response.data.message,
            timer: 2000,
          });
          this.loadOrganizersUsers(this.pagination.current_page);
        }
      } catch (error) {
        console.error('Erreur suppression organisateur user:', error);
        Swal.fire({
          icon: 'error',
          title: 'Erreur',
          text: error.response?.data?.message || 'Impossible de supprimer cet utilisateur organisateur',
        });
      }
    },

    async resetPassword(organizerUser) {
      Swal.fire({
        title: 'Réinitialiser le mot de passe ?',
        html: `<p>Un email de réinitialisation sera envoyé à <strong>${organizerUser.email}</strong></p>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Oui, envoyer',
        cancelButtonText: 'Annuler',
      }).then(async (result) => {
        if (result.isConfirmed) {
          try {
            const response = await axios.post(`/api/v1/admin/users/${organizerUser.id}/reset-password`);

            if (response.data.success) {
              Swal.fire({
                icon: 'success',
                title: 'Email envoyé',
                text: response.data.message,
              });
            }
          } catch (error) {
            Swal.fire({
              icon: 'error',
              title: 'Erreur',
              text: error.response?.data?.message || 'Impossible d\'envoyer l\'email',
            });
          }
        }
      });
    },

    getStatusClass(status) {
      const classes = {
        'active': 'bg-green-100 text-green-800',
        'inactive': 'bg-gray-100 text-gray-800',
        'suspended': 'bg-red-100 text-red-800',
      };
      return classes[status] || 'bg-gray-100 text-gray-800';
    },

    formatStatus(status) {
      const statuses = {
        'active': 'Actif',
        'inactive': 'Inactif',
        'suspended': 'Suspendu',
      };
      return statuses[status] || status;
    },

    formatDate(date) {
      if (!date) return 'N/A';
      return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
      });
    },
  },
};
</script>
