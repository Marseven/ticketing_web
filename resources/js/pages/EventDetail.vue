<template>
  <div class="event-detail-page bg-white min-h-screen">
    <!-- Mobile Header: Sticky -->
    <header class="sticky top-0 z-50 bg-white shadow-sm md:hidden">
      <div class="flex items-center justify-between px-4 py-3">
        <!-- Back Button -->
        <button @click="$router.back()" class="p-2 hover:bg-gray-100 rounded-lg">
          <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>

        <!-- Logo/Title -->
        <div class="flex items-center gap-2">
          <img src="/images/logo.png" alt="Logo" class="h-8" />
        </div>

        <!-- Hamburger Menu -->
        <button @click="showMenu = true" class="p-2 hover:bg-gray-100 rounded-lg">
          <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>
      </div>
    </header>

    <!-- Mobile Menu Overlay -->
    <transition name="fade">
      <div v-if="showMenu" @click="showMenu = false" class="fixed inset-0 z-50 md:hidden">
        <div class="absolute inset-0 bg-black/50" @click="showMenu = false"></div>

        <div @click.stop class="absolute right-0 top-0 bottom-0 w-80 bg-gray-800 text-white shadow-2xl overflow-y-auto">
          <!-- Close Button -->
          <button @click="showMenu = false" class="absolute top-4 right-4 p-2 hover:bg-gray-700 rounded-lg">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>

          <!-- Menu Content -->
          <div class="p-6 pt-16">
            <nav class="space-y-1">
              <router-link to="/" @click="showMenu = false" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span class="font-semibold">Accueil</span>
              </router-link>

              <div>
                <button @click="showEventsSubmenu = !showEventsSubmenu" class="flex items-center justify-between w-full px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors">
                  <div class="flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="font-semibold">Événements</span>
                  </div>
                  <svg class="w-4 h-4 transition-transform" :class="{'rotate-180': showEventsSubmenu}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                  </svg>
                </button>
                <div v-if="showEventsSubmenu" class="ml-8 mt-1 space-y-1">
                  <router-link to="/events" @click="showMenu = false" class="block px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors text-sm">
                    Tous les événements
                  </router-link>
                  <router-link v-for="category in menuCategories" :key="category.id" :to="`/events?category=${category.id}`" @click="showMenu = false" class="block px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors text-sm">
                    {{ category.name }}
                  </router-link>
                </div>
              </div>

              <router-link to="/ticket-retrieve" @click="showMenu = false" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                </svg>
                <span class="font-semibold">Récupérer mon ticket</span>
              </router-link>

              <a href="#contact" @click="showMenu = false" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <span class="font-semibold">Contacts</span>
              </a>

              <a href="#about" @click="showMenu = false" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="font-semibold">À propos</span>
              </a>
            </nav>

            <!-- Creator Button -->
            <div class="mt-8">
              <router-link to="/organizer/login" @click="showMenu = false" class="block w-full text-center px-6 py-3 bg-yellow-500 text-gray-900 rounded-lg font-bold hover:bg-yellow-400 transition-colors">
                Créateur d'événements
              </router-link>
            </div>

            <!-- Footer: Social Icons -->
            <div class="mt-12 pt-6 border-t border-gray-700">
              <div class="flex justify-center gap-6">
                <a href="#" class="text-gray-400 hover:text-white transition-colors">
                  <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                </a>
                <a href="#" class="text-gray-400 hover:text-white transition-colors">
                  <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </a>
                <a href="#" class="text-gray-400 hover:text-white transition-colors">
                  <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                </a>
                <a href="#" class="text-gray-400 hover:text-white transition-colors">
                  <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 012.31-4.64 2.93 2.93 0 01.88.13V9.4a6.84 6.84 0 00-1-.05A6.33 6.33 0 005 20.1a6.34 6.34 0 0010.86-4.43v-7a8.16 8.16 0 004.77 1.52v-3.4a4.85 4.85 0 01-1.04-.1z"/></svg>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="flex items-center justify-center min-h-screen">
        <div class="animate-spin rounded-full h-16 w-16 border-4 border-blue-900 border-t-yellow-500"></div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-container">
      <div class="flex items-center justify-center min-h-screen bg-gray-50 px-4">
        <div class="text-center bg-white p-8 md:p-12 rounded-2xl shadow-lg max-w-md w-full">
          <div class="text-red-600 text-lg md:text-xl mb-6 font-semibold">{{ error }}</div>
          <button @click="loadEvent" class="w-full md:w-auto bg-blue-900 text-white px-8 py-3 rounded-xl font-bold hover:bg-yellow-500 hover:text-blue-900 transition-all duration-200">
            Réessayer
          </button>
        </div>
      </div>
    </div>

    <!-- Event Content -->
    <div v-else-if="event" class="event-content">
      <!-- Event Image Hero (Mobile: full width, Desktop: container) -->
      <section class="relative">
        <div class="w-full h-64 md:h-96 lg:h-[500px] overflow-hidden">
          <img
            v-if="eventImageUrl"
            :src="eventImageUrl"
            :alt="event.title"
            class="w-full h-full object-cover"
            @error="handleImageError"
          />
          <div v-else class="w-full h-full bg-gradient-to-br from-blue-900 to-blue-800 flex items-center justify-center">
            <svg class="w-24 h-24 md:w-32 md:h-32 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
          </div>
        </div>
      </section>

      <!-- Event Info Section -->
      <section class="bg-white">
        <div class="max-w-7xl mx-auto px-4 py-6 md:py-8">
          <!-- Event Title -->
          <h1 class="text-2xl md:text-4xl lg:text-5xl font-bold text-blue-900 mb-4 md:mb-6 leading-tight">
            {{ event.title }}
          </h1>

          <!-- Event Meta Info (Mobile: Stack, Desktop: Row) -->
          <div class="space-y-3 md:space-y-0 md:flex md:flex-wrap md:gap-6 mb-6 md:mb-8">
            <!-- Date -->
            <div class="flex items-center gap-3 text-gray-700">
              <div class="bg-blue-100 p-2 rounded-lg flex-shrink-0">
                <svg class="w-5 h-5 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
              </div>
              <span class="font-semibold text-sm md:text-base">{{ formatFullDate(eventDate) }}</span>
            </div>

            <!-- Time -->
            <div class="flex items-center gap-3 text-gray-700">
              <div class="bg-blue-100 p-2 rounded-lg flex-shrink-0">
                <svg class="w-5 h-5 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <span class="font-semibold text-sm md:text-base">{{ formatTime(eventDate) }}</span>
            </div>

            <!-- Location -->
            <div class="flex items-center gap-3 text-gray-700">
              <div class="bg-blue-100 p-2 rounded-lg flex-shrink-0">
                <svg class="w-5 h-5 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
              </div>
              <span class="font-semibold text-sm md:text-base line-clamp-1">{{ event.venue_name }}, {{ event.venue_city }}</span>
            </div>
          </div>

          <!-- Organizer Info (if available) -->
          <div v-if="event.organizer" class="mb-6 pb-6 border-b border-gray-200">
            <p class="text-sm text-gray-600">Organisé par</p>
            <p class="font-bold text-blue-900">{{ event.organizer.name || event.organizer.organization_name }}</p>
          </div>

          <!-- Desktop: Two Column Layout -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 md:gap-8">
            <!-- Main Content (Left Column on Desktop) -->
            <div class="lg:col-span-2 space-y-6">
              <!-- Ticket Types Section -->
              <div class="bg-gray-50 p-4 md:p-6 rounded-2xl">
                <h3 class="text-lg md:text-xl font-bold text-blue-900 mb-4 flex items-center gap-2">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                  </svg>
                  Types de tickets
                </h3>

                <div v-if="event.ticket_types && event.ticket_types.length > 0" class="space-y-3">
                  <div
                    v-for="ticketType in event.ticket_types"
                    :key="ticketType.id"
                    class="bg-white p-4 rounded-xl border-2 border-gray-200 hover:border-blue-900 transition-all duration-200"
                  >
                    <div class="flex items-start justify-between gap-4">
                      <div class="flex-1 min-w-0">
                        <h4 class="font-bold text-blue-900 mb-1 text-base md:text-lg">{{ ticketType.name }}</h4>
                        <p v-if="ticketType.description" class="text-sm text-gray-600 mb-2 line-clamp-2">{{ ticketType.description }}</p>
                        <div class="flex items-center gap-2 text-xs md:text-sm">
                          <span class="text-gray-500">Places disponibles:</span>
                          <span class="font-semibold px-2 py-1 rounded-lg" :class="getQuantityDisplayCount(ticketType) > 10 ? 'bg-green-100 text-green-700' : getQuantityDisplayCount(ticketType) > 0 ? 'bg-orange-100 text-orange-700' : 'bg-red-100 text-red-700'">
                            {{ getQuantityDisplay(ticketType) }}
                          </span>
                        </div>
                      </div>

                      <div class="text-right flex-shrink-0">
                        <div class="text-xl md:text-2xl font-bold text-blue-900">
                          <span v-if="ticketType.price > 0">{{ formatPrice(ticketType.price) }}</span>
                          <span v-else class="text-green-600">GRATUIT</span>
                        </div>
                        <div class="text-xs text-gray-500">XAF</div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Fallback if no ticket types -->
                <div v-else class="bg-white p-4 rounded-xl border-2 border-gray-200">
                  <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                      <h4 class="font-bold text-blue-900 mb-1">Billet standard</h4>
                      <p class="text-sm text-gray-600 mb-2">Accès général à l'événement</p>
                      <div class="flex items-center gap-2 text-sm">
                        <span class="text-gray-500">Places disponibles:</span>
                        <span class="font-semibold px-2 py-1 rounded-lg" :class="availableTickets > 20 ? 'bg-green-100 text-green-700' : availableTickets > 0 ? 'bg-orange-100 text-orange-700' : 'bg-red-100 text-red-700'">
                          {{ availableTickets > 0 ? availableTickets : 'Complet' }}
                        </span>
                      </div>
                    </div>

                    <div class="text-right">
                      <div class="text-2xl font-bold text-blue-900">
                        <span v-if="minPrice > 0">{{ formatPrice(minPrice) }}</span>
                        <span v-else class="text-green-600">GRATUIT</span>
                      </div>
                      <div class="text-xs text-gray-500">XAF</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Description -->
              <div class="bg-white p-4 md:p-6 rounded-2xl border border-gray-200">
                <h2 class="text-lg md:text-xl font-bold text-blue-900 mb-4 flex items-center gap-2">
                  <div class="bg-blue-100 p-2 rounded-lg">
                    <svg class="w-5 h-5 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                  </div>
                  À propos de cet événement
                </h2>
                <div class="text-gray-700 leading-relaxed space-y-3 text-sm md:text-base">
                  <p v-for="(paragraph, index) in descriptionParagraphs" :key="index">
                    {{ paragraph }}
                  </p>
                  <div v-if="!descriptionParagraphs.length" class="text-gray-500 italic">
                    Aucune description disponible pour cet événement.
                  </div>
                </div>
              </div>

              <!-- Schedule Section (if available) -->
              <ScheduleSection v-if="event.schedules && event.schedules.length > 1" :schedules="event.schedules" />

              <!-- Venue Section -->
              <VenueSection :event="event" />

              <!-- Similar Events (Desktop only, mobile shows at bottom) -->
              <div v-if="similarEvents.length > 0" class="hidden md:block bg-white p-6 rounded-2xl border border-gray-200">
                <h2 class="text-xl font-bold text-blue-900 mb-6 flex items-center gap-2">
                  <div class="bg-blue-100 p-2 rounded-lg">
                    <svg class="w-5 h-5 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                  </div>
                  Événements similaires
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <EventCard
                    v-for="similarEvent in similarEvents.slice(0, 4)"
                    :key="similarEvent.id"
                    :event="similarEvent"
                    class="transform hover:scale-105 transition-all duration-300"
                  />
                </div>
              </div>
            </div>

            <!-- Sidebar (Right Column on Desktop) -->
            <div class="lg:col-span-1 space-y-6">
              <!-- Share Card -->
              <ShareCard :event="event" />

              <!-- Favorite Button (Desktop) -->
              <div class="hidden lg:block">
                <FavoriteButton :eventId="event.id" class="w-full" />
              </div>
            </div>
          </div>

          <!-- Similar Events (Mobile only) -->
          <div v-if="similarEvents.length > 0" class="md:hidden mt-8 bg-white p-4 rounded-2xl border border-gray-200">
            <h2 class="text-lg font-bold text-blue-900 mb-4 flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
              </svg>
              Événements similaires
            </h2>

            <div class="space-y-3">
              <EventCard
                v-for="similarEvent in similarEvents.slice(0, 3)"
                :key="similarEvent.id"
                :event="similarEvent"
              />
            </div>
          </div>
        </div>
      </section>
    </div>

    <!-- Fixed Bottom Action Bar (Mobile Only) -->
    <div v-if="event && !loading && !error" class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-4 md:hidden z-40 shadow-lg">
      <div class="flex items-center gap-3">
        <FavoriteButton :eventId="event.id" />

        <button
          v-if="canPurchaseTickets"
          @click="goToBooking"
          class="flex-1 bg-blue-900 text-white px-6 py-4 rounded-xl text-base font-bold hover:bg-yellow-500 hover:text-blue-900 transition-all duration-200 shadow-lg flex items-center justify-center gap-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
          </svg>
          <span v-if="minPrice > 0">Réserver - {{ formatPrice(minPrice) }} XAF</span>
          <span v-else>Réserver gratuit</span>
        </button>

        <div v-else class="flex-1 px-6 py-4 rounded-xl font-bold text-base flex items-center justify-center gap-2"
             :class="isEventPassed ? 'bg-gray-500 text-white' : 'bg-red-600 text-white'">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
          </svg>
          {{ isEventPassed ? 'Terminé' : 'Complet' }}
        </div>
      </div>
    </div>

    <!-- Desktop: Action Button in Content (not fixed) -->
    <div v-if="event && !loading && !error" class="hidden md:block">
      <div class="max-w-7xl mx-auto px-4 pb-8">
        <div class="flex items-center gap-4 justify-center lg:justify-start">
          <button
            v-if="canPurchaseTickets"
            @click="goToBooking"
            class="bg-blue-900 text-white px-10 py-4 rounded-xl text-lg font-bold hover:bg-yellow-500 hover:text-blue-900 transition-all duration-200 shadow-lg transform hover:scale-105 flex items-center justify-center gap-3"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
            </svg>
            <span v-if="minPrice > 0">
              Acheter un ticket à partir de {{ formatPrice(minPrice) }} XAF
            </span>
            <span v-else>Obtenir un ticket gratuit</span>
          </button>

          <div v-else class="px-10 py-4 rounded-xl font-bold text-lg flex items-center justify-center gap-3"
               :class="isEventPassed ? 'bg-gray-500 text-white' : 'bg-red-600 text-white'">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            {{ isEventPassed ? 'Événement terminé' : 'Événement complet' }}
          </div>
        </div>
      </div>
    </div>

    <!-- Add bottom padding for mobile fixed button -->
    <div class="h-24 md:hidden"></div>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useEventsStore } from '../stores/events'
import EventCard from '../components/EventCard.vue'
import FavoriteButton from '../components/FavoriteButton.vue'
import ScheduleSection from '../components/ScheduleSection.vue'
import VenueSection from '../components/VenueSection.vue'
import ShareCard from '../components/ShareCard.vue'

export default {
  name: 'EventDetail',
  components: {
    EventCard,
    FavoriteButton,
    ScheduleSection,
    VenueSection,
    ShareCard
  },
  setup() {
    const route = useRoute()
    const router = useRouter()
    const eventsStore = useEventsStore()

    // State
    const event = ref(null)
    const similarEvents = ref([])
    const loading = ref(true)
    const error = ref(null)
    const imageError = ref(false)
    const showMenu = ref(false)
    const showEventsSubmenu = ref(false)
    const menuCategories = ref([])

    // Computed properties
    const eventImageUrl = computed(() => {
      if (imageError.value || !event.value) return null

      let imageUrl = event.value.image || event.value.image_url || event.value.image_file

      if (!imageUrl || imageUrl.trim() === '') {
        return null
      }

      if (imageUrl.startsWith('http://') || imageUrl.startsWith('https://')) {
        return imageUrl
      }

      if (imageUrl.startsWith('/')) {
        return window.location.origin + imageUrl
      }

      if (!imageUrl.includes('/')) {
        return `${window.location.origin}/storage/images/events/${imageUrl}`
      }

      return imageUrl
    })

    const eventDate = computed(() => {
      if (event.value?.schedules && event.value.schedules.length > 0) {
        const dateStr = event.value.schedules[0].starts_at
        if (dateStr) {
          return new Date(dateStr)
        }
      }
      return null
    })

    const minPrice = computed(() => {
      if (event.value?.ticket_types && event.value.ticket_types.length > 0) {
        const prices = event.value.ticket_types.map(t => parseFloat(t.price) || 0).filter(price => price > 0)
        return prices.length > 0 ? Math.min(...prices) : 0
      }
      return 0
    })

    const isEventPassed = computed(() => {
      if (!eventDate.value) return false
      return new Date() > eventDate.value
    })

    const availableTickets = computed(() => {
      if (isEventPassed.value) return 0

      if (event.value?.ticket_types && event.value.ticket_types.length > 0) {
        return event.value.ticket_types.reduce((total, ticketType) => {
          if (ticketType.remaining_quantity !== undefined && ticketType.remaining_quantity !== null) {
            return total + Math.max(0, ticketType.remaining_quantity)
          }
          if (ticketType.available_quantity !== undefined && ticketType.available_quantity !== null) {
            const sold = ticketType.sold_quantity || 0
            return total + Math.max(0, ticketType.available_quantity - sold)
          }
          return total
        }, 0)
      }
      return 1000
    })

    const canPurchaseTickets = computed(() => {
      return !isEventPassed.value && availableTickets.value > 0
    })

    const descriptionParagraphs = computed(() => {
      if (!event.value?.description) return []
      return event.value.description.split('\n').filter(p => p.trim())
    })

    // Methods
    const formatFullDate = (date) => {
      if (!date) return 'Date à confirmer'
      return date.toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
      })
    }

    const formatTime = (date) => {
      if (!date) return 'Heure à confirmer'
      return date.toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    const formatPrice = (price) => {
      return new Intl.NumberFormat('fr-FR').format(price)
    }

    const goToBooking = () => {
      router.push(`/checkout/${route.params.slug}`)
    }

    const loadEvent = async () => {
      const eventSlug = route.params.slug
      if (!eventSlug) return

      try {
        loading.value = true
        error.value = null

        const data = await eventsStore.fetchEvent(eventSlug)
        event.value = data.event

        // Load similar events
        if (event.value?.category) {
          const eventsData = await eventsStore.getEventsByCategory(event.value.category)
          similarEvents.value = eventsData.events
            .filter(e => e.slug !== event.value.slug)
            .slice(0, 6)
        } else {
          const allEventsData = await eventsStore.fetchEvents()
          similarEvents.value = allEventsData.events
            .filter(e => e.slug !== event.value.slug)
            .slice(0, 6)
        }

      } catch (err) {
        error.value = err.message || 'Erreur lors du chargement de l\'événement'
      } finally {
        loading.value = false
      }
    }

    const loadCategories = async () => {
      try {
        const response = await fetch('/api/client/categories', {
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        })

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`)
        }

        const data = await response.json()

        if (data.success && data.categories) {
          menuCategories.value = data.categories.slice(0, 5)
        }
      } catch (error) {
        console.error('Erreur lors du chargement des catégories:', error)
        menuCategories.value = []
      }
    }

    const getQuantityDisplayCount = (ticketType) => {
      if (ticketType.remaining_quantity !== undefined && ticketType.remaining_quantity !== null) {
        return Math.max(0, ticketType.remaining_quantity)
      }
      if (ticketType.available_quantity !== undefined && ticketType.available_quantity !== null) {
        const sold = ticketType.sold_quantity || 0
        return Math.max(0, ticketType.available_quantity - sold)
      }
      return 0
    }

    const getQuantityDisplay = (ticketType) => {
      const count = getQuantityDisplayCount(ticketType)
      if (ticketType.available_quantity === null) {
        return 'Illimitées'
      }
      return count > 0 ? count : 'Complet'
    }

    const handleImageError = () => {
      console.warn('EventDetail - Erreur de chargement de l\'image')
      imageError.value = true
    }

    // Watchers
    watch(() => route.params.slug, () => {
      if (route.params.slug) {
        loadEvent()
      }
    })

    // Lifecycle
    onMounted(() => {
      loadEvent()
      loadCategories()
    })

    return {
      event,
      similarEvents,
      loading,
      error,
      showMenu,
      showEventsSubmenu,
      menuCategories,
      eventImageUrl,
      eventDate,
      minPrice,
      isEventPassed,
      availableTickets,
      canPurchaseTickets,
      descriptionParagraphs,
      formatFullDate,
      formatTime,
      formatPrice,
      goToBooking,
      loadEvent,
      getQuantityDisplayCount,
      getQuantityDisplay,
      handleImageError
    }
  }
}
</script>

<style scoped>
/* Fade transition for menu */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

/* Line clamp utility */
.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Smooth scrolling */
html {
  scroll-behavior: smooth;
}
</style>
