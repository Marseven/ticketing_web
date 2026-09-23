/**
 * Remplissage d'un champ `<input type="datetime-local">`.
 *
 * Ces champs n'acceptent qu'une seule forme : « AAAA-MM-JJTHH:mm », sans
 * fuseau. Toute la difficulté est d'y mettre la bonne heure de mur.
 *
 * Le piège classique est `new Date(valeur).toISOString().slice(0, 16)` :
 * `toISOString()` convertit en UTC, donc une séance de 09:00 à Libreville
 * s'affiche 08:00. L'organisateur enregistre, 08:00 part en base, et l'horaire
 * recule d'une heure à chaque passage dans le formulaire.
 *
 * On ne reconvertit donc jamais une chaîne déjà datée : le serveur l'envoie
 * avec son décalage (« 2026-12-19T09:00:00+01:00 »), et l'heure qu'elle porte
 * est exactement celle qui a été saisie. La lire telle quelle la rend
 * indépendante du fuseau du navigateur qui l'affiche — un administrateur en
 * déplacement voit la même heure que l'organisateur sur place.
 */

/** « AAAA-MM-JJTHH:mm » à partir de ce que renvoie l'API. */
export function toDateTimeLocal (value) {
  if (!value) return ''

  if (typeof value === 'string') {
    // Déjà daté : « 2026-12-19T09:00:00+01:00 », « …Z », ou sans fuseau.
    if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/.test(value)) {
      return value.slice(0, 16)
    }

    // Forme SQL : « 2026-12-19 09:00:00 ».
    if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}/.test(value)) {
      return value.slice(0, 16).replace(' ', 'T')
    }
  }

  const date = value instanceof Date ? value : new Date(value)

  return Number.isNaN(date.getTime()) ? '' : fromDate(date)
}

/**
 * « AAAA-MM-JJTHH:mm » à partir d'un objet Date, dans le fuseau du navigateur.
 *
 * À réserver aux dates calculées sur place — l'heure courante, une fin déduite
 * d'un début. Pour une valeur venant du serveur, passer par `toDateTimeLocal`,
 * qui n'y touche pas.
 */
export function fromDate (date) {
  if (!(date instanceof Date) || Number.isNaN(date.getTime())) return ''

  const pad = (n) => String(n).padStart(2, '0')

  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
    + `T${pad(date.getHours())}:${pad(date.getMinutes())}`
}

/** Maintenant, prêt pour un champ `datetime-local`. */
export function nowForInput () {
  return fromDate(new Date())
}
