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
      events.value = []
      cities.value = []
      
      return {
        events: [],
        cities: [],
        total: 0,
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