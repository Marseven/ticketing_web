import html2canvas from 'html2canvas'

/**
 * Capture et enregistrement du billet en image.
 *
 * Deux contraintes de Safari iOS dictent ce fichier :
 *
 * 1. **L'activation utilisateur ne survit pas à un `await`.** Capturer le billet
 *    prend une à trois secondes ; déclencher le téléchargement après coup revient
 *    à le déclencher hors du geste, et iOS l'ignore en silence. La capture est
 *    donc faite *avant* le tap, et le gestionnaire de clic ne fait plus
 *    qu'enregistrer un blob déjà prêt.
 * 2. **« Télécharger » n'est pas le geste iOS.** Sur iPhone, l'enregistrement
 *    d'une image passe par la feuille de partage (« Enregistrer l'image », qui
 *    range le billet dans Photos). On la privilégie quand elle accepte des
 *    fichiers, et on retombe sur le lien de téléchargement ailleurs.
 */

/**
 * Attend que les images du billet (affiche, QR, logo) soient chargées : une
 * capture lancée trop tôt produit un billet incomplet.
 */
function waitForImages(el) {
  const images = Array.from(el.querySelectorAll('img'))

  return Promise.all(images.map((img) => {
    if (img.complete && img.naturalWidth > 0) return Promise.resolve()

    return new Promise((resolve) => {
      img.addEventListener('load', resolve, { once: true })
      img.addEventListener('error', resolve, { once: true })
    })
  }))
}

/**
 * Rend l'élément du billet en JPEG.
 *
 * `allowTaint: false` est volontaire : si une image reste inaccessible en
 * cross-origin (une affiche hébergée ailleurs), html2canvas l'omet au lieu de
 * « teinter » le canvas. Un canvas teinté fait échouer l'export — mieux vaut un
 * billet sans affiche qu'aucun billet, le QR étant l'essentiel.
 *
 * @returns {Promise<Blob>}
 */
export async function captureTicketBlob(el) {
  await waitForImages(el)

  const canvas = await html2canvas(el, {
    scale: 2,
    useCORS: true,
    allowTaint: false,
    imageTimeout: 15000,
    backgroundColor: '#ffffff',
    ignoreElements: (node) => node.classList?.contains('ticket-cover-bg'),
  })

  return new Promise((resolve, reject) => {
    canvas.toBlob(
      (blob) => (blob ? resolve(blob) : reject(new Error('Export de l\'image impossible'))),
      'image/jpeg',
      0.95
    )
  })
}

/**
 * Enregistre le blob côté utilisateur.
 *
 * ⚠️ À appeler directement depuis le gestionnaire de clic, sans `await` avant :
 * `navigator.share` exige l'activation utilisateur.
 *
 * @returns {Promise<'shared'|'cancelled'|'downloaded'>}
 */
export async function saveTicketImage(blob, filename) {
  const file = new File([blob], filename, { type: 'image/jpeg' })

  if (typeof navigator !== 'undefined' && navigator.canShare && navigator.canShare({ files: [file] })) {
    try {
      await navigator.share({ files: [file] })
      return 'shared'
    } catch (e) {
      // L'utilisateur a fermé la feuille de partage : ne pas enchaîner sur un
      // téléchargement qu'il n'a pas demandé.
      if (e?.name === 'AbortError') return 'cancelled'
      // Partage refusé pour une autre raison : on tente le lien classique.
    }
  }

  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  link.rel = 'noopener'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  // Safari lit le blob après le clic : révoquer trop tôt annule l'enregistrement.
  setTimeout(() => URL.revokeObjectURL(url), 60000)

  return 'downloaded'
}
