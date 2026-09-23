import { computed, onUnmounted, ref } from 'vue'

/**
 * Ouverture de la billetterie d'un événement.
 *
 * Un événement peut être publié et visible bien avant que la vente n'ouvre :
 * on l'annonce, les gens le découvrent, mais personne ne peut encore acheter.
 * L'écran doit alors montrer le compte à rebours et refuser l'achat.
 *
 * ⚠️ L'API renvoie la DATE d'ouverture, jamais un booléen « ouvert ». La liste
 * des événements est mise en cache soixante secondes côté serveur : un booléen
 * y serait périmé pile à l'instant qui compte, et la billetterie resterait
 * fermée jusqu'à une minute après l'heure annoncée. Le navigateur compare donc
 * la date à sa propre horloge, qui bat à la seconde — l'achat s'ouvre de
 * lui-même à l'échéance, sans rechargement.
 */

/** Lit une date d'ouverture, quelle que soit la forme reçue. */
export function parseOpening (value) {
  if (!value) return null

  // Laravel peut sérialiser en ISO 8601 ou en « Y-m-d H:i:s » ; Safari refuse
  // la seconde forme telle quelle, d'où le remplacement de l'espace.
  const date = new Date(typeof value === 'string' ? value.replace(' ', 'T') : value)

  return Number.isNaN(date.getTime()) ? null : date
}

/** Décompose un écart en jours / heures / minutes / secondes. */
export function splitDelay (milliseconds) {
  const left = Math.max(0, milliseconds)

  return {
    days: Math.floor(left / 86400000),
    hours: Math.floor((left % 86400000) / 3600000),
    minutes: Math.floor((left % 3600000) / 60000),
    seconds: Math.floor((left % 60000) / 1000),
  }
}

/** « lundi 6 octobre à 10:00 » */
export function formatOpening (date) {
  if (!date) return ''

  return date.toLocaleString('fr-FR', {
    weekday: 'long', day: 'numeric', month: 'long',
    hour: '2-digit', minute: '2-digit',
  })
}

/**
 * Horloge partagée par tous les comptes à rebours de la page.
 *
 * Une carte par événement ferait autant de `setInterval` : sur une liste de
 * trente événements, trente minuteries pour afficher la même seconde. Une
 * seule bat ici, et elle s'arrête dès que plus personne ne l'écoute.
 */
const now = ref(new Date())
let timer = null
let listeners = 0

function subscribe () {
  listeners += 1

  if (timer === null) {
    timer = setInterval(() => { now.value = new Date() }, 1000)
  }
}

function unsubscribe () {
  listeners = Math.max(0, listeners - 1)

  if (listeners === 0 && timer !== null) {
    clearInterval(timer)
    timer = null
  }
}

/**
 * @param {import('vue').Ref|Function} source  l'événement, ou sa date d'ouverture
 */
export function useSalesOpening (source) {
  const read = () => {
    const value = typeof source === 'function' ? source() : source?.value

    if (value && typeof value === 'object' && 'sales_start_at' in value) {
      return value.sales_start_at
    }

    return value
  }

  const openingAt = computed(() => parseOpening(read()))

  const salesNotOpenYet = computed(() => {
    const date = openingAt.value

    return !!date && date.getTime() > now.value.getTime()
  })

  const countdown = computed(() => splitDelay(
    openingAt.value ? openingAt.value.getTime() - now.value.getTime() : 0
  ))

  const openingLabel = computed(() => formatOpening(openingAt.value))

  /** « J-12 », « 3 h », « 12 min » — assez court pour une pastille de carte. */
  const shortCountdown = computed(() => {
    if (!salesNotOpenYet.value) return ''

    const { days, hours, minutes } = countdown.value

    if (days > 0) return `J-${days}`
    if (hours > 0) return `${hours} h`
    if (minutes > 0) return `${minutes} min`

    return 'imminent'
  })

  subscribe()
  onUnmounted(unsubscribe)

  return { openingAt, salesNotOpenYet, countdown, openingLabel, shortCountdown }
}

/**
 * Variante pour une LISTE d'événements.
 *
 * Dans un `v-for`, on ne peut pas appeler un composable par ligne : on expose
 * donc des fonctions, branchées sur la même horloge que le reste de la page.
 *
 *   const { salesPending, salesBadge } = useSalesClock()
 *   <span v-if="salesPending(event)">En vente {{ salesBadge(event) }}</span>
 */
export function useSalesClock () {
  subscribe()
  onUnmounted(unsubscribe)

  const openingOf = (event) => parseOpening(event?.sales_start_at)

  const salesPending = (event) => {
    const date = openingOf(event)

    return !!date && date.getTime() > now.value.getTime()
  }

  const salesBadge = (event) => {
    if (!salesPending(event)) return ''

    const { days, hours, minutes } = splitDelay(
      openingOf(event).getTime() - now.value.getTime()
    )

    if (days > 0) return `J-${days}`
    if (hours > 0) return `${hours} h`
    if (minutes > 0) return `${minutes} min`

    return 'imminent'
  }

  return { salesPending, salesBadge }
}
