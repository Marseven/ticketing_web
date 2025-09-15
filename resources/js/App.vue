<template>
  <div id="app">
    <Header v-if="showHeader" />
    <main class="min-h-screen">
      <router-view />
    </main>
    <Footer v-if="showFooter" />
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
      // Masquer aussi sur les routes admin
      const isAdminRoute = route.path && route.path.startsWith('/admin')
      return !hiddenRoutes.includes(route.name) && !isAdminRoute
    })
    
    const showFooter = computed(() => {
      const hiddenRoutes = ['scanner', 'checkout']
      // Masquer aussi sur les routes admin
      const isAdminRoute = route.path && route.path.startsWith('/admin')
      return !hiddenRoutes.includes(route.name) && !isAdminRoute
    })
    
    return {
      showHeader,
      showFooter
    }
  }
}
</script>

<style>
body {
  font-family: 'Inter', sans-serif;
}
</style>