<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-2xl">
      <!-- Logo et titre -->
      <div class="text-center mb-8">
        <h2 class="text-3xl font-extrabold text-blue-950">
          Inscription Organisateur
        </h2>
        <p class="mt-2 text-sm text-gray-600">
          Créez votre compte pour organiser et gérer vos événements
        </p>
      </div>

      <!-- Formulaire -->
      <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
        <form @submit.prevent="handleRegister" class="space-y-6">
          <!-- Section Informations Utilisateur -->
          <div class="border-b border-gray-200 pb-6">
            <h3 class="text-lg font-medium text-blue-950 mb-4">
              Informations Personnelles
            </h3>

            <!-- Nom complet -->
            <div class="mb-4">
              <label for="name" class="block text-sm font-medium text-gray-700">
                Nom complet <span class="text-red-500">*</span>
              </label>
              <input
                id="name"
                v-model="formData.name"
                type="text"
                required
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-950 focus:border-blue-950"
                :class="{ 'border-red-500': errors.name }"
              />
              <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
            </div>

            <!-- Email -->
            <div class="mb-4">
              <label for="email" class="block text-sm font-medium text-gray-700">
                Adresse email
              </label>
              <input
                id="email"
                v-model="formData.email"
                type="email"
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-950 focus:border-blue-950"
                :class="{ 'border-red-500': errors.email }"
              />
              <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
            </div>

            <!-- Téléphone -->
            <div class="mb-4">
              <label for="phone" class="block text-sm font-medium text-gray-700">
                Numéro de téléphone <span class="text-red-500">*</span>
              </label>
              <input
                id="phone"
                v-model="formData.phone"
                type="tel"
                required
                placeholder="Ex: 6XXXXXXXX"
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-950 focus:border-blue-950"
                :class="{ 'border-red-500': errors.phone }"
              />
              <p v-if="errors.phone" class="mt-1 text-sm text-red-600">{{ errors.phone }}</p>
            </div>

            <!-- Mot de passe -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="password" class="block text-sm font-medium text-gray-700">
                  Mot de passe <span class="text-red-500">*</span>
                </label>
                <input
                  id="password"
                  v-model="formData.password"
                  type="password"
                  required
                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-950 focus:border-blue-950"
                  :class="{ 'border-red-500': errors.password }"
                />
                <p v-if="errors.password" class="mt-1 text-sm text-red-600">{{ errors.password }}</p>
              </div>

              <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                  Confirmer mot de passe <span class="text-red-500">*</span>
                </label>
                <input
                  id="password_confirmation"
                  v-model="formData.password_confirmation"
                  type="password"
                  required
                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-950 focus:border-blue-950"
                />
              </div>
            </div>
          </div>

          <!-- Section Informations Organisation -->
          <div class="pt-4">
            <h3 class="text-lg font-medium text-blue-950 mb-4">
              Informations de l'Organisation
            </h3>

            <!-- Nom de l'organisation -->
            <div class="mb-4">
              <label for="organization_name" class="block text-sm font-medium text-gray-700">
                Nom de l'organisation <span class="text-red-500">*</span>
              </label>
              <input
                id="organization_name"
                v-model="formData.organization_name"
                type="text"
                required
                placeholder="Ex: Ma Compagnie, Mon Entreprise, etc."
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-950 focus:border-blue-950"
                :class="{ 'border-red-500': errors.organization_name }"
              />
              <p v-if="errors.organization_name" class="mt-1 text-sm text-red-600">{{ errors.organization_name }}</p>
              <p class="mt-1 text-xs text-gray-500">
                Ce nom sera affiché sur vos événements
              </p>
            </div>

            <!-- Description (optionnel) -->
            <div class="mb-4">
              <label for="description" class="block text-sm font-medium text-gray-700">
                Description de l'organisation (optionnel)
              </label>
              <textarea
                id="description"
                v-model="formData.description"
                rows="3"
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-950 focus:border-blue-950"
                placeholder="Présentez brièvement votre organisation..."
              ></textarea>
            </div>
          </div>

          <!-- Message d'erreur global -->
          <div v-if="errorMessage" class="rounded-md bg-red-50 p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <p class="text-sm text-red-800">{{ errorMessage }}</p>
              </div>
            </div>
          </div>

          <!-- Bouton de soumission -->
          <div>
            <button
              type="submit"
              :disabled="loading"
              class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-950 hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-950 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="loading" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Inscription en cours...
              </span>
              <span v-else>Créer mon compte organisateur</span>
            </button>
          </div>

          <!-- Lien vers connexion -->
          <div class="text-center mt-4">
            <p class="text-sm text-gray-600">
              Vous avez déjà un compte ?
              <router-link to="/login" class="font-medium text-blue-950 hover:text-yellow-500">
                Se connecter
              </router-link>
            </p>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import authUtils from '../../utils/auth'

const router = useRouter()

const formData = ref({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  organization_name: '',
  description: '',
  is_organizer: true
})

const errors = ref({})
const errorMessage = ref('')
const loading = ref(false)

const handleRegister = async () => {
  errors.value = {}
  errorMessage.value = ''
  loading.value = true

  try {
    // Étape 1: Récupérer le cookie CSRF
    await fetch('/sanctum/csrf-cookie', {
      credentials: 'same-origin'
    })

    // Étape 2: Faire la requête d'inscription avec le token CSRF
    const response = await fetch('/api/register', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify(formData.value)
    })

    const data = await response.json()

    if (!response.ok) {
      if (response.status === 422 && data.errors) {
        // Erreurs de validation
        errors.value = data.errors
        // Prendre le premier message d'erreur comme message global
        errorMessage.value = Object.values(data.errors)[0][0]
      } else {
        errorMessage.value = data.message || 'Une erreur est survenue lors de l\'inscription'
      }
      loading.value = false
      return
    }

    // Succès - Sauvegarder le token et les données utilisateur
    if (data.token) {
      authUtils.saveAuth(data.token, data.user)
    }

    // Rediriger vers le dashboard organisateur avec un message de succès
    router.push({
      name: 'organizer-dashboard',
      query: { message: 'Inscription réussie ! Veuillez vérifier votre email pour confirmer votre compte.' }
    })

  } catch (error) {
    console.error('Erreur lors de l\'inscription:', error)
    errorMessage.value = 'Une erreur est survenue. Veuillez réessayer.'
    loading.value = false
  }
}
</script>

<style scoped>
/* Styles personnalisés si nécessaire */
</style>
