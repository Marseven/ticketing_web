<template>
  <div class="min-h-screen organizer-layout">
    <!-- Header spécifique organisateur -->
    <OrganizerHeader />
    
    <!-- Contenu principal avec fond -->
    <main class="flex-1 bg-gray-50">
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

/* Style de base pour l'espace organisateur */
main {
  min-height: calc(100vh - 140px); /* Ajuster selon la taille du header/footer */
}

/* Background subtil pour différencier l'espace organisateur */
.organizer-layout {
  background-color: #f8fafc;
}

/* S'assurer que le main prend tout l'espace */
main {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}
</style>