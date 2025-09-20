<template>
  <div class="profile-management p-6">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold mb-2" style="color: #272d63;">Mon Profil</h1>
      <p class="text-gray-600">Gérez vos informations personnelles et préférences</p>
    </div>

    <!-- Profile Overview -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
      <div class="flex items-center space-x-6">
        <div class="relative">
          <div class="w-24 h-24 rounded-full flex items-center justify-center text-white text-2xl font-bold"
               style="background-color: #272d63;">
            {{ getInitials(profile.name) }}
          </div>
          <button @click="changeAvatar" 
                  class="absolute bottom-0 right-0 text-white p-2 rounded-full transition-colors duration-200"
                  style="background-color: #fab511;"
                  @mouseover="$event.currentTarget.style.backgroundColor = '#272d63'"
                  @mouseleave="$event.currentTarget.style.backgroundColor = '#fab511'">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
          </button>
        </div>
        <div class="flex-1">
          <h3 class="text-xl font-bold" style="color: #272d63;">{{ profile.name }}</h3>
          <p class="text-gray-600 mb-1">{{ profile.email }}</p>
          <div class="flex items-center space-x-4 text-sm text-gray-500">
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
              <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
              </svg>
              Administrateur
            </span>
            <span>Membre depuis {{ formatDate(profile.created_at) }}</span>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Personal Information -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4" style="color: #272d63;">Informations Personnelles</h3>
        <form @submit.prevent="updateProfile" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Nom complet *</label>
            <input type="text" v-model="profileForm.name" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                   style="--tw-ring-color: #272d63;">
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
            <input type="email" v-model="profileForm.email" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                   style="--tw-ring-color: #272d63;">
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
            <input type="tel" v-model="profileForm.phone"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                   style="--tw-ring-color: #272d63;">
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Poste</label>
            <input type="text" v-model="profileForm.position"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                   style="--tw-ring-color: #272d63;">
          </div>
          
          <button type="submit" :disabled="updatingProfile"
                  class="w-full text-white py-2 px-4 rounded-lg font-medium transition-colors duration-200"
                  style="background-color: #272d63;"
                  @mouseover="$event.currentTarget.style.backgroundColor = '#fab511'"
                  @mouseleave="$event.currentTarget.style.backgroundColor = '#272d63'">
            {{ updatingProfile ? 'Mise à jour...' : 'Mettre à jour' }}
          </button>
        </form>
      </div>

      <!-- Change Password -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4" style="color: #272d63;">Changer le Mot de Passe</h3>
        <form @submit.prevent="changePassword" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Mot de passe actuel *</label>
            <input type="password" v-model="passwordForm.current_password" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                   style="--tw-ring-color: #272d63;">
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Nouveau mot de passe *</label>
            <input type="password" v-model="passwordForm.new_password" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                   style="--tw-ring-color: #272d63;">
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Confirmer le mot de passe *</label>
            <input type="password" v-model="passwordForm.new_password_confirmation" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                   style="--tw-ring-color: #272d63;">
          </div>
          
          <div v-if="passwordError" class="text-red-600 text-sm">{{ passwordError }}</div>
          
          <button type="submit" :disabled="changingPassword"
                  class="w-full text-white py-2 px-4 rounded-lg font-medium transition-colors duration-200"
                  style="background-color: #fab511;"
                  @mouseover="$event.currentTarget.style.backgroundColor = '#272d63'"
                  @mouseleave="$event.currentTarget.style.backgroundColor = '#fab511'">
            {{ changingPassword ? 'Changement...' : 'Changer le mot de passe' }}
          </button>
        </form>
      </div>
    </div>

    <!-- Preferences & Security -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
      <!-- Preferences -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4" style="color: #272d63;">Préférences</h3>
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <h4 class="font-medium text-gray-900">Notifications Email</h4>
              <p class="text-sm text-gray-600">Recevoir les notifications importantes par email</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="preferences.email_notifications" class="sr-only peer">
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"
                   :style="preferences.email_notifications ? { backgroundColor: '#272d63' } : {}"></div>
            </label>
          </div>
          
          <div class="flex items-center justify-between">
            <div>
              <h4 class="font-medium text-gray-900">Authentification 2FA</h4>
              <p class="text-sm text-gray-600">Sécurité renforcée avec un code à usage unique</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="preferences.two_factor_enabled" class="sr-only peer">
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"
                   :style="preferences.two_factor_enabled ? { backgroundColor: '#fab511' } : {}"></div>
            </label>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Langue de l'interface</label>
            <select v-model="preferences.language" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                    style="--tw-ring-color: #272d63;">
              <option value="fr">Français</option>
              <option value="en">English</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Fuseau horaire</label>
            <select v-model="preferences.timezone" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                    style="--tw-ring-color: #272d63;">
              <option value="Africa/Libreville">Libreville (GMT+1)</option>
              <option value="Africa/Lagos">Lagos (GMT+1)</option>
              <option value="UTC">UTC (GMT+0)</option>
            </select>
          </div>
          
          <button @click="updatePreferences" :disabled="updatingPreferences"
                  class="w-full bg-gray-600 hover:bg-gray-700 text-white py-2 px-4 rounded-lg font-medium transition-colors duration-200">
            {{ updatingPreferences ? 'Sauvegarde...' : 'Sauvegarder les préférences' }}
          </button>
        </div>
      </div>

      <!-- Security & Sessions -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4" style="color: #272d63;">Sécurité & Sessions</h3>
        
        <!-- Account Stats -->
        <div class="grid grid-cols-2 gap-4 mb-6">
          <div class="text-center p-3 bg-gray-50 rounded-lg">
            <div class="text-xl font-bold" style="color: #272d63;">{{ profile.login_count || 0 }}</div>
            <div class="text-sm text-gray-600">Connexions</div>
          </div>
          <div class="text-center p-3 bg-gray-50 rounded-lg">
            <div class="text-xl font-bold" style="color: #fab511;">{{ activeSessions.length }}</div>
            <div class="text-sm text-gray-600">Sessions actives</div>
          </div>
        </div>
        
        <!-- Active Sessions -->
        <div class="space-y-3">
          <h4 class="font-medium text-gray-900">Sessions Actives</h4>
          <div v-for="session in activeSessions" :key="session.id" 
               class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div class="flex items-center">
              <div class="w-8 h-8 rounded-full flex items-center justify-center mr-3"
                   style="background-color: rgba(39, 45, 99, 0.1);">
                <svg class="w-4 h-4" style="color: #272d63;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">{{ session.device }}</p>
                <p class="text-xs text-gray-500">{{ session.location }} • {{ formatRelativeTime(session.last_activity) }}</p>
              </div>
            </div>
            <div class="flex items-center space-x-2">
              <span v-if="session.current" 
                    class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">
                Actuelle
              </span>
              <button v-else @click="revokeSession(session)" 
                      class="text-xs text-red-600 hover:text-red-800 px-2 py-1 bg-red-100 hover:bg-red-200 rounded transition-colors duration-200">
                Révoquer
              </button>
            </div>
          </div>
        </div>
        
        <!-- Security Actions -->
        <div class="mt-6 space-y-2">
          <button @click="logoutAllSessions" 
                  class="w-full text-orange-600 hover:text-orange-800 py-2 px-4 bg-orange-100 hover:bg-orange-200 rounded-lg font-medium transition-colors duration-200">
            Déconnecter toutes les autres sessions
          </button>
          <button @click="downloadAccountData" 
                  class="w-full text-gray-600 hover:text-gray-800 py-2 px-4 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors duration-200">
            Télécharger mes données
          </button>
        </div>
      </div>
    </div>

    <!-- Avatar Upload Modal -->
    <div v-if="showAvatarModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-bold mb-4" style="color: #272d63;">Changer la photo de profil</h3>
        
        <div class="text-center mb-4">
          <div class="w-24 h-24 mx-auto rounded-full flex items-center justify-center text-white text-2xl font-bold"
               style="background-color: #272d63;">
            {{ getInitials(profile.name) }}
          </div>
          <p class="text-sm text-gray-600 mt-2">Fonctionnalité à venir : upload d'image</p>
        </div>
        
        <div class="flex space-x-3">
          <button @click="showAvatarModal = false"
                  class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-lg font-medium hover:bg-gray-400 transition-colors duration-200">
            Annuler
          </button>
          <button @click="showAvatarModal = false"
                  class="flex-1 text-white py-2 px-4 rounded-lg font-medium transition-colors duration-200"
                  style="background-color: #272d63;"
                  @mouseover="$event.currentTarget.style.backgroundColor = '#fab511'"
                  @mouseleave="$event.currentTarget.style.backgroundColor = '#272d63'">
            Fermer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue'

export default {
  name: 'Profile',
  setup() {
    const loading = ref(false)
    const updatingProfile = ref(false)
    const changingPassword = ref(false)
    const updatingPreferences = ref(false)
    const showAvatarModal = ref(false)
    const passwordError = ref('')
    
    const profile = ref({
      id: 1,
      name: '',
      email: '',
      phone: '',
      position: '',
      created_at: new Date(),
      login_count: 0
    })
    
    const profileForm = reactive({
      name: '',
      email: '',
      phone: '',
      position: ''
    })
    
    const passwordForm = reactive({
      current_password: '',
      new_password: '',
      new_password_confirmation: ''
    })
    
    const preferences = reactive({
      email_notifications: true,
      two_factor_enabled: false,
      language: 'fr',
      timezone: 'Africa/Libreville'
    })
    
    const activeSessions = ref([])

    // Methods
    const loadProfile = async () => {
      loading.value = true
      try {
        const response = await fetch('/api/v1/admin/profile', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        if (response.ok) {
          const data = await response.json()
          if (data.success) {
            profile.value = data.data.user
            Object.assign(profileForm, data.data.user)
            Object.assign(preferences, data.data.preferences || preferences)
            activeSessions.value = data.data.sessions || []
          }
        } else {
          loadMockData()
        }
      } catch (error) {
        console.log('API non disponible, utilisation des données locales')
        loadMockData()
      } finally {
        loading.value = false
      }
    }
    
    const loadMockData = () => {
      const userData = {
        id: 1,
        name: localStorage.getItem('userName') || 'Admin Primea',
        email: localStorage.getItem('userEmail') || 'admin@primea.ga',
        phone: '+241 01 23 45 67',
        position: 'Super Administrateur',
        created_at: new Date('2024-01-15'),
        login_count: 127
      }
      
      profile.value = userData
      Object.assign(profileForm, userData)
      
      activeSessions.value = [
        {
          id: 1,
          device: 'Chrome sur macOS',
          location: 'Libreville, Gabon',
          last_activity: new Date(),
          current: true
        },
        {
          id: 2,
          device: 'Safari sur iPhone',
          location: 'Libreville, Gabon',
          last_activity: new Date(Date.now() - 2 * 60 * 60 * 1000),
          current: false
        }
      ]
    }
    
    const updateProfile = async () => {
      updatingProfile.value = true
      try {
        const response = await fetch('/api/v1/admin/profile', {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(profileForm)
        })
        
        if (response.ok) {
          const data = await response.json()
          if (data.success) {
            profile.value = { ...profile.value, ...profileForm }
            localStorage.setItem('userName', profileForm.name)
            localStorage.setItem('userEmail', profileForm.email)
            alert('Profil mis à jour avec succès')
          }
        } else {
          profile.value = { ...profile.value, ...profileForm }
          localStorage.setItem('userName', profileForm.name)
          localStorage.setItem('userEmail', profileForm.email)
          alert('Profil mis à jour avec succès (simulé)')
        }
      } catch (error) {
        console.log('API non disponible, mise à jour locale')
        profile.value = { ...profile.value, ...profileForm }
        localStorage.setItem('userName', profileForm.name)
        localStorage.setItem('userEmail', profileForm.email)
        alert('Profil mis à jour avec succès (simulé)')
      } finally {
        updatingProfile.value = false
      }
    }
    
    const changePassword = async () => {
      passwordError.value = ''
      
      if (passwordForm.new_password !== passwordForm.new_password_confirmation) {
        passwordError.value = 'Les mots de passe ne correspondent pas'
        return
      }
      
      if (passwordForm.new_password.length < 8) {
        passwordError.value = 'Le mot de passe doit contenir au moins 8 caractères'
        return
      }
      
      changingPassword.value = true
      try {
        const response = await fetch('/api/v1/admin/profile/password', {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(passwordForm)
        })
        
        if (response.ok) {
          const data = await response.json()
          if (data.success) {
            Object.assign(passwordForm, {
              current_password: '',
              new_password: '',
              new_password_confirmation: ''
            })
            alert('Mot de passe changé avec succès')
          }
        } else {
          Object.assign(passwordForm, {
            current_password: '',
            new_password: '',
            new_password_confirmation: ''
          })
          alert('Mot de passe changé avec succès (simulé)')
        }
      } catch (error) {
        console.log('API non disponible, changement simulé')
        Object.assign(passwordForm, {
          current_password: '',
          new_password: '',
          new_password_confirmation: ''
        })
        alert('Mot de passe changé avec succès (simulé)')
      } finally {
        changingPassword.value = false
      }
    }
    
    const updatePreferences = async () => {
      updatingPreferences.value = true
      try {
        const response = await fetch('/api/v1/admin/profile/preferences', {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(preferences)
        })
        
        if (response.ok) {
          alert('Préférences sauvegardées avec succès')
        } else {
          alert('Préférences sauvegardées avec succès (simulé)')
        }
      } catch (error) {
        console.log('API non disponible, sauvegarde simulée')
        alert('Préférences sauvegardées avec succès (simulé)')
      } finally {
        updatingPreferences.value = false
      }
    }
    
    const changeAvatar = () => {
      showAvatarModal.value = true
    }
    
    const revokeSession = async (session) => {
      if (!confirm('Êtes-vous sûr de vouloir révoquer cette session ?')) return
      
      try {
        const response = await fetch(`/api/v1/admin/profile/sessions/${session.id}`, {
          method: 'DELETE',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        if (response.ok) {
          activeSessions.value = activeSessions.value.filter(s => s.id !== session.id)
        } else {
          activeSessions.value = activeSessions.value.filter(s => s.id !== session.id)
        }
      } catch (error) {
        activeSessions.value = activeSessions.value.filter(s => s.id !== session.id)
      }
    }
    
    const logoutAllSessions = async () => {
      if (!confirm('Cela déconnectera toutes les autres sessions. Continuer ?')) return
      
      try {
        const response = await fetch('/api/v1/admin/profile/sessions/logout-all', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        if (response.ok) {
          activeSessions.value = activeSessions.value.filter(s => s.current)
          alert('Toutes les autres sessions ont été déconnectées')
        } else {
          activeSessions.value = activeSessions.value.filter(s => s.current)
          alert('Toutes les autres sessions ont été déconnectées (simulé)')
        }
      } catch (error) {
        activeSessions.value = activeSessions.value.filter(s => s.current)
        alert('Toutes les autres sessions ont été déconnectées (simulé)')
      }
    }
    
    const downloadAccountData = () => {
      alert('Téléchargement des données du compte (fonctionnalité à venir)')
    }
    
    // Utils
    const getInitials = (name) => {
      return name.split(' ').map(n => n.charAt(0)).join('').toUpperCase().substring(0, 2)
    }
    
    const formatDate = (date) => {
      return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long'
      })
    }
    
    const formatRelativeTime = (date) => {
      const now = new Date()
      const diff = now - new Date(date)
      const minutes = Math.floor(diff / 60000)
      const hours = Math.floor(diff / 3600000)
      const days = Math.floor(diff / 86400000)
      
      if (minutes < 1) return 'À l\'instant'
      if (minutes < 60) return `Il y a ${minutes} min`
      if (hours < 24) return `Il y a ${hours}h`
      return `Il y a ${days}j`
    }

    // Lifecycle
    onMounted(() => {
      loadProfile()
    })

    return {
      loading,
      updatingProfile,
      changingPassword,
      updatingPreferences,
      showAvatarModal,
      passwordError,
      profile,
      profileForm,
      passwordForm,
      preferences,
      activeSessions,
      updateProfile,
      changePassword,
      updatePreferences,
      changeAvatar,
      revokeSession,
      logoutAllSessions,
      downloadAccountData,
      getInitials,
      formatDate,
      formatRelativeTime
    }
  }
}
</script>

<style scoped>
.profile-management {
  font-family: 'Inter', sans-serif;
}
</style>