<template>
  <nav class="mobile-bottom-nav md:hidden" :class="{ 'nav-hidden': isHidden }">
    <div class="nav-container">
      <router-link
        v-for="item in navItems"
        :key="item.to"
        :to="item.to"
        class="nav-item"
        :class="{ 'nav-item--active': isActive(item) }"
        active-class=""
      >
        <!-- Home icon -->
        <svg v-if="item.icon === 'home'" class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>

        <!-- Calendar/Events icon -->
        <svg v-else-if="item.icon === 'events'" class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>

        <!-- Ticket icon -->
        <svg v-else-if="item.icon === 'tickets'" class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
        </svg>

        <!-- User/Profile icon -->
        <svg v-else-if="item.icon === 'profile'" class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>

        <span class="nav-label">{{ item.label }}</span>

        <!-- Active indicator dot -->
        <span v-if="isActive(item)" class="nav-dot"></span>
      </router-link>
    </div>
  </nav>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const route = useRoute()
const authStore = useAuthStore()
const isAuthenticated = computed(() => authStore.isAuthenticated)

const navItems = computed(() => {
  const items = [
    { to: '/', icon: 'home', label: 'Accueil', matchPaths: ['/'] },
    { to: '/events', icon: 'events', label: 'Evenements', matchPaths: ['/events'] },
  ]

  if (isAuthenticated.value) {
    items.push(
      { to: '/account/tickets', icon: 'tickets', label: 'Mes Tickets', matchPaths: ['/account/tickets', '/account/orders', '/account'] },
      { to: '/account/profile', icon: 'profile', label: 'Profil', matchPaths: ['/account/profile'] }
    )
  } else {
    items.push(
      { to: '/retrieve-ticket', icon: 'tickets', label: 'Retrouver', matchPaths: ['/retrieve-ticket'] },
      { to: '/login', icon: 'profile', label: 'Connexion', matchPaths: ['/login', '/register'] }
    )
  }

  return items
})

const isActive = (item) => {
  if (item.to === '/' && route.path === '/') return true
  if (item.to !== '/') {
    return item.matchPaths.some(p => route.path.startsWith(p))
  }
  return false
}

// Hide on scroll down, show on scroll up
const isHidden = ref(false)
let lastScrollY = 0

const handleScroll = () => {
  const currentScrollY = window.scrollY
  if (currentScrollY > lastScrollY && currentScrollY > 100) {
    isHidden.value = true
  } else {
    isHidden.value = false
  }
  lastScrollY = currentScrollY
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll, { passive: true })
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})
</script>

<style scoped>
.mobile-bottom-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 45;
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-top: 1px solid rgba(39, 45, 99, 0.08);
  padding-bottom: env(safe-area-inset-bottom, 0px);
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.mobile-bottom-nav.nav-hidden {
  transform: translateY(100%);
}

.nav-container {
  display: flex;
  justify-content: space-around;
  align-items: center;
  padding: 6px 8px 4px;
  max-width: 480px;
  margin: 0 auto;
}

.nav-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 2px;
  padding: 8px 12px;
  border-radius: 12px;
  min-width: 64px;
  min-height: 48px;
  color: #9ca3af;
  text-decoration: none;
  transition: all 0.2s ease;
  position: relative;
  -webkit-tap-highlight-color: transparent;
}

.nav-item:active {
  transform: scale(0.92);
}

.nav-item--active {
  color: #272d63;
}

.nav-icon {
  width: 22px;
  height: 22px;
  transition: all 0.2s ease;
}

.nav-item--active .nav-icon {
  stroke-width: 2.5;
}

.nav-label {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.01em;
  line-height: 1;
  white-space: nowrap;
}

.nav-item--active .nav-label {
  font-weight: 700;
}

.nav-dot {
  position: absolute;
  top: 4px;
  left: 50%;
  transform: translateX(-50%);
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background-color: #fab511;
}
</style>
