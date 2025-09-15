<template>
  <header class="bg-white shadow-primea sticky top-0 z-50">
    <nav class="max-w-7xl mx-auto px-4 py-4">
      <div class="flex items-center justify-between">
        <!-- Logo Primea -->
        <router-link :to="{ name: 'home' }" class="flex items-center group">
          <img 
            src="/images/logo.png" 
            alt="Primea" 
            class="h-12 w-auto transition-transform duration-200 group-hover:scale-105"
          />
        </router-link>

        <!-- Desktop Navigation -->
        <div class="hidden md:flex items-center space-x-8">
          <router-link 
            :to="{ name: 'events' }" 
            class="text-primea-blue hover:text-primea-yellow font-medium font-primea transition-colors duration-200"
          >
            Événements
          </router-link>

          <!-- Auth Links -->
          <template v-if="loading">
            <div class="flex items-center space-x-4">
              <div class="h-4 w-20 bg-gray-200 rounded animate-pulse"></div>
              <div class="h-8 w-24 bg-gray-200 rounded animate-pulse"></div>
            </div>
          </template>
          <template v-else-if="!isAuthenticated">
            <router-link 
              :to="{ name: 'login' }" 
              class="text-primea-blue hover:text-primea-yellow font-medium font-primea transition-colors duration-200"
            >
              Connexion
            </router-link>
            <router-link 
              :to="{ name: 'register' }" 
              class="bg-primea-blue text-white px-6 py-2 rounded-primea hover:bg-primea-yellow hover:text-primea-blue font-semibold font-primea transition-all duration-200 shadow-primea"
            >
              Inscription
            </router-link>
          </template>

          <!-- User Menu -->
          <template v-else>
            <!-- Icône billets pour les clients seulement -->
            <router-link 
              v-if="!isAdmin"
              :to="{ name: 'my-tickets' }" 
              class="relative text-primea-blue hover:text-primea-yellow transition-colors duration-200"
            >
              <TicketIcon class="w-6 h-6" />
              <span v-if="ticketCount > 0" class="absolute -top-2 -right-2 bg-primea-yellow text-primea-blue text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold">
                {{ ticketCount }}
              </span>
            </router-link>

            <div class="relative" ref="dropdown">
              <button 
                @click="toggleDropdown" 
                class="flex items-center space-x-2 text-primea-blue hover:text-primea-yellow transition-colors duration-200"
              >
                <div class="w-8 h-8 bg-primea-blue text-white rounded-full flex items-center justify-center">
                  <span class="text-sm font-medium font-primea">{{ userInitial }}</span>
                </div>
                <span class="font-medium font-primea">{{ userName }}</span>
                <ChevronDownIcon class="w-4 h-4 ml-1" />
              </button>

              <!-- Dropdown Menu -->
              <transition name="dropdown">
                <div v-if="dropdownOpen" class="absolute right-0 mt-2 w-64 bg-white rounded-primea shadow-primea-lg py-2 z-50 border border-gray-100">
                  <!-- Info utilisateur -->
                  <div class="px-4 py-3 border-b border-gray-200">
                    <p class="text-sm font-semibold text-primea-blue">{{ user?.name }}</p>
                    <p class="text-xs text-gray-500">{{ user?.email }}</p>
                    <p class="text-xs text-primea-yellow font-medium mt-1">
                      {{ userRoleText }}
                    </p>
                  </div>

                  <!-- Menu Admin -->
                  <template v-if="isAdmin">
                    <router-link 
                      :to="{ name: 'admin-dashboard' }" 
                      class="flex items-center px-4 py-2 text-primea-blue hover:bg-primea-blue/10 font-primea transition-colors duration-200"
                      @click="closeDropdown"
                    >
                      <CogIcon class="w-5 h-5 mr-3" />
                      Administration
                    </router-link>
                    <div class="border-t border-gray-200 my-2"></div>
                  </template>

                  <!-- Menu Organisateur -->
                  <template v-else-if="isOrganizer">
                    <router-link 
                      :to="{ name: 'organizer-dashboard' }" 
                      class="flex items-center px-4 py-2 text-primea-blue hover:bg-primea-blue/10 font-primea transition-colors duration-200"
                      @click="closeDropdown"
                    >
                      <BuildingOfficeIcon class="w-5 h-5 mr-3" />
                      Tableau de bord
                    </router-link>
                    <router-link 
                      :to="{ name: 'organizer-events' }" 
                      class="flex items-center px-4 py-2 text-primea-blue hover:bg-primea-blue/10 font-primea transition-colors duration-200"
                      @click="closeDropdown"
                    >
                      <CalendarIcon class="w-5 h-5 mr-3" />
                      Mes événements
                    </router-link>
                    <router-link 
                      :to="{ name: 'organizer-event-create' }" 
                      class="flex items-center px-4 py-2 text-primea-blue hover:bg-primea-blue/10 font-primea transition-colors duration-200"
                      @click="closeDropdown"
                    >
                      <PlusIcon class="w-5 h-5 mr-3" />
                      Créer un événement
                    </router-link>
                    <div class="border-t border-gray-200 my-2"></div>
                  </template>

                  <!-- Menu Client -->  
                  <template v-else>
                    <router-link 
                      :to="{ name: 'my-tickets' }" 
                      class="flex items-center px-4 py-2 text-primea-blue hover:bg-primea-blue/10 font-primea transition-colors duration-200"
                      @click="closeDropdown"
                    >
                      <TicketIcon class="w-5 h-5 mr-3" />
                      Mes billets
                    </router-link>
                    <router-link 
                      :to="{ name: 'my-orders' }" 
                      class="flex items-center px-4 py-2 text-primea-blue hover:bg-primea-blue/10 font-primea transition-colors duration-200"
                      @click="closeDropdown"
                    >
                      <ClipboardDocumentListIcon class="w-5 h-5 mr-3" />
                      Mes commandes
                    </router-link>
                    <div class="border-t border-gray-200 my-2"></div>
                  </template>

                  <!-- Profil (pour tous) -->
                  <router-link 
                    :to="{ name: 'profile' }" 
                    class="flex items-center px-4 py-2 text-primea-blue hover:bg-primea-blue/10 font-primea transition-colors duration-200"
                    @click="closeDropdown"
                  >
                    <UserIcon class="w-5 h-5 mr-3" />
                    Mon profil
                  </router-link>

                  <div class="border-t border-gray-200 my-2"></div>

                  <button 
                    @click="logout" 
                    class="flex items-center w-full px-4 py-2 text-primea-blue hover:bg-red-50 hover:text-red-600 font-primea transition-colors duration-200 text-left"
                  >
                    <ArrowRightOnRectangleIcon class="w-5 h-5 mr-3" />
                    Déconnexion
                  </button>
                </div>
              </transition>
            </div>
          </template>
        </div>

        <!-- Mobile Menu Button -->
        <button 
          @click="toggleMobileMenu" 
          class="md:hidden text-primea-blue hover:text-primea-yellow transition-colors duration-200"
        >
          <Bars3Icon v-if="!mobileMenuOpen" class="w-6 h-6" />
          <XMarkIcon v-else class="w-6 h-6" />
        </button>
      </div>

      <!-- Mobile Navigation -->
      <transition name="mobile-menu">
        <div v-if="mobileMenuOpen" class="md:hidden mt-4 pb-4 border-t border-gray-200">
          <router-link 
            :to="{ name: 'events' }" 
            class="block py-3 text-primea-blue hover:text-primea-yellow font-primea font-medium transition-colors duration-200"
            @click="closeMobileMenu"
          >
            Événements
          </router-link>

          <template v-if="loading">
            <div class="py-4">
              <div class="h-6 w-32 bg-gray-200 rounded animate-pulse mb-3"></div>
              <div class="h-8 w-28 bg-gray-200 rounded animate-pulse"></div>
            </div>
          </template>
          <template v-else-if="!isAuthenticated">
            <router-link 
              :to="{ name: 'login' }" 
              class="block py-3 text-primea-blue hover:text-primea-yellow font-primea font-medium transition-colors duration-200"
              @click="closeMobileMenu"
            >
              Connexion
            </router-link>
            <router-link 
              :to="{ name: 'register' }" 
              class="inline-block mt-2 px-6 py-2 bg-primea-blue text-white rounded-primea hover:bg-primea-yellow hover:text-primea-blue font-primea font-semibold transition-all duration-200"
              @click="closeMobileMenu"
            >
              Inscription
            </router-link>
          </template>

          <template v-else>
            <div class="border-t border-gray-200 mt-4 pt-4">
              <div class="py-2 text-sm text-primea-blue font-primea">
                Connecté en tant que {{ user?.name }}
              </div>
              <div class="text-xs text-primea-yellow font-medium mb-3">
                {{ userRoleText }}
              </div>
              
              <!-- Menu Admin Mobile -->
              <template v-if="isAdmin">
                <router-link 
                  :to="{ name: 'admin-dashboard' }" 
                  class="block py-3 text-primea-blue hover:text-primea-yellow font-primea font-medium transition-colors duration-200"
                  @click="closeMobileMenu"
                >
                  Administration
                </router-link>
              </template>
              
              <!-- Menu Organisateur Mobile -->
              <template v-else-if="isOrganizer">
                <router-link 
                  :to="{ name: 'organizer-dashboard' }" 
                  class="block py-3 text-primea-blue hover:text-primea-yellow font-primea font-medium transition-colors duration-200"
                  @click="closeMobileMenu"
                >
                  Tableau de bord
                </router-link>
                <router-link 
                  :to="{ name: 'organizer-events' }" 
                  class="block py-3 text-primea-blue hover:text-primea-yellow font-primea font-medium transition-colors duration-200"
                  @click="closeMobileMenu"
                >
                  Mes événements
                </router-link>
                <router-link 
                  :to="{ name: 'organizer-event-create' }" 
                  class="block py-3 text-primea-blue hover:text-primea-yellow font-primea font-medium transition-colors duration-200"
                  @click="closeMobileMenu"
                >
                  Créer un événement
                </router-link>
              </template>
              
              <!-- Menu Client Mobile -->
              <template v-else>
                <router-link 
                  :to="{ name: 'my-tickets' }" 
                  class="block py-3 text-primea-blue hover:text-primea-yellow font-primea font-medium transition-colors duration-200"
                  @click="closeMobileMenu"
                >
                  Mes billets
                </router-link>
                <router-link 
                  :to="{ name: 'my-orders' }" 
                  class="block py-3 text-primea-blue hover:text-primea-yellow font-primea font-medium transition-colors duration-200"
                  @click="closeMobileMenu"
                >
                  Mes commandes
                </router-link>
              </template>
              
              <!-- Profil (pour tous) -->
              <router-link 
                :to="{ name: 'profile' }" 
                class="block py-3 text-primea-blue hover:text-primea-yellow font-primea font-medium transition-colors duration-200"
                @click="closeMobileMenu"
              >
                Mon profil
              </router-link>
              
              <button 
                @click="logout" 
                class="block w-full text-left py-3 text-red-600 hover:text-red-700 font-primea font-medium transition-colors duration-200"
              >
                Déconnexion
              </button>
            </div>
          </template>
        </div>
      </transition>
    </nav>
  </header>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { 
  TicketIcon, 
  ChevronDownIcon, 
  ClipboardDocumentListIcon,
  UserIcon,
  BuildingOfficeIcon,
  ArrowRightOnRectangleIcon,
  Bars3Icon,
  XMarkIcon,
  CogIcon,
  CalendarIcon,
  PlusIcon
} from '@heroicons/vue/24/outline';

const router = useRouter();
const authStore = useAuthStore();

const dropdownOpen = ref(false);
const mobileMenuOpen = ref(false);
const dropdown = ref(null);

const isAuthenticated = computed(() => authStore.isAuthenticated);
const user = computed(() => authStore.user);
const loading = computed(() => authStore.loading);
const isOrganizer = computed(() => authStore.isOrganizer);
const isAdmin = computed(() => authStore.isAdmin);
const ticketCount = computed(() => authStore.activeTicketsCount || 0);
const userInitial = computed(() => user.value?.name?.charAt(0).toUpperCase() || 'U');

const userName = computed(() => {
  if (!user.value) return 'Mon compte'
  return user.value.name || 'Mon compte'
});

const userRoleText = computed(() => {
  if (isAdmin.value) return 'Administrateur'
  if (isOrganizer.value) return 'Organisateur'
  return 'Client'
});

const toggleDropdown = () => {
  dropdownOpen.value = !dropdownOpen.value;
};

const closeDropdown = () => {
  dropdownOpen.value = false;
};

const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value;
};

const closeMobileMenu = () => {
  mobileMenuOpen.value = false;
};

const logout = async () => {
  await authStore.logout();
  closeDropdown();
  closeMobileMenu();
  router.push({ name: 'home' });
};

// Close dropdown on click outside
const handleClickOutside = (event) => {
  if (dropdown.value && !dropdown.value.contains(event.target)) {
    closeDropdown();
  }
};

onMounted(async () => {
  document.addEventListener('click', handleClickOutside);
  // Initialiser le store d'authentification
  await authStore.initialize();
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
/* Ombre Primea pour le header */
.shadow-primea {
  box-shadow: 0 2px 15px rgba(39, 45, 99, 0.08);
}

/* Transitions pour les dropdowns */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.mobile-menu-enter-active,
.mobile-menu-leave-active {
  transition: all 0.3s ease;
}

.mobile-menu-enter-from,
.mobile-menu-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

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

/* Ombres supplémentaires */
.shadow-primea-lg {
  box-shadow: 0 8px 30px rgba(39, 45, 99, 0.15);
}
</style>
