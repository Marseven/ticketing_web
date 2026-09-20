<template>
  <teleport to="body">
    <!-- Barre de progression indéterminée, en haut de l'écran -->
    <transition name="gl-fade">
      <div
        v-if="loading.active"
        class="fixed top-0 left-0 right-0 z-[9999] h-1 bg-primea-blue/20 overflow-hidden pointer-events-none"
        role="progressbar"
        aria-busy="true"
        aria-label="Chargement en cours"
      >
        <div class="gl-bar h-full w-1/3 bg-primea-yellow rounded-full"></div>
      </div>
    </transition>

    <!-- Pastille « Chargement… » : n'apparaît qu'après 300 ms pour éviter le
         clignotement sur les requêtes rapides ; ne bloque pas les interactions. -->
    <transition name="gl-fade">
      <div
        v-if="showPill"
        class="fixed left-1/2 -translate-x-1/2 z-[9999] pointer-events-none"
        :style="{ top: 'max(env(safe-area-inset-top), 14px)' }"
      >
        <div class="flex items-center gap-2 bg-primea-blue text-white text-sm font-semibold px-4 py-2 rounded-full shadow-lg">
          <span class="inline-block w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
          Chargement…
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
import { ref, watch, onBeforeUnmount } from 'vue'
import { useLoadingStore } from '../stores/loading'

const loading = useLoadingStore()
const showPill = ref(false)
let timer = null

watch(
  () => loading.active,
  (active) => {
    clearTimeout(timer)
    if (active) {
      timer = setTimeout(() => { showPill.value = true }, 300)
    } else {
      showPill.value = false
    }
  },
  { immediate: true }
)

onBeforeUnmount(() => clearTimeout(timer))
</script>

<style>
/* Non scoped : le contenu est téléporté dans <body>. */
@keyframes gl-slide {
  0%   { transform: translateX(-100%); }
  100% { transform: translateX(300%); }
}
.gl-bar { animation: gl-slide 1.1s ease-in-out infinite; }
.gl-fade-enter-active, .gl-fade-leave-active { transition: opacity .2s ease; }
.gl-fade-enter-from, .gl-fade-leave-to { opacity: 0; }
</style>
