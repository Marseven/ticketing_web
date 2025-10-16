<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventRecurrenceRule;
use App\Models\EventSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EventRecurrenceService
{
    /**
     * Generate schedules based on recurrence rules.
     *
     * @param EventRecurrenceRule $rule
     * @param Carbon $baseStartTime Base time for the first occurrence
     * @param Carbon $baseEndTime Base duration
     * @param int|null $venueId Optional venue ID
     * @param int $maxOccurrences Safety limit
     * @return array Array of created schedules
     */
    public function generateSchedules(
        EventRecurrenceRule $rule,
        Carbon $baseStartTime,
        Carbon $baseEndTime,
        ?int $venueId = null,
        int $maxOccurrences = 365
    ): array {
        $schedules = [];
        $currentDate = $baseStartTime->copy();
        $endDate = $rule->until ? Carbon::parse($rule->until) : $baseStartTime->copy()->addYear();
        $count = 0;

        Log::info('🔄 Generating recurring schedules', [
            'event_id' => $rule->event_id,
            'frequency' => $rule->frequency,
            'interval' => $rule->interval,
            'from' => $baseStartTime->toDateTimeString(),
            'until' => $endDate->toDateTimeString(),
        ]);

        while ($currentDate->lte($endDate) && $count < $maxOccurrences) {
            // Si count est défini et atteint, arrêter
            if ($rule->count && $count >= $rule->count) {
                break;
            }

            // Vérifier si la date correspond aux critères
            if ($this->matchesRecurrencePattern($currentDate, $rule)) {
                // Vérifier si la date n'est pas dans les exceptions
                if (!$rule->isDateExcluded($currentDate)) {
                    // Créer le schedule
                    $startDateTime = $currentDate->copy()
                        ->setTime($baseStartTime->hour, $baseStartTime->minute, $baseStartTime->second);

                    $duration = $baseEndTime->diffInMinutes($baseStartTime);
                    $endDateTime = $startDateTime->copy()->addMinutes($duration);

                    $schedule = EventSchedule::create([
                        'event_id' => $rule->event_id,
                        'venue_id' => $venueId,
                        'starts_at' => $startDateTime,
                        'ends_at' => $endDateTime,
                        'door_time' => $startDateTime->copy()->subMinutes(30),
                        'status' => 'active',
                    ]);

                    $schedules[] = $schedule;
                    $count++;

                    Log::info('✅ Schedule created', [
                        'schedule_id' => $schedule->id,
                        'starts_at' => $startDateTime->toDateTimeString(),
                    ]);
                }
            }

            // Avancer à la prochaine date selon la fréquence
            $currentDate = $this->getNextOccurrenceDate($currentDate, $rule);
        }

        Log::info('✨ Recurring schedules generation complete', [
            'event_id' => $rule->event_id,
            'schedules_created' => count($schedules),
        ]);

        return $schedules;
    }

    /**
     * Check if a date matches the recurrence pattern.
     *
     * @param Carbon $date
     * @param EventRecurrenceRule $rule
     * @return bool
     */
    private function matchesRecurrencePattern(Carbon $date, EventRecurrenceRule $rule): bool
    {
        // Vérifier by_day (jours de la semaine)
        if ($rule->by_day) {
            $dayOfWeek = $this->carbonDayToICalDay($date->dayOfWeek);
            if (!in_array($dayOfWeek, $rule->by_day_array)) {
                return false;
            }
        }

        // Vérifier by_month_day (jours du mois)
        if ($rule->by_month_day) {
            if (!in_array($date->day, $rule->by_month_day_array)) {
                return false;
            }
        }

        // Vérifier by_month (mois de l'année)
        if ($rule->by_month) {
            if (!in_array($date->month, $rule->by_month_array)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the next occurrence date based on frequency and interval.
     *
     * @param Carbon $currentDate
     * @param EventRecurrenceRule $rule
     * @return Carbon
     */
    private function getNextOccurrenceDate(Carbon $currentDate, EventRecurrenceRule $rule): Carbon
    {
        $nextDate = $currentDate->copy();

        switch ($rule->frequency) {
            case 'daily':
                $nextDate->addDays($rule->interval);
                break;

            case 'weekly':
                $nextDate->addWeeks($rule->interval);
                break;

            case 'monthly':
                $nextDate->addMonths($rule->interval);
                break;

            case 'yearly':
                $nextDate->addYears($rule->interval);
                break;
        }

        return $nextDate;
    }

    /**
     * Convert Carbon day of week to iCalendar format.
     *
     * @param int $carbonDay 0=Sunday, 1=Monday, ..., 6=Saturday
     * @return string MO, TU, WE, TH, FR, SA, SU
     */
    private function carbonDayToICalDay(int $carbonDay): string
    {
        $map = [
            0 => 'SU',
            1 => 'MO',
            2 => 'TU',
            3 => 'WE',
            4 => 'TH',
            5 => 'FR',
            6 => 'SA',
        ];

        return $map[$carbonDay] ?? 'MO';
    }

    /**
     * Delete all generated schedules for an event.
     *
     * @param int $eventId
     * @return int Number of deleted schedules
     */
    public function deleteGeneratedSchedules(int $eventId): int
    {
        $deleted = EventSchedule::where('event_id', $eventId)->delete();

        Log::info('🗑️  Deleted recurring schedules', [
            'event_id' => $eventId,
            'count' => $deleted,
        ]);

        return $deleted;
    }

    /**
     * Regenerate all schedules for an event based on its recurrence rules.
     *
     * @param Event $event
     * @param Carbon $baseStartTime
     * @param Carbon $baseEndTime
     * @param int|null $venueId
     * @return array
     */
    public function regenerateSchedules(
        Event $event,
        Carbon $baseStartTime,
        Carbon $baseEndTime,
        ?int $venueId = null
    ): array {
        // Supprimer les anciens schedules
        $this->deleteGeneratedSchedules($event->id);

        $allSchedules = [];

        // Générer pour chaque règle de récurrence
        foreach ($event->recurrenceRule as $rule) {
            $schedules = $this->generateSchedules($rule, $baseStartTime, $baseEndTime, $venueId);
            $allSchedules = array_merge($allSchedules, $schedules);
        }

        return $allSchedules;
    }

    /**
     * Preview schedules without creating them.
     *
     * @param EventRecurrenceRule $rule
     * @param Carbon $baseStartTime
     * @param Carbon $baseEndTime
     * @param int $maxPreview
     * @return array Array of date strings
     */
    public function previewSchedules(
        EventRecurrenceRule $rule,
        Carbon $baseStartTime,
        Carbon $baseEndTime,
        int $maxPreview = 10
    ): array {
        $dates = [];
        $currentDate = $baseStartTime->copy();
        $endDate = $rule->until ? Carbon::parse($rule->until) : $baseStartTime->copy()->addMonths(3);
        $count = 0;

        while ($currentDate->lte($endDate) && $count < $maxPreview) {
            if ($rule->count && $count >= $rule->count) {
                break;
            }

            if ($this->matchesRecurrencePattern($currentDate, $rule)) {
                if (!$rule->isDateExcluded($currentDate)) {
                    $startDateTime = $currentDate->copy()
                        ->setTime($baseStartTime->hour, $baseStartTime->minute);

                    $duration = $baseEndTime->diffInMinutes($baseStartTime);
                    $endDateTime = $startDateTime->copy()->addMinutes($duration);

                    $dates[] = [
                        'starts_at' => $startDateTime->toDateTimeString(),
                        'ends_at' => $endDateTime->toDateTimeString(),
                        'day_name' => $startDateTime->locale('fr')->isoFormat('dddd D MMMM YYYY'),
                    ];
                    $count++;
                }
            }

            $currentDate = $this->getNextOccurrenceDate($currentDate, $rule);
        }

        return $dates;
    }
}
