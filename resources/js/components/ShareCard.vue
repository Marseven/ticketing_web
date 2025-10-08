<template>
  <div class="share-card bg-white rounded-2xl shadow-lg p-6">
    <h3 class="text-lg font-semibold mb-4">Partager cet événement</h3>
    
    <div class="flex space-x-3">
      <button 
        @click="shareOnFacebook"
        class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors"
      >
        Facebook
      </button>
      
      <button 
        @click="shareOnTwitter"
        class="flex-1 bg-sky-400 text-white py-2 px-4 rounded-lg hover:bg-sky-500 transition-colors"
      >
        Twitter
      </button>
      
      <button 
        @click="shareOnWhatsApp"
        class="flex-1 bg-green-500 text-white py-2 px-4 rounded-lg hover:bg-green-600 transition-colors"
      >
        WhatsApp
      </button>
    </div>
    
    <div class="mt-4">
      <label class="block text-sm font-medium text-gray-700 mb-2">Lien de l'événement</label>
      <div class="flex">
        <input 
          v-model="eventUrl" 
          type="text" 
          readonly 
          class="flex-1 px-3 py-2 border border-gray-300 rounded-l-lg bg-gray-50"
        />
        <button 
          @click="copyToClipboard"
          class="px-4 py-2 bg-gray-200 border border-l-0 border-gray-300 rounded-r-lg hover:bg-gray-300 transition-colors"
        >
          Copier
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue'

export default {
  name: 'ShareCard',
  props: {
    event: {
      type: Object,
      required: true
    }
  },
  setup(props) {
    const eventUrl = computed(() => {
      return `${window.location.origin}/events/${props.event.slug}`
    })

    const shareOnFacebook = () => {
      const url = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(eventUrl.value)}`
      window.open(url, '_blank')
    }

    const shareOnTwitter = () => {
      const text = `Découvrez cet événement: ${props.event.title}`
      const url = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(eventUrl.value)}`
      window.open(url, '_blank')
    }

    const shareOnWhatsApp = () => {
      const text = `Découvrez cet événement: ${props.event.title} ${eventUrl.value}`
      const url = `https://wa.me/?text=${encodeURIComponent(text)}`
      window.open(url, '_blank')
    }

    const copyToClipboard = async () => {
      try {
        await navigator.clipboard.writeText(eventUrl.value)
        // TODO: Afficher une notification de succès
      } catch (err) {
        console.error('Erreur lors de la copie:', err)
      }
    }

    return {
      eventUrl,
      shareOnFacebook,
      shareOnTwitter,
      shareOnWhatsApp,
      copyToClipboard
    }
  }
}
</script>