<template>
  <div
    :class="[
      'ticket-min bg-white overflow-hidden font-primea relative',
      size === 'small' ? 'rounded-xl' : 'rounded-2xl',
      'shadow-primea-lg'
    ]"
  >
    <div class="flex items-stretch" :class="size === 'small' ? 'ticket-row-sm' : 'ticket-row-lg'">
      <!-- Affiche de l'événement (gauche) — fond cover : net à l'export -->
      <div
        class="ticket-poster flex-1 bg-primea-gradient bg-center bg-cover"
        :style="posterStyle"
      >
        <div v-if="!ticketImage" class="w-full h-full flex items-center justify-center">
          <span class="text-white/50 text-sm">Affiche</span>
        </div>
      </div>

      <!-- QR Code — case CARRÉE que le QR remplit (peu de blanc autour) -->
      <div class="ticket-qr-cell flex-shrink-0 flex items-center justify-center p-2 bg-white">
        <img
          :src="qrSrc"
          alt="QR Code"
          crossorigin="anonymous"
          class="w-full h-full object-contain"
        />
      </div>

      <!-- Mention verticale (droite) -->
      <div class="flex items-center justify-center px-1 bg-white">
        <p
          class="ticket-vertical-note text-red-600 font-bold tracking-widest"
          :class="size === 'small' ? 'text-[7px]' : 'text-[9px]'"
        >
          QR CODE UNIQUE ET PERSONNEL — NE PAS LE PARTAGER
        </p>
      </div>
    </div>

    <!-- Logo Primea -->
    <img
      src="/images/logo.png?v=3"
      alt="Primea"
      :class="['absolute bottom-2 right-8 opacity-90', size === 'small' ? 'h-4' : 'h-5']"
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

    const posterStyle = computed(() =>
      ticketImage.value ? { backgroundImage: `url("${ticketImage.value}")` } : {}
    )

    const qrSrc = computed(() => {
      if (props.ticket?.qrCode) return props.ticket.qrCode
      const ref = props.ticket?.reference || 'PRIMEA-TICKET'
      return `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(ref)}`
    })

    return { ticketImage, posterStyle, qrSrc }
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

/* La case QR est CARRÉE : le QR la remplit (peu de blanc autour). La rangée
   prend la hauteur de cette case ; l'affiche (flex-1) s'étire pour la remplir
   (html2canvas rend correctement background-size: cover). */
.ticket-row-lg .ticket-qr-cell { width: 210px; height: 210px; }
.ticket-row-sm .ticket-qr-cell { width: 140px; height: 140px; }
.ticket-poster { align-self: stretch; }

/* Mention verticale (de bas en haut) */
.ticket-vertical-note {
  writing-mode: vertical-rl;
  transform: rotate(180deg);
  white-space: nowrap;
  line-height: 1;
  letter-spacing: 0.15em;
}
</style>
