<template>
  <div class="login-page relative overflow-hidden min-h-screen bg-white md:bg-transparent">
    <!-- Background Image (Desktop only) -->
    <div class="hidden md:block absolute inset-0">
      <img
        src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80"
        alt="Événements"
        class="w-full h-full object-cover opacity-40"
      />
      <div class="absolute inset-0 bg-blue-900/60"></div>
    </div>

    <!-- Main Content -->
    <div class="relative z-10 min-h-screen md:flex md:items-center md:justify-center px-4 py-8 md:py-12">
      <div class="max-w-md w-full mx-auto">
        <!-- Desktop Logo -->
        <div class="hidden md:block text-center mb-8 animate-fade-in">
          <img src="/images/logo_white.png" alt="Logo" class="h-16 mx-auto mb-6" />
        </div>

        <!-- Login Card -->
        <div class="bg-white md:bg-white/95 md:backdrop-blur-sm rounded-2xl md:rounded-3xl shadow-lg md:shadow-2xl p-6 md:p-8">

          <!-- Title -->
          <div class="text-center mb-6 md:mb-8">
            <h1 class="text-2xl md:text-3xl font-bold text-blue-900 mb-2">
              Espace Organisateur
            </h1>
            <p class="text-gray-600 text-sm md:text-base">
              Connectez-vous à votre compte organisateur
            </p>
          </div>

          <!-- Login Form -->
          <form @submit.prevent="handleLogin" class="space-y-5 md:space-y-6">

            <!-- Email/Phone Tabs -->
            <div class="mb-4 md:mb-6">
              <div class="flex rounded-xl bg-gray-100 p-1 border border-gray-200">
                <button
                  type="button"
                  @click="switchLoginType('email')"
                  class="flex-1 py-3 px-4 text-sm font-semibold rounded-lg transition-all duration-200"
                  :class="loginType === 'email'
                    ? 'bg-blue-900 text-white shadow-md'
                    : 'bg-transparent text-gray-600 hover:bg-yellow-500 hover:text-blue-900'"
                >
                  Email
                </button>
                <button
                  type="button"
                  @click="switchLoginType('phone')"
                  class="flex-1 py-3 px-4 text-sm font-semibold rounded-lg transition-all duration-200"
                  :class="loginType === 'phone'
                    ? 'bg-blue-900 text-white shadow-md'
                    : 'bg-transparent text-gray-600 hover:bg-yellow-500 hover:text-blue-900'"
                >
                  Téléphone
                </button>
              </div>
            </div>

            <!-- Email Field -->
            <div v-if="loginType === 'email'">
              <label for="email" class="block text-sm font-semibold text-blue-900 mb-2">
                Adresse email
              </label>
              <input
                type="email"
                id="email"
                v-model="loginForm.login"
                placeholder="votre.email@exemple.com"
                class="w-full px-4 py-3 md:py-3.5 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-yellow-500 focus:border-blue-900 transition-all duration-200 bg-white text-base"
                required
              />
            </div>

            <!-- Phone Field -->
            <div v-if="loginType === 'phone'">
              <label class="block text-sm font-semibold text-blue-900 mb-2">
                Numéro de téléphone
              </label>
              <PhoneInput
                v-model="loginForm.login"
                placeholder="01 23 45 67"
                class="w-full"
                required
              />
            </div>

            <!-- Password Field -->
            <div>
              <label for="password" class="block text-sm font-semibold text-blue-900 mb-2">
                Mot de passe
              </label>
              <div class="relative">
                <input
                  :type="showPassword ? 'text' : 'password'"
                  id="password"
                  v-model="loginForm.password"
                  placeholder="Votre mot de passe"
                  class="w-full px-4 py-3 md:py-3.5 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-yellow-500 focus:border-blue-900 transition-all duration-200 pr-12 bg-white text-base"
                  required
                />
                <button
                  type="button"
                  @click="togglePasswordVisibility"
                  class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-blue-900 transition-colors p-1"
                >
                  <svg v-if="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  </svg>
                  <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                  </svg>
                </button>
              </div>
            </div>

            <!-- Error Message -->
            <div v-if="error" class="bg-red-50 border-2 border-red-200 rounded-xl p-4">
              <p class="text-red-600 text-sm font-medium">{{ error }}</p>
            </div>

            <!-- Info Message -->
            <div v-if="info" class="bg-blue-50 border-2 border-blue-200 rounded-xl p-4">
              <p class="text-blue-600 text-sm font-medium">{{ info }}</p>
            </div>

            <!-- Success Message -->
            <div v-if="success" class="bg-green-50 border-2 border-green-200 rounded-xl p-4">
              <p class="text-green-600 text-sm font-medium">{{ success }}</p>
            </div>

            <!-- Submit Button -->
            <button
              type="submit"
              :disabled="loading"
              class="w-full bg-blue-900 text-white py-4 px-6 rounded-xl text-base md:text-lg font-bold transition-all duration-200 shadow-lg hover:bg-yellow-500 hover:text-blue-900 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-blue-900 disabled:hover:text-white transform hover:scale-105 disabled:transform-none"
            >
              <span v-if="loading" class="flex items-center justify-center">
                <svg class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/>
                  <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" class="opacity-75"/>
                </svg>
                Connexion en cours...
              </span>
              <span v-else>Se connecter</span>
            </button>

            <!-- Forgot Password Link -->
            <div class="text-center">
              <router-link
                to="/forgot-password"
                class="text-sm text-blue-900 hover:text-yellow-500 font-semibold transition-colors duration-200 inline-block"
              >
                Mot de passe oublié ?
              </router-link>
            </div>

          </form>

          <!-- Create Account Link -->
          <div class="mt-6 md:mt-8 pt-6 border-t border-gray-200 text-center">
            <p class="text-sm text-gray-600">
              Vous n'avez pas de compte organisateur ?
              <router-link
                to="/register-organizer"
                class="text-blue-900 hover:text-yellow-500 font-bold transition-colors duration-200 ml-1"
              >
                Créer un compte
              </router-link>
            </p>
            <p class="text-sm text-gray-600 mt-2">
              Utilisateur régulier ?
              <router-link
                to="/login"
                class="text-blue-900 hover:text-yellow-500 font-semibold transition-colors duration-200 ml-1"
              >
                Connexion client
              </router-link>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../../stores/auth.js'
import PhoneInput from '../../components/PhoneInput.vue'

export default {
  name: 'LoginOrganizer',
  components: {
    PhoneInput
  },
  setup() {
    const router = useRouter()
    const route = useRoute()
    const authStore = useAuthStore()

    // Reactive state
    const showPassword = ref(false)
    const loading = ref(false)
    const error = ref('')
    const info = ref('')
    const success = ref('')
    const loginType = ref('email')

    // Show success message from query params (after registration)
    if (route.query.message) {
      success.value = route.query.message
    }

    // Redirect if already authenticated as organizer
    onMounted(() => {
      if (authStore.isAuthenticated && authStore.user) {
        const accessLevel = authStore.user.access_level || 'client'

        if (accessLevel === 'organizer') {
          router.push('/organizer/dashboard')
        } else if (accessLevel === 'admin') {
          router.push('/admin/dashboard')
        }
        // Si c'est un client, on le laisse se connecter (peut-être qu'il veut créer un compte organisateur)
      }
    })

    const loginForm = ref({
      login: '',
      password: ''
    })

    // Methods
    const togglePasswordVisibility = () => {
      showPassword.value = !showPassword.value
    }

    const switchLoginType = (type) => {
      loginType.value = type
      loginForm.value.login = ''
      error.value = ''
      info.value = ''
      success.value = ''
    }

    const handleLogin = async () => {
      try {
        loading.value = true
        error.value = ''
        info.value = ''
        success.value = ''

        // Basic validation
        if (!loginForm.value.login || !loginForm.value.password) {
          throw new Error('Veuillez remplir tous les champs')
        }

        try {
          // Attempt login with API
          const result = await authStore.login({
            login: loginForm.value.login,
            password: loginForm.value.password
          })

          if (result.success) {
            // Vérifier si l'utilisateur est bien un organisateur ou admin
            const accessLevel = result.access_level || 'client'

            if (accessLevel !== 'organizer' && accessLevel !== 'admin') {
              error.value = 'Ce compte n\'est pas un compte organisateur. Veuillez utiliser la connexion client.'
              await authStore.logout()
              loading.value = false
              return
            }

            // Show info message if email is not verified
            if (result.email_verification_required) {
              info.value = result.message || 'N\'oubliez pas de vérifier votre adresse email.'
            }

            // Check if there's a redirect URL in query params
            const redirectUrl = route.query.redirect

            if (redirectUrl) {
              router.push(redirectUrl)
            } else {
              // Redirect based on access level
              if (accessLevel === 'admin') {
                router.push('/admin/dashboard')
              } else {
                router.push('/organizer/dashboard')
              }
            }
          } else {
            error.value = result.message || 'Identifiants incorrects'
            return
          }
        } catch (apiError) {
          console.error('Erreur de connexion:', apiError)

          // Parse error to display appropriate message
          if (apiError.response) {
            const status = apiError.response.status
            const data = apiError.response.data

            if (status === 401 || status === 422) {
              error.value = data.message || 'Email ou mot de passe incorrect'
            } else if (status === 403) {
              error.value = data.message || 'Accès refusé. Votre compte est peut-être inactif.'
            } else if (status === 429) {
              error.value = 'Trop de tentatives de connexion. Veuillez réessayer dans quelques minutes.'
            } else if (status >= 500) {
              error.value = 'Erreur du serveur. Veuillez réessayer plus tard.'
            } else {
              error.value = data.message || 'Une erreur est survenue lors de la connexion'
            }
          } else if (apiError.request) {
            error.value = 'Impossible de se connecter au serveur. Vérifiez votre connexion internet.'
          } else {
            error.value = apiError.message || 'Une erreur est survenue'
          }

          return
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
      info,
      success,
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
/* Animations */
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

.animate-fade-in {
  animation: fadeIn 0.8s ease-out;
}

/* Focus states */
input:focus {
  outline: none;
}

/* Smooth transitions */
* {
  transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}

/* Touch-friendly on mobile */
@media (max-width: 768px) {
  input,
  button {
    font-size: 16px; /* Prevents zoom on iOS */
  }
}
</style>
