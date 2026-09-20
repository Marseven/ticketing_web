/**
 * Catégories publiques, chargées une seule fois par session.
 *
 * L'en-tête et la page (accueil, liste d'événements) affichent la même liste :
 * sans mutualisation, chaque visite déclenchait deux appels simultanés au
 * premier rendu. Les catégories bougent très rarement — une mise en cache de
 * 10 min côté navigateur suffit, et le premier pic d'ouverture des ventes ne
 * tape plus l'API pour rien.
 *
 * - `inflight` mutualise les appels concurrents (en-tête + page au même instant)
 * - `sessionStorage` couvre les navigations suivantes (le SPA est rechargé
 *   entièrement quand l'utilisateur revient par un lien WhatsApp)
 */
const STORAGE_KEY = 'primea:categories'
const TTL_MS = 10 * 60 * 1000

let inflight = null

function readCache() {
  try {
    const raw = sessionStorage.getItem(STORAGE_KEY)
    if (!raw) return null
    const { at, list } = JSON.parse(raw)
    if (!Array.isArray(list) || Date.now() - at > TTL_MS) return null
    return list
  } catch (e) {
    return null
  }
}

function writeCache(list) {
  try {
    sessionStorage.setItem(STORAGE_KEY, JSON.stringify({ at: Date.now(), list }))
  } catch (e) {
    /* mode privé / quota : on se passe du cache */
  }
}

/**
 * @returns {Promise<Array<{id:number, name:string, slug:string}>>} liste brute
 *   (chaque appelant la met en forme comme il l'entend). Rejette comme `fetch`
 *   pour que les appelants gardent leur repli.
 */
export async function loadPublicCategories() {
  const cached = readCache()
  if (cached) return cached
  if (inflight) return inflight

  inflight = fetch('/api/client/categories', { headers: { Accept: 'application/json' } })
    .then((response) => {
      if (!response.ok) throw new Error('Erreur de chargement')
      return response.json()
    })
    .then((data) => {
      const list = data.success && Array.isArray(data.categories) ? data.categories : []
      writeCache(list)
      return list
    })
    .finally(() => {
      inflight = null
    })

  return inflight
}
