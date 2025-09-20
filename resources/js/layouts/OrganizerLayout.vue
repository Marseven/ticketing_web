<template>
  <div class="min-h-screen organizer-layout" style="background: blue !important;">
    <!-- Header spécifique organisateur -->
    <OrganizerHeader />
    
    <!-- Contenu principal -->
    <main class="flex-1 organizer-main" style="background: green !important;">
      <slot />
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

/* Style simple et efficace pour l'espace organisateur */
.organizer-layout {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.organizer-main {
  min-height: calc(100vh - 140px);
}
</style>