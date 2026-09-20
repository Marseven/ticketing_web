<template>
  <div
    :class="[
      'ticket-min bg-white overflow-hidden font-primea relative shadow-primea-lg',
      size === 'small' ? 'rounded-xl p-3 pb-7 ticket-sm' : 'rounded-2xl p-4 pb-9 ticket-lg'
    ]"
  >
    <div :class="['flex items-center', size === 'small' ? 'gap-3' : 'gap-4']">
      <!-- Affiche : ENTIÈRE, ratio naturel (jamais rognée), centrée sur la hauteur de la rangée -->
      <div class="ticket-poster-wrap flex-shrink-0 flex items-center justify-center">
        <img
          v-if="ticketImage"
          :src="ticketImage"
          :alt="ticket?.event?.title || 'Affiche'"
          crossorigin="anonymous"
          class="ticket-poster-img rounded-lg"
        />
        <div v-else class="ticket-poster-placeholder flex items-center justify-center bg-primea-gradient rounded-lg">
          <span class="text-white/50 text-sm">Affiche</span>
        </div>
      </div>

      <!-- QR : case carrée, QR ENTIER avec zone blanche (jamais coupé) -->
      <div class="ticket-qr-cell flex-shrink-0 flex items-center justify-center bg-white">
        <img
          :src="qrSrc"
          alt="QR Code"
          crossorigin="anonymous"
          class="ticket-qr-img"
        />
      </div>

      <!-- Mention verticale (droite), contenue dans la hauteur des cases -->
      <div class="ticket-note-col flex-shrink-0 flex items-center justify-center">
        <p class="ticket-vertical-note text-red-600 font-bold">
          QR CODE UNIQUE ET PERSONNEL — NE PAS LE PARTAGER
        </p>
      </div>
    </div>

    <!-- Logo Primea (à gauche de la mention, jamais dessus) -->
    <img
      src="/images/logo.png?v=3"
      alt="Primea"
      :class="['absolute opacity-90', size === 'small' ? 'bottom-1.5 right-3 h-4' : 'bottom-2 right-4 h-5']"
    />
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
    const ticketImage = computed(() =>
      props.ticket?.event?.image || props.ticket?.event?.image_url || null
    )

    const qrSrc = computed(() => {
      if (props.ticket?.qrCode) return props.ticket.qrCode
      const ref = props.ticket?.reference || 'PRIMEA-TICKET'
      return `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(ref)}`
    })

    return { ticketImage, qrSrc }
  }
}
</script>

<style scoped>
.font-primea {
  font-family: 'Inter', 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.bg-primea-gradient {
  background: linear-gradient(135deg, #272d63 0%, #1a1e47 100%);
}

.shadow-primea-lg {
  box-shadow: 0 8px 30px rgba(39, 45, 99, 0.15);
}

/* Dimensions FIXES en px (html2canvas ne gère ni aspect-ratio ni object-fit,
   mais respecte une <img> en width/height auto plafonnée). */

/* Affiche : entière au ratio naturel, plafonnée à la hauteur de la rangée.
   Une affiche carrée remplit exactement la case (= le modèle) ; un banner
   large s'affiche entier, moins haut, centré. */
.ticket-lg .ticket-poster-wrap { height: 200px; }
.ticket-sm .ticket-poster-wrap { height: 130px; }
.ticket-lg .ticket-poster-img { max-height: 200px; max-width: 260px; width: auto; height: auto; }
.ticket-sm .ticket-poster-img { max-height: 130px; max-width: 170px; width: auto; height: auto; }
.ticket-lg .ticket-poster-placeholder { width: 200px; height: 200px; }
.ticket-sm .ticket-poster-placeholder { width: 130px; height: 130px; }

/* QR : case carrée, QR entier + zone blanche. */
.ticket-lg .ticket-qr-cell { width: 200px; height: 200px; }
.ticket-sm .ticket-qr-cell { width: 130px; height: 130px; }
.ticket-lg .ticket-qr-img { width: 184px; height: 184px; }
.ticket-sm .ticket-qr-img { width: 118px; height: 118px; }

/* Mention verticale : contenue dans la hauteur des cases (pas de blanc
   supplémentaire au-dessus/en dessous du QR), ne chevauche jamais le QR. */
.ticket-lg .ticket-note-col { width: 22px; height: 200px; overflow: hidden; }
.ticket-sm .ticket-note-col { width: 16px; height: 130px; overflow: hidden; }
.ticket-vertical-note {
  writing-mode: vertical-rl;
  transform: rotate(180deg);
  white-space: nowrap;
  line-height: 1;
  letter-spacing: 0.02em;
  margin: 0;
}
.ticket-lg .ticket-vertical-note { font-size: 6.5px; }
.ticket-sm .ticket-vertical-note { font-size: 4.5px; }
</style>
