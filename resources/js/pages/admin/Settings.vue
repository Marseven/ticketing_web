<template>
  <div class="admin-settings min-h-screen bg-gray-100 font-primea">
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
          <router-link to="/admin/settings" class="flex items-center px-4 py-3 text-white bg-primea-yellow text-primea-blue rounded-primea-lg mb-2 font-primea font-semibold">
            <CogIcon class="w-5 h-5 mr-3" />Paramètres</router-link>
        </div>
      </nav>
    </div>

    <!-- Contenu principal -->
    <div class="lg:ml-64 transition-all duration-300">
      <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="px-6 py-4">
          <h1 class="text-2xl font-bold text-primea-blue font-primea">Paramètres système</h1>
        </div>
      </header>

      <main class="p-6">
        <div class="max-w-4xl mx-auto space-y-6">
          
          <!-- Configuration générale -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-primea-blue mb-4">Configuration générale</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nom de la plateforme</label>
                <input v-model="settings.platformName" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primea-blue focus:border-primea-blue" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">URL du site</label>
                <input v-model="settings.siteUrl" type="url" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primea-blue focus:border-primea-blue" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email de contact</label>
                <input v-model="settings.contactEmail" type="email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primea-blue focus:border-primea-blue" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Fuseau horaire</label>
                <select v-model="settings.timezone" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primea-blue focus:border-primea-blue">
                  <option value="Africa/Libreville">Africa/Libreville (GMT+0)</option>
                  <option value="Europe/Paris">Europe/Paris (GMT+1)</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Moyens de paiement -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-primea-blue mb-4">Moyens de paiement</h3>
            <div class="space-y-4">
              <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                <div class="flex items-center">
                  <img src="/images/am.png" alt="Airtel Money" class="h-8 w-12 mr-3" />
                  <div>
                    <h4 class="font-medium">Airtel Money</h4>
                    <p class="text-sm text-gray-600">Paiements mobile Airtel</p>
                  </div>
                </div>
                <label class="flex items-center">
                  <input v-model="settings.paymentMethods.airtel" type="checkbox" class="form-checkbox h-5 w-5 text-primea-blue" />
                  <span class="ml-2 text-sm">Actif</span>
                </label>
              </div>

              <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                <div class="flex items-center">
                  <img src="/images/mm.png" alt="Moov Money" class="h-8 w-12 mr-3" />
                  <div>
                    <h4 class="font-medium">Moov Money</h4>
                    <p class="text-sm text-gray-600">Paiements mobile Moov</p>
                  </div>
                </div>
                <label class="flex items-center">
                  <input v-model="settings.paymentMethods.moov" type="checkbox" class="form-checkbox h-5 w-5 text-primea-blue" />
                  <span class="ml-2 text-sm">Actif</span>
                </label>
              </div>

              <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                <div class="flex items-center">
                  <img src="/images/vm.png" alt="Visa" class="h-8 w-12 mr-3" />
                  <div>
                    <h4 class="font-medium">Cartes bancaires</h4>
                    <p class="text-sm text-gray-600">Visa, Mastercard</p>
                  </div>
                </div>
                <label class="flex items-center">
                  <input v-model="settings.paymentMethods.cards" type="checkbox" class="form-checkbox h-5 w-5 text-primea-blue" />
                  <span class="ml-2 text-sm">Actif</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Notifications -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-primea-blue mb-4">Notifications</h3>
            <div class="space-y-4">
              <label class="flex items-center">
                <input v-model="settings.notifications.email" type="checkbox" class="form-checkbox h-5 w-5 text-primea-blue" />
                <span class="ml-3">Notifications par email</span>
              </label>
              <label class="flex items-center">
                <input v-model="settings.notifications.sms" type="checkbox" class="form-checkbox h-5 w-5 text-primea-blue" />
                <span class="ml-3">Notifications par SMS</span>
              </label>
              <label class="flex items-center">
                <input v-model="settings.notifications.push" type="checkbox" class="form-checkbox h-5 w-5 text-primea-blue" />
                <span class="ml-3">Notifications push</span>
              </label>
            </div>
          </div>

          <!-- Maintenance -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-primea-blue mb-4">Mode maintenance</h3>
            <div class="flex items-center justify-between">
              <div>
                <h4 class="font-medium">Activer le mode maintenance</h4>
                <p class="text-sm text-gray-600">Le site sera temporairement indisponible pour les utilisateurs</p>
              </div>
              <label class="flex items-center">
                <input v-model="settings.maintenanceMode" type="checkbox" class="form-checkbox h-5 w-5 text-primea-blue" />
                <span class="ml-2 text-sm">Activé</span>
              </label>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end space-x-4">
            <button @click="resetSettings" class="px-4 py-2 text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
              Réinitialiser
            </button>
            <button @click="saveSettings" class="px-6 py-2 bg-primea-blue text-white rounded-lg hover:bg-primea-yellow hover:text-primea-blue transition-all">
              Sauvegarder
            </button>
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
  CogIcon
} from '@heroicons/vue/24/outline'
import CalendarIcon from '@/components/icons/CalendarIcon.vue'

export default {
  name: 'AdminSettings',
  components: {
    HomeIcon,
    UsersIcon,
    CalendarIcon,
    CreditCardIcon,
    ChartBarIcon,
    CogIcon
  },
  setup() {
    const sidebarOpen = ref(window.innerWidth >= 1024)
    
    const settings = ref({
      platformName: 'Primea Ticketing',
      siteUrl: 'https://primea.ga',
      contactEmail: 'admin@primea.ga',
      timezone: 'Africa/Libreville',
      paymentMethods: {
        airtel: true,
        moov: true,
        cards: true
      },
      notifications: {
        email: true,
        sms: false,
        push: true
      },
      maintenanceMode: false
    })

    const saveSettings = () => {
      console.log('Saving settings:', settings.value)
      // Logique de sauvegarde
    }

    const resetSettings = () => {
      // Reset aux valeurs par défaut
      settings.value = {
        platformName: 'Primea Ticketing',
        siteUrl: 'https://primea.ga',
        contactEmail: 'admin@primea.ga',
        timezone: 'Africa/Libreville',
        paymentMethods: {
          airtel: true,
          moov: true,
          cards: true
        },
        notifications: {
          email: true,
          sms: false,
          push: true
        },
        maintenanceMode: false
      }
    }

    return {
      sidebarOpen,
      settings,
      saveSettings,
      resetSettings
    }
  }
}
</script>

<style scoped>
.admin-settings {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}
</style>