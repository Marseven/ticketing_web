<template>
  <div class="forgot-password-page font-primea relative overflow-hidden min-h-screen">
    <!-- Image de fond avec overlay -->
    <div class="absolute inset-0">
      <img
        src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80"
        alt="Événements"
        class="w-full h-full object-cover opacity-40"
      />
      <div class="absolute inset-0 bg-primea-blue/60"></div>
    </div>

    <!-- Contenu principal -->
    <div class="relative z-10 min-h-screen flex items-center justify-center px-4 py-12">
      <div class="max-w-md w-full">
        <!-- Logo Primea -->
        <div class="text-center mb-8 animate-fade-in">
          <img src="/images/logo_white.png" alt="Primea" class="h-16 mx-auto mb-6" />
        </div>

        <!-- Carte de mot de passe oublié -->
        <div class="bg-white/95 backdrop-blur-sm rounded-primea-xl shadow-primea-lg p-8 animate-slide-up">

          <!-- Titre -->
          <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-primea-blue mb-2">
              Mot de passe oublié ?
            </h1>
            <p class="text-gray-600 font-primea">
              Entrez votre adresse email pour recevoir un lien de réinitialisation
            </p>
          </div>

          <!-- Message de succès -->
          <div v-if="success" class="bg-green-50 border border-green-200 rounded-primea-lg p-4 mb-6 animate-fade-in">
            <div class="flex items-center">
              <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <p class="text-green-700 text-sm font-primea">{{ success }}</p>
            </div>
          </div>

          <!-- Formulaire -->
          <form @submit.prevent="handleSubmit" class="space-y-6">

            <!-- Email -->
            <div>
              <label for="email" class="block text-sm font-semibold text-primea-blue mb-2 font-primea">
                Adresse email
              </label>
              <input
                type="email"
                id="email"
                v-model="email"
                placeholder="votre.email@exemple.com"
                class="w-full px-4 py-3 border-2 border-gray-200 rounded-primea-lg focus:ring-2 focus:ring-primea-yellow focus:border-primea-blue transition-all duration-200 font-primea bg-white/90"
                required
                :disabled="loading"
              />
            </div>

            <!-- Message d'erreur -->
            <div v-if="error" class="bg-red-50 border border-red-200 rounded-primea-lg p-4">
              <p class="text-red-600 text-sm font-primea">{{ error }}</p>
            </div>

            <!-- Bouton Envoyer -->
            <button
              type="submit"
              :disabled="loading"
              class="w-full text-white py-4 px-6 rounded-primea-lg text-lg font-bold transition-all duration-200 shadow-primea-lg transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none font-primea"
              :style="{ backgroundColor: '#272d63', color: '#ffffff' }"
              @mouseover="!loading && ($event.currentTarget.style.backgroundColor='#fab511', $event.currentTarget.style.color='#272d63')"
              @mouseleave="!loading && ($event.currentTarget.style.backgroundColor='#272d63', $event.currentTarget.style.color='#ffffff')"
            >
              <span v-if="loading" class="flex items-center justify-center pointer-events-none">
                <svg class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/>
                  <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" class="opacity-75"/>
                </svg>
                Envoi en cours...
              </span>
              <span v-else class="pointer-events-none">Envoyer le lien</span>
            </button>

            <!-- Retour connexion -->
            <div class="text-center">
              <router-link
                to="/login"
                class="text-sm text-primea-blue hover:text-primea-yellow font-semibold transition-colors duration-200 font-primea"
              >
                ← Retour à la connexion
              </router-link>
            </div>

          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue'
import axios from 'axios'

export default {
  name: 'ForgotPassword',
  setup() {
    const email = ref('')
    const loading = ref(false)
    const error = ref('')
    const success = ref('')

    const handleSubmit = async () => {
      try {
        loading.value = true
        error.value = ''
        success.value = ''

        // Validation basique
        if (!email.value) {
          throw new Error('Veuillez entrer votre adresse email')
        }

        // Appel API
        const response = await axios.post('/api/v1/auth/forgot-password', {
          email: email.value
        })

        if (response.data.success) {
          success.value = response.data.message || 'Un email de réinitialisation a été envoyé à votre adresse email.'
          email.value = '' // Vider le champ
        } else {
          error.value = response.data.message || 'Une erreur est survenue'
        }

      } catch (err) {
        console.error('Erreur forgot password:', err)

        // Gestion des erreurs
        if (err.response) {
          const status = err.response.status
          const data = err.response.data

          if (status === 422) {
            // Erreur de validation
            if (data.errors && data.errors.email) {
              error.value = data.errors.email[0]
            } else {
              error.value = data.message || 'Email invalide'
            }
          } else if (status === 404) {
            error.value = 'Aucun compte associé à cette adresse email'
          } else if (status === 429) {
            error.value = 'Trop de tentatives. Veuillez réessayer dans quelques minutes.'
          } else if (status >= 500) {
            error.value = 'Erreur du serveur. Veuillez réessayer plus tard.'
          } else {
            error.value = data.message || 'Une erreur est survenue'
          }
        } else if (err.request) {
          error.value = 'Impossible de se connecter au serveur. Vérifiez votre connexion internet.'
        } else {
          error.value = err.message || 'Une erreur est survenue'
        }
      } finally {
        loading.value = false
      }
    }

    return {
      email,
      loading,
      error,
      success,
      handleSubmit
    }
  }
}
</script>

<style scoped>
/* Variables CSS Primea */
:root {
  --primea-blue: #272d63;
  --primea-yellow: #fab511;
  --primea-white: #ffffff;
  --primea-blue-dark: #1a1e47;
  --primea-yellow-dark: #e09f0e;
  --font-primary: 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Couleurs de texte Primea */
.text-primea-blue {
  color: var(--primea-blue);
}

.text-primea-yellow {
  color: var(--primea-yellow);
}

/* Couleurs de fond Primea */
.bg-primea-blue {
  background-color: var(--primea-blue);
}

.bg-primea-yellow {
  background-color: var(--primea-yellow);
}

/* States hover Primea */
.hover\:text-primea-yellow:hover {
  color: var(--primea-yellow);
}

/* Focus states */
.focus\:ring-primea-yellow:focus {
  --tw-ring-color: var(--primea-yellow);
}

.focus\:border-primea-blue:focus {
  border-color: var(--primea-blue);
}

/* Coins arrondis Primea */
.rounded-primea-lg {
  border-radius: 16px;
}

.rounded-primea-xl {
  border-radius: 20px;
}

/* Ombres Primea */
.shadow-primea-lg {
  box-shadow: 0 8px 30px rgba(39, 45, 99, 0.15);
}

/* Police Primea */
.font-primea {
  font-family: var(--font-primary);
}

/* Animations */
.animate-spin {
  animation: spin 1s linear infinite;
}

.animate-fade-in {
  animation: fadeIn 0.8s ease-out;
}

.animate-slide-up {
  animation: slideUp 0.6s ease-out;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Transitions */
.transition-all {
  transition: all 0.2s ease-in-out;
}

.transition-colors {
  transition: color 0.2s ease-in-out, background-color 0.2s ease-in-out, border-color 0.2s ease-in-out;
}

/* Styles pour les inputs focus */
input:focus {
  outline: none;
}
</style>
