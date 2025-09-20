<template>
  <header class="bg-white shadow-primea sticky top-0 z-50 border-b border-gray-100">
    <nav class="max-w-7xl mx-auto px-4 py-3">
      <div class="flex items-center justify-between">
        <!-- Logo Primea avec badge organisateur -->
        <router-link :to="{ name: 'organizer-dashboard' }" class="flex items-center group">
          <img 
            src="/images/logo.png" 
            alt="Primea" 
            class="h-10 w-auto transition-transform duration-200 group-hover:scale-105"
          />
          <div class="ml-3 hidden md:block">
            <span class="text-xs text-primea-yellow bg-primea-yellow/10 px-2 py-1 rounded-full font-semibold font-primea">
              Espace Organisateur
            </span>
          </div>
        </router-link>

        <!-- Navigation Desktop -->
        <div class="hidden lg:flex items-center space-x-6">
          <router-link 
            :to="{ name: 'organizer-dashboard' }" 
            class="flex items-center space-x-2 text-primea-blue hover:text-primea-yellow font-medium font-primea transition-colors duration-200"
            :class="{ 'text-primea-yellow': $route.name === 'organizer-dashboard' }"
          >
            <HomeIcon class="w-5 h-5" />
            <span>Tableau de bord</span>
          </router-link>

          <router-link 
            :to="{ name: 'organizer-events' }" 
            class="flex items-center space-x-2 text-primea-blue hover:text-primea-yellow font-medium font-primea transition-colors duration-200"
            :class="{ 'text-primea-yellow': $route.name?.includes('organizer-event') }"
          >
            <CalendarIcon class="w-5 h-5" />
            <span>Mes Événements</span>
          </router-link>

          <router-link 
            :to="{ name: 'organizer-balance' }" 
            class="flex items-center space-x-2 text-primea-blue hover:text-primea-yellow font-medium font-primea transition-colors duration-200"
            :class="{ 'text-primea-yellow': $route.name?.includes('balance') || $route.name?.includes('payout') }"
          >
            <CurrencyDollarIcon class="w-5 h-5" />
            <span>Finances</span>
          </router-link>

          <!-- Temporairement désactivé - Page en cours de développement -->
          <!-- 
          <router-link 
            :to="{ name: 'organizer-physical-sales' }" 
            class="flex items-center space-x-2 text-primea-blue hover:text-primea-yellow font-medium font-primea transition-colors duration-200"
            :class="{ 'text-primea-yellow': $route.name?.includes('physical-sales') }"
          >
            <DevicePhoneMobileIcon class="w-5 h-5" />
            <span>Vente Physique</span>
          </router-link>
          -->
        </div>

        <!-- Actions rapides et profil -->
        <div class="flex items-center space-x-4">
          <!-- Bouton créer événement -->
          <router-link 
            :to="{ name: 'organizer-event-create' }" 
            class="hidden md:flex items-center space-x-2 bg-primea-blue text-white px-4 py-2 rounded-primea hover:bg-primea-yellow hover:text-primea-blue font-semibold font-primea transition-all duration-200 shadow-primea"
          >
            <PlusIcon class="w-4 h-4" />
            <span>Nouvel Événement</span>
          </router-link>

          <!-- Notifications -->
          <button class="relative text-primea-blue hover:text-primea-yellow transition-colors duration-200">
            <BellIcon class="w-6 h-6" />
            <span v-if="notificationCount > 0" class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold">
              {{ notificationCount }}
            </span>
          </button>

          <!-- Profile dropdown -->
          <div class="relative" ref="dropdown">
            <button 
              @click="toggleDropdown" 
              class="flex items-center space-x-2 text-primea-blue hover:text-primea-yellow transition-colors duration-200"
            >
              <div class="w-8 h-8 bg-primea-blue text-white rounded-full flex items-center justify-center">
                <span class="text-sm font-medium font-primea">{{ userInitial }}</span>
              </div>
              <div class="hidden md:block text-left">
                <p class="text-sm font-medium font-primea">{{ userName }}</p>
                <p class="text-xs text-gray-500">{{ organizerName }}</p>
              </div>
              <ChevronDownIcon class="w-4 h-4" />
            </button>

            <!-- Dropdown Menu -->
            <transition name="dropdown">
              <div v-if="dropdownOpen" class="absolute right-0 mt-2 w-64 bg-white rounded-primea shadow-primea-lg py-2 z-50 border border-gray-100">
                <!-- Info utilisateur -->
                <div class="px-4 py-3 border-b border-gray-200">
                  <p class="text-sm font-semibold text-primea-blue">{{ user?.name }}</p>
                  <p class="text-xs text-gray-500">{{ user?.email }}</p>
                  <p class="text-xs text-primea-yellow font-medium mt-1">
                    Organisateur - {{ organizerName }}
                  </p>
                </div>

                <!-- Menu items -->

                <router-link 
                  :to="{ name: 'profile' }" 
                  class="flex items-center px-4 py-2 text-primea-blue hover:bg-primea-blue/10 font-primea transition-colors duration-200"
                  @click="closeDropdown"
                >
                  <UserIcon class="w-5 h-5 mr-3" />
                  Mon profil
                </router-link>

                <router-link 
                  :to="{ name: 'home' }" 
                  class="flex items-center px-4 py-2 text-primea-blue hover:bg-primea-blue/10 font-primea transition-colors duration-200"
                  @click="closeDropdown"
                >
                  <GlobeAltIcon class="w-5 h-5 mr-3" />
                  Site public
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

          <!-- Mobile Menu Button -->
          <button 
            @click="toggleMobileMenu" 
            class="lg:hidden text-primea-blue hover:text-primea-yellow transition-colors duration-200"
          >
            <Bars3Icon v-if="!mobileMenuOpen" class="w-6 h-6" />
            <XMarkIcon v-else class="w-6 h-6" />
          </button>
        </div>
      </div>

      <!-- Mobile Navigation -->
      <transition name="mobile-menu">
        <div v-if="mobileMenuOpen" class="lg:hidden mt-4 pb-4 border-t border-gray-200">
          <div class="space-y-1">
            <router-link 
              :to="{ name: 'organizer-dashboard' }" 
              class="flex items-center space-x-3 py-3 text-primea-blue hover:text-primea-yellow font-primea font-medium transition-colors duration-200"
              @click="closeMobileMenu"
            >
              <HomeIcon class="w-5 h-5" />
              <span>Tableau de bord</span>
            </router-link>

            <router-link 
              :to="{ name: 'organizer-events' }" 
              class="flex items-center space-x-3 py-3 text-primea-blue hover:text-primea-yellow font-primea font-medium transition-colors duration-200"
              @click="closeMobileMenu"
            >
              <CalendarIcon class="w-5 h-5" />
              <span>Mes Événements</span>
            </router-link>

            <router-link 
              :to="{ name: 'organizer-event-create' }" 
              class="flex items-center space-x-3 py-3 text-primea-blue hover:text-primea-yellow font-primea font-medium transition-colors duration-200"
              @click="closeMobileMenu"
            >
              <PlusIcon class="w-5 h-5" />
              <span>Créer un événement</span>
            </router-link>

            <router-link 
              :to="{ name: 'organizer-balance' }" 
              class="flex items-center space-x-3 py-3 text-primea-blue hover:text-primea-yellow font-primea font-medium transition-colors duration-200"
              @click="closeMobileMenu"
            >
              <CurrencyDollarIcon class="w-5 h-5" />
              <span>Finances</span>
            </router-link>

            <!-- Temporairement désactivé
            <router-link 
              :to="{ name: 'organizer-physical-sales' }" 
              class="flex items-center space-x-3 py-3 text-primea-blue hover:text-primea-yellow font-primea font-medium transition-colors duration-200"
              @click="closeMobileMenu"
            >
              <DevicePhoneMobileIcon class="w-5 h-5" />
              <span>Vente Physique</span>
            </router-link>
            -->

            <div class="border-t border-gray-200 my-3"></div>

            <router-link 
              :to="{ name: 'profile' }" 
              class="flex items-center space-x-3 py-3 text-primea-blue hover:text-primea-yellow font-primea font-medium transition-colors duration-200"
              @click="closeMobileMenu"
            >
              <UserIcon class="w-5 h-5" />
              <span>Mon profil</span>
            </router-link>

            <button 
              @click="logout" 
              class="flex items-center space-x-3 w-full text-left py-3 text-red-600 hover:text-red-700 font-primea font-medium transition-colors duration-200"
            >
              <ArrowRightOnRectangleIcon class="w-5 h-5" />
              <span>Déconnexion</span>
            </button>
          </div>
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
  HomeIcon,
  CalendarIcon,
  CurrencyDollarIcon,
  DevicePhoneMobileIcon,
  PlusIcon,
  BellIcon,
  ChevronDownIcon,
  UserIcon,
  GlobeAltIcon,
  ArrowRightOnRectangleIcon,
  Bars3Icon,
  XMarkIcon
} from '@heroicons/vue/24/outline';

const router = useRouter();
const authStore = useAuthStore();

const dropdownOpen = ref(false);
const mobileMenuOpen = ref(false);
const dropdown = ref(null);

// Computed properties
const user = computed(() => authStore.user);
const userInitial = computed(() => user.value?.name?.charAt(0).toUpperCase() || 'U');
const userName = computed(() => user.value?.name || 'Mon compte');
const organizerName = computed(() => authStore.currentOrganizer?.name || 'Mon organisation');
const notificationCount = computed(() => 0); // À implémenter plus tard

// Methods
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

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
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