<?php

namespace App\Services;

use App\Exceptions\SlotFullException;
use App\Models\Appointment;
use App\Models\Application;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Enforces the "50 students per day" rule for each appointment type.
 */
class SlotService
{
    public function remaining(string $type, Carbon|string $date): int
    {
        $taken = Appointment::where('type', $type)->whereDate('appointment_date', $date)->count();

        return max(0, config('cics.daily_slots') - $taken);
    }

    /** Bookable weekdays (tomorrow onward) with the number of free slots. */
    public function availableDays(string $type): array
    {
        $days = [];
        $cursor = now()->addDay()->startOfDay();
        $end = now()->addDays(config('cics.booking_window'))->endOfDay();

        while ($cursor <= $end) {
            if ($cursor->isWeekday()) {
                $days[] = [
                    'date'      => $cursor->toDateString(),
                    'label'     => $cursor->format('D, M j, Y'),
                    'remaining' => $this->remaining($type, $cursor),
                ];
            }
            $cursor = $cursor->copy()->addDay();
        }

        return $days;
    }

    /**
     * Book atomically. The row lock keeps two students from taking the last slot at once.
     *
     * @throws SlotFullException
     */
    public function book(Application $application, string $type, string $date): Appointment
    {
        return DB::transaction(function () use ($application, $type, $date) {
            $taken = Appointment::where('type', $type)
                ->whereDate('appointment_date', $date)
                ->lockForUpdate()
                ->count();

            if ($taken >= config('cics.daily_slots')) {
                throw new SlotFullException('That date is already full. Please choose another date.');
            }

            $appointment = Appointment::create([
                'application_id'   => $application->id,
                'type'             => $type,
                'appointment_date' => $date,
                'slot_number'      => $taken + 1,
            ]);

            $appointment->update([
                'reference_no' => sprintf(
                    'CICS-%s-%s-%05d',
                    $type === Appointment::EXAM ? 'EX' : 'IN',
                    now()->format('Y'),
                    $appointment->id
                ),
            ]);

            return $appointment;
        });
    }
}
