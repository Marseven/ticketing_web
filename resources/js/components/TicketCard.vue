<template>
  <div class="ticket-card bg-white rounded-lg shadow-lg overflow-hidden">
    <div class="flex flex-col md:flex-row">
      
      <!-- Image de l'événement -->
      <div class="md:w-1/3">
        <img
          :src="ticket.event.image || 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800'"
          :alt="ticket.event.title"
          class="w-full h-48 md:h-full object-cover"
        />
      </div>

      <!-- Détails du ticket -->
      <div class="flex-1 p-6">
        <div class="flex flex-col h-full justify-between">
          
          <!-- Informations de l'événement -->
          <div>
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-bold text-gray-800">
                {{ ticket.event.title }}
              </h3>
              <span :class="statusClasses" class="px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wide">
                {{ statusText }}
              </span>
            </div>

            <div class="space-y-2 mb-4">
              <div class="flex items-center text-gray-600">
                <CalendarIcon class="w-4 h-4 mr-2" />
                {{ formatDate }}
              </div>
              <div v-if="ticket.event.venue_name" class="flex items-center text-gray-600">
                <MapPinIcon class="w-4 h-4 mr-2" />
                {{ ticket.event.venue_name }}
              </div>
            </div>

            <!-- Détails du ticket -->
            <div class="bg-gray-50 rounded-lg p-4 mb-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <span class="text-xs text-gray-500 block">Référence</span>
                  <span class="font-mono text-sm font-semibold text-blue-600">
                    {{ ticket.reference }}
                  </span>
                </div>
                <div>
                  <span class="text-xs text-gray-500 block">Type</span>
                  <span class="text-sm font-medium">{{ ticket.ticketType }}</span>
                </div>
                <div>
                  <span class="text-xs text-gray-500 block">Prix</span>
                  <span class="text-sm font-bold text-green-600">
                    {{ formatPrice(ticket.price) }} FCFA
                  </span>
                </div>
                <div>
                  <span class="text-xs text-gray-500 block">Status</span>
                  <span :class="statusClasses" class="text-sm font-medium">
                    {{ statusText }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex flex-col sm:flex-row gap-3">
            <button 
              @click="$emit('view', ticket)"
              class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700 transition-colors flex items-center justify-center"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
              Voir le ticket
            </button>
            
            <button 
              @click="$emit('download', ticket)"
              class="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-green-700 transition-colors flex items-center justify-center"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              Télécharger
            </button>
          </div>
        </div>
      </div>

      <!-- QR Code (visible sur desktop) -->
      <div class="hidden md:block w-32 p-6 bg-gray-50 border-l">
        <div class="text-center">
          <div class="bg-white p-2 rounded-lg shadow-sm mb-2">
            <img 
              :src="ticket.qrCode" 
              alt="QR Code"
              class="w-20 h-20 mx-auto"
            />
          </div>
          <span class="text-xs text-gray-500">QR Code</span>
        </div>
      </div>
    </div>

    <!-- QR Code mobile (visible sur mobile) -->
    <div class="md:hidden border-t bg-gray-50 p-4 text-center">
      <div class="bg-white inline-block p-3 rounded-lg shadow-sm">
        <img 
          :src="ticket.qrCode" 
          alt="QR Code"
          class="w-16 h-16"
        />
      </div>
      <p class="text-xs text-gray-500 mt-2">QR Code pour l'entrée</p>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue'
import { CalendarIcon, MapPinIcon } from '@heroicons/vue/24/outline'

export default {
  name: 'TicketCard',
  components: {
    CalendarIcon,
    MapPinIcon
  },
  props: {
    ticket: {
      type: Object,
      required: true
    }
  },
  emits: ['view', 'download'],
  setup(props) {
    
    // Status de la commande
    const statusClasses = computed(() => {
      switch (props.ticket.status) {
        case 'paid':
          return 'bg-green-100 text-green-800'
        case 'pending':
          return 'bg-yellow-100 text-yellow-800'
        case 'failed':
          return 'bg-red-100 text-red-800'
        case 'cancelled':
          return 'bg-red-100 text-red-800'
        case 'refunded':
          return 'bg-gray-100 text-gray-800'
        default:
          return 'bg-blue-100 text-blue-800'
      }
    })

    const statusText = computed(() => {
      switch (props.ticket.status) {
        case 'paid':
          return 'Payé'
        case 'pending':
          return 'En attente'
        case 'failed':
          return 'Échoué'
        case 'cancelled':
          return 'Annulé'
        case 'refunded':
          return 'Remboursé'
        default:
          return 'Inconnu'
      }
    })

    // Format de la date
    const formatDate = computed(() => {
      const date = new Date(props.ticket.event.date)
      return date.toLocaleDateString('fr-FR', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    })

    // Format du prix
    const formatPrice = (price) => {
      return new Intl.NumberFormat('fr-FR').format(price)
    }

    return {
      statusClasses,
      statusText,
      formatDate,
      formatPrice
    }
  }
}
</script>

<style scoped>
.ticket-card {
  transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.ticket-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Classes utilitaires */
.flex {
  display: flex;
}

.flex-col {
  flex-direction: column;
}

.flex-1 {
  flex: 1;
}

.items-center {
  align-items: center;
}

.justify-between {
  justify-content: space-between;
}

.justify-center {
  justify-content: center;
}

.bg-white {
  background-color: #ffffff;
}

.rounded-lg {
  border-radius: 0.5rem;
}

.shadow-lg {
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.overflow-hidden {
  overflow: hidden;
}

.w-full {
  width: 100%;
}

.h-48 {
  height: 12rem;
}

.object-cover {
  object-fit: cover;
}

.p-6 {
  padding: 1.5rem;
}

.p-4 {
  padding: 1rem;
}

.p-2 {
  padding: 0.5rem;
}

.p-3 {
  padding: 0.75rem;
}

.mb-4 {
  margin-bottom: 1rem;
}

.mb-2 {
  margin-bottom: 0.5rem;
}

.mr-2 {
  margin-right: 0.5rem;
}

.mt-2 {
  margin-top: 0.5rem;
}

.text-lg {
  font-size: 1.125rem;
}

.text-sm {
  font-size: 0.875rem;
}

.text-xs {
  font-size: 0.75rem;
}

.font-bold {
  font-weight: 700;
}

.font-semibold {
  font-weight: 600;
}

.font-medium {
  font-weight: 500;
}

.font-mono {
  font-family: ui-monospace, SFMono-Regular, "SF Mono", Consolas, "Liberation Mono", Menlo, monospace;
}

.text-gray-800 {
  color: #1f2937;
}

.text-gray-600 {
  color: #4b5563;
}

.text-gray-500 {
  color: #6b7280;
}

.text-blue-600 {
  color: #2563eb;
}

.text-green-600 {
  color: #059669;
}

.text-white {
  color: #ffffff;
}

.px-3 {
  padding-left: 0.75rem;
  padding-right: 0.75rem;
}

.px-4 {
  padding-left: 1rem;
  padding-right: 1rem;
}

.py-1 {
  padding-top: 0.25rem;
  padding-bottom: 0.25rem;
}

.py-2 {
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
}

.rounded-full {
  border-radius: 9999px;
}

.uppercase {
  text-transform: uppercase;
}

.tracking-wide {
  letter-spacing: 0.025em;
}

.space-y-2 > * + * {
  margin-top: 0.5rem;
}

.bg-gray-50 {
  background-color: #f9fafb;
}

.grid {
  display: grid;
}

.grid-cols-2 {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.gap-4 {
  gap: 1rem;
}

.gap-3 {
  gap: 0.75rem;
}

.block {
  display: block;
}

.bg-blue-600 {
  background-color: #2563eb;
}

.bg-green-600 {
  background-color: #059669;
}

.hover\:bg-blue-700:hover {
  background-color: #1d4ed8;
}

.hover\:bg-green-700:hover {
  background-color: #047857;
}

.transition-colors {
  transition-property: color, background-color, border-color, fill, stroke;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}

.w-4 {
  width: 1rem;
}

.h-4 {
  height: 1rem;
}

.w-20 {
  width: 5rem;
}

.h-20 {
  height: 5rem;
}

.w-16 {
  width: 4rem;
}

.h-16 {
  height: 4rem;
}

.w-32 {
  width: 8rem;
}

.mx-auto {
  margin-left: auto;
  margin-right: auto;
}

.hidden {
  display: none;
}

.border-l {
  border-left-width: 1px;
}

.border-t {
  border-top-width: 1px;
}

.text-center {
  text-align: center;
}

.shadow-sm {
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.inline-block {
  display: inline-block;
}

/* Status colors */
.bg-green-100 {
  background-color: #dcfce7;
}

.text-green-800 {
  color: #166534;
}

.bg-yellow-100 {
  background-color: #fef3c7;
}

.text-yellow-800 {
  color: #854d0e;
}

.bg-gray-100 {
  background-color: #f3f4f6;
}

.text-gray-800 {
  color: #1f2937;
}

.bg-red-100 {
  background-color: #fee2e2;
}

.text-red-800 {
  color: #991b1b;
}

.bg-blue-100 {
  background-color: #dbeafe;
}

.text-blue-800 {
  color: #1e40af;
}

/* Responsive */
@media (min-width: 768px) {
  .md\:flex-row {
    flex-direction: row;
  }
  
  .md\:w-1\/3 {
    width: 33.333333%;
  }
  
  .md\:h-full {
    height: 100%;
  }
  
  .md\:block {
    display: block;
  }
  
  .md\:hidden {
    display: none;
  }
}

@media (min-width: 640px) {
  .sm\:flex-row {
    flex-direction: row;
  }
}
</style>