<template>
  <div class="event-edit min-h-screen" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%)">
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
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Erreur</h2>
        <p class="text-gray-600 mb-6">{{ error }}</p>
        <router-link 
          :to="{ name: 'organizer-events' }"
          class="bg-primea-blue text-white px-6 py-3 rounded-primea hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200 font-primea"
        >
          Retour aux événements
        </router-link>
      </div>

      <!-- Edit form -->
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
                <h1 class="text-3xl font-bold text-primea-blue font-primea">Modifier l'événement</h1>
                <p class="text-gray-600 font-primea mt-1">{{ event.title }}</p>
              </div>
            </div>
            <div class="flex items-center space-x-3">
              <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full" 
                    :class="getEventStatusClass(event.status)">
                {{ getEventStatusName(event.status) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="updateEvent" class="space-y-8">
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main form content -->
            <div class="lg:col-span-2 space-y-6">
              <!-- Basic Information -->
              <div class="bg-white rounded-primea shadow-primea p-6">
                <h2 class="text-xl font-semibold text-primea-blue font-primea mb-6">Informations de base</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 font-primea mb-2">Titre de l'événement *</label>
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
                    <label class="block text-sm font-medium text-gray-700 font-primea mb-2">Catégorie *</label>
                    <select 
                      v-model="form.category_id"
                      required
                      class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea"
                    >
                      <option value="">Sélectionner une catégorie</option>
                      <option v-for="category in categories" :key="category.id" :value="category.id">
                        {{ category.name }}
                      </option>
                    </select>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 font-primea mb-2">Lieu *</label>
                    <div v-if="!showNewVenue">
                      <select 
                        v-model="form.venue_id" 
                        @change="handleVenueChange"
                        class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea"
                      >
                        <option value="">Sélectionner un lieu</option>
                        <option v-for="venue in venues" :key="venue.id" :value="venue.id">
                          {{ venue.name }} - {{ venue.city }}
                        </option>
                        <option value="new">+ Ajouter un nouveau lieu</option>
                      </select>
                    </div>
                    
                    <div v-if="showNewVenue || form.venue_id === 'new'" class="space-y-2">
                      <input 
                        v-model="form.new_venue_name" 
                        type="text" 
                        placeholder="Nom du lieu" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea" 
                        required
                      >
                      <input 
                        v-model="form.new_venue_city" 
                        type="text" 
                        placeholder="Ville" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea" 
                        required
                      >
                      <input 
                        v-model="form.new_venue_address" 
                        type="text" 
                        placeholder="Adresse" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea"
                      >
                      <button 
                        type="button" 
                        @click="cancelNewVenue" 
                        class="text-sm text-primea-blue hover:text-primea-yellow font-primea"
                      >
                        Utiliser un lieu existant
                      </button>
                    </div>
                  </div>

                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 font-primea mb-2">Image de l'événement</label>
                    <div class="border-2 border-dashed border-gray-300 rounded-primea p-4 text-center">
                      <div v-if="form.image_preview" class="mb-4">
                        <img 
                          :src="form.image_preview" 
                          alt="Aperçu" 
                          class="max-h-32 mx-auto rounded-primea"
                          @error="handleImageError"
                          @load="handleImageLoad"
                        >
                        <button 
                          type="button" 
                          @click="removeImage" 
                          class="mt-2 text-sm text-red-600 hover:text-red-800"
                        >
                          Supprimer l'image
                        </button>
                      </div>
                      <div v-else>
                        <input 
                          ref="imageInput"
                          type="file" 
                          accept="image/*" 
                          @change="handleImageUpload" 
                          class="hidden"
                        >
                        <button 
                          type="button" 
                          @click="$refs.imageInput.click()" 
                          class="bg-primea-blue text-white px-4 py-2 rounded-primea hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200 font-primea"
                        >
                          Choisir une image
                        </button>
                        <p class="text-sm text-gray-500 mt-2 font-primea">ou</p>
                        <input 
                          v-model="form.image_url"
                          type="url"
                          class="mt-2 w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea"
                          placeholder="URL de l'image (https://exemple.com/image.jpg)"
                          @input="handleImageUrl"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Programmation -->
              <div class="bg-white rounded-primea shadow-primea p-6">
                <div class="flex items-center justify-between mb-6">
                  <h2 class="text-xl font-semibold text-primea-blue font-primea">Programmation</h2>
                  <button 
                    type="button"
                    @click="addSchedule"
                    class="bg-primea-blue text-white px-4 py-2 rounded-primea hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200 font-primea"
                  >
                    <PlusIcon class="w-4 h-4 inline mr-2" />
                    Ajouter une séance
                  </button>
                </div>

                <div class="space-y-4">
                  <div v-for="(schedule, index) in form.schedules" :key="index" 
                       class="grid grid-cols-1 md:grid-cols-4 gap-4 p-4 border border-gray-200 rounded-primea">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 font-primea mb-1">Date et heure de début *</label>
                      <input 
                        v-model="schedule.starts_at"
                        type="datetime-local" 
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea"
                      />
                    </div>
                    
                    <div>
                      <label class="block text-sm font-medium text-gray-700 font-primea mb-1">Date et heure de fin *</label>
                      <input 
                        v-model="schedule.ends_at"
                        type="datetime-local" 
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea"
                      />
                    </div>
                    
                    <div>
                      <label class="block text-sm font-medium text-gray-700 font-primea mb-1">Ouverture des portes</label>
                      <input 
                        v-model="schedule.door_time"
                        type="datetime-local"
                        class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea"
                      />
                    </div>
                    
                    <div class="flex items-end">
                      <button 
                        v-if="form.schedules.length > 1"
                        type="button"
                        @click="removeSchedule(index)"
                        class="bg-red-600 text-white px-3 py-2 rounded-primea hover:bg-red-700 font-primea"
                      >
                        <TrashIcon class="w-4 h-4" />
                      </button>
                    </div>
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
                       class="grid grid-cols-1 md:grid-cols-5 gap-4 p-4 border border-gray-200 rounded-primea">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 font-primea mb-1">Nom *</label>
                      <input 
                        v-model="ticket.name"
                        type="text" 
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea"
                        placeholder="Ex: Billet Standard"
                      />
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 font-primea mb-1">Prix (XAF) *</label>
                      <input 
                        v-model.number="ticket.price"
                        type="number" 
                        min="0"
                        step="0.01"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea"
                        placeholder="25000"
                      />
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 font-primea mb-1">Capacité *</label>
                      <input 
                        v-model.number="ticket.capacity"
                        type="number" 
                        min="1"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea"
                        placeholder="100"
                      />
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 font-primea mb-1">Description</label>
                      <textarea 
                        v-model="ticket.description"
                        rows="2"
                        class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea resize-none"
                        placeholder="Description du billet..."
                      ></textarea>
                    </div>

                    <div class="flex items-center space-x-2">
                      <label class="flex items-center space-x-2">
                        <input 
                          v-model="ticket.is_active"
                          type="checkbox" 
                          class="rounded border-gray-300 text-primea-blue focus:ring-primea-blue"
                        />
                        <span class="text-sm font-medium text-gray-700 font-primea">Actif</span>
                      </label>
                      
                      <button 
                        type="button"
                        @click="removeTicketType(index)"
                        class="text-red-600 hover:text-red-800 p-1"
                      >
                        <TrashIcon class="w-4 h-4" />
                      </button>
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
                        v-model="form.is_active"
                        type="checkbox" 
                        class="rounded border-gray-300 text-primea-blue focus:ring-primea-blue"
                      />
                      <span class="text-sm font-medium text-gray-700 font-primea">Événement actif</span>
                    </label>
                    <p class="text-xs text-gray-500 mt-1 font-primea">Publier l'événement et activer les ventes</p>
                  </div>
                </div>
              </div>

              <!-- Actions -->
              <div class="bg-white rounded-primea shadow-primea p-6">
                <h3 class="text-lg font-semibold text-primea-blue font-primea mb-4">Actions</h3>
                <div class="space-y-3">
                  <button 
                    type="submit"
                    :disabled="saving"
                    class="w-full bg-primea-blue text-white px-4 py-2 rounded-primea hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200 font-primea disabled:opacity-50"
                  >
                    {{ saving ? 'Enregistrement...' : 'Enregistrer les modifications' }}
                  </button>

                  <router-link 
                    :to="{ name: 'organizer-event-detail', params: { slug: event?.slug || route.params.slug } }"
                    class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 text-gray-700 rounded-primea hover:bg-gray-50 transition-all duration-200 font-primea"
                  >
                    Annuler les modifications
                  </router-link>

                  <button 
                    v-if="event && !event.is_active"
                    type="button"
                    @click="publishEvent"
                    class="w-full bg-green-600 text-white px-4 py-2 rounded-primea hover:bg-green-700 transition-all duration-200 font-primea"
                  >
                    <CheckIcon class="w-4 h-4 inline mr-2" />
                    Publier l'événement
                  </button>
                </div>
              </div>

              <!-- Current image preview -->
              <div v-if="event" class="bg-white rounded-primea shadow-primea p-6">
                <h3 class="text-lg font-semibold text-primea-blue font-primea mb-4">Image actuelle</h3>
                <img 
                  :src="getCurrentImageUrl()" 
                  :alt="event.title" 
                  class="w-full h-48 object-cover rounded-primea"
                  @error="handleImageError"
                  @load="handleImageLoad"
                >
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { organizerService } from '../../services/api';
import Swal from 'sweetalert2';
import { 
  ArrowLeftIcon,
  ExclamationTriangleIcon,
  PlusIcon,
  TrashIcon,
  TicketIcon,
  CheckIcon
} from '@heroicons/vue/24/outline';

const route = useRoute();
const router = useRouter();

// État réactif
const loading = ref(true);
const saving = ref(false);
const error = ref(null);
const event = ref(null);
const categories = ref([]);
const venues = ref([]);
const showNewVenue = ref(false);
const imageInput = ref(null);

// Formulaire
const form = reactive({
  title: '',
  description: '',
  category_id: '',
  venue_id: '',
  new_venue_name: '',
  new_venue_city: '',
  new_venue_address: '',
  image_url: '',
  image_preview: '',
  is_active: false,
  schedules: [{ starts_at: '', ends_at: '', door_time: '' }],
  ticket_types: []
});

// Méthodes
const loadCategories = async () => {
  try {
    const response = await organizerService.getCategories();
    categories.value = response.data.categories || [];
  } catch (err) {
    console.error('Erreur chargement catégories:', err);
  }
};

const loadVenues = async () => {
  try {
    const response = await organizerService.getVenues();
    venues.value = response.data.venues || response.data.data || [];
  } catch (err) {
    console.error('Erreur chargement lieux:', err);
  }
};

const loadEvent = async () => {
  loading.value = true;
  error.value = null;
  
  try {
    let identifier = route.params.slug;
    
    // Déterminer si c'est un ID numérique ou un slug
    let eventData;
    if (/^\d+$/.test(identifier)) {
      // C'est un ID numérique
      const response = await organizerService.getEvent(identifier);
      eventData = response.data.data;
    } else {
      // C'est un slug, on doit chercher par slug
      try {
        const eventsResponse = await organizerService.getEvents();
        const events = eventsResponse.data.data.events.data || eventsResponse.data.data.events;
        eventData = events.find(e => e.slug === identifier);
        
        if (!eventData) {
          throw new Error(`Événement avec le slug "${identifier}" non trouvé`);
        }
      } catch (eventsError) {
        throw new Error(`Événement avec le slug "${identifier}" non trouvé`);
      }
    }
    
    if (!eventData) {
      throw new Error(`Événement introuvable`);
    }
    
    // Stocker l'événement
    event.value = {
      ...eventData,
      venue_name: eventData.venue?.name || eventData.venue_name || '',
      venue_address: eventData.venue?.address || eventData.venue_address || '',
      category_name: eventData.category?.name || eventData.category_name || '',
    };
    
    // Remplir le formulaire avec les données existantes
    Object.assign(form, {
      title: event.value.title || '',
      description: event.value.description || '',
      category_id: event.value.category_id || '',
      venue_id: event.value.venue_id || '',
      new_venue_name: '',
      new_venue_city: '',
      new_venue_address: '',
      image_url: event.value.image_url || '',
      image_preview: event.value.image_url || '',
      is_active: event.value.is_active ?? false,
      schedules: (event.value.schedules && event.value.schedules.length > 0) 
        ? event.value.schedules.map(s => ({
            starts_at: s.starts_at ? new Date(s.starts_at).toISOString().slice(0, 16) : '',
            ends_at: s.ends_at ? new Date(s.ends_at).toISOString().slice(0, 16) : '',
            door_time: s.door_time ? new Date(s.door_time).toISOString().slice(0, 16) : ''
          }))
        : [{ starts_at: '', ends_at: '', door_time: '' }],
      ticket_types: (event.value.ticket_types || event.value.ticketTypes || []).map(tt => ({
        id: tt.id,
        name: tt.name || '',
        description: tt.description || '',
        price: tt.price || 0,
        capacity: tt.capacity || tt.available_quantity || tt.max_quantity || tt.quantity || 0,
        is_active: tt.is_active ?? (tt.status === 'active')
      }))
    });
    
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

const updateEvent = async () => {
  saving.value = true;
  try {
    if (!event.value) {
      throw new Error('Aucun événement à mettre à jour');
    }

    // Préparer les données à envoyer (structure simplifiée pour l'édition)
    const updateData = {
      title: form.title,
      description: form.description,
      category_id: form.category_id,
      venue_id: form.venue_id || null,
      new_venue_name: form.venue_id === 'new' ? form.new_venue_name : null,
      new_venue_city: form.venue_id === 'new' ? form.new_venue_city : null,
      new_venue_address: form.venue_id === 'new' ? form.new_venue_address : null,
      image_url: form.image_url,
      is_active: form.is_active,
      schedules: form.schedules,
      ticket_types: form.ticket_types.map(tt => ({
        id: tt.id || null,
        name: tt.name,
        description: tt.description,
        price: tt.price,
        capacity: tt.capacity,
        is_active: tt.is_active
      }))
    };

    // Appel à l'API pour mettre à jour l'événement
    await organizerService.updateEvent(event.value.id, updateData);
    
    await Swal.fire({
      title: 'Succès !',
      text: 'Événement mis à jour avec succès !',
      icon: 'success',
      confirmButtonColor: '#272d63'
    });
    
    router.push({ name: 'organizer-event-detail', params: { slug: event.value.slug } });
  } catch (err) {
    console.error('Erreur lors de la mise à jour:', err);
    await Swal.fire({
      title: 'Erreur',
      text: err.response?.data?.message || 'Erreur lors de la mise à jour de l\'événement',
      icon: 'error',
      confirmButtonColor: '#272d63'
    });
  } finally {
    saving.value = false;
  }
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
      
      await Swal.fire({
        title: 'Succès !',
        text: 'Événement publié avec succès !',
        icon: 'success',
        confirmButtonColor: '#272d63'
      });
    } catch (err) {
      console.error('Erreur publication:', err);
      await Swal.fire({
        title: 'Erreur',
        text: 'Erreur lors de la publication de l\'événement',
        icon: 'error',
        confirmButtonColor: '#272d63'
      });
    }
  }
};

const addTicketType = () => {
  form.ticket_types.push({
    name: '',
    description: '',
    price: '',
    capacity: '',
    is_active: true
  });
};

const removeTicketType = async (index) => {
  const result = await Swal.fire({
    title: 'Supprimer le type de billet',
    text: 'Êtes-vous sûr de vouloir supprimer ce type de billet ?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Oui, supprimer',
    cancelButtonText: 'Annuler'
  });
  
  if (result.isConfirmed) {
    form.ticket_types.splice(index, 1);
  }
};

const addSchedule = () => {
  form.schedules.push({ starts_at: '', ends_at: '', door_time: '' });
};

const removeSchedule = (index) => {
  if (form.schedules.length > 1) {
    form.schedules.splice(index, 1);
  }
};

const handleVenueChange = () => {
  if (form.venue_id === 'new') {
    showNewVenue.value = true;
  }
};

const cancelNewVenue = () => {
  showNewVenue.value = false;
  form.venue_id = '';
  form.new_venue_name = '';
  form.new_venue_city = '';
  form.new_venue_address = '';
};

const handleImageUpload = (event) => {
  const file = event.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = (e) => {
      form.image_preview = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const handleImageUrl = () => {
  if (form.image_url) {
    form.image_preview = form.image_url;
  }
};

const removeImage = () => {
  form.image_url = '';
  form.image_preview = '';
  if (imageInput.value) {
    imageInput.value.value = '';
  }
};

const getCurrentImageUrl = () => {
  // Priorité: image en cours d'édition, puis image actuelle, puis placeholder
  if (form.image_preview) {
    return form.image_preview;
  }
  
  if (!event.value) return 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=400&h=300&fit=crop&crop=center';
  
  const imageUrl = event.value.image_url || event.value.image;
  
  if (imageUrl) {
    // Vérifier si c'est déjà une URL complète ou si c'est juste un nom de fichier
    if (imageUrl.startsWith('http')) {
      return imageUrl;
    } else if (imageUrl.startsWith('/')) {
      return imageUrl;
    } else {
      // Construire l'URL complète si c'est juste un nom de fichier
      return `/storage/events/${imageUrl}`;
    }
  }
  
  // Image placeholder par défaut
  return 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=400&h=300&fit=crop&crop=center';
};

const handleImageError = (event) => {
  console.log('Erreur de chargement d\'image:', event.target.src);
  // Remplacer par l'image placeholder en cas d'erreur
  event.target.src = 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=400&h=300&fit=crop&crop=center';
};

const handleImageLoad = (event) => {
  console.log('Image chargée avec succès:', event.target.src);
};

// Utilitaires
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

onMounted(async () => {
  await Promise.all([
    loadCategories(),
    loadVenues(),
    loadEvent()
  ]);
});
</script>

<style scoped>
/* Style personnalisé pour l'édition d'événement */
.event-edit {
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