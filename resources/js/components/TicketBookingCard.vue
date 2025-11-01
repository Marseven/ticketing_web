<template>
  <div class="ticket-booking-card bg-white rounded-2xl shadow-lg p-6 sticky top-6">
    <h3 class="text-xl font-semibold mb-4">Prendre vos tickets</h3>
    
    <div v-if="ticketTypes && ticketTypes.length > 0" class="space-y-4">
      <div v-for="ticket in ticketTypes" :key="ticket.id" class="border border-gray-200 rounded-lg p-4">
        <div class="flex justify-between items-start mb-2">
          <div>
            <div class="font-medium">{{ ticket.name }}</div>
            <div class="text-sm text-gray-600">{{ ticket.description }}</div>
          </div>
          <div class="text-right">
            <div class="font-bold text-primea-blue">{{ ticket.price }}€</div>
          </div>
        </div>
        
        <div class="flex items-center justify-between mt-3">
          <div class="flex items-center space-x-3">
            <button @click="decrementQuantity(ticket.id)" class="w-8 h-8 flex items-center justify-center border rounded-full">-</button>
            <span>{{ getQuantity(ticket.id) }}</span>
            <button @click="incrementQuantity(ticket.id)" class="w-8 h-8 flex items-center justify-center border rounded-full">+</button>
          </div>
          <div class="text-sm text-gray-600">{{ ticket.available_quantity }} disponibles</div>
        </div>
      </div>
      
      <div class="border-t pt-4">
        <div class="flex justify-between items-center mb-4">
          <span class="font-semibold">Total:</span>
          <span class="font-bold text-xl text-primea-blue">{{ totalPrice }}€</span>
        </div>
        
        <button 
          @click="proceedToCheckout"
          :disabled="totalQuantity === 0"
          class="w-full bg-primea-blue text-white py-3 rounded-xl font-medium hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Prendre un ticket ({{ totalQuantity }} ticket{{ totalQuantity > 1 ? 's' : '' }})
        </button>
      </div>
    </div>
    
    <div v-else class="text-center py-8 text-gray-600">
      Aucun ticket disponible pour cet événement
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'

export default {
  name: 'TicketBookingCard',
  props: {
    eventId: {
      type: [String, Number],
      required: true
    },
    ticketTypes: {
      type: Array,
      default: () => []
    }
  },
  setup(props) {
    const router = useRouter()
    const quantities = ref({})

    const getQuantity = (ticketId) => quantities.value[ticketId] || 0

    const incrementQuantity = (ticketId) => {
      quantities.value[ticketId] = (quantities.value[ticketId] || 0) + 1
    }

    const decrementQuantity = (ticketId) => {
      if (quantities.value[ticketId] > 0) {
        quantities.value[ticketId]--
      }
    }

    const totalQuantity = computed(() => {
      return Object.values(quantities.value).reduce((total, qty) => total + qty, 0)
    })

    const totalPrice = computed(() => {
      return Object.entries(quantities.value).reduce((total, [ticketId, qty]) => {
        const ticket = props.ticketTypes.find(t => t.id.toString() === ticketId)
        return total + (ticket ? ticket.price * qty : 0)
      }, 0)
    })

    const proceedToCheckout = () => {
      if (totalQuantity.value > 0) {
        router.push(`/checkout/${props.eventId}`)
      }
    }

    return {
      quantities,
      getQuantity,
      incrementQuantity,
      decrementQuantity,
      totalQuantity,
      totalPrice,
      proceedToCheckout
    }
  }
}
</script>