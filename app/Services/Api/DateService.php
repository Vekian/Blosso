<?php

namespace App\Services\Api;

use App\Contracts\Services\DateServiceInterface;
use Carbon\Carbon;

class DateService implements DateServiceInterface {

    public function calculateDate(int $value, string $unit): string
    {
        $currentDate = Carbon::now();
        $futureDate = match($unit) {
            "days" => $currentDate->addDays($value)->format('Y-m-d'),
            "weeks" => $currentDate->addWeeks($value)->format('Y-m-d'),
        };
        return $futureDate;
    }

    public function formatDate(string $date, string $oldFormat, string $newFormat): string
    {
        $formatedDate = Carbon::createFromFormat($oldFormat, $date)->format($newFormat);
        return $formatedDate;
    }

}