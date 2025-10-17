<template>
  <div id="app">
    <!-- Header global: visible sur desktop uniquement, caché sur mobile -->
    <div v-if="showHeader" class="hidden md:block">
      <Header />
    </div>
    <main>
      <router-view />
    </main>
    <!-- Footer global: visible sur desktop uniquement, caché sur mobile -->
    <div v-if="showFooter" class="hidden md:block">
      <Footer />
    </div>
  </div>
</template>

<script>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import Header from './components/layout/Header.vue'
import Footer from './components/layout/Footer.vue'

export default {
  name: 'App',
  components: {
    Header,
    Footer
  },
  setup() {
    const route = useRoute()

    // Masquer header/footer sur certaines pages
    const showHeader = computed(() => {
      const hiddenRoutes = ['scanner', 'checkout']
      // Masquer aussi sur les routes admin et organisateur qui ont leur propre layout
      const isAdminRoute = route.path && route.path.startsWith('/admin')
      const isOrganizerRoute = route.path && route.path.startsWith('/organizer')
      // Le header global s'affiche sur desktop (md:block) pour les pages guest/client
      // Sur mobile, chaque page a son propre header intégré
      return !hiddenRoutes.includes(route.name) && !isAdminRoute && !isOrganizerRoute
    })

    const showFooter = computed(() => {
      const hiddenRoutes = ['scanner', 'checkout']
      // Masquer aussi sur les routes admin et organisateur qui ont leur propre layout
      const isAdminRoute = route.path && route.path.startsWith('/admin')
      const isOrganizerRoute = route.path && route.path.startsWith('/organizer')
      // Le footer global s'affiche sur desktop (md:block) pour les pages guest/client
      // Sur mobile, chaque page a son propre footer si nécessaire
      return !hiddenRoutes.includes(route.name) && !isAdminRoute && !isOrganizerRoute
    })

    return {
      showHeader,
      showFooter
    }
  }
}
</script>

<style>
html, body {
  font-family: 'Inter', sans-serif;
  height: auto;
  overflow-x: hidden;
  overflow-y: auto;
  margin: 0;
  padding: 0;
}

#app {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

main {
  flex: 1;
}
</style>