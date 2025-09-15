<template>
  <button 
    @click="toggleFavorite"
    class="flex items-center justify-center w-12 h-12 rounded-full bg-white shadow-lg hover:shadow-xl transition-all duration-200 hover:text-primea-yellow"
    :class="{ 'text-red-500': isFavorite, 'text-gray-400': !isFavorite }"
  >
    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
      <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
    </svg>
  </button>
</template>

<script>
import { ref } from 'vue'

export default {
  name: 'FavoriteButton',
  props: {
    eventId: {
      type: [String, Number],
      required: true
    }
  },
  setup(props) {
    const isFavorite = ref(false)

    const toggleFavorite = () => {
      isFavorite.value = !isFavorite.value
      // TODO: Implémenter la logique de favori avec l'API
    }

    return {
      isFavorite,
      toggleFavorite
    }
  }
}
</script>

<style scoped>
.text-primea-yellow {
  color: #fab511;
}

button:hover .text-primea-yellow {
  color: #fab511 !important;
}
</style>