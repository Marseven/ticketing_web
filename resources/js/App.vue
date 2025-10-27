<template>
  <div id="app">
    <!-- Header global: gère automatiquement mobile (avec burger) et desktop (menu normal) -->
    <NewHeader v-if="showHeader" />
    <main>
      <router-view />
    </main>
    <!-- Footer global: gère automatiquement mobile et desktop -->
    <NewFooter v-if="showFooter" />
  </div>
</template>

<script>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import NewHeader from './components/layout/NewHeader.vue'
import NewFooter from './components/layout/NewFooter.vue'

export default {
  name: 'App',
  components: {
    NewHeader,
    NewFooter
  },
  setup() {
    const route = useRoute()

    // Masquer header/footer sur certaines pages
    const showHeader = computed(() => {
      const hiddenRoutes = ['scanner']
      // Masquer aussi sur les routes admin et organisateur qui ont leur propre layout
      const isAdminRoute = route.path && route.path.startsWith('/admin')
      const isOrganizerRoute = route.path && route.path.startsWith('/organizer')
      // Le header global s'affiche sur desktop (md:block) pour les pages guest/client
      // Sur mobile, chaque page a son propre header intégré
      return !hiddenRoutes.includes(route.name) && !isAdminRoute && !isOrganizerRoute
    })

    const showFooter = computed(() => {
      const hiddenRoutes = ['scanner']
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
  font-family: 'Inter', 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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