<template>
  <div style="background: red !important; color: white; padding: 20px; min-height: 80vh;">
    <h1>🔴 TEST DASHBOARD ORGANISATEUR</h1>
    <p>Si vous voyez ce texte en rouge, le composant fonctionne !</p>
    <p>Route actuelle: {{ $route.path }}</p>
    <p>Layout: {{ $parent?.constructor?.name || 'Inconnu' }}</p>
  </div>
</template>

<script setup>
// Test simple avec script setup
console.log('OrganizerDashboard component loaded');
</script>