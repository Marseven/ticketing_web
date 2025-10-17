<template>
  <div class="home bg-white min-h-screen">
    <!-- Mobile Header -->
    <header class="sticky top-0 z-50 bg-white shadow-sm">
      <div class="px-4 py-3 md:py-4">
        <div class="flex items-center justify-between">
          <!-- Back Button (only on mobile) -->
          <button
            @click="$router.go(-1)"
            class="md:hidden p-2 -ml-2 text-gray-600 hover:text-primea-blue"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
          </button>

          <!-- Logo and Title -->
          <div class="flex-1 flex items-center justify-center md:justify-start">
            <img src="/images/logo.png" alt="Primea" class="h-10 md:h-12" />
            <div class="ml-3 md:ml-4 text-left">
              <h1 class="text-primea-blue font-bold text-base md:text-lg leading-tight">La Billetterie</h1>
              <p class="text-xs text-gray-600">Simple, Rapide et Sécurisée</p>
            </div>
          </div>

          <!-- Hamburger Menu -->
          <button
            @click="showMenu = !showMenu"
            class="p-2 -mr-2 text-primea-blue"
          >
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
          </button>
        </div>
      </div>
    </header>

    <!-- Mobile Menu Overlay -->
    <transition name="fade">
      <div
        v-if="showMenu"
        class="fixed inset-0 z-50 bg-black bg-opacity-50"
        @click="showMenu = false"
      >
        <div
          class="absolute right-0 top-0 h-full w-80 bg-gray-800 shadow-xl transform transition-transform"
          @click.stop
        >
          <!-- Menu Header -->
          <div class="px-4 py-3 bg-white border-b">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <img src="/images/logo.png" alt="Primea" class="h-10" />
                <div class="ml-3 text-left">
                  <h1 class="text-primea-blue font-bold text-base leading-tight">La Billetterie</h1>
                  <p class="text-xs text-gray-600">Simple, Rapide et Sécurisée</p>
                </div>
              </div>
              <button @click="showMenu = false" class="text-primea-blue p-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>
          </div>

          <!-- Menu Items -->
          <nav class="p-6 space-y-4">
            <router-link
              to="/"
              class="block text-white text-lg py-3 hover:text-primea-yellow transition-colors"
              @click="showMenu = false"
            >
              Accueil
            </router-link>

            <div>
              <button
                @click="showEventsSubmenu = !showEventsSubmenu"
                class="w-full flex items-center justify-between text-white text-lg py-3 hover:text-primea-yellow transition-colors"
              >
                <span>Événements</span>
                <svg
                  class="w-5 h-5 transition-transform"
                  :class="{ 'rotate-180': showEventsSubmenu }"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
              </button>
              <div v-if="showEventsSubmenu" class="pl-4 mt-2 space-y-2">
                <router-link
                  to="/events"
                  class="block text-gray-300 py-2 hover:text-primea-yellow"
                  @click="showMenu = false"
                >
                  Tous les événements
                </router-link>
                <router-link
                  v-for="category in categories.slice(1, 4)"
                  :key="category.id"
                  :to="`/events?category=${category.id}`"
                  class="block text-gray-300 py-2 hover:text-primea-yellow"
                  @click="showMenu = false"
                >
                  {{ category.name }}
                </router-link>
              </div>
            </div>

            <router-link
              to="/retrieve-ticket"
              class="block text-white text-lg py-3 hover:text-primea-yellow transition-colors"
              @click="showMenu = false"
            >
              Récupérer mon ticket perdu
            </router-link>

            <a
              href="#contact"
              class="block text-white text-lg py-3 hover:text-primea-yellow transition-colors"
              @click="showMenu = false"
            >
              Contacts
            </a>

            <a
              href="#about"
              class="block text-white text-lg py-3 hover:text-primea-yellow transition-colors"
              @click="showMenu = false"
            >
              À propos
            </a>

            <div class="pt-6">
              <router-link
                to="/login"
                class="block bg-primea-blue text-white text-center py-3 px-6 rounded-lg font-bold hover:bg-primea-yellow hover:text-primea-blue transition-colors"
                @click="showMenu = false"
              >
                Créateur d'événements
              </router-link>
            </div>
          </nav>

          <!-- Menu Footer -->
          <div class="absolute bottom-0 left-0 right-0 p-6 border-t border-gray-700">
            <img src="/images/logo_white.png" alt="Primea" class="h-8 mx-auto mb-4" />
            <div class="flex justify-center space-x-4">
              <a href="#" class="text-white hover:text-primea-yellow">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                </svg>
              </a>
              <a href="#" class="text-white hover:text-primea-yellow">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
              </a>
              <a href="#" class="text-white hover:text-primea-yellow">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                </svg>
              </a>
              <a href="#" class="text-white hover:text-primea-yellow">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                </svg>
              </a>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- Hero Section -->
    <section class="relative min-h-[500px] md:min-h-[600px] flex items-center">
      <!-- Background Image -->
      <div class="absolute inset-0">
        <img
          src="https://images.unsplash.com/photo-1540575467063-178a50c2df87"
          alt="Événements"
          class="w-full h-full object-cover"
        />
        <div class="absolute inset-0 bg-black bg-opacity-60"></div>
      </div>

      <!-- Content -->
      <div class="relative z-10 w-full px-4 py-12 md:py-20">
        <div class="max-w-4xl mx-auto text-center">
          <!-- Search Bar -->
          <div class="mb-8">
            <div class="relative">
              <input
                type="text"
                v-model="searchQuery"
                @keyup.enter="searchEvents"
                placeholder="| Chercher un événement"
                class="w-full px-6 py-4 rounded-lg text-base bg-white/90 text-gray-800 placeholder-gray-600 focus:outline-none focus:bg-white focus:ring-2 focus:ring-primea-yellow"
              />
              <button
                @click="searchEvents"
                class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-primea-blue text-white p-3 rounded-lg hover:bg-primea-yellow hover:text-primea-blue transition-colors"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
              </button>
            </div>
          </div>

          <!-- Slogan -->
          <h1 class="text-2xl md:text-4xl lg:text-5xl font-bold text-white mb-8 leading-tight uppercase">
            SE PROCURER UN TICKET<br>
            N'A JAMAIS ÉTÉ AUSSI SIMPLE !
          </h1>

          <!-- CTA Button -->
          <div class="mb-6">
            <router-link
              to="/login"
              class="inline-block bg-primea-blue text-white px-8 py-4 rounded-lg text-base font-bold hover:bg-primea-yellow hover:text-primea-blue transition-colors shadow-lg"
            >
              Créateur d'événements
            </router-link>
          </div>

          <!-- Links -->
          <div class="flex flex-col sm:flex-row items-center justify-center gap-4 text-white">
            <router-link
              to="/retrieve-ticket"
              class="text-sm underline hover:text-primea-yellow transition-colors"
            >
              Récupérer mon ticket perdu...
            </router-link>

            <a
              href="https://wa.me/237"
              target="_blank"
              class="flex items-center gap-2 bg-white/10 backdrop-blur-sm px-4 py-2 rounded-lg hover:bg-white/20 transition-colors"
            >
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
              </svg>
              <span class="text-sm">Nous contacter</span>
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- Categories Section -->
    <section class="py-6 bg-gray-50">
      <div class="px-4 max-w-7xl mx-auto">
        <div class="grid grid-cols-3 gap-3 md:gap-4">
          <button
            v-for="category in mainCategories"
            :key="category.id"
            @click="filterByCategory(category.id)"
            :class="[
              'py-4 px-3 rounded-lg text-xs md:text-sm font-bold transition-all',
              selectedCategory === category.id
                ? 'bg-primea-blue text-white shadow-lg scale-105'
                : 'bg-white text-primea-blue border-2 border-primea-blue hover:bg-primea-yellow hover:border-primea-yellow'
            ]"
          >
            {{ category.name }}
          </button>
        </div>
      </div>
    </section>

    <!-- Events Section -->
    <section class="py-8 md:py-12 bg-white">
      <div class="px-4 max-w-7xl mx-auto">
        <h2 class="text-xl md:text-3xl font-bold text-center text-primea-blue mb-6 md:mb-8">
          Tous les événements en cours
        </h2>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-4 border-primea-blue border-t-primea-yellow"></div>
        </div>

        <!-- Events Grid -->
        <div v-else-if="filteredEvents.length > 0" class="space-y-4 md:space-y-0 md:grid md:grid-cols-2 lg:grid-cols-3 md:gap-6">
          <router-link
            v-for="event in filteredEvents"
            :key="event.id"
            :to="`/events/${event.slug}`"
            class="block bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-shadow"
          >
            <div class="relative pb-[60%]">
              <img
                :src="event.cover_image || 'https://via.placeholder.com/400x240'"
                :alt="event.title"
                class="absolute inset-0 w-full h-full object-cover"
              />
            </div>
            <div class="p-4">
              <h3 class="font-bold text-primea-blue text-sm md:text-base line-clamp-2 mb-2">
                {{ event.title }}
              </h3>
              <p class="text-xs text-gray-600 mb-1">
                {{ formatDate(event.event_date) }}
              </p>
              <p class="text-xs text-gray-500">
                {{ event.venue?.name || 'Lieu à définir' }}
              </p>
            </div>
          </router-link>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-12">
          <p class="text-gray-500 mb-4">Aucun événement disponible pour le moment.</p>
          <button
            @click="filterByCategory('all')"
            class="bg-primea-blue text-white px-6 py-3 rounded-lg hover:bg-primea-yellow hover:text-primea-blue transition-colors"
          >
            Voir tous les événements
          </button>
        </div>
      </div>
    </section>

    <!-- Espace Pub -->
    <section class="py-12 bg-gray-200">
      <div class="px-4 max-w-7xl mx-auto">
        <div class="text-center">
          <h2 class="text-4xl font-bold text-gray-400 mb-4">ESPACE PUB</h2>
          <a href="#" class="text-blue-600 hover:underline">En savoir plus...</a>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-100 py-8">
      <div class="px-4 max-w-7xl mx-auto text-center">
        <img src="/images/logo.png" alt="Primea" class="h-12 mx-auto mb-4" />
        <div class="flex justify-center space-x-6 mb-4">
          <a href="#" class="text-gray-600 hover:text-primea-blue">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
              <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
            </svg>
          </a>
          <a href="#" class="text-gray-600 hover:text-primea-blue">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
              <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
            </svg>
          </a>
          <a href="#" class="text-gray-600 hover:text-primea-blue">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
            </svg>
          </a>
          <a href="#" class="text-gray-600 hover:text-primea-blue">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
            </svg>
          </a>
        </div>
        <p class="text-sm text-gray-500">© 2025 Primea. Tous droits réservés.</p>
      </div>
    </footer>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

export default {
  name: 'Home',
  setup() {
    const router = useRouter()

    const showMenu = ref(false)
    const showEventsSubmenu = ref(false)
    const searchQuery = ref('')
    const selectedCategory = ref('all')
    const events = ref([])
    const categories = ref([])
    const loading = ref(true)

    // Main categories for buttons
    const mainCategories = computed(() => {
      return categories.value.slice(0, 3)
    })

    // Filtered events
    const filteredEvents = computed(() => {
      if (selectedCategory.value === 'all') {
        return events.value
      }
      return events.value.filter(event => {
        const eventCategoryId = event.category?.id || event.category_id
        return eventCategoryId === parseInt(selectedCategory.value)
      })
    })

    // Methods
    const loadEvents = async () => {
      try {
        loading.value = true
        const response = await fetch('/api/client/events', {
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          }
        })

        if (!response.ok) throw new Error('Erreur de chargement')

        const data = await response.json()
        events.value = data.events || data.data || []
      } catch (error) {
        console.error('Erreur:', error)
        events.value = []
      } finally {
        loading.value = false
      }
    }

    const loadCategories = async () => {
      try {
        const response = await fetch('/api/client/categories', {
          headers: { 'Accept': 'application/json' }
        })

        if (!response.ok) throw new Error('Erreur de chargement')

        const data = await response.json()

        if (data.success && data.categories) {
          categories.value = [
            { id: 'all', name: 'Tous' },
            ...data.categories.map(cat => ({
              id: cat.id,
              name: cat.name,
              slug: cat.slug
            }))
          ]
        }
      } catch (error) {
        console.error('Erreur:', error)
        categories.value = [
          { id: 'all', name: 'Tous' },
          { id: '1', name: 'Concerts/Shows' },
          { id: '2', name: 'Cinéma/Théâtre/Conférence/Expo' },
          { id: '3', name: 'Sports' }
        ]
      }
    }

    const searchEvents = () => {
      if (searchQuery.value.trim()) {
        router.push({
          path: '/events',
          query: { search: searchQuery.value.trim() }
        })
      } else {
        router.push('/events')
      }
    }

    const filterByCategory = (categoryId) => {
      selectedCategory.value = categoryId.toString()
    }

    const formatDate = (dateString) => {
      const date = new Date(dateString)
      return date.toLocaleDateString('fr-FR', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    }

    // Lifecycle
    onMounted(() => {
      loadEvents()
      loadCategories()
    })

    return {
      showMenu,
      showEventsSubmenu,
      searchQuery,
      selectedCategory,
      events,
      categories,
      loading,
      mainCategories,
      filteredEvents,
      searchEvents,
      filterByCategory,
      formatDate
    }
  }
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
