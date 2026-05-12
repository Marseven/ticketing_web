<template>
  <div class="event-create min-h-screen" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%)">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <!-- Header -->
      <div class="mb-6 md:mb-8">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3 md:space-x-4">
            <button
              @click="$router.go(-1)"
              class="text-primea-blue hover:text-primea-yellow transition-colors flex-shrink-0"
            >
              <ArrowLeftIcon class="w-5 h-5 md:w-6 md:h-6" />
            </button>
            <div>
              <h1 class="text-xl md:text-3xl font-bold text-primea-blue font-primea">Créer un événement</h1>
              <p class="text-gray-600 font-primea mt-1 text-sm md:text-base hidden sm:block">Configurez tous les détails de votre événement</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Form -->
      <form @submit.prevent="createEvent" class="space-y-6 md:space-y-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
          <!-- Main form content -->
          <div class="lg:col-span-2 space-y-4 md:space-y-6">
            <!-- Basic Information -->
            <div class="bg-white rounded-primea shadow-primea p-4 md:p-6">
              <h2 class="text-lg md:text-xl font-semibold text-primea-blue font-primea mb-4 md:mb-6">Informations de base</h2>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 md:gap-6">
                <div class="sm:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 font-primea mb-2">Titre de l'événement</label>
                  <input
                    v-model="form.title"
                    type="text"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea text-base"
                    placeholder="Nom de votre événement"
                  />
                </div>

                <div class="sm:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 font-primea mb-2">Description</label>
                  <textarea
                    v-model="form.description"
                    rows="4"
                    class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea resize-none text-base"
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


                <div class="sm:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 font-primea mb-2">Image de l'événement</label>
                  <div class="border-2 border-dashed border-gray-300 rounded-primea p-4 text-center">
                    <div v-if="form.image_preview" class="mb-4">
                      <img :src="form.image_preview" alt="Aperçu" class="max-h-32 mx-auto rounded-primea">
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
            <div class="bg-white rounded-primea shadow-primea p-4 md:p-6">
              <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4 md:mb-6">
                <h2 class="text-lg md:text-xl font-semibold text-primea-blue font-primea">Programmation</h2>
                <button
                  type="button"
                  @click="addSchedule"
                  class="bg-primea-blue text-white px-3 md:px-4 py-2 rounded-primea hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200 font-primea text-sm md:text-base w-full sm:w-auto"
                >
                  <PlusIcon class="w-4 h-4 inline mr-1 md:mr-2" />
                  Ajouter une séance
                </button>
              </div>

              <div class="space-y-4">
                <div v-for="(schedule, index) in form.schedules" :key="index"
                     class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4 p-3 md:p-4 border border-gray-200 rounded-primea">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 font-primea mb-1">Date et heure de début *</label>
                    <input 
                      v-model="schedule.starts_at"
                      type="datetime-local" 
                      required
                      @change="adjustEndTime(schedule)"
                      class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea"
                    />
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-700 font-primea mb-1">Date et heure de fin *</label>
                    <input 
                      v-model="schedule.ends_at"
                      type="datetime-local" 
                      required
                      :min="schedule.starts_at"
                      class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea"
                    />
                    <p class="text-xs text-gray-500 mt-1 font-primea">La date de fin doit être égale ou postérieure à la date de début</p>
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
            <div class="bg-white rounded-primea shadow-primea p-4 md:p-6">
              <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4 md:mb-6">
                <h2 class="text-lg md:text-xl font-semibold text-primea-blue font-primea">Types de billets</h2>
                <button
                  type="button"
                  @click="addTicketType"
                  class="bg-primea-blue text-white px-3 md:px-4 py-2 rounded-primea hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200 font-primea text-sm md:text-base w-full sm:w-auto"
                >
                  <PlusIcon class="w-4 h-4 inline mr-1 md:mr-2" />
                  Ajouter un type
                </button>
              </div>

              <div v-if="form.ticket_types.length === 0" class="text-center py-6 md:py-8 text-gray-500">
                <TicketIcon class="w-10 md:w-12 h-10 md:h-12 mx-auto text-gray-300 mb-2" />
                <p class="font-primea text-sm md:text-base">Aucun type de billet configuré</p>
                <p class="text-xs md:text-sm font-primea mt-1">Cliquez sur "Ajouter un type" pour commencer</p>
              </div>

              <div v-else class="space-y-4">
                <div v-for="(ticket, index) in form.ticket_types" :key="index"
                     class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-5 gap-3 md:gap-4 p-3 md:p-4 border border-gray-200 rounded-primea">
                  <div class="col-span-2 sm:col-span-1">
                    <label class="block text-xs md:text-sm font-medium text-gray-700 font-primea mb-1">Nom *</label>
                    <input
                      v-model="ticket.name"
                      type="text"
                      required
                      class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea text-base"
                      placeholder="Ex: Billet Standard"
                    />
                  </div>

                  <div>
                    <label class="block text-xs md:text-sm font-medium text-gray-700 font-primea mb-1">Prix (XAF) *</label>
                    <input
                      v-model.number="ticket.price"
                      type="number"
                      min="0"
                      step="0.01"
                      required
                      class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea text-base"
                      placeholder="25000"
                    />
                  </div>

                  <div>
                    <label class="block text-xs md:text-sm font-medium text-gray-700 font-primea mb-1">Capacité *</label>
                    <input
                      v-model.number="ticket.capacity"
                      type="number"
                      min="1"
                      required
                      class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea text-base"
                      placeholder="100"
                    />
                  </div>

                  <div class="col-span-2 sm:col-span-1">
                    <label class="block text-xs md:text-sm font-medium text-gray-700 font-primea mb-1">Description</label>
                    <input
                      v-model="ticket.description"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea text-base"
                      placeholder="Description du billet"
                    />
                  </div>

                  <div class="col-span-2 lg:col-span-1 flex items-end justify-end">
                    <button
                      v-if="form.ticket_types.length > 1"
                      type="button"
                      @click="removeTicketType(index)"
                      class="bg-red-600 text-white px-3 py-2 rounded-primea hover:bg-red-700 font-primea text-sm w-full lg:w-auto"
                    >
                      <TrashIcon class="w-4 h-4 inline mr-1 lg:mr-0" />
                      <span class="lg:hidden">Supprimer</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Sidebar -->
          <div class="space-y-4 md:space-y-6">
            <!-- Settings -->
            <div class="bg-white rounded-primea shadow-primea p-4 md:p-6">
              <h3 class="text-base md:text-lg font-semibold text-primea-blue font-primea mb-3 md:mb-4">Paramètres</h3>
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
                  <label class="block text-sm font-medium text-gray-700 font-primea mb-2">Statut *</label>
                  <select 
                    v-model="form.status"
                    class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea"
                  >
                    <option value="draft">Brouillon</option>
                    <option value="published">Publié</option>
                    <option value="cancelled">Annulé</option>
                  </select>
                  <p class="text-xs text-gray-500 mt-1 font-primea">Les brouillons ne sont pas visibles par le public</p>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="bg-white rounded-primea shadow-primea p-4 md:p-6">
              <h3 class="text-base md:text-lg font-semibold text-primea-blue font-primea mb-3 md:mb-4">Actions</h3>
              <div class="space-y-3">
                <button
                  type="submit"
                  :disabled="creating"
                  class="w-full bg-primea-blue text-white px-4 py-3 rounded-primea hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200 font-primea disabled:opacity-50 text-sm md:text-base font-semibold"
                >
                  {{ creating ? 'Création en cours...' : 'Créer l\'événement' }}
                </button>

                <router-link
                  to="/organizer/dashboard"
                  class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 text-gray-700 rounded-primea hover:bg-gray-50 transition-all duration-200 font-primea text-sm md:text-base"
                >
                  Annuler
                </router-link>
              </div>
            </div>

            <!-- Preview -->
            <div v-if="form.image_url || form.image_preview" class="bg-white rounded-primea shadow-primea p-4 md:p-6">
              <h3 class="text-base md:text-lg font-semibold text-primea-blue font-primea mb-3 md:mb-4">Aperçu de l'image</h3>
              <img :src="form.image_preview || form.image_url" :alt="form.title" class="w-full h-40 md:h-48 object-cover rounded-primea">
            </div>

            <!-- Summary -->
            <div class="bg-white rounded-primea shadow-primea p-4 md:p-6">
              <h3 class="text-base md:text-lg font-semibold text-primea-blue font-primea mb-3 md:mb-4">Résumé</h3>
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

    <ImageCropper
      v-model:open="cropperOpen"
      :src="cropperSrc"
      aspect-ratio="16/9"
      :file-name="cropperFileName"
      title="Recadrer la couverture de l'événement"
      @cropped="onImageCropped"
      @cancel="onCropCancel"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { organizerService } from '../../services/api';
import Swal from 'sweetalert2';
import ImageCropper from '../../components/ImageCropper.vue';
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
const showNewVenue = ref(false);
const imageInput = ref(null);

// Formulaire
const form = reactive({
  title: '',
  description: '',
  category_id: '',
  venue_id: '',
  image_url: '',
  image_preview: '',
  image_file: null,
  status: 'draft',
  // Nouveaux champs pour lieu
  new_venue_name: '',
  new_venue_city: '',
  new_venue_address: '',
  // Programmation
  schedules: [{ starts_at: '', ends_at: '' }],
  // Types de billets
  ticket_types: [{ name: '', price: '', capacity: '', description: '' }]
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
    capacity: ''
  });
};

const addSchedule = () => {
  form.schedules.push({ starts_at: '', ends_at: '' });
};

const removeSchedule = (index) => {
  if (form.schedules.length > 1) {
    form.schedules.splice(index, 1);
  }
};

const handleVenueChange = () => {
  if (form.venue_id === 'new') {
    showNewVenue.value = true;
  } else {
    showNewVenue.value = false;
  }
};

const cancelNewVenue = () => {
  showNewVenue.value = false;
  form.venue_id = '';
  form.new_venue_name = '';
  form.new_venue_city = '';
  form.new_venue_address = '';
};

const cropperOpen = ref(false);
const cropperSrc = ref('');
const cropperFileName = ref('event-cover.jpg');

const handleImageUpload = (event) => {
  const file = event.target.files[0];
  if (!file) return;

  // Lire l'image pour ouvrir le cropper. Le fichier final sera produit
  // par ImageCropper (recadré au ratio 16/9 pour rester cohérent avec
  // l'affichage SmartImage).
  const reader = new FileReader();
  reader.onload = (e) => {
    cropperSrc.value = e.target.result;
    cropperFileName.value = file.name.replace(/\.[^.]+$/, '') + '.jpg';
    cropperOpen.value = true;
  };
  reader.readAsDataURL(file);
};

const onImageCropped = (croppedFile) => {
  form.image_file = croppedFile;
  const reader = new FileReader();
  reader.onload = (e) => {
    form.image_preview = e.target.result;
    form.image_url = '';
  };
  reader.readAsDataURL(croppedFile);
  cropperSrc.value = '';
};

const onCropCancel = () => {
  cropperSrc.value = '';
  if (imageInput.value) imageInput.value.value = '';
};

const handleImageUrl = () => {
  if (form.image_url) {
    form.image_preview = form.image_url;
    form.image_file = null; // Clear file if URL is entered
  }
};

const removeImage = () => {
  form.image_url = '';
  form.image_preview = '';
  form.image_file = null;
  if (imageInput.value) {
    imageInput.value.value = '';
  }
};

const removeTicketType = (index) => {
  if (form.ticket_types.length > 1) {
    form.ticket_types.splice(index, 1);
  }
};

const adjustEndTime = (schedule) => {
  // Si la date de fin est antérieure à la date de début, l'ajuster
  if (schedule.starts_at && (!schedule.ends_at || new Date(schedule.ends_at) < new Date(schedule.starts_at))) {
    // Ajouter 2 heures par défaut à la date de début
    const startDate = new Date(schedule.starts_at);
    startDate.setHours(startDate.getHours() + 2);
    schedule.ends_at = startDate.toISOString().slice(0, 16); // Format datetime-local
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

  if (!form.title) {
    Swal.fire({
      title: 'Erreur',
      text: 'Veuillez remplir le titre de l\'événement',
      icon: 'error',
      confirmButtonColor: '#272d63'
    });
    return;
  }

  creating.value = true;
  try {
    // Validation avancée
    if (!form.category_id) {
      Swal.fire({
        title: 'Erreur',
        text: 'Veuillez sélectionner une catégorie',
        icon: 'error',
        confirmButtonColor: '#272d63'
      });
      return;
    }

    if (!form.venue_id && !form.new_venue_name) {
      Swal.fire({
        title: 'Erreur',
        text: 'Veuillez sélectionner un lieu ou créer un nouveau lieu',
        icon: 'error',
        confirmButtonColor: '#272d63'
      });
      return;
    }

    if (form.schedules.some(s => !s.starts_at || !s.ends_at)) {
      Swal.fire({
        title: 'Erreur',
        text: 'Veuillez remplir toutes les dates et heures de programmation',
        icon: 'error',
        confirmButtonColor: '#272d63'
      });
      return;
    }
    
    // Vérifier que les dates de fin sont après ou égales aux dates de début
    if (form.schedules.some(s => new Date(s.ends_at) < new Date(s.starts_at))) {
      Swal.fire({
        title: 'Erreur',
        text: 'La date de fin doit être égale ou postérieure à la date de début',
        icon: 'error',
        confirmButtonColor: '#272d63'
      });
      return;
    }

    // Préparer les données pour l'API
    const eventData = {
      title: form.title,
      description: form.description,
      category_id: form.category_id,
      venue_id: form.venue_id !== 'new' ? form.venue_id : null,
      venue_name: form.venue_id === 'new' ? form.new_venue_name : null,
      venue_city: form.venue_id === 'new' ? form.new_venue_city : 'Abidjan',
      venue_address: form.venue_id === 'new' ? form.new_venue_address : null,
      is_active: form.status === 'published' ? 1 : 0,
      schedules: form.schedules.map(schedule => ({
        starts_at: schedule.starts_at,
        ends_at: schedule.ends_at,
        door_time: schedule.starts_at // Utiliser la même heure de début pour door_time
      })),
      ticket_types: form.ticket_types.map(ticket => ({
        name: ticket.name,
        description: ticket.description,
        price: parseFloat(ticket.price),
        capacity: parseInt(ticket.capacity),
        is_active: true
      }))
    };
    
    let response;
    if (form.image_file) {
      // Si un fichier est uploadé, utiliser FormData
      const formData = new FormData();
      
      // Ajouter tous les champs au FormData
      Object.keys(eventData).forEach(key => {
        if (eventData[key] !== null && eventData[key] !== undefined) {
          if (key === 'schedules' || key === 'ticket_types') {
            formData.append(key, JSON.stringify(eventData[key]));
          } else {
            formData.append(key, eventData[key]);
          }
        }
      });
      
      // Ajouter le fichier image
      formData.append('image_file', form.image_file);
      
      response = await organizerService.createEventWithFile(formData);
    } else {
      // Si une URL est utilisée ou pas d'image
      eventData.image_url = form.image_url || null;
      response = await organizerService.createEvent(eventData);
    }
    
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
    console.error('Détails erreur:', err.response?.data);
    
    let errorMessage = err.response?.data?.message || 'Erreur lors de la création de l\'événement';
    
    // Afficher les détails des erreurs de validation
    if (err.response?.data?.errors) {
      const errors = err.response.data.errors;
      const errorDetails = Object.keys(errors).map(field => {
        return `${field}: ${errors[field].join(', ')}`;
      }).join('\n');
      
      errorMessage += '\n\nDétails:\n' + errorDetails;
    }
    
    Swal.fire({
      title: 'Erreur de validation',
      text: errorMessage,
      icon: 'error',
      confirmButtonColor: '#272d63'
    });
  } finally {
    creating.value = false;
  }
};

// Charger les données initiales
const loadInitialData = async () => {
  loading.value = true;
  try {
    // Charger les catégories
    const categoriesResponse = await organizerService.getCategories();
    if (categoriesResponse.data.success) {
      categories.value = categoriesResponse.data.categories || categoriesResponse.data.data;
    }
    
    // Charger les lieux
    const venuesResponse = await organizerService.getVenues();
    if (venuesResponse.data.success) {
      venues.value = venuesResponse.data.venues || venuesResponse.data.data;
    }
  } catch (error) {
    console.error('Erreur chargement données initiales:', error);
  } finally {
    loading.value = false;
  }
};

// Utilitaires
const formatAmount = (amount) => {
  return new Intl.NumberFormat('fr-FR').format(amount);
};

// Charger les données au montage
onMounted(() => {
  loadInitialData();
});
</script>

<style scoped>
/* Style personnalisé pour la création d'événement */
.event-create {
  font-family: 'Inter', 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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
  font-family: 'Inter', 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
</style>