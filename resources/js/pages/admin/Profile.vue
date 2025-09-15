<template>
  <div class="admin-profile min-h-screen bg-gray-100 font-primea">
    <!-- Sidebar simplifié -->
    <div class="fixed inset-y-0 left-0 w-64 bg-primea-blue text-white z-30" :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }">
      <div class="flex items-center justify-between p-4 border-b border-primea-blue-dark">
        <div class="flex items-center space-x-3">
          <img src="/images/logo_white.png" alt="Primea" class="h-8" />
          <span class="font-bold text-lg font-primea">Administration</span>
        </div>
      </div>
      <nav class="mt-6">
        <div class="px-4">
          <router-link to="/admin/dashboard" class="flex items-center px-4 py-3 text-blue-200 hover:bg-primea-yellow hover:text-primea-blue rounded-primea-lg mb-2 transition-colors font-primea">
            <HomeIcon class="w-5 h-5 mr-3" />Tableau de bord</router-link>
          <router-link to="/admin/users" class="flex items-center px-4 py-3 text-blue-200 hover:bg-primea-yellow hover:text-primea-blue rounded-primea-lg mb-2 transition-colors font-primea">
            <UsersIcon class="w-5 h-5 mr-3" />Utilisateurs</router-link>
          <router-link to="/admin/events" class="flex items-center px-4 py-3 text-blue-200 hover:bg-primea-yellow hover:text-primea-blue rounded-primea-lg mb-2 transition-colors font-primea">
            <CalendarIcon class="w-5 h-5 mr-3" />Événements</router-link>
          <router-link to="/admin/transactions" class="flex items-center px-4 py-3 text-blue-200 hover:bg-primea-yellow hover:text-primea-blue rounded-primea-lg mb-2 transition-colors font-primea">
            <CreditCardIcon class="w-5 h-5 mr-3" />Transactions</router-link>
          <router-link to="/admin/reports" class="flex items-center px-4 py-3 text-blue-200 hover:bg-primea-yellow hover:text-primea-blue rounded-primea-lg mb-2 transition-colors font-primea">
            <ChartBarIcon class="w-5 h-5 mr-3" />Rapports</router-link>
          <router-link to="/admin/settings" class="flex items-center px-4 py-3 text-blue-200 hover:bg-primea-yellow hover:text-primea-blue rounded-primea-lg mb-2 transition-colors font-primea">
            <CogIcon class="w-5 h-5 mr-3" />Paramètres</router-link>
        </div>
      </nav>
    </div>

    <!-- Contenu principal -->
    <div class="lg:ml-64 transition-all duration-300">
      <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="px-6 py-4">
          <h1 class="text-2xl font-bold text-primea-blue font-primea">Mon profil administrateur</h1>
        </div>
      </header>

      <main class="p-6">
        <div class="max-w-4xl mx-auto space-y-6">
          
          <!-- Photo de profil -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center space-x-6">
              <div class="relative">
                <img :src="profile.avatar" alt="Avatar" class="w-20 h-20 rounded-full object-cover" />
                <button @click="changeAvatar" class="absolute bottom-0 right-0 bg-primea-blue text-white p-2 rounded-full hover:bg-primea-yellow hover:text-primea-blue transition-all">
                  <CameraIcon class="w-4 h-4" />
                </button>
              </div>
              <div>
                <h3 class="text-lg font-semibold text-primea-blue">{{ profile.name }}</h3>
                <p class="text-gray-600">{{ profile.role }}</p>
                <p class="text-sm text-gray-500">Membre depuis {{ formatDate(profile.created_at) }}</p>
              </div>
            </div>
          </div>

          <!-- Informations personnelles -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-primea-blue mb-4">Informations personnelles</h3>
            <form @submit.prevent="updateProfile" class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Prénom</label>
                  <input v-model="profile.first_name" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primea-blue focus:border-primea-blue" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Nom</label>
                  <input v-model="profile.last_name" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primea-blue focus:border-primea-blue" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                  <input v-model="profile.email" type="email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primea-blue focus:border-primea-blue" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                  <input v-model="profile.phone" type="tel" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primea-blue focus:border-primea-blue" />
                </div>
              </div>
              <div class="flex justify-end">
                <button type="submit" class="px-6 py-2 bg-primea-blue text-white rounded-lg hover:bg-primea-yellow hover:text-primea-blue transition-all">
                  Mettre à jour
                </button>
              </div>
            </form>
          </div>

          <!-- Changer mot de passe -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-primea-blue mb-4">Changer le mot de passe</h3>
            <form @submit.prevent="changePassword" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Mot de passe actuel</label>
                <input v-model="passwordForm.current" type="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primea-blue focus:border-primea-blue" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nouveau mot de passe</label>
                <input v-model="passwordForm.new" type="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primea-blue focus:border-primea-blue" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Confirmer le nouveau mot de passe</label>
                <input v-model="passwordForm.confirm" type="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primea-blue focus:border-primea-blue" />
              </div>
              <div class="flex justify-end">
                <button type="submit" class="px-6 py-2 bg-primea-blue text-white rounded-lg hover:bg-primea-yellow hover:text-primea-blue transition-all">
                  Changer le mot de passe
                </button>
              </div>
            </form>
          </div>

          <!-- Préférences -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-primea-blue mb-4">Préférences</h3>
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <div>
                  <h4 class="font-medium">Notifications email</h4>
                  <p class="text-sm text-gray-600">Recevoir les notifications par email</p>
                </div>
                <label class="flex items-center">
                  <input v-model="profile.preferences.email_notifications" type="checkbox" class="form-checkbox h-5 w-5 text-primea-blue" />
                </label>
              </div>
              
              <div class="flex items-center justify-between">
                <div>
                  <h4 class="font-medium">Authentification à deux facteurs</h4>
                  <p class="text-sm text-gray-600">Sécurité renforcée pour votre compte</p>
                </div>
                <label class="flex items-center">
                  <input v-model="profile.preferences.two_factor" type="checkbox" class="form-checkbox h-5 w-5 text-primea-blue" />
                </label>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Langue</label>
                <select v-model="profile.preferences.language" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primea-blue focus:border-primea-blue">
                  <option value="fr">Français</option>
                  <option value="en">English</option>
                </select>
              </div>

              <div class="flex justify-end">
                <button @click="updatePreferences" class="px-6 py-2 bg-primea-blue text-white rounded-lg hover:bg-primea-yellow hover:text-primea-blue transition-all">
                  Sauvegarder les préférences
                </button>
              </div>
            </div>
          </div>

          <!-- Sessions actives -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-primea-blue mb-4">Sessions actives</h3>
            <div class="space-y-3">
              <div v-for="session in activeSessions" :key="session.id" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex items-center">
                  <div class="bg-primea-blue p-2 rounded-full mr-3">
                    <ComputerDesktopIcon class="w-4 h-4 text-white" />
                  </div>
                  <div>
                    <p class="font-medium">{{ session.device }}</p>
                    <p class="text-sm text-gray-600">{{ session.location }} - {{ formatRelativeTime(session.last_activity) }}</p>
                  </div>
                </div>
                <div class="flex items-center space-x-2">
                  <span v-if="session.current" class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">Session actuelle</span>
                  <button v-else @click="revokeSession(session)" class="text-red-600 hover:text-red-800 text-sm">Révoquer</button>
                </div>
              </div>
            </div>
          </div>

        </div>
      </main>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue'
import { 
  HomeIcon,
  UsersIcon,
  CreditCardIcon,
  ChartBarIcon,
  CogIcon,
  CameraIcon,
  ComputerDesktopIcon
} from '@heroicons/vue/24/outline'
import CalendarIcon from '@/components/icons/CalendarIcon.vue'

export default {
  name: 'AdminProfile',
  components: {
    HomeIcon,
    UsersIcon,
    CalendarIcon,
    CreditCardIcon,
    ChartBarIcon,
    CogIcon,
    CameraIcon,
    ComputerDesktopIcon
  },
  setup() {
    const sidebarOpen = ref(window.innerWidth >= 1024)
    
    const profile = ref({
      name: 'Admin Primea',
      first_name: 'Admin',
      last_name: 'Primea',
      email: 'admin@primea.ga',
      phone: '+241 07 12 34 56 78',
      role: 'Super Administrateur',
      avatar: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=80&h=80&fit=crop&crop=face&auto=format',
      created_at: new Date('2023-01-15'),
      preferences: {
        email_notifications: true,
        two_factor: false,
        language: 'fr'
      }
    })

    const passwordForm = ref({
      current: '',
      new: '',
      confirm: ''
    })

    const activeSessions = ref([
      {
        id: 1,
        device: 'Chrome sur Windows',
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
    ])

    const formatDate = (date) => {
      return date.toLocaleDateString('fr-FR', { year: 'numeric', month: 'long' })
    }

    const formatRelativeTime = (date) => {
      const now = new Date()
      const diffInMinutes = Math.floor((now - date) / (1000 * 60))
      const diffInHours = Math.floor(diffInMinutes / 60)
      
      if (diffInMinutes < 5) return 'À l\'instant'
      if (diffInMinutes < 60) return `Il y a ${diffInMinutes} minutes`
      if (diffInHours < 24) return `Il y a ${diffInHours} heures`
      return date.toLocaleDateString('fr-FR')
    }

    const changeAvatar = () => {
      console.log('Change avatar')
      // Logique de changement d'avatar
    }

    const updateProfile = () => {
      console.log('Update profile:', profile.value)
    }

    const changePassword = () => {
      if (passwordForm.value.new !== passwordForm.value.confirm) {
        alert('Les mots de passe ne correspondent pas')
        return
      }
      console.log('Change password')
      passwordForm.value = { current: '', new: '', confirm: '' }
    }

    const updatePreferences = () => {
      console.log('Update preferences:', profile.value.preferences)
    }

    const revokeSession = (session) => {
      console.log('Revoke session:', session)
      activeSessions.value = activeSessions.value.filter(s => s.id !== session.id)
    }

    return {
      sidebarOpen,
      profile,
      passwordForm,
      activeSessions,
      formatDate,
      formatRelativeTime,
      changeAvatar,
      updateProfile,
      changePassword,
      updatePreferences,
      revokeSession
    }
  }
}
</script>

<style scoped>
.admin-profile {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}
</style>