<template>
  <div 
    :class="[
      'ticket-component bg-white rounded-primea-xl overflow-hidden font-primea',
      size === 'small' ? 'max-w-sm' : 'max-w-2xl',
      'shadow-primea-lg'
    ]"
  >
    <!-- Section du haut avec image sans texte -->
    <div class="relative">
      <!-- Image d'arrière-plan simple -->
      <div 
        :class="[
          'relative overflow-hidden bg-primea-gradient',
          size === 'small' ? 'h-40' : 'h-56'
        ]"
      >
        <img 
          v-if="ticket?.event?.image" 
          :src="ticket.event.image" 
          :alt="ticket.event?.title"
          class="w-full h-full object-cover"
        />
        <div class="absolute inset-0 bg-black/30"></div>
      </div>
    </div>

    <!-- Section du bas avec détails -->
    <div 
      :class="[
        'p-4 bg-gray-50 border-t-4 border-dashed border-gray-300',
        size === 'small' ? 'text-sm' : 'text-base'
      ]"
    >
      <div class="flex justify-between items-start">
        <!-- Informations détaillées -->
        <div class="flex-1">
          <!-- Titre de l'événement agrandi -->
          <div class="mb-4">
            <h3 
              :class="[
                'font-bold text-primea-blue mb-2',
                size === 'small' ? 'text-lg' : 'text-3xl'
              ]"
            >
              {{ ticket?.event?.title || "L'OISEAU RARE" }}
            </h3>
          </div>
          
          <!-- Date et lieu -->
          <div class="mb-4 space-y-1">
            <div class="font-semibold text-gray-800">{{ formatEventDate }}</div>
            <div class="text-gray-600 text-sm">À {{ ticket?.event?.venue_name?.toUpperCase() || 'ENTRE NOUS BAR' }}</div>
          </div>
          
          <div class="mb-4">
            <span class="text-sm text-gray-600">Catégorie : </span>
            <span class="font-semibold text-gray-800">{{ ticket?.ticketType || 'standard' }}</span>
          </div>
          
          <div class="mb-4">
            <div 
              :class="[
                'font-bold text-primea-blue',
                size === 'small' ? 'text-lg' : 'text-2xl'
              ]"
            >
              {{ formatPrice(ticket?.price) }} XAF
            </div>
          </div>
          
          <!-- Avertissement -->
          <div class="text-xs text-red-600 leading-tight mb-4 text-justify">
            <strong>*ATTENTION:*</strong>
            CE TICKET EST STRICTEMENT PERSONNEL ET À USAGE UNIQUE. IL NE PEUT ÊTRE NI VENDU NI DONNÉ À AUTRUI SOUS PEINE D'ÊTRE REFUSÉ À L'ENTRÉE.
          </div>
          
          <!-- Logo Primea -->
          <div class="flex justify-start">
            <img src="/images/logo.png" alt="Primea" class="h-6" />
          </div>
        </div>

        <!-- Référence et QR Code -->
        <div 
          :class="[
            'ml-4 text-center',
            size === 'small' ? 'w-20' : 'w-32'
          ]"
        >
          <!-- Référence du ticket au-dessus du QR code -->
          <div class="mb-3">
            <div 
              :class="[
                'font-bold text-primea-blue',
                size === 'small' ? 'text-sm' : 'text-lg'
              ]"
            >
              {{ ticket?.reference || '0001' }}
            </div>
          </div>
          
          <!-- QR Code -->
          <div class="bg-white p-2 border-2 border-gray-300 rounded-primea">
            <img 
              :src="ticket?.qrCode || generateQRCode()" 
              alt="QR Code"
              :class="[
                'object-contain mx-auto',
                size === 'small' ? 'w-16 h-16' : 'w-24 h-24'
              ]"
            />
          </div>
          <p class="text-xs text-gray-500 mt-2 leading-tight">
            Ce QR Code est unique<br>
            et ne peut être scanné<br>
            qu'une seule fois
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue'

export default {
  name: 'TicketComponent',
  props: {
    ticket: {
      type: Object,
      required: true
    },
    size: {
      type: String,
      default: 'large', // 'small' ou 'large'
      validator: (value) => ['small', 'large'].includes(value)
    }
  },
  setup(props) {
    // Computed properties
    const formatEventDate = computed(() => {
      if (!props.ticket?.event?.date) return 'DIMANCHE 27 JUILLET 2025'
      
      const date = new Date(props.ticket.event.date)
      return date.toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
      }).toUpperCase()
    })

    const eventDay = computed(() => {
      if (!props.ticket?.event?.date) return '27'
      
      const date = new Date(props.ticket.event.date)
      return date.getDate()
    })

    const eventMonth = computed(() => {
      if (!props.ticket?.event?.date) return 'JUILLET'
      
      const date = new Date(props.ticket.event.date)
      return date.toLocaleDateString('fr-FR', { month: 'long' }).toUpperCase()
    })

    const eventTime = computed(() => {
      if (!props.ticket?.event?.time) return 'DÈS 13H'
      return `DÈS ${props.ticket.event.time}`
    })

    // Méthodes
    const formatPrice = (price) => {
      if (!price) return '10.000'
      return new Intl.NumberFormat('fr-FR').format(price)
    }

    const generateQRCode = () => {
      // Si le ticket a déjà un QR code, l'utiliser
      if (props.ticket?.qrCode) {
        return props.ticket.qrCode
      }
      
      // Sinon, générer un QR code simple basé sur la référence du ticket
      const ticketRef = props.ticket?.reference || 'PRIMEA-TICKET'
      return `data:image/svg+xml;base64,${btoa(`
        <svg width="100" height="100" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <pattern id="qr" patternUnits="userSpaceOnUse" width="10" height="10">
              <rect width="5" height="5" fill="#000"/>
              <rect x="5" y="5" width="5" height="5" fill="#000"/>
            </pattern>
          </defs>
          <rect width="100" height="100" fill="url(#qr)"/>
          <text x="50" y="50" text-anchor="middle" fill="#333" font-size="8">${ticketRef.slice(-4)}</text>
        </svg>
      `)}`
    }

    return {
      formatEventDate,
      eventDay,
      eventMonth,
      eventTime,
      formatPrice,
      generateQRCode
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
  --font-primary: 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Classes Primea */
.font-primea {
  font-family: var(--font-primary);
}

.text-primea-blue {
  color: var(--primea-blue);
}

.text-primea-yellow {
  color: var(--primea-yellow);
}

.bg-primea-blue {
  background-color: var(--primea-blue);
}

.bg-primea-yellow {
  background-color: var(--primea-yellow);
}

.bg-primea-gradient {
  background: linear-gradient(135deg, var(--primea-blue) 0%, #1a1e47 100%);
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

.shadow-primea-lg {
  box-shadow: 0 8px 30px rgba(39, 45, 99, 0.15);
}

/* Animations et effets */
.ticket-component {
  transition: all 0.3s ease-in-out;
}

.ticket-component:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 40px rgba(39, 45, 99, 0.2);
}

/* Style de découpe de ticket */
.border-dashed {
  border-style: dashed;
}
</style>