/**
 * Une seule façon de transformer un échec serveur en phrase lisible.
 *
 * Laravel renvoie sur un refus de validation (422) :
 *   { message: "The given data was invalid.", errors: { champ: ["…"] } }
 *
 * Les pages n'affichaient le plus souvent que `message` — donc une phrase
 * générique, en anglais, qui ne dit pas QUEL champ est en cause. L'utilisateur
 * voyait « Une erreur est survenue » devant un formulaire qu'il ne savait pas
 * corriger. Le détail par champ était pourtant là, dans `errors`.
 *
 * Onze pages en faisaient chacune leur propre lecture, toutes différentes.
 * Celle-ci est commune, et traite aussi bien une erreur axios qu'un corps de
 * réponse `fetch` déjà décodé.
 */

/** Phrase générique de Laravel, à ne jamais montrer telle quelle. */
const GENERIQUES = [
  'the given data was invalid.',
  'the given data was invalid',
  'validation failed',
  'unauthenticated.',
  'server error'
]

/** Ce que le serveur a répondu, quelle que soit la façon dont on l'a appelé. */
function corps (source) {
  if (!source) return {}
  if (source.response && typeof source.response === 'object') return source.response.data || {}
  if (source.data && typeof source.data === 'object') return source.data
  return source
}

function statut (source) {
  return source?.response?.status ?? source?.status ?? null
}

/**
 * Les messages par champ, tels que le serveur les a rendus.
 * @returns {Object<string, string>} un message par champ, le premier
 */
export function fieldErrors (source) {
  const { errors } = corps(source)
  if (!errors || typeof errors !== 'object') return {}

  return Object.fromEntries(
    Object.entries(errors)
      .map(([champ, messages]) => [champ, Array.isArray(messages) ? messages[0] : String(messages)])
      .filter(([, message]) => Boolean(message))
  )
}

/** Tous les messages de champ, sans doublon et dans l'ordre. */
export function errorList (source) {
  return [...new Set(Object.values(fieldErrors(source)))]
}

/**
 * Une phrase à montrer. Le détail des champs prime sur le message générique :
 * « Le numéro de téléphone est obligatoire » vaut mieux que « Une erreur est
 * survenue ».
 */
export function errorMessage (source, secours = 'Une erreur est survenue.') {
  const liste = errorList(source)
  if (liste.length === 1) return liste[0]
  if (liste.length > 1) return liste.join(' ')

  const { message } = corps(source)

  if (message && !GENERIQUES.includes(String(message).trim().toLowerCase())) {
    return message
  }

  return parStatut(statut(source)) || secours
}

/**
 * Quand le serveur ne dit rien d'utile, le code HTTP en dit déjà long — et
 * bien plus que « Une erreur est survenue ».
 */
function parStatut (code) {
  switch (code) {
    case 401: return 'Votre session a expiré. Reconnectez-vous.'
    case 403: return "Vous n'avez pas accès à cette action."
    case 404: return 'Élément introuvable.'
    case 409: return 'Cette action entre en conflit avec l\'état actuel.'
    case 413: return 'Le fichier est trop volumineux.'
    case 419: return 'Votre session a expiré. Rechargez la page.'
    case 422: return 'Certains champs sont invalides.'
    case 429: return 'Trop de tentatives. Patientez quelques instants.'
    case 500:
    case 502:
    case 503:
    case 504: return 'Le serveur est indisponible. Réessayez dans un instant.'
    default: return null
  }
}

/**
 * Le même contenu, en liste, pour une alerte qui a la place de l'afficher.
 * Plusieurs champs invalides méritent plusieurs lignes : les concaténer en un
 * seul paragraphe les rend illisibles dès qu'il y en a trois.
 */
export function errorHtml (source, secours) {
  const liste = errorList(source)

  if (liste.length <= 1) {
    return escape(errorMessage(source, secours))
  }

  return '<ul style="text-align:left;margin:0;padding-left:1.1rem">'
    + liste.map((m) => `<li>${escape(m)}</li>`).join('')
    + '</ul>'
}

function escape (texte) {
  return String(texte ?? '').replace(/[&<>"']/g, (c) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  }[c]))
}

/**
 * Une panne réseau n'est pas un refus du serveur : le dire évite que
 * l'utilisateur corrige un formulaire qui n'avait rien d'invalide.
 */
export function isNetworkError (source) {
  return Boolean(source?.request) && !source?.response
}

/**
 * L'alerte, avec la même présentation partout.
 *
 * `Swal` est passé en argument plutôt qu'importé : les pages l'importent déjà,
 * et cela garde ce module utilisable sans dépendance.
 */
export function showFormError (Swal, source, { title = 'Erreur', fallback } = {}) {
  if (isNetworkError(source)) {
    return Swal.fire({
      icon: 'error',
      title: 'Connexion impossible',
      text: "Le serveur n'a pas répondu. Vérifiez votre connexion internet.",
      confirmButtonColor: '#272d63'
    })
  }

  return Swal.fire({
    icon: 'error',
    title,
    html: errorHtml(source, fallback),
    confirmButtonColor: '#272d63'
  })
}
