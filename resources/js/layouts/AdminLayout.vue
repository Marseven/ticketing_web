<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg transform transition-transform duration-300 ease-in-out lg:translate-x-0" 
         :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }"
         @click.stop>
      
      <!-- Logo -->
      <div class="flex items-center justify-center h-16 px-4" style="background-color: #272d63;">
        <img src="/images/logo_white.png" alt="Primea" class="h-10" />
      </div>

      <!-- Navigation Menu -->
      <nav class="mt-8 px-4">
        <div class="space-y-2">
          <!-- Dashboard -->
          <router-link to="/admin/dashboard" 
                       class="flex items-center px-4 py-3 text-gray-700 rounded-lg transition-colors duration-200"
                       :class="getMenuItemClass('/admin/dashboard')"
                       :style="getMenuItemStyle('/admin/dashboard')"
                       @mouseover="handleMenuHover($event, '/admin/dashboard')"
                       @mouseleave="handleMenuLeave($event, '/admin/dashboard')">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
            </svg>
            Tableau de Bord
          </router-link>

          <!-- Gestion des Organisateurs -->
          <router-link to="/admin/organizers" 
                       class="flex items-center px-4 py-3 text-gray-700 rounded-lg transition-colors duration-200"
                       :class="getMenuItemClass('/admin/organizers')"
                       :style="getMenuItemStyle('/admin/organizers')"
                       @mouseover="handleMenuHover($event, '/admin/organizers')"
                       @mouseleave="handleMenuLeave($event, '/admin/organizers')">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h4a1 1 0 011 1v5m-6 0h6"></path>
            </svg>
            Organisateurs
          </router-link>

          <!-- Gestion des Événements -->
          <router-link to="/admin/events" 
                       class="flex items-center px-4 py-3 text-gray-700 rounded-lg transition-colors duration-200"
                       :class="getMenuItemClass('/admin/events')"
                       :style="getMenuItemStyle('/admin/events')"
                       @mouseover="handleMenuHover($event, '/admin/events')"
                       @mouseleave="handleMenuLeave($event, '/admin/events')">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            Événements
          </router-link>

          <!-- Suivi des Achats -->
          <router-link to="/admin/orders"
                       class="flex items-center px-4 py-3 text-gray-700 rounded-lg transition-colors duration-200"
                       :class="getMenuItemClass('/admin/orders')"
                       :style="getMenuItemStyle('/admin/orders')"
                       @mouseover="handleMenuHover($event, '/admin/orders')"
                       @mouseleave="handleMenuLeave($event, '/admin/orders')">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Achats
          </router-link>

          <!-- Suivi des Paiements -->
          <router-link to="/admin/payments" 
                       class="flex items-center px-4 py-3 text-gray-700 rounded-lg transition-colors duration-200"
                       :class="getMenuItemClass('/admin/payments')"
                       :style="getMenuItemStyle('/admin/payments')"
                       @mouseover="handleMenuHover($event, '/admin/payments')"
                       @mouseleave="handleMenuLeave($event, '/admin/payments')">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
            </svg>
            Paiements
          </router-link>

          <!-- Gestion des Payouts -->
          <router-link to="/admin/payouts" 
                       class="flex items-center px-4 py-3 text-gray-700 rounded-lg transition-colors duration-200"
                       :class="getMenuItemClass('/admin/payouts')"
                       :style="getMenuItemStyle('/admin/payouts')"
                       @mouseover="handleMenuHover($event, '/admin/payouts')"
                       @mouseleave="handleMenuLeave($event, '/admin/payouts')">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
            </svg>
            Payouts
          </router-link>

          <!-- Configuration des Soldes -->
          <router-link to="/admin/balance-config"
                       class="flex items-center px-4 py-3 text-gray-700 rounded-lg transition-colors duration-200"
                       :class="getMenuItemClass('/admin/balance-config')"
                       :style="getMenuItemStyle('/admin/balance-config')"
                       @mouseover="handleMenuHover($event, '/admin/balance-config')"
                       @mouseleave="handleMenuLeave($event, '/admin/balance-config')">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            Config. Soldes
          </router-link>

          <!-- Gestion des Utilisateurs (Dropdown) -->
          <div>
            <button @click="showUsersMenu = !showUsersMenu"
                    class="flex items-center justify-between w-full px-4 py-3 text-gray-700 rounded-lg transition-colors duration-200 hover:bg-gray-100">
              <div class="flex items-center">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                </svg>
                <span>Gestion des Utilisateurs</span>
              </div>
              <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': showUsersMenu }"
                   fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </button>

            <!-- Submenu -->
            <div v-show="showUsersMenu" class="ml-4 mt-2 space-y-1">
              <router-link to="/admin/user-types"
                           class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg transition-colors duration-200 hover:bg-gray-100"
                           :class="{ 'bg-gray-100 text-primea-blue font-medium': $route.path === '/admin/user-types' }">
                Types d'Utilisateurs
              </router-link>

              <router-link to="/admin/privileges"
                           class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg transition-colors duration-200 hover:bg-gray-100"
                           :class="{ 'bg-gray-100 text-primea-blue font-medium': $route.path === '/admin/privileges' }">
                Privilèges
              </router-link>

              <router-link to="/admin/roles"
                           class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg transition-colors duration-200 hover:bg-gray-100"
                           :class="{ 'bg-gray-100 text-primea-blue font-medium': $route.path === '/admin/roles' }">
                Rôles
              </router-link>

              <router-link to="/admin/admins"
                           class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg transition-colors duration-200 hover:bg-gray-100"
                           :class="{ 'bg-gray-100 text-primea-blue font-medium': $route.path === '/admin/admins' }">
                Admins
              </router-link>

              <router-link to="/admin/clients"
                           class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg transition-colors duration-200 hover:bg-gray-100"
                           :class="{ 'bg-gray-100 text-primea-blue font-medium': $route.path === '/admin/clients' }">
                Clients
              </router-link>

              <router-link to="/admin/organizers-users"
                           class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg transition-colors duration-200 hover:bg-gray-100"
                           :class="{ 'bg-gray-100 text-primea-blue font-medium': $route.path === '/admin/organizers-users' }">
                Organisateurs Users
              </router-link>

              <router-link to="/admin/trashed-users"
                           class="flex items-center px-4 py-2 text-sm text-gray-600 rounded-lg transition-colors duration-200 hover:bg-gray-100"
                           :class="{ 'bg-gray-100 text-primea-blue font-medium': $route.path === '/admin/trashed-users' }">
                Corbeille
              </router-link>
            </div>
          </div>

          <!-- Divider -->
          <div class="border-t border-gray-200 my-4"></div>

          <!-- Catégories -->
          <router-link to="/admin/categories" 
                       class="flex items-center px-4 py-3 text-gray-700 rounded-lg transition-colors duration-200"
                       :class="getMenuItemClass('/admin/categories')"
                       :style="getMenuItemStyle('/admin/categories')"
                       @mouseover="handleMenuHover($event, '/admin/categories')"
                       @mouseleave="handleMenuLeave($event, '/admin/categories')">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
            </svg>
            Catégories
          </router-link>

          <!-- Lieux -->
          <router-link to="/admin/venues" 
                       class="flex items-center px-4 py-3 text-gray-700 rounded-lg transition-colors duration-200"
                       :class="getMenuItemClass('/admin/venues')"
                       :style="getMenuItemStyle('/admin/venues')"
                       @mouseover="handleMenuHover($event, '/admin/venues')"
                       @mouseleave="handleMenuLeave($event, '/admin/venues')">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            Lieux
          </router-link>

          <!-- Rapports -->
          <router-link to="/admin/reports" 
                       class="flex items-center px-4 py-3 text-gray-700 rounded-lg transition-colors duration-200"
                       :class="getMenuItemClass('/admin/reports')"
                       :style="getMenuItemStyle('/admin/reports')"
                       @mouseover="handleMenuHover($event, '/admin/reports')"
                       @mouseleave="handleMenuLeave($event, '/admin/reports')">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            Rapports
          </router-link>
        </div>
      </nav>
    </div>

    <!-- Main Content -->
    <div class="lg:ml-64">
      <!-- Header -->
      <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="px-6 py-4">
          <div class="flex items-center justify-between">
            <!-- Left: Menu Toggle + Title + Breadcrumb -->
            <div class="flex items-center space-x-4">
              <!-- Mobile menu toggle -->
              <button @click="toggleSidebar" class="lg:hidden p-2 rounded-md transition-colors duration-200"
                      style="color: #272d63;"
                      @mouseover="$event.currentTarget.style.backgroundColor = '#fab511'; $event.currentTarget.style.color = '#272d63'"
                      @mouseleave="$event.currentTarget.style.backgroundColor = 'transparent'; $event.currentTarget.style.color = '#272d63'">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
              </button>

              <div>
                <!-- Page Title -->
                <h1 class="text-2xl font-semibold" style="color: #272d63;">{{ pageTitle }}</h1>
                
                <!-- Breadcrumb -->
                <nav class="flex mt-1" aria-label="Breadcrumb">
                  <ol class="flex items-center space-x-2 text-sm text-gray-500">
                    <li>
                      <router-link to="/admin/dashboard" class="hover:text-gray-700">Administration</router-link>
                    </li>
                    <li v-for="(breadcrumb, index) in breadcrumbs" :key="index" class="flex items-center">
                      <svg class="w-4 h-4 mx-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                      </svg>
                      <router-link v-if="breadcrumb.to" :to="breadcrumb.to" class="hover:text-gray-700">
                        {{ breadcrumb.name }}
                      </router-link>
                      <span v-else class="text-gray-900">{{ breadcrumb.name }}</span>
                    </li>
                  </ol>
                </nav>
              </div>
            </div>

            <!-- Right: Notifications + User Menu -->
            <div class="flex items-center space-x-4">
              <!-- Notifications -->
              <div class="relative">
                <button @click="toggleNotifications" class="p-2 text-gray-400 hover:text-gray-500 relative">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                  </svg>
                  <span v-if="unreadNotifications > 0" class="absolute -top-1 -right-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-500 rounded-full">
                    {{ unreadNotifications > 9 ? '9+' : unreadNotifications }}
                  </span>
                </button>

                <!-- Notifications Dropdown -->
                <div v-if="showNotifications" class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 z-50">
                  <div class="p-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Notifications</h3>
                  </div>
                  <div class="max-h-64 overflow-y-auto">
                    <div v-if="notifications.length === 0" class="p-4 text-center text-gray-500">
                      Aucune notification
                    </div>
                    <div v-else>
                      <div v-for="notification in notifications" :key="notification.id" 
                           class="p-4 border-b border-gray-100 hover:bg-gray-50 cursor-pointer"
                           @click="markAsRead(notification)">
                        <div class="flex">
                          <div class="flex-shrink-0">
                            <div class="w-2 h-2 bg-blue-600 rounded-full mt-2" v-if="!notification.read"></div>
                            <div class="w-2 h-2 bg-gray-300 rounded-full mt-2" v-else></div>
                          </div>
                          <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900">{{ notification.title }}</p>
                            <p class="text-sm text-gray-500">{{ notification.message }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ formatTime(notification.created_at) }}</p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="p-4 border-t border-gray-200">
                    <button @click="markAllAsRead" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                      Tout marquer comme lu
                    </button>
                  </div>
                </div>
              </div>

              <!-- User Menu -->
              <div class="relative">
                <button @click="toggleUserMenu" class="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-100">
                  <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-medium">
                    {{ user?.name?.charAt(0)?.toUpperCase() || 'A' }}
                  </div>
                  <div class="hidden md:block text-left">
                    <p class="text-sm font-medium text-gray-900">{{ user?.name || 'Admin' }}</p>
                    <p class="text-xs text-gray-500">Administrateur</p>
                  </div>
                  <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                  </svg>
                </button>

                <!-- User Dropdown -->
                <div v-if="showUserMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 z-50">
                  <div class="py-1">
                    <router-link to="/admin/profile" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                      <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                      </svg>
                      Mon Profil
                    </router-link>
                    
                    <router-link to="/admin/settings" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                      <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                      </svg>
                      Paramètres
                    </router-link>

                    <div class="border-t border-gray-100"></div>
                    
                    <button @click="logout" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                      <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                      </svg>
                      Déconnexion
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <main class="flex-1">
        <router-view />
      </main>

      <!-- Footer -->
      <footer class="bg-white border-t border-gray-200 py-4 px-6">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-600">
            © 2025 Primea. Tous droits réservés.
          </div>
          <div class="text-sm text-gray-500">
            Plateforme d'événements de référence
          </div>
        </div>
      </footer>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div v-if="sidebarOpen" class="fixed inset-0 z-40 lg:hidden" @click="sidebarOpen = false">
      <div class="fixed inset-0 bg-black opacity-50"></div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

export default {
  name: 'AdminLayout',
  setup() {
    const route = useRoute()
    const router = useRouter()
    
    // État réactif
    const sidebarOpen = ref(false)
    const showNotifications = ref(false)
    const showUserMenu = ref(false)
    const showUsersMenu = ref(false)
    
    const user = ref({
      name: localStorage.getItem('userName') || 'Admin',
      email: localStorage.getItem('userEmail') || 'admin@primea.com'
    })
    
    const notifications = ref([
      {
        id: 1,
        title: 'Nouveau paiement',
        message: 'Paiement de 15,000 XAF reçu',
        read: false,
        created_at: new Date(Date.now() - 300000) // 5 minutes ago
      },
      {
        id: 2,
        title: 'Achat en attente',
        message: '3 achats nécessitent votre attention',
        read: false,
        created_at: new Date(Date.now() - 900000) // 15 minutes ago
      },
      {
        id: 3,
        title: 'Nouvel organisateur',
        message: 'Demande de vérification reçue',
        read: true,
        created_at: new Date(Date.now() - 3600000) // 1 hour ago
      }
    ])

    // Computed
    const unreadNotifications = computed(() => {
      return notifications.value.filter(n => !n.read).length
    })

    const pageTitle = computed(() => {
      const titles = {
        '/admin/dashboard': 'Tableau de Bord',
        '/admin/users': 'Gestion des Utilisateurs',
        '/admin/admins': 'Gestion des Admins',
        '/admin/clients': 'Gestion des Clients',
        '/admin/organizers-users': 'Gestion des Utilisateurs Organisateurs',
        '/admin/trashed-users': 'Corbeille - Utilisateurs Supprimés',
        '/admin/user-types': 'Types d\'Utilisateurs',
        '/admin/privileges': 'Privilèges',
        '/admin/roles': 'Rôles',
        '/admin/organizers': 'Gestion des Organisateurs',
        '/admin/events': 'Gestion des Événements',
        '/admin/orders': 'Suivi des Achats',
        '/admin/payments': 'Suivi des Paiements',
        '/admin/payouts': 'Gestion des Payouts',
        '/admin/balance-config': 'Configuration des Soldes',
        '/admin/categories': 'Gestion des Catégories',
        '/admin/venues': 'Gestion des Lieux',
        '/admin/reports': 'Rapports et Analyses',
        '/admin/profile': 'Mon Profil',
        '/admin/settings': 'Paramètres'
      }
      return titles[route.path] || 'Administration'
    })

    const breadcrumbs = computed(() => {
      const crumbs = []
      const path = route.path

      if (path === '/admin/dashboard') return []

      if (path === '/admin/users') {
        crumbs.push({ name: 'Utilisateurs', to: null })
      }
      if (path === '/admin/admins') {
        crumbs.push({ name: 'Admins', to: null })
      }
      if (path === '/admin/clients') {
        crumbs.push({ name: 'Clients', to: null })
      }
      if (path === '/admin/organizers-users') {
        crumbs.push({ name: 'Utilisateurs Organisateurs', to: null })
      }
      if (path === '/admin/trashed-users') {
        crumbs.push({ name: 'Corbeille', to: null })
      }
      if (path === '/admin/user-types') {
        crumbs.push({ name: 'Types d\'Utilisateurs', to: null })
      }
      if (path === '/admin/privileges') {
        crumbs.push({ name: 'Privilèges', to: null })
      }
      if (path === '/admin/roles') {
        crumbs.push({ name: 'Rôles', to: null })
      }
      if (path.startsWith('/admin/organizers')) {
        crumbs.push({ name: 'Organisateurs', to: '/admin/organizers' })
      }
      if (path.startsWith('/admin/events')) {
        crumbs.push({ name: 'Événements', to: '/admin/events' })
      }
      if (path.startsWith('/admin/orders')) {
        crumbs.push({ name: 'Achats', to: '/admin/orders' })
      }
      if (path.startsWith('/admin/payments')) {
        crumbs.push({ name: 'Paiements', to: '/admin/payments' })
      }
      if (path.startsWith('/admin/payouts')) {
        crumbs.push({ name: 'Payouts', to: '/admin/payouts' })
      }
      if (path.startsWith('/admin/balance-config')) {
        crumbs.push({ name: 'Configuration des Soldes', to: '/admin/balance-config' })
      }
      if (path.startsWith('/admin/categories')) {
        crumbs.push({ name: 'Catégories', to: '/admin/categories' })
      }
      if (path.startsWith('/admin/venues')) {
        crumbs.push({ name: 'Lieux', to: '/admin/venues' })
      }
      if (path.startsWith('/admin/reports')) {
        crumbs.push({ name: 'Rapports', to: '/admin/reports' })
      }

      return crumbs
    })

    // Méthodes
    const toggleSidebar = () => {
      sidebarOpen.value = !sidebarOpen.value
    }
    
    // Utilitaire pour les styles de menu
    const getMenuItemStyle = (path) => {
      return route.path === path ? 
        { backgroundColor: '#272d63', borderColor: '#fab511', color: '#ffffff' } : 
        { backgroundColor: 'transparent' }
    }
    
    const getMenuItemClass = (path) => {
      return { 'text-white border-r-2': route.path === path }
    }
    
    const handleMenuHover = (event, path) => {
      event.currentTarget.style.backgroundColor = route.path === path ? '#272d63' : '#fab511'
      event.currentTarget.style.color = '#ffffff'
    }
    
    const handleMenuLeave = (event, path) => {
      event.currentTarget.style.backgroundColor = route.path === path ? '#272d63' : 'transparent'
      event.currentTarget.style.color = route.path === path ? '#ffffff' : '#374151'
    }

    const toggleNotifications = () => {
      showNotifications.value = !showNotifications.value
      showUserMenu.value = false
    }

    const toggleUserMenu = () => {
      showUserMenu.value = !showUserMenu.value
      showNotifications.value = false
    }

    const markAsRead = (notification) => {
      notification.read = true
    }

    const markAllAsRead = () => {
      notifications.value.forEach(n => n.read = true)
      showNotifications.value = false
    }

    const formatTime = (date) => {
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

    const logout = () => {
      if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
        localStorage.removeItem('token')
        localStorage.removeItem('userRole')
        localStorage.removeItem('userName')
        localStorage.removeItem('userEmail')
        router.push('/login')
      }
    }

    const loadUserData = async () => {
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
            user.value = data.data.user
            localStorage.setItem('userName', user.value.name)
            localStorage.setItem('userEmail', user.value.email)
          }
        } else {
          // API non disponible, utiliser les données localStorage
          console.log('API non disponible, utilisation des données locales')
        }
      } catch (error) {
        console.log('API non disponible, utilisation des données locales')
        // Pas d'erreur dans la console, utiliser les données localStorage existantes
      }
    }

    const loadNotifications = async () => {
      try {
        const response = await fetch('/api/v1/admin/notifications', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        if (response.ok) {
          const data = await response.json()
          if (data.success) {
            notifications.value = data.data.notifications
          }
        } else {
          // API non disponible, garder les notifications par défaut
          console.log('API notifications non disponible, utilisation des données par défaut')
        }
      } catch (error) {
        // API non disponible, garder les notifications par défaut
        console.log('API notifications non disponible, utilisation des données par défaut')
      }
    }

    // Close dropdowns when clicking outside
    const handleClickOutside = (event) => {
      if (!event.target.closest('.relative')) {
        showNotifications.value = false
        showUserMenu.value = false
      }
    }

    // Lifecycle
    onMounted(() => {
      loadUserData()
      loadNotifications()
      document.addEventListener('click', handleClickOutside)

      // Ouvrir le menu utilisateurs si on est sur une page utilisateur
      const userPages = ['/admin/user-types', '/admin/privileges', '/admin/roles', '/admin/admins', '/admin/clients', '/admin/organizers-users', '/admin/trashed-users']
      if (userPages.includes(route.path)) {
        showUsersMenu.value = true
      }
    })

    // Watch route changes to close mobile sidebar and manage users menu
    watch(route, () => {
      sidebarOpen.value = false

      // Ouvrir automatiquement le menu utilisateurs si on est sur une page utilisateur
      const userPages = ['/admin/user-types', '/admin/privileges', '/admin/roles', '/admin/admins', '/admin/clients', '/admin/organizers-users', '/admin/trashed-users']
      if (userPages.includes(route.path)) {
        showUsersMenu.value = true
      }
    })

    return {
      // État
      sidebarOpen,
      showNotifications,
      showUserMenu,
      showUsersMenu,
      user,
      notifications,
      unreadNotifications,
      pageTitle,
      breadcrumbs,

      // Méthodes
      toggleSidebar,
      toggleNotifications,
      toggleUserMenu,
      markAsRead,
      markAllAsRead,
      formatTime,
      logout,
      loadUserData,
      loadNotifications,
      getMenuItemStyle,
      getMenuItemClass,
      handleMenuHover,
      handleMenuLeave
    }
  }
}
</script>

<style scoped>
/* Smooth transitions */
.router-link-active {
  @apply bg-blue-50 text-blue-700 border-r-2 border-blue-600;
}

/* Custom scrollbar for notifications */
.max-h-64::-webkit-scrollbar {
  width: 4px;
}

.max-h-64::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.max-h-64::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 2px;
}

.max-h-64::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>