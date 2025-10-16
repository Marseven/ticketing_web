<template>
  <div class="organizer-dashboard min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <!-- Header du dashboard -->
      <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-primea-blue font-primea mb-2">Tableau de Bord Organisateur</h1>
          <p class="text-gray-600 font-primea">Bienvenue dans votre espace de gestion d'événements</p>
        </div>
        <div class="flex items-center space-x-4">
          <div class="text-right">
            <p class="text-sm text-gray-500 font-primea">Dernière connexion</p>
            <p class="text-sm font-semibold text-primea-blue font-primea">{{ formatDate(new Date()) }}</p>
          </div>
          <div v-if="userAvatar" class="w-12 h-12 rounded-full overflow-hidden">
            <img :src="userAvatar" :alt="authStore.user?.name" class="w-full h-full object-cover">
          </div>
          <div v-else class="w-12 h-12 bg-primea-blue text-white rounded-full flex items-center justify-center">
            <span class="text-lg font-bold font-primea">{{ userInitials }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Cartes de statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <!-- Total Événements -->
      <div class="bg-white rounded-primea shadow-primea p-6 border-l-4 border-primea-blue">
        <div class="flex items-center">
          <div class="p-2 bg-primea-blue/10 rounded-lg">
            <CalendarIcon class="w-6 h-6 text-primea-blue" />
          </div>
          <div class="ml-4">
            <p class="text-sm text-gray-600 font-primea">Événements Total</p>
            <p class="text-2xl font-bold text-primea-blue font-primea">{{ stats.total_events }}</p>
          </div>
        </div>
      </div>

      <!-- Événements Actifs -->
      <div class="bg-white rounded-primea shadow-primea p-6 border-l-4 border-primea-blue">
        <div class="flex items-center">
          <div class="p-2 rounded-lg" style="background-color: rgba(39, 45, 99, 0.15);">
            <PlayIcon class="w-6 h-6 text-primea-blue" />
          </div>
          <div class="ml-4">
            <p class="text-sm text-gray-600 font-primea">Événements Actifs</p>
            <p class="text-2xl font-bold text-primea-blue font-primea">{{ stats.active_events }}</p>
          </div>
        </div>
      </div>

      <!-- Billets Vendus -->
      <div class="bg-white rounded-primea shadow-primea p-6 border-l-4 border-primea-yellow">
        <div class="flex items-center">
          <div class="p-2 bg-primea-yellow/10 rounded-lg">
            <TicketIcon class="w-6 h-6 text-primea-yellow" />
          </div>
          <div class="ml-4">
            <p class="text-sm text-gray-600 font-primea">Billets Vendus</p>
            <p class="text-2xl font-bold text-primea-yellow font-primea">{{ stats.tickets_sold }}</p>
          </div>
        </div>
      </div>

      <!-- Revenus -->
      <div class="bg-white rounded-primea shadow-primea p-6 border-l-4 border-primea-blue">
        <div class="flex items-center">
          <div class="p-2 rounded-lg" style="background-color: rgba(39, 45, 99, 0.2);">
            <CurrencyDollarIcon class="w-6 h-6 text-primea-blue" />
          </div>
          <div class="ml-4">
            <p class="text-sm text-gray-600 font-primea">Revenus</p>
            <p class="text-2xl font-bold text-primea-blue font-primea">{{ formatAmount(stats.total_revenue) }} XAF</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Actions rapides -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
      <!-- Actions principales -->
      <div class="lg:col-span-2">
        <div class="bg-white rounded-primea shadow-primea p-6">
          <h2 class="text-xl font-semibold text-primea-blue font-primea mb-6">Actions Rapides</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <router-link 
              :to="{ name: 'organizer-event-create' }"
              class="flex items-center p-4 bg-primea-blue text-white rounded-primea hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200 group"
            >
              <PlusIcon class="w-6 h-6 mr-3 group-hover:scale-110 transition-transform" />
              <div>
                <p class="font-semibold font-primea">Créer un Événement</p>
                <p class="text-sm opacity-90">Nouveau projet d'événement</p>
              </div>
            </router-link>

            <router-link 
              :to="{ name: 'organizer-events' }"
              class="flex items-center p-4 border-2 border-primea-blue text-primea-blue rounded-primea hover:bg-primea-blue hover:text-white transition-all duration-200 group"
            >
              <EyeIcon class="w-6 h-6 mr-3 group-hover:scale-110 transition-transform" />
              <div>
                <p class="font-semibold font-primea group-hover:text-white">Gérer les Événements</p>
                <p class="text-sm opacity-70 group-hover:text-white group-hover:opacity-90">Voir et modifier</p>
              </div>
            </router-link>

            <router-link 
              :to="{ name: 'organizer-balance' }"
              class="flex items-center p-4 border-2 border-primea-yellow text-primea-yellow rounded-primea hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200 group"
            >
              <CreditCardIcon class="w-6 h-6 mr-3 group-hover:scale-110 transition-transform" />
              <div>
                <p class="font-semibold font-primea group-hover:text-primea-blue">Gestion Financière</p>
                <p class="text-sm opacity-70 group-hover:text-primea-blue group-hover:opacity-90">Soldes et payouts</p>
              </div>
            </router-link>

            <router-link 
              :to="{ name: 'organizer-profile' }"
              class="flex items-center p-4 border-2 border-gray-400 text-gray-600 rounded-primea hover:bg-gray-400 hover:text-white transition-all duration-200 group"
            >
              <UserIcon class="w-6 h-6 mr-3 group-hover:scale-110 transition-transform" />
              <div>
                <p class="font-semibold font-primea">Mon Profil</p>
                <p class="text-sm opacity-70">Informations personnelles</p>
              </div>
            </router-link>
          </div>
        </div>
      </div>

      <!-- Notifications récentes -->
      <div class="bg-white rounded-primea shadow-primea p-6">
        <h2 class="text-xl font-semibold text-primea-blue font-primea mb-6">Notifications</h2>
        <div class="space-y-4">
          <div v-if="notifications.length === 0" class="text-center text-gray-500 py-8">
            <BellIcon class="w-12 h-12 mx-auto text-gray-300 mb-2" />
            <p class="font-primea">Aucune notification</p>
          </div>
          <div v-else v-for="notification in notifications" :key="notification.id" 
               class="p-3 bg-gray-50 rounded-primea border-l-4" 
               :class="getNotificationClass(notification.type)">
            <p class="text-sm font-medium font-primea">{{ notification.title }}</p>
            <p class="text-xs text-gray-600 mt-1">{{ notification.message }}</p>
            <p class="text-xs text-gray-400 mt-2">{{ formatDateTime(notification.created_at) }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Événements récents -->
    <div class="bg-white rounded-primea shadow-primea overflow-hidden">
      <div class="p-6 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h2 class="text-xl font-semibold text-primea-blue font-primea">Événements Récents</h2>
          <router-link 
            :to="{ name: 'organizer-events' }"
            class="text-primea-blue hover:text-primea-yellow transition-colors font-primea text-sm"
          >
            Voir tous
          </router-link>
        </div>
      </div>
      
      <div v-if="recentEvents.length === 0" class="p-8 text-center text-gray-500">
        <CalendarIcon class="w-16 h-16 mx-auto text-gray-300 mb-4" />
        <p class="font-primea text-lg">Aucun événement créé</p>
        <p class="font-primea text-sm mt-2">Commencez par créer votre premier événement</p>
        <router-link 
          :to="{ name: 'organizer-event-create' }"
          class="inline-flex items-center mt-4 px-4 py-2 bg-primea-blue text-white rounded-primea hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200 font-primea"
        >
          <PlusIcon class="w-4 h-4 mr-2" />
          Créer un événement
        </router-link>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase font-primea">Événement</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase font-primea">Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase font-primea">Statut</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase font-primea">Billets</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase font-primea">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="event in recentEvents" :key="event.id" class="hover:bg-gray-50">
              <td class="px-6 py-4">
                <div>
                  <p class="text-sm font-medium text-gray-900 font-primea">{{ event.title }}</p>
                  <p class="text-sm text-gray-500">{{ event.venue_name }}</p>
                </div>
              </td>
              <td class="px-6 py-4 text-sm text-gray-900 font-primea">{{ formatDate(event.event_date) }}</td>
              <td class="px-6 py-4">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" 
                      :class="getEventStatusClass(event.status)">
                  {{ getEventStatusName(event.status) }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-900 font-primea">{{ event.tickets_sold || 0 }}</td>
              <td class="px-6 py-4 text-sm">
                <router-link 
                  :to="{ name: 'organizer-event-detail', params: { slug: event.slug } }"
                  class="text-primea-blue hover:text-primea-yellow font-primea"
                >
                  Gérer
                </router-link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { organizerService } from '../../services/api';
import { 
  CalendarIcon,
  PlayIcon,
  TicketIcon,
  CurrencyDollarIcon,
  PlusIcon,
  EyeIcon,
  CreditCardIcon,
  UserIcon,
  BellIcon
} from '@heroicons/vue/24/outline';

const authStore = useAuthStore();

// État réactif
const loading = ref(false);
const stats = reactive({
  total_events: 0,
  active_events: 0,
  tickets_sold: 0,
  total_revenue: 0
});

const recentEvents = ref([]);
const notifications = ref([]);

// Computed
const userInitials = computed(() => {
  const user = authStore.user;
  if (!user?.name) return 'U';
  return user.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
});

const userAvatar = computed(() => {
  return authStore.user?.avatar_url || null;
});

// Méthodes
const loadDashboardData = async () => {
  loading.value = true;
  try {
    // Charger les statistiques depuis l'API
    const [statsResponse, eventsResponse, notificationsResponse] = await Promise.all([
      organizerService.getDashboardStats(),
      organizerService.getRecentEvents(),
      organizerService.getNotifications()
    ]);

    // Mettre à jour les statistiques
    Object.assign(stats, statsResponse.data.data || {
      total_events: 0,
      active_events: 0,
      tickets_sold: 0,
      total_revenue: 0
    });

    // Mettre à jour les événements récents
    recentEvents.value = eventsResponse.data.data || [];

    // Mettre à jour les notifications
    notifications.value = notificationsResponse.data.data || [];

  } catch (error) {
    console.error('Erreur lors du chargement du dashboard:', error);
    // En cas d'erreur, utiliser des données par défaut
    Object.assign(stats, {
      total_events: 0,
      active_events: 0,
      tickets_sold: 0,
      total_revenue: 0
    });
    recentEvents.value = [];
    notifications.value = [];
  } finally {
    loading.value = false;
  }
};

// Utilitaires
const formatAmount = (amount) => {
  return new Intl.NumberFormat('fr-FR').format(amount);
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('fr-FR');
};

const formatDateTime = (datetime) => {
  return new Date(datetime).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getEventStatusName = (status) => {
  const names = {
    active: 'Actif',
    draft: 'Brouillon',
    published: 'Publié',
    cancelled: 'Annulé',
    completed: 'Terminé'
  };
  return names[status] || status;
};

const getEventStatusClass = (status) => {
  const classes = {
    active: 'bg-green-100 text-green-800',
    draft: 'bg-gray-100 text-gray-800',
    published: 'bg-blue-100 text-blue-800',
    cancelled: 'bg-red-100 text-red-800',
    completed: 'bg-purple-100 text-purple-800'
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};

const getNotificationClass = (type) => {
  const classes = {
    success: 'border-green-500',
    warning: 'border-yellow-500',
    error: 'border-red-500',
    info: 'border-blue-500'
  };
  return classes[type] || 'border-gray-300';
};

onMounted(() => {
  loadDashboardData();
});
</script>

<style scoped>
/* Style personnalisé pour le dashboard organisateur */
.organizer-dashboard {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  font-family: 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Couleurs Primea */
.text-primea-blue {
  color: #272d63;
}

.text-primea-yellow {
  color: #fab511;
}

.bg-primea-blue {
  background-color: #272d63;
}

.bg-primea-yellow {
  background-color: #fab511;
}

.border-primea-blue {
  border-color: #272d63;
}

.border-primea-yellow {
  border-color: #fab511;
}

.hover\:bg-primea-blue:hover {
  background-color: #272d63;
}

.hover\:bg-primea-yellow:hover {
  background-color: #fab511;
}

.hover\:text-primea-blue:hover {
  color: #272d63;
}

.hover\:text-primea-yellow:hover {
  color: #fab511;
}

/* Coins arrondis Primea */
.rounded-primea {
  border-radius: 12px;
}

/* Ombres Primea */
.shadow-primea {
  box-shadow: 0 2px 15px rgba(39, 45, 99, 0.08);
}

/* Police Primea */
.font-primea {
  font-family: 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
</style>