<template>
  <form @submit.prevent="submitForm" class="flex flex-col sm:flex-row gap-4 justify-center max-w-md mx-auto">
    <input 
      type="email" 
      v-model="email"
      placeholder="Votre adresse email"
      class="search-bar-modern flex-1"
      required
      :disabled="loading"
    />
    <button 
      type="submit" 
      :disabled="loading || !email"
      class="btn-gradient-secondary px-6 py-3 rounded-xl whitespace-nowrap transition-all duration-300"
    >
      <svg 
        v-if="loading"
        class="w-5 h-5 mr-2 animate-spin" 
        fill="none" 
        viewBox="0 0 24 24"
      >
        <circle 
          cx="12" 
          cy="12" 
          r="10" 
          stroke="currentColor" 
          stroke-width="4" 
          class="opacity-25"
        />
        <path 
          fill="currentColor" 
          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" 
          class="opacity-75"
        />
      </svg>
      <span v-if="!loading">S'inscrire</span>
      <span v-else>Inscription...</span>
    </button>
  </form>

  <!-- Message de succès ou d'erreur -->
  <div v-if="message" :class="[
    'mt-4 p-4 rounded-lg text-center transition-all duration-300',
    messageType === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'
  ]">
    {{ message }}
  </div>
</template>

<script>
import { ref } from 'vue'

export default {
  name: 'NewsletterForm',
  setup() {
    const email = ref('')
    const loading = ref(false)
    const message = ref('')
    const messageType = ref('')

    const submitForm = async () => {
      if (!email.value) return

      loading.value = true
      message.value = ''

      try {
        // Simuler l'inscription à la newsletter
        // Ici, vous intégrerez avec votre API
        await new Promise(resolve => setTimeout(resolve, 1500))
        
        // Simulation d'un succès (remplacer par un vrai appel API)
        const success = Math.random() > 0.1 // 90% de chances de succès
        
        if (success) {
          message.value = 'Merci ! Vous êtes maintenant inscrit à notre newsletter.'
          messageType.value = 'success'
          email.value = ''
        } else {
          throw new Error('Une erreur est survenue')
        }
      } catch (error) {
        message.value = 'Une erreur est survenue. Veuillez réessayer plus tard.'
        messageType.value = 'error'
      } finally {
        loading.value = false
        
        // Effacer le message après 5 secondes
        setTimeout(() => {
          message.value = ''
        }, 5000)
      }
    }

    return {
      email,
      loading,
      message,
      messageType,
      submitForm
    }
  }
}
</script>

<style scoped>
.search-bar-modern {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border: 2px solid rgba(39, 45, 99, 0.1);
  border-radius: 12px;
  padding: 12px 16px;
  transition: all 0.3s ease;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.search-bar-modern:focus {
  outline: none;
  border-color: var(--primea-blue);
  box-shadow: 0 0 0 4px rgba(39, 45, 99, 0.1);
  transform: translateY(-2px);
}

.search-bar-modern:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-gradient-secondary {
  background: linear-gradient(135deg, var(--primea-yellow) 0%, var(--primea-yellow-dark) 100%);
  color: var(--primea-blue);
  border: none;
  font-family: var(--font-primary);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  position: relative;
  overflow: hidden;
}

.btn-gradient-secondary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(250, 181, 17, 0.3);
}

.btn-gradient-secondary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.flex {
  display: flex;
}

.flex-col {
  flex-direction: column;
}

.flex-1 {
  flex: 1;
}

.items-center {
  align-items: center;
}

.justify-center {
  justify-content: center;
}

.gap-4 {
  gap: 1rem;
}

.max-w-md {
  max-width: 28rem;
}

.mx-auto {
  margin-left: auto;
  margin-right: auto;
}

.px-6 {
  padding-left: 1.5rem;
  padding-right: 1.5rem;
}

.py-3 {
  padding-top: 0.75rem;
  padding-bottom: 0.75rem;
}

.rounded-xl {
  border-radius: 0.75rem;
}

.rounded-lg {
  border-radius: 0.5rem;
}

.whitespace-nowrap {
  white-space: nowrap;
}

.transition-all {
  transition: all 0.3s ease;
}

.duration-300 {
  transition-duration: 300ms;
}

.mt-4 {
  margin-top: 1rem;
}

.p-4 {
  padding: 1rem;
}

.text-center {
  text-align: center;
}

.w-5 {
  width: 1.25rem;
}

.h-5 {
  height: 1.25rem;
}

.mr-2 {
  margin-right: 0.5rem;
}

.opacity-25 {
  opacity: 0.25;
}

.opacity-75 {
  opacity: 0.75;
}

.bg-green-100 {
  background-color: #dcfce7;
}

.text-green-800 {
  color: #166534;
}

.border-green-200 {
  border-color: #bbf7d0;
}

.bg-red-100 {
  background-color: #fecaca;
}

.text-red-800 {
  color: #991b1b;
}

.border-red-200 {
  border-color: #fecaca;
}

.border {
  border-width: 1px;
}

@media (min-width: 640px) {
  .sm\:flex-row {
    flex-direction: row;
  }
}

:root {
  --primea-blue: #272d63;
  --primea-yellow: #fab511;
  --primea-yellow-dark: #e09f0e;
  --font-primary: 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
</style>