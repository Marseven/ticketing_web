import axios from 'axios'

// Configuration de base d'axios
const api = axios.create({
  baseURL: '/api/v1',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// Intercepteur pour ajouter le token d'authentification
api.interceptors.request.use(
  (config) => {
    // Récupérer le token d'authentification
    const token = localStorage.getItem('auth_token') || localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    
    // Ajouter les headers requis pour Laravel
    config.headers['X-Requested-With'] = 'XMLHttpRequest'
    
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Intercepteur pour gérer les erreurs de réponse
api.interceptors.response.use(
  (response) => response,
  (error) => {
    console.log('Erreur API:', error.response?.status, error.response?.data)
    
    if (error.response?.status === 401) {
      // Token expiré ou invalide
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user')
      window.location.href = '/login'
    } else if (error.response?.status === 419) {
      console.error('CSRF token mismatch. Vérifiez la configuration Sanctum.')
    }
    return Promise.reject(error)
  }
)

// Services API
export const ticketService = {
  // Récupérer un ticket par son code
  getTicket(code) {
    return api.get(`/tickets/${code}`)
  },

  // Récupérer des tickets via token de récupération
  retrieveTickets(token) {
    return api.get(`/tickets/retrieve/${token}`)
  },

  // Valider un ticket (pour le scanner)
  validateTicket(qrCode, action = 'validate') {
    return api.post('/tickets/validate', { qr_code: qrCode, action })
  },

  // Rechercher des tickets par email, téléphone ou référence
  searchTickets(searchData) {
    // Cette méthode simule une recherche basée sur les données fournies
    // En réalité, il faudrait créer un endpoint spécifique dans le backend
    const { reference, phone, email } = searchData
    
    if (reference) {
      // Si on a une référence, essayer de récupérer directement
      return this.getTicket(reference)
    }
    
    // Pour email/phone, on pourrait créer un token de récupération
    // Format: base64(email:reference_estimee)
    if (email || phone) {
      // Simulation - en réalité il faudrait une vraie API de recherche
      return new Promise((resolve, reject) => {
        setTimeout(() => {
          // Simuler des résultats basés sur les critères de test
          if (email?.includes('test') || phone?.includes('123') || reference?.includes('TKT')) {
            resolve({
              data: {
                tickets: [{
                  id: 1,
                  code: 'TKT-2024-ABC123',
                  status: 'issued',
                  event: {
                    id: 1,
                    title: 'L\'OISEAU RARE',
                    slug: 'oiseau-rare',
                    image_url: 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=800',
                    venue_name: 'Entre Nous Bar'
                  },
                  ticket_type: {
                    id: 1,
                    name: 'Standard',
                    description: 'Accès standard'
                  },
                  buyer: {
                    name: 'John Doe',
                    email: email || 'john@example.com'
                  },
                  issued_at: '27/07/2025 10:00:00',
                  used_at: null,
                  schedule: {
                    starts_at: '27/07/2025 20:00:00',
                    ends_at: '27/07/2025 23:00:00',
                    door_time: '27/07/2025 19:30:00'
                  }
                }]
              }
            })
          } else {
            reject(new Error('Aucun ticket trouvé avec ces informations'))
          }
        }, 1500)
      })
    }
    
    return Promise.reject(new Error('Veuillez fournir au moins un critère de recherche'))
  }
}

export const eventService = {
  // Récupérer tous les événements
  getEvents(filters = {}) {
    return api.get('/events', { params: filters })
  },

  // Récupérer un événement par ID
  getEvent(id) {
    return api.get(`/events/${id}`)
  },

  // Récupérer les statistiques de scan d'un événement
  getScanStats(id) {
    return api.get(`/events/${id}/scan-stats`)
  }
}

export const orderService = {
  // Créer une nouvelle commande
  createOrder(orderData) {
    return api.post('/orders', orderData)
  },

  // Récupérer les commandes de l'utilisateur
  getOrders() {
    return api.get('/orders')
  },

  // Récupérer une commande spécifique
  getOrder(id) {
    return api.get(`/orders/${id}`)
  },

  // Traiter le paiement d'une commande
  processPayment(orderId, paymentData) {
    return api.post(`/orders/${orderId}/pay`, paymentData)
  },

  // Récupérer les tickets de l'utilisateur
  getMyTickets() {
    return api.get('/orders')
  }
}

export const ticketApiService = {
  // Récupérer les tickets de l'utilisateur connecté
  getMyTickets(filters = {}) {
    return api.get('/orders', { params: filters })
  },
  
  // Récupérer les détails d'un ticket spécifique
  getTicketDetails(ticketId) {
    return api.get(`/tickets/${ticketId}`)
  }
}

export const authService = {
  // Connexion
  login(credentials) {
    return api.post('/auth/login', credentials)
  },

  // Inscription
  register(userData) {
    return api.post('/auth/register', userData)
  },

  // Déconnexion
  logout() {
    return api.post('/auth/logout')
  },

  // Récupérer les infos de l'utilisateur connecté
  getUser() {
    return api.get('/auth/me')
  },

  // Rafraîchir le token
  refresh() {
    return api.post('/auth/refresh')
  }
}

export const paymentService = {
  // Récupérer les paiements
  getPayments() {
    return api.get('/payments')
  },

  // Récupérer un paiement spécifique
  getPayment(id) {
    return api.get(`/payments/${id}`)
  },

  // Vérifier le statut d'un paiement
  getPaymentStatus(id) {
    return api.get(`/payments/${id}/status`)
  }
}

export const qrCodeService = {
  // Générer un QR code sécurisé pour un ticket
  generateSecureQR(ticketId) {
    return api.get(`/qrcodes/tickets/${ticketId}/secure`)
  },

  // Analyser un QR code
  analyzeQRCode(qrCodeData) {
    return api.post('/qrcodes/analyze', { qr_code: qrCodeData })
  },

  // Comparer les formats de QR code
  compareQRFormats(ticketId) {
    return api.get(`/qrcodes/tickets/${ticketId}/compare`)
  }
}

export const guestService = {
  // Créer une commande invité (sans authentification)
  createGuestOrder(orderData) {
    return axios.post('/api/guest/orders', orderData, {
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
  },

  // Récupérer une commande invité par référence
  getGuestOrder(reference) {
    return axios.get(`/api/guest/orders/${reference}`, {
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
  },

  // Récupérer un billet invité par code
  getGuestTicket(code) {
    return axios.get(`/api/guest/tickets/${code}`, {
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
  },

  // Récupérer tous les billets d'un email invité
  retrieveGuestTickets(email) {
    return axios.get(`/api/guest/tickets/retrieve/${encodeURIComponent(email)}`, {
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
  }
}

export const organizerService = {
  // Récupérer les statistiques du tableau de bord
  getDashboardStats() {
    return api.get('/organizer/dashboard/stats')
  },

  // Récupérer les événements récents de l'organisateur
  getRecentEvents() {
    return api.get('/organizer/events/recent')
  },

  // Récupérer les notifications de l'organisateur
  getNotifications() {
    return api.get('/organizer/notifications')
  },

  // Récupérer tous les événements de l'organisateur
  getEvents(filters = {}) {
    return api.get('/organizer/events', { params: filters })
  },

  // Créer un nouvel événement
  createEvent(eventData) {
    return api.post('/organizer/events', eventData)
  },

  // Créer un nouvel événement avec fichier
  createEventWithFile(formData) {
    return api.post('/organizer/events', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
  },

  // Mettre à jour un événement
  updateEvent(id, eventData) {
    return api.put(`/organizer/events/${id}`, eventData)
  },

  // Mettre à jour un événement avec fichier
  updateEventWithFile(id, formData) {
    // Ajouter _method pour Laravel method spoofing avec PUT et FormData
    formData.append('_method', 'PUT');
    return api.post(`/organizer/events/${id}`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
  },

  // Récupérer les détails d'un événement
  getEvent(id) {
    return api.get(`/organizer/events/${id}`)
  },

  // Récupérer les statistiques d'un événement
  getEventStats(id) {
    return api.get(`/organizer/events/${id}/stats`)
  },

  // Récupérer le solde et les transactions
  getBalance() {
    return api.get('/organizer/balances')
  },

  // Récupérer l'historique des paiements
  getPaymentHistory() {
    return api.get('/organizer/payouts')
  },

  // Demander un payout
  requestPayout(payoutData) {
    return api.post('/organizer/payouts', payoutData)
  },

  // Récupérer le profil de l'organisateur
  getProfile() {
    return api.get('/organizer/profile')
  },

  // Mettre à jour le profil de l'organisateur
  updateProfile(profileData) {
    return api.put('/organizer/profile', profileData)
  },

  // Uploader un logo/avatar
  uploadAvatar(formData) {
    return api.post('/organizer/profile/avatar', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
  },

  // Récupérer les catégories
  getCategories() {
    return api.get('/categories')
  },

  // Récupérer les lieux/venues
  getVenues() {
    return api.get('/venues')
  }
}

export const clientService = {
  // Récupérer le profil client
  getProfile() {
    return api.get('/profile')
  },

  // Mettre à jour le profil client
  updateProfile(profileData) {
    return api.put('/profile', profileData)
  },

  // Uploader un avatar
  uploadAvatar(formData) {
    return api.post('/profile/avatar', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
  }
}

// Export par défaut de l'instance axios configurée
export default api