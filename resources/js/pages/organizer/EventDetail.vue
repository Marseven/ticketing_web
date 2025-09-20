<template>
  <div class="event-detail min-h-screen" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%)">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <!-- Loading state -->
      <div v-if="loading" class="flex justify-center items-center h-64">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primea-blue"></div>
      </div>

      <!-- Error state -->
      <div v-else-if="error" class="text-center py-16">
        <div class="mb-4">
          <ExclamationTriangleIcon class="w-16 h-16 mx-auto text-red-500" />
        </div>
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Événement introuvable</h2>
        <p class="text-gray-600 mb-6">{{ error }}</p>
        <router-link 
          :to="{ name: 'organizer-events' }"
          class="bg-primea-blue text-white px-6 py-3 rounded-primea hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200 font-primea"
        >
          Retour aux événements
        </router-link>
      </div>

      <!-- Event content -->
      <div v-else-if="event">
        <!-- Header -->
        <div class="mb-8">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <button 
                @click="$router.go(-1)"
                class="text-primea-blue hover:text-primea-yellow transition-colors"
              >
                <ArrowLeftIcon class="w-6 h-6" />
              </button>
              <div>
                <h1 class="text-3xl font-bold text-primea-blue font-primea">{{ event.title }}</h1>
                <p class="text-gray-600 font-primea mt-1">Gestion de l'événement</p>
              </div>
            </div>
            <div class="flex items-center space-x-3">
              <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full" 
                    :class="getEventStatusClass(event.status)">
                {{ getEventStatusName(event.status) }}
              </span>
              <div class="flex space-x-2">
                <button 
                  @click="editEvent"
                  class="bg-primea-blue text-white px-4 py-2 rounded-primea hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200 font-primea"
                >
                  <PencilIcon class="w-4 h-4 inline mr-2" />
                  Modifier
                </button>
                <button 
                  v-if="event.status === 'draft'"
                  @click="publishEvent"
                  class="bg-green-600 text-white px-4 py-2 rounded-primea hover:bg-green-700 transition-all duration-200 font-primea"
                >
                  <CheckIcon class="w-4 h-4 inline mr-2" />
                  Publier
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Stats cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <div class="bg-white rounded-primea shadow-primea p-6">
            <div class="flex items-center">
              <div class="p-2 bg-blue-100 rounded-lg">
                <TicketIcon class="w-6 h-6 text-blue-600" />
              </div>
              <div class="ml-4">
                <p class="text-sm text-gray-600 font-primea">Billets vendus</p>
                <p class="text-2xl font-bold text-blue-600 font-primea">{{ event.tickets_sold || 0 }}</p>
              </div>
            </div>
          </div>
          
          <div class="bg-white rounded-primea shadow-primea p-6">
            <div class="flex items-center">
              <div class="p-2 bg-green-100 rounded-lg">
                <CurrencyDollarIcon class="w-6 h-6 text-green-600" />
              </div>
              <div class="ml-4">
                <p class="text-sm text-gray-600 font-primea">Revenus</p>
                <p class="text-2xl font-bold text-green-600 font-primea">{{ formatAmount(event.total_revenue || 0) }} XAF</p>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-primea shadow-primea p-6">
            <div class="flex items-center">
              <div class="p-2 bg-yellow-100 rounded-lg">
                <UsersIcon class="w-6 h-6 text-yellow-600" />
              </div>
              <div class="ml-4">
                <p class="text-sm text-gray-600 font-primea">Capacité</p>
                <p class="text-2xl font-bold text-yellow-600 font-primea">{{ event.max_attendees || 'Illimitée' }}</p>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-primea shadow-primea p-6">
            <div class="flex items-center">
              <div class="p-2 bg-purple-100 rounded-lg">
                <CalendarIcon class="w-6 h-6 text-purple-600" />
              </div>
              <div class="ml-4">
                <p class="text-sm text-gray-600 font-primea">Date</p>
                <p class="text-lg font-bold text-purple-600 font-primea">{{ formatDate(event.event_date) }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Main content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Event details -->
          <div class="lg:col-span-2 space-y-6">
            <!-- Basic info -->
            <div class="bg-white rounded-primea shadow-primea p-6">
              <h2 class="text-xl font-semibold text-primea-blue font-primea mb-6">Informations générales</h2>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 font-primea">Titre</label>
                  <p class="mt-1 text-gray-900 font-primea">{{ event.title }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 font-primea">Lieu</label>
                  <p class="mt-1 text-gray-900 font-primea">{{ event.venue_name }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 font-primea">Date et heure</label>
                  <p class="mt-1 text-gray-900 font-primea">{{ formatDateTime(event.event_date) }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 font-primea">Catégorie</label>
                  <p class="mt-1 text-gray-900 font-primea">{{ event.category_name || 'Non spécifiée' }}</p>
                </div>
                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 font-primea">Description</label>
                  <p class="mt-1 text-gray-900 font-primea leading-relaxed">{{ event.description || 'Aucune description' }}</p>
                </div>
              </div>
            </div>

            <!-- Ticket types -->
            <div class="bg-white rounded-primea shadow-primea p-6">
              <h2 class="text-xl font-semibold text-primea-blue font-primea mb-6">Types de billets</h2>
              <div v-if="event.ticket_types && event.ticket_types.length > 0" class="space-y-4">
                <div v-for="ticket in event.ticket_types" :key="ticket.id" 
                     class="border border-gray-200 rounded-primea p-4">
                  <div class="flex justify-between items-start">
                    <div>
                      <h3 class="font-semibold text-gray-900 font-primea">{{ ticket.name }}</h3>
                      <p class="text-gray-600 text-sm font-primea mt-1">{{ ticket.description }}</p>
                      <div class="flex items-center space-x-4 mt-2">
                        <span class="text-lg font-bold text-primea-blue font-primea">
                          {{ formatAmount(ticket.price) }} XAF
                        </span>
                        <span class="text-sm text-gray-500 font-primea">
                          {{ ticket.sold || 0 }} / {{ ticket.quantity || 'Illimité' }} vendus
                        </span>
                      </div>
                    </div>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                          :class="ticket.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
                      {{ ticket.is_active ? 'Actif' : 'Inactif' }}
                    </span>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-8 text-gray-500">
                <TicketIcon class="w-12 h-12 mx-auto text-gray-300 mb-2" />
                <p class="font-primea">Aucun type de billet configuré</p>
              </div>
            </div>

            <!-- Recent orders -->
            <div class="bg-white rounded-primea shadow-primea p-6">
              <h2 class="text-xl font-semibold text-primea-blue font-primea mb-6">Commandes récentes</h2>
              <div v-if="recentOrders.length > 0" class="overflow-x-auto">
                <table class="w-full">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase font-primea">Client</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase font-primea">Billets</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase font-primea">Montant</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase font-primea">Date</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200">
                    <tr v-for="order in recentOrders" :key="order.id">
                      <td class="px-4 py-3 text-sm text-gray-900 font-primea">{{ order.customer_name }}</td>
                      <td class="px-4 py-3 text-sm text-gray-900 font-primea">{{ order.ticket_quantity }}</td>
                      <td class="px-4 py-3 text-sm font-medium text-gray-900 font-primea">{{ formatAmount(order.total_amount) }} XAF</td>
                      <td class="px-4 py-3 text-sm text-gray-500 font-primea">{{ formatDate(order.created_at) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div v-else class="text-center py-8 text-gray-500">
                <ShoppingCartIcon class="w-12 h-12 mx-auto text-gray-300 mb-2" />
                <p class="font-primea">Aucune commande pour cet événement</p>
              </div>
            </div>
          </div>

          <!-- Sidebar -->
          <div class="space-y-6">
            <!-- Quick actions -->
            <div class="bg-white rounded-primea shadow-primea p-6">
              <h3 class="text-lg font-semibold text-primea-blue font-primea mb-4">Actions rapides</h3>
              <div class="space-y-3">
                <button 
                  @click="shareEvent"
                  class="w-full flex items-center justify-center px-4 py-2 border border-primea-blue text-primea-blue rounded-primea hover:bg-primea-blue hover:text-white transition-all duration-200 font-primea"
                >
                  <ShareIcon class="w-4 h-4 mr-2" />
                  Partager l'événement
                </button>
                <button 
                  @click="viewPublicPage"
                  class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 text-gray-700 rounded-primea hover:bg-gray-50 transition-all duration-200 font-primea"
                >
                  <EyeIcon class="w-4 h-4 mr-2" />
                  Voir la page publique
                </button>
                <button 
                  @click="exportData"
                  class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 text-gray-700 rounded-primea hover:bg-gray-50 transition-all duration-200 font-primea"
                >
                  <ArrowDownTrayIcon class="w-4 h-4 mr-2" />
                  Exporter les données
                </button>
              </div>
            </div>

            <!-- Event image -->
            <div v-if="event.image_url" class="bg-white rounded-primea shadow-primea p-6">
              <h3 class="text-lg font-semibold text-primea-blue font-primea mb-4">Image de l'événement</h3>
              <img :src="event.image_url" :alt="event.title" class="w-full h-48 object-cover rounded-primea">
            </div>

            <!-- Event settings -->
            <div class="bg-white rounded-primea shadow-primea p-6">
              <h3 class="text-lg font-semibold text-primea-blue font-primea mb-4">Paramètres</h3>
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-700 font-primea">Événement public</span>
                  <span class="text-sm font-medium" :class="event.is_public ? 'text-green-600' : 'text-red-600'">
                    {{ event.is_public ? 'Oui' : 'Non' }}
                  </span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-700 font-primea">Ventes actives</span>
                  <span class="text-sm font-medium" :class="event.sales_active ? 'text-green-600' : 'text-red-600'">
                    {{ event.sales_active ? 'Oui' : 'Non' }}
                  </span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-700 font-primea">Créé le</span>
                  <span class="text-sm text-gray-600 font-primea">{{ formatDate(event.created_at) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { 
  ArrowLeftIcon,
  ExclamationTriangleIcon,
  PencilIcon,
  CheckIcon,
  TicketIcon,
  CurrencyDollarIcon,
  UsersIcon,
  CalendarIcon,
  ShareIcon,
  EyeIcon,
  ArrowDownTrayIcon,
  ShoppingCartIcon
} from '@heroicons/vue/24/outline';

const route = useRoute();
const router = useRouter();

// État réactif
const loading = ref(true);
const error = ref(null);
const event = ref(null);
const recentOrders = ref([]);

// Méthodes
const loadEvent = async () => {
  loading.value = true;
  error.value = null;
  
  try {
    const slug = route.params.slug;
    
    // Simulation d'appel API
    await new Promise(resolve => setTimeout(resolve, 800));
    
    // Base de données simulée d'événements
    const events = {
      'concert-jazz-etoiles': {
        id: 1,
        title: 'Concert Jazz sous les étoiles',
        slug: 'concert-jazz-etoiles',
        description: 'Une soirée musicale exceptionnelle sous un ciel étoilé avec les plus grands artistes de jazz de la région.',
        venue_name: 'Palais de la Culture',
        venue_address: '123 Avenue de la République, Abidjan',
        event_date: '2025-10-15T20:00:00Z',
        status: 'active',
        category_name: 'Musique',
        max_attendees: 500,
        tickets_sold: 45,
        total_revenue: 2250000,
        is_public: true,
        sales_active: true,
        image_url: '/images/concert-jazz.jpg',
        created_at: '2025-09-01T10:00:00Z',
        ticket_types: [
          {
            id: 1,
            name: 'Billet Standard',
            description: 'Accès général à l\'événement',
            price: 25000,
            quantity: 300,
            sold: 32,
            is_active: true
          },
          {
            id: 2,
            name: 'Billet VIP',
            description: 'Accès VIP avec boissons incluses',
            price: 50000,
            quantity: 100,
            sold: 13,
            is_active: true
          }
        ]
      },
      'oiseau-rare': {
        id: 2,
        title: "L'OISEAU RARE",
        slug: 'oiseau-rare',
        description: 'Concert intimiste dans un cadre exceptionnel',
        venue_name: 'Entre Nous Bar',
        venue_address: '456 Rue des Arts, Abidjan',
        event_date: '2025-07-27T20:00:00Z',
        status: 'published',
        category_name: 'Musique',
        max_attendees: 150,
        tickets_sold: 85,
        total_revenue: 850000,
        is_public: true,
        sales_active: true,
        image_url: '/images/oiseau-rare.jpg',
        created_at: '2025-06-15T10:00:00Z',
        ticket_types: [
          {
            id: 3,
            name: 'Entrée Standard',
            description: 'Accès à la soirée',
            price: 10000,
            quantity: 150,
            sold: 85,
            is_active: true
          }
        ]
      },
      'festival-arts-culture': {
        id: 3,
        title: 'Festival Arts & Culture',
        slug: 'festival-arts-culture',
        description: 'Un festival célébrant la richesse culturelle ivoirienne',
        venue_name: 'Amphithéâtre National',
        venue_address: '789 Boulevard de la Culture, Abidjan',
        event_date: '2025-09-10T14:00:00Z',
        status: 'published',
        category_name: 'Culture',
        max_attendees: 300,
        tickets_sold: 162,
        total_revenue: 2430000,
        is_public: true,
        sales_active: true,
        image_url: '/images/festival-culture.jpg',
        created_at: '2025-07-01T10:00:00Z',
        ticket_types: [
          {
            id: 4,
            name: 'Pass Journée',
            description: 'Accès toute la journée',
            price: 15000,
            quantity: 300,
            sold: 162,
            is_active: true
          }
        ]
      },
      'soiree-hip-hop': {
        id: 4,
        title: 'Soirée Hip-Hop',
        slug: 'soiree-hip-hop',
        description: 'Soirée hip-hop avec les meilleurs artistes locaux',
        venue_name: 'Club Central',
        venue_address: '321 Avenue Central, Abidjan',
        event_date: '2025-06-20T21:00:00Z',
        status: 'completed',
        category_name: 'Musique',
        max_attendees: 120,
        tickets_sold: 120,
        total_revenue: 1200000,
        is_public: true,
        sales_active: false,
        image_url: '/images/hip-hop.jpg',
        created_at: '2025-05-01T10:00:00Z',
        ticket_types: [
          {
            id: 5,
            name: 'Entrée VIP',
            description: 'Accès VIP avec bar',
            price: 10000,
            quantity: 120,
            sold: 120,
            is_active: false
          }
        ]
      },
      'festival-gastronomique': {
        id: 5,
        title: 'Festival Gastronomique',
        slug: 'festival-gastronomique',
        description: 'Découverte de la gastronomie ivoirienne et internationale',
        venue_name: 'Centre des Expositions',
        venue_address: '654 Zone Industrielle, Abidjan',
        event_date: '2025-11-02T12:00:00Z',
        status: 'draft',
        category_name: 'Gastronomie',
        max_attendees: 400,
        tickets_sold: 0,
        total_revenue: 0,
        is_public: false,
        sales_active: false,
        image_url: '/images/gastronomie.jpg',
        created_at: '2025-09-15T10:00:00Z',
        ticket_types: [
          {
            id: 6,
            name: 'Pass Dégustation',
            description: 'Accès aux stands de dégustation',
            price: 20000,
            quantity: 400,
            sold: 0,
            is_active: false
          }
        ]
      }
    };

    // Vérifier si l'événement existe
    if (events[slug]) {
      event.value = events[slug];

      // Commandes récentes simulées basées sur l'événement
      if (event.value.tickets_sold > 0) {
        recentOrders.value = [
          {
            id: 1,
            customer_name: 'Kofi Asante',
            ticket_quantity: 2,
            total_amount: event.value.ticket_types[0].price * 2,
            created_at: '2025-09-19T14:30:00Z'
          },
          {
            id: 2,
            customer_name: 'Aminata Traore',
            ticket_quantity: 1,
            total_amount: event.value.ticket_types[0].price,
            created_at: '2025-09-18T09:15:00Z'
          }
        ];
      } else {
        recentOrders.value = [];
      }
    } else {
      error.value = `Événement avec le slug "${slug}" non trouvé`;
    }
  } catch (err) {
    error.value = 'Erreur lors du chargement de l\'événement';
    console.error('Erreur:', err);
  } finally {
    loading.value = false;
  }
};

const editEvent = () => {
  router.push({ name: 'organizer-event-edit', params: { slug: event.value.slug } });
};

const publishEvent = async () => {
  if (confirm('Êtes-vous sûr de vouloir publier cet événement ?')) {
    // Simulation d'appel API
    event.value.status = 'active';
    alert('Événement publié avec succès !');
  }
};

const shareEvent = () => {
  const url = `${window.location.origin}/events/${event.value.slug}`;
  navigator.clipboard.writeText(url);
  alert('Lien copié dans le presse-papiers !');
};

const viewPublicPage = () => {
  const url = `/events/${event.value.slug}`;
  window.open(url, '_blank');
};

const exportData = () => {
  alert('Fonctionnalité d\'export en cours de développement');
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

onMounted(() => {
  loadEvent();
});
</script>

<style scoped>
/* Style personnalisé pour le détail d'événement organisateur */
.event-detail {
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