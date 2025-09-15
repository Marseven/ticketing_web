<template>
  <div class="register-page font-primea relative overflow-hidden min-h-screen">
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

        <!-- Carte d'inscription -->
        <div class="bg-white/95 backdrop-blur-sm rounded-primea-xl shadow-primea-lg p-8 animate-slide-up">
          
          <!-- Titre -->
          <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-primea-blue mb-2">
              Inscription
            </h1>
            <p class="text-gray-600 font-primea">
              Rejoignez la communauté Primea
            </p>
          </div>

        <!-- Formulaire d'inscription -->
        <form class="space-y-4" @submit.prevent="register">
          <!-- Nom complet -->
          <div>
            <label for="name" class="block text-sm font-semibold text-primea-blue mb-2 font-primea">
              Nom complet <span class="text-red-500">*</span>
            </label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              required
              class="w-full px-4 py-3 border-2 border-gray-200 rounded-primea-lg focus:ring-2 focus:ring-primea-yellow focus:border-primea-blue transition-all duration-200 font-primea bg-white/90"
              placeholder="Votre nom complet"
            />
          </div>

          <!-- Email -->
          <div>
            <label for="email" class="block text-sm font-semibold text-primea-blue mb-2 font-primea">
              Adresse email <span class="text-gray-400 text-xs font-normal">(optionnel)</span>
            </label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              class="w-full px-4 py-3 border-2 border-gray-200 rounded-primea-lg focus:ring-2 focus:ring-primea-yellow focus:border-primea-blue transition-all duration-200 font-primea bg-white/90"
              placeholder="votre@email.com"
            />
          </div>

          <!-- Téléphone -->
          <div>
            <label for="phone" class="block text-sm font-semibold text-primea-blue mb-2 font-primea">
              Numéro de téléphone <span class="text-red-500">*</span>
            </label>
            <PhoneInput
              id="phone"
              v-model="form.phone"
              placeholder="XX XX XX XX"
              :required="true"
              default-country="GA"
              input-class="font-primea bg-white/90"
            />
          </div>

          <!-- Mot de passe -->
          <div>
            <label for="password" class="block text-sm font-semibold text-primea-blue mb-2 font-primea">
              Mot de passe <span class="text-red-500">*</span>
            </label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              required
              class="w-full px-4 py-3 border-2 border-gray-200 rounded-primea-lg focus:ring-2 focus:ring-primea-yellow focus:border-primea-blue transition-all duration-200 font-primea bg-white/90"
              placeholder="Votre mot de passe"
            />
          </div>

          <!-- Confirmation mot de passe -->
          <div>
            <label for="password_confirmation" class="block text-sm font-semibold text-primea-blue mb-2 font-primea">
              Confirmer le mot de passe <span class="text-red-500">*</span>
            </label>
            <input
              id="password_confirmation"
              v-model="form.password_confirmation"
              type="password"
              required
              class="w-full px-4 py-3 border-2 border-gray-200 rounded-primea-lg focus:ring-2 focus:ring-primea-yellow focus:border-primea-blue transition-all duration-200 font-primea bg-white/90"
              placeholder="Confirmer votre mot de passe"
            />
          </div>

          <!-- Note sur les champs obligatoires -->
          <div class="text-xs text-gray-500 font-primea mt-4">
            <span class="text-red-500">*</span> Champs obligatoires
          </div>

          <!-- Conditions d'utilisation -->
          <div class="flex items-center mt-4">
            <input
              id="terms"
              v-model="form.terms"
              type="checkbox"
              required
              class="h-4 w-4 text-primea-blue focus:ring-primea-yellow border-gray-300 rounded"
            />
            <label for="terms" class="ml-2 block text-sm text-gray-700 font-primea">
              J'accepte les 
              <a href="#" class="text-primea-blue hover:text-primea-yellow font-semibold transition-colors">conditions d'utilisation</a>
              et la 
              <a href="#" class="text-primea-blue hover:text-primea-yellow font-semibold transition-colors">politique de confidentialité</a>
              <span class="text-red-500">*</span>
            </label>
          </div>

          <!-- Message d'erreur -->
          <div v-if="error" class="bg-red-50 border border-red-200 rounded-primea-lg p-4 mt-4">
            <p class="text-red-800 text-sm font-primea">{{ error }}</p>
          </div>

          <!-- Bouton d'inscription -->
          <button
            type="submit"
            :disabled="loading"
            class="w-full mt-6 text-white py-4 px-6 rounded-primea-lg text-lg font-bold transition-all duration-200 shadow-primea-lg transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none font-primea"
            :style="{ backgroundColor: '#272d63', color: '#ffffff' }"
            @mouseover="$event.currentTarget.style.backgroundColor='#fab511'; $event.currentTarget.style.color='#272d63'"
            @mouseleave="$event.currentTarget.style.backgroundColor='#272d63'; $event.currentTarget.style.color='#ffffff'"
          >
            <span v-if="loading" class="flex items-center justify-center pointer-events-none">
              <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Inscription en cours...
            </span>
            <span v-else class="pointer-events-none">Créer mon compte</span>
          </button>

          <!-- Lien de connexion -->
          <div class="text-center mt-6">
            <p class="text-sm text-gray-600 font-primea">
              Vous avez déjà un compte ?
              <router-link to="/login" class="font-bold text-primea-blue hover:text-primea-yellow transition-colors duration-200">
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
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import PhoneInput from '../../components/PhoneInput.vue'

export default {
  name: 'Register',
  components: {
    PhoneInput
  },
  setup() {
    const router = useRouter()
    const loading = ref(false)
    const error = ref('')
    
    const form = ref({
      name: '',
      email: '',
      phone: '',
      password: '',
      password_confirmation: '',
      terms: false
    })

    const register = async () => {
      if (form.value.password !== form.value.password_confirmation) {
        error.value = 'Les mots de passe ne correspondent pas'
        return
      }

      if (!form.value.terms) {
        error.value = 'Vous devez accepter les conditions d\'utilisation'
        return
      }

      loading.value = true
      error.value = ''

      try {
        // Simulation d'inscription (à remplacer par l'API réelle)
        await new Promise(resolve => setTimeout(resolve, 2000))
        
        // Redirection vers la page de connexion
        router.push('/login')
      } catch (err) {
        error.value = 'Erreur lors de l\'inscription. Veuillez réessayer.'
      } finally {
        loading.value = false
      }
    }

    return {
      form,
      loading,
      error,
      register
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