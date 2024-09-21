<?php

namespace App\Models;

use Doctrine\Common\Cache\Psr6\InvalidArgument;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ExperimentARecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'unique_field_1',
        'unique_field_2',
        'unique_field_3',
        'update_field_1',
        'update_field_2',
        'update_field_3',
    ];

    public static function upsertPrototype(array $values, array $uniqueFields, ?array $updateFields = null): array
    {
        $chunkSize = 1000;
        $chunks = array_chunk($values, $chunkSize);

        $results = [
            'skipped' => 0,
            'updated' => 0,
            'inserted' => 0,
        ];
        foreach ($chunks as $chunk) {
            $result = static::upsertPrototypeChunk($chunk, $uniqueFields, $updateFields);

            $results['skipped'] += $result['skipped'];
            $results['updated'] += $result['updated'];
            $results['inserted'] += $result['inserted'];
        }

        return $results;
    }

    protected static function upsertPrototypeChunk(array $values, array $uniqueFields, ?array $updateFields = null): array
    {
        if (empty($values)) {
            return true;
        }

        if (empty($uniqueFields)) {
            throw new InvalidArgument('Unique fields must be provided');
        }

        $now = now();

        // Find matching records based on unique fields
        $uniqueFieldsIn = [];
        foreach ($values as &$value) {
            $missingUniqueFields = array_diff_key(array_flip($uniqueFields), $value);
            if (! empty($missingUniqueFields)) {
                throw new InvalidArgument(sprintf('Expected unique field/s %s missing', implode(', ', array_keys($missingUniqueFields))));
            }

            $missingUpdateFields = array_diff_key(array_flip($updateFields), $value);
            if (! empty($missingUpdateFields)) {
                throw new InvalidArgument(sprintf('Expected update field/s %s missing', implode(', ', array_keys($missingUpdateFields))));
            }

            $valueUniqueFields = array_map(fn ($field) => "'{$value[$field]}'", $uniqueFields);
            $uniqueFieldsIn[] = '('.implode(',', $valueUniqueFields).')';

            $value['uniquesHash'] = md5(implode('|', array_map(fn ($field) => $value[$field], $uniqueFields)));
            $value['updatesHash'] = md5(implode('|', array_map(fn ($field) => $value[$field], $updateFields)));
        }
        unset($value);

        $uniqueFieldsIn = '('.implode(',', $uniqueFieldsIn).')';

        // Find the matching records
        $lookupValues = static::query()
            ->select('id')
            ->selectRaw('MD5(CONCAT_WS("|", '.implode(',', $uniqueFields).')) as unique_fields_hash')
            ->selectRaw('MD5(CONCAT_WS("|", '.implode(',', $updateFields).')) as update_values_hash')
            ->whereRaw('('.implode(',', $uniqueFields).") IN {$uniqueFieldsIn}")
            ->get()
            ->mapWithKeys(fn ($record) => [$record->unique_fields_hash => [
                'id' => $record->id,
                'update_values_hash' => $record->update_values_hash,
            ]])->toArray();

        $matchingCount = 0;
        $updates = [];
        $inserts = [];
        foreach ($values as $value) {
            // Ignore any values that match the unique and values
            $uniquesHash = $value['uniquesHash'];
            $updatesHash = $value['updatesHash'];

            // Discard the values that match the unique and update fields
            unset(
                $value['uniquesHash'],
                $value['updatesHash']
            );

            if (isset($lookupValues[$uniquesHash])) {
                if ($lookupValues[$uniquesHash]['update_values_hash'] === $updatesHash) {
                    $matchingCount++;

                    continue;
                }

                // Unset the unique fields dynamically
                // - General error: 1364 Field 'unique_field_1' doesn't have a default value
                foreach ($uniqueFields as $uniqueField) {
                    unset($value[$uniqueField]);
                }

                $updates[] = array_merge([
                    'id' => $lookupValues[$uniquesHash]['id'],
                ], $value, [
                    'updated_at' => $now,
                ]);

                continue;
            }

            $inserts[] = array_merge($value, [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $values = null;
        unset($values);

        $insertsCount = count($inserts);
        if ($insertsCount > 0) {
            self::insert($inserts);
        }
        $inserts = null;
        unset($inserts);

        $updatesCount = count($updates);
        if ($updatesCount > 0) {
            // self::upsert($updates, ['id'], $updateFields);
            self::updateAllById($updates, $updateFields);
        }
        $updates = null;
        unset($updates);

        return [
            'skipped' => $matchingCount,
            'updated' => $updatesCount,
            'inserted' => $insertsCount,
        ];
    }

    protected static function updateAllById(array $values, array $updateFields): void
    {
        $sets = [];
        $sets[] = 'updated_at = NOW()';
        foreach ($updateFields as $field) {
            $sets[] = "{$field} = (CASE id ".implode(' ', array_map(fn ($value) => "WHEN {$value['id']} THEN '{$value[$field]}'", $values)).' END)';
        }

        $sets = sprintf(
            'UPDATE experiment_a_records SET %s WHERE id IN (%s)',
            implode(',', $sets),
            implode(',', array_column($values, 'id'))
        );

        DB::transaction(fn () => DB::statement($sets), 5);
    }
}
