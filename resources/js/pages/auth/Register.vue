<template>
  <div class="register-page relative overflow-hidden min-h-screen bg-white md:bg-transparent">
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
          <img src="/images/logo_white.png" alt="Logo" class="h-16 mx-auto mb-6" />
        </div>

        <!-- Register Card -->
        <div class="bg-white md:bg-white/95 md:backdrop-blur-sm rounded-2xl md:rounded-3xl shadow-lg md:shadow-2xl p-6 md:p-8">

          <!-- Title -->
          <div class="text-center mb-6 md:mb-8">
            <h1 class="text-2xl md:text-3xl font-bold text-primea-blue mb-2">
              Créer un compte
            </h1>
            <p class="text-gray-600 text-sm md:text-base">
              Pour suivre vos achats de tickets
            </p>
          </div>

          <!-- Registration Form -->
          <form class="space-y-4 md:space-y-5" @submit.prevent="register">
            <!-- Full Name -->
            <div>
              <label for="name" class="block text-sm font-semibold text-primea-blue mb-2">
                Nom complet <span class="text-red-500">*</span>
              </label>
              <input
                id="name"
                v-model="form.name"
                type="text"
                required
                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-primea-yellow focus:border-primea-blue transition-all duration-200 bg-white text-base"
                placeholder="Votre nom complet"
              />
            </div>

            <!-- Email -->
            <div>
              <label for="email" class="block text-sm font-semibold text-primea-blue mb-2">
                Adresse email <span class="text-gray-400 text-xs font-normal">(optionnel)</span>
              </label>
              <input
                id="email"
                v-model="form.email"
                type="email"
                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-primea-yellow focus:border-primea-blue transition-all duration-200 bg-white text-base"
                placeholder="votre@email.com"
              />
            </div>

            <!-- Phone -->
            <div>
              <label for="phone" class="block text-sm font-semibold text-primea-blue mb-2">
                Numéro de téléphone <span class="text-red-500">*</span>
              </label>
              <PhoneInput
                id="phone"
                v-model="form.phone"
                placeholder="XX XX XX XX"
                :required="true"
                default-country="GA"
              />
            </div>

            <!-- Password -->
            <div>
              <label for="password" class="block text-sm font-semibold text-primea-blue mb-2">
                Mot de passe <span class="text-red-500">*</span>
              </label>
              <input
                id="password"
                v-model="form.password"
                type="password"
                required
                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-primea-yellow focus:border-primea-blue transition-all duration-200 bg-white text-base"
                placeholder="Votre mot de passe"
              />
            </div>

            <!-- Password Confirmation -->
            <div>
              <label for="password_confirmation" class="block text-sm font-semibold text-primea-blue mb-2">
                Confirmer le mot de passe <span class="text-red-500">*</span>
              </label>
              <input
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                required
                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-primea-yellow focus:border-primea-blue transition-all duration-200 bg-white text-base"
                placeholder="Confirmer votre mot de passe"
              />
            </div>

            <!-- Required Fields Note -->
            <div class="text-xs text-gray-500">
              <span class="text-red-500">*</span> Champs obligatoires
            </div>

            <!-- Terms and Conditions -->
            <div class="flex items-start pt-2">
              <input
                id="terms"
                v-model="form.terms"
                type="checkbox"
                required
                class="h-4 w-4 mt-0.5 text-primea-blue focus:ring-primea-yellow border-gray-300 rounded flex-shrink-0"
              />
              <label for="terms" class="ml-2 block text-sm text-gray-700">
                J'accepte les
                <router-link to="/terms" class="text-primea-blue hover:text-primea-yellow font-semibold transition-colors">conditions d'utilisation</router-link>
                et la
                <router-link to="/privacy" class="text-primea-blue hover:text-primea-yellow font-semibold transition-colors">politique de confidentialité</router-link>
                <span class="text-red-500">*</span>
              </label>
            </div>

            <!-- Error Message -->
            <div v-if="error && !isConflictError" class="bg-red-50 border-2 border-red-200 rounded-xl p-4">
              <p class="text-red-600 text-sm font-medium">{{ error }}</p>
            </div>

            <!-- Conflict Error (email/phone already used) -->
            <div v-if="isConflictError" class="bg-yellow-50 border-2 border-yellow-200 rounded-xl p-4">
              <p class="text-yellow-800 text-sm font-medium mb-3">{{ error }}</p>
              <div class="flex flex-col sm:flex-row gap-2">
                <router-link
                  to="/login"
                  class="inline-flex items-center justify-center px-4 py-2 bg-primea-blue text-white rounded-lg text-sm font-semibold hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200"
                >
                  Se connecter
                </router-link>
                <router-link
                  to="/forgot-password"
                  class="inline-flex items-center justify-center px-4 py-2 border-2 border-primea-blue text-primea-blue rounded-lg text-sm font-semibold hover:bg-primea-blue hover:text-white transition-all duration-200"
                >
                  Mot de passe oublié ?
                </router-link>
              </div>
            </div>

            <!-- Submit Button -->
            <button
              type="submit"
              :disabled="loading"
              class="w-full bg-primea-blue text-white py-4 px-6 rounded-xl text-base md:text-lg font-bold transition-all duration-200 shadow-lg hover:bg-primea-yellow hover:text-primea-blue disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-primea-blue disabled:hover:text-white transform hover:scale-105 disabled:transform-none"
            >
              <span v-if="loading" class="flex items-center justify-center">
                <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Inscription en cours...
              </span>
              <span v-else>Créer mon compte</span>
            </button>

            <!-- Login Link -->
            <div class="text-center pt-4">
              <p class="text-sm text-gray-600">
                Vous avez déjà un compte ?
                <router-link
                  to="/login"
                  class="text-primea-blue hover:text-primea-yellow font-bold transition-colors duration-200 ml-1"
                >
                  Se connecter
                </router-link>
              </p>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import PhoneInput from '../../components/PhoneInput.vue'
import { authService } from '../../services/api.js'
import { useAuthStore } from '../../stores/auth.js'

export default {
  name: 'Register',
  components: {
    PhoneInput
  },
  setup() {
    const router = useRouter()
    const authStore = useAuthStore()
    const loading = ref(false)
    const error = ref('')
    const isConflictError = ref(false)

    const form = ref({
      name: '',
      email: '',
      phone: '',
      password: '',
      password_confirmation: '',
      terms: false
    })

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

    const register = async () => {
      // Client-side validation
      error.value = ''
      isConflictError.value = false

      if (!form.value.name || form.value.name.trim().length < 2) {
        error.value = 'Le nom complet doit contenir au moins 2 caractères'
        return
      }

      if (!form.value.phone) {
        error.value = 'Le numéro de téléphone est obligatoire'
        return
      }

      if (!form.value.password || form.value.password.length < 8) {
        error.value = 'Le mot de passe doit contenir au moins 8 caractères'
        return
      }

      if (form.value.password !== form.value.password_confirmation) {
        error.value = 'Les mots de passe ne correspondent pas'
        return
      }

      if (!form.value.terms) {
        error.value = 'Vous devez accepter les conditions d\'utilisation'
        return
      }

      loading.value = true

      try {
        // Prepare registration data
        const registrationData = {
          name: form.value.name,
          email: form.value.email || undefined, // Only send if filled
          phone: form.value.phone,
          password: form.value.password,
          password_confirmation: form.value.password_confirmation
        }

        const response = await authService.register(registrationData)

        // If registration requires email verification
        if (response.data.email_verification_required) {
          router.push({
            name: 'email-verification'
          })
        } else {
          // Redirect to login page with success message
          router.push({
            name: 'login',
            query: { message: 'Inscription réussie ! Vous pouvez maintenant vous connecter.' }
          })
        }
      } catch (err) {
        console.error('Erreur d\'inscription:', err)

        // Detailed error handling
        if (err.response) {
          const status = err.response.status
          const data = err.response.data

          if (status === 422) {
            // Validation errors
            if (data.errors) {
              const errors = data.errors

              // Check if it's a uniqueness conflict (email or phone already taken)
              const isUnique = (errors.email && errors.email[0]?.includes('déjà')) ||
                               (errors.phone && errors.phone[0]?.includes('déjà'))
              if (isUnique) {
                isConflictError.value = true
                error.value = errors.email?.[0] || errors.phone?.[0] || 'Cet email ou ce numéro de téléphone est déjà associé à un compte.'
              } else {
                // Display all validation errors clearly
                const errorMessages = []
                if (errors.name) errorMessages.push(`Nom: ${errors.name[0]}`)
                if (errors.email) errorMessages.push(`Email: ${errors.email[0]}`)
                if (errors.phone) errorMessages.push(`Téléphone: ${errors.phone[0]}`)
                if (errors.password) errorMessages.push(`Mot de passe: ${errors.password[0]}`)

                if (errorMessages.length > 0) {
                  error.value = errorMessages.join(' • ')
                } else {
                  const firstError = Object.values(errors)[0]
                  error.value = Array.isArray(firstError) ? firstError[0] : firstError
                }
              }
            } else if (data.message) {
              error.value = data.message
            } else {
              error.value = 'Données invalides. Veuillez vérifier vos informations.'
            }
          } else if (status === 409) {
            // Conflict (email or phone already used)
            isConflictError.value = true
            error.value = data.message || 'Cet email ou ce numéro de téléphone est déjà associé à un compte.'
          } else if (status === 429) {
            // Too many attempts
            error.value = 'Trop de tentatives. Veuillez réessayer dans quelques minutes.'
          } else if (status >= 500) {
            // Server error
            error.value = 'Erreur du serveur. Veuillez réessayer plus tard.'
          } else {
            error.value = data.message || 'Une erreur est survenue lors de l\'inscription'
          }
        } else if (err.request) {
          // Request sent but no response
          error.value = 'Impossible de se connecter au serveur. Vérifiez votre connexion internet.'
        } else {
          // Error during request configuration
          error.value = err.message || 'Une erreur est survenue. Veuillez réessayer.'
        }
      } finally {
        loading.value = false
      }
    }

    return {
      form,
      loading,
      error,
      isConflictError,
      register
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
