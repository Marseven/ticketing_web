<template>
  <div class="min-h-screen bg-gradient-to-br from-primea-blue via-primea-blue to-primea-blue flex items-center justify-center px-4 py-12">
    <div class="max-w-md w-full">
      <!-- Logo et titre -->
      <div class="text-center mb-8 animate-fade-in">
        <img src="/images/logo_white.png?v=3" alt="Logo" class="h-20 mx-auto mb-6" />
        <h1 class="text-3xl md:text-4xl font-extrabold text-white mb-3">
          Espace Créateur d'événements
        </h1>
        <p class="text-blue-200 text-base md:text-lg">
          Gérez et organisez vos événements en toute simplicité
        </p>
      </div>

      <!-- Card avec les boutons -->
      <div class="bg-white rounded-3xl shadow-2xl p-8 md:p-10 space-y-5 animate-slide-up">
        <!-- Bouton Se connecter -->
        <router-link
          to="/login-organizer"
          class="block w-full bg-primea-blue text-white py-4 px-6 rounded-xl text-center text-lg font-bold transition-all duration-200 shadow-lg hover:bg-primea-yellow hover:text-primea-blue transform hover:scale-105"
        >
          <div class="flex items-center justify-center gap-3">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
            </svg>
            <span>Se connecter</span>
          </div>
        </router-link>

        <!-- L'inscription autonome est fermée : les comptes organisateurs
             sont créés par l'administration. Pour la rouvrir, rétablir ici la
             carte « Créer un compte » vers /register-organizer, ainsi que les
             liens publics retirés du pied de page, de l'en-tête mobile, de
             l'accueil et de « Comment ça marche ». -->

        <!-- Retour à l'accueil -->
        <div class="pt-4 text-center border-t border-gray-200">
          <router-link
            to="/"
            class="text-sm text-primea-blue hover:text-primea-yellow font-semibold transition-colors inline-flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Retour à l'accueil
          </router-link>
        </div>
      </div>

      <!-- Info supplémentaire -->
      <div class="mt-6 text-center text-white/80 text-sm">
        <p>
          Vous êtes un utilisateur régulier ?
          <router-link to="/login" class="underline hover:text-primea-yellow transition-colors font-semibold ml-1">
            Connectez-vous ici
          </router-link>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
// Pas de logique nécessaire, juste du routage
</script>

<style scoped>
/* Animations */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-20px);
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

.animate-fade-in {
  animation: fadeIn 0.6s ease-out;
}

.animate-slide-up {
  animation: slideUp 0.6s ease-out 0.2s both;
}
</style>
