<template>
  <div class="event-approval p-6">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-primea-blue">Validation des événements</h1>
      <p class="text-gray-600 mt-1">Approuvez les événements et fixez la commission de la plateforme (variable par organisateur).</p>
    </div>

    <!-- Filtres statut -->
    <div class="flex gap-2 mb-6">
      <button v-for="tab in tabs" :key="tab.value"
              @click="switchTab(tab.value)"
              class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
              :class="activeTab === tab.value ? 'bg-primea-blue text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'">
        {{ tab.label }}
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-16">
      <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-primea-blue"></div>
    </div>

    <!-- Liste vide -->
    <div v-else-if="events.length === 0" class="bg-white rounded-lg shadow p-10 text-center text-gray-500">
      Aucun événement {{ activeTabLabel.toLowerCase() }}.
    </div>

    <!-- Table -->
    <div v-else class="bg-white rounded-lg shadow overflow-x-auto">
      <table class="w-full">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Événement</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Organisateur</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Commission</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="event in events" :key="event.id">
            <td class="px-4 py-3">
              <div class="font-medium text-gray-900">{{ event.title }}</div>
              <div class="text-xs text-gray-500">{{ event.category?.name || '—' }}</div>
            </td>
            <td class="px-4 py-3 text-sm text-gray-700">{{ event.organizer?.name || '—' }}</td>
            <td class="px-4 py-3 text-sm">
              <span class="font-semibold text-gray-900">{{ displayCommission(event) }} %</span>
              <span v-if="event.commission_percentage === null" class="text-xs text-gray-400 ml-1">(défaut org.)</span>
            </td>
            <td class="px-4 py-3">
              <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" :class="statusClass(event.approval_status)">
                {{ statusLabel(event.approval_status) }}
              </span>
            </td>
            <td class="px-4 py-3 text-right whitespace-nowrap">
              <button v-if="event.approval_status !== 'approved'"
                      @click="openApprove(event)"
                      class="text-green-600 hover:text-green-800 text-sm font-medium mr-3">
                Approuver
              </button>
              <button v-if="event.approval_status !== 'rejected'"
                      @click="openReject(event)"
                      class="text-red-600 hover:text-red-800 text-sm font-medium">
                Rejeter
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Approuver -->
    <div v-if="approveModal.open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
        <h3 class="text-lg font-bold text-primea-blue mb-1">Approuver l'événement</h3>
        <p class="text-sm text-gray-600 mb-4">{{ approveModal.event?.title }}</p>

        <label class="block text-sm font-medium text-gray-700 mb-2">Commission plateforme (%)</label>
        <input v-model.number="approveModal.commission" type="number" min="0" max="100" step="0.5"
               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primea-blue focus:border-transparent" />
        <p class="text-xs text-gray-500 mt-1">
          Laisser vide pour utiliser le défaut de l'organisateur ({{ approveModal.event?.organizer?.default_commission_percentage ?? 10 }} %).
        </p>

        <div class="flex justify-end gap-2 mt-6">
          <button @click="approveModal.open = false" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Annuler</button>
          <button @click="submitApprove" :disabled="submitting"
                  class="px-4 py-2 bg-primea-blue text-white rounded-lg hover:bg-primea-yellow hover:text-primea-blue transition-colors disabled:opacity-50">
            {{ submitting ? 'Traitement…' : 'Approuver' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Rejeter -->
    <div v-if="rejectModal.open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
        <h3 class="text-lg font-bold text-primea-blue mb-1">Rejeter l'événement</h3>
        <p class="text-sm text-gray-600 mb-4">{{ rejectModal.event?.title }}</p>

        <label class="block text-sm font-medium text-gray-700 mb-2">Motif du rejet *</label>
        <textarea v-model="rejectModal.reason" rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primea-blue focus:border-transparent"
                  placeholder="Expliquez ce qui doit être corrigé…"></textarea>

        <div class="flex justify-end gap-2 mt-6">
          <button @click="rejectModal.open = false" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Annuler</button>
          <button @click="submitReject" :disabled="submitting || !rejectModal.reason.trim()"
                  class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50">
            {{ submitting ? 'Traitement…' : 'Rejeter' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import Swal from 'sweetalert2'

const tabs = [
  { value: 'pending', label: 'En attente' },
  { value: 'approved', label: 'Approuvés' },
  { value: 'rejected', label: 'Rejetés' },
]

const loading = ref(false)
const submitting = ref(false)
const events = ref([])
const activeTab = ref('pending')

const approveModal = reactive({ open: false, event: null, commission: null })
const rejectModal = reactive({ open: false, event: null, reason: '' })

const activeTabLabel = computed(() => tabs.find(t => t.value === activeTab.value)?.label || '')

const authHeaders = () => ({
  'Authorization': `Bearer ${localStorage.getItem('token')}`,
  'Accept': 'application/json',
  'Content-Type': 'application/json',
})

const loadEvents = async () => {
  loading.value = true
  try {
    const res = await fetch(`/api/v1/admin/events/approval/queue?approval_status=${activeTab.value}`, {
      headers: authHeaders(),
    })
    const data = await res.json()
    if (data.success) {
      events.value = data.data?.data ?? data.data ?? []
    }
  } catch (e) {
    console.error('Erreur chargement événements:', e)
  } finally {
    loading.value = false
  }
}

const switchTab = (tab) => {
  activeTab.value = tab
  loadEvents()
}

const displayCommission = (event) => {
  if (event.commission_percentage !== null && event.commission_percentage !== undefined) {
    return Number(event.commission_percentage).toFixed(2)
  }
  return Number(event.organizer?.default_commission_percentage ?? 10).toFixed(2)
}

const statusLabel = (s) => ({ pending: 'En attente', approved: 'Approuvé', rejected: 'Rejeté' }[s] || s)
const statusClass = (s) => ({
  pending: 'bg-yellow-100 text-yellow-800',
  approved: 'bg-green-100 text-green-800',
  rejected: 'bg-red-100 text-red-800',
}[s] || 'bg-gray-100 text-gray-800')

const openApprove = (event) => {
  approveModal.event = event
  approveModal.commission = event.commission_percentage !== null ? Number(event.commission_percentage) : null
  approveModal.open = true
}

const openReject = (event) => {
  rejectModal.event = event
  rejectModal.reason = ''
  rejectModal.open = true
}

const submitApprove = async () => {
  submitting.value = true
  try {
    const body = {}
    if (approveModal.commission !== null && approveModal.commission !== '') {
      body.commission_percentage = approveModal.commission
    }
    const res = await fetch(`/api/v1/admin/events/${approveModal.event.id}/approve`, {
      method: 'POST', headers: authHeaders(), body: JSON.stringify(body),
    })
    const data = await res.json()
    if (data.success) {
      approveModal.open = false
      Swal.fire({ icon: 'success', title: 'Approuvé', text: 'Événement approuvé.', confirmButtonColor: '#004B5E' })
      loadEvents()
    } else {
      Swal.fire({ icon: 'error', title: 'Erreur', text: data.message || 'Échec', confirmButtonColor: '#004B5E' })
    }
  } catch (e) {
    Swal.fire({ icon: 'error', title: 'Erreur technique', text: 'Réessayez.', confirmButtonColor: '#004B5E' })
  } finally {
    submitting.value = false
  }
}

const submitReject = async () => {
  submitting.value = true
  try {
    const res = await fetch(`/api/v1/admin/events/${rejectModal.event.id}/reject`, {
      method: 'POST', headers: authHeaders(),
      body: JSON.stringify({ rejection_reason: rejectModal.reason }),
    })
    const data = await res.json()
    if (data.success) {
      rejectModal.open = false
      Swal.fire({ icon: 'success', title: 'Rejeté', text: 'Événement rejeté.', confirmButtonColor: '#004B5E' })
      loadEvents()
    } else {
      Swal.fire({ icon: 'error', title: 'Erreur', text: data.message || 'Échec', confirmButtonColor: '#004B5E' })
    }
  } catch (e) {
    Swal.fire({ icon: 'error', title: 'Erreur technique', text: 'Réessayez.', confirmButtonColor: '#004B5E' })
  } finally {
    submitting.value = false
  }
}

onMounted(loadEvents)
</script>
