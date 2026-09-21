<template>
  <div class="min-h-screen bg-gray-50 py-6 px-4">
    <div class="max-w-5xl mx-auto">

      <!-- Chargement -->
      <div v-if="loading && !summary" class="text-center py-20 text-gray-500">Chargement…</div>

      <!-- Lien invalide -->
      <div v-else-if="notFound" class="text-center py-20">
        <svg class="w-14 h-14 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 0h10.5a2.25 2.25 0 012.25 2.25v6.75a2.25 2.25 0 01-2.25 2.25H6.75a2.25 2.25 0 01-2.25-2.25v-6.75a2.25 2.25 0 012.25-2.25z" />
        </svg>
        <h1 class="text-xl font-bold text-gray-900">Lien invalide ou expiré</h1>
        <p class="text-gray-600 mt-2">Ce lien de suivi n'est plus valide. Demandez-en un nouveau à l'organisation.</p>
      </div>

      <template v-else-if="summary">
        <!-- En-tête événement -->
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-6">
          <div class="flex flex-col sm:flex-row">
            <img v-if="summary.event.image_url" :src="summary.event.image_url" alt=""
                 class="w-full sm:w-48 h-40 sm:h-auto object-cover" />
            <div class="p-5 flex-1">
              <p class="text-xs font-semibold text-primea-yellow uppercase tracking-wide mb-1">Suivi des billets</p>
              <h1 class="text-2xl font-bold text-primea-blue">{{ summary.event.title }}</h1>
              <div class="mt-2 text-sm text-gray-600 space-y-1.5">
                <p v-if="summary.event.organizer" class="flex items-center gap-2">
                  <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A13 13 0 0112 15c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  {{ summary.event.organizer }}
                </p>
                <p v-if="summary.event.venue" class="flex items-center gap-2">
                  <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  {{ summary.event.venue }}
                </p>
                <p v-if="summary.event.date" class="flex items-center gap-2">
                  <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  {{ formatDate(summary.event.date) }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Statistiques -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
          <div class="bg-white rounded-xl shadow-sm p-4 text-center">
            <p class="text-3xl font-bold text-primea-blue">{{ summary.stats.total }}</p>
            <p class="text-xs text-gray-500 mt-1">Billets au total</p>
          </div>
          <div class="bg-white rounded-xl shadow-sm p-4 text-center">
            <p class="text-3xl font-bold text-green-600">{{ summary.stats.scanned }}</p>
            <p class="text-xs text-gray-500 mt-1">Scannés (entrés)</p>
          </div>
          <div class="bg-white rounded-xl shadow-sm p-4 text-center">
            <p class="text-3xl font-bold text-gray-700">{{ summary.stats.not_scanned }}</p>
            <p class="text-xs text-gray-500 mt-1">Pas encore entrés</p>
          </div>
          <div class="bg-white rounded-xl shadow-sm p-4 text-center">
            <p class="text-3xl font-bold text-primea-blue">{{ scanRate }}%</p>
            <p class="text-xs text-gray-500 mt-1">Taux d'entrée</p>
          </div>
        </div>

        <!-- Revenu -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
          <p class="text-xs text-gray-500">Revenu des billets</p>
          <p class="text-3xl font-bold text-primea-blue">{{ formatMoney(summary.stats.revenue) }}</p>
          <p class="text-xs text-gray-500 mt-1">
            En ligne {{ formatMoney(summary.stats.revenue_online) }}
            · Physique {{ formatMoney(summary.stats.revenue_physical) }}
          </p>
        </div>

        <!-- Par provenance : un clic filtre la liste plus bas -->
        <div v-if="sourceRows.length" class="bg-white rounded-xl shadow-sm p-4 mb-6">
          <h2 class="text-sm font-semibold text-gray-700 mb-3">Par provenance</h2>
          <div class="space-y-2">
            <button
              v-for="row in sourceRows"
              :key="row.key"
              type="button"
              @click="filterBySource(row.key)"
              class="w-full flex items-center gap-3 rounded-lg px-2 py-2 text-left transition-colors"
              :class="filters.ticket_source === row.key ? 'bg-primea-blue/10' : 'hover:bg-gray-50'"
            >
              <span class="w-24 text-sm font-medium text-gray-700">{{ row.label }}</span>
              <span class="flex-1 text-sm text-gray-600">
                {{ row.total }} billet<span v-if="row.total > 1">s</span>
                · {{ row.scanned }} entré<span v-if="row.scanned > 1">s</span>
              </span>
              <span class="text-sm font-semibold text-primea-blue whitespace-nowrap">{{ formatMoney(row.revenue) }}</span>
            </button>
          </div>
        </div>

        <!-- Répartition par type -->
        <div v-if="summary.stats.by_type?.length" class="bg-white rounded-xl shadow-sm p-4 mb-6">
          <h2 class="text-sm font-semibold text-gray-700 mb-3">Par catégorie de billet</h2>
          <div class="space-y-2">
            <div v-for="(t, i) in summary.stats.by_type" :key="i" class="flex items-center gap-3">
              <span class="w-28 text-sm text-gray-700 truncate">{{ t.name }}</span>
              <div class="flex-1 bg-gray-100 rounded-full h-3 overflow-hidden">
                <div class="bg-green-500 h-3" :style="{ width: (t.total ? (t.scanned / t.total * 100) : 0) + '%' }"></div>
              </div>
              <span class="text-sm text-gray-600 w-20 text-right">{{ t.scanned }}/{{ t.total }}</span>
            </div>
          </div>
        </div>

        <!-- Recherche + filtres -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
          <input v-model="filters.search" @input="debouncedLoad" type="text"
                 placeholder="Rechercher : code, nom, e-mail, référence…"
                 class="w-full border border-gray-300 rounded-lg px-4 py-2.5 mb-3 focus:ring-2 focus:ring-primea-blue outline-none" />
          <div class="flex flex-wrap gap-2 items-center">
            <button v-for="opt in scanTabs" :key="opt.value" @click="setScan(opt.value)"
                    class="px-3 py-1.5 rounded-full text-sm font-medium transition"
                    :class="filters.scan === opt.value ? 'bg-primea-blue text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
              {{ opt.label }}
            </button>
            <select v-model="filters.ticket_source" @change="applyFilters"
                    class="ml-auto border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
              <option value="">Toutes provenances</option>
              <option value="online">En ligne</option>
              <option value="physical">Physique</option>
              <option value="comped">Invitation</option>
            </select>
          </div>
        </div>

        <!-- Liste des billets -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
          <div v-if="loadingTickets" class="p-10 text-center text-gray-500">Chargement…</div>
          <div v-else-if="tickets.length === 0" class="p-10 text-center text-gray-500">Aucun billet trouvé.</div>
          <ul v-else class="divide-y divide-gray-100">
            <li v-for="t in tickets" :key="t.code" @click="openDetail(t.code)"
                class="p-4 flex items-center gap-3 hover:bg-gray-50 cursor-pointer">
              <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center"
                   :class="t.scanned ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-400'">
                <svg v-if="t.scanned" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                  <circle cx="12" cy="12" r="7" />
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="font-mono text-sm text-primea-blue">{{ t.code }}</p>
                <p class="text-sm text-gray-700 truncate">{{ t.holder_name || '—' }} · {{ t.type || 'Billet' }}</p>
              </div>
              <div class="text-right">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                      :class="t.scanned ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'">
                  {{ t.scanned ? 'Entré' : 'Non entré' }}
                </span>
                <p v-if="t.scanned && t.scanned_at" class="text-xs text-gray-400 mt-1">{{ formatDate(t.scanned_at) }}</p>
              </div>
            </li>
          </ul>

          <!-- Pagination -->
          <div v-if="pagination && pagination.last_page > 1" class="flex items-center justify-between p-4 border-t">
            <span class="text-sm text-gray-600">{{ pagination.from }}–{{ pagination.to }} / {{ pagination.total }}</span>
            <div class="flex gap-2">
              <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page <= 1"
                      class="px-3 py-1 border rounded-lg text-sm disabled:opacity-40">Précédent</button>
              <button @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page"
                      class="px-3 py-1 border rounded-lg text-sm disabled:opacity-40">Suivant</button>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- Modal détail billet -->
    <div v-if="showDetail" class="fixed inset-0 bg-black/50 flex items-end sm:items-center justify-center z-50 p-0 sm:p-4"
         @click.self="showDetail = false">
      <div class="bg-white w-full sm:max-w-lg sm:rounded-2xl rounded-t-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-4 border-b sticky top-0 bg-white">
          <h3 class="font-bold text-primea-blue">Détail du billet</h3>
          <button @click="showDetail = false" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
        </div>

        <div v-if="loadingDetail" class="p-10 text-center text-gray-500">Chargement…</div>
        <div v-else-if="detail" class="p-5 space-y-5">
          <div class="flex items-center justify-between">
            <span class="font-mono text-primea-blue">{{ detail.code }}</span>
            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                  :class="detail.scanned_at ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'">
              {{ detail.scanned_at ? 'Entré' : 'Non entré' }}
            </span>
          </div>

          <div>
            <h4 class="text-xs font-semibold text-gray-400 uppercase mb-2">Acheteur</h4>
            <p class="text-sm"><span class="text-gray-500">Nom :</span> {{ detail.holder.name || '—' }}</p>
            <p class="text-sm"><span class="text-gray-500">E-mail :</span> {{ detail.holder.email || '—' }}</p>
            <p class="text-sm"><span class="text-gray-500">Téléphone :</span> {{ detail.holder.phone || '—' }}</p>
          </div>

          <div>
            <h4 class="text-xs font-semibold text-gray-400 uppercase mb-2">Billet & commande</h4>
            <p class="text-sm"><span class="text-gray-500">Catégorie :</span> {{ detail.type || '—' }}</p>
            <p class="text-sm"><span class="text-gray-500">Provenance :</span> {{ sourceLabel(detail.source) }}</p>
            <p class="text-sm"><span class="text-gray-500">Référence :</span> {{ detail.order?.reference || '—' }}</p>
            <p class="text-sm"><span class="text-gray-500">Date d'achat :</span> {{ detail.order?.purchase_date ? formatDate(detail.order.purchase_date) : '—' }}</p>
            <p v-if="detail.order" class="text-sm"><span class="text-gray-500">Montant :</span> {{ formatAmount(detail.order.total_amount) }} {{ detail.order.currency }}</p>
          </div>

          <div>
            <h4 class="text-xs font-semibold text-gray-400 uppercase mb-2">Historique des scans</h4>
            <p v-if="!detail.checkins?.length" class="text-sm text-gray-500">Jamais scanné.</p>
            <ul v-else class="space-y-2">
              <li v-for="(c, i) in detail.checkins" :key="i" class="text-sm flex items-start gap-2">
                <svg class="w-2.5 h-2.5 mt-1.5 flex-shrink-0" :class="c.result === 'valid' ? 'text-green-600' : 'text-orange-500'" viewBox="0 0 8 8" fill="currentColor">
                  <circle cx="4" cy="4" r="4" />
                </svg>
                <div>
                  <p>{{ resultLabel(c.result) }} — {{ formatDate(c.scanned_at) }}</p>
                  <p class="text-xs text-gray-500">par {{ c.scanned_by || 'Inconnu' }}<span v-if="c.device_id"> · {{ c.device_id }}</span></p>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const token = route.params.token

const loading = ref(true)
const loadingTickets = ref(false)
const notFound = ref(false)
const summary = ref(null)
const tickets = ref([])
const pagination = ref(null)

const showDetail = ref(false)
const loadingDetail = ref(false)
const detail = ref(null)

const filters = reactive({ search: '', scan: '', ticket_source: '', page: 1 })
const scanTabs = [
  { value: '', label: 'Tous' },
  { value: 'scanned', label: 'Entrés' },
  { value: 'not_scanned', label: 'Pas entrés' },
]

let searchTimeout = null

const scanRate = computed(() => {
  const s = summary.value?.stats
  if (!s || !s.total) return 0
  return Math.round((s.scanned / s.total) * 100)
})

const formatDate = (d) => d
  ? new Date(d).toLocaleString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
  : '—'
const formatAmount = (a) => new Intl.NumberFormat('fr-FR').format(a || 0)
const sourceLabel = (s) => ({ online: 'En ligne', physical: 'Physique', comped: 'Invitation' }[s] || 'En ligne')
const resultLabel = (r) => ({ valid: 'Entrée validée', duplicate: 'Déjà scanné', invalid: 'Refusé', reset: 'Réinitialisé (admin)' }[r] || r)

const loadSummary = async () => {
  try {
    const res = await fetch(`/api/v1/track/${token}`)
    if (res.status === 404) { notFound.value = true; return }
    const data = await res.json()
    if (data.success) summary.value = data.data
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

const loadTickets = async () => {
  loadingTickets.value = true
  try {
    const params = new URLSearchParams()
    if (filters.search) params.append('search', filters.search)
    if (filters.scan) params.append('scan', filters.scan)
    if (filters.ticket_source) params.append('ticket_source', filters.ticket_source)
    params.append('page', filters.page)
    const res = await fetch(`/api/v1/track/${token}/tickets?${params}`)
    if (res.status === 404) { notFound.value = true; return }
    const data = await res.json()
    if (data.success) {
      tickets.value = data.data.tickets.data
      pagination.value = {
        current_page: data.data.tickets.current_page,
        last_page: data.data.tickets.last_page,
        from: data.data.tickets.from,
        to: data.data.tickets.to,
        total: data.data.tickets.total,
      }
    }
  } catch (e) {
    console.error(e)
  } finally {
    loadingTickets.value = false
  }
}

// Séparateur de milliers posé à la main : `Intl` insère une espace insécable
// étroite qui passe mal dans certains navigateurs in-app (WhatsApp, Facebook).
const formatMoney = (value) => {
  const amount = Math.round(Number(value) || 0)
  return String(amount).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' FCFA'
}

// Ne montrer que les provenances réellement présentes : un événement sans
// billet physique n'a pas besoin d'une ligne à zéro.
const sourceRows = computed(() => {
  const bySource = summary.value?.stats?.by_source || {}
  return [
    { key: 'online', label: 'En ligne' },
    { key: 'physical', label: 'Physique' },
    { key: 'comped', label: 'Invitations' },
  ]
    .map((row) => ({ ...row, ...(bySource[row.key] || { total: 0, scanned: 0, revenue: 0 }) }))
    .filter((row) => row.total > 0)
})

const applyFilters = () => { filters.page = 1; loadTickets() }
const setScan = (v) => { filters.scan = v; applyFilters() }
// Re-cliquer sur la provenance active enlève le filtre.
const filterBySource = (key) => {
  filters.ticket_source = filters.ticket_source === key ? '' : key
  applyFilters()
}
const debouncedLoad = () => { clearTimeout(searchTimeout); searchTimeout = setTimeout(applyFilters, 400) }
const changePage = (p) => { filters.page = p; loadTickets() }

const openDetail = async (code) => {
  showDetail.value = true
  loadingDetail.value = true
  detail.value = null
  try {
    const res = await fetch(`/api/v1/track/${token}/tickets/${encodeURIComponent(code)}`)
    const data = await res.json()
    if (data.success) detail.value = data.data.ticket
  } catch (e) {
    console.error(e)
  } finally {
    loadingDetail.value = false
  }
}

onMounted(() => {
  loadSummary()
  loadTickets()
})
</script>
