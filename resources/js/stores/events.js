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
        throw new Error(`HTTP error! status: ${response.status}`)
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
      console.error('Error fetching events:', err)
      error.value = err.message || 'Erreur lors du chargement des événements'
      
      // Fallback avec des données d'exemple si l'API échoue
      const sampleEvents = [
        {
          id: 1,
          slug: 'concert-jazz-etoiles',
          title: 'Concert Jazz sous les étoiles',
          description: 'Une soirée musicale exceptionnelle sous un ciel étoilé avec les plus grands artistes de jazz de la région.',
          venue_name: 'Palais de la Culture',
          venue_city: 'Abidjan',
          category_name: 'Musique',
          image_url: 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=400',
          schedules: [
            {
              starts_at: '2025-10-15T20:00:00Z'
            }
          ],
          ticket_types: [
            {
              name: 'Billet Standard',
              price: 25000,
              quantity: 300,
              available_quantity: 268
            },
            {
              name: 'Billet VIP',
              price: 50000,
              quantity: 100,
              available_quantity: 87
            }
          ],
          min_price: 25000,
          is_featured: true
        },
        {
          id: 2,
          slug: 'oiseau-rare',
          title: "L'OISEAU RARE",
          description: 'Concert intimiste dans un cadre exceptionnel',
          venue_name: 'Entre Nous Bar',
          venue_city: 'Abidjan',
          category_name: 'Musique',
          image_url: 'https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?w=400',
          schedules: [
            {
              starts_at: '2025-07-27T20:00:00Z'
            }
          ],
          ticket_types: [
            {
              name: 'Entrée Standard',
              price: 10000,
              quantity: 150,
              available_quantity: 65
            }
          ],
          min_price: 10000,
          is_featured: false
        },
        {
          id: 3,
          slug: 'festival-arts-culture',
          title: 'Festival Arts & Culture',
          description: 'Un festival célébrant la richesse culturelle ivoirienne',
          venue_name: 'Amphithéâtre National',
          venue_city: 'Abidjan',
          category_name: 'Culture',
          image_url: 'https://images.unsplash.com/photo-1459749411175-04bf5292ceea?w=400',
          schedules: [
            {
              starts_at: '2025-09-10T14:00:00Z'
            }
          ],
          ticket_types: [
            {
              name: 'Pass Journée',
              price: 15000,
              quantity: 300,
              available_quantity: 138
            }
          ],
          min_price: 15000,
          is_featured: true
        }
      ];
      
      events.value = sampleEvents
      cities.value = ['Abidjan']
      
      return {
        events: sampleEvents,
        cities: ['Abidjan'],
        total: sampleEvents.length,
        currentPage: 1,
        lastPage: 1
      }
    } finally {
      loading.value = false
    }
  }

  const fetchEvent = async (slug) => {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`/api/client/events/${slug}`, {
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
      currentEvent.value = null
      throw err
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