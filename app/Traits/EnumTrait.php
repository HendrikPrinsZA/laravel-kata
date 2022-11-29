<?php

namespace App\Traits;

use Illuminate\Support\Collection;

trait EnumTrait
{
    private function getMappingDetails(self $case): array
    {
        if (method_exists($this, 'mappingDetails')) {
            return $this->mappingDetails()[$case->value];
        }

        if (method_exists($this, 'details')) {
            return $this->details();
        }

        return [
            'code' => $case->value,
            'name' => $case->name,
        ];
    }

    public static function all(): Collection
    {
        return collect(self::cases())->map(
            fn (self $case) => $case->getMappingDetails($case)
        );
    }
}
