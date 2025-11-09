<template>
  <div class="bg-white p-8 rounded-primea-xl shadow-primea">
    <h2 class="text-2xl font-bold text-primea-blue mb-6 flex items-center gap-3">
      <div class="bg-primea-blue/10 p-2 rounded-primea">
        <svg class="w-6 h-6 text-primea-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
      </div>
      Lieu et accès
    </h2>

    <!-- Informations du lieu -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <!-- Détails du lieu -->
      <div class="space-y-4">
        <div>
          <h3 class="font-semibold text-primea-blue mb-2">Nom du lieu</h3>
          <p class="text-gray-700 text-lg">{{ event.venue_name || 'Lieu à confirmer' }}</p>
        </div>

        <div>
          <h3 class="font-semibold text-primea-blue mb-2">Adresse</h3>
          <p class="text-gray-700">{{ event.venue_address || 'Adresse à confirmer' }}</p>
          <p class="text-gray-700">{{ event.venue_city || 'Ville à confirmer' }}</p>
        </div>

        <!-- Boutons d'action -->
        <div class="flex flex-col sm:flex-row gap-3 mt-6">
          <button
            @click="openInGoogleMaps"
            class="bg-primea-blue text-white px-6 py-3 rounded-primea-lg font-semibold hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200 flex items-center justify-center gap-2"
            style="background-color: #272d63; color: #ffffff;"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
            </svg>
            Voir sur la carte
          </button>

          <button
            @click="getDirections"
            class="bg-white border-2 border-primea-blue text-primea-blue px-6 py-3 rounded-primea-lg font-semibold hover:bg-primea-blue hover:text-white transition-all duration-200 flex items-center justify-center gap-2"
            style="background-color: #ffffff; color: #272d63; border: 2px solid #272d63;"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Itinéraire
          </button>
        </div>
      </div>

      <!-- Carte OpenStreetMap avec Leaflet -->
      <div class="h-64 lg:h-80 rounded-primea-lg overflow-hidden shadow-primea">
        <div
          ref="mapContainer"
          class="w-full h-full"
        >
          <div v-if="!mapLoaded" class="w-full h-full bg-gray-200 flex items-center justify-center text-gray-500">
            <svg class="w-12 h-12 mx-auto mb-2 animate-spin text-primea-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, watch, onUnmounted } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

export default {
  name: 'VenueSection',
  props: {
    event: {
      type: Object,
      required: true
    }
  },
  setup(props) {
    const mapContainer = ref(null)
    const mapLoaded = ref(false)
    let map = null
    let marker = null

    // Coordonnées par défaut (Libreville, Gabon)
    const defaultCoords = { lat: 0.4162, lng: 9.4673 }

    // Icône personnalisée pour le marqueur
    const customIcon = L.divIcon({
      className: 'custom-marker',
      html: `
        <div style="
          width: 40px;
          height: 40px;
          background-color: #272d63;
          border: 3px solid #fab511;
          border-radius: 50% 50% 50% 0;
          transform: rotate(-45deg);
          display: flex;
          align-items: center;
          justify-content: center;
          box-shadow: 0 3px 10px rgba(0,0,0,0.3);
        ">
          <svg style="width: 20px; height: 20px; transform: rotate(45deg);" fill="white" viewBox="0 0 24 24">
            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
          </svg>
        </div>
      `,
      iconSize: [40, 40],
      iconAnchor: [20, 40],
      popupAnchor: [0, -40]
    })

    const initializeMap = async () => {
      if (!mapContainer.value) return

      try {
        const coordinates = await getCoordinates()

        // Créer la carte Leaflet
        map = L.map(mapContainer.value).setView([coordinates.lat, coordinates.lng], 15)

        // Ajouter le layer OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '© OpenStreetMap contributors'
        }).addTo(map)

        // Ajouter un marqueur personnalisé
        marker = L.marker([coordinates.lat, coordinates.lng], {
          icon: customIcon,
          title: props.event.venue_name
        }).addTo(map)

        // Ajouter une popup
        const popupContent = `
          <div style="font-family: 'Inter', 'Myriad Pro', sans-serif; min-width: 200px;">
            <h3 style="color: #272d63; margin: 0 0 8px 0; font-weight: 600; font-size: 16px;">${props.event.venue_name}</h3>
            <p style="margin: 0 0 4px 0; color: #666; font-size: 14px;">${props.event.venue_address}</p>
            <p style="margin: 0; color: #666; font-size: 14px;">${props.event.venue_city}</p>
          </div>
        `
        marker.bindPopup(popupContent)

        // Ouvrir la popup automatiquement
        marker.openPopup()

        mapLoaded.value = true
      } catch (error) {
        console.error('Erreur lors du chargement de la carte:', error)
        mapLoaded.value = true
      }
    }

    const getCoordinates = async () => {
      const address = `${props.event.venue_address}, ${props.event.venue_city}, Gabon`

      try {
        // Utiliser l'API Nominatim d'OpenStreetMap (gratuite)
        const response = await fetch(
          `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1`,
          {
            headers: {
              'User-Agent': 'Primea Ticketing Platform'
            }
          }
        )

        const data = await response.json()

        if (data && data.length > 0) {
          return {
            lat: parseFloat(data[0].lat),
            lng: parseFloat(data[0].lon)
          }
        } else {
          console.warn('Géocodage échoué, utilisation des coordonnées par défaut')
          return defaultCoords
        }
      } catch (error) {
        console.warn('Erreur lors du géocodage:', error)
        return defaultCoords
      }
    }

    const openInGoogleMaps = () => {
      const address = encodeURIComponent(`${props.event.venue_name}, ${props.event.venue_address}, ${props.event.venue_city}`)
      const url = `https://www.google.com/maps/search/?api=1&query=${address}`
      window.open(url, '_blank')
    }

    const getDirections = () => {
      const address = encodeURIComponent(`${props.event.venue_name}, ${props.event.venue_address}, ${props.event.venue_city}`)
      const url = `https://www.google.com/maps/dir/?api=1&destination=${address}`
      window.open(url, '_blank')
    }

    onMounted(async () => {
      // Attendre que le DOM soit prêt
      await new Promise(resolve => setTimeout(resolve, 100))
      await initializeMap()
    })

    onUnmounted(() => {
      // Nettoyer la carte lors de la destruction du composant
      if (map) {
        map.remove()
        map = null
      }
    })

    // Réinitialiser la carte si l'événement change
    watch(() => props.event, async () => {
      if (map) {
        map.remove()
        map = null
        await initializeMap()
      }
    })

    return {
      mapContainer,
      mapLoaded,
      openInGoogleMaps,
      getDirections
    }
  }
}
</script>

<style scoped>
/* Variables CSS Primea */
:root {
  --primea-blue: #272d63;
  --primea-yellow: #fab511;
  --primea-white: #ffffff;
}

.text-primea-blue {
  color: var(--primea-blue);
}

.bg-primea-blue {
  background-color: var(--primea-blue);
}

.hover\:bg-primea-yellow:hover {
  background-color: var(--primea-yellow);
}

.hover\:text-primea-blue:hover {
  color: var(--primea-blue);
}

.hover\:bg-primea-blue:hover {
  background-color: var(--primea-blue);
}

.border-primea-blue {
  border-color: var(--primea-blue);
}

.rounded-primea {
  border-radius: 12px;
}

.rounded-primea-lg {
  border-radius: 16px;
}

.rounded-primea-xl {
  border-radius: 20px;
}

.shadow-primea {
  box-shadow: 0 4px 20px rgba(39, 45, 99, 0.1);
}

/* Animations */
.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.transition-all {
  transition: all 0.2s ease-in-out;
}
</style>