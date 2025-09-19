<template>
  <div class="physical-sales p-6 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <div class="flex items-center mb-2">
            <button @click="$router.go(-1)" class="text-gray-600 hover:text-gray-800 mr-4">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
              </svg>
            </button>
            <h1 class="text-3xl font-bold text-gray-900">Ventes Physiques</h1>
          </div>
          <p class="text-gray-600">Ajoutez les ventes effectuées en dehors de la plateforme pour maintenir la cohérence des chiffres</p>
        </div>
        <button @click="openAddSaleModal" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
          <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
          Ajouter Vente
        </button>
      </div>
    </div>

    <!-- Event Selection -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
      <h2 class="text-xl font-bold mb-4">Sélectionner un Événement</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Événement</label>
          <select v-model="selectedEventId" @change="loadEventDetails" class="w-full border rounded-lg px-3 py-2">
            <option value="">Choisir un événement</option>
            <option v-for="event in events" :key="event.id" :value="event.id">
              {{ event.title }}
            </option>
          </select>
        </div>
        
        <div v-if="selectedEvent">
          <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
          <span class="inline-flex px-3 py-2 text-sm font-semibold rounded-full" 
                :class="getStatusBadgeClass(selectedEvent.status)">
            {{ getStatusName(selectedEvent.status) }}
          </span>
        </div>
        
        <div v-if="selectedEvent">
          <label class="block text-sm font-medium text-gray-700 mb-2">Prochaine séance</label>
          <p class="text-sm text-gray-900">
            {{ nextSchedule ? formatDateTime(nextSchedule.starts_at) : 'Aucune séance programmée' }}
          </p>
        </div>
      </div>
    </div>

    <!-- Event Details & Stats -->
    <div v-if="selectedEvent" class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
      <!-- Event Summary -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold mb-4">Résumé de l'Événement</h3>
        <div class="space-y-3">
          <div>
            <p class="text-sm text-gray-600">Titre</p>
            <p class="font-medium">{{ selectedEvent.title }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-600">Lieu</p>
            <p class="text-sm">{{ selectedEvent.venue?.name || '-' }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-600">Catégorie</p>
            <p class="text-sm">{{ selectedEvent.category?.name || '-' }}</p>
          </div>
        </div>
      </div>

      <!-- Online Sales Stats -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold mb-4">Ventes en Ligne</h3>
        <div class="space-y-3">
          <div class="flex justify-between">
            <span class="text-sm text-gray-600">Tickets vendus</span>
            <span class="font-medium">{{ onlineStats.tickets_sold }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-sm text-gray-600">Revenus</span>
            <span class="font-medium text-green-600">{{ formatAmount(onlineStats.revenue) }} XAF</span>
          </div>
          <div class="flex justify-between">
            <span class="text-sm text-gray-600">Tickets utilisés</span>
            <span class="font-medium">{{ onlineStats.tickets_used }}</span>
          </div>
        </div>
      </div>

      <!-- Physical Sales Stats -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold mb-4">Ventes Physiques</h3>
        <div class="space-y-3">
          <div class="flex justify-between">
            <span class="text-sm text-gray-600">Tickets vendus</span>
            <span class="font-medium">{{ physicalStats.tickets_sold }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-sm text-gray-600">Revenus</span>
            <span class="font-medium text-blue-600">{{ formatAmount(physicalStats.revenue) }} XAF</span>
          </div>
          <div class="flex justify-between border-t pt-3">
            <span class="text-sm font-medium text-gray-700">Total Général</span>
            <span class="font-bold text-gray-900">{{ formatAmount(onlineStats.revenue + physicalStats.revenue) }} XAF</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Ticket Types Available -->
    <div v-if="selectedEvent && selectedEvent.ticketTypes" class="bg-white rounded-lg shadow p-6 mb-8">
      <h3 class="text-lg font-bold mb-4">Types de Billets Disponibles</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="ticketType in selectedEvent.ticketTypes" :key="ticketType.id" 
             class="border rounded-lg p-4">
          <div class="flex justify-between items-start mb-2">
            <h4 class="font-medium text-gray-900">{{ ticketType.name }}</h4>
            <span class="text-sm font-bold text-green-600">{{ formatAmount(ticketType.price) }} XAF</span>
          </div>
          
          <div class="grid grid-cols-2 gap-2 text-sm mb-3">
            <div>
              <p class="text-gray-600">Capacité</p>
              <p class="font-medium">{{ ticketType.capacity }}</p>
            </div>
            <div>
              <p class="text-gray-600">Vendus (en ligne)</p>
              <p class="font-medium text-blue-600">{{ getOnlineSoldCount(ticketType.id) }}</p>
            </div>
            <div>
              <p class="text-gray-600">Vendus (physique)</p>
              <p class="font-medium text-purple-600">{{ getPhysicalSoldCount(ticketType.id) }}</p>
            </div>
            <div>
              <p class="text-gray-600">Restants</p>
              <p class="font-medium text-gray-900">{{ getRemainingCount(ticketType) }}</p>
            </div>
          </div>
          
          <button @click="quickAddSale(ticketType)" 
                  :disabled="getRemainingCount(ticketType) <= 0"
                  class="w-full bg-blue-600 text-white py-2 px-3 rounded text-sm hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
            {{ getRemainingCount(ticketType) <= 0 ? 'Capacité atteinte' : 'Vente Rapide' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Physical Sales History -->
    <div v-if="selectedEvent" class="bg-white rounded-lg shadow">
      <div class="p-6 border-b">
        <div class="flex justify-between items-center">
          <h3 class="text-lg font-bold">Historique des Ventes Physiques</h3>
          <div class="flex space-x-3">
            <select v-model="filters.ticket_type" @change="loadPhysicalSales" class="border rounded-lg px-3 py-2 text-sm">
              <option value="">Tous les types</option>
              <option v-for="type in selectedEvent.ticketTypes" :key="type.id" :value="type.id">
                {{ type.name }}
              </option>
            </select>
            
            <button @click="exportPhysicalSales" class="bg-green-600 text-white px-3 py-2 rounded text-sm hover:bg-green-700">
              <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
              </svg>
              Export
            </button>
          </div>
        </div>
      </div>
      
      <div v-if="loadingSales" class="p-8 text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-4 text-gray-600">Chargement...</p>
      </div>
      
      <div v-else-if="physicalSales.length === 0" class="p-8 text-center text-gray-500">
        Aucune vente physique enregistrée pour cet événement
      </div>
      
      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type de Billet</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantité</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prix Unitaire</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Point de Vente</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="sale in physicalSales" :key="sale.id">
              <td class="px-6 py-4 text-sm text-gray-900">{{ formatDate(sale.sale_date) }}</td>
              <td class="px-6 py-4 text-sm text-gray-900">{{ sale.ticket_type_name }}</td>
              <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ sale.quantity }}</td>
              <td class="px-6 py-4 text-sm text-gray-900">{{ formatAmount(sale.unit_price) }} XAF</td>
              <td class="px-6 py-4 text-sm font-bold text-gray-900">{{ formatAmount(sale.total_amount) }} XAF</td>
              <td class="px-6 py-4 text-sm text-gray-900">{{ sale.sales_point || '-' }}</td>
              <td class="px-6 py-4 text-sm text-gray-900">
                <span class="max-w-xs truncate block" :title="sale.notes">{{ sale.notes || '-' }}</span>
              </td>
              <td class="px-6 py-4 text-sm space-x-2">
                <button @click="editSale(sale)" class="text-blue-600 hover:text-blue-900">Modifier</button>
                <button @click="deleteSale(sale)" class="text-red-600 hover:text-red-900">Supprimer</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Add/Edit Sale Modal -->
    <div v-if="showSaleModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-bold mb-4">{{ editingSale ? 'Modifier' : 'Ajouter' }} Vente Physique</h3>
        
        <form @submit.prevent="saveSale">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Type de billet *</label>
              <select v-model="saleForm.ticket_type_id" required class="w-full border rounded-lg px-3 py-2">
                <option value="">Sélectionner un type</option>
                <option v-for="type in availableTicketTypes" :key="type.id" :value="type.id">
                  {{ type.name }} - {{ formatAmount(type.price) }} XAF ({{ getRemainingCount(type) }} restants)
                </option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Quantité *</label>
              <input v-model.number="saleForm.quantity" type="number" min="1" :max="maxQuantity" required 
                     class="w-full border rounded-lg px-3 py-2">
              <p v-if="maxQuantity > 0" class="text-xs text-gray-500 mt-1">Maximum: {{ maxQuantity }}</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Prix unitaire (XAF) *</label>
              <input v-model.number="saleForm.unit_price" type="number" min="0" step="0.01" required 
                     class="w-full border rounded-lg px-3 py-2">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Date de vente *</label>
              <input v-model="saleForm.sale_date" type="datetime-local" required 
                     class="w-full border rounded-lg px-3 py-2">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Point de vente</label>
              <input v-model="saleForm.sales_point" type="text" 
                     placeholder="Ex: Entrée principale, Guichet 1..." 
                     class="w-full border rounded-lg px-3 py-2">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
              <textarea v-model="saleForm.notes" rows="3" 
                        placeholder="Informations complémentaires..." 
                        class="w-full border rounded-lg px-3 py-2"></textarea>
            </div>
            
            <div v-if="saleForm.quantity && saleForm.unit_price" class="bg-blue-50 p-3 rounded-lg">
              <p class="text-sm text-blue-800">
                <strong>Total: {{ formatAmount(saleForm.quantity * saleForm.unit_price) }} XAF</strong>
              </p>
            </div>
          </div>
          
          <div class="flex justify-end space-x-3 mt-6">
            <button type="button" @click="showSaleModal = false" 
                    class="px-4 py-2 text-gray-600 hover:text-gray-800">
              Annuler
            </button>
            <button type="submit" :disabled="saving"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50">
              {{ saving ? 'Enregistrement...' : (editingSale ? 'Mettre à jour' : 'Ajouter') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, watch, onMounted } from 'vue'

export default {
  name: 'PhysicalSales',
  setup() {
    // État réactif
    const loading = ref(false)
    const loadingSales = ref(false)
    const saving = ref(false)
    const showSaleModal = ref(false)
    const editingSale = ref(null)
    
    const events = ref([])
    const selectedEventId = ref('')
    const selectedEvent = ref(null)
    const physicalSales = ref([])
    
    const onlineStats = reactive({
      tickets_sold: 0,
      revenue: 0,
      tickets_used: 0
    })
    
    const physicalStats = reactive({
      tickets_sold: 0,
      revenue: 0
    })
    
    const filters = reactive({
      ticket_type: ''
    })
    
    const saleForm = reactive({
      ticket_type_id: '',
      quantity: 1,
      unit_price: 0,
      sale_date: '',
      sales_point: '',
      notes: ''
    })

    // Computed
    const nextSchedule = computed(() => {
      if (!selectedEvent.value?.schedules) return null
      return selectedEvent.value.schedules.find(s => new Date(s.starts_at) > new Date())
    })

    const availableTicketTypes = computed(() => {
      if (!selectedEvent.value?.ticketTypes) return []
      return selectedEvent.value.ticketTypes.filter(type => getRemainingCount(type) > 0)
    })

    const maxQuantity = computed(() => {
      if (!saleForm.ticket_type_id) return 0
      const ticketType = selectedEvent.value?.ticketTypes.find(t => t.id == saleForm.ticket_type_id)
      return ticketType ? getRemainingCount(ticketType) : 0
    })

    // Watchers
    watch(() => saleForm.ticket_type_id, (newTypeId) => {
      if (newTypeId) {
        const ticketType = selectedEvent.value?.ticketTypes.find(t => t.id == newTypeId)
        if (ticketType) {
          saleForm.unit_price = ticketType.price
        }
      }
    })

    // Méthodes
    const loadEvents = async () => {
      loading.value = true
      try {
        const response = await fetch('/api/v1/organizer/events', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        const data = await response.json()
        if (data.success) {
          events.value = data.data.events.data || data.data.events
        }
      } catch (error) {
        console.error('Erreur chargement événements:', error)
      } finally {
        loading.value = false
      }
    }

    const loadEventDetails = async () => {
      if (!selectedEventId.value) {
        selectedEvent.value = null
        return
      }

      try {
        const response = await fetch(`/api/v1/organizer/events/${selectedEventId.value}/sales`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        const data = await response.json()
        if (data.success) {
          selectedEvent.value = data.data.event
          Object.assign(onlineStats, data.data.stats)
          
          loadPhysicalSales()
        }
      } catch (error) {
        console.error('Erreur chargement détails événement:', error)
      }
    }

    const loadPhysicalSales = async () => {
      if (!selectedEventId.value) return
      
      loadingSales.value = true
      try {
        const queryParams = new URLSearchParams()
        if (filters.ticket_type) queryParams.append('ticket_type_id', filters.ticket_type)
        
        const response = await fetch(`/api/v1/organizer/events/${selectedEventId.value}/physical-sales?${queryParams}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        const data = await response.json()
        if (data.success) {
          physicalSales.value = data.data.sales
          Object.assign(physicalStats, data.data.stats)
        }
      } catch (error) {
        console.error('Erreur chargement ventes physiques:', error)
      } finally {
        loadingSales.value = false
      }
    }

    const openAddSaleModal = () => {
      if (!selectedEvent.value) {
        alert('Veuillez d\'abord sélectionner un événement')
        return
      }
      
      editingSale.value = null
      Object.assign(saleForm, {
        ticket_type_id: '',
        quantity: 1,
        unit_price: 0,
        sale_date: new Date().toISOString().slice(0, 16),
        sales_point: '',
        notes: ''
      })
      showSaleModal.value = true
    }

    const quickAddSale = (ticketType) => {
      editingSale.value = null
      Object.assign(saleForm, {
        ticket_type_id: ticketType.id,
        quantity: 1,
        unit_price: ticketType.price,
        sale_date: new Date().toISOString().slice(0, 16),
        sales_point: '',
        notes: ''
      })
      showSaleModal.value = true
    }

    const editSale = (sale) => {
      editingSale.value = sale
      Object.assign(saleForm, {
        ticket_type_id: sale.ticket_type_id,
        quantity: sale.quantity,
        unit_price: sale.unit_price,
        sale_date: new Date(sale.sale_date).toISOString().slice(0, 16),
        sales_point: sale.sales_point || '',
        notes: sale.notes || ''
      })
      showSaleModal.value = true
    }

    const saveSale = async () => {
      saving.value = true
      try {
        const url = editingSale.value 
          ? `/api/v1/organizer/events/${selectedEventId.value}/physical-sales/${editingSale.value.id}`
          : `/api/v1/organizer/events/${selectedEventId.value}/physical-sales`
        
        const method = editingSale.value ? 'PUT' : 'POST'
        
        const response = await fetch(url, {
          method,
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(saleForm)
        })
        
        const data = await response.json()
        if (data.success) {
          alert(editingSale.value ? 'Vente mise à jour avec succès' : 'Vente ajoutée avec succès')
          showSaleModal.value = false
          loadPhysicalSales()
          loadEventDetails() // Recharger pour mettre à jour les stats
        } else {
          alert(data.message || 'Erreur lors de l\'enregistrement')
        }
      } catch (error) {
        console.error('Erreur sauvegarde vente:', error)
        alert('Erreur technique')
      } finally {
        saving.value = false
      }
    }

    const deleteSale = async (sale) => {
      if (!confirm('Êtes-vous sûr de vouloir supprimer cette vente physique ?')) {
        return
      }

      try {
        const response = await fetch(`/api/v1/organizer/events/${selectedEventId.value}/physical-sales/${sale.id}`, {
          method: 'DELETE',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        const data = await response.json()
        if (data.success) {
          alert('Vente supprimée avec succès')
          loadPhysicalSales()
          loadEventDetails()
        } else {
          alert(data.message || 'Erreur lors de la suppression')
        }
      } catch (error) {
        console.error('Erreur suppression vente:', error)
        alert('Erreur technique')
      }
    }

    const exportPhysicalSales = async () => {
      try {
        const queryParams = new URLSearchParams()
        if (filters.ticket_type) queryParams.append('ticket_type_id', filters.ticket_type)
        
        const response = await fetch(`/api/v1/organizer/events/${selectedEventId.value}/physical-sales/export?${queryParams}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        if (response.ok) {
          const blob = await response.blob()
          const url = window.URL.createObjectURL(blob)
          const a = document.createElement('a')
          a.href = url
          a.download = `ventes-physiques-${selectedEvent.value.title}-${new Date().toISOString().split('T')[0]}.csv`
          document.body.appendChild(a)
          a.click()
          window.URL.revokeObjectURL(url)
          document.body.removeChild(a)
        }
      } catch (error) {
        console.error('Erreur export:', error)
        alert('Erreur lors de l\'export des données')
      }
    }

    // Utilitaires
    const getOnlineSoldCount = (ticketTypeId) => {
      // Cette donnée devrait venir de l'API
      const ticketType = selectedEvent.value?.ticketTypes.find(t => t.id === ticketTypeId)
      return ticketType?.online_sold || 0
    }

    const getPhysicalSoldCount = (ticketTypeId) => {
      return physicalSales.value
        .filter(sale => sale.ticket_type_id === ticketTypeId)
        .reduce((sum, sale) => sum + sale.quantity, 0)
    }

    const getRemainingCount = (ticketType) => {
      const onlineSold = getOnlineSoldCount(ticketType.id)
      const physicalSold = getPhysicalSoldCount(ticketType.id)
      return Math.max(0, ticketType.capacity - onlineSold - physicalSold)
    }

    const formatAmount = (amount) => {
      return new Intl.NumberFormat('fr-FR').format(amount)
    }

    const formatDate = (date) => {
      return new Date(date).toLocaleDateString('fr-FR')
    }

    const formatDateTime = (datetime) => {
      return new Date(datetime).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    const getStatusName = (status) => {
      const names = {
        draft: 'Brouillon',
        published: 'Publié',
        cancelled: 'Annulé'
      }
      return names[status] || status
    }

    const getStatusBadgeClass = (status) => {
      const classes = {
        draft: 'bg-gray-100 text-gray-800',
        published: 'bg-green-100 text-green-800',
        cancelled: 'bg-red-100 text-red-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    }

    // Lifecycle
    onMounted(() => {
      loadEvents()
    })

    return {
      // État
      loading,
      loadingSales,
      saving,
      showSaleModal,
      editingSale,
      events,
      selectedEventId,
      selectedEvent,
      physicalSales,
      onlineStats,
      physicalStats,
      filters,
      saleForm,
      nextSchedule,
      availableTicketTypes,
      maxQuantity,
      
      // Méthodes
      loadEvents,
      loadEventDetails,
      loadPhysicalSales,
      openAddSaleModal,
      quickAddSale,
      editSale,
      saveSale,
      deleteSale,
      exportPhysicalSales,
      getOnlineSoldCount,
      getPhysicalSoldCount,
      getRemainingCount,
      
      // Utilitaires
      formatAmount,
      formatDate,
      formatDateTime,
      getStatusName,
      getStatusBadgeClass,
    }
  }
}
</script>

<style scoped>
.physical-sales {
  font-family: 'Inter', sans-serif;
}
</style>