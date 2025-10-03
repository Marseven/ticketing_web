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
              <p class="text-sm font-medium text-gray-500">Billets achetés</p>
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
                <div class="w-20 h-20 bg-primea-blue text-white rounded-full flex items-center justify-center text-2xl font-bold">
                  {{ userInitial }}
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-900 mb-1">Photo de profil</h4>
                  <p class="text-sm text-gray-500 mb-3">Ajoutez une photo pour personnaliser votre profil</p>
                  <button 
                    type="button"
                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-primea hover:bg-gray-50 transition-colors duration-200 flex items-center gap-2"
                  >
                    <PhotoIcon class="w-4 h-4" />
                    Changer la photo
                  </button>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Nom -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Nom complet</label>
                  <input 
                    v-model="profileForm.name"
                    type="text" 
                    class="w-full px-4 py-3 border border-gray-300 rounded-primea focus:ring-primea-blue focus:border-primea-blue"
                    placeholder="Votre nom complet"
                  />
                </div>

                <!-- Email -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Adresse email</label>
                  <input 
                    v-model="profileForm.email"
                    type="email" 
                    class="w-full px-4 py-3 border border-gray-300 rounded-primea focus:ring-primea-blue focus:border-primea-blue"
                    placeholder="votre@email.com"
                  />
                </div>

                <!-- Téléphone -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Numéro de téléphone</label>
                  <input 
                    v-model="profileForm.phone"
                    type="tel" 
                    class="w-full px-4 py-3 border border-gray-300 rounded-primea focus:ring-primea-blue focus:border-primea-blue"
                    placeholder="+241 XX XX XX XX"
                  />
                </div>

                <!-- Date de naissance -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Date de naissance</label>
                  <input 
                    v-model="profileForm.birthdate"
                    type="date" 
                    class="w-full px-4 py-3 border border-gray-300 rounded-primea focus:ring-primea-blue focus:border-primea-blue"
                  />
                </div>

                <!-- Ville -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Ville</label>
                  <input 
                    v-model="profileForm.city"
                    type="text" 
                    class="w-full px-4 py-3 border border-gray-300 rounded-primea focus:ring-primea-blue focus:border-primea-blue"
                    placeholder="Libreville"
                  />
                </div>

                <!-- Pays -->
                <div>
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
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Biographie</label>
                <textarea 
                  v-model="profileForm.bio"
                  rows="4"
                  class="w-full px-4 py-3 border border-gray-300 rounded-primea focus:ring-primea-blue focus:border-primea-blue"
                  placeholder="Parlez-nous de vous..."
                ></textarea>
                <p class="text-sm text-gray-500 mt-1">{{ profileForm.bio.length }}/500 caractères</p>
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
                <button class="w-full px-4 py-2 text-left text-red-600 hover:bg-red-50 rounded-primea transition-colors duration-200 flex items-center gap-2">
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
                  class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-primea-blue transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primea-blue focus:ring-offset-2"
                >
                  <span class="translate-x-5 pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                </button>
              </div>

              <div class="flex items-center justify-between">
                <div>
                  <p class="font-medium text-gray-900">Notifications SMS</p>
                  <p class="text-sm text-gray-500">Rappels et confirmations</p>
                </div>
                <button 
                  class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-primea-blue transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primea-blue focus:ring-offset-2"
                >
                  <span class="translate-x-5 pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                </button>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Langue préférée</label>
                <select 
                  v-model="profileForm.language"
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
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import CalendarIcon from '../../components/icons/CalendarIcon.vue'
import { clientService, ticketApiService } from '../../services/api.js'
import { 
  UserIcon,
  TicketIcon,
  StarIcon,
  PhotoIcon,
  CheckIcon,
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
    UserIcon,
    TicketIcon,
    StarIcon,
    PhotoIcon,
    CheckIcon,
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
    const loading = ref(false)
    const error = ref(null)

    const user = computed(() => authStore.user)
    const userInitial = computed(() => user.value?.name?.charAt(0).toUpperCase() || 'U')

    const profileForm = ref({
      name: '',
      email: '',
      phone: '',
      birthdate: '',
      city: '',
      country: 'GA',
      bio: '',
      language: 'fr'
    })

    // Charger le profil depuis l'API
    const loadProfile = async () => {
      try {
        loading.value = true
        error.value = null
        const response = await clientService.getProfile()
        const profile = response.data.user || response.data
        
        profileForm.value = {
          name: profile.name || '',
          email: profile.email || '',
          phone: profile.phone || '',
          birthdate: profile.birthdate || '',
          city: profile.city || '',
          country: profile.country || 'GA',
          bio: profile.bio || '',
          language: profile.language || 'fr'
        }
      } catch (err) {
        console.error('Erreur lors du chargement du profil:', err)
        error.value = 'Impossible de charger votre profil'
        // Utiliser les données par défaut depuis auth store
        profileForm.value = {
          name: user.value?.name || '',
          email: user.value?.email || '',
          phone: '',
          birthdate: '',
          city: '',
          country: 'GA',
          bio: '',
          language: 'fr'
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

    // Charger les données au montage du composant
    onMounted(() => {
      loadProfile()
      loadStats()
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
      const memberSince = new Date('2023-03-15')
      const now = new Date()
      const diffTime = Math.abs(now - memberSince)
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
      const months = Math.floor(diffDays / 30)
      return months > 12 ? `${Math.floor(months / 12)} an${Math.floor(months / 12) > 1 ? 's' : ''}` : `${months} mois`
    })

    const recentActivities = ref([
      {
        id: 1,
        title: 'Billet acheté pour "L\'OISEAU RARE"',
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

    const updateProfile = async () => {
      saving.value = true
      try {
        await clientService.updateProfile(profileForm.value)
        console.log('Profil mis à jour:', profileForm.value)
        
        // Mettre à jour les données dans le store auth si nécessaire
        if (authStore.updateUser) {
          authStore.updateUser({
            name: profileForm.value.name,
            email: profileForm.value.email
          })
        }
      } catch (error) {
        console.error('Erreur lors de la mise à jour:', error)
        error.value = 'Impossible de mettre à jour votre profil'
      } finally {
        saving.value = false
      }
    }

    const updatePassword = async () => {
      if (passwordForm.value.newPassword !== passwordForm.value.confirmPassword) {
        alert('Les mots de passe ne correspondent pas')
        return
      }
      
      try {
        // Simuler la mise à jour du mot de passe
        await new Promise(resolve => setTimeout(resolve, 1000))
        console.log('Mot de passe mis à jour')
        showPasswordModal.value = false
        passwordForm.value = {
          currentPassword: '',
          newPassword: '',
          confirmPassword: ''
        }
      } catch (error) {
        console.error('Erreur lors de la mise à jour du mot de passe:', error)
      }
    }

    const resetForm = () => {
      loadProfile() // Recharger les données depuis l'API
    }

    return {
      profileForm,
      passwordForm,
      stats,
      membershipDuration,
      recentActivities,
      saving,
      showPasswordModal,
      loading,
      error,
      userInitial,
      updateProfile,
      updatePassword,
      resetForm,
      loadProfile
    }
  }
}
</script>

<style scoped>
.my-profile {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
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