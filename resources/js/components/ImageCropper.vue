<template>
  <Teleport to="body">
    <div
      v-if="open && src"
      class="fixed inset-0 z-[60] flex items-center justify-center bg-black/70 p-4"
      role="dialog"
      aria-modal="true"
    >
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl flex flex-col max-h-[90vh]">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
          <div>
            <h3 class="text-lg font-bold text-primea-blue">{{ title }}</h3>
            <p class="text-xs text-gray-500 mt-0.5">
              Ajustez le cadrage. Le ratio est imposé pour garder un rendu cohérent partout sur le site.
            </p>
          </div>
          <button
            type="button"
            aria-label="Fermer"
            class="p-1 rounded hover:bg-gray-100 text-gray-500"
            @click="cancel"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Cropper -->
        <div class="flex-1 overflow-hidden bg-gray-900">
          <Cropper
            ref="cropperRef"
            :src="src"
            :stencil-props="{ aspectRatio: numericAspectRatio }"
            :default-size="defaultSize"
            image-restriction="fit-area"
            class="cropper"
          />
        </div>

        <!-- Footer -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center sm:justify-end gap-2 px-5 py-4 border-t border-gray-200">
          <button
            type="button"
            class="px-4 py-2 rounded-lg text-gray-700 hover:bg-gray-100 border border-gray-200 order-2 sm:order-1"
            @click="cancel"
          >
            Annuler
          </button>
          <button
            type="button"
            class="px-4 py-2 rounded-lg bg-primea-blue text-white font-semibold hover:bg-primea-yellow hover:text-primea-blue transition-colors order-1 sm:order-2"
            :disabled="processing"
            @click="confirm"
          >
            {{ processing ? 'Traitement…' : 'Valider le cadrage' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script>
import { ref, computed } from 'vue'
import { Cropper } from 'vue-advanced-cropper'
import 'vue-advanced-cropper/dist/style.css'

export default {
  name: 'ImageCropper',
  components: { Cropper },
  props: {
    /** Affichage du modal. */
    open: { type: Boolean, default: false },
    /** Source de l'image: dataURL ou ObjectURL. */
    src: { type: String, default: '' },
    /** Ratio cible. "W/H" string ou nombre. */
    aspectRatio: { type: [String, Number], default: '16/9' },
    /** Nom du fichier de sortie. */
    fileName: { type: String, default: 'cropped-image.jpg' },
    /** MIME de sortie. */
    mimeType: { type: String, default: 'image/jpeg' },
    /** Qualité JPEG (0–1). */
    quality: { type: Number, default: 0.9 },
    /** Titre affiché en haut du modal. */
    title: { type: String, default: 'Recadrer l\'image' },
  },
  emits: ['cropped', 'cancel', 'update:open'],
  setup(props, { emit }) {
    const cropperRef = ref(null)
    const processing = ref(false)

    const numericAspectRatio = computed(() => {
      if (typeof props.aspectRatio === 'number') return props.aspectRatio
      const [w, h] = String(props.aspectRatio).split('/').map(Number)
      return w && h ? w / h : 16 / 9
    })

    const defaultSize = ({ imageSize }) => ({
      width: imageSize.width,
      height: imageSize.height,
    })

    const close = () => emit('update:open', false)

    const cancel = () => {
      emit('cancel')
      close()
    }

    const confirm = async () => {
      if (!cropperRef.value) return
      processing.value = true
      try {
        const { canvas } = cropperRef.value.getResult()
        if (!canvas) {
          processing.value = false
          return
        }

        const blob = await new Promise(resolve => {
          canvas.toBlob(resolve, props.mimeType, props.quality)
        })

        if (!blob) {
          processing.value = false
          return
        }

        const file = new File([blob], props.fileName, {
          type: props.mimeType,
          lastModified: Date.now(),
        })

        emit('cropped', file)
        close()
      } finally {
        processing.value = false
      }
    }

    return { cropperRef, processing, numericAspectRatio, defaultSize, cancel, confirm }
  },
}
</script>

<style scoped>
.cropper {
  height: 60vh;
  max-height: 500px;
}
</style>
