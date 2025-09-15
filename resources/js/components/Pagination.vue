<template>
  <div v-if="totalPages > 1" class="flex justify-center items-center gap-2 mt-8">
    <!-- Bouton Précédent -->
    <button
      @click="goToPage(currentPage - 1)"
      :disabled="currentPage === 1"
      :class="[
        'flex items-center gap-2 px-4 py-2 rounded-lg transition-all duration-200',
        currentPage === 1 
          ? 'bg-gray-200 text-gray-400 cursor-not-allowed' 
          : 'bg-white text-gray-700 hover:bg-gray-50 shadow-sm border'
      ]"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
      </svg>
      <span class="hidden sm:inline">Précédent</span>
    </button>

    <!-- Pages -->
    <div class="flex items-center gap-1">
      <!-- Première page -->
      <button
        v-if="!pages.includes(1)"
        @click="goToPage(1)"
        class="px-3 py-2 rounded-lg bg-white text-gray-700 hover:bg-gray-50 shadow-sm border transition-all duration-200"
      >
        1
      </button>
      
      <!-- Points de suspension gauche -->
      <span v-if="!pages.includes(1) && !pages.includes(2)" class="px-2 text-gray-500">...</span>

      <!-- Pages visibles -->
      <button
        v-for="page in pages"
        :key="page"
        @click="goToPage(page)"
        :class="[
          'px-3 py-2 rounded-lg transition-all duration-200',
          page === currentPage
            ? 'bg-blue-600 text-white shadow-md'
            : 'bg-white text-gray-700 hover:bg-gray-50 shadow-sm border'
        ]"
      >
        {{ page }}
      </button>

      <!-- Points de suspension droite -->
      <span v-if="!pages.includes(totalPages) && !pages.includes(totalPages - 1)" class="px-2 text-gray-500">...</span>

      <!-- Dernière page -->
      <button
        v-if="!pages.includes(totalPages)"
        @click="goToPage(totalPages)"
        class="px-3 py-2 rounded-lg bg-white text-gray-700 hover:bg-gray-50 shadow-sm border transition-all duration-200"
      >
        {{ totalPages }}
      </button>
    </div>

    <!-- Bouton Suivant -->
    <button
      @click="goToPage(currentPage + 1)"
      :disabled="currentPage === totalPages"
      :class="[
        'flex items-center gap-2 px-4 py-2 rounded-lg transition-all duration-200',
        currentPage === totalPages 
          ? 'bg-gray-200 text-gray-400 cursor-not-allowed' 
          : 'bg-white text-gray-700 hover:bg-gray-50 shadow-sm border'
      ]"
    >
      <span class="hidden sm:inline">Suivant</span>
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
      </svg>
    </button>
  </div>

  <!-- Info de pagination mobile -->
  <div class="sm:hidden text-center text-sm text-gray-600 mt-4">
    Page {{ currentPage }} sur {{ totalPages }}
  </div>
</template>

<script>
import { computed } from 'vue'

export default {
  name: 'Pagination',
  props: {
    currentPage: {
      type: Number,
      required: true
    },
    totalPages: {
      type: Number,
      required: true
    },
    maxVisiblePages: {
      type: Number,
      default: 5
    }
  },
  emits: ['page-change'],
  setup(props, { emit }) {
    
    // Calculer les pages visibles
    const pages = computed(() => {
      const pages = []
      const half = Math.floor(props.maxVisiblePages / 2)
      
      let start = Math.max(1, props.currentPage - half)
      let end = Math.min(props.totalPages, props.currentPage + half)
      
      // Ajuster si on est près du début ou de la fin
      if (end - start + 1 < props.maxVisiblePages) {
        if (start === 1) {
          end = Math.min(props.totalPages, start + props.maxVisiblePages - 1)
        } else {
          start = Math.max(1, end - props.maxVisiblePages + 1)
        }
      }
      
      for (let i = start; i <= end; i++) {
        pages.push(i)
      }
      
      return pages
    })

    const goToPage = (page) => {
      if (page >= 1 && page <= props.totalPages && page !== props.currentPage) {
        emit('page-change', page)
      }
    }

    return {
      pages,
      goToPage
    }
  }
}
</script>

<style scoped>
.justify-center {
  justify-content: center;
}

.items-center {
  align-items: center;
}

.gap-1 {
  gap: 0.25rem;
}

.gap-2 {
  gap: 0.5rem;
}

.flex {
  display: flex;
}

.px-2 {
  padding-left: 0.5rem;
  padding-right: 0.5rem;
}

.px-3 {
  padding-left: 0.75rem;
  padding-right: 0.75rem;
}

.px-4 {
  padding-left: 1rem;
  padding-right: 1rem;
}

.py-2 {
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
}

.rounded-lg {
  border-radius: 0.5rem;
}

.shadow-sm {
  box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
}

.shadow-md {
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
}

.border {
  border: 1px solid #e5e7eb;
}

.transition-all {
  transition: all 0.2s ease;
}

.duration-200 {
  transition-duration: 200ms;
}

.cursor-not-allowed {
  cursor: not-allowed;
}

.text-center {
  text-align: center;
}

.text-sm {
  font-size: 0.875rem;
}

.w-4 {
  width: 1rem;
}

.h-4 {
  height: 1rem;
}

.mt-4 {
  margin-top: 1rem;
}

.mt-8 {
  margin-top: 2rem;
}

.bg-gray-200 {
  background-color: #e5e7eb;
}

.bg-white {
  background-color: #ffffff;
}

.bg-blue-600 {
  background-color: #2563eb;
}

.text-gray-400 {
  color: #9ca3af;
}

.text-gray-500 {
  color: #6b7280;
}

.text-gray-600 {
  color: #4b5563;
}

.text-gray-700 {
  color: #374151;
}

.text-white {
  color: #ffffff;
}

.hover\:bg-gray-50:hover {
  background-color: #f9fafb;
}

@media (min-width: 640px) {
  .sm\:inline {
    display: inline;
  }
  
  .sm\:hidden {
    display: none;
  }
}

@media (max-width: 639px) {
  .hidden {
    display: none;
  }
}
</style>