import { defineStore } from 'pinia'

/**
 * État global de chargement, affiché par <GlobalLoader /> (App.vue).
 *  - `navigating` : un changement de page est en cours (hooks du routeur,
 *    y compris le téléchargement des chunks lazy — visible sur mobile).
 *  - `pending`    : nombre d'appels API en vol (axios + fetch instrumentés).
 * Toute action utilisateur qui déclenche une navigation ou un appel réseau
 * affiche donc un retour visuel sans rien changer dans les composants.
 */
export const useLoadingStore = defineStore('loading', {
  state: () => ({
    pending: 0,
    navigating: false,
  }),

  getters: {
    active: (state) => state.navigating || state.pending > 0,
  },

  actions: {
    start() {
      this.pending++
    },
    stop() {
      this.pending = Math.max(0, this.pending - 1)
    },
    navStart() {
      this.navigating = true
    },
    navEnd() {
      this.navigating = false
    },
  },
})
