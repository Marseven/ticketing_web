<template>
  <div class="physical-tickets p-6">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-primea-blue">Billets physiques (QR imprimés)</h1>
      <p class="text-gray-600 mt-1">Générez des QR codes à imprimer sur des billets physiques et suivez leurs scans.</p>
    </div>

    <!-- Sélection événement -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">Événement</label>
      <select v-model="selectedEventId" @change="onEventChange"
              class="w-full md:w-1/2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primea-blue">
        <option value="">Sélectionnez un événement</option>
        <option v-for="ev in events" :key="ev.id" :value="ev.id">{{ ev.title }}</option>
      </select>
    </div>

    <template v-if="selectedEventId">
      <!-- Synthèse -->
      <div v-if="summary" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
          <p class="text-xs text-gray-500">Physiques générés</p>
          <p class="text-2xl font-bold text-primea-blue">{{ summary.physical.total }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
          <p class="text-xs text-gray-500">Physiques scannés</p>
          <p class="text-2xl font-bold text-green-600">{{ summary.physical.scanned }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
          <p class="text-xs text-gray-500">En ligne vendus</p>
          <p class="text-2xl font-bold text-gray-700">{{ summary.online.total }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
          <p class="text-xs text-gray-500">En ligne scannés</p>
          <p class="text-2xl font-bold text-gray-700">{{ summary.online.scanned }}</p>
        </div>
      </div>

      <!-- Génération -->
      <div class="bg-white rounded-lg shadow p-4 mb-6">
        <h2 class="text-lg font-bold text-primea-blue mb-3">Générer un lot</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
          <div>
            <label class="block text-xs text-gray-600 mb-1">Type de billet (optionnel)</label>
            <select v-model="genForm.ticket_type_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
              <option :value="null">—</option>
              <option v-for="tt in ticketTypes" :key="tt.id" :value="tt.id">{{ tt.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs text-gray-600 mb-1">Date (optionnel)</label>
            <select v-model="genForm.schedule_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
              <option :value="null">—</option>
              <option v-for="s in schedules" :key="s.id" :value="s.id">{{ formatDate(s.starts_at) }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs text-gray-600 mb-1">Quantité *</label>
            <input v-model.number="genForm.quantity" type="number" min="1" max="500"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" />
          </div>
          <div>
            <button @click="generate" :disabled="generating || !genForm.quantity"
                    class="w-full bg-primea-blue text-white px-4 py-2 rounded-lg hover:bg-primea-yellow hover:text-primea-blue transition-colors disabled:opacity-50">
              {{ generating ? 'Génération…' : 'Générer + Imprimer' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Lots -->
      <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b"><h2 class="text-lg font-bold text-primea-blue">Lots générés</h2></div>
        <div v-if="batches.length === 0" class="p-8 text-center text-gray-500">Aucun lot pour cet événement.</div>
        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lot</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Scannés</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Restants</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="b in batches" :key="b.batch_reference">
                <td class="px-4 py-3 text-sm font-mono">{{ b.batch_reference }}</td>
                <td class="px-4 py-3 text-sm text-right">{{ b.total }}</td>
                <td class="px-4 py-3 text-sm text-right text-green-600 font-semibold">{{ b.scanned }}</td>
                <td class="px-4 py-3 text-sm text-right">{{ b.not_scanned }}</td>
                <td class="px-4 py-3 text-sm text-right">
                  <button @click="printBatch(b.batch_reference)" class="text-primea-blue hover:text-primea-yellow font-medium">
                    Imprimer (PDF)
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import Swal from 'sweetalert2'

const events = ref([])
const ticketTypes = ref([])
const schedules = ref([])
const batches = ref([])
const summary = ref(null)
const selectedEventId = ref('')
const generating = ref(false)

const genForm = reactive({ ticket_type_id: null, schedule_id: null, quantity: 50 })

const authHeaders = () => ({
  'Authorization': `Bearer ${localStorage.getItem('token')}`,
  'Accept': 'application/json',
  'Content-Type': 'application/json',
})

const formatDate = (d) => d ? new Date(d).toLocaleString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—'

const loadEvents = async () => {
  try {
    const res = await fetch('/api/v1/admin/events', { headers: authHeaders() })
    const data = await res.json()
    if (data.success) {
      events.value = data.data?.data ?? data.data ?? []
    }
  } catch (e) { console.error(e) }
}

const onEventChange = async () => {
  batches.value = []
  summary.value = null
  ticketTypes.value = []
  schedules.value = []
  genForm.ticket_type_id = null
  genForm.schedule_id = null
  if (!selectedEventId.value) return

  try {
    const res = await fetch(`/api/v1/admin/events/${selectedEventId.value}`, { headers: authHeaders() })
    const data = await res.json()
    const ev = data.data ?? data
    ticketTypes.value = ev.ticket_types ?? ev.ticketTypes ?? []
    schedules.value = ev.schedules ?? []
  } catch (e) { console.error(e) }

  await loadBatches()
}

const loadBatches = async () => {
  try {
    const res = await fetch(`/api/v1/admin/events/${selectedEventId.value}/physical-tickets/batches`, { headers: authHeaders() })
    const data = await res.json()
    if (data.success) {
      batches.value = data.data.batches || []
      summary.value = data.data.summary
    }
  } catch (e) { console.error(e) }
}

const generate = async () => {
  generating.value = true
  try {
    const res = await fetch(`/api/v1/admin/events/${selectedEventId.value}/physical-tickets`, {
      method: 'POST', headers: authHeaders(), body: JSON.stringify(genForm),
    })
    const data = await res.json()
    if (data.success) {
      await loadBatches()
      Swal.fire({ icon: 'success', title: 'Lot généré', text: data.message, confirmButtonColor: '#272d63' })
      printBatch(data.data.batch_reference)
    } else {
      Swal.fire({ icon: 'error', title: 'Erreur', text: data.message || 'Échec', confirmButtonColor: '#272d63' })
    }
  } catch (e) {
    Swal.fire({ icon: 'error', title: 'Erreur technique', text: 'Réessayez.', confirmButtonColor: '#272d63' })
  } finally {
    generating.value = false
  }
}

const printBatch = async (batchRef) => {
  try {
    const res = await fetch(`/api/v1/admin/physical-tickets/batches/${batchRef}/print`, {
      headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` },
    })
    if (!res.ok) throw new Error('print failed')
    const blob = await res.blob()
    const url = URL.createObjectURL(blob)
    window.open(url, '_blank')
    setTimeout(() => URL.revokeObjectURL(url), 60000)
  } catch (e) {
    Swal.fire({ icon: 'error', title: 'Impression', text: 'Impossible de générer le PDF.', confirmButtonColor: '#272d63' })
  }
}

onMounted(loadEvents)
</script>
