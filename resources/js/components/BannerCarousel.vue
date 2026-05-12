<template>
  <div v-if="banners.length > 0" class="banner-carousel mb-8">
    <div class="w-full aspect-[16/3] sm:aspect-[20/3] md:aspect-[24/3] lg:aspect-[28/3] xl:aspect-[32/3]">
      <div class="relative w-full h-full overflow-hidden rounded-lg shadow-lg bg-white">
        <!-- Badge Ad -->
        <div class="absolute top-1 right-1 sm:top-2 sm:right-2 z-20">
          <span class="bg-gray-800/80 text-white text-[8px] sm:text-[10px] font-semibold px-1 sm:px-2 py-0.5 rounded backdrop-blur-sm">
            Ad
          </span>
        </div>

        <!-- Dots de navigation -->
        <div
          v-if="banners.length > 1"
          class="absolute bottom-1 sm:bottom-2 left-0 right-0 flex justify-center gap-1 sm:gap-2 z-10"
        >
          <button
            v-for="(banner, index) in banners"
            :key="banner.id"
            type="button"
            :aria-label="`Afficher la publicité ${index + 1}`"
            :aria-current="index === currentIndex ? 'true' : 'false'"
            class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full transition-colors"
            :class="index === currentIndex ? 'bg-white' : 'bg-white/50 hover:bg-white/80'"
            @click="goToSlide(index)"
          />
        </div>

        <!-- Boutons précédent/suivant -->
        <div
          v-if="banners.length > 1 && showNavButtons"
          class="absolute inset-y-0 left-0 right-0 flex items-center justify-between px-2 sm:px-3 pointer-events-none z-10"
        >
          <button
            type="button"
            aria-label="Précédent"
            class="pointer-events-auto bg-white/75 hover:bg-white rounded-full p-1.5 sm:p-2 shadow text-primea-blue hover:text-primea-yellow transition-colors"
            @click="prevSlide"
          >
            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <button
            type="button"
            aria-label="Suivant"
            class="pointer-events-auto bg-white/75 hover:bg-white rounded-full p-1.5 sm:p-2 shadow text-primea-blue hover:text-primea-yellow transition-colors"
            @click="nextSlide"
          >
            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>

        <!-- Slide -->
        <component
          :is="activeBanner.link_url ? 'a' : 'div'"
          v-bind="activeBanner.link_url ? {
            href: activeBanner.link_url,
            target: '_blank',
            rel: 'noopener sponsored',
          } : {}"
          class="block w-full h-full"
          :aria-label="activeBanner.title"
        >
          <div class="w-full h-full flex items-center justify-center">
            <img
              :src="activeBanner.image_url"
              :alt="activeBanner.title"
              class="max-w-full max-h-full object-contain object-center"
              loading="lazy"
            />
          </div>
        </component>
      </div>
    </div>

    <!-- Informations optionnelles sous la bannière -->
    <div v-if="showInfo && activeBanner.description" class="mt-3 text-center">
      <h3 class="text-lg font-bold text-gray-900">{{ activeBanner.title }}</h3>
      <p class="text-sm text-gray-600 mt-1">{{ activeBanner.description }}</p>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import axios from 'axios'

export default {
  name: 'BannerCarousel',
  props: {
    position: {
      type: String,
      default: 'home',
      validator: (value) => ['home', 'home-top', 'home-bottom', 'events', 'checkout', 'all'].includes(value)
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
        const response = await axios.get(`/api/v1/banners/active`, {
          params: { position: props.position }
        })
        if (response.data && response.data.success) {
          banners.value = response.data.data
        }
      } catch (error) {
        console.error('Erreur lors du chargement des bannieres:', error)
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
  animation: fadeIn 0.4s ease-in-out;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}
</style>
