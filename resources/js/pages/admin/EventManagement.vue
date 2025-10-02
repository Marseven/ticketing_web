<template>
  <div class="event-management p-6 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 mb-2">Gestion des Événements</h1>
          <p class="text-gray-600">Créer et gérer tous les événements de la plateforme</p>
        </div>
        <button @click="openCreateModal" 
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
          <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
          Créer Événement
        </button>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="p-2 bg-blue-100 rounded-lg">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm text-gray-600">Total Événements</p>
            <p class="text-2xl font-bold text-blue-600">{{ stats.total_events || 0 }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="p-2 bg-green-100 rounded-lg">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm text-gray-600">Publiés</p>
            <p class="text-2xl font-bold text-green-600">{{ stats.published_events || 0 }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="p-2 bg-yellow-100 rounded-lg">
            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm text-gray-600">Brouillons</p>
            <p class="text-2xl font-bold text-yellow-600">{{ stats.draft_events || 0 }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="p-2 bg-purple-100 rounded-lg">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm text-gray-600">Tickets Vendus</p>
            <p class="text-2xl font-bold text-purple-600">{{ stats.total_tickets_sold || 0 }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
      <h2 class="text-xl font-bold mb-4">Filtres</h2>
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
          <input v-model="filters.search" @input="debouncedSearch" type="text" 
                 placeholder="Titre ou description..." class="w-full border rounded-lg px-3 py-2">
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
          <select v-model="filters.status" @change="loadEvents" class="w-full border rounded-lg px-3 py-2">
            <option value="">Tous les statuts</option>
            <option value="draft">Brouillon</option>
            <option value="published">Publié</option>
            <option value="cancelled">Annulé</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Organisateur</label>
          <select v-model="filters.organizer_id" @change="loadEvents" class="w-full border rounded-lg px-3 py-2">
            <option value="">Tous les organisateurs</option>
            <option v-for="organizer in organizers" :key="organizer.id" :value="organizer.id">
              {{ organizer.name }}
            </option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Catégorie</label>
          <select v-model="filters.category_id" @change="loadEvents" class="w-full border rounded-lg px-3 py-2">
            <option value="">Toutes les catégories</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </select>
        </div>
        
        <div class="flex items-end">
          <button @click="resetFilters" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
            Réinitialiser
          </button>
        </div>
      </div>
    </div>

    <!-- Events List -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-6 border-b">
        <h2 class="text-xl font-bold">Liste des Événements</h2>
      </div>
      
      <div v-if="loading" class="p-8 text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-4 text-gray-600">Chargement...</p>
      </div>
      
      <div v-else-if="events.length === 0" class="p-8 text-center text-gray-500">
        Aucun événement trouvé
      </div>
      
      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Événement</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Organisateur</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Catégorie</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tickets</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="event in events" :key="event.id">
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <div class="w-12 h-12 bg-blue-600 text-white rounded-lg flex items-center justify-center font-bold text-lg">
                    {{ event.title.charAt(0).toUpperCase() }}
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">{{ event.title }}</div>
                    <div class="text-sm text-gray-500">{{ event.slug }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 text-sm text-gray-900">{{ event.organizer?.name || '-' }}</td>
              <td class="px-6 py-4 text-sm text-gray-900">{{ event.category?.name || '-' }}</td>
              <td class="px-6 py-4 text-sm text-gray-900">
                {{ event.schedules?.[0] ? formatDateTime(event.schedules[0].starts_at) : 'Non programmé' }}
              </td>
              <td class="px-6 py-4 text-sm text-gray-900">
                <div class="space-y-1">
                  <div>Vendus: {{ event.tickets_sold || 0 }}</div>
                  <div class="text-gray-500">Total: {{ event.total_capacity || 0 }}</div>
                </div>
              </td>
              <td class="px-6 py-4 text-sm">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" 
                      :class="getStatusBadgeClass(event.status)">
                  {{ getStatusName(event.status) }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm space-x-2">
                <button @click="editEvent(event)" class="text-blue-600 hover:text-blue-900">
                  Modifier
                </button>
                <button @click="viewEventDetails(event)" class="text-green-600 hover:text-green-900">
                  Détails
                </button>
                <button @click="duplicateEvent(event)" class="text-purple-600 hover:text-purple-900">
                  Dupliquer
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <div v-if="pagination && pagination.last_page > 1" class="p-6 border-t">
        <div class="flex justify-between items-center">
          <div class="text-sm text-gray-700">
            Affichage {{ pagination.from }} à {{ pagination.to }} sur {{ pagination.total }} événements
          </div>
          <div class="flex space-x-2">
            <button @click="changePage(pagination.current_page - 1)" 
                    :disabled="pagination.current_page <= 1"
                    class="px-3 py-1 border rounded disabled:opacity-50">
              Précédent
            </button>
            <span class="px-3 py-1 text-sm text-gray-600">
              Page {{ pagination.current_page }} sur {{ pagination.last_page }}
            </span>
            <button @click="changePage(pagination.current_page + 1)" 
                    :disabled="pagination.current_page >= pagination.last_page"
                    class="px-3 py-1 border rounded disabled:opacity-50">
              Suivant
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Event Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-4xl max-h-screen overflow-y-auto">
        <h3 class="text-lg font-bold mb-4">{{ editingEvent ? 'Modifier' : 'Créer' }} Événement</h3>
        
        <form @submit.prevent="saveEvent">
          <div class="space-y-6">
            <!-- Basic Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Titre *</label>
                <input v-model="eventForm.title" type="text" required 
                       class="w-full border rounded-lg px-3 py-2">
              </div>
              
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                <textarea v-model="eventForm.description" rows="4" required 
                          class="w-full border rounded-lg px-3 py-2"></textarea>
              </div>

              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Image de l'événement</label>
                <ImageUpload 
                  v-model="eventForm.image"
                  entity-type="events"
                  size="medium"
                  alt-text="Image de l'événement"
                  @change="handleImageChange"
                />
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Organisateur *</label>
                <select v-model="eventForm.organizer_id" required class="w-full border rounded-lg px-3 py-2">
                  <option value="">Sélectionner un organisateur</option>
                  <option v-for="organizer in organizers" :key="organizer.id" :value="organizer.id">
                    {{ organizer.name }}
                  </option>
                </select>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Catégorie *</label>
                <select v-model="eventForm.category_id" required class="w-full border rounded-lg px-3 py-2">
                  <option value="">Sélectionner une catégorie</option>
                  <option v-for="category in categories" :key="category.id" :value="category.id">
                    {{ category.name }}
                  </option>
                </select>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Lieu *</label>
                <div v-if="!showNewVenue">
                  <select v-model="eventForm.venue_id" class="w-full border rounded-lg px-3 py-2">
                    <option value="">Sélectionner un lieu</option>
                    <option v-for="venue in venues" :key="venue.id" :value="venue.id">
                      {{ venue.name }} - {{ venue.city }}
                    </option>
                    <option value="new">+ Ajouter un nouveau lieu</option>
                  </select>
                </div>
                
                <div v-if="showNewVenue || eventForm.venue_id === 'new'" class="space-y-2">
                  <input v-model="eventForm.new_venue_name" type="text" 
                         placeholder="Nom du lieu" class="w-full border rounded-lg px-3 py-2" required>
                  <input v-model="eventForm.new_venue_city" type="text" 
                         placeholder="Ville" class="w-full border rounded-lg px-3 py-2" required>
                  <input v-model="eventForm.new_venue_address" type="text" 
                         placeholder="Adresse" class="w-full border rounded-lg px-3 py-2">
                  <button type="button" @click="cancelNewVenue" 
                          class="text-sm text-blue-600 hover:text-blue-800">
                    Utiliser un lieu existant
                  </button>
                </div>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Statut *</label>
                <select v-model="eventForm.status" required class="w-full border rounded-lg px-3 py-2">
                  <option value="draft">Brouillon</option>
                  <option value="published">Publié</option>
                  <option value="cancelled">Annulé</option>
                </select>
              </div>
            </div>

            <!-- Schedules -->
            <div>
              <h4 class="text-md font-bold mb-3">Programmation</h4>
              <div class="space-y-3">
                <div v-for="(schedule, index) in eventForm.schedules" :key="index" 
                     class="grid grid-cols-1 md:grid-cols-3 gap-3 p-3 border rounded-lg">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date et heure de début</label>
                    <input v-model="schedule.starts_at" type="datetime-local" required 
                           class="w-full border rounded-lg px-3 py-2">
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date et heure de fin</label>
                    <input v-model="schedule.ends_at" type="datetime-local" required 
                           class="w-full border rounded-lg px-3 py-2">
                  </div>
                  
                  <div class="flex items-end">
                    <button type="button" @click="removeSchedule(index)" 
                            class="bg-red-600 text-white px-3 py-2 rounded-lg hover:bg-red-700">
                      Supprimer
                    </button>
                  </div>
                </div>
                
                <button type="button" @click="addSchedule" 
                        class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                  Ajouter une séance
                </button>
              </div>
            </div>

            <!-- Ticket Types -->
            <div>
              <h4 class="text-md font-bold mb-3">Types de Billets</h4>
              <div class="space-y-3">
                <div v-for="(ticketType, index) in eventForm.ticket_types" :key="index" 
                     class="grid grid-cols-1 md:grid-cols-5 gap-3 p-3 border rounded-lg">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                    <input v-model="ticketType.name" type="text" required 
                           class="w-full border rounded-lg px-3 py-2">
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prix (XAF)</label>
                    <input v-model.number="ticketType.price" type="number" min="0" step="0.01" required 
                           class="w-full border rounded-lg px-3 py-2">
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Capacité</label>
                    <input v-model.number="ticketType.capacity" type="number" min="1" required 
                           class="w-full border rounded-lg px-3 py-2">
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <input v-model="ticketType.description" type="text" 
                           class="w-full border rounded-lg px-3 py-2">
                  </div>
                  
                  <div class="flex items-end">
                    <button type="button" @click="removeTicketType(index)" 
                            class="bg-red-600 text-white px-3 py-2 rounded-lg hover:bg-red-700">
                      Supprimer
                    </button>
                  </div>
                </div>
                
                <button type="button" @click="addTicketType" 
                        class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                  Ajouter un type de billet
                </button>
              </div>
            </div>
          </div>
          
          <div class="flex justify-end space-x-3 mt-6">
            <button type="button" @click="showModal = false" 
                    class="px-4 py-2 text-gray-600 hover:text-gray-800">
              Annuler
            </button>
            <button type="submit" :disabled="saving"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50">
              {{ saving ? 'Enregistrement...' : (editingEvent ? 'Mettre à jour' : 'Créer') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Event Details Modal -->
    <div v-if="showDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-4xl max-h-screen overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-bold">Détails de l'événement</h3>
          <button @click="showDetailsModal = false" class="text-gray-600 hover:text-gray-800">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        
        <div v-if="selectedEvent" class="space-y-6">
          <!-- Event Info -->
          <div class="grid grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700">Titre</label>
              <p class="text-sm font-medium text-gray-900">{{ selectedEvent.title }}</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Statut</label>
              <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" 
                    :class="getStatusBadgeClass(selectedEvent.status)">
                {{ getStatusName(selectedEvent.status) }}
              </span>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Organisateur</label>
              <p class="text-sm text-gray-900">{{ selectedEvent.organizer?.name || '-' }}</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Catégorie</label>
              <p class="text-sm text-gray-900">{{ selectedEvent.category?.name || '-' }}</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Lieu</label>
              <p class="text-sm text-gray-900">{{ selectedEvent.venue?.name || '-' }}</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Date de création</label>
              <p class="text-sm text-gray-900">{{ formatDateTime(selectedEvent.created_at) }}</p>
            </div>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700">Description</label>
            <p class="text-sm text-gray-900">{{ selectedEvent.description }}</p>
          </div>

          <!-- Schedules -->
          <div v-if="selectedEvent.schedules && selectedEvent.schedules.length > 0">
            <label class="block text-sm font-medium text-gray-700 mb-2">Programmation</label>
            <div class="space-y-2">
              <div v-for="schedule in selectedEvent.schedules" :key="schedule.id" 
                   class="bg-gray-50 p-3 rounded-lg">
                <p class="text-sm">Du {{ formatDateTime(schedule.starts_at) }} au {{ formatDateTime(schedule.ends_at) }}</p>
              </div>
            </div>
          </div>

          <!-- Ticket Types -->
          <div v-if="(selectedEvent.ticketTypes || selectedEvent.ticket_types) && (selectedEvent.ticketTypes?.length > 0 || selectedEvent.ticket_types?.length > 0)">
            <label class="block text-sm font-medium text-gray-700 mb-2">Types de billets</label>
            <div class="overflow-x-auto">
              <table class="w-full text-sm border border-gray-200 rounded-lg">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prix</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Capacité</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="ticketType in (selectedEvent.ticketTypes || selectedEvent.ticket_types)" :key="ticketType.id" class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-900">{{ ticketType.name }}</td>
                    <td class="px-4 py-3 text-gray-900">{{ formatAmount(ticketType.price) }} XAF</td>
                    <td class="px-4 py-3 text-gray-900">{{ getTicketCapacity(ticketType) }}</td>
                    <td class="px-4 py-3">
                      <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" 
                            :class="ticketType.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                        {{ ticketType.status === 'active' ? 'Actif' : 'Inactif' }}
                      </span>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ ticketType.description || '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          
          <!-- No Ticket Types Message -->
          <div v-else>
            <label class="block text-sm font-medium text-gray-700 mb-2">Types de billets</label>
            <div class="bg-gray-50 p-4 rounded-lg text-center">
              <p class="text-gray-500">Aucun type de billet défini pour cet événement</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted, watch } from 'vue'

import ImageUpload from '../../components/ImageUpload.vue'

export default {
  name: 'EventManagement',
  components: {
    ImageUpload
  },
  setup() {
    // État réactif
    const loading = ref(false)
    const saving = ref(false)
    const showModal = ref(false)
    const showDetailsModal = ref(false)
    const editingEvent = ref(null)
    const selectedEvent = ref(null)
    
    const events = ref([])
    const organizers = ref([])
    const categories = ref([])
    const venues = ref([])
    const pagination = ref(null)
    
    const stats = reactive({
      total_events: 0,
      published_events: 0,
      draft_events: 0,
      total_tickets_sold: 0
    })
    
    const filters = reactive({
      search: '',
      status: '',
      organizer_id: '',
      category_id: '',
      page: 1
    })
    
    const eventForm = reactive({
      title: '',
      description: '',
      organizer_id: '',
      category_id: '',
      venue_id: '',
      status: 'draft',
      image: {},
      schedules: [],
      ticket_types: [],
      // Pour un nouveau lieu
      new_venue_name: '',
      new_venue_city: '',
      new_venue_address: ''
    })
    
    const showNewVenue = ref(false)

    let searchTimeout = null

    // Méthodes
    const loadEvents = async () => {
      loading.value = true
      try {
        const queryParams = new URLSearchParams()
        Object.keys(filters).forEach(key => {
          if (filters[key] && filters[key] !== '') {
            queryParams.append(key, filters[key])
          }
        })
        
        const response = await fetch(`/api/v1/admin/events?${queryParams}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
          }
        })
        
        const data = await response.json()
        if (data.success) {
          events.value = data.data.events.data
          pagination.value = {
            current_page: data.data.events.current_page,
            last_page: data.data.events.last_page,
            from: data.data.events.from,
            to: data.data.events.to,
            total: data.data.events.total
          }
          
          // Calculer les stats
          const allEvents = data.data.events.data
          stats.total_events = pagination.value.total
          stats.published_events = allEvents.filter(e => e.status === 'published').length
          stats.draft_events = allEvents.filter(e => e.status === 'draft').length
          stats.total_tickets_sold = allEvents.reduce((sum, e) => sum + (e.tickets_sold || 0), 0)
        }
      } catch (error) {
        console.error('Erreur chargement événements:', error)
      } finally {
        loading.value = false
      }
    }

    const loadOrganizers = async () => {
      try {
        const response = await fetch('/api/v1/admin/organizers', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
          }
        })
        
        const data = await response.json()
        if (data.success) {
          organizers.value = data.data.organizers.data || data.data.organizers
        }
      } catch (error) {
        console.error('Erreur chargement organisateurs:', error)
      }
    }

    const loadCategories = async () => {
      try {
        const response = await fetch('/api/v1/categories', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
          }
        })
        
        const data = await response.json()
        console.log('Categories response:', data)
        if (data.success) {
          categories.value = data.categories || data.data
          console.log('Categories loaded:', categories.value)
        }
      } catch (error) {
        console.error('Erreur chargement catégories:', error)
      }
    }

    const loadVenues = async () => {
      try {
        const response = await fetch('/api/v1/venues', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
          }
        })
        
        const data = await response.json()
        if (data.success) {
          venues.value = data.venues || data.data
        }
      } catch (error) {
        console.error('Erreur chargement lieux:', error)
        venues.value = []
      }
    }

    const debouncedSearch = () => {
      clearTimeout(searchTimeout)
      searchTimeout = setTimeout(() => {
        filters.page = 1
        loadEvents()
      }, 500)
    }

    const changePage = (page) => {
      if (page >= 1 && page <= pagination.value.last_page) {
        filters.page = page
        loadEvents()
      }
    }

    const resetFilters = () => {
      Object.assign(filters, {
        search: '',
        status: '',
        organizer_id: '',
        category_id: '',
        page: 1
      })
      loadEvents()
    }

    const openCreateModal = () => {
      editingEvent.value = null
      Object.assign(eventForm, {
        title: '',
        description: '',
        organizer_id: '',
        category_id: '',
        venue_id: '',
        status: 'draft',
        image: {},
        new_venue_name: '',
        new_venue_city: '',
        new_venue_address: '',
        schedules: [{ starts_at: '', ends_at: '' }],
        ticket_types: [{ name: '', price: 0, capacity: 0, description: '' }]
      })
      showNewVenue.value = false
      showModal.value = true
    }

    const editEvent = async (event) => {
      try {
        const response = await fetch(`/api/v1/admin/events/${event.id}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        
        const data = await response.json()
        if (data.success) {
          editingEvent.value = data.data.event
          
          // Préparer les données d'image
          let imageData = {}
          if (data.data.event.image_url) {
            imageData.url = data.data.event.image_url
          } else if (data.data.event.image_file) {
            imageData.filename = data.data.event.image_file
          }

          Object.assign(eventForm, {
            title: data.data.event.title,
            description: data.data.event.description,
            organizer_id: data.data.event.organizer_id,
            category_id: data.data.event.category_id,
            venue_id: data.data.event.venue_id,
            status: data.data.event.status,
            image: imageData,
            schedules: data.data.event.schedules?.map(s => ({
              starts_at: s.starts_at ? s.starts_at.slice(0, 16) : '',
              ends_at: s.ends_at ? s.ends_at.slice(0, 16) : ''
            })) || [{ starts_at: '', ends_at: '' }],
            ticket_types: (data.data.event.ticket_types || data.data.event.ticketTypes || []).map(t => ({
              name: t.name,
              price: t.price,
              capacity: t.capacity || t.available_quantity || t.max_quantity || t.quantity || 0,
              description: t.description || ''
            }))
          })
          
          // Assurer qu'il y a au moins un type de billet vide si aucun n'existe
          if (!eventForm.ticket_types || eventForm.ticket_types.length === 0) {
            eventForm.ticket_types = [{ name: '', price: 0, capacity: 0, description: '' }]
          }
          
          showModal.value = true
        }
      } catch (error) {
        console.error('Erreur chargement événement:', error)
      }
    }

    const saveEvent = async () => {
      saving.value = true
      try {
        const url = editingEvent.value 
          ? `/api/v1/admin/events/${editingEvent.value.id}`
          : '/api/v1/admin/events'
        
        const method = editingEvent.value ? 'PUT' : 'POST'
        
        // Préparer les données avec les images
        const formData = { ...eventForm }
        
        // Gérer les données d'image
        if (eventForm.image.url) {
          formData.image_url = eventForm.image.url
          formData.image_file = null
        } else if (eventForm.image.filename) {
          formData.image_file = eventForm.image.filename
          formData.image_url = null
        }
        
        // Nettoyer les données
        delete formData.image
        
        const response = await fetch(url, {
          method,
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify(formData)
        })
        
        const data = await response.json()
        if (data.success) {
          Swal.fire({
            icon: 'success',
            title: 'Succès',
            text: editingEvent.value ? 'Événement mis à jour avec succès' : 'Événement créé avec succès'
          })
          showModal.value = false
          loadEvents()
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: data.message || 'Erreur lors de la sauvegarde'
          })
        }
      } catch (error) {
        console.error('Erreur sauvegarde événement:', error)
        Swal.fire({
          icon: 'error',
          title: 'Erreur technique',
          text: 'Une erreur est survenue lors de la sauvegarde'
        })
      } finally {
        saving.value = false
      }
    }

    const viewEventDetails = async (event) => {
      try {
        const response = await fetch(`/api/v1/admin/events/${event.id}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        
        const data = await response.json()
        if (data.success) {
          selectedEvent.value = data.data.event
          showDetailsModal.value = true
        }
      } catch (error) {
        console.error('Erreur chargement détails événement:', error)
      }
    }

    const duplicateEvent = async (event) => {
      try {
        // D'abord récupérer les détails complets de l'événement
        const response = await fetch(`/api/v1/admin/events/${event.id}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        
        const data = await response.json()
        if (data.success) {
          const eventData = data.data.event
          editingEvent.value = null
          
          // Remplir le formulaire avec toutes les données de l'événement
          Object.assign(eventForm, {
            title: `${eventData.title} (Copie)`,
            description: eventData.description || '',
            organizer_id: eventData.organizer_id,
            category_id: eventData.category_id,
            venue_id: eventData.venue_id,
            status: 'draft',
            new_venue_name: '',
            new_venue_city: '',
            new_venue_address: '',
            schedules: eventData.schedules?.map(s => ({
              starts_at: s.starts_at ? s.starts_at.slice(0, 16) : '',
              ends_at: s.ends_at ? s.ends_at.slice(0, 16) : ''
            })) || [{ starts_at: '', ends_at: '' }],
            ticket_types: (eventData.ticket_types || eventData.ticketTypes || []).map(t => ({
              name: t.name,
              price: t.price,
              capacity: t.capacity || t.available_quantity || t.max_quantity || t.quantity || 0,
              description: t.description || ''
            }))
          })
          
          // Assurer qu'il y a au moins un horaire et un type de billet vide
          if (!eventForm.schedules || eventForm.schedules.length === 0) {
            eventForm.schedules = [{ starts_at: '', ends_at: '' }]
          }
          
          if (!eventForm.ticket_types || eventForm.ticket_types.length === 0) {
            eventForm.ticket_types = [{ name: '', price: 0, capacity: 0, description: '' }]
          }
          
          showNewVenue.value = false
          showModal.value = true
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: 'Impossible de récupérer les détails de l\'événement'
          })
        }
      } catch (error) {
        console.error('Erreur duplication événement:', error)
        Swal.fire({
          icon: 'error',
          title: 'Erreur technique',
          text: 'Une erreur est survenue lors de la duplication'
        })
      }
    }

    const addSchedule = () => {
      eventForm.schedules.push({ starts_at: '', ends_at: '' })
    }

    const removeSchedule = (index) => {
      eventForm.schedules.splice(index, 1)
    }

    const addTicketType = () => {
      eventForm.ticket_types.push({ name: '', price: 0, capacity: 0, description: '' })
    }

    const removeTicketType = (index) => {
      eventForm.ticket_types.splice(index, 1)
    }

    // Utilitaires
    const formatAmount = (amount) => {
      return new Intl.NumberFormat('fr-FR').format(amount)
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

    // Watcher pour gérer la sélection de nouveau lieu
    watch(() => eventForm.venue_id, (newValue) => {
      if (newValue === 'new') {
        showNewVenue.value = true
      }
    })

    const cancelNewVenue = () => {
      showNewVenue.value = false
      eventForm.venue_id = ''
      eventForm.new_venue_name = ''
      eventForm.new_venue_city = ''
      eventForm.new_venue_address = ''
    }

    const handleImageChange = (imageData) => {
      eventForm.image = imageData
    }

    // Lifecycle
    onMounted(() => {
      loadEvents()
      loadOrganizers()
      loadCategories()
      loadVenues()
    })

    return {
      // État
      loading,
      saving,
      showModal,
      showDetailsModal,
      editingEvent,
      selectedEvent,
      events,
      organizers,
      categories,
      venues,
      pagination,
      stats,
      filters,
      eventForm,
      showNewVenue,
      
      // Méthodes
      loadEvents,
      loadOrganizers,
      loadCategories,
      loadVenues,
      debouncedSearch,
      changePage,
      resetFilters,
      openCreateModal,
      editEvent,
      saveEvent,
      viewEventDetails,
      duplicateEvent,
      addSchedule,
      removeSchedule,
      addTicketType,
      removeTicketType,
      cancelNewVenue,
      handleImageChange,
      
      // Utilitaires
      formatAmount,
      formatDateTime,
      getStatusName,
      getStatusBadgeClass,
      getTicketCapacity: (ticket) => {
        return ticket.capacity || ticket.available_quantity || ticket.max_quantity || ticket.quantity || 0;
      },
    }
  }
}
</script>

<style scoped>
.event-management {
  font-family: 'Inter', sans-serif;
}
</style>