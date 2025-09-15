<template>
  <div class="schedule-section">
    <h3 class="text-xl font-semibold mb-4">Programme</h3>
    <div class="space-y-3">
      <div v-for="schedule in schedules" :key="schedule.id" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
        <div>
          <div class="font-medium">{{ schedule.title }}</div>
          <div class="text-sm text-gray-600">{{ formatTime(schedule.starts_at) }} - {{ formatTime(schedule.ends_at) }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ScheduleSection',
  props: {
    schedules: {
      type: Array,
      default: () => []
    }
  },
  methods: {
    formatTime(time) {
      return new Date(time).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
    }
  }
}
</script>