<template>
  <div class="ticket-component bg-white rounded-primea-xl overflow-hidden font-primea max-w-sm shadow-primea-lg">
    <!-- Image de l'événement -->
    <div class="relative">
      <div class="relative overflow-hidden bg-primea-gradient h-40">
        <img
          :src="ticket.event.image || 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800'"
          :alt="ticket.event.title"
          class="w-full h-full object-cover"
        />
        <div class="absolute inset-0 bg-black/30"></div>
      </div>
    </div>

    <!-- Corps du ticket avec bordure pointillée -->
    <div class="p-4 bg-gray-50 border-t-4 border-dashed border-gray-300 text-sm">
      <div class="flex justify-between items-start">
        <!-- Section gauche: Informations -->
        <div class="flex-1">
          <!-- Titre -->
          <div class="mb-4">
            <h3 class="font-bold text-primea-blue mb-2 text-lg">{{ ticket.event.title }}</h3>
          </div>

          <!-- Date et lieu -->
          <div class="mb-4 space-y-1">
            <div class="font-semibold text-gray-800">{{ formatDateUppercase }}</div>
            <div class="text-gray-600 text-sm">À {{ (ticket.event.venue_name || 'LIEU À DÉFINIR').toUpperCase() }}</div>
          </div>

          <!-- Catégorie -->
          <div class="mb-4">
            <span class="text-sm text-gray-600">Catégorie : </span>
            <span class="font-semibold text-gray-800">{{ ticket.ticketType }}</span>
          </div>

          <!-- Prix -->
          <div class="mb-4">
            <div class="font-bold text-primea-blue text-lg">{{ formatPrice(ticket.price) }} XAF</div>
          </div>

          <!-- Message d'avertissement -->
          <div class="text-xs text-red-600 leading-tight mb-4 text-justify">
            <strong>*ATTENTION:*</strong>
            CE TICKET EST STRICTEMENT PERSONNEL ET À USAGE UNIQUE. IL NE PEUT ÊTRE NI VENDU NI DONNÉ À AUTRUI SOUS PEINE D'ÊTRE REFUSÉ À L'ENTRÉE.
          </div>

          <!-- Logo Primea -->
          <div class="flex justify-start">
            <img src="/images/logo.png" alt="Primea" class="h-6" />
          </div>
        </div>

        <!-- Section droite: QR Code -->
        <div class="ml-4 text-center w-20">
          <!-- Code du ticket -->
          <div class="mb-3">
            <div class="font-bold text-primea-blue text-sm">{{ ticket.reference }}</div>
          </div>

          <!-- QR Code -->
          <div class="bg-white p-2 border-2 border-gray-300 rounded-primea">
            <img
              :src="ticket.qrCode"
              alt="QR Code"
              class="object-contain mx-auto w-16 h-16"
            />
          </div>

          <!-- Message sous le QR -->
          <p class="text-xs text-gray-500 mt-2 leading-tight">
            Ce QR Code est unique<br>
            et ne peut être scanné<br>
            qu'une seule fois
          </p>
        </div>
      </div>
    </div>

    <!-- Actions en dessous du ticket -->
    <div class="ticket-actions p-4 bg-white flex gap-3">
      <button
        @click="$emit('view', ticket)"
        class="flex-1 bg-primea-blue text-white py-3 px-4 rounded-primea font-semibold text-sm hover:bg-primea-blue/90 transition-colors duration-200"
      >
        Voir le ticket
      </button>

      <button
        @click="$emit('download', ticket)"
        class="flex-1 bg-primea-yellow text-primea-blue py-3 px-4 rounded-primea font-semibold text-sm hover:bg-primea-yellow/90 transition-colors duration-200"
      >
        Télécharger PDF
      </button>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue'

export default {
  name: 'TicketCard',
  props: {
    ticket: {
      type: Object,
      required: true
    }
  },
  emits: ['view', 'download'],
  setup(props) {

    // Format de la date en majuscules
    const formatDateUppercase = computed(() => {
      const date = new Date(props.ticket.event.date)
      return date.toLocaleDateString('fr-FR', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      }).toUpperCase()
    })

    // Format du prix
    const formatPrice = (price) => {
      return new Intl.NumberFormat('fr-FR').format(price)
    }

    return {
      formatDateUppercase,
      formatPrice
    }
  }
}
</script>

<style scoped>
/* Variables CSS */
:root {
  --primea-blue: #272d63;
  --primea-yellow: #fab511;
}

.text-primea-blue {
  color: var(--primea-blue);
}

.bg-primea-blue {
  background-color: var(--primea-blue);
}

.bg-primea-yellow {
  background-color: var(--primea-yellow);
}

.text-primea-blue {
  color: var(--primea-blue);
}

.bg-primea-gradient {
  background: linear-gradient(135deg, var(--primea-blue) 0%, #1a1f4a 100%);
}

.rounded-primea {
  border-radius: 12px;
}

.rounded-primea-xl {
  border-radius: 20px;
}

.shadow-primea-lg {
  box-shadow: 0 8px 30px rgba(39, 45, 99, 0.15);
}

.font-primea {
  font-family: 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Hover effects */
.ticket-component:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 40px rgba(39, 45, 99, 0.2);
  transition: all 0.3s ease;
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .ticket-component {
    max-width: 100%;
  }
}
</style>
