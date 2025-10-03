<template>
  <div class="email-verification-page font-primea relative overflow-hidden min-h-screen">
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

        <!-- Carte de vérification -->
        <div class="bg-white/95 backdrop-blur-sm rounded-primea-xl shadow-primea-lg p-8 animate-slide-up">
          
          <!-- État de vérification réussie -->
          <div v-if="verificationStatus === 'success'" class="text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <CheckCircleIcon class="w-10 h-10 text-green-500" />
            </div>
            <h1 class="text-2xl font-bold text-primea-blue mb-4">
              Email vérifié !
            </h1>
            <p class="text-gray-600 mb-6">
              Votre adresse email a été vérifiée avec succès. Vous pouvez maintenant accéder à toutes les fonctionnalités de Primea.
            </p>
            <router-link 
              to="/login"
              class="inline-block w-full py-3 px-6 bg-primea-blue text-white rounded-primea hover:bg-primea-yellow hover:text-primea-blue font-semibold transition-all duration-200 text-center"
            >
              Se connecter
            </router-link>
          </div>

          <!-- État de vérification en attente -->
          <div v-else-if="verificationStatus === 'pending'" class="text-center">
            <div class="w-16 h-16 bg-primea-blue/10 rounded-full flex items-center justify-center mx-auto mb-4">
              <EnvelopeIcon class="w-10 h-10 text-primea-blue" />
            </div>
            <h1 class="text-2xl font-bold text-primea-blue mb-4">
              Vérifiez votre email
            </h1>
            <p class="text-gray-600 mb-6">
              Un email de vérification a été envoyé à <strong>{{ userEmail }}</strong>. 
              Cliquez sur le lien dans l'email pour activer votre compte.
            </p>
            
            <!-- Notice de renvoi -->
            <div class="bg-primea-blue/5 border border-primea-blue/20 rounded-primea p-4 mb-6">
              <p class="text-sm text-primea-blue">
                <strong>Vous n'avez pas reçu l'email ?</strong><br>
                Vérifiez votre dossier spam ou cliquez sur le bouton ci-dessous pour renvoyer un nouvel email.
              </p>
            </div>

            <!-- Bouton de renvoi -->
            <button 
              @click="resendVerification"
              :disabled="resending || resendCooldown > 0"
              class="w-full py-3 px-6 bg-primea-yellow text-primea-blue rounded-primea hover:bg-primea-blue hover:text-white font-semibold transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed mb-4"
            >
              <span v-if="resending" class="flex items-center justify-center">
                <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Envoi en cours...
              </span>
              <span v-else-if="resendCooldown > 0">
                Renvoyer dans {{ resendCooldown }}s
              </span>
              <span v-else>
                Renvoyer l'email de vérification
              </span>
            </button>

            <!-- Message de succès du renvoi -->
            <div v-if="resendSuccess" class="bg-green-50 border border-green-200 rounded-primea p-4 mb-4">
              <p class="text-green-700 text-sm font-medium">
                Email de vérification renvoyé avec succès !
              </p>
            </div>

            <!-- Lien vers la connexion -->
            <div class="text-center">
              <router-link 
                to="/login"
                class="text-sm text-primea-blue hover:text-primea-yellow font-semibold transition-colors duration-200"
              >
                Retour à la connexion
              </router-link>
            </div>
          </div>

          <!-- État d'erreur -->
          <div v-else-if="verificationStatus === 'error'" class="text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <XMarkIcon class="w-10 h-10 text-red-500" />
            </div>
            <h1 class="text-2xl font-bold text-red-600 mb-4">
              Erreur de vérification
            </h1>
            <p class="text-gray-600 mb-6">
              {{ errorMessage || 'Le lien de vérification est invalide ou a expiré.' }}
            </p>
            <router-link 
              to="/register"
              class="inline-block w-full py-3 px-6 bg-primea-blue text-white rounded-primea hover:bg-primea-yellow hover:text-primea-blue font-semibold transition-all duration-200 text-center"
            >
              Créer un nouveau compte
            </router-link>
          </div>

          <!-- État de chargement -->
          <div v-else class="text-center">
            <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-primea-blue mx-auto mb-4"></div>
            <p class="text-gray-500">Vérification en cours...</p>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { authService } from '../../services/api.js'
import { 
  CheckCircleIcon, 
  EnvelopeIcon, 
  XMarkIcon 
} from '@heroicons/vue/24/outline'

export default {
  name: 'EmailVerification',
  components: {
    CheckCircleIcon,
    EnvelopeIcon,
    XMarkIcon
  },
  setup() {
    const route = useRoute()
    const router = useRouter()
    const authStore = useAuthStore()

    const verificationStatus = ref('loading') // loading, success, pending, error
    const errorMessage = ref('')
    const resending = ref(false)
    const resendSuccess = ref(false)
    const resendCooldown = ref(0)

    const userEmail = computed(() => authStore.user?.email || '')

    // Vérifier l'email au montage si on a les paramètres
    onMounted(async () => {
      const { id, hash } = route.params

      if (id && hash) {
        // Vérification directe via lien
        await verifyEmailWithLink(id, hash)
      } else {
        // Vérifier le statut de l'utilisateur connecté
        await checkEmailStatus()
      }
    })

    const verifyEmailWithLink = async (id, hash) => {
      try {
        const response = await fetch(`/api/v1/auth/email/verify/${id}/${hash}`)
        const data = await response.json()

        if (response.ok) {
          verificationStatus.value = 'success'
        } else {
          verificationStatus.value = 'error'
          errorMessage.value = data.message
        }
      } catch (error) {
        console.error('Erreur de vérification:', error)
        verificationStatus.value = 'error'
        errorMessage.value = 'Une erreur est survenue'
      }
    }

    const checkEmailStatus = async () => {
      try {
        if (!authStore.isAuthenticated) {
          // Rediriger vers login si pas connecté
          router.push('/login')
          return
        }

        const response = await authService.checkEmailVerification()
        
        if (response.data.email_verified) {
          verificationStatus.value = 'success'
        } else {
          verificationStatus.value = 'pending'
        }
      } catch (error) {
        console.error('Erreur lors de la vérification du statut:', error)
        verificationStatus.value = 'pending'
      }
    }

    const resendVerification = async () => {
      if (resendCooldown.value > 0) return

      try {
        resending.value = true
        resendSuccess.value = false

        await authService.resendEmailVerification()
        
        resendSuccess.value = true
        startCooldown()

        // Effacer le message de succès après 5 secondes
        setTimeout(() => {
          resendSuccess.value = false
        }, 5000)

      } catch (error) {
        console.error('Erreur lors du renvoi:', error)
        errorMessage.value = 'Impossible de renvoyer l\'email'
      } finally {
        resending.value = false
      }
    }

    const startCooldown = () => {
      resendCooldown.value = 60 // 60 secondes
      const timer = setInterval(() => {
        resendCooldown.value--
        if (resendCooldown.value <= 0) {
          clearInterval(timer)
        }
      }, 1000)
    }

    return {
      verificationStatus,
      errorMessage,
      resending,
      resendSuccess,
      resendCooldown,
      userEmail,
      resendVerification
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
.hover\:bg-primea-yellow:hover {
  background-color: var(--primea-yellow) !important;
}

.hover\:text-primea-blue:hover {
  color: var(--primea-blue) !important;
}

.hover\:bg-primea-blue:hover {
  background-color: var(--primea-blue) !important;
}

.hover\:text-white:hover {
  color: white !important;
}

.hover\:text-primea-yellow:hover {
  color: var(--primea-yellow);
}

/* Coins arrondis Primea */
.rounded-primea {
  border-radius: 12px;
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

.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Transitions */
.transition-all {
  transition: all 0.2s ease-in-out;
}

.transition-colors {
  transition: color 0.2s ease-in-out, background-color 0.2s ease-in-out, border-color 0.2s ease-in-out;
}
</style>