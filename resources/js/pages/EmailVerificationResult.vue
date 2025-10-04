<template>
  <div class="min-h-screen bg-gradient-to-br from-primea-blue to-primea-blue-dark flex items-center justify-center px-4 py-12 font-primea">
    <!-- Conteneur principal -->
    <div class="max-w-md w-full">
      <!-- Logo Primea -->
      <div class="text-center mb-8 animate-fade-in">
        <img src="/images/logo_white.png" alt="Primea" class="h-16 mx-auto mb-6" />
      </div>

      <!-- Carte de résultat -->
      <div class="bg-white rounded-primea-xl shadow-primea-lg p-8 animate-slide-up">

        <!-- Icône de succès -->
        <div v-if="status === 'success'" class="text-center mb-6">
          <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
          </div>
          <h1 class="text-2xl font-bold text-green-600 mb-2">Email vérifié !</h1>
          <p class="text-gray-600">{{ message }}</p>
        </div>

        <!-- Icône d'info -->
        <div v-else-if="status === 'info'" class="text-center mb-6">
          <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-10 h-10 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <h1 class="text-2xl font-bold text-blue-600 mb-2">Information</h1>
          <p class="text-gray-600">{{ message }}</p>
        </div>

        <!-- Icône d'erreur -->
        <div v-else class="text-center mb-6">
          <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </div>
          <h1 class="text-2xl font-bold text-red-600 mb-2">Erreur</h1>
          <p class="text-gray-600">{{ message }}</p>
        </div>

        <!-- Boutons d'action -->
        <div class="mt-8 space-y-3">
          <router-link
            v-if="status === 'success'"
            to="/login"
            class="block w-full text-center bg-primea-blue text-white py-3 px-6 rounded-primea-lg font-semibold hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200 shadow-primea"
          >
            Se connecter maintenant
          </router-link>

          <router-link
            v-else
            to="/login"
            class="block w-full text-center bg-primea-blue text-white py-3 px-6 rounded-primea-lg font-semibold hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200 shadow-primea"
          >
            Retour à la connexion
          </router-link>

          <router-link
            to="/"
            class="block w-full text-center border-2 border-primea-blue text-primea-blue py-3 px-6 rounded-primea-lg font-semibold hover:bg-primea-blue hover:text-white transition-all duration-200"
          >
            Retour à l'accueil
          </router-link>
        </div>

        <!-- Message d'aide -->
        <div v-if="status === 'error'" class="mt-6 pt-6 border-t border-gray-200 text-center">
          <p class="text-sm text-gray-600 mb-3">
            Besoin d'aide ?
          </p>
          <a href="mailto:support@primea.ga" class="text-primea-blue hover:text-primea-yellow font-semibold text-sm">
            Contacter le support
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'

export default {
  name: 'EmailVerificationResult',
  setup() {
    const route = useRoute()
    const status = ref('error')
    const message = ref('Une erreur est survenue')

    onMounted(() => {
      // Récupérer les paramètres de l'URL
      status.value = route.query.status || 'error'
      message.value = route.query.message || 'Une erreur est survenue'
    })

    return {
      status,
      message
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

.bg-primea-blue-dark {
  background-color: var(--primea-blue-dark);
}

.bg-primea-yellow {
  background-color: var(--primea-yellow);
}

/* States hover Primea */
.hover\:bg-primea-yellow:hover {
  background-color: var(--primea-yellow) !important;
}

.hover\:text-primea-blue:hover {
  color: var(--primea-blue) !important;
}

.hover\:text-primea-yellow:hover {
  color: var(--primea-yellow);
}

.hover\:bg-primea-blue:hover {
  background-color: var(--primea-blue);
}

.hover\:text-white:hover {
  color: white;
}

/* Border colors */
.border-primea-blue {
  border-color: var(--primea-blue);
}

/* Coins arrondis Primea */
.rounded-primea {
  border-radius: 12px;
}

.rounded-primea-lg {
  border-radius: 16px;
}

.rounded-primea-xl {
  border-radius: 20px;
}

/* Ombres Primea */
.shadow-primea {
  box-shadow: 0 4px 20px rgba(39, 45, 99, 0.1);
}

.shadow-primea-lg {
  box-shadow: 0 8px 30px rgba(39, 45, 99, 0.15);
}

/* Police Primea */
.font-primea {
  font-family: var(--font-primary);
}

/* Animations */
.animate-fade-in {
  animation: fadeIn 0.8s ease-out;
}

.animate-slide-up {
  animation: slideUp 0.6s ease-out;
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

/* Gradients */
.bg-gradient-to-br {
  background-image: linear-gradient(to bottom right, var(--tw-gradient-stops));
}

.from-primea-blue {
  --tw-gradient-from: var(--primea-blue);
  --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(39, 45, 99, 0));
}

.to-primea-blue-dark {
  --tw-gradient-to: var(--primea-blue-dark);
}
</style>
