<template>
  <div class="trashed-users-page p-6 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="mb-8">
      <div>
        <h1 class="text-3xl font-bold text-primea-blue mb-2">Corbeille - Utilisateurs Supprimés</h1>
        <p class="text-gray-600">Consulter et restaurer les utilisateurs supprimés</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
      <h2 class="text-xl font-bold mb-4">Filtres</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
          <input v-model="filters.search" @input="debouncedSearch" type="text"
                 placeholder="Nom ou email..." class="w-full border rounded-lg px-3 py-2">
        </div>

        <div class="flex items-end">
          <button @click="resetFilters" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
            Réinitialiser
          </button>
        </div>
      </div>
    </div>

    <!-- Trashed Users List -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-6 border-b">
        <h2 class="text-xl font-bold">Liste des Utilisateurs Supprimés</h2>
        <p class="text-sm text-gray-600 mt-1">Total: {{ pagination.total }} utilisateur(s) supprimé(s)</p>
      </div>

      <div v-if="loading" class="p-8 text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primea-blue mx-auto"></div>
        <p class="mt-4 text-gray-600">Chargement...</p>
      </div>

      <div v-else-if="trashedUsers.length === 0" class="p-8 text-center text-gray-500">
        Aucun utilisateur supprimé trouvé
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Utilisateur</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rôles</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date de suppression</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="user in trashedUsers" :key="user.id" class="hover:bg-gray-50">
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <div class="w-10 h-10 bg-gray-400 text-white rounded-full flex items-center justify-center font-bold">
                    {{ user.name.charAt(0).toUpperCase() }}
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                    <div class="text-xs text-gray-500">ID: {{ user.id }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 text-sm text-gray-900">{{ user.email }}</td>
              <td class="px-6 py-4 text-sm">
                <div class="flex flex-wrap gap-1">
                  <span v-for="role in user.roles" :key="role.id"
                        class="bg-gray-100 text-gray-800 px-2 py-1 rounded-full text-xs font-medium">
                    {{ role.name }}
                  </span>
                </div>
              </td>
              <td class="px-6 py-4 text-sm">
                <span :class="getUserTypeClass(user)" class="px-2 py-1 rounded-full text-xs font-medium">
                  {{ getUserTypeLabel(user) }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-600">
                {{ formatDate(user.deleted_at) }}
              </td>
              <td class="px-6 py-4 text-sm">
                <div class="flex items-center space-x-2">
                  <button @click="viewDetails(user)"
                          class="text-blue-600 hover:text-blue-800"
                          title="Voir les détails">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </button>
                  <button @click="confirmRestore(user)"
                          class="text-green-600 hover:text-green-800"
                          title="Restaurer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
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

    <!-- Details Modal -->
    <div v-if="showDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b">
          <div class="flex items-center justify-between">
            <h3 class="text-xl font-bold text-primea-blue">Détails de l'Utilisateur Supprimé</h3>
            <button @click="closeDetailsModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <div v-if="selectedUser" class="p-6">
          <div class="grid grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
              <p class="text-gray-900 font-medium">{{ selectedUser.name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
              <p class="text-gray-900">{{ selectedUser.email }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
              <p class="text-gray-900">{{ selectedUser.phone || 'N/A' }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
              <span :class="getUserTypeClass(selectedUser)" class="px-3 py-1 rounded-full text-sm font-medium">
                {{ getUserTypeLabel(selectedUser) }}
              </span>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Date d'inscription</label>
              <p class="text-gray-900">{{ formatDate(selectedUser.created_at) }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Date de suppression</label>
              <p class="text-red-600 font-medium">{{ formatDate(selectedUser.deleted_at) }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Email vérifié</label>
              <span v-if="selectedUser.email_verified_at" class="text-green-600">
                ✓ Vérifié le {{ formatDate(selectedUser.email_verified_at) }}
              </span>
              <span v-else class="text-orange-600">✗ Non vérifié</span>
            </div>
          </div>

          <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Rôles</label>
            <div class="flex flex-wrap gap-2">
              <span v-for="role in selectedUser.roles" :key="role.id"
                    class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-sm font-medium">
                {{ role.name }}
              </span>
            </div>
          </div>
        </div>

        <div class="p-6 border-t bg-gray-50 flex justify-end">
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
  name: 'TrashedUsers',

  data() {
    return {
      trashedUsers: [],
      loading: false,
      showDetailsModal: false,
      selectedUser: null,
      searchTimeout: null,
      filters: {
        search: '',
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
    this.loadTrashedUsers();
  },

  methods: {
    async loadTrashedUsers(page = 1) {
      this.loading = true;
      try {
        const params = {
          page,
          per_page: this.pagination.per_page,
          ...this.filters,
        };

        const response = await axios.get('/api/v1/admin/users/trashed', { params });

        if (response.data.success) {
          this.trashedUsers = response.data.data.data;
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
        console.error('Erreur chargement utilisateurs supprimés:', error);
        Swal.fire({
          icon: 'error',
          title: 'Erreur',
          text: 'Impossible de charger les utilisateurs supprimés',
        });
      } finally {
        this.loading = false;
      }
    },

    debouncedSearch() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.loadTrashedUsers();
      }, 500);
    },

    resetFilters() {
      this.filters = {
        search: '',
      };
      this.loadTrashedUsers();
    },

    goToPage(page) {
      if (page >= 1 && page <= this.pagination.last_page) {
        this.loadTrashedUsers(page);
      }
    },

    viewDetails(user) {
      this.selectedUser = user;
      this.showDetailsModal = true;
    },

    closeDetailsModal() {
      this.showDetailsModal = false;
      this.selectedUser = null;
    },

    confirmRestore(user) {
      Swal.fire({
        title: 'Restaurer cet utilisateur ?',
        html: `<p>Voulez-vous vraiment restaurer <strong>${user.name}</strong> ?</p><p class="text-sm text-gray-600 mt-2">L'utilisateur sera réactivé et pourra à nouveau accéder à son compte.</p>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Oui, restaurer',
        cancelButtonText: 'Annuler',
      }).then((result) => {
        if (result.isConfirmed) {
          this.restoreUser(user);
        }
      });
    },

    async restoreUser(user) {
      try {
        const response = await axios.post(`/api/v1/admin/users/${user.id}/restore`);

        if (response.data.success) {
          Swal.fire({
            icon: 'success',
            title: 'Restauré',
            text: response.data.message,
            timer: 2000,
          });
          this.loadTrashedUsers(this.pagination.current_page);
        }
      } catch (error) {
        console.error('Erreur restauration utilisateur:', error);
        Swal.fire({
          icon: 'error',
          title: 'Erreur',
          text: error.response?.data?.message || 'Impossible de restaurer cet utilisateur',
        });
      }
    },

    getUserTypeClass(user) {
      if (user.is_admin) return 'bg-red-100 text-red-800';
      if (user.is_organizer) return 'bg-yellow-100 text-yellow-800';
      return 'bg-green-100 text-green-800';
    },

    getUserTypeLabel(user) {
      if (user.is_admin) return 'Admin';
      if (user.is_organizer) return 'Organisateur';
      return 'Client';
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
