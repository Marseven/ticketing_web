import { test } from 'node:test'
import assert from 'node:assert/strict'
import {
  fieldErrors, errorList, errorMessage, errorHtml, isNetworkError
} from '../../resources/js/utils/formErrors.js'

// Ce que Laravel renvoie réellement sur un refus de validation.
const refus422 = {
  response: {
    status: 422,
    data: {
      message: 'The given data was invalid.',
      errors: {
        phone: ['Le numéro de téléphone est obligatoire.'],
        email: ['Cet email est déjà utilisé.']
      }
    }
  }
}

test('le détail par champ est extrait', () => {
  assert.deepEqual(fieldErrors(refus422), {
    phone: 'Le numéro de téléphone est obligatoire.',
    email: 'Cet email est déjà utilisé.'
  })
})

test('le détail prime sur la phrase générique de Laravel', () => {
  // C'est tout le problème : les pages affichaient « The given data was
  // invalid. », qui ne dit ni quel champ ni pourquoi — et en anglais.
  const message = errorMessage(refus422)
  assert.ok(!message.includes('given data'))
  assert.ok(message.includes('téléphone'))
})

test('un seul champ invalide donne une phrase, pas une liste', () => {
  const un = { response: { status: 422, data: { errors: { phone: ['Numéro invalide.'] } } } }
  assert.equal(errorMessage(un), 'Numéro invalide.')
  assert.equal(errorHtml(un), 'Numéro invalide.')
})

test('plusieurs champs donnent une liste lisible', () => {
  const html = errorHtml(refus422)
  assert.ok(html.startsWith('<ul'))
  assert.equal((html.match(/<li>/g) || []).length, 2)
})

test('le message du serveur est gardé quand il est utile', () => {
  const metier = { response: { status: 422, data: { message: 'Cette catégorie est complète.' } } }
  assert.equal(errorMessage(metier), 'Cette catégorie est complète.')
})

test('sans rien d_utile, le code HTTP parle à la place du secours', () => {
  assert.equal(errorMessage({ response: { status: 429, data: {} } }),
    'Trop de tentatives. Patientez quelques instants.')
  assert.equal(errorMessage({ response: { status: 500, data: {} } }),
    'Le serveur est indisponible. Réessayez dans un instant.')
  assert.equal(errorMessage({ response: { status: 403, data: {} } }),
    "Vous n'avez pas accès à cette action.")
})

test('un corps fetch déjà décodé est accepté tel quel', () => {
  // Trente-huit pages utilisent fetch et non axios : elles passent le corps.
  const corps = { message: 'The given data was invalid.', errors: { name: ['Le nom est obligatoire.'] } }
  assert.equal(errorMessage(corps), 'Le nom est obligatoire.')
})

test('une panne réseau se distingue d_un refus', () => {
  // Sans cette distinction, l'utilisateur corrige un formulaire qui n'avait
  // rien d'invalide.
  assert.equal(isNetworkError({ request: {} }), true)
  assert.equal(isNetworkError(refus422), false)
})

test('le secours ne sert qu_en dernier recours', () => {
  assert.equal(errorMessage(null, 'Rien ne va.'), 'Rien ne va.')
  assert.equal(errorMessage({}, 'Rien ne va.'), 'Rien ne va.')
})

test('le contenu du serveur est échappé avant affichage', () => {
  // `html:` dans SweetAlert interprète le balisage : un message renvoyé par le
  // serveur ne doit pas pouvoir devenir du code dans la page.
  const hostile = {
    response: { status: 422, data: { errors: {
      a: ['<img src=x onerror=alert(1)>'], b: ['Autre erreur.']
    } } }
  }
  const html = errorHtml(hostile)
  assert.ok(!html.includes('<img'))
  assert.ok(html.includes('&lt;img'))
})

test('errorList dédoublonne', () => {
  const doublons = { response: { data: { errors: { a: ['Pareil.'], b: ['Pareil.'] } } } }
  assert.deepEqual(errorList(doublons), ['Pareil.'])
})
