<template>
  <div v-if="activeBanner" class="top-ad-banner bg-gray-100 border-b border-gray-200">
    <div class="relative w-full">
      <component
        :is="activeBanner.link_url ? 'a' : 'div'"
        v-bind="activeBanner.link_url ? {
          href: activeBanner.link_url,
          target: '_blank',
          rel: 'noopener sponsored',
        } : {}"
        class="block w-full"
      >
        <img
          :src="activeBanner.image_url"
          :alt="activeBanner.title"
          class="w-full h-auto max-h-[80px] md:max-h-[90px] object-cover object-center block"
          loading="lazy"
        />
      </component>

      <!-- Badge "Ad" -->
      <span class="absolute top-1 right-2 inline-flex items-center gap-1 bg-black/70 text-white text-[10px] font-semibold uppercase tracking-wider px-1.5 py-0.5 rounded">
        Ad
      </span>
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

    const activeBanner = computed(() => banners.value[currentIndex.value] || null)

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

    onMounted(async () => {
      await loadBanners()
      startRotation()
    })

    onUnmounted(() => {
      if (rotationInterval) clearInterval(rotationInterval)
    })

    return { activeBanner }
  }
}
</script>
