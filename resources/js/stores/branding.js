import { defineStore } from 'pinia'

// Défauts alignés sur Setting::BRANDING_DEFAULTS (fallback si l'injection blade
// window.__BRANDING__ est absente, ex : page servie hors app.blade.php).
const DEFAULTS = {
  app_name: 'MyTicketO',
  header_title: 'La Billetterie',
  header_subtitle: 'Simple, Rapide et Sécurisée',
  contact_email: 'contact@primea.ga',
  logo_url: '/images/logo.png?v=2',
  logo_white_url: '/images/logo_white.png?v=2',
  favicon_url: '/images/ico.png?v=2',
  meta_title: "MyTicketO - Se procurer un ticket n'a jamais été aussi simple",
  meta_description: '',
  og_image: '/images/ico.png?v=2',
}

export const useBrandingStore = defineStore('branding', {
  state: () => ({ ...DEFAULTS, ...(typeof window !== 'undefined' ? window.__BRANDING__ || {} : {}) }),

  actions: {
    // Applique titre + favicon au document (utile après une édition admin ;
    // au premier rendu, le blade a déjà posé les bonnes valeurs).
    applyDocument() {
      try {
        if (this.meta_title) document.title = this.meta_title
        for (const rel of ['icon', 'shortcut icon']) {
          const link = document.querySelector(`link[rel="${rel}"]`)
          if (link) link.href = this.favicon_url
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
