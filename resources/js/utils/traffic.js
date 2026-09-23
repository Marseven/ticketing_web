/**
 * Signalement de fréquentation.
 *
 * L'application est une SPA : le serveur ne voit qu'un seul chargement par
 * visite, alors que la personne parcourt plusieurs écrans. Le navigateur
 * signale donc lui-même chaque changement d'écran.
 *
 * Trois règles tiennent ce fichier :
 * - la mesure ne doit JAMAIS gêner la navigation, d'où le `catch` silencieux
 *   et l'absence d'`await` bloquant ;
 * - les espaces privés ne sont pas comptés : ce qui intéresse, c'est
 *   l'audience publique, pas les allées et venues d'un administrateur ;
 * - aucun cookie n'est posé, et rien d'identifiant n'est envoyé. Le serveur
 *   fabrique lui-même une empreinte du jour, qu'il ne peut pas rattacher à
 *   une personne.
 */

const PRIVATE_AREAS = ['/admin', '/organizer', '/scanner', '/account']

function isPrivate (path) {
  return PRIVATE_AREAS.some((area) => path === area || path.startsWith(area + '/'))
}

/** Dernier chemin compté, pour ne pas doubler un remplacement d'URL. */
let lastPath = null

export function recordPageView (path, referrer) {
  if (!path || isPrivate(path) || path === lastPath) return

  lastPath = path

  try {
    const body = JSON.stringify({ path, referrer: referrer ?? document.referrer ?? null })

    // `sendBeacon` survit à la fermeture de l'onglet et ne retarde rien.
    // Certains navigateurs ne l'exposent pas : on retombe alors sur `fetch`,
    // en mode « keepalive » pour la même raison.
    if (navigator.sendBeacon) {
      const blob = new Blob([body], { type: 'application/json' })
      if (navigator.sendBeacon('/api/v1/traffic/hit', blob)) return
    }

    fetch('/api/v1/traffic/hit', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body,
      keepalive: true,
    }).catch(() => { /* une mesure perdue n'est pas un incident */ })
  } catch (e) {
    /* idem */
  }
}

/** Branche la mesure sur les changements d'écran du routeur. */
export function trackNavigation (router) {
  router.afterEach((to, from) => {
    recordPageView(to.path, from.fullPath ? window.location.origin + from.fullPath : undefined)
  })
}
