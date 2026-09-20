<template>
  <div
    ref="rootEl"
    :class="[
      'ticket-min bg-white overflow-hidden font-primea relative shadow-primea-lg w-full',
      size === 'small' ? 'rounded-xl p-3 pb-7' : 'rounded-2xl p-4 pb-9'
    ]"
  >
    <!-- Contenu CENTRÉ : affiche (proportions conservées) + QR de même hauteur + mention -->
    <div class="flex items-center justify-center" :style="{ gap: gap + 'px' }">
      <!-- Affiche : ENTIÈRE, proportions conservées, la plus grande possible. Elle donne la hauteur. -->
      <div class="flex-shrink-0 flex items-center justify-center">
        <img
          v-if="ticketImage"
          ref="posterEl"
          :src="ticketImage"
          :alt="ticket?.event?.title || 'Affiche'"
          crossorigin="anonymous"
          class="rounded-lg block"
          :style="{ width: posterW + 'px', height: rowH + 'px' }"
          @load="layout"
        />
        <div
          v-else
          class="flex items-center justify-center bg-primea-gradient rounded-lg"
          :style="{ width: rowH + 'px', height: rowH + 'px' }"
        >
          <span class="text-white/50 text-sm">Affiche</span>
        </div>
      </div>

      <!-- QR : carré de la MÊME hauteur que l'affiche, entier + zone blanche -->
      <div
        class="flex-shrink-0 flex items-center justify-center bg-white"
        :style="{ width: rowH + 'px', height: rowH + 'px' }"
      >
        <img
          :src="qrSrc"
          alt="QR Code"
          crossorigin="anonymous"
          :style="{ width: qrPx + 'px', height: qrPx + 'px' }"
        />
      </div>

      <!-- Mention : NOIRE, CENTRÉE, pivotée, contenue dans la hauteur (1 à 2 lignes) -->
      <div class="relative flex-shrink-0" :style="{ width: noteW + 'px', height: rowH + 'px' }">
        <p
          class="ticket-note text-gray-900 font-bold text-center"
          :style="{ width: rowH + 'px', fontSize: noteFont + 'px', maxHeight: noteW + 'px' }"
        >
          QR CODE UNIQUE ET PERSONNEL — NE PAS LE PARTAGER
        </p>
      </div>
    </div>

    <!-- Logo Primea, dans la bande de marge basse (jamais sur le QR) -->
    <img
      src="/images/logo.png?v=3"
      alt="Primea"
      :class="['absolute opacity-90', size === 'small' ? 'bottom-1.5 right-3 h-4' : 'bottom-2 right-4 h-5']"
    />
  </div>
</template>

<script>
import { computed, ref, onMounted, onBeforeUnmount } from 'vue'

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

    // --- Moteur de mise en page : tout en px explicites (html2canvas) -------
    // L'affiche est la plus grande possible en conservant ses proportions, dans
    // les limites (maxW/maxH) ET la largeur disponible ; le QR prend la même
    // hauteur ; la mention tient dans cette hauteur.
    const small = props.size === 'small'
    const pad = small ? 12 : 16
    const gap = small ? 12 : 16
    const noteW = small ? 16 : 22
    const noteFont = small ? 5 : 7
    const maxW = small ? 180 : 340
    const maxH = small ? 140 : 220

    const rootEl = ref(null)
    const posterEl = ref(null)
    const rowH = ref(small ? 130 : 200)
    const posterW = ref(small ? 130 : 200)

    const layout = () => {
      const img = posterEl.value
      const nw = img?.naturalWidth
      const nh = img?.naturalHeight
      if (!nw || !nh) return
      const r = nw / nh

      // Largeur disponible pour la rangée (carte moins ses marges internes).
      const cardW = rootEl.value?.clientWidth || 0
      const avail = Math.max(0, cardW - 2 * pad - 2 * gap - noteW)

      // Contrainte : affiche W + QR (W/r) doit tenir dans avail.
      let W = maxW
      if (avail > 0) W = Math.min(W, avail / (1 + 1 / r))
      let H = W / r
      if (H > maxH) { H = maxH; W = H * r }

      posterW.value = Math.max(40, Math.round(W))
      rowH.value = Math.max(40, Math.round(H))
    }

    onMounted(() => {
      if (posterEl.value?.complete) layout()
      window.addEventListener('resize', layout)
    })
    onBeforeUnmount(() => window.removeEventListener('resize', layout))

    // QR entier avec une zone blanche (~6 % de chaque côté).
    const qrPx = computed(() => Math.max(32, Math.round(rowH.value * 0.88)))

    return { ticketImage, qrSrc, rootEl, posterEl, rowH, posterW, qrPx, gap, noteW, noteFont, layout }
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

/* Mention : bloc écrit à l'horizontale (1 à 2 lignes, se replie seul quand la
   rangée est basse) puis pivoté de -90° et centré — html2canvas rend bien
   texte replié + transform (contrairement à writing-mode vertical). */
.ticket-note {
  position: absolute;
  top: 50%;
  left: 50%;
  margin: 0;
  transform: translate(-50%, -50%) rotate(-90deg);
  transform-origin: center;
  line-height: 1.25;
  letter-spacing: 0.04em;
  overflow: hidden;
}
</style>
