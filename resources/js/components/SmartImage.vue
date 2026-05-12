<template>
  <div
    class="smart-image relative overflow-hidden bg-gray-100"
    :class="roundedClass"
    :style="containerStyle"
  >
    <template v-if="src">
      <!-- Backdrop flou (image identique, zoomée et floutée) -->
      <img
        :src="src"
        alt=""
        aria-hidden="true"
        class="absolute inset-0 w-full h-full object-cover scale-110 blur-xl opacity-60 select-none pointer-events-none"
        loading="lazy"
      />
      <!-- Image principale -->
      <img
        :src="src"
        :alt="alt"
        class="relative w-full h-full"
        :class="fitClass"
        :loading="eager ? 'eager' : 'lazy'"
        :fetchpriority="eager ? 'high' : 'auto'"
        @error="onError"
      />
    </template>

    <!-- Placeholder quand pas d'image -->
    <div
      v-else
      class="absolute inset-0 flex items-center justify-center text-gray-300"
    >
      <slot name="placeholder">
        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
      </slot>
    </div>

    <!-- Slot overlay: badges, gradient, prix, etc. (au-dessus de l'image) -->
    <slot />
  </div>
</template>

<script>
import { computed, ref, watch } from 'vue'

export default {
  name: 'SmartImage',
  props: {
    src: { type: String, default: null },
    alt: { type: String, default: '' },
    // Format "W/H" (string) ou nombre. Ex: "16/9", "1/1", "21/9".
    aspectRatio: { type: [String, Number], default: '16/9' },
    // 'contain' (defaut, conserve l'image entière) ou 'cover' (remplit, peut crop)
    fit: { type: String, default: 'contain' },
    // Coins arrondis: 'none' | 'sm' | 'md' | 'lg' | 'xl' | 'full'
    rounded: { type: String, default: 'lg' },
    // Charger en priorité (above the fold)
    eager: { type: Boolean, default: false },
  },
  setup(props, { emit }) {
    const broken = ref(false)

    watch(() => props.src, () => { broken.value = false })

    const containerStyle = computed(() => ({
      aspectRatio: typeof props.aspectRatio === 'number'
        ? String(props.aspectRatio)
        : props.aspectRatio,
    }))

    const fitClass = computed(() => props.fit === 'cover'
      ? 'object-cover object-center'
      : 'object-contain object-center')

    const roundedClass = computed(() => ({
      'rounded-sm': props.rounded === 'sm',
      'rounded-md': props.rounded === 'md',
      'rounded-lg': props.rounded === 'lg',
      'rounded-xl': props.rounded === 'xl',
      'rounded-full': props.rounded === 'full',
    }))

    const onError = (event) => {
      broken.value = true
      emit('error', event)
    }

    return { containerStyle, fitClass, roundedClass, onError }
  },
}
</script>
