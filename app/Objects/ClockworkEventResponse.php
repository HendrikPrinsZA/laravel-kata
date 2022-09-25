<?php

namespace App\Objects;

use App\Models\User;
use Clockwork\Clockwork;
use Clockwork\Request\Timeline\Event;
use Clockwork\Storage\Storage;
use Clockwork\Support\Laravel\ClockworkSupport;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage as FacadesStorage;

class ClockworkEventResponse
{
    protected ClockworkSupport $clockworkSupport;

    public function __construct(
        protected Clockwork $clock,
        protected Event $event,
        protected mixed $result
    ) {
        $this->clockworkSupport = app(ClockworkSupport::class);
    }

    public function duration(): int|float|Event
    {
        return $this->event->duration();
    }

    public function response(): string
    {
        return is_string($this->result)
            ? $this->result
            : json_encode($this->result, JSON_PRETTY_PRINT);
    }

    public function queryCount(): int
    {
        return rand(5, 25);
    }

    /**
     * Incorrect!
     */
    public function databaseDuration(): float
    {
        $start = $this->event->start;
        $end = $this->event->end;

        $queries = collect(
            $this->clockworkSupport->getExtendedData('latest')->original['databaseQueries']
        )->filter(function ($query) use ($start, $end) {
            return $query['time'] >= $start && $query['time'] <= $end;
        });

        return $this->clockworkSupport
            ->getExtendedData('latest')
            ->original['databaseDuration'];

        return collect();
    }
}
