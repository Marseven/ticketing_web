import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useEventsStore = defineStore('events', () => {
  // État
  const events = ref([])
  const currentEvent = ref(null)
  const loading = ref(false)
  const error = ref(null)
  const cities = ref([])
  const categories = ref(['concert', 'theater', 'sport', 'conference', 'festival'])

  // Getters
  const featuredEvents = computed(() => 
    events.value.filter(event => event.is_featured).slice(0, 6)
  )

  const upcomingEvents = computed(() => {
    const now = new Date()
    return events.value.filter(event => {
      if (event.schedules && event.schedules.length > 0) {
        const eventDate = new Date(event.schedules[0].start_date)
        return eventDate > now
      }
      return true
    })
  })

  const eventsByCategory = computed(() => {
    const grouped = {}
    events.value.forEach(event => {
      if (event.category) {
        if (!grouped[event.category]) {
          grouped[event.category] = []
        }
        grouped[event.category].push(event)
      }
    })
    return grouped
  })

  // Actions
  const fetchEvents = async (params = {}) => {
    loading.value = true
    error.value = null

    try {
      // Construction de l'URL avec les paramètres
      const queryParams = new URLSearchParams()
      
      if (params.search) queryParams.append('search', params.search)
      if (params.category) queryParams.append('category', params.category)
      if (params.city) queryParams.append('city', params.city)
      if (params.date) queryParams.append('date', params.date)
      if (params.price_range) queryParams.append('price_range', params.price_range)
      if (params.sort) queryParams.append('sort', params.sort)
      if (params.page) queryParams.append('page', params.page)

      const url = `/api/client/events${queryParams.toString() ? '?' + queryParams.toString() : ''}`
      
      const response = await fetch(url, {
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })

      if (!response.ok) {
        console.warn(`API not available (${response.status}), using demo data`)
        // Utiliser les données de démonstration si l'API n'est pas disponible
        return getDemoEvents()
      }

      const data = await response.json()
      
      events.value = data.events || data.data || []
      
      // Extraire les villes uniques
      const uniqueCities = [...new Set(events.value.map(event => event.venue_city).filter(Boolean))]
      cities.value = uniqueCities.sort()

      return {
        events: events.value,
        cities: cities.value,
        total: data.total || events.value.length,
        currentPage: data.current_page || 1,
        lastPage: data.last_page || 1
      }

    } catch (err) {
      console.warn('API call failed, using demo data:', err)
      error.value = null // Ne pas afficher d'erreur, utiliser les données de démo
      
      // Données de fallback pour le développement
      return getDemoEvents()
    } finally {
      loading.value = false
    }
  }

  const fetchEvent = async (id) => {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`/api/client/events/${id}`, {
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        }
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const data = await response.json()
      currentEvent.value = data.event

      return data

    } catch (err) {
      console.error('Error fetching event:', err)
      error.value = err.message || 'Erreur lors du chargement de l\'événement'
      
      // Données de fallback pour le développement
      currentEvent.value = getDemoEvent(id)
      return { event: currentEvent.value }
    } finally {
      loading.value = false
    }
  }

  const searchEvents = async (query) => {
    return fetchEvents({ search: query })
  }

  const getEventsByCategory = async (category) => {
    return fetchEvents({ category })
  }

  const bookTickets = async (eventId, tickets) => {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`/api/client/events/${eventId}/book`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ tickets })
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const data = await response.json()
      return data

    } catch (err) {
      console.error('Error booking tickets:', err)
      error.value = err.message || 'Erreur lors de la réservation'
      throw err
    } finally {
      loading.value = false
    }
  }

  const clearError = () => {
    error.value = null
  }

  // Données de démonstration pour le développement
  const getDemoEvents = () => {
    // Calculer les dates pour les prochains mois
    const now = new Date()
    const getDateInMonths = (months, day = 15, hour = 20) => {
      const date = new Date(now)
      date.setMonth(date.getMonth() + months)
      date.setDate(day)
      date.setHours(hour, 0, 0, 0)
      return date.toISOString()
    }

    const demoEvents = [
      // Événements dans 2 mois
      {
        id: 1,
        title: "Festival Électro Gabonais",
        description: "Le plus grand festival de musique électronique du Gabon avec des DJs internationaux et locaux. Une nuit de folie garantie !",
        venue_name: "Stade d'Angondjé",
        venue_address: "Quartier d'Angondjé",
        venue_city: "Libreville",
        category: "festival",
        image_url: "https://images.unsplash.com/photo-1429962714451-bb934ecdc4ec?w=800",
        is_featured: true,
        schedules: [
          {
            start_date: getDateInMonths(2, 15, 20),
            end_date: getDateInMonths(2, 16, 4)
          }
        ],
        ticket_types: [
          {
            id: 1,
            name: "Early Bird",
            description: "Tarif préférentiel - accès général",
            price: 12000,
            quantity: 500,
            sold: 123
          },
          {
            id: 2,
            name: "VIP",
            description: "Accès VIP + backstage + boissons",
            price: 35000,
            quantity: 100,
            sold: 34
          }
        ],
        organizer: {
          name: "Gabon Electronic Music Festival"
        }
      },
      {
        id: 2,
        title: "Concert Afrobeat Live",
        description: "Une soirée dédiée à l'afrobeat avec des artistes locaux et internationaux. Danse et musique garanties !",
        venue_name: "Palais des Sports",
        venue_address: "Boulevard Triomphal",
        venue_city: "Port-Gentil",
        category: "concert",
        image_url: "https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=800",
        is_featured: true,
        schedules: [
          {
            start_date: getDateInMonths(2, 22, 21),
            end_date: getDateInMonths(2, 23, 2)
          }
        ],
        ticket_types: [
          {
            id: 1,
            name: "Standard",
            description: "Accès général",
            price: 15000,
            quantity: 200,
            sold: 45
          },
          {
            id: 2,
            name: "VIP",
            description: "Accès VIP avec boissons incluses",
            price: 35000,
            quantity: 50,
            sold: 12
          }
        ],
        organizer: {
          name: "Gabonese Jazz Association"
        }
      },
      {
        id: 2,
        title: "Festival des Arts Urbains",
        description: "Un festival unique célébrant la culture urbaine avec des spectacles de danse, rap, graffiti et bien plus encore.",
        venue_name: "Place de l'Indépendance",
        venue_address: "Place de l'Indépendance",
        venue_city: "Libreville",
        category: "festival",
        image_url: "https://images.unsplash.com/photo-1493676304819-0d7a8d026dcf?w=800",
        is_featured: true,
        schedules: [
          {
            start_date: "2024-02-20T14:00:00",
            end_date: "2024-02-20T22:00:00"
          }
        ],
        ticket_types: [
          {
            id: 3,
            name: "Jour unique",
            description: "Accès pour toute la journée",
            price: 8000,
            quantity: 500,
            sold: 125
          }
        ],
        organizer: {
          name: "Festival Arts Urbains Gabon"
        }
      },
      {
        id: 3,
        title: "Conférence Tech Innovation 2024",
        description: "La plus grande conférence technologique de l'année avec des experts internationaux partageant les dernières innovations.",
        venue_name: "Centre de Conférences Omar Bongo",
        venue_address: "Avenue du Général de Gaulle",
        venue_city: "Libreville",
        category: "conference",
        image_url: "https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800",
        is_featured: false,
        schedules: [
          {
            start_date: "2024-03-10T09:00:00",
            end_date: "2024-03-10T17:00:00"
          }
        ],
        ticket_types: [
          {
            id: 4,
            name: "Standard",
            description: "Accès aux conférences",
            price: 25000,
            quantity: 300,
            sold: 89
          },
          {
            id: 5,
            name: "Premium",
            description: "Accès aux conférences + networking lunch",
            price: 45000,
            quantity: 100,
            sold: 34
          }
        ],
        organizer: {
          name: "Gabon Tech Hub"
        }
      },
      {
        id: 4,
        title: "Match de Gala - Lions vs Eagles",
        description: "Un match de football spectaculaire entre deux équipes légendaires. Venez supporter votre équipe favorite !",
        venue_name: "Stade d'Angondjé",
        venue_address: "Quartier d'Angondjé",
        venue_city: "Libreville",
        category: "sport",
        image_url: "https://images.unsplash.com/photo-1551698618-1dfe5d97d256?w=800",
        is_featured: true,
        schedules: [
          {
            start_date: "2024-02-25T15:00:00",
            end_date: "2024-02-25T17:00:00"
          }
        ],
        ticket_types: [
          {
            id: 6,
            name: "Tribune populaire",
            description: "Places en tribune populaire",
            price: 5000,
            quantity: 1000,
            sold: 456
          },
          {
            id: 7,
            name: "Tribune VIP",
            description: "Places VIP avec rafraîchissements",
            price: 20000,
            quantity: 200,
            sold: 78
          }
        ],
        organizer: {
          name: "Fédération Gabonaise de Football"
        }
      },
      {
        id: 5,
        title: "Spectacle Théâtral: Les Misérables",
        description: "Une adaptation moderne du chef-d'œuvre de Victor Hugo par la troupe nationale. Une expérience théâtrale inoubliable.",
        venue_name: "Théâtre National",
        venue_address: "Boulevard de l'Indépendance",
        venue_city: "Libreville",
        category: "theater",
        image_url: "https://images.unsplash.com/photo-1507924538820-ede94a04019d?w=800",
        is_featured: false,
        schedules: [
          {
            start_date: "2024-03-05T19:30:00",
            end_date: "2024-03-05T22:00:00"
          }
        ],
        ticket_types: [
          {
            id: 8,
            name: "Orchestre",
            description: "Places d'orchestre",
            price: 12000,
            quantity: 150,
            sold: 67
          },
          {
            id: 9,
            name: "Balcon",
            description: "Places au balcon",
            price: 18000,
            quantity: 80,
            sold: 23
          }
        ],
        organizer: {
          name: "Théâtre National du Gabon"
        }
      },
      {
        id: 6,
        title: "Concert Afrobeat Live",
        description: "Une soirée dédiée à l'afrobeat avec des artistes locaux et internationaux. Danse et musique garanties !",
        venue_name: "Palais des Sports",
        venue_address: "Boulevard Triomphal",
        venue_city: "Port-Gentil",
        category: "concert",
        image_url: "https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=800",
        is_featured: true,
        schedules: [
          {
            start_date: "2024-03-15T21:00:00",
            end_date: "2024-03-16T02:00:00"
          }
        ],
        ticket_types: [
          {
            id: 3,
            name: "Standard",
            description: "Accès général",
            price: 10000,
            quantity: 400,
            sold: 89
          },
          {
            id: 4,
            name: "VIP",
            description: "Accès VIP avec boissons",
            price: 25000,
            quantity: 150,
            sold: 45
          }
        ],
        organizer: {
          name: "Gabon Music Productions"
        }
      },

      // Événements dans 3 mois
      {
        id: 3,
        title: "Théâtre: Les Misérables",
        description: "Une adaptation moderne du chef-d'œuvre de Victor Hugo par la troupe nationale. Une expérience théâtrale inoubliable.",
        venue_name: "Théâtre National",
        venue_address: "Boulevard de l'Indépendance",
        venue_city: "Libreville",
        category: "theater",
        image_url: "https://images.unsplash.com/photo-1507924538820-ede94a04019d?w=800",
        is_featured: true,
        schedules: [
          {
            start_date: getDateInMonths(3, 8, 19),
            end_date: getDateInMonths(3, 8, 22)
          }
        ],
        ticket_types: [
          {
            id: 5,
            name: "Orchestre",
            description: "Places d'orchestre",
            price: 12000,
            quantity: 150,
            sold: 67
          },
          {
            id: 6,
            name: "Balcon",
            description: "Places au balcon",
            price: 18000,
            quantity: 80,
            sold: 23
          }
        ],
        organizer: {
          name: "Théâtre National du Gabon"
        }
      },
      {
        id: 4,
        title: "Conférence Tech Innovation 2025",
        description: "La plus grande conférence technologique de l'année avec des experts internationaux partageant les dernières innovations en IA, blockchain et IoT.",
        venue_name: "Centre de Conférences Omar Bongo",
        venue_address: "Avenue du Général de Gaulle",
        venue_city: "Libreville",
        category: "conference",
        image_url: "https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800",
        is_featured: false,
        schedules: [
          {
            start_date: getDateInMonths(3, 20, 9),
            end_date: getDateInMonths(3, 20, 17)
          }
        ],
        ticket_types: [
          {
            id: 7,
            name: "Standard",
            description: "Accès aux conférences",
            price: 25000,
            quantity: 300,
            sold: 89
          },
          {
            id: 8,
            name: "Premium",
            description: "Accès conférences + networking lunch + workshops",
            price: 45000,
            quantity: 100,
            sold: 34
          }
        ],
        organizer: {
          name: "Gabon Tech Hub"
        }
      },

      // Événements dans 6 mois
      {
        id: 5,
        title: "Festival des Arts Urbains Libreville",
        description: "Un festival unique célébrant la culture urbaine avec des spectacles de danse, rap, graffiti, breakdance et bien plus encore. Street art et performances live !",
        venue_name: "Place de l'Indépendance",
        venue_address: "Place de l'Indépendance",
        venue_city: "Libreville",
        category: "festival",
        image_url: "https://images.unsplash.com/photo-1493676304819-0d7a8d026dcf?w=800",
        is_featured: true,
        schedules: [
          {
            start_date: getDateInMonths(6, 10, 14),
            end_date: getDateInMonths(6, 10, 22)
          }
        ],
        ticket_types: [
          {
            id: 9,
            name: "Pass Journée",
            description: "Accès pour toute la journée",
            price: 8000,
            quantity: 500,
            sold: 125
          },
          {
            id: 10,
            name: "Pass Artiste",
            description: "Accès VIP + rencontre avec les artistes",
            price: 20000,
            quantity: 100,
            sold: 15
          }
        ],
        organizer: {
          name: "Festival Arts Urbains Gabon"
        }
      },
      {
        id: 6,
        title: "Match de Gala - Lions vs Panthères",
        description: "Le match de l'année ! Un affrontement spectaculaire entre les Lions de l'Estuaire et les Panthères du Haut-Ogooué. Venez supporter votre équipe !",
        venue_name: "Stade d'Angondjé",
        venue_address: "Quartier d'Angondjé",
        venue_city: "Libreville",
        category: "sport",
        image_url: "https://images.unsplash.com/photo-1551698618-1dfe5d97d256?w=800",
        is_featured: true,
        schedules: [
          {
            start_date: getDateInMonths(6, 25, 15),
            end_date: getDateInMonths(6, 25, 17)
          }
        ],
        ticket_types: [
          {
            id: 11,
            name: "Tribune populaire",
            description: "Places en tribune populaire",
            price: 5000,
            quantity: 1000,
            sold: 456
          },
          {
            id: 12,
            name: "Tribune VIP",
            description: "Places VIP avec rafraîchissements",
            price: 20000,
            quantity: 200,
            sold: 78
          }
        ],
        organizer: {
          name: "Fédération Gabonaise de Football"
        }
      },
      {
        id: 7,
        title: "Festival de Jazz du Golfe de Guinée",
        description: "Un festival de jazz exceptionnel réunissant les plus grands noms du jazz africain et international. Trois jours de concerts dans un cadre magique.",
        venue_name: "Amphithéâtre de la Marina",
        venue_address: "Boulevard de la Marina",
        venue_city: "Port-Gentil",
        category: "festival",
        image_url: "https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=800",
        is_featured: true,
        schedules: [
          {
            start_date: getDateInMonths(6, 5, 18),
            end_date: getDateInMonths(6, 7, 23)
          }
        ],
        ticket_types: [
          {
            id: 13,
            name: "Pass 1 jour",
            description: "Accès pour une journée au choix",
            price: 15000,
            quantity: 200,
            sold: 45
          },
          {
            id: 14,
            name: "Pass 3 jours",
            description: "Accès pour les 3 jours du festival",
            price: 35000,
            quantity: 150,
            sold: 23
          },
          {
            id: 15,
            name: "VIP 3 jours",
            description: "Accès VIP + rencontres artistes + dîner",
            price: 75000,
            quantity: 50,
            sold: 8
          }
        ],
        organizer: {
          name: "Association Jazz Gabon"
        }
      }
    ]

    events.value = demoEvents
    const uniqueCities = [...new Set(demoEvents.map(event => event.venue_city).filter(Boolean))]
    cities.value = uniqueCities.sort()

    return {
      events: demoEvents,
      cities: cities.value,
      total: demoEvents.length,
      currentPage: 1,
      lastPage: 1
    }
  }

  const getDemoEvent = (id) => {
    const demoEvents = getDemoEvents().events
    return demoEvents.find(event => event.id == id) || demoEvents[0]
  }

  return {
    // État
    events,
    currentEvent,
    loading,
    error,
    cities,
    categories,
    
    // Getters
    featuredEvents,
    upcomingEvents,
    eventsByCategory,
    
    // Actions
    fetchEvents,
    fetchEvent,
    searchEvents,
    getEventsByCategory,
    bookTickets,
    clearError
  }
})