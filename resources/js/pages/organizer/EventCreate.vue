<template>
  <div class="event-create min-h-screen" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%)">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
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
              <h1 class="text-3xl font-bold text-primea-blue font-primea">Créer un événement</h1>
              <p class="text-gray-600 font-primea mt-1">Configurez tous les détails de votre événement</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Form -->
      <form @submit.prevent="createEvent" class="space-y-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Main form content -->
          <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <div class="bg-white rounded-primea shadow-primea p-6">
              <h2 class="text-xl font-semibold text-primea-blue font-primea mb-6">Informations de base</h2>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 font-primea mb-2">Titre de l'événement</label>
                  <input 
                    v-model="form.title"
                    type="text" 
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea"
                    placeholder="Nom de votre événement"
                  />
                </div>

                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 font-primea mb-2">Description</label>
                  <textarea 
                    v-model="form.description"
                    rows="4"
                    class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea resize-none"
                    placeholder="Décrivez votre événement..."
                  ></textarea>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 font-primea mb-2">Date et heure</label>
                  <input 
                    v-model="form.event_date"
                    type="datetime-local" 
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 font-primea mb-2">Catégorie</label>
                  <select 
                    v-model="form.category_name"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea"
                  >
                    <option value="">Sélectionner une catégorie</option>
                    <option value="Musique">Musique</option>
                    <option value="Culture">Culture</option>
                    <option value="Gastronomie">Gastronomie</option>
                    <option value="Sport">Sport</option>
                    <option value="Business">Business</option>
                    <option value="Autres">Autres</option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 font-primea mb-2">Lieu</label>
                  <input 
                    v-model="form.venue_name"
                    type="text" 
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea"
                    placeholder="Nom du lieu"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 font-primea mb-2">Adresse</label>
                  <input 
                    v-model="form.venue_address"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea"
                    placeholder="Adresse complète"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 font-primea mb-2">Capacité maximale</label>
                  <input 
                    v-model="form.max_attendees"
                    type="number" 
                    min="1"
                    class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea"
                    placeholder="Nombre de places (optionnel)"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 font-primea mb-2">URL de l'image</label>
                  <input 
                    v-model="form.image_url"
                    type="url"
                    class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea"
                    placeholder="https://exemple.com/image.jpg"
                  />
                </div>
              </div>
            </div>

            <!-- Ticket Types -->
            <div class="bg-white rounded-primea shadow-primea p-6">
              <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold text-primea-blue font-primea">Types de billets</h2>
                <button 
                  type="button"
                  @click="addTicketType"
                  class="bg-primea-blue text-white px-4 py-2 rounded-primea hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200 font-primea"
                >
                  <PlusIcon class="w-4 h-4 inline mr-2" />
                  Ajouter un type
                </button>
              </div>

              <div v-if="form.ticket_types.length === 0" class="text-center py-8 text-gray-500">
                <TicketIcon class="w-12 h-12 mx-auto text-gray-300 mb-2" />
                <p class="font-primea">Aucun type de billet configuré</p>
                <p class="text-sm font-primea mt-1">Cliquez sur "Ajouter un type" pour commencer</p>
              </div>

              <div v-else class="space-y-4">
                <div v-for="(ticket, index) in form.ticket_types" :key="index" 
                     class="border border-gray-200 rounded-primea p-4">
                  <div class="flex justify-between items-start mb-4">
                    <h3 class="font-semibold text-gray-900 font-primea">Type de billet {{ index + 1 }}</h3>
                    <button 
                      type="button"
                      @click="removeTicketType(index)"
                      class="text-red-600 hover:text-red-800"
                    >
                      <TrashIcon class="w-4 h-4" />
                    </button>
                  </div>

                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 font-primea mb-1">Nom</label>
                      <input 
                        v-model="ticket.name"
                        type="text" 
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea"
                        placeholder="Ex: Billet Standard"
                      />
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 font-primea mb-1">Prix (XAF)</label>
                      <input 
                        v-model="ticket.price"
                        type="number" 
                        min="0"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea"
                        placeholder="25000"
                      />
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 font-primea mb-1">Capacité</label>
                      <input 
                        v-model="ticket.capacity"
                        type="number" 
                        min="1"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea"
                        placeholder="100"
                      />
                    </div>

                    <div class="flex items-center">
                      <label class="flex items-center space-x-2">
                        <input 
                          v-model="ticket.is_active"
                          type="checkbox" 
                          class="rounded border-gray-300 text-primea-blue focus:ring-primea-blue"
                        />
                        <span class="text-sm font-medium text-gray-700 font-primea">Actif</span>
                      </label>
                    </div>

                    <div class="md:col-span-2">
                      <label class="block text-sm font-medium text-gray-700 font-primea mb-1">Description</label>
                      <textarea 
                        v-model="ticket.description"
                        rows="2"
                        class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea resize-none"
                        placeholder="Description du billet..."
                      ></textarea>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Sidebar -->
          <div class="space-y-6">
            <!-- Settings -->
            <div class="bg-white rounded-primea shadow-primea p-6">
              <h3 class="text-lg font-semibold text-primea-blue font-primea mb-4">Paramètres</h3>
              <div class="space-y-4">
                <div>
                  <label class="flex items-center space-x-2">
                    <input 
                      v-model="form.is_public"
                      type="checkbox" 
                      class="rounded border-gray-300 text-primea-blue focus:ring-primea-blue"
                    />
                    <span class="text-sm font-medium text-gray-700 font-primea">Événement public</span>
                  </label>
                  <p class="text-xs text-gray-500 mt-1 font-primea">Visible dans la liste publique des événements</p>
                </div>

                <div>
                  <label class="flex items-center space-x-2">
                    <input 
                      v-model="form.sales_active"
                      type="checkbox" 
                      class="rounded border-gray-300 text-primea-blue focus:ring-primea-blue"
                    />
                    <span class="text-sm font-medium text-gray-700 font-primea">Ventes actives</span>
                  </label>
                  <p class="text-xs text-gray-500 mt-1 font-primea">Permettre l'achat de billets</p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 font-primea mb-2">Statut</label>
                  <select 
                    v-model="form.status"
                    class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea"
                  >
                    <option value="draft">Brouillon</option>
                    <option value="published">Publié</option>
                  </select>
                  <p class="text-xs text-gray-500 mt-1 font-primea">Les brouillons ne sont pas visibles par le public</p>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="bg-white rounded-primea shadow-primea p-6">
              <h3 class="text-lg font-semibold text-primea-blue font-primea mb-4">Actions</h3>
              <div class="space-y-3">
                <button 
                  type="submit"
                  :disabled="creating"
                  class="w-full bg-primea-blue text-white px-4 py-2 rounded-primea hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200 font-primea disabled:opacity-50"
                >
                  {{ creating ? 'Création en cours...' : 'Créer l\'événement' }}
                </button>

                <router-link 
                  to="/organizer/dashboard"
                  class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 text-gray-700 rounded-primea hover:bg-gray-50 transition-all duration-200 font-primea"
                >
                  Annuler
                </router-link>
              </div>
            </div>

            <!-- Preview -->
            <div v-if="form.image_url" class="bg-white rounded-primea shadow-primea p-6">
              <h3 class="text-lg font-semibold text-primea-blue font-primea mb-4">Aperçu de l'image</h3>
              <img :src="form.image_url" :alt="form.title" class="w-full h-48 object-cover rounded-primea">
            </div>

            <!-- Summary -->
            <div class="bg-white rounded-primea shadow-primea p-6">
              <h3 class="text-lg font-semibold text-primea-blue font-primea mb-4">Résumé</h3>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span class="text-gray-600 font-primea">Types de billets:</span>
                  <span class="font-medium font-primea">{{ form.ticket_types.length }}</span>
                </div>
                
                <div class="flex justify-between">
                  <span class="text-gray-600 font-primea">Capacité totale:</span>
                  <span class="font-medium font-primea">{{ totalCapacity || 'Illimitée' }}</span>
                </div>
                
                <div v-if="minPrice > 0" class="flex justify-between">
                  <span class="text-gray-600 font-primea">Prix minimum:</span>
                  <span class="font-medium font-primea">{{ formatAmount(minPrice) }} XAF</span>
                </div>
                
                <div v-if="maxPrice > 0" class="flex justify-between">
                  <span class="text-gray-600 font-primea">Prix maximum:</span>
                  <span class="font-medium font-primea">{{ formatAmount(maxPrice) }} XAF</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { organizerService } from '../../services/api';
import Swal from 'sweetalert2';
import { 
  ArrowLeftIcon,
  PlusIcon,
  TrashIcon,
  TicketIcon
} from '@heroicons/vue/24/outline';

const router = useRouter();

// État réactif
const creating = ref(false);
const categories = ref([]);
const venues = ref([]);
const loading = ref(false);

// Formulaire
const form = reactive({
  title: '',
  description: '',
  event_date: '',
  category_name: '',
  venue_name: '',
  venue_address: '',
  max_attendees: '',
  image_url: '',
  is_public: true,
  sales_active: true,
  status: 'draft',
  ticket_types: []
});

// Computed
const totalCapacity = computed(() => {
  return form.ticket_types.reduce((total, type) => total + (parseInt(type.capacity) || 0), 0);
});

const minPrice = computed(() => {
  if (form.ticket_types.length === 0) return 0;
  const prices = form.ticket_types.map(type => parseInt(type.price) || 0).filter(p => p > 0);
  return prices.length > 0 ? Math.min(...prices) : 0;
});

const maxPrice = computed(() => {
  if (form.ticket_types.length === 0) return 0;
  const prices = form.ticket_types.map(type => parseInt(type.price) || 0);
  return prices.length > 0 ? Math.max(...prices) : 0;
});

// Méthodes
const addTicketType = () => {
  form.ticket_types.push({
    name: '',
    description: '',
    price: '',
    capacity: '',
    is_active: true
  });
};

const removeTicketType = (index) => {
  if (confirm('Êtes-vous sûr de vouloir supprimer ce type de billet ?')) {
    form.ticket_types.splice(index, 1);
  }
};

const createEvent = async () => {
  // Validation de base
  if (form.ticket_types.length === 0) {
    Swal.fire({
      title: 'Erreur',
      text: 'Veuillez ajouter au moins un type de billet',
      icon: 'error',
      confirmButtonColor: '#272d63'
    });
    return;
  }

  if (!form.title || !form.event_date || !form.venue_name) {
    Swal.fire({
      title: 'Erreur',
      text: 'Veuillez remplir tous les champs obligatoires',
      icon: 'error',
      confirmButtonColor: '#272d63'
    });
    return;
  }

  creating.value = true;
  try {
    // Préparer les données pour l'API
    const eventData = {
      title: form.title,
      description: form.description,
      event_date: form.event_date,
      category_id: getCategoryId(form.category_name), // On devra implémenter cette fonction
      venue_name: form.venue_name,
      venue_city: 'Abidjan', // Valeur par défaut
      venue_address: form.venue_address || form.venue_name,
      max_attendees: form.max_attendees ? parseInt(form.max_attendees) : null,
      image_url: form.image_url,
      is_active: form.status === 'published',
      schedules: [{
        starts_at: form.event_date,
        ends_at: new Date(new Date(form.event_date).getTime() + 3 * 60 * 60 * 1000).toISOString(), // +3h par défaut
        door_time: new Date(new Date(form.event_date).getTime() - 30 * 60 * 1000).toISOString() // -30min par défaut
      }],
      ticket_types: form.ticket_types.map(ticket => ({
        name: ticket.name,
        description: ticket.description,
        price: parseFloat(ticket.price),
        capacity: parseInt(ticket.capacity),
        is_active: ticket.is_active
      }))
    };
    
    const response = await organizerService.createEvent(eventData);
    
    if (response.data.success) {
      Swal.fire({
        title: 'Succès !',
        text: 'Événement créé avec succès !',
        icon: 'success',
        confirmButtonColor: '#272d63'
      }).then(() => {
        router.push(`/organizer/events/${response.data.data.id}`);
      });
    } else {
      throw new Error(response.data.message || 'Erreur lors de la création');
    }
  } catch (err) {
    console.error('Erreur création événement:', err);
    Swal.fire({
      title: 'Erreur',
      text: err.response?.data?.message || 'Erreur lors de la création de l\'événement',
      icon: 'error',
      confirmButtonColor: '#272d63'
    });
  } finally {
    creating.value = false;
  }
};

// Fonction utilitaire pour obtenir l'ID de catégorie
const getCategoryId = (categoryName) => {
  const categoryMap = {
    'Musique': 1,
    'Culture': 2,
    'Gastronomie': 3,
    'Sport': 4,
    'Business': 5,
    'Autres': 6
  };
  return categoryMap[categoryName] || 6; // Par défaut "Autres"
};

// Utilitaires
const formatAmount = (amount) => {
  return new Intl.NumberFormat('fr-FR').format(amount);
};
</script>

<style scoped>
/* Style personnalisé pour la création d'événement */
.event-create {
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