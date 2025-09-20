<template>
  <div class="min-h-screen organizer-layout bg-gray-50">
    <!-- Header spécifique organisateur -->
    <OrganizerHeader />
    
    <!-- Contenu principal avec fond forcé -->
    <main class="flex-1 min-h-screen organizer-main">
      <div class="organizer-content">
        <slot />
      </div>
    </main>
    
    <!-- Footer spécifique organisateur -->
    <OrganizerFooter />
  </div>
</template>

<script setup>
import OrganizerHeader from '../components/layout/OrganizerHeader.vue';
import OrganizerFooter from '../components/layout/OrganizerFooter.vue';
</script>

<style scoped>
.organizer-layout {
  display: flex;
  flex-direction: column;
}

/* Style de base pour l'espace organisateur */
.organizer-layout {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%) !important;
}

.organizer-main {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%) !important;
  min-height: calc(100vh - 140px);
}

.organizer-content {
  background: transparent !important;
  min-height: inherit;
}

/* Forcer le fond sur tous les éléments enfants et leurs enfants */
.organizer-content > *,
.organizer-content > * > *,
.organizer-content .bg-white {
  background: transparent !important;
}

/* Exception pour les cartes et modals qui doivent rester blanches */
.organizer-content .bg-white.rounded-primea,
.organizer-content .bg-white.shadow-primea,
.organizer-content .modal,
.organizer-content .dropdown-menu {
  background: white !important;
}
</style>