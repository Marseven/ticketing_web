<template>
  <div class="event-create min-h-screen bg-gray-100">
    <!-- En-tête -->
    <header class="bg-blue-600 text-white py-4 px-4 shadow-lg">
      <div class="max-w-4xl mx-auto flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <router-link to="/organizer/events" class="text-blue-200 hover:text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
          </router-link>
          <img src="/images/logo_white.png" alt="Primea" class="h-8" />
          <div>
            <h1 class="text-xl font-bold">Créer un événement</h1>
            <p class="text-blue-200 text-sm">Nouveau spectacle</p>
          </div>
        </div>
      </div>
    </header>

    <div class="max-w-4xl mx-auto py-8 px-4">
      <form @submit.prevent="createEvent" class="space-y-8">
        
        <!-- Informations générales -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <h2 class="text-xl font-bold text-gray-800 mb-6">Informations générales</h2>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
              <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                Titre de l'événement *
              </label>
              <input 
                type="text"
                id="title"
                v-model="eventForm.title"
                placeholder="Ex: Concert Jazz Under The Stars"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                required
              />
            </div>

            <div class="md:col-span-2">
              <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                Description *
              </label>
              <textarea 
                id="description"
                v-model="eventForm.description"
                rows="4"
                placeholder="Décrivez votre événement..."
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                required
              ></textarea>
            </div>

            <div>
              <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                Catégorie *
              </label>
              <select 
                id="category"
                v-model="eventForm.category"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                required
              >
                <option value="">Sélectionner une catégorie</option>
                <option value="concert">Concert</option>
                <option value="theater">Théâtre</option>
                <option value="sport">Sport</option>
                <option value="conference">Conférence</option>
                <option value="festival">Festival</option>
                <option value="cinema">Cinéma</option>
                <option value="expo">Exposition</option>
              </select>
            </div>

            <div>
              <label for="ageRestriction" class="block text-sm font-medium text-gray-700 mb-2">
                Restriction d'âge
              </label>
              <select 
                id="ageRestriction"
                v-model="eventForm.ageRestriction"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="all">Tout public</option>
                <option value="16">16 ans et plus</option>
                <option value="18">18 ans et plus</option>
                <option value="21">21 ans et plus</option>
              </select>
            </div>

            <!-- Upload d'image -->
            <div class="md:col-span-2">
              <label for="image" class="block text-sm font-medium text-gray-700 mb-2">
                Image principale
              </label>
              <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors">
                <div v-if="!imagePreview">
                  <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  <div class="flex text-sm text-gray-600 justify-center">
                    <label for="image-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500">
                      <span>Télécharger une image</span>
                      <input id="image-upload" type="file" @change="handleImageUpload" class="sr-only" accept="image/*">
                    </label>
                    <p class="pl-1">ou glisser-déposer</p>
                  </div>
                  <p class="text-xs text-gray-500">PNG, JPG, GIF jusqu'à 5MB</p>
                </div>
                <div v-else class="relative">
                  <img :src="imagePreview" alt="Aperçu" class="mx-auto h-32 object-cover rounded-lg">
                  <button type="button" @click="removeImage" class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Date et lieu -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <h2 class="text-xl font-bold text-gray-800 mb-6">Date et lieu</h2>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="startDate" class="block text-sm font-medium text-gray-700 mb-2">
                Date de début *
              </label>
              <input 
                type="datetime-local"
                id="startDate"
                v-model="eventForm.startDate"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                required
              />
            </div>

            <div>
              <label for="endDate" class="block text-sm font-medium text-gray-700 mb-2">
                Date de fin
              </label>
              <input 
                type="datetime-local"
                id="endDate"
                v-model="eventForm.endDate"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <div class="md:col-span-2">
              <label for="venue" class="block text-sm font-medium text-gray-700 mb-2">
                Lieu *
              </label>
              <input 
                type="text"
                id="venue"
                v-model="eventForm.venue"
                placeholder="Ex: Palais de la Culture, Libreville"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                required
              />
            </div>

            <div>
              <label for="city" class="block text-sm font-medium text-gray-700 mb-2">
                Ville *
              </label>
              <select 
                id="city"
                v-model="eventForm.city"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                required
              >
                <option value="">Sélectionner une ville</option>
                <option value="Libreville">Libreville</option>
                <option value="Bouaké">Bouaké</option>
                <option value="Daloa">Daloa</option>
                <option value="Korhogo">Korhogo</option>
                <option value="San Pedro">San Pedro</option>
                <option value="Man">Man</option>
                <option value="Divo">Divo</option>
                <option value="Gagnoa">Gagnoa</option>
              </select>
            </div>

            <div>
              <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                Adresse complète
              </label>
              <input 
                type="text"
                id="address"
                v-model="eventForm.address"
                placeholder="Adresse exacte du lieu"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
          </div>
        </div>

        <!-- Types de tickets -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-800">Types de tickets</h2>
            <button 
              type="button"
              @click="addTicketType"
              class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
            >
              + Ajouter un type
            </button>
          </div>

          <div class="space-y-4">
            <div 
              v-for="(ticket, index) in eventForm.ticketTypes" 
              :key="index"
              class="border border-gray-200 rounded-lg p-4"
            >
              <div class="flex items-center justify-between mb-4">
                <h3 class="font-medium text-gray-800">Type de ticket #{{ index + 1 }}</h3>
                <button 
                  v-if="eventForm.ticketTypes.length > 1"
                  type="button"
                  @click="removeTicketType(index)"
                  class="text-red-600 hover:text-red-800"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                </button>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                  <input 
                    type="text"
                    v-model="ticket.name"
                    placeholder="Ex: Standard, VIP"
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    required
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Prix (FCFA) *</label>
                  <input 
                    type="number"
                    v-model="ticket.price"
                    placeholder="15000"
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    required
                    min="0"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Quantité *</label>
                  <input 
                    type="number"
                    v-model="ticket.quantity"
                    placeholder="100"
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    required
                    min="1"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Max par personne</label>
                  <input 
                    type="number"
                    v-model="ticket.maxPerPerson"
                    placeholder="10"
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    min="1"
                  />
                </div>
              </div>

              <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea 
                  v-model="ticket.description"
                  rows="2"
                  placeholder="Avantages de ce type de ticket..."
                  class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                ></textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- Options avancées -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <h2 class="text-xl font-bold text-gray-800 mb-6">Options avancées</h2>
          
          <div class="space-y-4">
            <div class="flex items-center">
              <input 
                type="checkbox"
                id="isPrivate"
                v-model="eventForm.isPrivate"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <label for="isPrivate" class="ml-2 block text-sm text-gray-900">
                Événement privé (invitation uniquement)
              </label>
            </div>

            <div class="flex items-center">
              <input 
                type="checkbox"
                id="allowRefunds"
                v-model="eventForm.allowRefunds"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <label for="allowRefunds" class="ml-2 block text-sm text-gray-900">
                Autoriser les remboursements
              </label>
            </div>

            <div class="flex items-center">
              <input 
                type="checkbox"
                id="requireApproval"
                v-model="eventForm.requireApproval"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <label for="requireApproval" class="ml-2 block text-sm text-gray-900">
                Nécessite approbation avant publication
              </label>
            </div>
          </div>
        </div>

        <!-- Messages d'erreur -->
        <div v-if="errors.length > 0" class="bg-red-50 border border-red-200 rounded-lg p-4">
          <div class="flex">
            <svg class="w-5 h-5 text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
              <h3 class="text-sm font-medium text-red-800">Erreurs de validation :</h3>
              <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                <li v-for="error in errors" :key="error">{{ error }}</li>
              </ul>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between">
          <router-link 
            to="/organizer/events"
            class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition-colors"
          >
            Annuler
          </router-link>

          <div class="space-x-4">
            <button 
              type="button"
              @click="saveDraft"
              :disabled="loading"
              class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition-colors disabled:opacity-50"
            >
              Sauvegarder comme brouillon
            </button>

            <button 
              type="submit"
              :disabled="loading"
              class="px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors disabled:opacity-50"
            >
              <span v-if="loading" class="flex items-center">
                <svg class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/>
                  <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" class="opacity-75"/>
                </svg>
                Création en cours...
              </span>
              <span v-else>Publier l'événement</span>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'

export default {
  name: 'EventCreate',
  setup() {
    const router = useRouter()

    // État réactif
    const loading = ref(false)
    const errors = ref([])
    const imagePreview = ref('')

    const eventForm = reactive({
      title: '',
      description: '',
      category: '',
      ageRestriction: 'all',
      startDate: '',
      endDate: '',
      venue: '',
      city: '',
      address: '',
      image: null,
      isPrivate: false,
      allowRefunds: true,
      requireApproval: false,
      ticketTypes: [
        {
          name: 'Standard',
          price: 10000,
          quantity: 100,
          maxPerPerson: 10,
          description: ''
        }
      ]
    })

    // Méthodes
    const handleImageUpload = (event) => {
      const file = event.target.files[0]
      if (file) {
        eventForm.image = file
        const reader = new FileReader()
        reader.onload = (e) => {
          imagePreview.value = e.target.result
        }
        reader.readAsDataURL(file)
      }
    }

    const removeImage = () => {
      eventForm.image = null
      imagePreview.value = ''
    }

    const addTicketType = () => {
      eventForm.ticketTypes.push({
        name: '',
        price: 0,
        quantity: 0,
        maxPerPerson: 10,
        description: ''
      })
    }

    const removeTicketType = (index) => {
      eventForm.ticketTypes.splice(index, 1)
    }

    const validateForm = () => {
      errors.value = []

      // Validations de base
      if (!eventForm.title.trim()) {
        errors.value.push('Le titre est requis')
      }

      if (!eventForm.description.trim()) {
        errors.value.push('La description est requise')
      }

      if (!eventForm.category) {
        errors.value.push('La catégorie est requise')
      }

      if (!eventForm.startDate) {
        errors.value.push('La date de début est requise')
      }

      if (!eventForm.venue.trim()) {
        errors.value.push('Le lieu est requis')
      }

      if (!eventForm.city) {
        errors.value.push('La ville est requise')
      }

      // Validation des dates
      if (eventForm.startDate && eventForm.endDate) {
        const startDate = new Date(eventForm.startDate)
        const endDate = new Date(eventForm.endDate)
        
        if (endDate <= startDate) {
          errors.value.push('La date de fin doit être postérieure à la date de début')
        }
      }

      // Validation des types de tickets
      if (eventForm.ticketTypes.length === 0) {
        errors.value.push('Au moins un type de ticket est requis')
      } else {
        eventForm.ticketTypes.forEach((ticket, index) => {
          if (!ticket.name.trim()) {
            errors.value.push(`Le nom du type de ticket #${index + 1} est requis`)
          }
          if (!ticket.price || ticket.price <= 0) {
            errors.value.push(`Le prix du type de ticket #${index + 1} doit être supérieur à 0`)
          }
          if (!ticket.quantity || ticket.quantity <= 0) {
            errors.value.push(`La quantité du type de ticket #${index + 1} doit être supérieure à 0`)
          }
        })
      }

      return errors.value.length === 0
    }

    const createEvent = async () => {
      if (!validateForm()) {
        return
      }

      try {
        loading.value = true
        
        // Simulation de création d'événement
        await new Promise(resolve => setTimeout(resolve, 2000))
        
        // Redirection vers la liste des événements
        router.push('/organizer/events')
        
      } catch (error) {
        console.error('Erreur lors de la création:', error)
        errors.value.push('Erreur lors de la création de l\'événement')
      } finally {
        loading.value = false
      }
    }

    const saveDraft = async () => {
      try {
        loading.value = true
        
        // Simulation de sauvegarde en brouillon
        await new Promise(resolve => setTimeout(resolve, 1000))
        
        // Redirection vers la liste des événements
        router.push('/organizer/events')
        
      } catch (error) {
        console.error('Erreur lors de la sauvegarde:', error)
        errors.value.push('Erreur lors de la sauvegarde du brouillon')
      } finally {
        loading.value = false
      }
    }

    return {
      eventForm,
      loading,
      errors,
      imagePreview,
      handleImageUpload,
      removeImage,
      addTicketType,
      removeTicketType,
      createEvent,
      saveDraft
    }
  }
}
</script>

<style scoped>
.event-create {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

/* Animation du loader */
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

/* Grid utilities */
.grid {
  display: grid;
}

.grid-cols-1 {
  grid-template-columns: repeat(1, minmax(0, 1fr));
}

@media (min-width: 768px) {
  .md\:grid-cols-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  
  .md\:grid-cols-4 {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
  
  .md\:col-span-2 {
    grid-column: span 2 / span 2;
  }
}

.gap-4 {
  gap: 1rem;
}

.gap-6 {
  gap: 1.5rem;
}

.space-x-4 > * + * {
  margin-left: 1rem;
}

.space-y-4 > * + * {
  margin-top: 1rem;
}

.space-y-8 > * + * {
  margin-top: 2rem;
}

.transition-colors {
  transition: color 0.2s ease-in-out, background-color 0.2s ease-in-out;
}

/* Screen reader only */
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
</style>