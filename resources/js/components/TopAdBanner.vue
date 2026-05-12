<template>
  <div v-if="hasBanners" class="top-ad-banner bg-gray-50 border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6 py-2">
      <div class="w-full aspect-[16/3] sm:aspect-[20/3] md:aspect-[24/3] lg:aspect-[28/3] xl:aspect-[32/3]">
        <div class="relative w-full h-full overflow-hidden rounded-md bg-white">
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
              v-for="(banner, idx) in banners"
              :key="banner.id"
              type="button"
              :aria-label="`Afficher la publicité ${idx + 1}`"
              :aria-current="idx === currentIndex ? 'true' : 'false'"
              class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full transition-colors"
              :class="idx === currentIndex ? 'bg-white' : 'bg-white/50 hover:bg-white/80'"
              @click="selectBanner(idx)"
            />
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
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import axios from 'axios'

export default {
  name: 'TopAdBanner',
  setup() {
    const banners = ref([])
    const currentIndex = ref(0)
    let rotationInterval = null

    const hasBanners = computed(() => banners.value.length > 0)
    const activeBanner = computed(() => banners.value[currentIndex.value] || {})

    const loadBanners = async () => {
      try {
        const response = await axios.get('/api/v1/banners/active', {
          params: { position: 'header-top' }
        })
        if (response.data && response.data.success) {
          banners.value = response.data.data
        }
      } catch (error) {
        console.error('Erreur chargement bandeau pub:', error)
      }
    }

    const startRotation = () => {
      if (banners.value.length > 1) {
        rotationInterval = setInterval(() => {
          currentIndex.value = (currentIndex.value + 1) % banners.value.length
        }, 7000)
      }
    }

    const stopRotation = () => {
      if (rotationInterval) {
        clearInterval(rotationInterval)
        rotationInterval = null
      }
    }

    const selectBanner = (idx) => {
      currentIndex.value = idx
      stopRotation()
      startRotation()
    }

    onMounted(async () => {
      await loadBanners()
      startRotation()
    })

    onUnmounted(stopRotation)

    return { banners, currentIndex, hasBanners, activeBanner, selectBanner }
  }
}
</script>
