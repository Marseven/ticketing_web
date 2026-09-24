import { useLoadingStore } from '../stores/loading'
import { handleUnauthorized, isUnauthorized } from './sessionExpired'

/**
 * Instrumente `window.fetch` pour que tout appel réseau déclenché par un
 * bouton (beaucoup de pages utilisent fetch directement, pas axios) alimente
 * le loader global. Idempotent. Un appel peut se rendre silencieux (sondage
 * en arrière-plan, ex. statut de paiement) avec l'en-tête `X-No-Loader: 1`.
 */
export function installFetchLoader() {
  if (typeof window === 'undefined' || window.__primeaFetchLoader) return
  window.__primeaFetchLoader = true

  const nativeFetch = window.fetch.bind(window)

  window.fetch = async (input, init = {}) => {
    const silent = hasNoLoaderHeader(init.headers) || (input instanceof Request && input.headers.get('X-No-Loader'))
    const store = silent ? null : useLoadingStore()

    store?.start()
    try {
      const response = await nativeFetch(input, init)

      // Session perdue : sans ce relais, une trentaine de pages appelant
      // `fetch` directement restaient vides sans rien dire.
      const url = typeof input === 'string' ? input : (input?.url ?? '')
      if (isUnauthorized(response.status, url)) {
        handleUnauthorized(url)
      }

      return response
    } finally {
      store?.stop()
    }
  }
}

function hasNoLoaderHeader(headers) {
  if (!headers) return false
  if (typeof headers.get === 'function') return !!headers.get('X-No-Loader')
  if (Array.isArray(headers)) return headers.some(([k]) => String(k).toLowerCase() === 'x-no-loader')
  return Object.keys(headers).some((k) => k.toLowerCase() === 'x-no-loader')
}
