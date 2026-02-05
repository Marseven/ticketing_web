<template>
  <div
    :class="[
      'ticket-component bg-white overflow-hidden font-primea',
      size === 'small' ? 'max-w-sm rounded-xl' : 'max-w-2xl rounded-2xl',
      'shadow-primea-lg'
    ]"
  >
    <!-- Section Image Événement -->
    <div class="relative">
      <div
        :class="[
          'relative overflow-hidden bg-primea-gradient',
          size === 'small' ? 'h-44' : 'h-64'
        ]"
      >
        <img
          v-if="ticket?.event?.image"
          :src="ticket.event.image"
          :alt="ticket.event?.title"
          class="w-full h-full object-cover"
        />
        <div v-else class="w-full h-full flex items-center justify-center">
          <span class="text-white/50 text-lg">Image de l'événement</span>
        </div>
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
          'absolute top-4 right-4 font-bold text-red-600 font-mono',
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
            <p class="text-red-600 font-bold mb-2 tracking-wide">** ATTENTION **</p>
            <p class="text-gray-500 leading-loose tracking-normal">
              Ce ticket est strictement personnel et à usage<br />
              unique. Tâchez de ne le remettre à personne.
            </p>
          </div>

          <!-- Logo Primea -->
          <div class="mt-3">
            <img
              src="/images/logo.png"
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
          <!-- Espace pour le numéro (déjà positionné en absolu) -->
          <div :class="size === 'small' ? 'h-6' : 'h-8'"></div>

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

    <!-- Section Titulaire (optionnel) -->
    <div
      v-if="ticket?.buyer_name || ticket?.buyer_email"
      :class="[
        'bg-gray-50 border-t-2 border-dashed border-gray-200',
        size === 'small' ? 'px-4 py-3' : 'px-6 py-4'
      ]"
    >
      <p :class="['font-semibold text-primea-blue uppercase mb-2', size === 'small' ? 'text-[10px]' : 'text-xs']">
        Informations du titulaire
      </p>
      <div class="flex justify-between">
        <div :class="size === 'small' ? 'text-xs' : 'text-sm'">
          <span class="text-gray-500">Nom : </span>
          <span class="font-medium text-gray-800">{{ ticket?.buyer_name || 'Non renseigné' }}</span>
        </div>
        <div :class="size === 'small' ? 'text-xs' : 'text-sm'">
          <span class="text-gray-500">Email : </span>
          <span class="font-medium text-gray-800">{{ ticket?.buyer_email || 'Non renseigné' }}</span>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div
      :class="[
        'bg-primea-blue text-white text-center',
        size === 'small' ? 'py-2 px-3' : 'py-3 px-4'
      ]"
    >
      <p :class="['font-mono', size === 'small' ? 'text-[10px]' : 'text-xs']">
        {{ ticket?.reference || 'TKT-XXXXXXXX' }}
      </p>
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

/* Animations et effets */
.ticket-component {
  transition: all 0.3s ease-in-out;
}

.ticket-component:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 40px rgba(39, 45, 99, 0.2);
}
</style>
