<template>
  <div class="clients-page p-6 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="mb-8">
      <div>
        <h1 class="text-3xl font-bold text-primea-blue mb-2">Gestion des Clients</h1>
        <p class="text-gray-600">Gérer les comptes clients</p>
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
          <select v-model="filters.status" @change="loadClients" class="w-full border rounded-lg px-3 py-2">
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

    <!-- Clients List -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-6 border-b">
        <h2 class="text-xl font-bold">Liste des Clients</h2>
        <p class="text-sm text-gray-600 mt-1">Total: {{ pagination.total }} client(s)</p>
      </div>

      <div v-if="loading" class="p-8 text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primea-blue mx-auto"></div>
        <p class="mt-4 text-gray-600">Chargement...</p>
      </div>

      <div v-else-if="clients.length === 0" class="p-8 text-center text-gray-500">
        Aucun client trouvé
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Utilisateur</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Commandes</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Billets</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rôles</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Inscription</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="client in clients" :key="client.id" class="hover:bg-gray-50">
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <div class="w-10 h-10 bg-primea-blue text-white rounded-full flex items-center justify-center font-bold">
                    {{ client.name.charAt(0).toUpperCase() }}
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">{{ client.name }}</div>
                    <div class="text-xs text-gray-500">ID: {{ client.id }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 text-sm text-gray-900">{{ client.email }}</td>
              <td class="px-6 py-4 text-sm">
                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium">
                  {{ client.orders_count || 0 }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm">
                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium">
                  {{ client.tickets_count || 0 }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm">
                <div class="flex flex-wrap gap-1">
                  <span v-for="role in client.roles" :key="role.id"
                        class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium">
                    {{ role.name }}
                  </span>
                </div>
              </td>
              <td class="px-6 py-4 text-sm">
                <span :class="getStatusClass(client.status)" class="px-2 py-1 rounded-full text-xs font-medium">
                  {{ formatStatus(client.status) }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-600">
                {{ formatDate(client.created_at) }}
              </td>
              <td class="px-6 py-4 text-sm">
                <div class="flex items-center space-x-2">
                  <button @click="viewDetails(client)"
                          class="text-blue-600 hover:text-blue-800"
                          title="Voir les détails">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </button>
                  <button @click="openEditModal(client)"
                          class="text-green-600 hover:text-green-800"
                          title="Modifier">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button @click="toggleStatus(client)"
                          :class="client.status === 'active' ? 'text-orange-600 hover:text-orange-800' : 'text-green-600 hover:text-green-800'"
                          :title="client.status === 'active' ? 'Désactiver' : 'Activer'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                  </button>
                  <button @click="confirmDelete(client)"
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
              {{ isEditMode ? 'Modifier le Client' : 'Créer un Client' }}
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
            <h3 class="text-xl font-bold text-primea-blue">Détails du Client</h3>
            <button @click="closeDetailsModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <div v-if="selectedClient" class="p-6">
          <div class="grid grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
              <p class="text-gray-900 font-medium">{{ selectedClient.name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
              <p class="text-gray-900">{{ selectedClient.email }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
              <p class="text-gray-900">{{ selectedClient.phone || 'N/A' }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
              <span :class="getStatusClass(selectedClient.status)" class="px-3 py-1 rounded-full text-sm font-medium">
                {{ formatStatus(selectedClient.status) }}
              </span>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Commandes</label>
              <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                {{ selectedClient.orders_count || 0 }}
              </span>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Billets</label>
              <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                {{ selectedClient.tickets_count || 0 }}
              </span>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Date d'inscription</label>
              <p class="text-gray-900">{{ formatDate(selectedClient.created_at) }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Email vérifié</label>
              <span v-if="selectedClient.email_verified_at" class="text-green-600">
                ✓ Vérifié le {{ formatDate(selectedClient.email_verified_at) }}
              </span>
              <span v-else class="text-orange-600">✗ Non vérifié</span>
            </div>
          </div>

          <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Rôles</label>
            <div class="flex flex-wrap gap-2">
              <span v-for="role in selectedClient.roles" :key="role.id"
                    class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                {{ role.name }}
              </span>
            </div>
          </div>
        </div>

        <div class="p-6 border-t bg-gray-50 flex justify-between">
          <button @click="resetPassword(selectedClient)"
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
  name: 'Clients',

  data() {
    return {
      clients: [],
      loading: false,
      submitting: false,
      showFormModal: false,
      showDetailsModal: false,
      isEditMode: false,
      selectedClient: null,
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
    this.loadClients();
  },

  methods: {
    async loadClients(page = 1) {
      this.loading = true;
      try {
        const params = {
          page,
          per_page: this.pagination.per_page,
          ...this.filters,
        };

        const response = await axios.get('/api/v1/admin/users/clients', { params });

        if (response.data.success) {
          this.clients = response.data.data.data;
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
        console.error('Erreur chargement clients:', error);
        Swal.fire({
          icon: 'error',
          title: 'Erreur',
          text: 'Impossible de charger les clients',
        });
      } finally {
        this.loading = false;
      }
    },

    debouncedSearch() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.loadClients();
      }, 500);
    },

    resetFilters() {
      this.filters = {
        search: '',
        status: '',
      };
      this.loadClients();
    },

    goToPage(page) {
      if (page >= 1 && page <= this.pagination.last_page) {
        this.loadClients(page);
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

    openEditModal(client) {
      this.isEditMode = true;
      this.selectedClient = client;
      this.formData = {
        name: client.name,
        email: client.email,
        status: client.status,
      };
      this.showFormModal = true;
    },

    closeFormModal() {
      this.showFormModal = false;
      this.selectedClient = null;
    },

    async submitForm() {
      this.submitting = true;
      try {
        const data = { ...this.formData, is_admin: false };

        const url = this.isEditMode
          ? `/api/v1/admin/users/${this.selectedClient.id}`
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
          this.loadClients(this.pagination.current_page);
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

    viewDetails(client) {
      this.selectedClient = client;
      this.showDetailsModal = true;
    },

    closeDetailsModal() {
      this.showDetailsModal = false;
      this.selectedClient = null;
    },

    async toggleStatus(client) {
      try {
        const response = await axios.post(`/api/v1/admin/users/${client.id}/toggle-status`);

        if (response.data.success) {
          Swal.fire({
            icon: 'success',
            title: 'Succès',
            text: response.data.message,
            timer: 2000,
          });
          this.loadClients(this.pagination.current_page);
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

    confirmDelete(client) {
      Swal.fire({
        title: 'Supprimer ce client ?',
        html: `<p>Voulez-vous vraiment supprimer <strong>${client.name}</strong> ?</p><p class="text-sm text-gray-600 mt-2">Cette action effectue une suppression douce (soft delete). L'utilisateur peut être restauré.</p>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler',
      }).then((result) => {
        if (result.isConfirmed) {
          this.deleteClient(client);
        }
      });
    },

    async deleteClient(client) {
      try {
        const response = await axios.delete(`/api/v1/admin/users/${client.id}`);

        if (response.data.success) {
          Swal.fire({
            icon: 'success',
            title: 'Supprimé',
            text: response.data.message,
            timer: 2000,
          });
          this.loadClients(this.pagination.current_page);
        }
      } catch (error) {
        console.error('Erreur suppression client:', error);
        Swal.fire({
          icon: 'error',
          title: 'Erreur',
          text: error.response?.data?.message || 'Impossible de supprimer ce client',
        });
      }
    },

    async resetPassword(client) {
      Swal.fire({
        title: 'Réinitialiser le mot de passe ?',
        html: `<p>Un email de réinitialisation sera envoyé à <strong>${client.email}</strong></p>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Oui, envoyer',
        cancelButtonText: 'Annuler',
      }).then(async (result) => {
        if (result.isConfirmed) {
          try {
            const response = await axios.post(`/api/v1/admin/users/${client.id}/reset-password`);

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

<style scoped>
* {
  font-family: 'Inter', 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
</style>
