/**
 * Fin de session : une seule porte de sortie.
 *
 * Quand le jeton n'est plus valable, l'API répond 401. Jusqu'ici seule
 * l'instance axios de `services/api.js` réagissait — or une trentaine de pages
 * appellent `fetch` directement. Sur ces pages, la session expirée ne
 * produisait RIEN : les données ne se chargeaient pas, aucun message, aucune
 * redirection. On croyait l'application figée et on rechargeait la page à la
 * main pour retrouver l'écran de connexion.
 *
 * Tout passe donc par ici, et une seule redirection part, quel que soit le
 * nombre d'appels tombés en même temps.
 */

/** Adresses où un 401 est une réponse normale, pas une session perdue. */
const PUBLIC_ENDPOINTS = ['/api/login', '/api/register', '/api/forgot-password', '/api/reset-password', '/auth/login', '/auth/register']

let redirecting = false

function isPublicEndpoint (url) {
  return PUBLIC_ENDPOINTS.some((path) => String(url).includes(path))
}

function storedToken () {
  try {
    return localStorage.getItem('token') || localStorage.getItem('auth_token')
  } catch (e) {
    return null
  }
}

/**
 * Efface la session et renvoie vers la connexion, une seule fois.
 * `url` sert à ignorer les 401 attendus (mauvais mot de passe, par exemple).
 */
export function handleUnauthorized (url = '') {
  if (redirecting) return
  if (isPublicEndpoint(url)) return

  // Sans jeton, un 401 est simplement une page réservée : c'est au garde de
  // navigation de s'en occuper, pas à nous.
  if (!storedToken()) return

  redirecting = true

  try {
    // TOUTES les clés, et pas seulement `auth_token` : en laisser une
    // derrière faisait croire à l'application qu'elle était encore connectée,
    // et la renvoyait en boucle vers la connexion.
    for (const key of ['token', 'auth_token', 'userRole', 'userName', 'userEmail', 'user']) {
      localStorage.removeItem(key)
    }
  } catch (e) {
    /* navigation privée */
  }

  const retour = window.location.pathname + window.location.search
  const suffixe = retour && retour !== '/' ? '?redirect=' + encodeURIComponent(retour) : ''

  window.location.href = '/login' + suffixe
}

/** Vrai si cette réponse signale une session perdue. */
export function isUnauthorized (status, url) {
  return status === 401 && String(url).includes('/api/') && !isPublicEndpoint(url)
}
