<?php

namespace Database\Seeders\Models;

use App\Models\Day;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class DaysSeeder extends ModelSeeder
{
    protected const MAX_PLACEHOLDERS = 65000;

    protected const DATE_START = '2001-01-01';

    protected const DATE_END = '2027-12-31';

    public function seed(): void
    {
        $dateStart = Carbon::createFromFormat('Y-m-d', self::DATE_START);
        $dateEnd = Carbon::createFromFormat('Y-m-d', self::DATE_END);

        // $this->seedPerDay($dateStart, $dateEnd);
        // $this->seedPerMonth($dateStart, $dateEnd);
        $this->seedPerYear($dateStart, $dateEnd);
        // $this->seedMaxPlaceholders($dateStart, $dateEnd);
    }

    /**
     * Stats
     * - Empty: 11,162.59 ms
     * - Full:   2,451.80 ms
     */
    protected function seedPerDay(Carbon $dateStart, Carbon $dateEnd): void
    {
        $days = CarbonPeriod::create($dateStart, '1 day', $dateEnd);

        /** @var Carbon $day */
        foreach ($days as $day) {
            Day::firstOrCreate([
                'day' => $day->toDateString(),
            ]);
        }
    }

    /**
     * Stats
     * - Empty: 484.37 ms
     * - Full:  404.95 ms
     */
    protected function seedPerMonth(Carbon $dateStart, Carbon $dateEnd): void
    {
        $days = [];
        $month = $dateStart->month;
        while ($dateStart->lte($dateEnd)) {
            if ($month !== $dateStart->month) {
                $month = $dateStart->month;
                $this->upsert($days);
                $days = [];
            }

            $days[] = $dateStart->toDateString();
            $dateStart->addDay();
        }

        $this->upsert($days);
    }

    /**
     * Stats
     * - Empty: 175.69 ms
     * - Full:  202.90 ms
     */
    protected function seedPerYear(Carbon $dateStart, Carbon $dateEnd): void
    {
        $days = [];
        $year = $dateStart->year;
        while ($dateStart->lte($dateEnd)) {
            if ($year !== $dateStart->year) {
                $year = $dateStart->year;
                $this->upsert($days);
                $days = [];
            }

            $days[] = $dateStart->toDateString();
            $dateStart->addDay();
        }

        $this->upsert($days);
    }

    /**
     * Stats
     * - Empty: 273.32 ms
     * - Full:  183.24 ms
     */
    protected function seedMaxPlaceholders(Carbon $dateStart, Carbon $dateEnd): void
    {
        $days = [];
        while ($dateStart->lte($dateEnd)) {
            if (count($days) === self::MAX_PLACEHOLDERS) {
                $this->upsert($days);
                $days = [];
            }

            $days[] = $dateStart->toDateString();
            $dateStart->addDay();
        }

        $this->upsert($days);
    }

    protected function upsert(array $days): void
    {
        if (empty($days)) {
            return;
        }

        $days = collect($days)
            ->map(fn (string $day) => ['day' => $day])
            ->toArray();

        Day::insertOrIgnore($days);
    }
}
