<template>
  <div class="ticket-management p-6 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">Tous les Billets</h1>
      <p class="text-gray-600">Rechercher et suivre tous les billets (numériques et physiques) et leur statut de scan.</p>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
      <div class="bg-white rounded-lg shadow p-5">
        <p class="text-sm text-gray-600">Total</p>
        <p class="text-2xl font-bold text-primea-blue">{{ stats.total || 0 }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-5">
        <p class="text-sm text-gray-600">Émis</p>
        <p class="text-2xl font-bold text-green-600">{{ stats.issued || 0 }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-5">
        <p class="text-sm text-gray-600">Scannés</p>
        <p class="text-2xl font-bold text-blue-600">{{ stats.used || 0 }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-5">
        <p class="text-sm text-gray-600">Physiques</p>
        <p class="text-2xl font-bold text-gray-700">{{ stats.physical || 0 }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-5">
        <p class="text-sm text-gray-600">En ligne</p>
        <p class="text-2xl font-bold text-gray-700">{{ stats.online || 0 }}</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
      <h2 class="text-xl font-bold mb-4">Filtres</h2>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
          <input v-model="filters.search" @input="debouncedSearch" type="text"
                 placeholder="Code, référence, client…"
                 class="w-full border rounded-lg px-3 py-2" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Événement</label>
          <select v-model="filters.event_id" @change="applyFilters" class="w-full border rounded-lg px-3 py-2">
            <option value="">Tous les événements</option>
            <option v-for="ev in events" :key="ev.id" :value="ev.id">{{ ev.title }}</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Organisateur</label>
          <select v-model="filters.organizer_id" @change="applyFilters" class="w-full border rounded-lg px-3 py-2">
            <option value="">Tous les organisateurs</option>
            <option v-for="org in organizers" :key="org.id" :value="org.id">{{ org.name }}</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
          <select v-model="filters.status" @change="applyFilters" class="w-full border rounded-lg px-3 py-2">
            <option value="">Tous les statuts</option>
            <option value="issued">Émis</option>
            <option value="used">Utilisé (scanné)</option>
            <option value="cancelled">Annulé</option>
            <option value="refunded">Remboursé</option>
            <option value="void">Annulé (void)</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Provenance</label>
          <select v-model="filters.ticket_source" @change="applyFilters" class="w-full border rounded-lg px-3 py-2">
            <option value="">Toutes</option>
            <option value="online">En ligne</option>
            <option value="physical">Physique</option>
            <option value="comped">Invitation</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Date de début</label>
          <input v-model="filters.date_from" @change="applyFilters" type="date" class="w-full border rounded-lg px-3 py-2" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Date de fin</label>
          <input v-model="filters.date_to" @change="applyFilters" type="date" class="w-full border rounded-lg px-3 py-2" />
        </div>

        <div class="flex items-end">
          <button @click="resetFilters" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-50">
            Réinitialiser
          </button>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow">
      <div v-if="loading" class="p-10 text-center text-gray-500">Chargement…</div>
      <div v-else-if="tickets.length === 0" class="p-10 text-center text-gray-500">Aucun billet trouvé.</div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Événement</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Détenteur</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Provenance</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Commande</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Scanné le</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="t in tickets" :key="t.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 font-mono text-primea-blue">{{ t.code }}</td>
              <td class="px-4 py-3">{{ t.event?.title || '—' }}</td>
              <td class="px-4 py-3">{{ t.ticket_type?.name || '—' }}</td>
              <td class="px-4 py-3">
                <div>{{ t.holder?.name || '—' }}</div>
                <div class="text-xs text-gray-500">{{ t.holder?.email || '' }}</div>
              </td>
              <td class="px-4 py-3">
                <span class="inline-flex px-2 py-1 text-xs rounded-full" :class="sourceBadgeClass(t.source)">
                  {{ sourceLabel(t.source) }}
                </span>
              </td>
              <td class="px-4 py-3">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" :class="ticketStatusBadgeClass(t.status)">
                  {{ ticketStatusLabel(t.status) }}
                </span>
              </td>
              <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ t.order_reference || '—' }}</td>
              <td class="px-4 py-3 text-gray-600">{{ t.used_at ? formatDateTime(t.used_at) : '—' }}</td>
              <td class="px-4 py-3 text-right">
                <button v-if="t.status === 'used'" @click="resetScan(t)"
                        class="text-orange-600 hover:text-orange-800 text-xs font-medium"
                        title="Réinitialiser : rendre ce billet de nouveau valide (fraude avérée)">
                  Réinitialiser
                </button>
                <span v-else class="text-gray-300 text-xs">—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination && pagination.last_page > 1" class="flex items-center justify-between p-4 border-t">
        <p class="text-sm text-gray-600">
          {{ pagination.from }}–{{ pagination.to }} sur {{ pagination.total }}
        </p>
        <div class="flex gap-2">
          <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page <= 1"
                  class="px-3 py-1 border rounded-lg text-sm disabled:opacity-40">Précédent</button>
          <span class="px-3 py-1 text-sm">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
          <button @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page"
                  class="px-3 py-1 border rounded-lg text-sm disabled:opacity-40">Suivant</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import Swal from 'sweetalert2'
import { ticketStatusLabel, ticketStatusBadgeClass } from '../../utils/status'

const loading = ref(false)
const tickets = ref([])
const events = ref([])
const organizers = ref([])
const pagination = ref(null)
const stats = reactive({ total: 0, issued: 0, used: 0, physical: 0, online: 0 })

const filters = reactive({
  search: '',
  event_id: '',
  organizer_id: '',
  status: '',
  ticket_source: '',
  date_from: '',
  date_to: '',
  page: 1,
})

let searchTimeout = null

const authHeaders = () => ({
  Authorization: `Bearer ${localStorage.getItem('token')}`,
  Accept: 'application/json',
})

const formatDateTime = (d) => d
  ? new Date(d).toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
  : '—'

const sourceLabel = (s) => ({ online: 'En ligne', physical: 'Physique', comped: 'Invitation' }[s] || 'En ligne')
const sourceBadgeClass = (s) => ({
  physical: 'bg-indigo-100 text-indigo-800',
  comped: 'bg-amber-100 text-amber-800',
}[s] || 'bg-gray-100 text-gray-700')

const loadTickets = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    Object.keys(filters).forEach((k) => {
      if (filters[k] !== '' && filters[k] !== null) params.append(k, filters[k])
    })
    const res = await fetch(`/api/v1/admin/tickets?${params}`, { headers: authHeaders() })
    const data = await res.json()
    if (data.success && data.data?.tickets) {
      tickets.value = data.data.tickets.data
      pagination.value = {
        current_page: data.data.tickets.current_page,
        last_page: data.data.tickets.last_page,
        from: data.data.tickets.from,
        to: data.data.tickets.to,
        total: data.data.tickets.total,
      }
      Object.assign(stats, data.data.stats || {})
    }
  } catch (e) {
    console.error('Erreur chargement billets:', e)
  } finally {
    loading.value = false
  }
}

const applyFilters = () => { filters.page = 1; loadTickets() }
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(applyFilters, 400)
}
const changePage = (p) => { filters.page = p; loadTickets() }
const resetFilters = () => {
  filters.search = ''
  filters.event_id = ''
  filters.organizer_id = ''
  filters.status = ''
  filters.ticket_source = ''
  filters.date_from = ''
  filters.date_to = ''
  filters.page = 1
  loadTickets()
}

const resetScan = async (t) => {
  const confirm = await Swal.fire({
    icon: 'warning',
    title: 'Réinitialiser ce billet ?',
    html: `Le billet <b>${t.code}</b> repassera à <b>valide (non scanné)</b> et pourra être scanné à nouveau.<br><span class="text-sm text-gray-500">À n'utiliser qu'en cas de fraude avérée. L'opération est tracée.</span>`,
    showCancelButton: true,
    confirmButtonText: 'Oui, réinitialiser',
    cancelButtonText: 'Annuler',
    confirmButtonColor: '#ea580c',
    cancelButtonColor: '#6b7280',
  })
  if (!confirm.isConfirmed) return

  try {
    const res = await fetch(`/api/v1/admin/tickets/${encodeURIComponent(t.code)}/reset-scan`, {
      method: 'POST',
      headers: { ...authHeaders(), 'Content-Type': 'application/json' },
    })
    const data = await res.json()
    if (data.success) {
      Swal.fire({ icon: 'success', title: 'Billet réinitialisé', text: data.message, confirmButtonColor: '#272d63' })
      loadTickets()
    } else {
      Swal.fire({ icon: 'error', title: 'Erreur', text: data.message || 'Échec', confirmButtonColor: '#272d63' })
    }
  } catch (e) {
    Swal.fire({ icon: 'error', title: 'Erreur technique', text: 'Réessayez.', confirmButtonColor: '#272d63' })
  }
}

const loadEvents = async () => {
  try {
    const res = await fetch('/api/v1/admin/events', { headers: authHeaders() })
    const data = await res.json()
    if (data.success) events.value = data.data?.events?.data ?? data.data?.events ?? []
  } catch (e) { console.error(e) }
}

const loadOrganizers = async () => {
  try {
    const res = await fetch('/api/v1/admin/organizers', { headers: authHeaders() })
    const data = await res.json()
    if (data.success) organizers.value = data.data?.organizers?.data ?? data.data?.organizers ?? []
  } catch (e) { console.error(e) }
}

onMounted(() => {
  loadTickets()
  loadEvents()
  loadOrganizers()
})
</script>
