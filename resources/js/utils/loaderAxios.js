import axios from 'axios'
import { useLoadingStore } from '../stores/loading'
import { handleUnauthorized } from './sessionExpired'

/**
 * Instrumente l'instance axios PAR DÉFAUT (utilisée directement par une dizaine
 * de pages : accueil, bannières, admin…) pour alimenter le loader global —
 * même logique que l'instance `api` de services/api.js. Idempotent.
 * Un appel se rend silencieux avec l'en-tête `X-No-Loader: 1`.
 */
export function installAxiosLoader() {
  if (axios.__primeaLoader) return
  axios.__primeaLoader = true

  axios.interceptors.request.use((config) => {
    if (!config.headers?.['X-No-Loader']) {
      useLoadingStore().start()
      config.__loader = true
    }
    return config
  })

  axios.interceptors.response.use(
    (response) => {
      if (response.config?.__loader) useLoadingStore().stop()
      return response
    },
    (error) => {
      if (error.config?.__loader) useLoadingStore().stop()

      if (error.response?.status === 401) {
        handleUnauthorized(error.config?.url ?? '')
      }

      return Promise.reject(error)
    }
  )
}
