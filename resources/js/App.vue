<template>
  <div class="app-container">
    <!-- Header global: gère automatiquement mobile (avec burger) et desktop (menu normal) -->
    <NewHeader v-if="showHeader" />
    <main :class="{ 'pb-20 md:pb-0': showBottomNav }">
      <router-view />
    </main>
    <!-- Footer global: gère automatiquement mobile et desktop -->
    <NewFooter v-if="showFooter" />
    <!-- Mobile Bottom Navigation -->
    <MobileBottomNav v-if="showBottomNav" />
    <!-- PWA Install Prompt -->
    <PWAInstallPrompt />
  </div>
</template>

<script>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import NewHeader from './components/layout/NewHeader.vue'
import NewFooter from './components/layout/NewFooter.vue'
import MobileBottomNav from './components/MobileBottomNav.vue'
import PWAInstallPrompt from './components/PWAInstallPrompt.vue'

export default {
  name: 'App',
  components: {
    NewHeader,
    NewFooter,
    MobileBottomNav,
    PWAInstallPrompt
  },
  setup() {
    const route = useRoute()

    const isSpecialRoute = computed(() => {
      const hiddenRoutes = ['scanner']
      const isAdminRoute = route.path && route.path.startsWith('/admin')
      const isOrganizerRoute = route.path && route.path.startsWith('/organizer')
      return hiddenRoutes.includes(route.name) || isAdminRoute || isOrganizerRoute
    })

    const showHeader = computed(() => !isSpecialRoute.value)
    const showFooter = computed(() => !isSpecialRoute.value)

    const showBottomNav = computed(() => {
      if (isSpecialRoute.value) return false
      // Masquer sur les pages avec leur propre barre fixe en bas
      const noNavRoutes = ['event-detail', 'checkout', 'payment', 'ticket-success', 'payment-success']
      return !noNavRoutes.includes(route.name)
    })

    return {
      showHeader,
      showFooter,
      showBottomNav
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

#app,
.app-container {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

main {
  flex: 1;
}
</style>