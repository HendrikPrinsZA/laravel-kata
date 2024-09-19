<?php

namespace App\Models;

use Doctrine\Common\Cache\Psr6\InvalidArgument;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ExperimentARecord
 *
 * ## Some notes
 *
 * ### Recude to separate between insert and update
 * - We can reduce the complexity by separating the insert and update operations
 *
 * ### Large switch: Optimise upsertPrototype to find matches
 * Option 1: Using JOIN with a Derived Table
 * ```sql
 * SELECT e.*
 * FROM experiment_a1_records e
 * JOIN (
 *   SELECT 'value1a' AS unique_field_1, 'value2a' AS unique_field_2, 'value3a' AS unique_field_3
 *   UNION ALL
 *   SELECT 'value1b', 'value2b', 'value3b'
 *   UNION ALL
 *   SELECT 'value1c', 'value2c', 'value3c'
 *   -- Add more pairings as needed...
 * ) AS pairings
 * ON e.unique_field_1 = pairings.unique_field_1
 *    AND e.unique_field_2 = pairings.unique_field_2
 *    AND e.unique_field_3 = pairings.unique_field_3;
 * ```
 *
 * When to Use Which:
 * - Small: If you have a relatively small number of pairings, using the IN operator with
 *          tuples is a simple and effective approach.
 * - Large: If you have many pairings, using a derived table and performing a JOIN is often more efficient,
 *          especially when the list is large. You can also insert the pairings into a temporary table and
 *          join with it for better maintainability and performance when dealing with larger datasets.
 */
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

    /**
     * Upsert prototype
     *
     *
     * @return int|bool Returns the number of records inserted or updated
     */
    public static function upsertPrototype(array $values, array $uniqueFields, ?array $updateFields = null): array
    {
        $chunkSize = 1000;
        $chunks = array_chunk($values, $chunkSize);

        $results = [
            'skipped' => 0,
            'updated' => 0,
            'inserted' => 0,
        ];
        foreach ($chunks as $index => $chunk) {
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

        // Find matching records based on unique fields
        $uniqueFieldsIn = [];
        foreach ($values as $value) {
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
        }

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
            $uniquesHash = md5(implode('|', array_map(fn ($field) => $value[$field], $uniqueFields)));
            $updatesHash = md5(implode('|', array_map(fn ($field) => $value[$field], $updateFields)));

            if (isset($lookupValues[$uniquesHash])) {
                if ($lookupValues[$uniquesHash]['update_values_hash'] === $updatesHash) {
                    $matchingCount++;

                    continue;
                }

                $updates[] = array_merge($value, [
                    'id' => $lookupValues[$uniquesHash]['id'],
                    'updated_at' => now(),
                ]);

                continue;
            }

            $inserts[] = array_merge($value, [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $insertsCount = count($inserts);
        if ($insertsCount > 0) {
            self::insert($inserts);
        }

        $updatesCount = count($updates);
        if ($updatesCount > 0) {
            self::upsert($updates, ['id'], $updateFields);
        }

        return [
            'skipped' => $matchingCount,
            'updated' => $updatesCount,
            'inserted' => $insertsCount,
        ];
    }
}
