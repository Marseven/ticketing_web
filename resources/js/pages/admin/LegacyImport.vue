<template>
  <div class="legacy-import p-6">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-primea-blue">Import données MyTicketO</h1>
      <p class="text-gray-600 mt-1">Importer les données de l'ancienne plateforme MyTicketO (base legacy) vers cette plateforme.</p>
    </div>

    <!-- Connexion legacy -->
    <div class="mb-6 rounded-lg p-4" :class="legacy.connected ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'">
      <div class="flex items-center gap-2">
        <span class="w-2.5 h-2.5 rounded-full" :class="legacy.connected ? 'bg-green-500' : 'bg-red-500'"></span>
        <span class="font-medium" :class="legacy.connected ? 'text-green-800' : 'text-red-800'">
          {{ legacy.connected ? 'Base legacy connectée' : 'Base legacy non connectée' }}
        </span>
      </div>
      <p v-if="!legacy.connected" class="text-sm text-red-700 mt-1">
        Chargez le dump dans une base MySQL et renseignez <code>LEGACY_DB_*</code> dans le <code>.env</code>.
        <span v-if="legacy.error" class="block text-xs mt-1 opacity-75">{{ legacy.error }}</span>
      </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <!-- Actuel -->
      <div class="bg-white rounded-lg shadow p-5">
        <h2 class="text-lg font-bold text-primea-blue mb-3">Données actuelles (seront remplacées)</h2>
        <div class="grid grid-cols-2 gap-2 text-sm">
          <div v-for="(v, k) in current" :key="k" class="flex justify-between border-b border-gray-100 py-1">
            <span class="text-gray-600">{{ labels[k] || k }}</span>
            <span class="font-semibold">{{ v }}</span>
          </div>
        </div>
        <p v-if="imported.events > 0" class="text-xs text-gray-500 mt-3">
          Déjà importés : {{ imported.events }} events, {{ imported.tickets }} billets, {{ imported.orders }} commandes.
        </p>
      </div>

      <!-- Legacy -->
      <div class="bg-white rounded-lg shadow p-5">
        <h2 class="text-lg font-bold text-primea-blue mb-3">Disponible côté MyTicketO (legacy)</h2>
        <div v-if="legacy.connected" class="grid grid-cols-2 gap-2 text-sm">
          <div class="flex justify-between border-b border-gray-100 py-1"><span class="text-gray-600">Événements</span><span class="font-semibold">{{ legacy.events }}</span></div>
          <div class="flex justify-between border-b border-gray-100 py-1"><span class="text-gray-600">Organisateurs</span><span class="font-semibold">{{ legacy.owners }}</span></div>
          <div class="flex justify-between border-b border-gray-100 py-1"><span class="text-gray-600">Clients</span><span class="font-semibold">{{ legacy.users }}</span></div>
          <div class="flex justify-between border-b border-gray-100 py-1"><span class="text-gray-600">Billets</span><span class="font-semibold">{{ legacy.tickets }}</span></div>
          <div class="flex justify-between border-b border-gray-100 py-1"><span class="text-gray-600">Paiements payés</span><span class="font-semibold">{{ legacy.pay_paid }}</span></div>
        </div>
        <p v-else class="text-sm text-gray-400">Non disponible (base non connectée).</p>
      </div>
    </div>

    <!-- Options -->
    <div class="bg-white rounded-lg shadow p-5 mb-6">
      <h2 class="text-lg font-bold text-primea-blue mb-3">Options d'import</h2>
      <label class="flex items-start gap-3 mb-3 cursor-pointer">
        <input type="checkbox" v-model="opts.all" class="mt-1" />
        <span>
          <span class="block text-sm font-medium text-gray-800">Inclure les événements passés</span>
          <span class="block text-xs text-gray-500">Par défaut, seuls les événements à venir sont importés.</span>
        </span>
      </label>
      <label class="flex items-start gap-3 cursor-pointer">
        <input type="checkbox" v-model="opts.fresh" class="mt-1" />
        <span>
          <span class="block text-sm font-medium text-red-700">Purger les données existantes avant l'import (remplacement)</span>
          <span class="block text-xs text-gray-500">Supprime événements, ventes, billets, organisateurs et clients actuels. <strong>Conserve les comptes admin, catégories et bannières.</strong></span>
        </span>
      </label>
    </div>

    <!-- Actions -->
    <div class="flex flex-wrap gap-3 mb-6">
      <button @click="preview" :disabled="busy || !legacy.connected"
              class="px-4 py-2 rounded-lg border border-primea-blue text-primea-blue font-medium hover:bg-primea-blue/5 disabled:opacity-50">
        {{ busy === 'preview' ? 'Simulation…' : 'Prévisualiser (simulation)' }}
      </button>
      <button @click="confirmRun" :disabled="busy || !legacy.connected"
              class="px-4 py-2 rounded-lg bg-primea-blue text-white font-semibold hover:bg-primea-yellow hover:text-primea-blue transition-colors disabled:opacity-50">
        {{ busy === 'run' ? 'Import en cours…' : 'Lancer l\'import' }}
      </button>
    </div>

    <!-- Journal -->
    <div v-if="output" class="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs font-mono overflow-x-auto whitespace-pre-wrap">{{ output }}</div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import Swal from 'sweetalert2'

const labels = {
  events: 'Événements', event_schedules: 'Dates', ticket_types: 'Types de billets',
  orders: 'Commandes', payments: 'Paiements', tickets: 'Billets', checkins: 'Scans',
  venues: 'Lieux', organizers: 'Organisateurs', users: 'Utilisateurs',
}

const current = ref({})
const imported = ref({ events: 0, tickets: 0, orders: 0 })
const legacy = reactive({ connected: false })
const opts = reactive({ all: false, fresh: true })
const busy = ref(null)
const output = ref('')

const authHeaders = () => ({
  'Authorization': `Bearer ${localStorage.getItem('token')}`,
  'Accept': 'application/json',
  'Content-Type': 'application/json',
})

const loadStatus = async () => {
  try {
    const res = await fetch('/api/v1/admin/legacy-import/status', { headers: authHeaders() })
    const data = await res.json()
    if (data.success) {
      current.value = data.data.current
      imported.value = data.data.imported
      Object.assign(legacy, data.data.legacy)
    }
  } catch (e) { console.error(e) }
}

const preview = async () => {
  busy.value = 'preview'
  output.value = ''
  try {
    const res = await fetch('/api/v1/admin/legacy-import/preview', {
      method: 'POST', headers: authHeaders(), body: JSON.stringify({ all: opts.all }),
    })
    const data = await res.json()
    output.value = data.data?.output || data.message || 'Aucune sortie.'
  } catch (e) {
    output.value = 'Erreur : ' + e.message
  } finally {
    busy.value = null
  }
}

const confirmRun = async () => {
  const warn = opts.fresh
    ? 'Cette action va SUPPRIMER toutes les données actuelles (événements, ventes, organisateurs, clients) puis importer celles de MyTicketO.'
    : 'Cette action va importer les données de MyTicketO (sans purger l\'existant).'

  const { value } = await Swal.fire({
    title: 'Confirmer l\'import',
    html: `<p class="text-sm">${warn}</p><p class="text-sm mt-3">Tapez <strong>REMPLACER</strong> pour confirmer :</p>`,
    input: 'text',
    inputPlaceholder: 'REMPLACER',
    showCancelButton: true,
    confirmButtonText: 'Lancer',
    confirmButtonColor: '#004B5E',
    cancelButtonText: 'Annuler',
  })
  if (!value) return

  busy.value = 'run'
  output.value = ''
  try {
    const res = await fetch('/api/v1/admin/legacy-import/run', {
      method: 'POST', headers: authHeaders(),
      body: JSON.stringify({ confirm: value, fresh: opts.fresh, all: opts.all }),
    })
    const data = await res.json()
    if (data.success) {
      output.value = data.data.output
      Swal.fire({ icon: 'success', title: 'Import terminé', confirmButtonColor: '#004B5E' })
      loadStatus()
    } else {
      output.value = data.message || 'Échec.'
      Swal.fire({ icon: 'error', title: 'Erreur', text: data.message, confirmButtonColor: '#004B5E' })
    }
  } catch (e) {
    output.value = 'Erreur : ' + e.message
  } finally {
    busy.value = null
  }
}

onMounted(loadStatus)
</script>
