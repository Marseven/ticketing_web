// Libellés (FR) et couleurs cohérents pour tous les statuts affichés.
// Convention couleurs : vert = payé/réussi/valide, jaune = en attente,
// rouge = échoué/annulé, violet = remboursé, bleu = utilisé/info.

const GREEN = 'bg-green-100 text-green-800'
const YELLOW = 'bg-yellow-100 text-yellow-800'
const RED = 'bg-red-100 text-red-800'
const PURPLE = 'bg-purple-100 text-purple-800'
const BLUE = 'bg-blue-100 text-blue-800'
const GRAY = 'bg-gray-100 text-gray-800'

// --- Commandes -------------------------------------------------------------
const ORDER = {
  pending: { label: 'En attente', class: YELLOW },
  paid: { label: 'Payé', class: GREEN },
  confirmed: { label: 'Payé', class: GREEN }, // alias historique
  completed: { label: 'Terminée', class: GREEN }, // alias historique
  cancelled: { label: 'Annulée', class: RED },
  refunded: { label: 'Remboursée', class: PURPLE },
}

// --- Paiements -------------------------------------------------------------
const PAYMENT = {
  initiated: { label: 'En attente', class: YELLOW },
  pending: { label: 'En attente', class: YELLOW },
  processing: { label: 'En cours', class: YELLOW },
  success: { label: 'Réussi', class: GREEN },
  paid: { label: 'Payé', class: GREEN },
  failed: { label: 'Échoué', class: RED },
  cancelled: { label: 'Annulé', class: RED },
  expired: { label: 'Expiré', class: GRAY },
  refunded: { label: 'Remboursé', class: PURPLE },
}

// --- Billets ---------------------------------------------------------------
const TICKET = {
  issued: { label: 'Émis', class: GREEN },
  active: { label: 'Valide', class: GREEN },
  used: { label: 'Utilisé', class: BLUE },
  cancelled: { label: 'Annulé', class: RED },
  refunded: { label: 'Remboursé', class: PURPLE },
  void: { label: 'Annulé', class: GRAY },
  expired: { label: 'Expiré', class: GRAY },
}

function pick(map, status, fallbackLabel) {
  const key = String(status ?? '').toLowerCase()
  return map[key] || { label: fallbackLabel ?? (status || '—'), class: GRAY }
}

export const orderStatusLabel = (s) => pick(ORDER, s).label
export const orderStatusBadgeClass = (s) => pick(ORDER, s).class

export const paymentStatusLabel = (s) => pick(PAYMENT, s).label
export const paymentStatusBadgeClass = (s) => pick(PAYMENT, s).class

export const ticketStatusLabel = (s) => pick(TICKET, s).label
export const ticketStatusBadgeClass = (s) => pick(TICKET, s).class
