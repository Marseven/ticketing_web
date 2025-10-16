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
                <p class="text-2xl font-bold text-yellow-600 font-primea">{{ getTotalCapacity() }}</p>
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
                <p class="text-lg font-bold text-purple-600 font-primea">{{ getEventDate() }}</p>
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
                  <label class="block text-sm font-medium text-gray-700 font-primea">Catégorie</label>
                  <p class="mt-1 text-gray-900 font-primea">{{ event.category_name || 'Non spécifiée' }}</p>
                </div>
                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 font-primea">Description</label>
                  <p class="mt-1 text-gray-900 font-primea leading-relaxed">{{ event.description || 'Aucune description' }}</p>
                </div>
              </div>
            </div>

            <!-- Programmation -->
            <div class="bg-white rounded-primea shadow-primea p-6">
              <h2 class="text-xl font-semibold text-primea-blue font-primea mb-6">Programmation</h2>
              <div v-if="event.schedules && event.schedules.length > 0" class="space-y-4">
                <div v-for="schedule in event.schedules" :key="schedule.id" 
                     class="border border-gray-200 rounded-primea p-4">
                  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 font-primea">Début</label>
                      <p class="mt-1 text-gray-900 font-primea">{{ formatDateTime(schedule.starts_at) }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 font-primea">Fin</label>
                      <p class="mt-1 text-gray-900 font-primea">{{ formatDateTime(schedule.ends_at) }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 font-primea">Ouverture des portes</label>
                      <p class="mt-1 text-gray-900 font-primea">
                        {{ schedule.door_time ? formatDateTime(schedule.door_time) : 'Non spécifiée' }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-8 text-gray-500">
                <CalendarIcon class="w-12 h-12 mx-auto text-gray-300 mb-2" />
                <p class="font-primea">Aucune programmation définie</p>
              </div>
            </div>

            <!-- Ticket types -->
            <div class="bg-white rounded-primea shadow-primea p-6">
              <h2 class="text-xl font-semibold text-primea-blue font-primea mb-6">Types de billets</h2>
              <div v-if="event.ticket_types && event.ticket_types.length > 0" class="space-y-4">
                <div v-for="ticket in event.ticket_types" :key="ticket.id" 
                     class="border border-gray-200 rounded-primea p-4">
                  <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-start">
                    <div class="md:col-span-2">
                      <div class="flex items-center justify-between mb-2">
                        <h3 class="font-semibold text-gray-900 font-primea">{{ ticket.name }}</h3>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                              :class="ticket.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
                          {{ ticket.is_active ? 'Actif' : 'Inactif' }}
                        </span>
                      </div>
                      <p class="text-gray-600 text-sm font-primea">{{ ticket.description || 'Aucune description' }}</p>
                    </div>
                    
                    <div class="text-center">
                      <label class="block text-xs text-gray-500 font-primea mb-1">Prix</label>
                      <span class="text-lg font-bold text-primea-blue font-primea">
                        {{ formatAmount(ticket.price) }} XAF
                      </span>
                    </div>
                    
                    <div class="text-center">
                      <label class="block text-xs text-gray-500 font-primea mb-1">Disponibilité</label>
                      <div class="text-sm font-primea">
                        <span class="font-medium text-gray-900">{{ ticket.sold || 0 }}</span>
                        <span class="text-gray-500"> / </span>
                        <span class="font-medium text-gray-900">{{ getTicketCapacity(ticket) }}</span>
                      </div>
                      <div class="text-xs text-gray-500 mt-1">
                        {{ getTicketSalesPercentage(ticket) }}% vendus
                      </div>
                    </div>
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
              <h2 class="text-xl font-semibold text-primea-blue font-primea mb-6">Achats récents</h2>
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
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  Exporter les données
                </button>
              </div>
            </div>

            <!-- Event image -->
            <div v-if="event" class="bg-white rounded-primea shadow-primea p-6">
              <h3 class="text-lg font-semibold text-primea-blue font-primea mb-4">Image de l'événement</h3>
              <div class="w-full h-48 rounded-primea overflow-hidden">
                <img 
                  v-if="hasEventImage()"
                  :src="getEventImageUrl()" 
                  :alt="event.title" 
                  class="w-full h-full object-cover"
                  @error="handleImageError"
                  @load="handleImageLoad"
                >
                <div 
                  v-else 
                  class="w-full h-full bg-gradient-to-br from-blue-100 to-yellow-100 flex items-center justify-center"
                >
                  <CalendarIcon class="w-16 h-16 text-gray-400" />
                </div>
              </div>
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
import { organizerService } from '../../services/api';
import Swal from 'sweetalert2';
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
    let identifier = route.params.slug;
    
    // Déterminer si c'est un ID numérique ou un slug
    let eventData;
    let recentOrdersData = [];

    if (/^\d+$/.test(identifier)) {
      // C'est un ID numérique
      const response = await organizerService.getEvent(identifier);
      // La structure de réponse a changé: {event: ..., recent_orders: ...}
      if (response.data.data.event) {
        eventData = response.data.data.event;
        recentOrdersData = response.data.data.recent_orders || [];
      } else {
        // Fallback si l'API retourne l'ancienne structure
        eventData = response.data.data;
      }
    } else {
      // C'est un slug, on doit chercher par slug
      // Pour l'instant, on va essayer de récupérer tous les événements et chercher par slug
      try {
        const eventsResponse = await organizerService.getEvents();
        const events = eventsResponse.data.data.events.data || eventsResponse.data.data.events;
        eventData = events.find(e => e.slug === identifier);

        if (!eventData) {
          throw new Error(`Événement avec le slug "${identifier}" non trouvé`);
        }

        // Charger les détails avec les recent_orders en utilisant l'ID trouvé
        if (eventData.id) {
          const detailResponse = await organizerService.getEvent(eventData.id);
          if (detailResponse.data.data.event) {
            eventData = detailResponse.data.data.event;
            recentOrdersData = detailResponse.data.data.recent_orders || [];
          }
        }
      } catch (eventsError) {
        // Si la recherche par slug échoue, essayons directement avec l'ID si c'est un slug qui ressemble à un ID
        throw new Error(`Événement avec le slug "${identifier}" non trouvé`);
      }
    }
    
    if (!eventData) {
      throw new Error(`Événement introuvable`);
    }
    
    // Transformer les données pour correspondre au format attendu
    event.value = {
      ...eventData,
      venue_name: eventData.venue?.name || 'Lieu non spécifié',
      venue_address: eventData.venue?.address || '',
      category_name: eventData.category?.name || 'Non spécifiée',
      tickets_sold: eventData.tickets?.filter(t => ['issued', 'used'].includes(t.status)).length || 0,
      // Utiliser le revenu calculé par l'API (montant NET pour l'organisateur)
      total_revenue: eventData.revenue || 0,
      is_public: eventData.is_active || false,
      sales_active: eventData.is_active || false,
      // Assurer que ticket_types est accessible
      ticket_types: eventData.ticket_types || eventData.ticketTypes || []
    };
    
    // Calculer les statistiques des types de billets si disponibles
    const ticketTypes = eventData.ticket_types || eventData.ticketTypes || [];
    if (ticketTypes.length > 0) {
      event.value.ticket_types = ticketTypes.map(ticketType => ({
        ...ticketType,
        sold: eventData.tickets?.filter(t => 
          t.ticket_type_id === ticketType.id && ['issued', 'used'].includes(t.status)
        ).length || 0
      }));
    }
    
    // Utiliser les achats récents retournés directement par l'API
    recentOrders.value = recentOrdersData;
    
  } catch (err) {
    console.error('Erreur lors du chargement de l\'événement:', err);
    if (err.response?.status === 404) {
      error.value = `Événement avec l'identifiant "${route.params.slug}" non trouvé`;
    } else if (err.message) {
      error.value = err.message;
    } else {
      error.value = 'Erreur lors du chargement de l\'événement';
    }
  } finally {
    loading.value = false;
  }
};

const editEvent = () => {
  router.push({ name: 'organizer-event-edit', params: { slug: event.value.slug } });
};

const publishEvent = async () => {
  const result = await Swal.fire({
    title: 'Publier l\'événement',
    text: 'Êtes-vous sûr de vouloir publier cet événement ?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#272d63',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Oui, publier',
    cancelButtonText: 'Annuler'
  });
  
  if (result.isConfirmed) {
    try {
      await organizerService.updateEvent(event.value.id, {
        status: 'published',
        is_active: true
      });
      
      event.value.status = 'published';
      event.value.is_active = true;
      
      Swal.fire({
        title: 'Succès !',
        text: 'Événement publié avec succès !',
        icon: 'success',
        confirmButtonColor: '#272d63'
      });
    } catch (err) {
      console.error('Erreur publication:', err);
      Swal.fire({
        title: 'Erreur',
        text: 'Erreur lors de la publication de l\'événement',
        icon: 'error',
        confirmButtonColor: '#272d63'
      });
    }
  }
};

const shareEvent = async () => {
  const url = `${window.location.origin}/events/${event.value.slug}`;
  try {
    await navigator.clipboard.writeText(url);
    Swal.fire({
      title: 'Lien copié !',
      text: 'Le lien de l\'événement a été copié dans le presse-papiers',
      icon: 'success',
      timer: 2000,
      showConfirmButton: false
    });
  } catch (err) {
    Swal.fire({
      title: 'Lien de l\'événement',
      html: `<input type="text" value="${url}" class="w-full p-2 border rounded" readonly onclick="this.select()">`,
      showConfirmButton: false,
      showCloseButton: true
    });
  }
};

const viewPublicPage = () => {
  const url = `/events/${event.value.slug}`;
  window.open(url, '_blank');
};

const exportData = () => {
  Swal.fire({
    title: 'Export des données',
    text: 'Fonctionnalité d\'export en cours de développement',
    icon: 'info',
    confirmButtonColor: '#272d63'
  });
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

const getTicketCapacity = (ticket) => {
  // Priorité: capacity -> available_quantity -> max_quantity -> quantity
  const capacity = ticket.capacity ?? ticket.available_quantity ?? ticket.max_quantity ?? ticket.quantity;
  
  if (capacity === null || capacity === undefined) {
    return 'Illimité';
  }
  
  return typeof capacity === 'number' ? new Intl.NumberFormat('fr-FR').format(capacity) : capacity;
};

const getTicketSalesPercentage = (ticket) => {
  const sold = ticket.sold || 0;
  const capacity = ticket.capacity ?? ticket.available_quantity ?? ticket.max_quantity ?? ticket.quantity;
  
  if (capacity === null || capacity === undefined || capacity <= 0) {
    return 0;
  }
  
  return Math.round((sold / capacity) * 100);
};

const handleImageError = (event) => {
  console.log('Erreur de chargement d\'image:', event.target.src);
  // Remplacer par l'image placeholder en cas d'erreur
  event.target.src = 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=400&h=300&fit=crop&crop=center';
};

const handleImageLoad = (event) => {
  console.log('Image chargée avec succès:', event.target.src);
};

const getTotalCapacity = () => {
  if (!event.value || !event.value.ticket_types) return 'Non définie';
  
  const total = event.value.ticket_types.reduce((sum, ticket) => {
    const capacity = ticket.capacity || ticket.available_quantity || ticket.max_quantity || ticket.quantity || 0;
    return sum + (typeof capacity === 'number' ? capacity : 0);
  }, 0);
  
  return total > 0 ? new Intl.NumberFormat('fr-FR').format(total) : 'Non définie';
};

const getEventDate = () => {
  if (!event.value) return 'Non définie';
  
  // Priorité : event_date, puis première programmation
  if (event.value.event_date && event.value.event_date !== 'Invalid Date') {
    return formatDate(event.value.event_date);
  }
  
  if (event.value.schedules && event.value.schedules.length > 0) {
    const firstSchedule = event.value.schedules[0];
    if (firstSchedule.starts_at) {
      return formatDate(firstSchedule.starts_at);
    }
  }
  
  return 'Non définie';
};

const hasEventImage = () => {
  if (!event.value) return false;
  const imageUrl = event.value.image_url || event.value.image || event.value.image_file;
  return imageUrl && imageUrl.trim() !== '';
};

const getEventImageUrl = () => {
  if (!event.value) return '';
  
  // Priorité: image_url, puis image, puis image_file
  let imageUrl = event.value.image_url || event.value.image || event.value.image_file;
  
  console.log('EventDetail - Image URL pour événement:', event.value.title, 'URL brute:', imageUrl);
  
  if (imageUrl && imageUrl.trim() !== '') {
    // Si c'est déjà une URL complète (commence par http:// ou https://), on la retourne telle quelle
    if (imageUrl.startsWith('http://') || imageUrl.startsWith('https://')) {
      console.log('EventDetail - URL complète détectée:', imageUrl);
      return imageUrl;
    }
    
    // Si c'est un chemin relatif, on construit l'URL complète
    if (imageUrl.startsWith('/')) {
      const baseUrl = window.location.origin;
      imageUrl = baseUrl + imageUrl;
      console.log('EventDetail - URL construite depuis chemin relatif:', imageUrl);
      return imageUrl;
    }
    
    // Si c'est un nom de fichier dans le storage
    if (!imageUrl.includes('/')) {
      const baseUrl = window.location.origin;
      imageUrl = `${baseUrl}/storage/images/events/${imageUrl}`;
      console.log('EventDetail - URL construite depuis nom de fichier:', imageUrl);
      return imageUrl;
    }
    
    // Sinon on retourne tel quel
    return imageUrl;
  }
  
  console.log('EventDetail - Aucune image trouvée pour:', event.value.title);
  return '';
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