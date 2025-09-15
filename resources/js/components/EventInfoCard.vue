<template>
  <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ title }}</h3>
    <div class="space-y-3">
      <slot />
    </div>
  </div>
</template>

<script>
export default {
  name: 'EventInfoCard',
  props: {
    title: {
      type: String,
      required: true
    }
  }
}
</script>