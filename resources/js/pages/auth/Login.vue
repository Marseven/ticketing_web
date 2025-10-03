<template>
  <div class="login-page font-primea relative overflow-hidden min-h-screen">
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

        <!-- Carte de connexion -->
        <div class="bg-white/95 backdrop-blur-sm rounded-primea-xl shadow-primea-lg p-8 animate-slide-up">
          
          <!-- Titre -->
          <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-primea-blue mb-2">
              Connexion
            </h1>
            <p class="text-gray-600 font-primea">
              Accédez à votre espace Primea
            </p>
          </div>

        <!-- Formulaire de connexion -->
        <form @submit.prevent="handleLogin" class="space-y-6">
          
          <!-- Onglets Email/Téléphone -->
          <div class="mb-6">
            <div class="flex rounded-primea-lg bg-white/50 p-1 border-2 border-gray-200">
              <button
                type="button"
                @click="switchLoginType('email')"
                class="flex-1 py-3 px-4 text-sm font-semibold rounded-primea transition-all duration-200 font-primea"
                :style="loginType === 'email' 
                  ? { backgroundColor: '#272d63', color: '#ffffff' }
                  : { backgroundColor: 'transparent', color: '#6b7280' }"
                @mouseover="if (loginType !== 'email') { $event.currentTarget.style.backgroundColor='#fab511'; $event.currentTarget.style.color='#272d63'; }"
                @mouseleave="if (loginType !== 'email') { $event.currentTarget.style.backgroundColor='transparent'; $event.currentTarget.style.color='#6b7280'; }"
              >
                Email
              </button>
              <button
                type="button"
                @click="switchLoginType('phone')"
                class="flex-1 py-3 px-4 text-sm font-semibold rounded-primea transition-all duration-200 font-primea"
                :style="loginType === 'phone' 
                  ? { backgroundColor: '#272d63', color: '#ffffff' }
                  : { backgroundColor: 'transparent', color: '#6b7280' }"
                @mouseover="if (loginType !== 'phone') { $event.currentTarget.style.backgroundColor='#fab511'; $event.currentTarget.style.color='#272d63'; }"
                @mouseleave="if (loginType !== 'phone') { $event.currentTarget.style.backgroundColor='transparent'; $event.currentTarget.style.color='#6b7280'; }"
              >
                Téléphone
              </button>
            </div>
          </div>

          <!-- Champ Email -->
          <div v-if="loginType === 'email'">
            <label for="email" class="block text-sm font-semibold text-primea-blue mb-2 font-primea">
              Adresse email
            </label>
            <input 
              type="email"
              id="email"
              v-model="loginForm.login"
              placeholder="votre.email@exemple.com"
              class="w-full px-4 py-3 border-2 border-gray-200 rounded-primea-lg focus:ring-2 focus:ring-primea-yellow focus:border-primea-blue transition-all duration-200 font-primea bg-white/90"
              required
            />
          </div>

          <!-- Champ Téléphone -->
          <div v-if="loginType === 'phone'">
            <label class="block text-sm font-semibold text-primea-blue mb-2 font-primea">
              Numéro de téléphone
            </label>
            <PhoneInput
              v-model="loginForm.login"
              placeholder="01 23 45 67"
              class="w-full"
              required
            />
          </div>

          <!-- Mot de passe -->
          <div>
            <label for="password" class="block text-sm font-semibold text-primea-blue mb-2 font-primea">
              Mot de passe
            </label>
            <div class="relative">
              <input 
                :type="showPassword ? 'text' : 'password'"
                id="password"
                v-model="loginForm.password"
                placeholder="Votre mot de passe"
                class="w-full px-4 py-3 border-2 border-gray-200 rounded-primea-lg focus:ring-2 focus:ring-primea-yellow focus:border-primea-blue transition-all duration-200 pr-12 font-primea bg-white/90"
                required
              />
              <button 
                type="button"
                @click="togglePasswordVisibility"
                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-primea-blue transition-colors"
              >
                <EyeIcon v-if="showPassword" class="w-5 h-5" />
                <EyeSlashIcon v-else class="w-5 h-5" />
              </button>
            </div>
          </div>

          <!-- Message d'erreur -->
          <div v-if="error" class="bg-red-50 border border-red-200 rounded-primea-lg p-4">
            <p class="text-red-600 text-sm font-primea">{{ error }}</p>
          </div>

          <!-- Bouton Connexion -->
          <button 
            type="submit"
            :disabled="loading"
            class="w-full text-white py-4 px-6 rounded-primea-lg text-lg font-bold transition-all duration-200 shadow-primea-lg transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none font-primea"
            :style="{ backgroundColor: '#272d63', color: '#ffffff' }"
            @mouseover="$event.currentTarget.style.backgroundColor='#fab511'; $event.currentTarget.style.color='#272d63'"
            @mouseleave="$event.currentTarget.style.backgroundColor='#272d63'; $event.currentTarget.style.color='#ffffff'"
          >
            <span v-if="loading" class="flex items-center justify-center pointer-events-none">
              <svg class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/>
                <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" class="opacity-75"/>
              </svg>
              Connexion en cours...
            </span>
            <span v-else class="pointer-events-none">Se connecter</span>
          </button>

          <!-- Mot de passe oublié -->
          <div class="text-center">
            <router-link 
              to="/forgot-password"
              class="text-sm text-primea-blue hover:text-primea-yellow font-semibold transition-colors duration-200 font-primea"
            >
              Mot de passe oublié ?
            </router-link>
          </div>

        </form>

        <!-- Lien création de compte -->
        <div class="mt-8 pt-6 border-t border-gray-200 text-center">
          <p class="text-sm text-gray-600 mb-4 font-primea">
            Nouveau sur Primea ?
          </p>
          <router-link 
            to="/register"
            class="text-primea-blue hover:text-primea-yellow font-bold text-sm transition-colors duration-200 font-primea"
          >
            Créer un compte pour suivre mes achats !
          </router-link>
        </div>
        
        </div>

        <!-- Lien récupérer ticket -->
        <div class="text-center mt-8">
          <router-link 
            to="/retrieve-ticket"
            class="text-white/90 hover:text-primea-yellow font-semibold text-sm transition-colors duration-200 font-primea bg-white/10 backdrop-blur-sm px-4 py-2 rounded-primea border border-white/20 hover:border-primea-yellow"
          >
            Récupérer mon ticket perdu
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../../stores/auth.js'
import PhoneInput from '../../components/PhoneInput.vue'
import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline'
import authUtils from '../../utils/auth'

export default {
  name: 'Login',
  components: {
    PhoneInput,
    EyeIcon,
    EyeSlashIcon
  },
  setup() {
    const router = useRouter()
    const route = useRoute()
    const authStore = useAuthStore()

    // État réactif
    const showPassword = ref(false)
    const loading = ref(false)
    const error = ref('')
    const loginType = ref('email')

    const loginForm = ref({
      login: '',
      password: ''
    })

    // Méthodes
    const togglePasswordVisibility = () => {
      showPassword.value = !showPassword.value
    }

    const switchLoginType = (type) => {
      loginType.value = type
      loginForm.value.login = '' // Réinitialiser le champ
      error.value = '' // Effacer les erreurs
    }

    const handleLogin = async () => {
      try {
        loading.value = true
        error.value = ''

        // Validation basique
        if (!loginForm.value.login || !loginForm.value.password) {
          throw new Error('Veuillez remplir tous les champs')
        }

        try {
          // Tentative de connexion avec l'API
          const result = await authStore.login({
            login: loginForm.value.login,
            password: loginForm.value.password
          })

          if (result.success) {
            // Vérifier s'il y a une URL de redirection dans les query params
            const redirectUrl = route.query.redirect
            
            if (redirectUrl) {
              // Si une URL de redirection existe, y aller directement
              router.push(redirectUrl)
            } else {
              // Sinon, redirection basée sur le niveau d'accès
              const accessLevel = result.access_level || 'client'
              
              switch (accessLevel) {
                case 'admin':
                  router.push('/admin/dashboard')
                  break
                case 'organizer':
                  router.push('/organizer/dashboard')
                  break
                default:
                  router.push('/')
              }
            }
          } else {
            throw new Error(result.message || 'Identifiants incorrects')
          }
        } catch (apiError) {
          // Si l'API n'est pas disponible, utiliser les comptes de test
          console.warn('API non disponible, utilisation des comptes de test:', apiError)
          
          const testAccounts = [
            { login: 'user@test.com', phone: '+241012345678', password: 'user123', role: 'user', name: 'Utilisateur Test' },
            { login: 'organizer@test.com', phone: '+241078901234', password: 'organizer123', role: 'organizer', name: 'Organisateur Test' },
            { login: 'admin@test.com', phone: '+241065432100', password: 'admin123', role: 'admin', name: 'Admin Test' }
          ]

          const account = testAccounts.find(acc => 
            (acc.login === loginForm.value.login || acc.phone === loginForm.value.login) && 
            acc.password === loginForm.value.password
          )

          if (!account) {
            throw new Error('Identifiants incorrects. Essayez un des comptes de test.')
          }

          // Simuler la connexion
          await new Promise(resolve => setTimeout(resolve, 1000))
          
          // Stocker les informations d'authentification avec authUtils
          const token = 'test-token-' + account.role
          authUtils.saveAuth(token, { 
            name: account.name, 
            email: account.login 
          }, account.role)
          
          // Vérifier s'il y a une URL de redirection
          const redirectUrl = route.query.redirect
          
          if (redirectUrl) {
            // Si une URL de redirection existe, y aller directement
            router.push(redirectUrl)
          } else {
            // Sinon, redirection selon le rôle
            switch (account.role) {
              case 'admin':
                router.push('/admin/dashboard')
                break
              case 'organizer':
                router.push('/organizer/dashboard')
                break
              default:
                router.push('/')
            }
          }
        }

      } catch (err) {
        error.value = err.message || 'Erreur lors de la connexion'
      } finally {
        loading.value = false
      }
    }

    return {
      showPassword,
      loading,
      error,
      loginType,
      loginForm,
      togglePasswordVisibility,
      switchLoginType,
      handleLogin
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

/* Fond dégradé Primea */
.bg-primea-gradient {
  background: linear-gradient(135deg, var(--primea-blue) 0%, var(--primea-blue-dark) 100%);
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

.hover\:text-primea-yellow:hover {
  color: var(--primea-yellow);
}

.hover\:border-primea-yellow:hover {
  border-color: var(--primea-yellow);
}

/* Focus states */
.focus\:ring-primea-yellow:focus {
  --tw-ring-color: var(--primea-yellow);
}

.focus\:border-primea-blue:focus {
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