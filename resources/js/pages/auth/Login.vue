<template>
  <div class="login-page relative overflow-hidden min-h-screen bg-white md:bg-transparent">
    <!-- Background Image (Desktop only) -->
    <div class="hidden md:block absolute inset-0">
      <img
        src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80"
        alt="Événements"
        class="w-full h-full object-cover opacity-40"
      />
      <div class="absolute inset-0 bg-primea-blue/60"></div>
    </div>

    <!-- Main Content -->
    <div class="relative z-10 min-h-screen md:flex md:items-center md:justify-center px-4 py-8 md:py-12">
      <div class="max-w-md w-full mx-auto">
        <!-- Desktop Logo -->
        <div class="hidden md:block text-center mb-8 animate-fade-in">
          <img src="/images/logo_white.png?v=3" alt="Logo" class="h-16 mx-auto mb-6" />
        </div>

        <!-- Login Card -->
        <div class="bg-white md:bg-white/95 md:backdrop-blur-sm rounded-2xl md:rounded-3xl shadow-lg md:shadow-2xl p-6 md:p-8">

          <!-- Title -->
          <div class="text-center mb-6 md:mb-8">
            <h1 class="text-2xl md:text-3xl font-bold text-primea-blue mb-2">
              Bienvenue
            </h1>
            <p class="text-gray-600 text-sm md:text-base">
              Connectez-vous à votre compte
            </p>
          </div>

          <!-- Login Form -->
          <form v-if="!twoFactor.challenge" @submit.prevent="handleLogin" class="space-y-5 md:space-y-6">

            <!-- Email/Phone Tabs -->
            <div class="mb-4 md:mb-6">
              <div class="flex rounded-xl bg-gray-100 p-1 border border-gray-200">
                <button
                  type="button"
                  @click="switchLoginType('email')"
                  class="flex-1 py-3 px-4 text-sm font-semibold rounded-lg transition-all duration-200"
                  :class="loginType === 'email'
                    ? 'bg-primea-blue text-white shadow-md'
                    : 'bg-transparent text-gray-600 hover:bg-primea-yellow hover:text-primea-blue'"
                >
                  Email
                </button>
                <button
                  type="button"
                  @click="switchLoginType('phone')"
                  class="flex-1 py-3 px-4 text-sm font-semibold rounded-lg transition-all duration-200"
                  :class="loginType === 'phone'
                    ? 'bg-primea-blue text-white shadow-md'
                    : 'bg-transparent text-gray-600 hover:bg-primea-yellow hover:text-primea-blue'"
                >
                  Téléphone
                </button>
              </div>
            </div>

            <!-- Email Field -->
            <div v-if="loginType === 'email'">
              <label for="email" class="block text-sm font-semibold text-primea-blue mb-2">
                Adresse email
              </label>
              <input
                type="email"
                id="email"
                v-model="loginForm.login"
                placeholder="votre.email@exemple.com"
                class="w-full px-4 py-3 md:py-3.5 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-primea-yellow focus:border-primea-blue transition-all duration-200 bg-white text-base"
                required
              />
            </div>

            <!-- Phone Field -->
            <div v-if="loginType === 'phone'">
              <label class="block text-sm font-semibold text-primea-blue mb-2">
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
              <label for="password" class="block text-sm font-semibold text-primea-blue mb-2">
                Mot de passe
              </label>
              <div class="relative">
                <input
                  :type="showPassword ? 'text' : 'password'"
                  id="password"
                  v-model="loginForm.password"
                  placeholder="Votre mot de passe"
                  class="w-full px-4 py-3 md:py-3.5 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-primea-yellow focus:border-primea-blue transition-all duration-200 pr-12 bg-white text-base"
                  required
                />
                <button
                  type="button"
                  @click="togglePasswordVisibility"
                  class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-primea-blue transition-colors p-1"
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
              class="w-full bg-primea-blue text-white py-4 px-6 rounded-xl text-base md:text-lg font-bold transition-all duration-200 shadow-lg hover:bg-primea-yellow hover:text-primea-blue disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-primea-blue disabled:hover:text-white transform hover:scale-105 disabled:transform-none"
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
                class="text-sm text-primea-blue hover:text-primea-yellow font-semibold transition-colors duration-200 inline-block"
              >
                Mot de passe oublié ?
              </router-link>
            </div>

          </form>

          <!-- Second facteur : le mot de passe a été accepté, mais aucune
               session n'existe encore. Elle ne naîtra qu'avec un code valide. -->
          <div v-else class="space-y-5">
            <div class="text-center">
              <div class="mx-auto w-14 h-14 rounded-2xl bg-primea-blue/10 flex items-center justify-center mb-4">
                <svg class="w-7 h-7 text-primea-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
              </div>
              <h2 class="text-xl font-bold text-primea-blue mb-1">Vérification en deux étapes</h2>
              <p class="text-sm text-gray-600">
                {{ twoFactor.useRecovery
                  ? 'Saisissez l\'un de vos codes de secours.'
                  : 'Saisissez le code affiché par votre application d\'authentification.' }}
              </p>
            </div>

            <form @submit.prevent="submitTwoFactor" class="space-y-4">
              <input
                v-if="!twoFactor.useRecovery"
                v-model="twoFactor.code"
                type="text"
                inputmode="numeric"
                autocomplete="one-time-code"
                maxlength="6"
                placeholder="000000"
                aria-label="Code à six chiffres"
                class="w-full px-4 py-3 text-center text-2xl tracking-[0.4em] font-semibold rounded-xl border border-gray-200 focus:border-primea-blue focus:ring-2 focus:ring-primea-blue/20 outline-none"
              />

              <input
                v-else
                v-model="twoFactor.recoveryCode"
                type="text"
                autocomplete="off"
                placeholder="XXXXX-XXXXX"
                aria-label="Code de secours"
                class="w-full px-4 py-3 text-center text-lg tracking-widest font-medium uppercase rounded-xl border border-gray-200 focus:border-primea-blue focus:ring-2 focus:ring-primea-blue/20 outline-none"
              />

              <button
                type="submit"
                :disabled="verifyingCode"
                class="w-full py-3 rounded-xl bg-primea-blue text-white font-semibold min-h-[48px] disabled:opacity-60 transition"
              >
                {{ verifyingCode ? 'Vérification…' : 'Valider' }}
              </button>
            </form>

            <div class="flex items-center justify-between text-sm">
              <button type="button" class="text-primea-blue hover:underline" @click="toggleRecovery">
                {{ twoFactor.useRecovery ? 'Utiliser mon application' : 'Utiliser un code de secours' }}
              </button>
              <button type="button" class="text-gray-500 hover:underline" @click="cancelTwoFactor">
                Annuler
              </button>
            </div>
          </div>

          <!-- Create Account Link -->
          <div class="mt-6 md:mt-8 pt-6 border-t border-gray-200 text-center">
            <p class="text-sm text-gray-600">
              Vous n'avez pas de compte ?
              <router-link
                to="/register"
                class="text-primea-blue hover:text-primea-yellow font-bold transition-colors duration-200 ml-1"
              >
                Créer un compte
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
  name: 'Login',
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

    // Redirect if already authenticated
    onMounted(() => {
      if (authStore.isAuthenticated && authStore.user) {
        const accessLevel = authStore.user.access_level || 'client'

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

    // Le second facteur vit à part du formulaire : tant que `challenge` est
    // vide, rien n'a été demandé et l'écran ne change pas.
    const twoFactor = ref({ challenge: '', code: '', recoveryCode: '', useRecovery: false })
    const verifyingCode = ref(false)

    /**
     * Où atterrir une fois la session réellement ouverte. Partagé par la
     * connexion simple et par le second facteur, pour que les deux mènent au
     * même endroit.
     */
    const goAfterLogin = (result) => {
      if (result.email_verification_required) {
        info.value = result.message || 'N\'oubliez pas de vérifier votre adresse email.'
      }

      const redirectUrl = route.query.redirect

      if (redirectUrl) {
        router.push(redirectUrl)
        return
      }

      switch (result.access_level || 'client') {
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

    const toggleRecovery = () => {
      twoFactor.value.useRecovery = !twoFactor.value.useRecovery
      error.value = ''
    }

    const cancelTwoFactor = () => {
      twoFactor.value = { challenge: '', code: '', recoveryCode: '', useRecovery: false }
      error.value = ''
    }

    /**
     * C'est ici que la session naît : le mot de passe seul n'a rien ouvert.
     */
    const submitTwoFactor = async () => {
      error.value = ''
      verifyingCode.value = true

      try {
        const result = await authStore.completeTwoFactor({
          challenge: twoFactor.value.challenge,
          code: twoFactor.value.useRecovery ? null : twoFactor.value.code,
          recoveryCode: twoFactor.value.useRecovery ? twoFactor.value.recoveryCode : null
        })

        if (result.success) {
          cancelTwoFactor()
          goAfterLogin(result)
          return
        }

        error.value = result.message || 'Code incorrect'
      } catch (apiError) {
        const data = apiError.response?.data

        // Défi brûlé ou expiré : il faut repartir du mot de passe, sinon
        // l'utilisateur s'acharne sur un écran qui n'ouvrira plus rien.
        if (['CHALLENGE_EXPIRED', 'CHALLENGE_BURNED'].includes(data?.error_code)) {
          cancelTwoFactor()
          error.value = data.message || 'Session expirée, reconnectez-vous.'
          return
        }

        error.value = data?.message || 'Code incorrect'
      } finally {
        verifyingCode.value = false
      }
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

          // Mot de passe accepté, mais la session n'existe pas encore : le
          // serveur n'a rendu qu'un défi.
          if (result.two_factor_required) {
            twoFactor.value = {
              challenge: result.challenge,
              code: '',
              recoveryCode: '',
              useRecovery: false
            }
            return
          }

          if (result.success) {
            goAfterLogin(result)
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
      handleLogin,
      twoFactor,
      verifyingCode,
      submitTwoFactor,
      toggleRecovery,
      cancelTwoFactor
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
