<template>
  <div class="min-h-screen bg-gray-50 font-primea">
    <div class="max-w-7xl mx-auto px-4 py-6">
      <!-- Breadcrumb et titre -->
      <div class="mb-8">
        <!-- Breadcrumb -->
        <nav class="flex items-center space-x-2 text-sm text-gray-600 mb-4">
          <router-link 
            :to="{ name: 'home' }" 
            class="hover:text-primea-blue transition-colors duration-200 flex items-center"
          >
            <HomeIcon class="w-4 h-4 mr-1" />
            Accueil
          </router-link>
          <ChevronRightIcon class="w-4 h-4 text-gray-400" />
          <span class="text-primea-blue font-medium">Mon compte</span>
          <ChevronRightIcon v-if="currentPageName" class="w-4 h-4 text-gray-400" />
          <span v-if="currentPageName" class="text-gray-500">{{ currentPageName }}</span>
        </nav>

        <!-- Titre principal avec navigation -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <h1 class="text-4xl font-bold text-primea-blue font-primea mb-2">Mon Compte</h1>
            <p class="text-gray-600">Gérez vos tickets, achats et informations personnelles</p>
          </div>

          <!-- Navigation rapide -->
          <div class="flex items-center gap-3">
            <router-link 
              :to="{ name: 'my-tickets' }" 
              class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-primea hover:border-primea-blue hover:text-primea-blue transition-all duration-200"
              :class="{ 'border-primea-blue text-primea-blue bg-primea-blue/5': $route.name === 'my-tickets' }"
            >
              <TicketIcon class="w-4 h-4" />
              <span class="hidden sm:inline">Mes tickets</span>
            </router-link>
            
            <router-link 
              :to="{ name: 'my-orders' }" 
              class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-primea hover:border-primea-blue hover:text-primea-blue transition-all duration-200"
              :class="{ 'border-primea-blue text-primea-blue bg-primea-blue/5': $route.name === 'my-orders' }"
            >
              <ClipboardDocumentListIcon class="w-4 h-4" />
              <span class="hidden sm:inline">Mes achats</span>
            </router-link>
            
            <router-link 
              :to="{ name: 'profile' }" 
              class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-primea hover:border-primea-blue hover:text-primea-blue transition-all duration-200"
              :class="{ 'border-primea-blue text-primea-blue bg-primea-blue/5': $route.name === 'profile' }"
            >
              <UserIcon class="w-4 h-4" />
              <span class="hidden sm:inline">Mon profil</span>
            </router-link>
          </div>
        </div>
      </div>

      <!-- Contenu des pages -->
      <router-view />
    </div>
  </div>
</template>

<script>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { 
  HomeIcon,
  ChevronRightIcon,
  TicketIcon,
  ClipboardDocumentListIcon,
  UserIcon
} from '@heroicons/vue/24/outline'

export default {
  name: 'Account',
  components: {
    HomeIcon,
    ChevronRightIcon,
    TicketIcon,
    ClipboardDocumentListIcon,
    UserIcon
  },
  setup() {
    const route = useRoute()

    const currentPageName = computed(() => {
      const pageNames = {
        'my-tickets': 'Mes tickets',
        'my-orders': 'Mes achats', 
        'profile': 'Mon profil'
      }
      return pageNames[route.name] || null
    })

    return {
      currentPageName
    }
  }
}
</script>

<style scoped>
.font-primea {
  font-family: 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.text-primea-blue {
  color: #272d63;
}

.bg-primea-blue {
  background-color: #272d63;
}

.hover\:text-primea-blue:hover {
  color: #272d63;
}

.hover\:border-primea-blue:hover {
  border-color: #272d63;
}

.border-primea-blue {
  border-color: #272d63;
}

.rounded-primea {
  border-radius: 12px;
}

.bg-primea-blue\/5 {
  background-color: rgba(39, 45, 99, 0.05);
}
</style>