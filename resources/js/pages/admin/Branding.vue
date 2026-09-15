<template>
  <div class="branding p-6">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-primea-blue">Identité de marque</h1>
      <p class="text-gray-600 mt-1">Configurez le nom, les logos et les métadonnées de la plateforme. Les changements sont appliqués à chaud (rechargez la page publique pour les voir).</p>
    </div>

    <!-- Textes -->
    <div class="bg-white rounded-lg shadow p-5 mb-6">
      <h2 class="text-lg font-bold text-primea-blue mb-4">Textes</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'application</label>
          <input v-model="form.app_name" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email de contact</label>
          <input v-model="form.contact_email" type="email" class="w-full px-3 py-2 border border-gray-300 rounded-lg" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Titre d'en-tête</label>
          <input v-model="form.header_title" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg" />
          <p class="text-xs text-gray-400 mt-1">Grande ligne du header (ex : « La Billetterie »).</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Sous-titre d'en-tête</label>
          <input v-model="form.header_subtitle" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg" />
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Titre SEO / onglet (meta title)</label>
          <input v-model="form.meta_title" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg" />
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Description SEO (meta description)</label>
          <textarea v-model="form.meta_description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
        </div>
      </div>

      <!-- Couleurs -->
      <div class="mt-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Couleurs de la palette</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div v-for="c in colorFields" :key="c.key">
            <label class="block text-xs text-gray-600 mb-1">{{ c.label }}</label>
            <div class="flex items-center gap-2">
              <input type="color" v-model="form[c.key]" class="h-9 w-12 rounded border border-gray-300 p-0.5 cursor-pointer" />
              <input type="text" v-model="form[c.key]" class="flex-1 px-2 py-1.5 border border-gray-300 rounded-lg text-sm font-mono uppercase" />
            </div>
          </div>
        </div>
        <p class="text-xs text-gray-400 mt-2">Appliquées à toute l'interface (boutons, en-têtes, accents). Rechargez les pages publiques pour voir le rendu.</p>
      </div>

      <div class="mt-6">
        <button @click="saveText" :disabled="busy"
                class="px-4 py-2 rounded-lg bg-primea-blue text-white font-semibold hover:bg-primea-yellow hover:text-primea-blue transition-colors disabled:opacity-50">
          {{ busy === 'text' ? 'Enregistrement…' : 'Enregistrer' }}
        </button>
      </div>
    </div>

    <!-- Images -->
    <div class="bg-white rounded-lg shadow p-5">
      <h2 class="text-lg font-bold text-primea-blue mb-4">Logos & favicon</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div v-for="asset in assets" :key="asset.field" class="border border-gray-100 rounded-lg p-4">
          <p class="text-sm font-medium text-gray-800 mb-2">{{ asset.label }}</p>
          <div class="flex items-center gap-4">
            <div class="w-24 h-16 flex items-center justify-center rounded"
                 :class="asset.dark ? 'bg-primea-blue' : 'bg-gray-50 border border-gray-100'">
              <img v-if="current[asset.field]" :src="current[asset.field]" :alt="asset.label" class="max-h-14 max-w-full object-contain" />
              <span v-else class="text-xs text-gray-400">—</span>
            </div>
            <div class="flex-1">
              <input type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml,image/x-icon" class="text-sm"
                     @change="e => onFile(asset.field, e)" />
              <button @click="uploadAsset(asset.field)" :disabled="busy || !files[asset.field]"
                      class="mt-2 px-3 py-1.5 rounded-lg bg-primea-blue text-white text-sm font-medium hover:bg-primea-yellow hover:text-primea-blue transition-colors disabled:opacity-50">
                {{ busy === asset.field ? 'Envoi…' : 'Remplacer' }}
              </button>
            </div>
          </div>
        </div>
      </div>
      <p class="text-xs text-gray-400 mt-4">PNG/JPG/WEBP/SVG/ICO, 2 Mo max. Le logo blanc s'affiche sur fond sombre (footer, menu mobile).</p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import Swal from 'sweetalert2'
import { useBrandingStore } from '../../stores/branding'

const brandingStore = useBrandingStore()

const assets = [
  { field: 'logo_url', label: 'Logo (couleur)', dark: false },
  { field: 'logo_white_url', label: 'Logo (blanc, fond sombre)', dark: true },
  { field: 'favicon_url', label: 'Favicon', dark: false },
  { field: 'og_image', label: 'Image de partage (OG)', dark: false },
]

const current = ref({})
const form = reactive({
  app_name: '', header_title: '', header_subtitle: '',
  contact_email: '', meta_title: '', meta_description: '',
  color_primary: '#272d63', color_accent: '#fab511', color_secondary: '#1a1f4a',
})

const colorFields = [
  { key: 'color_primary', label: 'Primaire (teal foncé)' },
  { key: 'color_accent', label: 'Accent (ambre)' },
  { key: 'color_secondary', label: 'Secondaire (teal)' },
]
const files = reactive({})
const busy = ref(null)

const authHeaders = () => ({
  Authorization: `Bearer ${localStorage.getItem('token')}`,
  Accept: 'application/json',
  'Content-Type': 'application/json',
})

const load = async () => {
  try {
    const res = await fetch('/api/v1/branding', { headers: { Accept: 'application/json' } })
    const data = await res.json()
    if (data.success) {
      current.value = data.data
      for (const k of Object.keys(form)) form[k] = data.data[k] ?? ''
    }
  } catch (e) { console.error(e) }
}

const saveText = async () => {
  busy.value = 'text'
  try {
    const res = await fetch('/api/v1/admin/branding', {
      method: 'PUT', headers: authHeaders(), body: JSON.stringify(form),
    })
    const data = await res.json()
    if (data.success) {
      current.value = data.data
      // Appliquer les couleurs en direct (met à jour l'UI sans reload).
      Object.assign(brandingStore, data.data)
      brandingStore.applyColors()
      Swal.fire({ icon: 'success', title: 'Identité mise à jour', confirmButtonColor: '#272d63' })
    } else {
      Swal.fire({ icon: 'error', title: 'Erreur', text: data.message, confirmButtonColor: '#272d63' })
    }
  } catch (e) {
    Swal.fire({ icon: 'error', title: 'Erreur', text: e.message, confirmButtonColor: '#272d63' })
  } finally { busy.value = null }
}

const onFile = (field, e) => { files[field] = e.target.files[0] || null }

const uploadAsset = async (field) => {
  if (!files[field]) return
  busy.value = field
  try {
    const fd = new FormData()
    fd.append('field', field)
    fd.append('file', files[field])
    const res = await fetch('/api/v1/admin/branding/asset', {
      method: 'POST',
      headers: { Authorization: `Bearer ${localStorage.getItem('token')}`, Accept: 'application/json' },
      body: fd,
    })
    const data = await res.json()
    if (data.success) {
      current.value = data.data.branding
      files[field] = null
      Swal.fire({ icon: 'success', title: 'Fichier mis à jour', confirmButtonColor: '#272d63' })
    } else {
      Swal.fire({ icon: 'error', title: 'Erreur', text: data.message, confirmButtonColor: '#272d63' })
    }
  } catch (e) {
    Swal.fire({ icon: 'error', title: 'Erreur', text: e.message, confirmButtonColor: '#272d63' })
  } finally { busy.value = null }
}

onMounted(load)
</script>
