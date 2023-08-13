<?php

use Larawell\Inspectation\Inspectation;

if (! function_exists('inspect')) {
    /**
     * Creates a new inspectation.
     *
     * @template TValue
     *
     * @param  TValue|null  $value
     * @return Inspectation<TValue|null>
     */
    function inspect(mixed $value = null): Inspectation
    {
        return new Inspectation($value);
    }
}
