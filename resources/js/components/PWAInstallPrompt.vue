<template>
  <Transition name="slide-up">
    <div
      v-if="showPrompt"
      class="pwa-install-prompt fixed bottom-0 left-0 right-0 z-50 p-4 md:hidden"
    >
      <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 p-4">
        <div class="flex items-start gap-4">
          <!-- App Icon -->
          <div class="flex-shrink-0">
            <img
              src="/images/ico.png"
              alt="Primea"
              class="w-14 h-14 rounded-xl shadow-md"
            />
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <h3 class="text-base font-bold text-gray-900">Installer Primea</h3>
            <p class="text-sm text-gray-600 mt-0.5">
              Installez l'application pour un accès rapide
            </p>

            <!-- Action Buttons -->
            <div class="flex gap-2 mt-3">
              <button
                @click="installPWA"
                class="flex-1 bg-primea-blue text-white px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200 shadow-sm"
              >
                Installer
              </button>
              <button
                @click="dismissPrompt"
                class="px-4 py-2.5 text-gray-500 hover:text-gray-700 text-sm font-medium"
              >
                Plus tard
              </button>
            </div>
          </div>

          <!-- Close Button -->
          <button
            @click="dismissPrompt"
            class="flex-shrink-0 p-1 text-gray-400 hover:text-gray-600"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- iOS Instructions -->
        <div v-if="isIOS && !isStandalone" class="mt-3 pt-3 border-t border-gray-100">
          <p class="text-xs text-gray-500 text-center">
            Appuyez sur
            <svg class="w-4 h-4 inline-block mx-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
            </svg>
            puis "Sur l'écran d'accueil"
          </p>
        </div>

        <!-- Manual Installation Instructions (Chrome/Edge/etc.) -->
        <div v-if="showManualInstructions && !isIOS" class="mt-3 pt-3 border-t border-gray-100">
          <p class="text-xs text-gray-600 text-center font-medium mb-2">
            Pour installer l'application :
          </p>
          <ul class="text-xs text-gray-500 space-y-1">
            <li class="flex items-center justify-center gap-1">
              <span>1. Cliquez sur</span>
              <svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
              </svg>
              <span>dans la barre d'adresse</span>
            </li>
            <li class="text-center">2. Sélectionnez "Installer l'application"</li>
          </ul>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'

const showPrompt = ref(false)
const deferredPrompt = ref(null)

// Detect iOS
const isIOS = computed(() => {
  return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream
})

// Check if already installed as standalone
const isStandalone = computed(() => {
  return window.matchMedia('(display-mode: standalone)').matches ||
         window.navigator.standalone === true
})

// Check if should show prompt
const shouldShowPrompt = () => {
  // Don't show if already standalone
  if (isStandalone.value) return false

  // Check if user dismissed recently (24 hours)
  const dismissedAt = localStorage.getItem('pwa-prompt-dismissed')
  if (dismissedAt) {
    const dismissedTime = parseInt(dismissedAt)
    const now = Date.now()
    const hoursSinceDismissed = (now - dismissedTime) / (1000 * 60 * 60)
    if (hoursSinceDismissed < 24) return false
  }

  // Check if already installed
  const installed = localStorage.getItem('pwa-installed')
  if (installed) return false

  return true
}

const showManualInstructions = ref(false)

const installPWA = async () => {
  if (deferredPrompt.value) {
    // Show the native install prompt
    try {
      deferredPrompt.value.prompt()
      const { outcome } = await deferredPrompt.value.userChoice

      if (outcome === 'accepted') {
        localStorage.setItem('pwa-installed', 'true')
      }

      deferredPrompt.value = null
      showPrompt.value = false
    } catch (error) {
      console.error('PWA install error:', error)
      showManualInstructions.value = true
    }
  } else if (isIOS.value) {
    // For iOS, we just show instructions (already displayed)
    // User needs to manually add to home screen
    showManualInstructions.value = true
  } else {
    // No deferred prompt available - show manual instructions
    showManualInstructions.value = true
  }
}

const dismissPrompt = () => {
  showPrompt.value = false
  localStorage.setItem('pwa-prompt-dismissed', Date.now().toString())
}

// Detect if browser supports PWA installation
const isMobile = computed(() => {
  return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
})

onMounted(() => {
  // Listen for the beforeinstallprompt event
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault()
    deferredPrompt.value = e

    if (shouldShowPrompt()) {
      // Show prompt after a delay to not be too aggressive
      setTimeout(() => {
        showPrompt.value = true
      }, 3000)
    }
  })

  // For iOS, show custom prompt after delay
  if (isIOS.value && shouldShowPrompt()) {
    setTimeout(() => {
      showPrompt.value = true
    }, 5000)
  }

  // For mobile browsers that don't fire beforeinstallprompt (or if event doesn't fire)
  // Show the prompt with manual instructions after a longer delay
  if (isMobile.value && !isIOS.value && shouldShowPrompt()) {
    setTimeout(() => {
      // Only show if we haven't already shown due to beforeinstallprompt
      if (!showPrompt.value && !deferredPrompt.value) {
        showPrompt.value = true
        showManualInstructions.value = true
      }
    }, 6000)
  }

  // Listen for successful installation
  window.addEventListener('appinstalled', () => {
    localStorage.setItem('pwa-installed', 'true')
    showPrompt.value = false
  })
})
</script>

<style scoped>
.pwa-install-prompt {
  background: linear-gradient(to top, rgba(0, 0, 0, 0.1), transparent);
  padding-bottom: max(1rem, env(safe-area-inset-bottom));
}

.bg-primea-blue {
  background-color: #272d63;
}

.text-primea-blue {
  color: #272d63;
}

.hover\:bg-primea-yellow:hover {
  background-color: #fab511;
}

.hover\:text-primea-blue:hover {
  color: #272d63;
}

/* Slide up animation */
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s ease-out;
}

.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(100%);
  opacity: 0;
}
</style>
