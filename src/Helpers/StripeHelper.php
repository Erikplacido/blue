<?php
namespace Src\Helpers;

class StripeHelper {
    public static function getIntervalFromString(string $recurrence): ?string {
        return match ($recurrence) {
            'weekly' => 'P7D',
            'fortnightly' => 'P15D',
            'monthly' => 'P30D',
            default => null,
        };
    }

    public static function getRecurringLabel(string $interval): string {
        return match ($interval) {
            'P7D' => 'Weekly',
            'P15D' => 'Fortnightly',
            'P30D' => 'Monthly',
            default => 'One-time',
        };
    }
}
