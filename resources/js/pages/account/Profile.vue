<template>
  <div class="my-profile min-h-screen bg-gray-50 font-primea">
    <div class="max-w-7xl mx-auto">

      <!-- Statistiques du profil -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-primea-lg shadow-sm p-6 border-l-4 border-primea-blue">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <UserIcon class="w-8 h-8 text-primea-blue" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Membre depuis</p>
              <p class="text-2xl font-bold text-gray-900">{{ membershipDuration }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-primea-lg shadow-sm p-6 border-l-4 border-green-500">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <TicketIcon class="w-8 h-8 text-green-500" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Tickets achetés</p>
              <p class="text-2xl font-bold text-gray-900">{{ stats.totalTickets }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-primea-lg shadow-sm p-6 border-l-4 border-primea-yellow">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <CalendarIcon size="xl" class="text-primea-yellow" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Événements fréquentés</p>
              <p class="text-2xl font-bold text-gray-900">{{ stats.eventsAttended }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-primea-lg shadow-sm p-6 border-l-4 border-purple-500">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <StarIcon class="w-8 h-8 text-purple-500" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Points fidélité</p>
              <p class="text-2xl font-bold text-gray-900">{{ stats.loyaltyPoints }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- État de chargement -->
      <div v-if="loading" class="text-center py-16">
        <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-primea-blue mx-auto mb-4"></div>
        <p class="text-gray-500">Chargement de votre profil...</p>
      </div>

      <!-- Message d'erreur -->
      <div v-else-if="error" class="text-center py-16">
        <ExclamationTriangleIcon class="w-16 h-16 text-red-400 mx-auto mb-4" />
        <h3 class="text-xl font-medium text-red-600 mb-2">Erreur</h3>
        <p class="text-gray-500 mb-6">{{ error }}</p>
        <button 
          @click="loadProfile"
          class="inline-flex items-center px-6 py-3 bg-primea-blue text-white rounded-primea hover:bg-primea-yellow hover:text-primea-blue font-semibold transition-all duration-200"
        >
          Réessayer
        </button>
      </div>

      <!-- Alert de vérification d'email -->
      <div v-if="!emailVerified && !loading && !error" class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-primea">
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <ExclamationTriangleIcon class="w-5 h-5 text-yellow-500 mr-2" />
            <div>
              <p class="text-yellow-800 font-medium">Email non vérifié</p>
              <p class="text-yellow-700 text-sm">Vérifiez votre email pour accéder à toutes les fonctionnalités.</p>
            </div>
          </div>
          <button 
            @click="resendEmailVerification"
            :disabled="resendingEmail"
            class="px-4 py-2 bg-yellow-500 text-white rounded-primea hover:bg-yellow-600 font-semibold transition-all duration-200 disabled:opacity-50"
          >
            <span v-if="resendingEmail">Envoi...</span>
            <span v-else>Renvoyer</span>
          </button>
        </div>
      </div>

      <!-- Message de succès -->
      <div v-if="successMessage && !loading && !error" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-primea">
        <div class="flex items-center">
          <CheckCircleIcon class="w-5 h-5 text-green-500 mr-2" />
          <p class="text-green-700 font-medium">{{ successMessage }}</p>
        </div>
      </div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Informations personnelles -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-primea-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
              <h3 class="text-lg font-semibold text-primea-blue flex items-center">
                <UserIcon class="w-5 h-5 mr-2" />
                Informations personnelles
              </h3>
            </div>

            <form @submit.prevent="updateProfile" class="p-6 space-y-6">
              <!-- Photo de profil -->
              <div class="flex items-center space-x-6">
                <div class="w-20 h-20 rounded-full overflow-hidden bg-primea-blue text-white flex items-center justify-center text-2xl font-bold">
                  <img 
                    v-if="profileForm.avatar" 
                    :src="profileForm.avatar" 
                    :alt="profileForm.name"
                    class="w-full h-full object-cover"
                    @error="profileForm.avatar = null"
                  />
                  <span v-else>{{ userInitial }}</span>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-900 mb-1">Photo de profil</h4>
                  <p class="text-sm text-gray-500 mb-3">Ajoutez une photo pour personnaliser votre profil</p>
                  <input 
                    ref="avatarInput"
                    type="file" 
                    accept="image/*" 
                    @change="handleAvatarUpload"
                    class="hidden"
                  />
                  <button 
                    type="button"
                    @click="$refs.avatarInput.click()"
                    :disabled="uploadingAvatar"
                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-primea hover:bg-gray-50 transition-colors duration-200 flex items-center gap-2 disabled:opacity-50"
                  >
                    <div v-if="uploadingAvatar" class="w-4 h-4 border-2 border-gray-400 border-t-transparent rounded-full animate-spin"></div>
                    <PhotoIcon v-else class="w-4 h-4" />
                    {{ uploadingAvatar ? 'Upload...' : 'Changer la photo' }}
                  </button>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Nom -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Nom complet <span class="text-red-500">*</span>
                  </label>
                  <input 
                    v-model="profileForm.name"
                    type="text"
                    required
                    maxlength="255"
                    class="w-full px-4 py-3 border border-gray-300 rounded-primea focus:ring-primea-blue focus:border-primea-blue"
                    placeholder="Votre nom complet"
                  />
                </div>

                <!-- Email -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Adresse email <span class="text-red-500">*</span>
                  </label>
                  <input 
                    v-model="profileForm.email"
                    type="email"
                    required
                    maxlength="255"
                    class="w-full px-4 py-3 border border-gray-300 rounded-primea focus:ring-primea-blue focus:border-primea-blue"
                    placeholder="votre@email.com"
                  />
                </div>

                <!-- Téléphone -->
                <div v-if="hasPhoneField">
                  <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Numéro de téléphone</label>
                  <PhoneInput
                    id="phone"
                    v-model="profileForm.phone"
                    placeholder="XX XX XX XX"
                    default-country="GA"
                  />
                </div>

                <!-- Date de naissance -->
                <div v-if="hasBirthdateField">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Date de naissance</label>
                  <input 
                    v-model="profileForm.birthdate"
                    type="date" 
                    class="w-full px-4 py-3 border border-gray-300 rounded-primea focus:ring-primea-blue focus:border-primea-blue"
                  />
                </div>

                <!-- Ville -->
                <div v-if="hasCityField">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Ville</label>
                  <input 
                    v-model="profileForm.city"
                    type="text"
                    maxlength="100"
                    class="w-full px-4 py-3 border border-gray-300 rounded-primea focus:ring-primea-blue focus:border-primea-blue"
                    placeholder="Libreville"
                  />
                </div>

                <!-- Pays -->
                <div v-if="hasCountryField">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Pays</label>
                  <select 
                    v-model="profileForm.country"
                    class="w-full px-4 py-3 border border-gray-300 rounded-primea focus:ring-primea-blue focus:border-primea-blue"
                  >
                    <option value="">Sélectionner un pays</option>
                    <option value="GA">Gabon</option>
                    <option value="SN">Sénégal</option>
                    <option value="ML">Mali</option>
                    <option value="BF">Burkina Faso</option>
                    <option value="GH">Ghana</option>
                  </select>
                </div>
              </div>

              <!-- Bio -->
              <div v-if="hasBioField">
                <label class="block text-sm font-medium text-gray-700 mb-2">Biographie</label>
                <textarea 
                  v-model="profileForm.bio"
                  rows="4"
                  maxlength="500"
                  class="w-full px-4 py-3 border border-gray-300 rounded-primea focus:ring-primea-blue focus:border-primea-blue"
                  placeholder="Parlez-nous de vous..."
                ></textarea>
                <p class="text-sm text-gray-500 mt-1">{{ (profileForm.bio || '').length }}/500 caractères</p>
              </div>

              <!-- Boutons d'action -->
              <div class="flex flex-col md:flex-row gap-4 pt-6 border-t border-gray-200">
                <button 
                  type="submit"
                  :disabled="saving"
                  class="px-6 py-3 bg-primea-blue text-white rounded-primea hover:bg-primea-yellow hover:text-primea-blue font-semibold transition-all duration-200 flex items-center justify-center gap-2"
                >
                  <CheckIcon v-if="!saving" class="w-4 h-4" />
                  <div v-else class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                  {{ saving ? 'Enregistrement...' : 'Sauvegarder les modifications' }}
                </button>
                
                <button 
                  type="button"
                  @click="resetForm"
                  class="px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-primea hover:bg-gray-50 font-semibold transition-all duration-200"
                >
                  Annuler
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Panneau latéral -->
        <div class="space-y-6">
          <!-- Sécurité du compte -->
          <div class="bg-white rounded-primea-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
              <h3 class="text-lg font-semibold text-primea-blue flex items-center">
                <ShieldCheckIcon class="w-5 h-5 mr-2" />
                Sécurité du compte
              </h3>
            </div>

            <div class="p-6 space-y-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="font-medium text-gray-900">Mot de passe</p>
                  <p class="text-sm text-gray-500">Dernière modification il y a 3 mois</p>
                </div>
                <button 
                  @click="showPasswordModal = true"
                  class="text-primea-blue hover:text-primea-yellow transition-colors duration-200"
                >
                  <PencilIcon class="w-5 h-5" />
                </button>
              </div>

              <div class="flex items-center justify-between">
                <div>
                  <p class="font-medium text-gray-900">Authentification à deux facteurs</p>
                  <p class="text-sm text-gray-500">Protection renforcée de votre compte</p>
                </div>
                <button 
                  class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-gray-200 transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primea-blue focus:ring-offset-2"
                >
                  <span class="translate-x-0 pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                </button>
              </div>

              <div class="pt-4 border-t border-gray-200">
                <button 
                  @click="showDeleteModal = true"
                  class="w-full px-4 py-2 text-left text-red-600 hover:bg-red-50 rounded-primea transition-colors duration-200 flex items-center gap-2"
                >
                  <ExclamationTriangleIcon class="w-4 h-4" />
                  Supprimer mon compte
                </button>
              </div>
            </div>
          </div>

          <!-- Préférences -->
          <div class="bg-white rounded-primea-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
              <h3 class="text-lg font-semibold text-primea-blue flex items-center">
                <CogIcon class="w-5 h-5 mr-2" />
                Préférences
              </h3>
            </div>

            <div class="p-6 space-y-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="font-medium text-gray-900">Notifications par email</p>
                  <p class="text-sm text-gray-500">Recevoir les offres et nouveautés</p>
                </div>
                <button 
                  @click="updatePreferences('emailNotifications', !preferences.emailNotifications)"
                  :class="[
                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primea-blue focus:ring-offset-2',
                    preferences.emailNotifications ? 'bg-primea-blue' : 'bg-gray-200'
                  ]"
                >
                  <span :class="[
                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                    preferences.emailNotifications ? 'translate-x-5' : 'translate-x-0'
                  ]"></span>
                </button>
              </div>

              <div class="flex items-center justify-between">
                <div>
                  <p class="font-medium text-gray-900">Notifications SMS</p>
                  <p class="text-sm text-gray-500">Rappels et confirmations</p>
                </div>
                <button 
                  @click="updatePreferences('smsNotifications', !preferences.smsNotifications)"
                  :class="[
                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primea-blue focus:ring-offset-2',
                    preferences.smsNotifications ? 'bg-primea-blue' : 'bg-gray-200'
                  ]"
                >
                  <span :class="[
                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                    preferences.smsNotifications ? 'translate-x-5' : 'translate-x-0'
                  ]"></span>
                </button>
              </div>

              <div v-if="hasLanguageField">
                <label class="block text-sm font-medium text-gray-700 mb-2">Langue préférée</label>
                <select 
                  v-model="preferences.language"
                  @change="updatePreferences('language', preferences.language)"
                  class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-primea-blue focus:border-primea-blue"
                >
                  <option value="fr">Français</option>
                  <option value="en">English</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Activité récente -->
          <div class="bg-white rounded-primea-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
              <h3 class="text-lg font-semibold text-primea-blue flex items-center">
                <ClockIcon class="w-5 h-5 mr-2" />
                Activité récente
              </h3>
            </div>

            <div class="p-6">
              <div class="space-y-4">
                <div v-for="activity in recentActivities" :key="activity.id" class="flex items-start gap-3">
                  <div class="w-8 h-8 bg-primea-blue/10 rounded-full flex items-center justify-center flex-shrink-0">
                    <component :is="activity.icon" class="w-4 h-4 text-primea-blue" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900">{{ activity.title }}</p>
                    <p class="text-sm text-gray-500">{{ activity.date }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal changement de mot de passe -->
      <div v-if="showPasswordModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-primea-lg max-w-md w-full p-6">
          <h3 class="text-lg font-semibold text-primea-blue mb-4">Changer le mot de passe</h3>
          
          <form @submit.prevent="updatePassword" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Mot de passe actuel</label>
              <input 
                v-model="passwordForm.currentPassword"
                type="password" 
                class="w-full px-4 py-3 border border-gray-300 rounded-primea focus:ring-primea-blue focus:border-primea-blue"
                required
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Nouveau mot de passe</label>
              <input 
                v-model="passwordForm.newPassword"
                type="password" 
                class="w-full px-4 py-3 border border-gray-300 rounded-primea focus:ring-primea-blue focus:border-primea-blue"
                required
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Confirmer le nouveau mot de passe</label>
              <input 
                v-model="passwordForm.confirmPassword"
                type="password" 
                class="w-full px-4 py-3 border border-gray-300 rounded-primea focus:ring-primea-blue focus:border-primea-blue"
                required
              />
            </div>

            <div class="flex gap-3 pt-4">
              <button 
                type="submit"
                class="flex-1 px-4 py-3 bg-primea-blue text-white rounded-primea hover:bg-primea-yellow hover:text-primea-blue font-semibold transition-all duration-200"
              >
                Mettre à jour
              </button>
              <button 
                type="button"
                @click="showPasswordModal = false"
                class="flex-1 px-4 py-3 border-2 border-gray-300 text-gray-700 rounded-primea hover:bg-gray-50 font-semibold transition-all duration-200"
              >
                Annuler
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal suppression de compte -->
      <div v-if="showDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-primea-lg max-w-md w-full p-6">
          <h3 class="text-lg font-semibold text-red-600 mb-4 flex items-center gap-2">
            <ExclamationTriangleIcon class="w-6 h-6" />
            Supprimer mon compte
          </h3>
          
          <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-primea">
            <p class="text-red-800 text-sm font-medium mb-2">⚠️ Action irréversible</p>
            <p class="text-red-700 text-sm">Cette action supprimera définitivement votre compte et toutes vos données. Cette action ne peut pas être annulée.</p>
          </div>
          
          <form @submit.prevent="deleteAccount" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Mot de passe</label>
              <input 
                v-model="deleteForm.password"
                type="password" 
                class="w-full px-4 py-3 border border-gray-300 rounded-primea focus:ring-red-500 focus:border-red-500"
                placeholder="Votre mot de passe"
                required
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Tapez "SUPPRIMER" pour confirmer
              </label>
              <input 
                v-model="deleteForm.confirmation"
                type="text" 
                class="w-full px-4 py-3 border border-gray-300 rounded-primea focus:ring-red-500 focus:border-red-500"
                placeholder="SUPPRIMER"
                required
              />
            </div>

            <div v-if="error" class="bg-red-50 border border-red-200 rounded-primea p-3">
              <p class="text-red-800 text-sm">{{ error }}</p>
            </div>

            <div class="flex gap-3 pt-4">
              <button 
                type="submit"
                class="flex-1 px-4 py-3 bg-red-600 text-white rounded-primea hover:bg-red-700 font-semibold transition-all duration-200"
              >
                Supprimer définitivement
              </button>
              <button 
                type="button"
                @click="showDeleteModal = false; deleteForm = { password: '', confirmation: '' }"
                class="flex-1 px-4 py-3 border-2 border-gray-300 text-gray-700 rounded-primea hover:bg-gray-50 font-semibold transition-all duration-200"
              >
                Annuler
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import CalendarIcon from '../../components/icons/CalendarIcon.vue'
import PhoneInput from '../../components/PhoneInput.vue'
import { clientService, ticketApiService } from '../../services/api.js'
import { 
  UserIcon,
  TicketIcon,
  StarIcon,
  PhotoIcon,
  CheckIcon,
  CheckCircleIcon,
  ShieldCheckIcon,
  PencilIcon,
  ExclamationTriangleIcon,
  CogIcon,
  ClockIcon
} from '@heroicons/vue/24/outline'

export default {
  name: 'Profile',
  components: {
    CalendarIcon,
    PhoneInput,
    UserIcon,
    TicketIcon,
    StarIcon,
    PhotoIcon,
    CheckIcon,
    CheckCircleIcon,
    ShieldCheckIcon,
    PencilIcon,
    ExclamationTriangleIcon,
    CogIcon,
    ClockIcon
  },
  setup() {
    const authStore = useAuthStore()
    const saving = ref(false)
    const showPasswordModal = ref(false)
    const showDeleteModal = ref(false)
    const loading = ref(false)
    const error = ref(null)
    const uploadingAvatar = ref(false)
    const successMessage = ref('')
    const emailVerified = ref(true)
    const resendingEmail = ref(false)
    
    // Variables pour les préférences
    const preferences = ref({
      emailNotifications: true,
      smsNotifications: true,
      language: 'fr'
    })
    
    // Variables pour la suppression
    const deleteForm = ref({
      password: '',
      confirmation: ''
    })

    const user = computed(() => authStore.user)
    const userInitial = computed(() => user.value?.name?.charAt(0).toUpperCase() || 'U')
    
    // Computed pour vérifier les champs disponibles - afficher toujours les champs pour permettre la saisie
    const hasPhoneField = computed(() => true)
    const hasBioField = computed(() => true)
    const hasCityField = computed(() => true)
    const hasCountryField = computed(() => true)
    const hasBirthdateField = computed(() => true)
    const hasLanguageField = computed(() => true)

    const profileForm = ref({
      name: '',
      email: '',
      phone: '',
      bio: '',
      city: '',
      country: 'GA',
      birthdate: '',
      language: 'fr',
      avatar: null
    })

    // Charger le profil depuis l'API
    const loadProfile = async () => {
      try {
        loading.value = true
        error.value = null
        const response = await clientService.getProfile()
        const profile = response.data.user || response.data
        
        console.log('Données du profil reçues:', profile)
        
        profileForm.value = {
          name: profile.name || '',
          email: profile.email || '',
          phone: profile.phone || '',
          bio: profile.bio || '',
          city: profile.city || '',
          country: profile.country || 'GA',
          birthdate: profile.birthdate || '',
          language: profile.language || 'fr',
          avatar: profile.avatar_url && !profile.avatar_url.includes('user-default.jpg') ? profile.avatar_url : null
        }
        
        // Charger les préférences depuis les métadonnées
        preferences.value = {
          emailNotifications: profile.email_notifications ?? true,
          smsNotifications: profile.sms_notifications ?? true,
          language: profile.language || 'fr'
        }
        
        // Vérifier le statut de vérification d'email
        emailVerified.value = !!profile.email_verified_at
      } catch (err) {
        console.error('Erreur lors du chargement du profil:', err)
        error.value = 'Impossible de charger votre profil'
        // Utiliser les données par défaut depuis auth store
        profileForm.value = {
          name: user.value?.name || '',
          email: user.value?.email || '',
          phone: '',
          bio: '',
          city: '',
          country: 'GA',
          birthdate: '',
          language: 'fr',
          avatar: null
        }
      } finally {
        loading.value = false
      }
    }

    // Charger les statistiques depuis l'API
    const loadStats = async () => {
      try {
        const response = await ticketApiService.getMyTickets()
        const orders = response.data.orders || []
        
        stats.value = {
          totalTickets: orders.reduce((sum, order) => sum + (order.tickets_count || 0), 0),
          eventsAttended: orders.filter(order => order.status === 'paid').length,
          loyaltyPoints: orders.length * 50 // Points fictifs basés sur le nombre d'achats
        }
      } catch (err) {
        console.error('Erreur lors du chargement des statistiques:', err)
        // Garder les valeurs par défaut
      }
    }

    // Charger les activités récentes depuis l'API
    const loadActivities = async () => {
      try {
        const response = await clientService.getRecentActivities()
        recentActivities.value = response.data.activities || []
      } catch (err) {
        console.error('Erreur lors du chargement des activités:', err)
        // Garder les valeurs par défaut
      }
    }

    // Charger les données au montage du composant
    onMounted(() => {
      loadProfile()
      loadStats()
      loadActivities()
    })

    const passwordForm = ref({
      currentPassword: '',
      newPassword: '',
      confirmPassword: ''
    })

    const stats = ref({
      totalTickets: 15,
      eventsAttended: 8,
      loyaltyPoints: 1250
    })

    const membershipDuration = computed(() => {
      if (!user.value?.created_at) return 'Récent'
      
      const memberSince = new Date(user.value.created_at)
      const now = new Date()
      const diffTime = Math.abs(now - memberSince)
      const diffMinutes = Math.floor(diffTime / (1000 * 60))
      const diffHours = Math.floor(diffTime / (1000 * 60 * 60))
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
      const months = Math.floor(diffDays / 30)
      
      if (diffMinutes < 60) {
        return `${diffMinutes} minute${diffMinutes > 1 ? 's' : ''}`
      } else if (diffHours < 24) {
        return `${diffHours} heure${diffHours > 1 ? 's' : ''}`
      } else if (diffDays < 30) {
        return `${diffDays} jour${diffDays > 1 ? 's' : ''}`
      } else if (months < 12) {
        return `${months} mois`
      } else {
        const years = Math.floor(months / 12)
        return `${years} an${years > 1 ? 's' : ''}`
      }
    })

    const recentActivities = ref([
      {
        id: 1,
        title: 'Ticket acheté pour "L\'OISEAU RARE"',
        date: 'Il y a 2 jours',
        icon: 'TicketIcon'
      },
      {
        id: 2,
        title: 'Profil mis à jour',
        date: 'Il y a 1 semaine',
        icon: 'UserIcon'
      },
      {
        id: 3,
        title: 'Événement "Jazz Night" fréquenté',
        date: 'Il y a 2 semaines',
        icon: 'StarIcon'
      }
    ])

    // Validation côté client
    const validateForm = () => {
      if (!profileForm.value.name?.trim()) {
        error.value = 'Le nom est requis'
        return false
      }
      
      if (!profileForm.value.email?.trim()) {
        error.value = 'L\'email est requis'
        return false
      }
      
      if (profileForm.value.bio && profileForm.value.bio.length > 500) {
        error.value = 'La biographie ne peut pas dépasser 500 caractères'
        return false
      }
      
      if (profileForm.value.city && profileForm.value.city.length > 100) {
        error.value = 'La ville ne peut pas dépasser 100 caractères'
        return false
      }
      
      if (profileForm.value.country && profileForm.value.country.length !== 2) {
        error.value = 'Le code pays doit faire 2 caractères'
        return false
      }
      
      return true
    }

    const updateProfile = async () => {
      error.value = null
      successMessage.value = ''
      
      // Validation côté client
      if (!validateForm()) {
        return
      }
      
      saving.value = true
      
      try {
        await clientService.updateProfile(profileForm.value)
        console.log('Profil mis à jour:', profileForm.value)
        successMessage.value = 'Profil mis à jour avec succès'
        
        // Mettre à jour les données dans le store auth si nécessaire
        if (authStore.updateUser) {
          authStore.updateUser({
            name: profileForm.value.name,
            email: profileForm.value.email
          })
        }
        
        // Effacer le message de succès après 3 secondes
        setTimeout(() => {
          successMessage.value = ''
        }, 3000)
      } catch (err) {
        console.error('Erreur lors de la mise à jour:', err)
        if (err.response?.data?.errors) {
          // Afficher la première erreur de validation du backend
          const firstError = Object.values(err.response.data.errors)[0]
          error.value = Array.isArray(firstError) ? firstError[0] : firstError
        } else if (err.response?.data?.message) {
          error.value = err.response.data.message
        } else {
          error.value = 'Impossible de mettre à jour votre profil'
        }
      } finally {
        saving.value = false
      }
    }

    const updatePassword = async () => {
      if (passwordForm.value.newPassword !== passwordForm.value.confirmPassword) {
        error.value = 'Les mots de passe ne correspondent pas'
        return
      }
      
      try {
        error.value = null
        const response = await clientService.updatePassword({
          current_password: passwordForm.value.currentPassword,
          new_password: passwordForm.value.newPassword,
          new_password_confirmation: passwordForm.value.confirmPassword
        })
        
        successMessage.value = response.data.message || 'Mot de passe mis à jour avec succès'
        showPasswordModal.value = false
        passwordForm.value = {
          currentPassword: '',
          newPassword: '',
          confirmPassword: ''
        }
        
        setTimeout(() => {
          successMessage.value = ''
        }, 3000)
      } catch (err) {
        console.error('Erreur lors de la mise à jour du mot de passe:', err)
        if (err.response?.data?.errors) {
          const firstError = Object.values(err.response.data.errors)[0]
          error.value = Array.isArray(firstError) ? firstError[0] : firstError
        } else if (err.response?.data?.message) {
          error.value = err.response.data.message
        } else {
          error.value = 'Erreur lors de la mise à jour du mot de passe'
        }
      }
    }

    const resetForm = () => {
      loadProfile() // Recharger les données depuis l'API
    }

    // Gérer l'upload d'avatar
    const handleAvatarUpload = async (event) => {
      const file = event.target.files[0]
      if (!file) return

      // Vérifier le type de fichier
      if (!file.type.startsWith('image/')) {
        alert('Veuillez sélectionner un fichier image')
        return
      }

      // Vérifier la taille (max 5MB)
      if (file.size > 5 * 1024 * 1024) {
        alert('La taille du fichier ne doit pas dépasser 5MB')
        return
      }

      try {
        uploadingAvatar.value = true
        
        // Créer FormData pour l'upload
        const formData = new FormData()
        formData.append('avatar', file)
        
        // Uploader via l'API
        const response = await clientService.uploadAvatar(formData)
        
        // Mettre à jour l'avatar dans le formulaire avec cache busting
        if (response.data.avatar_url) {
          // Ajouter un timestamp pour forcer le rafraîchissement
          const timestamp = new Date().getTime()
          profileForm.value.avatar = `${response.data.avatar_url}?t=${timestamp}`
        }
        
        successMessage.value = 'Avatar mis à jour avec succès'
        setTimeout(() => {
          successMessage.value = ''
        }, 3000)
      } catch (error) {
        console.error('Erreur lors de l\'upload de l\'avatar:', error)
        error.value = 'Erreur lors de l\'upload de l\'avatar'
      } finally {
        uploadingAvatar.value = false
        // Réinitialiser l'input file
        event.target.value = ''
      }
    }

    // Renvoyer l'email de vérification
    const resendEmailVerification = async () => {
      try {
        resendingEmail.value = true
        await authService.resendEmailVerification()
        
        successMessage.value = 'Email de vérification renvoyé avec succès'
        setTimeout(() => {
          successMessage.value = ''
        }, 3000)
      } catch (err) {
        console.error('Erreur lors du renvoi:', err)
        error.value = 'Impossible de renvoyer l\'email de vérification'
      } finally {
        resendingEmail.value = false
      }
    }

    // Mettre à jour les préférences
    const updatePreferences = async (type, value) => {
      try {
        const updateData = {}
        updateData[type] = value
        
        await clientService.updatePreferences(updateData)
        preferences.value[type] = value
        
        successMessage.value = 'Préférences mises à jour'
        setTimeout(() => {
          successMessage.value = ''
        }, 2000)
      } catch (err) {
        console.error('Erreur lors de la mise à jour des préférences:', err)
        error.value = 'Erreur lors de la mise à jour des préférences'
      }
    }

    // Supprimer le compte
    const deleteAccount = async () => {
      if (deleteForm.value.confirmation !== 'SUPPRIMER') {
        error.value = 'Vous devez taper "SUPPRIMER" pour confirmer'
        return
      }
      
      try {
        error.value = null
        await clientService.deleteAccount({
          password: deleteForm.value.password,
          confirmation: deleteForm.value.confirmation
        })
        
        // Déconnecter l'utilisateur et rediriger
        authStore.logout()
        window.location.href = '/'
      } catch (err) {
        console.error('Erreur lors de la suppression:', err)
        if (err.response?.data?.errors) {
          const firstError = Object.values(err.response.data.errors)[0]
          error.value = Array.isArray(firstError) ? firstError[0] : firstError
        } else if (err.response?.data?.message) {
          error.value = err.response.data.message
        } else {
          error.value = 'Erreur lors de la suppression du compte'
        }
      }
    }

    return {
      profileForm,
      passwordForm,
      stats,
      membershipDuration,
      recentActivities,
      saving,
      showPasswordModal,
      showDeleteModal,
      loading,
      error,
      successMessage,
      uploadingAvatar,
      userInitial,
      preferences,
      deleteForm,
      emailVerified,
      resendingEmail,
      resendEmailVerification,
      updateProfile,
      updatePassword,
      updatePreferences,
      deleteAccount,
      resetForm,
      loadProfile,
      handleAvatarUpload,
      hasPhoneField,
      hasBioField,
      hasCityField,
      hasCountryField,
      hasBirthdateField,
      hasLanguageField
    }
  }
}
</script>

<style scoped>
.my-profile {
  background-color: #f8fafc;
}

.font-primea {
  font-family: 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.text-primea-blue {
  color: #272d63;
}

.bg-primea-blue {
  background-color: #272d63;
}

.text-primea-yellow {
  color: #fab511;
}

.bg-primea-yellow {
  background-color: #fab511;
}

.hover\:bg-primea-yellow:hover {
  background-color: #fab511;
}

.hover\:text-primea-blue:hover {
  color: #272d63;
}

.hover\:bg-primea-blue:hover {
  background-color: #272d63;
}

.border-primea-blue {
  border-color: #272d63;
}

.rounded-primea {
  border-radius: 12px;
}

.rounded-primea-lg {
  border-radius: 16px;
}
</style>