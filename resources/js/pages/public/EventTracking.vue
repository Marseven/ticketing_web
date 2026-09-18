<template>
  <div class="min-h-screen bg-gray-50 py-6 px-4">
    <div class="max-w-5xl mx-auto">

      <!-- Chargement -->
      <div v-if="loading && !summary" class="text-center py-20 text-gray-500">Chargement…</div>

      <!-- Lien invalide -->
      <div v-else-if="notFound" class="text-center py-20">
        <div class="text-5xl mb-4">🔒</div>
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
              <div class="mt-2 text-sm text-gray-600 space-y-1">
                <p v-if="summary.event.organizer">👤 {{ summary.event.organizer }}</p>
                <p v-if="summary.event.venue">📍 {{ summary.event.venue }}</p>
                <p v-if="summary.event.date">🗓️ {{ formatDate(summary.event.date) }}</p>
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
                <span v-if="t.scanned">✓</span><span v-else>○</span>
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
                <span :class="c.result === 'valid' ? 'text-green-600' : 'text-orange-500'">●</span>
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
const resultLabel = (r) => ({ valid: 'Entrée validée', duplicate: 'Déjà scanné', invalid: 'Refusé' }[r] || r)

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

const applyFilters = () => { filters.page = 1; loadTickets() }
const setScan = (v) => { filters.scan = v; applyFilters() }
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
