<template>
  <header class="bg-white shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto">
      <!-- Mobile Header -->
      <div class="md:hidden px-4 py-3">
        <!-- Ligne 1: Actions (Retour et Menu) -->
        <div class="flex items-center justify-between mb-2">
          <!-- Back Button -->
          <button
            @click="goBack"
            class="p-1 -ml-2 text-gray-600 hover:text-blue-950 transition-colors"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
          </button>

          <!-- Burger Menu -->
          <button
            @click="toggleMenu"
            class="p-1 -mr-2 text-blue-950 hover:text-yellow-500 transition-colors"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
          </button>
        </div>

        <!-- Ligne 2: Logo et Texte -->
        <div class="flex items-center justify-between">
          <!-- Logo -->
          <router-link :to="{ name: 'home' }" class="group">
            <img src="/images/logo.png" alt="Logo" class="h-14 w-auto transition-transform duration-200 group-hover:scale-105" />
          </router-link>

          <!-- Texte -->
          <div class="text-right">
            <h1 class="text-blue-950 font-black leading-tight" style="font-size: 1.54rem;">La Billetterie</h1>
            <p class="text-blue-950 text-xs font-medium leading-tight">Simple, Rapide et Sécurisée</p>
          </div>
        </div>
      </div>

      <!-- Desktop/Tablet Header -->
      <div class="hidden md:flex items-center justify-between px-6 py-4">
        <!-- Logo and Title -->
        <router-link :to="{ name: 'home' }" class="flex items-center group">
          <img src="/images/logo.png" alt="Logo" class="h-20 w-auto transition-transform duration-200 group-hover:scale-105" />
          <div class="ml-4 text-left">
            <h1 class="text-blue-950 text-3xl font-black leading-tight">La Billetterie</h1>
            <p class="text-blue-950 text-sm font-medium leading-tight">Simple, Rapide et Sécurisée</p>
          </div>
        </router-link>

        <!-- Desktop Navigation -->
        <nav class="flex items-center space-x-8">
          <router-link
            :to="{ name: 'home' }"
            class="text-blue-950 hover:text-yellow-500 font-semibold transition-colors duration-200"
          >
            Accueil
          </router-link>

          <!-- Events Dropdown -->
          <div class="relative" ref="eventsDropdown">
            <button
              @click="toggleEventsDropdown"
              class="flex items-center space-x-1 text-blue-950 hover:text-yellow-500 font-semibold transition-colors duration-200"
            >
              <span>Événements</span>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>

            <!-- Categories Dropdown -->
            <transition name="dropdown">
              <div v-if="eventsDropdownOpen" class="absolute left-0 mt-2 w-56 bg-white rounded-xl shadow-2xl py-2 border border-gray-100">
                <router-link
                  :to="{ name: 'events' }"
                  @click="closeEventsDropdown"
                  class="flex items-center px-4 py-2 text-blue-950 hover:bg-blue-950/10 transition-colors duration-200 font-semibold"
                >
                  Tous les événements
                </router-link>
                <div class="border-t border-gray-200 my-2"></div>
                <router-link
                  v-for="category in categories"
                  :key="category.id"
                  :to="{ name: 'events', query: { category: category.id } }"
                  @click="closeEventsDropdown"
                  class="flex items-center px-4 py-2 text-sm text-blue-950 hover:bg-blue-950/10 transition-colors duration-200"
                >
                  {{ category.name }}
                </router-link>
              </div>
            </transition>
          </div>

          <router-link
            :to="{ name: 'ticket-retrieve' }"
            class="text-blue-950 hover:text-yellow-500 font-semibold transition-colors duration-200"
          >
            Récupérer mon ticket
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
              class="text-blue-950 hover:text-yellow-500 font-semibold transition-colors duration-200"
            >
              Connexion
            </router-link>
            <router-link
              :to="{ name: 'register' }"
              class="bg-blue-950 text-white px-6 py-2.5 rounded-xl hover:bg-yellow-500 hover:text-blue-950 font-bold transition-all duration-200 shadow-lg"
            >
              Inscription
            </router-link>
          </template>

          <!-- User Menu Desktop -->
          <template v-else>
            <router-link
              v-if="!isAdmin"
              :to="{ name: 'my-tickets' }"
              class="relative text-blue-950 hover:text-yellow-500 transition-colors duration-200"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
              </svg>
              <span v-if="ticketCount > 0" class="absolute -top-2 -right-2 bg-yellow-500 text-blue-950 text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold">
                {{ ticketCount }}
              </span>
            </router-link>

            <div class="relative" ref="dropdown">
              <button
                @click="toggleDropdown"
                class="flex items-center space-x-2 text-blue-950 hover:text-yellow-500 transition-colors duration-200"
              >
                <div class="w-9 h-9 bg-blue-950 text-white rounded-full flex items-center justify-center overflow-hidden">
                  <img
                    v-if="userAvatar"
                    :src="userAvatar"
                    :alt="userName"
                    class="w-full h-full object-cover"
                    @error="handleAvatarError"
                  />
                  <span v-else class="text-sm font-medium">{{ userInitial }}</span>
                </div>
                <span class="font-semibold">{{ userName }}</span>
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
              </button>

              <!-- Dropdown Menu -->
              <transition name="dropdown">
                <div v-if="dropdownOpen" class="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-2xl py-2 border border-gray-100">
                  <div class="px-4 py-3 border-b border-gray-200">
                    <p class="text-sm font-semibold text-blue-950">{{ user?.name }}</p>
                    <p class="text-xs text-gray-500">{{ user?.email }}</p>
                    <p class="text-xs text-yellow-500 font-medium mt-1">{{ userRoleText }}</p>
                  </div>

                  <template v-if="isAdmin">
                    <router-link :to="{ name: 'admin-dashboard' }" @click="closeDropdown" class="flex items-center px-4 py-2 text-blue-950 hover:bg-blue-950/10 transition-colors duration-200">
                      <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                      </svg>
                      Administration
                    </router-link>
                    <div class="border-t border-gray-200 my-2"></div>
                  </template>

                  <template v-else-if="isOrganizer">
                    <router-link :to="{ name: 'organizer-dashboard' }" @click="closeDropdown" class="flex items-center px-4 py-2 text-blue-950 hover:bg-blue-950/10 transition-colors duration-200">
                      <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                      </svg>
                      Tableau de bord
                    </router-link>
                    <router-link :to="{ name: 'organizer-events' }" @click="closeDropdown" class="flex items-center px-4 py-2 text-blue-950 hover:bg-blue-950/10 transition-colors duration-200">
                      <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                      </svg>
                      Mes événements
                    </router-link>
                    <div class="border-t border-gray-200 my-2"></div>
                  </template>

                  <template v-else>
                    <router-link :to="{ name: 'my-tickets' }" @click="closeDropdown" class="flex items-center px-4 py-2 text-blue-950 hover:bg-blue-950/10 transition-colors duration-200">
                      <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                      </svg>
                      Mes tickets
                    </router-link>
                    <router-link :to="{ name: 'my-orders' }" @click="closeDropdown" class="flex items-center px-4 py-2 text-blue-950 hover:bg-blue-950/10 transition-colors duration-200">
                      <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                      </svg>
                      Mes achats
                    </router-link>
                    <div class="border-t border-gray-200 my-2"></div>
                  </template>

                  <router-link :to="{ name: 'profile' }" @click="closeDropdown" class="flex items-center px-4 py-2 text-blue-950 hover:bg-blue-950/10 transition-colors duration-200">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Mon profil
                  </router-link>

                  <div class="border-t border-gray-200 my-2"></div>

                  <button @click="logout" class="flex items-center w-full px-4 py-2 text-blue-950 hover:bg-red-50 hover:text-red-600 transition-colors duration-200 text-left">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Déconnexion
                  </button>
                </div>
              </transition>
            </div>
          </template>
        </nav>
      </div>
    </div>

    <!-- Mobile Menu Overlay -->
    <transition name="slide">
      <div v-if="mobileMenuOpen" class="fixed inset-0 z-50 md:hidden" @click="closeMenu">
        <div class="absolute inset-0 bg-black/50"></div>
        <div @click.stop class="absolute right-0 top-0 bottom-0 w-full bg-gray-700/90 shadow-xl flex flex-col">
          <!-- Menu Header -->
          <div class="flex-shrink-0 px-4 py-3 bg-white border-b">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <img src="/images/logo.png" alt="Logo" class="h-12" />
                <div class="text-right ml-2">
                  <h1 class="text-blue-950 font-black leading-tight" style="font-size: 1.54rem;">La Billetterie</h1>
                  <p class="text-blue-950 text-xs font-medium leading-tight">Simple, Rapide et Sécurisée</p>
                </div>
              </div>
              <button @click="closeMenu" class="text-blue-950 p-2 flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>
          </div>

          <!-- Menu Items -->
          <nav class="flex-1 min-h-0 overflow-y-auto p-6">
            <div class="w-4/5 mx-auto space-y-4">
            <router-link to="/" @click="closeMenu" class="block text-white text-lg py-3 hover:text-yellow-500 transition-colors">
              Accueil
            </router-link>

            <!-- Events with submenu -->
            <div>
              <button @click="toggleMobileEventsSubmenu" class="flex items-center justify-between w-full text-white text-lg py-3 hover:text-yellow-500 transition-colors">
                <span>Événements</span>
                <svg class="w-4 h-4 transition-transform" :class="{'rotate-180': mobileEventsSubmenuOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
              </button>
              <div v-if="mobileEventsSubmenuOpen" class="ml-4 mt-2 space-y-2">
                <router-link :to="{ name: 'events' }" @click="closeMenu" class="block text-white/80 text-base py-2 hover:text-yellow-500 transition-colors">
                  Tous les événements
                </router-link>
                <router-link
                  v-for="category in categories"
                  :key="category.id"
                  :to="{ name: 'events', query: { category: category.id } }"
                  @click="closeMenu"
                  class="block text-white/80 text-sm py-2 hover:text-yellow-500 transition-colors"
                >
                  {{ category.name }}
                </router-link>
              </div>
            </div>

            <router-link :to="{ name: 'ticket-retrieve' }" @click="closeMenu" class="block text-white text-lg py-3 hover:text-yellow-500 transition-colors">
              Récupérer mon ticket
            </router-link>

            <template v-if="!isAuthenticated">
              <div class="pt-6 space-y-3 flex flex-col">
                <router-link :to="{ name: 'login' }" @click="closeMenu" class="block w-full text-center bg-blue-950 text-white py-3 px-6 rounded-lg font-bold hover:bg-yellow-500 hover:text-blue-950 transition-colors">
                  Connexion
                </router-link>
                <router-link :to="{ name: 'register' }" @click="closeMenu" class="block w-full text-center border-2 border-white text-white py-3 px-6 rounded-lg font-bold hover:bg-white hover:text-blue-950 transition-colors">
                  Inscription
                </router-link>
              </div>
            </template>

            <template v-else>
              <div class="pt-6 border-t border-gray-700">
                <div class="text-white text-sm mb-4">
                  Connecté en tant que <span class="font-bold">{{ user?.name }}</span>
                </div>

                <template v-if="isAdmin">
                  <router-link :to="{ name: 'admin-dashboard' }" @click="closeMenu" class="block text-white text-lg py-3 hover:text-yellow-500 transition-colors">
                    Administration
                  </router-link>
                </template>

                <template v-else-if="isOrganizer">
                  <router-link :to="{ name: 'organizer-dashboard' }" @click="closeMenu" class="block text-white text-lg py-3 hover:text-yellow-500 transition-colors">
                    Tableau de bord
                  </router-link>
                  <router-link :to="{ name: 'organizer-events' }" @click="closeMenu" class="block text-white text-lg py-3 hover:text-yellow-500 transition-colors">
                    Mes événements
                  </router-link>
                </template>

                <template v-else>
                  <router-link :to="{ name: 'my-tickets' }" @click="closeMenu" class="block text-white text-lg py-3 hover:text-yellow-500 transition-colors">
                    Mes tickets
                  </router-link>
                </template>

                <router-link :to="{ name: 'profile' }" @click="closeMenu" class="block text-white text-lg py-3 hover:text-yellow-500 transition-colors">
                  Mon profil
                </router-link>

                <button @click="logout" class="block w-full text-left text-red-400 text-lg py-3 hover:text-red-300 transition-colors">
                  Déconnexion
                </button>
              </div>
            </template>

            <div v-if="!isAuthenticated" class="pt-6">
              <router-link to="/organizer-choice" @click="closeMenu" class="block w-full bg-yellow-500 text-blue-950 text-center py-3 px-6 rounded-lg font-bold hover:bg-yellow-400 transition-colors">
                Créateur d'événements
              </router-link>
            </div>
            </div>
          </nav>

          <!-- Menu Footer -->
          <div class="flex-shrink-0 p-6 flex flex-col items-center gap-3 bg-gray-700/90 border-t border-white/10">
            <img src="/images/logo.png" alt="Primea Logo" class="h-12" style="filter: brightness(0) invert(1) opacity(0.9);" />
            <div class="flex items-center gap-4 text-white/60">
              <a href="https://wa.me/237" target="_blank" class="hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                </svg>
              </a>
              <a href="#" target="_blank" class="hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
              </a>
              <a href="#" target="_blank" class="hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                </svg>
              </a>
              <a href="#" target="_blank" class="hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                </svg>
              </a>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </header>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const dropdownOpen = ref(false)
const mobileMenuOpen = ref(false)
const eventsDropdownOpen = ref(false)
const mobileEventsSubmenuOpen = ref(false)
const dropdown = ref(null)
const eventsDropdown = ref(null)
const userAvatar = ref(null)
const categories = ref([])

const isAuthenticated = computed(() => authStore.isAuthenticated)
const user = computed(() => authStore.user)
const loading = computed(() => authStore.loading)
const isOrganizer = computed(() => authStore.isOrganizer)
const isAdmin = computed(() => authStore.isAdmin)
const ticketCount = computed(() => authStore.activeTicketsCount || 0)
const userInitial = computed(() => user.value?.name?.charAt(0).toUpperCase() || 'U')

const userName = computed(() => {
  if (!user.value) return 'Mon compte'
  return user.value.name || 'Mon compte'
})

const userRoleText = computed(() => {
  if (isAdmin.value) return 'Administrateur'
  if (isOrganizer.value) return 'Organisateur'
  return 'Client'
})

const loadUserAvatar = () => {
  if (user.value) {
    if (user.value.avatar_file) {
      userAvatar.value = `/storage/images/users/${user.value.avatar_file}`
    } else if (user.value.avatar_url && !user.value.avatar_url.includes('user-default.jpg')) {
      userAvatar.value = user.value.avatar_url
    } else {
      userAvatar.value = null
    }
  } else {
    userAvatar.value = null
  }
}

const handleAvatarError = () => {
  userAvatar.value = null
}

const toggleDropdown = () => {
  dropdownOpen.value = !dropdownOpen.value
}

const closeDropdown = () => {
  dropdownOpen.value = false
}

const toggleEventsDropdown = () => {
  eventsDropdownOpen.value = !eventsDropdownOpen.value
}

const closeEventsDropdown = () => {
  eventsDropdownOpen.value = false
}

const toggleMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value
}

const closeMenu = () => {
  mobileMenuOpen.value = false
  mobileEventsSubmenuOpen.value = false
}

const toggleMobileEventsSubmenu = () => {
  mobileEventsSubmenuOpen.value = !mobileEventsSubmenuOpen.value
}

const goBack = () => {
  router.back()
}

const loadCategories = async () => {
  try {
    const response = await fetch('/api/client/categories', {
      headers: { 'Accept': 'application/json' }
    })
    if (!response.ok) throw new Error('Erreur de chargement')
    const data = await response.json()
    if (data.success && data.categories) {
      categories.value = data.categories.map(cat => ({
        id: cat.id,
        name: cat.name,
        slug: cat.slug
      }))
    }
  } catch (error) {
    console.error('Erreur:', error)
  }
}

const logout = async () => {
  await authStore.logout()
  closeDropdown()
  closeMenu()
  window.location.href = '/'
}

const handleClickOutside = (event) => {
  if (dropdown.value && !dropdown.value.contains(event.target)) {
    closeDropdown()
  }
  if (eventsDropdown.value && !eventsDropdown.value.contains(event.target)) {
    closeEventsDropdown()
  }
}

onMounted(async () => {
  document.addEventListener('click', handleClickOutside)
  await authStore.initialize()
  loadUserAvatar()
  loadCategories()
})

watch(user, () => {
  loadUserAvatar()
}, { deep: true })

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.slide-enter-active,
.slide-leave-active {
  transition: opacity 0.3s ease;
}

.slide-enter-from,
.slide-leave-to {
  opacity: 0;
}
</style>
