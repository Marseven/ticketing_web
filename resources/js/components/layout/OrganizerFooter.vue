<template>
  <footer class="text-white border-t border-gray-200 mt-12" style="background-color: #272d63;">
    <div class="max-w-7xl mx-auto px-4 py-8">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Informations organisateur -->
        <div>
          <div class="flex items-center mb-4">
            <img 
              src="/images/logo_white.png" 
              alt="Primea" 
              class="h-8 w-auto"
            />
            <span class="ml-3 text-sm bg-primea-yellow/20 text-primea-yellow px-2 py-1 rounded-full font-semibold font-primea">
              Espace Organisateur
            </span>
          </div>
          <p class="text-gray-300 mb-4 font-primea text-sm">
            Gérez vos événements, suivez vos ventes et optimisez votre activité avec Primea.
          </p>
          <div class="flex items-center space-x-2 text-primea-yellow">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            <span class="text-xs font-primea">Plateforme sécurisée et fiable</span>
          </div>
        </div>

        <!-- Liens rapides organisateur -->
        <div>
          <h3 class="font-semibold text-base mb-4 text-primea-yellow font-primea">Navigation rapide</h3>
          <ul class="space-y-2">
            <li>
              <router-link 
                :to="{ name: 'organizer-dashboard' }" 
                class="text-gray-300 hover:text-primea-yellow transition-colors duration-200 font-primea text-sm flex items-center space-x-2"
              >
                <HomeIcon class="w-4 h-4" />
                <span>Tableau de bord</span>
              </router-link>
            </li>
            <li>
              <router-link 
                :to="{ name: 'organizer-events' }" 
                class="text-gray-300 hover:text-primea-yellow transition-colors duration-200 font-primea text-sm flex items-center space-x-2"
              >
                <CalendarIcon class="w-4 h-4" />
                <span>Mes événements</span>
              </router-link>
            </li>
            <li>
              <router-link 
                :to="{ name: 'organizer-event-create' }" 
                class="text-gray-300 hover:text-primea-yellow transition-colors duration-200 font-primea text-sm flex items-center space-x-2"
              >
                <PlusIcon class="w-4 h-4" />
                <span>Créer un événement</span>
              </router-link>
            </li>
            <li>
              <router-link 
                :to="{ name: 'organizer-balance' }" 
                class="text-gray-300 hover:text-primea-yellow transition-colors duration-200 font-primea text-sm flex items-center space-x-2"
              >
                <CurrencyDollarIcon class="w-4 h-4" />
                <span>Gestion financière</span>
              </router-link>
            </li>
            <li>
              <router-link 
                :to="{ name: 'organizer-physical-sales' }" 
                class="text-gray-300 hover:text-primea-yellow transition-colors duration-200 font-primea text-sm flex items-center space-x-2"
              >
                <DevicePhoneMobileIcon class="w-4 h-4" />
                <span>Vente physique</span>
              </router-link>
            </li>
          </ul>
        </div>

        <!-- Support et ressources -->
        <div>
          <h3 class="font-semibold text-base mb-4 text-primea-yellow font-primea">Support & Ressources</h3>
          <ul class="space-y-2">
            <li>
              <a href="#" class="text-gray-300 hover:text-primea-yellow transition-colors duration-200 font-primea text-sm flex items-center space-x-2">
                <QuestionMarkCircleIcon class="w-4 h-4" />
                <span>Centre d'aide organisateurs</span>
              </a>
            </li>
            <li>
              <a href="#" class="text-gray-300 hover:text-primea-yellow transition-colors duration-200 font-primea text-sm flex items-center space-x-2">
                <BookOpenIcon class="w-4 h-4" />
                <span>Guide de démarrage</span>
              </a>
            </li>
            <li>
              <a href="#" class="text-gray-300 hover:text-primea-yellow transition-colors duration-200 font-primea text-sm flex items-center space-x-2">
                <ChatBubbleLeftIcon class="w-4 h-4" />
                <span>Support technique</span>
              </a>
            </li>
            <li>
              <a href="#" class="text-gray-300 hover:text-primea-yellow transition-colors duration-200 font-primea text-sm flex items-center space-x-2">
                <ChartBarIcon class="w-4 h-4" />
                <span>Conseils marketing</span>
              </a>
            </li>
            <li>
              <router-link 
                :to="{ name: 'home' }" 
                class="text-gray-300 hover:text-primea-yellow transition-colors duration-200 font-primea text-sm flex items-center space-x-2"
              >
                <GlobeAltIcon class="w-4 h-4" />
                <span>Retour au site public</span>
              </router-link>
            </li>
          </ul>

          <!-- Stats rapides (optionnel) -->
          <div v-if="quickStats" class="mt-6 p-3 bg-primea-blue/30 rounded-primea">
            <h4 class="text-primea-yellow font-semibold text-sm mb-2 font-primea">Statistiques rapides</h4>
            <div class="grid grid-cols-2 gap-2 text-xs">
              <div class="text-gray-300">
                <span class="block font-medium text-white">{{ quickStats.events || 0 }}</span>
                <span>Événements</span>
              </div>
              <div class="text-gray-300">
                <span class="block font-medium text-white">{{ quickStats.tickets || 0 }}</span>
                <span>Billets vendus</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Barre du bas -->
      <div class="mt-8 pt-6 border-t border-gray-700">
        <div class="flex flex-col md:flex-row justify-between items-center">
          <div class="flex items-center space-x-4">
            <p class="text-gray-300 text-sm font-primea">
              © 2025 Primea - Espace Organisateur
            </p>
            <div class="flex items-center space-x-1 text-green-400">
              <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
              <span class="text-xs font-primea">Système opérationnel</span>
            </div>
          </div>
          
          <div class="flex items-center space-x-4 mt-4 md:mt-0">
            <!-- Liens légaux -->
            <a href="#" class="text-gray-400 hover:text-primea-yellow text-xs font-primea transition-colors duration-200">
              Conditions organisateurs
            </a>
            <a href="#" class="text-gray-400 hover:text-primea-yellow text-xs font-primea transition-colors duration-200">
              Commission & tarifs
            </a>
            <a href="#" class="text-gray-400 hover:text-primea-yellow text-xs font-primea transition-colors duration-200">
              Contact
            </a>
          </div>
        </div>

        <!-- Version et dernière mise à jour -->
        <div class="mt-4 text-center">
          <p class="text-gray-400 text-xs font-primea">
            Version 2.1.0 - Dernière mise à jour: {{ lastUpdate }}
          </p>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { 
  HomeIcon,
  CalendarIcon,
  CurrencyDollarIcon,
  DevicePhoneMobileIcon,
  PlusIcon,
  QuestionMarkCircleIcon,
  BookOpenIcon,
  ChatBubbleLeftIcon,
  ChartBarIcon,
  GlobeAltIcon
} from '@heroicons/vue/24/outline';

const authStore = useAuthStore();

// Computed properties
const quickStats = computed(() => {
  // Ces données pourraient venir d'un store dédié aux stats organisateur
  return {
    events: 5, // Exemple de données
    tickets: 127
  };
});

const lastUpdate = computed(() => {
  const date = new Date();
  return date.toLocaleDateString('fr-FR', { 
    day: '2-digit', 
    month: '2-digit', 
    year: 'numeric' 
  });
});
</script>

<style scoped>
/* Police Primea */
.font-primea {
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

.hover\:text-primea-yellow:hover {
  color: #fab511;
}

.hover\:bg-primea-yellow:hover {
  background-color: #fab511;
}

.hover\:text-primea-blue:hover {
  color: #272d63;
}

/* Coins arrondis Primea */
.rounded-primea {
  border-radius: 12px;
}

.rounded-primea-lg {
  border-radius: 16px;
}
</style>