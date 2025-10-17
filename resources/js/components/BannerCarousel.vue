<template>
  <div v-if="banners.length > 0" class="banner-carousel mb-8">
    <div class="relative">
      <!-- Bannière active -->
      <div class="banner-slide">
        <a v-if="activeBanner.link_url"
           :href="activeBanner.link_url"
           target="_blank"
           rel="noopener noreferrer"
           class="block">
          <img :src="activeBanner.image_url"
               :alt="activeBanner.title"
               class="w-full h-auto rounded-lg shadow-lg object-cover banner-image">
        </a>
        <div v-else>
          <img :src="activeBanner.image_url"
               :alt="activeBanner.title"
               class="w-full h-auto rounded-lg shadow-lg object-cover banner-image">
        </div>

        <!-- Informations optionnelles de la bannière -->
        <div v-if="showInfo && activeBanner.description"
             class="mt-4 text-center">
          <h3 class="text-lg font-bold text-gray-900">{{ activeBanner.title }}</h3>
          <p class="text-sm text-gray-600 mt-1">{{ activeBanner.description }}</p>
        </div>
      </div>

      <!-- Indicateurs de navigation (si plusieurs bannières) -->
      <div v-if="banners.length > 1" class="flex justify-center mt-4 space-x-2">
        <button v-for="(banner, index) in banners" :key="banner.id"
                @click="goToSlide(index)"
                class="w-3 h-3 rounded-full transition-all duration-200"
                :class="index === currentIndex ? 'bg-blue-900 w-8' : 'bg-gray-300 hover:bg-gray-400'">
        </button>
      </div>

      <!-- Boutons de navigation (optionnels) -->
      <div v-if="banners.length > 1 && showNavButtons" class="absolute inset-y-0 left-0 right-0 flex items-center justify-between px-4 pointer-events-none">
        <button @click="prevSlide"
                class="pointer-events-auto bg-white bg-opacity-75 hover:bg-opacity-100 rounded-full p-2 shadow-lg transition-all duration-200 text-blue-900 hover:text-yellow-500">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
          </svg>
        </button>
        <button @click="nextSlide"
                class="pointer-events-auto bg-white bg-opacity-75 hover:bg-opacity-100 rounded-full p-2 shadow-lg transition-all duration-200 text-blue-900 hover:text-yellow-500">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue'

export default {
  name: 'BannerCarousel',
  props: {
    position: {
      type: String,
      default: 'home',
      validator: (value) => ['home', 'events', 'checkout', 'all'].includes(value)
    },
    autoPlay: {
      type: Boolean,
      default: true
    },
    interval: {
      type: Number,
      default: 5000 // 5 secondes
    },
    showInfo: {
      type: Boolean,
      default: false
    },
    showNavButtons: {
      type: Boolean,
      default: true
    }
  },
  setup(props) {
    const banners = ref([])
    const currentIndex = ref(0)
    const loading = ref(false)
    let autoPlayInterval = null

    const activeBanner = computed(() => {
      return banners.value[currentIndex.value] || {}
    })

    const loadBanners = async () => {
      loading.value = true
      try {
        const response = await fetch(`/api/banners/active?position=${props.position}`)
        if (response.ok) {
          const data = await response.json()
          if (data.success) {
            banners.value = data.data
          }
        }
      } catch (error) {
        console.error('Erreur lors du chargement des bannières:', error)
      } finally {
        loading.value = false
      }
    }

    const nextSlide = () => {
      currentIndex.value = (currentIndex.value + 1) % banners.value.length
      resetAutoPlay()
    }

    const prevSlide = () => {
      currentIndex.value = (currentIndex.value - 1 + banners.value.length) % banners.value.length
      resetAutoPlay()
    }

    const goToSlide = (index) => {
      currentIndex.value = index
      resetAutoPlay()
    }

    const startAutoPlay = () => {
      if (props.autoPlay && banners.value.length > 1) {
        autoPlayInterval = setInterval(nextSlide, props.interval)
      }
    }

    const stopAutoPlay = () => {
      if (autoPlayInterval) {
        clearInterval(autoPlayInterval)
        autoPlayInterval = null
      }
    }

    const resetAutoPlay = () => {
      stopAutoPlay()
      startAutoPlay()
    }

    onMounted(() => {
      loadBanners().then(() => {
        startAutoPlay()
      })
    })

    onUnmounted(() => {
      stopAutoPlay()
    })

    return {
      banners,
      currentIndex,
      loading,
      activeBanner,
      nextSlide,
      prevSlide,
      goToSlide
    }
  }
}
</script>

<style scoped>
.banner-carousel {
  position: relative;
  max-width: 100%;
}

.banner-slide {
  position: relative;
  animation: fadeIn 0.5s ease-in-out;
}

.banner-image {
  max-height: 400px;
  width: 100%;
  object-fit: cover;
}

@media (max-width: 768px) {
  .banner-image {
    max-height: 250px;
  }
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}
</style>
