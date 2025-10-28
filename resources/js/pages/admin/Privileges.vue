<template>
  <div class="privileges-page p-6 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-primea-blue mb-2">Privilèges</h1>
          <p class="text-gray-600">Consultation des privilèges du système</p>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
      <h2 class="text-xl font-bold mb-4">Filtres</h2>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Module</label>
          <select v-model="filters.module" @change="loadPrivileges" class="w-full border rounded-lg px-3 py-2">
            <option value="">Tous les modules</option>
            <option value="auth">Authentification</option>
            <option value="events">Événements</option>
            <option value="orders">Commandes</option>
            <option value="payments">Paiements</option>
            <option value="tickets">Billets</option>
            <option value="scanning">Scan</option>
            <option value="organizers">Organisateurs</option>
            <option value="users">Utilisateurs</option>
            <option value="admin">Administration</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Action</label>
          <select v-model="filters.action" @change="loadPrivileges" class="w-full border rounded-lg px-3 py-2">
            <option value="">Toutes les actions</option>
            <option value="create">Créer</option>
            <option value="read">Lire</option>
            <option value="view">Voir</option>
            <option value="update">Modifier</option>
            <option value="delete">Supprimer</option>
            <option value="manage">Gérer</option>
            <option value="access">Accéder</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
          <select v-model="filters.status" @change="loadPrivileges" class="w-full border rounded-lg px-3 py-2">
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

    <!-- Privileges List -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-6 border-b">
        <h2 class="text-xl font-bold">Liste des Privilèges</h2>
        <p class="text-sm text-gray-600 mt-1">Total: {{ pagination.total }} privilège(s)</p>
      </div>

      <div v-if="loading" class="p-8 text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primea-blue mx-auto"></div>
        <p class="mt-4 text-gray-600">Chargement...</p>
      </div>

      <div v-else-if="privileges.length === 0" class="p-8 text-center text-gray-500">
        Aucun privilège trouvé
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slug</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Module</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rôles</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="privilege in privileges" :key="privilege.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 text-sm text-gray-900">{{ privilege.id }}</td>
              <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ privilege.name }}</td>
              <td class="px-6 py-4 text-sm text-gray-600 font-mono">{{ privilege.slug }}</td>
              <td class="px-6 py-4 text-sm">
                <span class="bg-purple-100 text-purple-800 px-2 py-1 rounded-full text-xs font-medium">
                  {{ formatModule(privilege.module) }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm">
                <span class="bg-indigo-100 text-indigo-800 px-2 py-1 rounded-full text-xs font-medium">
                  {{ formatAction(privilege.action) }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-900">
                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium">
                  {{ privilege.roles_count || 0 }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm">
                <span v-if="privilege.is_active" class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium">
                  Actif
                </span>
                <span v-else class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs font-medium">
                  Inactif
                </span>
              </td>
              <td class="px-6 py-4 text-sm">
                <button @click="viewDetails(privilege)"
                        class="text-primea-blue hover:text-blue-800">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </button>
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
      <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b">
          <div class="flex items-center justify-between">
            <h3 class="text-xl font-bold text-primea-blue">Détails du Privilège</h3>
            <button @click="closeDetailsModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <div v-if="selectedPrivilege" class="p-6">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">ID</label>
              <p class="text-gray-900">{{ selectedPrivilege.id }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
              <p class="text-gray-900 font-medium">{{ selectedPrivilege.name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
              <p class="text-gray-900 font-mono bg-gray-100 px-3 py-2 rounded">{{ selectedPrivilege.slug }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
              <p class="text-gray-900">{{ selectedPrivilege.description || 'Aucune description' }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Module</label>
              <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-sm font-medium">
                {{ formatModule(selectedPrivilege.module) }}
              </span>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Action</label>
              <span class="bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full text-sm font-medium">
                {{ formatAction(selectedPrivilege.action) }}
              </span>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Rôles associés</label>
              <div v-if="selectedPrivilege.roles && selectedPrivilege.roles.length > 0" class="flex flex-wrap gap-2 mt-2">
                <span v-for="role in selectedPrivilege.roles" :key="role.id"
                      class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                  {{ role.name }}
                </span>
              </div>
              <p v-else class="text-gray-500">Aucun rôle associé</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
              <span v-if="selectedPrivilege.is_active" class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                Actif
              </span>
              <span v-else class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-medium">
                Inactif
              </span>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Date de création</label>
              <p class="text-gray-900">{{ formatDate(selectedPrivilege.created_at) }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Dernière modification</label>
              <p class="text-gray-900">{{ formatDate(selectedPrivilege.updated_at) }}</p>
            </div>
          </div>
        </div>

        <div class="p-6 border-t bg-gray-50">
          <button @click="closeDetailsModal" class="w-full bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
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
  name: 'Privileges',

  data() {
    return {
      privileges: [],
      loading: false,
      showDetailsModal: false,
      selectedPrivilege: null,
      filters: {
        module: '',
        action: '',
        status: '',
      },
      pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 50,
        total: 0,
        from: 0,
        to: 0,
      },
    };
  },

  mounted() {
    this.loadPrivileges();
  },

  methods: {
    async loadPrivileges(page = 1) {
      this.loading = true;
      try {
        const params = {
          page,
          per_page: this.pagination.per_page,
          ...this.filters,
        };

        const response = await axios.get('/api/v1/admin/privileges', { params });

        if (response.data.success) {
          this.privileges = response.data.data.data;
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
        console.error('Erreur chargement privilèges:', error);
        Swal.fire({
          icon: 'error',
          title: 'Erreur',
          text: 'Impossible de charger les privilèges',
        });
      } finally {
        this.loading = false;
      }
    },

    resetFilters() {
      this.filters = {
        module: '',
        action: '',
        status: '',
      };
      this.loadPrivileges();
    },

    goToPage(page) {
      if (page >= 1 && page <= this.pagination.last_page) {
        this.loadPrivileges(page);
      }
    },

    viewDetails(privilege) {
      this.selectedPrivilege = privilege;
      this.showDetailsModal = true;
    },

    closeDetailsModal() {
      this.showDetailsModal = false;
      this.selectedPrivilege = null;
    },

    formatModule(module) {
      const modules = {
        'auth': 'Authentification',
        'events': 'Événements',
        'orders': 'Commandes',
        'payments': 'Paiements',
        'tickets': 'Billets',
        'scanning': 'Scan',
        'organizers': 'Organisateurs',
        'users': 'Utilisateurs',
        'admin': 'Administration',
      };
      return modules[module] || module;
    },

    formatAction(action) {
      const actions = {
        'create': 'Créer',
        'read': 'Lire',
        'view': 'Voir',
        'update': 'Modifier',
        'delete': 'Supprimer',
        'manage': 'Gérer',
        'access': 'Accéder',
      };
      return actions[action] || action;
    },

    formatDate(date) {
      if (!date) return 'N/A';
      return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
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
