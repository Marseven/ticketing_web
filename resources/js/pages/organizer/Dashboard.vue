<template>
  <div class="organizer-dashboard min-h-screen bg-gray-100">
    <!-- En-tête organisateur -->
    <header class="bg-blue-600 text-white py-4 px-4 shadow-lg">
      <div class="max-w-6xl mx-auto flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <img src="/images/logo_white.png" alt="Primea" class="h-8" />
          <div>
            <h1 class="text-xl font-bold">Espace Organisateur</h1>
            <p class="text-blue-200 text-sm">Tableau de bord</p>
          </div>
        </div>
        
        <div class="flex items-center space-x-4">
          <div class="text-right">
            <p class="text-sm text-blue-200">Connecté en tant que</p>
            <p class="font-semibold">{{ organizer.name || 'Organisateur' }}</p>
          </div>
          <button @click="logout" class="bg-blue-700 hover:bg-blue-800 px-4 py-2 rounded-lg transition-colors">
            Déconnexion
          </button>
        </div>
      </div>
    </header>

    <div class="max-w-6xl mx-auto py-8 px-4">
      <!-- Statistiques rapides -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
          <div class="flex items-center">
            <div class="bg-blue-100 p-3 rounded-full mr-4">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a4 4 0 118 0v4m-4 8a4 4 0 11-8 0 4 4 0 018 0z"/>
              </svg>
            </div>
            <div>
              <p class="text-gray-600 text-sm">Événements</p>
              <p class="text-2xl font-bold text-gray-800">{{ stats.totalEvents }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
          <div class="flex items-center">
            <div class="bg-green-100 p-3 rounded-full mr-4">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
              </svg>
            </div>
            <div>
              <p class="text-gray-600 text-sm">Tickets vendus</p>
              <p class="text-2xl font-bold text-gray-800">{{ stats.totalTicketsSold }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
          <div class="flex items-center">
            <div class="bg-yellow-100 p-3 rounded-full mr-4">
              <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
              </svg>
            </div>
            <div>
              <p class="text-gray-600 text-sm">Revenus totaux</p>
              <p class="text-2xl font-bold text-gray-800">{{ formatPrice(stats.totalRevenue) }} FCFA</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
          <div class="flex items-center">
            <div class="bg-purple-100 p-3 rounded-full mr-4">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
              </svg>
            </div>
            <div>
              <p class="text-gray-600 text-sm">Taux de vente</p>
              <p class="text-2xl font-bold text-gray-800">{{ stats.salesRate }}%</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions rapides -->
      <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-6">Actions rapides</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <router-link 
            to="/organizer/events/create" 
            class="flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors"
          >
            <div class="bg-blue-600 p-2 rounded-full mr-4">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
              </svg>
            </div>
            <div>
              <p class="font-semibold text-blue-800">Créer un événement</p>
              <p class="text-sm text-blue-600">Ajouter un nouvel événement</p>
            </div>
          </router-link>

          <router-link 
            to="/organizer/scanner" 
            class="flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors"
          >
            <div class="bg-green-600 p-2 rounded-full mr-4">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
              </svg>
            </div>
            <div>
              <p class="font-semibold text-green-800">Scanner QR</p>
              <p class="text-sm text-green-600">Contrôler les entrées</p>
            </div>
          </router-link>

          <router-link 
            to="/organizer/reports" 
            class="flex items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors"
          >
            <div class="bg-purple-600 p-2 rounded-full mr-4">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
              </svg>
            </div>
            <div>
              <p class="font-semibold text-purple-800">Rapports</p>
              <p class="text-sm text-purple-600">Analyses et statistiques</p>
            </div>
          </router-link>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Événements récents -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-800">Mes événements récents</h2>
            <router-link to="/organizer/events" class="text-blue-600 hover:text-blue-500 text-sm font-medium">
              Voir tout
            </router-link>
          </div>
          
          <div class="space-y-4">
            <div 
              v-for="event in recentEvents" 
              :key="event.id"
              class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50"
            >
              <img 
                :src="event.image || 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=100'" 
                :alt="event.title"
                class="w-16 h-16 object-cover rounded-lg mr-4"
              />
              <div class="flex-1">
                <h3 class="font-semibold text-gray-800">{{ event.title }}</h3>
                <p class="text-sm text-gray-600">{{ formatEventDate(event.date) }}</p>
                <div class="flex items-center space-x-4 mt-1">
                  <span class="text-xs px-2 py-1 rounded-full"
                        :class="getStatusClass(event.status)">
                    {{ getStatusText(event.status) }}
                  </span>
                  <span class="text-sm text-gray-500">{{ event.ticketsSold || 0 }} tickets vendus</span>
                </div>
              </div>
              <router-link 
                :to="`/organizer/events/${event.id}`"
                class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700"
              >
                Voir
              </router-link>
            </div>
          </div>
        </div>

        <!-- Notifications -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <h2 class="text-xl font-bold text-gray-800 mb-6">Notifications</h2>
          
          <div class="space-y-4">
            <div 
              v-for="notification in notifications" 
              :key="notification.id"
              class="flex items-start p-3 border-l-4 rounded"
              :class="getNotificationClass(notification.type)"
            >
              <div class="flex-shrink-0 mr-3">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                     :class="getNotificationIconClass(notification.type)">
                  <path v-if="notification.type === 'success'" fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                  <path v-else-if="notification.type === 'warning'" fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                  <path v-else fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
              </div>
              <div class="flex-1">
                <p class="text-sm font-medium" :class="getNotificationTextClass(notification.type)">
                  {{ notification.title }}
                </p>
                <p class="text-sm text-gray-600">{{ notification.message }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ formatRelativeTime(notification.createdAt) }}</p>
              </div>
            </div>
            
            <div v-if="notifications.length === 0" class="text-center py-4 text-gray-500">
              Aucune notification
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'

export default {
  name: 'OrganizerDashboard',
  setup() {
    const router = useRouter()

    // État réactif
    const organizer = ref({
      id: 1,
      name: 'Organisateur Demo',
      email: 'organizer@primea.com'
    })

    const stats = ref({
      totalEvents: 5,
      totalTicketsSold: 247,
      totalRevenue: 3750000,
      salesRate: 78
    })

    const recentEvents = ref([
      {
        id: 1,
        title: "L'OISEAU RARE",
        date: '2025-07-27T20:00:00',
        image: 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=100',
        status: 'published',
        ticketsSold: 85
      },
      {
        id: 2,
        title: 'Concert Jazz Night',
        date: '2025-08-15T19:30:00',
        image: 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=100',
        status: 'draft',
        ticketsSold: 0
      },
      {
        id: 3,
        title: 'Festival Arts & Culture',
        date: '2025-09-10T14:00:00',
        image: 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=100',
        status: 'published',
        ticketsSold: 162
      }
    ])

    const notifications = ref([
      {
        id: 1,
        type: 'success',
        title: 'Nouvelle vente',
        message: '5 nouveaux tickets vendus pour "L\'OISEAU RARE"',
        createdAt: new Date(Date.now() - 30 * 60 * 1000)
      },
      {
        id: 2,
        type: 'warning',
        title: 'Stock faible',
        message: 'Il ne reste que 15 tickets pour "Concert Jazz Night"',
        createdAt: new Date(Date.now() - 2 * 60 * 60 * 1000)
      },
      {
        id: 3,
        type: 'info',
        title: 'Événement approuvé',
        message: 'Votre événement "Festival Arts" a été approuvé',
        createdAt: new Date(Date.now() - 24 * 60 * 60 * 1000)
      }
    ])

    // Méthodes
    const formatPrice = (price) => {
      return new Intl.NumberFormat('fr-FR').format(price)
    }

    const formatEventDate = (dateString) => {
      if (!dateString) {
        return 'Date non définie'
      }

      const date = new Date(dateString)

      // Vérifier si la date est valide
      if (isNaN(date.getTime())) {
        return 'Date invalide'
      }

      return date.toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    const formatRelativeTime = (date) => {
      const now = new Date()
      const diffInMs = now - date
      const diffInMinutes = Math.floor(diffInMs / (1000 * 60))
      const diffInHours = Math.floor(diffInMs / (1000 * 60 * 60))
      const diffInDays = Math.floor(diffInMs / (1000 * 60 * 60 * 24))

      if (diffInMinutes < 60) {
        return `Il y a ${diffInMinutes} minute${diffInMinutes > 1 ? 's' : ''}`
      } else if (diffInHours < 24) {
        return `Il y a ${diffInHours} heure${diffInHours > 1 ? 's' : ''}`
      } else {
        return `Il y a ${diffInDays} jour${diffInDays > 1 ? 's' : ''}`
      }
    }

    const getStatusClass = (status) => {
      const classes = {
        published: 'bg-green-100 text-green-800',
        draft: 'bg-yellow-100 text-yellow-800',
        cancelled: 'bg-red-100 text-red-800',
        completed: 'bg-gray-100 text-gray-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    }

    const getStatusText = (status) => {
      const texts = {
        published: 'Publié',
        draft: 'Brouillon',
        cancelled: 'Annulé',
        completed: 'Terminé'
      }
      return texts[status] || 'Inconnu'
    }

    const getNotificationClass = (type) => {
      const classes = {
        success: 'border-green-400 bg-green-50',
        warning: 'border-yellow-400 bg-yellow-50',
        info: 'border-blue-400 bg-blue-50',
        error: 'border-red-400 bg-red-50'
      }
      return classes[type] || 'border-gray-400 bg-gray-50'
    }

    const getNotificationIconClass = (type) => {
      const classes = {
        success: 'text-green-400',
        warning: 'text-yellow-400',
        info: 'text-blue-400',
        error: 'text-red-400'
      }
      return classes[type] || 'text-gray-400'
    }

    const getNotificationTextClass = (type) => {
      const classes = {
        success: 'text-green-800',
        warning: 'text-yellow-800',
        info: 'text-blue-800',
        error: 'text-red-800'
      }
      return classes[type] || 'text-gray-800'
    }

    const logout = () => {
      // Logique de déconnexion
      router.push('/login')
    }

    return {
      organizer,
      stats,
      recentEvents,
      notifications,
      formatPrice,
      formatEventDate,
      formatRelativeTime,
      getStatusClass,
      getStatusText,
      getNotificationClass,
      getNotificationIconClass,
      getNotificationTextClass,
      logout
    }
  }
}
</script>

<style scoped>
.organizer-dashboard {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

/* Transitions */
.transition-colors {
  transition: color 0.2s ease-in-out, background-color 0.2s ease-in-out;
}

/* Grid utilities */
.grid {
  display: grid;
}

.grid-cols-1 {
  grid-template-columns: repeat(1, minmax(0, 1fr));
}

@media (min-width: 768px) {
  .md\:grid-cols-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  
  .md\:grid-cols-3 {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (min-width: 1024px) {
  .lg\:grid-cols-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  
  .lg\:grid-cols-4 {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

.gap-4 {
  gap: 1rem;
}

.gap-6 {
  gap: 1.5rem;
}

.space-x-4 > * + * {
  margin-left: 1rem;
}

.space-y-4 > * + * {
  margin-top: 1rem;
}
</style>