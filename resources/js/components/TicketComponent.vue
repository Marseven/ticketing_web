<template>
  <div
    ref="rootEl"
    :class="[
      'ticket-min bg-white overflow-hidden font-primea relative shadow-primea-lg w-full',
      size === 'small' ? 'rounded-xl p-3' : 'rounded-2xl p-4'
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
          :style="{ width: rowH + 'px', fontSize: noteFont + 'px' }"
        >
          QR CODE UNIQUE ET PERSONNEL — NE PAS LE PARTAGER
        </p>
      </div>
    </div>

    <!-- Bande basse, dans le flux : mentions à gauche, logo à droite.
         En flux et non en absolu : html2canvas place mal un bloc de texte
         ancré par `bottom` (le JPG le rognait alors que l'écran était bon). -->
    <div
      class="flex items-center justify-between"
      :style="{ marginTop: (small ? 8 : 10) + 'px', paddingBottom: (small ? 6 : 8) + 'px', gap: gap + 'px' }"
    >
      <!-- Ni hauteur fixe ni `overflow-hidden` ici : html2canvas pose la ligne
           de base plus bas que le navigateur, et la boîte rognait alors le bas
           des lettres dans le JPG téléchargé. -->
      <div
        class="min-w-0 whitespace-nowrap text-gray-900"
        :style="{ fontSize: infoFont + 'px', lineHeight: infoH + 'px' }"
      >
        <span class="font-bold">{{ typeLabel }}</span>
        <span v-if="priceLabel"> · {{ priceLabel }}</span>
        <span v-if="referenceLabel" class="text-gray-500"> · {{ referenceLabel }}</span>
      </div>

      <img
        src="/images/logo.png?v=3"
        alt="Primea"
        :class="['flex-shrink-0 opacity-90', small ? 'h-4' : 'h-5']"
      />
    </div>
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
      // `qrCode` doit être une image (data URI renvoyée par l'API, ou URL) :
      // certains endpoints exposent un champ `qr_code` qui contient la charge
      // utile du QR, pas une image — l'utiliser en `src` donnerait un billet
      // sans QR. Le service tiers ne reste qu'un dernier recours.
      const q = props.ticket?.qrCode
      if (typeof q === 'string' && (q.startsWith('data:image') || q.startsWith('http'))) return q
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
    // Largeur de la colonne de la mention : de quoi loger 3 lignes pivotées
    // (le cas des petits écrans, où la rangée est basse donc la mention se
    // replie davantage) plus une marge. html2canvas ne coupe pas les lignes
    // exactement comme le navigateur : avec une marge trop juste, le JPG
    // téléchargé perdait le dernier mot alors que l'écran était bon.
    const noteW = small ? 24 : 32
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

    // Le QR occupe toute la hauteur de la rangée : à l'œil il fait alors la même
    // hauteur que l'affiche, la fine marge blanche étant déjà dans le PNG.
    const qrPx = computed(() => Math.max(32, rowH.value))

    // --- Mentions lisibles en bas de billet -------------------------------
    // Ce qu'on lit quand on ne scanne pas : la catégorie achetée, ce qu'elle a
    // coûté, et la référence à citer au support.
    const infoFont = small ? 8 : 11
    // Hauteur de ligne fixée en pixels : html2canvas calcule autrement la
    // hauteur d'un bloc de texte et posait la ligne trop bas, hors de la carte.
    const infoH = small ? 12 : 16

    const typeLabel = computed(() => props.ticket?.ticketType || 'Standard')

    const priceLabel = computed(() => {
      const value = Number(props.ticket?.price)
      if (!Number.isFinite(value)) return ''
      if (value <= 0) return 'Gratuit'
      // Séparateur de milliers posé à la main : `Intl` insère une espace
      // insécable étroite que html2canvas ne rend pas toujours.
      return String(Math.round(value)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' FCFA'
    })

    const referenceLabel = computed(() => props.ticket?.reference || props.ticket?.code || '')

    return {
      ticketImage, qrSrc, rootEl, posterEl, rowH, posterW, qrPx, gap, noteW, noteFont, layout,
      small, pad, infoFont, infoH, typeLabel, priceLabel, referenceLabel
    }
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
}
</style>
