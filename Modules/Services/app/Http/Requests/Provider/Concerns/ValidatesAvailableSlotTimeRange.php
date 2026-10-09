<?php

namespace Modules\Services\Http\Requests\Provider\Concerns;

use Carbon\Carbon;
use Illuminate\Validation\Validator;

trait ValidatesAvailableSlotTimeRange
{
    /**
     * Flat list: slots.*.{start_time,end_time}
     */
    protected function validateFlatSlotsEndAfterStart(Validator $validator, string $slotsKey = 'slots'): void
    {
        $slots = $this->input($slotsKey);
        if (! is_array($slots)) {
            return;
        }

        foreach (array_values($slots) as $i => $slot) {
            if (! is_array($slot)) {
                continue;
            }
            $start = $slot['start_time'] ?? null;
            $end = $slot['end_time'] ?? null;
            if (! $this->slotEndIsAfterStart($start, $end)) {
                $validator->errors()->add(
                    "{$slotsKey}.{$i}.end_time",
                    __('validation.after', ['attribute' => 'end time', 'date' => 'start time'])
                );
            }
        }
    }

    /**
     * Nested: available_slots.*.slots.*.{start_time,end_time}
     */
    protected function validateNestedAvailableSlotsEndAfterStart(Validator $validator): void
    {
        $groups = $this->input('available_slots');
        if (! is_array($groups)) {
            return;
        }

        foreach (array_values($groups) as $d => $group) {
            if (! is_array($group)) {
                continue;
            }
            $slots = $group['slots'] ?? [];
            if (! is_array($slots)) {
                continue;
            }
            foreach (array_values($slots) as $s => $slot) {
                if (! is_array($slot)) {
                    continue;
                }
                $start = $slot['start_time'] ?? null;
                $end = $slot['end_time'] ?? null;
                if (! $this->slotEndIsAfterStart($start, $end)) {
                    $validator->errors()->add(
                        "available_slots.{$d}.slots.{$s}.end_time",
                        __('validation.after', ['attribute' => 'end time', 'date' => 'start time'])
                    );
                }
            }
        }
    }

    /**
     * Single pair (add / update single slot).
     */
    protected function validateSingleSlotEndAfterStart(Validator $validator, ?string $startKey = 'start_time', ?string $endKey = 'end_time'): void
    {
        $start = $this->input($startKey);
        $end = $this->input($endKey);
        if ($start === null || $end === null || $start === '' || $end === '') {
            return;
        }
        if (! $this->slotEndIsAfterStart($start, $end)) {
            $validator->errors()->add(
                $endKey,
                __('validation.after', ['attribute' => 'end time', 'date' => 'start time'])
            );
        }
    }

    private function slotEndIsAfterStart(mixed $start, mixed $end): bool
    {
        if (! is_string($start) || ! is_string($end) || $start === '' || $end === '') {
            return true;
        }

        try {
            $s = Carbon::createFromFormat('H:i', $start);
            $e = Carbon::createFromFormat('H:i', $end);
        } catch (\Exception) {
            return true;
        }

        return $e->gt($s);
    }
}
