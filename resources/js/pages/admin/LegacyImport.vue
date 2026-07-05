<template>
  <div class="legacy-import p-6">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-primea-blue">Import données MyTicketO</h1>
      <p class="text-gray-600 mt-1">Importer les données de l'ancienne plateforme MyTicketO (base legacy) vers cette plateforme.</p>
    </div>

    <!-- Étape 1 : charger le dump -->
    <div class="mb-6 bg-white rounded-lg shadow p-5">
      <h2 class="text-lg font-bold text-primea-blue mb-1">1. Charger le dump MyTicketO</h2>
      <p class="text-sm text-gray-500 mb-3">Sélectionnez le fichier <code>.sql</code> exporté de l'ancienne base MyTicketO.</p>

      <div class="flex items-center gap-3 mb-3">
        <span class="w-2.5 h-2.5 rounded-full" :class="legacy.loaded ? 'bg-green-500' : 'bg-gray-300'"></span>
        <span class="text-sm font-medium" :class="legacy.loaded ? 'text-green-800' : 'text-gray-500'">
          {{ legacy.loaded ? 'Dump chargé ✓' : 'Aucun dump chargé' }}
        </span>
      </div>

      <div class="flex flex-wrap items-center gap-3">
        <input ref="fileInput" type="file" accept=".sql" @change="onFile" class="text-sm" />
        <button @click="upload" :disabled="!file || busy"
                class="px-4 py-2 rounded-lg bg-primea-blue text-white text-sm font-medium hover:bg-primea-yellow hover:text-primea-blue transition-colors disabled:opacity-50">
          {{ busy === 'upload' ? 'Chargement…' : 'Charger le dump' }}
        </button>
        <button v-if="legacy.loaded" @click="cleanup" :disabled="busy"
                class="px-3 py-2 rounded-lg text-sm text-gray-600 border border-gray-200 hover:bg-gray-50 disabled:opacity-50">
          Supprimer le dump chargé
        </button>
      </div>
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
      <button @click="preview" :disabled="busy || !legacy.loaded"
              class="px-4 py-2 rounded-lg border border-primea-blue text-primea-blue font-medium hover:bg-primea-blue/5 disabled:opacity-50">
        {{ busy === 'preview' ? 'Simulation…' : 'Prévisualiser (simulation)' }}
      </button>
      <button @click="confirmRun" :disabled="busy || !legacy.loaded"
              class="px-4 py-2 rounded-lg bg-primea-blue text-white font-semibold hover:bg-primea-yellow hover:text-primea-blue transition-colors disabled:opacity-50">
        {{ busy === 'run' ? 'Import en cours…' : 'Lancer l\'import' }}
      </button>
    </div>

    <!-- État de l'import en arrière-plan -->
    <div v-if="importState && ['queued','running'].includes(importState.state)"
         class="mb-4 flex items-center gap-3 bg-blue-50 border border-blue-200 rounded-lg p-4">
      <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-primea-blue"></div>
      <span class="text-sm text-primea-blue font-medium">
        {{ importState.state === 'queued' ? 'Import en file d\'attente (démarrage dans la minute)…' : 'Import en cours…' }}
      </span>
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
const legacy = reactive({ connected: false, loaded: false })
const importState = ref(null)
const opts = reactive({ all: false, fresh: true })
const busy = ref(null)
const output = ref('')
const file = ref(null)
const fileInput = ref(null)

const onFile = (e) => { file.value = e.target.files[0] || null }

const upload = async () => {
  if (!file.value) return
  busy.value = 'upload'
  try {
    const fd = new FormData()
    fd.append('dump', file.value)
    const res = await fetch('/api/v1/admin/legacy-import/upload', {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}`, 'Accept': 'application/json' },
      body: fd,
    })
    const data = await res.json()
    if (data.success) {
      Swal.fire({ icon: 'success', title: 'Dump chargé', confirmButtonColor: '#004B5E' })
      file.value = null
      if (fileInput.value) fileInput.value.value = ''
      loadStatus()
    } else {
      Swal.fire({ icon: 'error', title: 'Erreur', text: data.message, confirmButtonColor: '#004B5E' })
    }
  } catch (e) {
    Swal.fire({ icon: 'error', title: 'Erreur', text: e.message, confirmButtonColor: '#004B5E' })
  } finally {
    busy.value = null
  }
}

const cleanup = async () => {
  busy.value = 'cleanup'
  try {
    await fetch('/api/v1/admin/legacy-import/cleanup', {
      method: 'POST', headers: authHeaders(),
    })
    loadStatus()
  } catch (e) { console.error(e) } finally { busy.value = null }
}

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
      importState.value = data.data.import || null
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
    if (data.success && data.data.projected) {
      const p = data.data.projected
      const rows = Object.keys(p).length
        ? Object.entries(p).map(([k, v]) => `  ${labels[k] || k} : ${v}`).join('\n')
        : (data.data.message || 'Aucun événement dans le périmètre.')
      output.value = 'Seront importés (aperçu, aucune écriture) :\n' + rows
    } else {
      output.value = data.message || 'Aucune sortie.'
    }
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
      output.value = 'Import lancé en arrière-plan. Traitement automatique dans la minute qui suit…'
      pollImport()
    } else {
      Swal.fire({ icon: 'error', title: 'Erreur', text: data.message, confirmButtonColor: '#004B5E' })
      busy.value = null
    }
  } catch (e) {
    output.value = 'Erreur : ' + e.message
    busy.value = null
  }
}

let pollTimer = null
const pollImport = () => {
  if (pollTimer) clearInterval(pollTimer)
  pollTimer = setInterval(async () => {
    await loadStatus()
    const imp = importState.value
    if (!imp) return
    if (imp.state === 'running') {
      output.value = 'Import en cours…'
    } else if (imp.state === 'done') {
      clearInterval(pollTimer); pollTimer = null; busy.value = null
      output.value = imp.output || 'Import terminé.'
      Swal.fire({ icon: 'success', title: 'Import terminé', confirmButtonColor: '#004B5E' })
    } else if (imp.state === 'error') {
      clearInterval(pollTimer); pollTimer = null; busy.value = null
      output.value = 'Erreur : ' + (imp.message || '')
      Swal.fire({ icon: 'error', title: 'Erreur d\'import', text: imp.message, confirmButtonColor: '#004B5E' })
    }
  }, 4000)
}

onMounted(async () => {
  await loadStatus()
  // Reprendre le suivi si un import est déjà en cours.
  if (importState.value && ['queued', 'running'].includes(importState.value.state)) {
    busy.value = 'run'
    pollImport()
  }
})
</script>
