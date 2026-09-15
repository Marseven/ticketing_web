import { defineStore } from 'pinia'

// Défauts alignés sur Setting::BRANDING_DEFAULTS (fallback si l'injection blade
// window.__BRANDING__ est absente, ex : page servie hors app.blade.php).
const DEFAULTS = {
  app_name: 'Primea',
  header_title: 'La Billetterie',
  header_subtitle: 'Simple, Rapide et Sécurisée',
  contact_email: 'contact@primea.ga',
  logo_url: '/images/logo.png?v=3',
  logo_white_url: '/images/logo_white.png?v=3',
  favicon_url: '/images/ico.png?v=3',
  meta_title: "Primea - Se procurer un ticket n'a jamais été aussi simple",
  meta_description: '',
  og_image: '/images/ico.png?v=3',
  color_primary: '#272d63',
  color_accent: '#fab511',
  color_secondary: '#1a1f4a',
}

// "#RRGGBB" | "#RGB" -> "R G B" (canaux) ; null si invalide.
function hexToRgbChannels(hex) {
  if (!hex) return null
  let h = String(hex).trim().replace(/^#/, '')
  if (h.length === 3) h = h.split('').map(c => c + c).join('')
  if (!/^[0-9a-fA-F]{6}$/.test(h)) return null
  return `${parseInt(h.slice(0, 2), 16)} ${parseInt(h.slice(2, 4), 16)} ${parseInt(h.slice(4, 6), 16)}`
}

export const useBrandingStore = defineStore('branding', {
  state: () => ({ ...DEFAULTS, ...(typeof window !== 'undefined' ? window.__BRANDING__ || {} : {}) }),

  actions: {
    // Applique titre + favicon + couleurs au document (utile après une édition
    // admin ; au premier rendu, le blade a déjà posé les bonnes valeurs).
    applyDocument() {
      try {
        if (this.meta_title) document.title = this.meta_title
        for (const rel of ['icon', 'shortcut icon']) {
          const link = document.querySelector(`link[rel="${rel}"]`)
          if (link) link.href = this.favicon_url
        }
        this.applyColors()
      } catch (e) { /* no-op */ }
    },

    // Pose les variables CSS de couleur sur :root (canaux RGB).
    applyColors() {
      try {
        const map = {
          '--brand-primary-rgb': this.color_primary,
          '--brand-accent-rgb': this.color_accent,
          '--brand-secondary-rgb': this.color_secondary,
        }
        for (const [varName, hex] of Object.entries(map)) {
          const channels = hexToRgbChannels(hex)
          if (channels) document.documentElement.style.setProperty(varName, channels)
        }
      } catch (e) { /* no-op */ }
    },

    // Recharge la marque depuis l'API (après une modification en admin).
    async refresh() {
      try {
        const res = await fetch('/api/v1/branding', { headers: { Accept: 'application/json' } })
        const data = await res.json()
        if (data.success && data.data) {
          Object.assign(this, data.data)
          this.applyDocument()
        }
      } catch (e) { /* garde les valeurs courantes */ }
    },
  },
})
