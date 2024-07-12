<?php

namespace App\Contracts\Services;

interface DateServiceInterface
{
    public function calculateDate(int $value, string $unit): string;
    public function formatDate(string $date, string $oldFormat, string $newFormat): string;

}
