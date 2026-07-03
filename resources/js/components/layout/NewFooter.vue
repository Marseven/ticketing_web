<template>
  <footer class="bg-gray-100 py-8">
    <div class="max-w-7xl mx-auto px-4">
      <!-- Mobile Footer -->
      <div class="md:hidden text-center">
        <img src="/images/logo.png?v=2" alt="MyTicketO" class="h-8 mx-auto mb-4" />

        <div class="flex flex-wrap justify-center gap-x-4 gap-y-1 mb-4 text-sm">
          <router-link to="/how-it-works" class="text-gray-600 hover:text-primea-yellow transition-colors">Comment ça marche</router-link>
          <router-link to="/help" class="text-gray-600 hover:text-primea-yellow transition-colors">Aide</router-link>
          <router-link to="/contact" class="text-gray-600 hover:text-primea-yellow transition-colors">Contact</router-link>
          <router-link to="/terms" class="text-gray-600 hover:text-primea-yellow transition-colors">CGU</router-link>
          <router-link to="/privacy" class="text-gray-600 hover:text-primea-yellow transition-colors">Confidentialité</router-link>
        </div>

        <p class="text-sm text-gray-500">&copy; {{ new Date().getFullYear() }} MyTicketO. Tous droits réservés.</p>
      </div>

      <!-- Desktop Footer -->
      <div class="hidden md:block">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
          <!-- Company Info -->
          <div>
            <div class="flex items-center mb-4">
              <img src="/images/logo.png?v=2" alt="MyTicketO" class="h-12 w-auto" />
            </div>
            <p class="text-gray-600 mb-4">
              La plateforme de référence pour vos événements.
            </p>
          </div>

          <!-- Quick Links -->
          <div>
            <h3 class="font-bold text-primea-blue text-lg mb-4">Liens rapides</h3>
            <ul class="space-y-2">
              <li>
                <router-link :to="{ name: 'events' }" class="text-gray-600 hover:text-primea-yellow transition-colors">
                  Tous les événements
                </router-link>
              </li>
              <li>
                <router-link to="/how-it-works" class="text-gray-600 hover:text-primea-yellow transition-colors">
                  Comment ça marche
                </router-link>
              </li>
              <li>
                <router-link :to="{ name: 'organizer-choice' }" class="text-gray-600 hover:text-primea-yellow transition-colors">
                  Devenir organisateur
                </router-link>
              </li>
              <li>
                <router-link :to="{ name: 'ticket-retrieve' }" class="text-gray-600 hover:text-primea-yellow transition-colors">
                  Récupérer mon ticket
                </router-link>
              </li>
            </ul>
          </div>

          <!-- Support -->
          <div>
            <h3 class="font-bold text-primea-blue text-lg mb-4">Support</h3>
            <ul class="space-y-2">
              <li>
                <router-link to="/help" class="text-gray-600 hover:text-primea-yellow transition-colors">
                  Centre d'aide
                </router-link>
              </li>
              <li>
                <router-link to="/terms" class="text-gray-600 hover:text-primea-yellow transition-colors">
                  Conditions d'utilisation
                </router-link>
              </li>
              <li>
                <router-link to="/privacy" class="text-gray-600 hover:text-primea-yellow transition-colors">
                  Politique de confidentialité
                </router-link>
              </li>
              <li>
                <router-link to="/contact" class="text-gray-600 hover:text-primea-yellow transition-colors">
                  Contact
                </router-link>
              </li>
            </ul>
          </div>
        </div>

        <!-- Bottom Bar -->
        <div class="pt-6 border-t border-gray-300">
          <div class="flex flex-col md:flex-row justify-between items-center">
            <p class="text-gray-500 text-sm">
              &copy; {{ new Date().getFullYear() }} MyTicketO. Tous droits réservés.
            </p>
            <p class="text-gray-500 text-sm mt-4 md:mt-0">
              Plateforme d'événements de référence
            </p>
          </div>
        </div>
      </div>
    </div>
  </footer>
</template>

<script>
export default {
  name: 'NewFooter'
}
</script>
