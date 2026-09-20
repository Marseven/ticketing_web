<template>
  <div
    :class="[
      'ticket-component bg-white overflow-hidden font-primea',
      size === 'small' ? 'max-w-sm rounded-xl' : 'max-w-2xl rounded-2xl',
      'shadow-primea-lg'
    ]"
  >
    <!-- Section Image Événement : affiche PLEINE LARGEUR au ratio naturel
         (comme le modèle) — pas de bandes noires, pas de déformation. -->
    <div class="relative bg-primea-gradient">
      <img v-if="ticket?.event?.image" :src="ticket.event.image" :alt="ticket.event?.title"
           crossorigin="anonymous" class="w-full h-auto block" />
      <div v-else :class="['w-full flex items-center justify-center', size === 'small' ? 'h-44' : 'h-64']">
        <span class="text-white/50 text-lg">Image de l'événement</span>
      </div>
    </div>

    <!-- Section Informations (2 colonnes) -->
    <div
      :class="[
        'relative bg-white',
        size === 'small' ? 'p-4' : 'p-6'
      ]"
    >
      <!-- Code du ticket en haut à droite -->
      <div
        :class="[
          'hidden',
          size === 'small' ? 'text-[10px]' : 'text-sm'
        ]"
      >
        {{ ticket?.reference || 'TKT-XXXXXXXX' }}
      </div>

      <div class="flex gap-4">
        <!-- Colonne Gauche : Détails -->
        <div class="flex-1 pr-2">
          <!-- Titre de l'événement -->
          <h3
            :class="[
              'font-bold text-primea-blue uppercase leading-tight mb-3',
              size === 'small' ? 'text-base pr-12' : 'text-xl pr-16'
            ]"
          >
            {{ ticket?.event?.title || "L'OISEAU RARE" }}
            <template v-if="ticket?.event?.venue_name">
              <br />À {{ ticket.event.venue_name.toUpperCase() }}
            </template>
          </h3>

          <!-- Date -->
          <div :class="['text-gray-800 mb-1', size === 'small' ? 'text-xs' : 'text-sm']">
            <span class="font-semibold">{{ formatEventDate }}</span>
          </div>

          <!-- Lieu -->
          <div :class="['text-gray-600 mb-1', size === 'small' ? 'text-xs' : 'text-sm']">
            <span>Lieu : </span>
            <span class="font-medium">{{ ticket?.event?.venue_name || 'Entre Nous Bar' }}</span>
          </div>

          <!-- Catégorie -->
          <div :class="['text-gray-600 mb-3', size === 'small' ? 'text-xs' : 'text-sm']">
            <span>Catégorie : </span>
            <span class="font-medium">{{ ticket?.ticketType || 'standard' }}</span>
          </div>

          <!-- Prix -->
          <div
            :class="[
              'font-bold text-red-600 mb-4',
              size === 'small' ? 'text-xl' : 'text-3xl'
            ]"
          >
            {{ formatPrice(ticket?.price) }} FCFA
          </div>

          <!-- Avertissement -->
          <div
            :class="[
              'border-t border-gray-200 pt-3 mb-3',
              size === 'small' ? 'text-[10px]' : 'text-xs'
            ]"
          >
            <p class="text-red-600 font-bold mb-0.5 tracking-wide">** ATTENTION **</p>
            <p class="text-red-600 leading-tight tracking-normal">
              Ce ticket est strictement personnel et à usage<br />
              unique. Tâchez de ne le remettre à personne.
            </p>
          </div>

          <!-- Logo Primea -->
          <div class="mt-3">
            <img
              src="/images/logo.png?v=3"
              alt="Primea"
              :class="size === 'small' ? 'h-5' : 'h-7'"
            />
            <p :class="['text-gray-400 mt-0.5', size === 'small' ? 'text-[8px]' : 'text-[10px]']">
              Simple, Rapide et Sécurisée
            </p>
          </div>
        </div>

        <!-- Colonne Droite : QR Code -->
        <div
          :class="[
            'flex flex-col items-center justify-start',
            size === 'small' ? 'w-28' : 'w-44'
          ]"
        >
          <!-- Référence centrée au-dessus du QR Code -->
          <div :class="['font-bold text-red-600 font-mono text-center mb-2', size === 'small' ? 'text-xs' : 'text-sm']">
            {{ ticket?.reference || 'TKT-XXXXXXXX' }}
          </div>

          <!-- QR Code -->
          <div class="bg-white">
            <img
              :src="ticket?.qrCode || generateQRCode()"
              alt="QR Code"
              :class="[
                'object-contain',
                size === 'small' ? 'w-24 h-24' : 'w-40 h-40'
              ]"
            />
          </div>

          <!-- Texte QR unique -->
          <div :class="['text-center mt-3', size === 'small' ? 'text-[10px]' : 'text-xs']">
            <p class="text-red-600 font-semibold">Ce QR Code est unique</p>
            <p class="text-gray-500">et ne peut être scanné qu'une seule fois</p>
          </div>
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
      default: 'large',
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

    const eventTime = computed(() => {
      if (!props.ticket?.event?.time) return 'DÈS 13H'
      return `DÈS ${props.ticket.event.time}`
    })

    // Méthodes
    const formatPrice = (price) => {
      if (!price && price !== 0) return '0'
      return new Intl.NumberFormat('fr-FR').format(price)
    }

    const generateQRCode = () => {
      if (props.ticket?.qrCode) {
        return props.ticket.qrCode
      }

      const ticketRef = props.ticket?.reference || 'PRIMEA-TICKET'
      return `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(ticketRef)}`
    }

    return {
      formatEventDate,
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
  --font-primary: 'Inter', 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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

.shadow-primea-lg {
  box-shadow: 0 8px 30px rgba(39, 45, 99, 0.15);
}

/* Image de l'événement : même rendu que la card (image entière + fond flou),
   avec hauteur explicite + dimensionnement par layout → fidèle aussi dans
   l'export html2canvas (qui ne gère ni aspect-ratio ni object-fit). */
.ticket-cover-bg {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  filter: blur(16px);
  transform: scale(1.1);
}
.ticket-cover-main {
  position: absolute;
  inset: 0;
  margin: auto;
  max-width: 100%;
  max-height: 100%;
  width: auto;
  height: auto;
}

/* Animations et effets */
.ticket-component {
  transition: all 0.3s ease-in-out;
}

.ticket-component:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 40px rgba(39, 45, 99, 0.2);
}
</style>
