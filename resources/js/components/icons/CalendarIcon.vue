<template>
  <CalendarDaysIcon :class="iconClass" v-bind="$attrs" />
</template>

<script>
import { CalendarDaysIcon } from '@heroicons/vue/24/outline'

export default {
  name: 'CalendarIcon',
  components: {
    CalendarDaysIcon
  },
  props: {
    size: {
      type: String,
      default: 'md',
      validator: value => ['xs', 'sm', 'md', 'lg', 'xl', '2xl'].includes(value)
    }
  },
  computed: {
    iconClass() {
      const sizeClasses = {
        xs: 'w-3 h-3',
        sm: 'w-4 h-4', 
        md: 'w-5 h-5',
        lg: 'w-6 h-6',
        xl: 'w-8 h-8',
        '2xl': 'w-16 h-16'
      }
      return sizeClasses[this.size]
    }
  }
}
</script>