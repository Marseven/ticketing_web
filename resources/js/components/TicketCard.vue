<template>
  <div class="ticket-wrapper">
    <!-- Vrai design de ticket avec découpe -->
    <div class="ticket-real">
      <!-- Section gauche avec image et infos principales -->
      <div class="ticket-left">
        <!-- Image de l'événement -->
        <div class="event-image-container">
          <img
            :src="ticket.event.image || 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800'"
            :alt="ticket.event.title"
            class="event-image"
          />
          <div class="image-overlay"></div>
        </div>

        <!-- Info principale -->
        <div class="ticket-main-info">
          <h3 class="event-title">{{ ticket.event.title }}</h3>

          <div class="event-details">
            <div class="detail-row">
              <CalendarIcon class="detail-icon" />
              <span class="detail-text">{{ formatDate }}</span>
            </div>
            <div v-if="ticket.event.venue_name" class="detail-row">
              <MapPinIcon class="detail-icon" />
              <span class="detail-text">{{ ticket.event.venue_name }}</span>
            </div>
          </div>

          <!-- Info ticket en grille -->
          <div class="ticket-grid">
            <div class="grid-item">
              <span class="grid-label">Référence</span>
              <span class="grid-value ticket-code">{{ ticket.reference }}</span>
            </div>
            <div class="grid-item">
              <span class="grid-label">Type</span>
              <span class="grid-value">{{ ticket.ticketType }}</span>
            </div>
            <div class="grid-item">
              <span class="grid-label">Prix</span>
              <span class="grid-value price">{{ formatPrice(ticket.price) }} XAF</span>
            </div>
            <div class="grid-item">
              <span class="grid-label">Statut</span>
              <span :class="statusClasses" class="status-badge">{{ statusText }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Ligne de découpe perforée -->
      <div class="ticket-perforation">
        <div class="perforation-line"></div>
      </div>

      <!-- Section droite avec QR code -->
      <div class="ticket-right">
        <div class="qr-section">
          <p class="qr-label">Scan à l'entrée</p>
          <div class="qr-container">
            <img
              :src="ticket.qrCode"
              alt="QR Code"
              class="qr-image"
            />
          </div>
          <p class="qr-code-text">{{ ticket.reference }}</p>
        </div>
      </div>
    </div>

    <!-- Actions en dessous du ticket -->
    <div class="ticket-actions">
      <button
        @click="$emit('view', ticket)"
        class="action-btn btn-view"
      >
        <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
        </svg>
        Voir le ticket
      </button>

      <button
        @click="$emit('download', ticket)"
        class="action-btn btn-download"
      >
        <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        Télécharger PDF
      </button>
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
/* Wrapper du ticket */
.ticket-wrapper {
  margin-bottom: 1.5rem;
}

/* Design du ticket réel */
.ticket-real {
  display: flex;
  background: white;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 10px 40px rgba(39, 45, 99, 0.15);
  transition: all 0.3s ease;
  position: relative;
}

.ticket-real:hover {
  transform: translateY(-4px);
  box-shadow: 0 15px 50px rgba(39, 45, 99, 0.25);
}

/* Section gauche du ticket */
.ticket-left {
  flex: 1;
  display: flex;
  flex-direction: column;
  position: relative;
}

/* Container de l'image */
.event-image-container {
  position: relative;
  height: 200px;
  overflow: hidden;
}

.event-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(to bottom, rgba(39, 45, 99, 0.1), rgba(39, 45, 99, 0.4));
}

/* Info principale du ticket */
.ticket-main-info {
  padding: 1.5rem;
  flex: 1;
}

.event-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #272d63;
  margin-bottom: 1rem;
  font-family: 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.event-details {
  margin-bottom: 1.25rem;
}

.detail-row {
  display: flex;
  align-items: center;
  margin-bottom: 0.5rem;
  color: #4b5563;
}

.detail-icon {
  width: 1.125rem;
  height: 1.125rem;
  margin-right: 0.5rem;
  color: #fab511;
}

.detail-text {
  font-size: 0.9375rem;
}

/* Grille d'informations */
.ticket-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
  background: #f9fafb;
  padding: 1rem;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
}

.grid-item {
  display: flex;
  flex-direction: column;
}

.grid-label {
  font-size: 0.75rem;
  color: #6b7280;
  text-transform: uppercase;
  font-weight: 600;
  letter-spacing: 0.05em;
  margin-bottom: 0.25rem;
}

.grid-value {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #1f2937;
}

.ticket-code {
  font-family: 'Courier New', monospace;
  color: #272d63;
  letter-spacing: 0.05em;
}

.price {
  color: #059669;
  font-size: 1.125rem;
}

/* Ligne de perforation */
.ticket-perforation {
  width: 2px;
  background: white;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.perforation-line {
  width: 100%;
  height: 100%;
  background-image: repeating-linear-gradient(
    to bottom,
    #e5e7eb 0px,
    #e5e7eb 10px,
    transparent 10px,
    transparent 20px
  );
}

.perforation-line::before,
.perforation-line::after {
  content: '';
  position: absolute;
  width: 20px;
  height: 20px;
  background: #f3f4f6;
  border-radius: 50%;
}

.perforation-line::before {
  top: -10px;
}

.perforation-line::after {
  bottom: -10px;
}

/* Section droite avec QR code */
.ticket-right {
  width: 200px;
  background: linear-gradient(135deg, #272d63 0%, #1a1e47 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.qr-section {
  text-align: center;
}

.qr-label {
  color: #fab511;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  margin-bottom: 1rem;
}

.qr-container {
  background: white;
  padding: 0.75rem;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  margin-bottom: 1rem;
}

.qr-image {
  width: 140px;
  height: 140px;
  display: block;
}

.qr-code-text {
  color: white;
  font-size: 0.75rem;
  font-family: 'Courier New', monospace;
  font-weight: 600;
  letter-spacing: 0.05em;
}

/* Badge de statut */
.status-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.bg-green-100 {
  background-color: #dcfce7;
  color: #166534;
}

.bg-yellow-100 {
  background-color: #fef3c7;
  color: #854d0e;
}

.bg-gray-100 {
  background-color: #f3f4f6;
  color: #1f2937;
}

.bg-red-100 {
  background-color: #fee2e2;
  color: #991b1b;
}

.bg-blue-100 {
  background-color: #dbeafe;
  color: #1e40af;
}

/* Actions en dessous du ticket */
.ticket-actions {
  display: flex;
  gap: 1rem;
  margin-top: 1rem;
}

.action-btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.875rem 1.5rem;
  border-radius: 12px;
  font-weight: 600;
  font-size: 0.9375rem;
  font-family: 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  cursor: pointer;
  border: none;
  transition: all 0.2s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.action-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.btn-icon {
  width: 1.25rem;
  height: 1.25rem;
  margin-right: 0.5rem;
}

.btn-view {
  background-color: #272d63;
  color: white;
}

.btn-view:hover {
  background-color: #1a1e47;
}

.btn-download {
  background-color: #fab511;
  color: #272d63;
}

.btn-download:hover {
  background-color: #e09f0e;
}

/* Responsive */
@media (max-width: 768px) {
  .ticket-real {
    flex-direction: column;
  }

  .ticket-perforation {
    display: none;
  }

  .ticket-right {
    width: 100%;
    padding: 2rem;
  }

  .qr-section {
    display: flex;
    align-items: center;
    gap: 1.5rem;
  }

  .qr-label {
    margin-bottom: 0;
  }

  .qr-container {
    margin-bottom: 0;
  }

  .ticket-actions {
    flex-direction: column;
  }

  .event-image-container {
    height: 180px;
  }
}

@media (max-width: 640px) {
  .event-title {
    font-size: 1.25rem;
  }

  .ticket-grid {
    grid-template-columns: 1fr;
    gap: 0.75rem;
  }

  .action-btn {
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
  }
}
</style>