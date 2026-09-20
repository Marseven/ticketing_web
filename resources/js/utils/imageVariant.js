/**
 * Variantes d'images servies dans les listes.
 *
 * `ImageOptimizer` écrit deux fichiers à chaque upload : le master (1600 px) et
 * une variante `medium_` (800 px). Une carte d'événement fait ~400 px de large :
 * lui servir le master, c'est dix fois le poids nécessaire sur une liste de dix
 * événements.
 *
 * Les affiches mises en ligne avant l'optimisation n'ont pas de variante, d'où
 * le repli : on tente `medium_`, et au premier échec on remet l'original.
 */

const PREFIX = 'medium_'

/** Les URL externes (Unsplash, bannières distantes) n'ont pas de variante. */
function isLocalUpload(url) {
  return typeof url === 'string' && url.includes('/storage/')
}

/**
 * URL de la variante légère, ou l'URL d'origine si elle ne s'applique pas.
 */
export function cardImageUrl(url) {
  if (!isLocalUpload(url)) return url

  const slash = url.lastIndexOf('/')
  const name = url.slice(slash + 1)
  if (!name || name.startsWith(PREFIX)) return url

  return url.slice(0, slash + 1) + PREFIX + name
}

/**
 * Chemin inverse : retrouve le master à partir d'une variante.
 */
export function fullImageUrl(url) {
  if (typeof url !== 'string') return url

  const slash = url.lastIndexOf('/')
  const name = url.slice(slash + 1)
  if (!name.startsWith(PREFIX)) return url

  return url.slice(0, slash + 1) + name.slice(PREFIX.length)
}

/**
 * Gestionnaire `@error` : bascule une seule fois sur le master.
 * À poser sur les `<img>` dont le `:src` passe par `cardImageUrl`.
 */
export function restoreFullImage(event) {
  const img = event?.target
  if (!img || img.dataset.fullSizeTried) return

  const full = fullImageUrl(img.src)
  if (full === img.src) return

  img.dataset.fullSizeTried = '1'
  img.src = full
}
