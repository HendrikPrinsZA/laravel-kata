<?php

namespace Larawell\Inspectation;

use Pest\Arch\PendingArchExpectation;
use Pest\Expectation;
use Pest\Expectations\EachExpectation;
use Pest\Expectations\HigherOrderExpectation;
use Pest\Expectations\OppositeExpectation;
use Pest\Mixins\Expectation as MixinsExpectation;

/**
 * @internal
 *
 * @template TValue
 *
 * @property OppositeExpectation $not Creates the opposite expectation.
 * @property EachExpectation $each Creates an expectation on each element on the traversable value.
 * @property PendingArchExpectation $classes
 * @property PendingArchExpectation $traits
 * @property PendingArchExpectation $interfaces
 * @property PendingArchExpectation $enums
 *
 * @mixin MixinsExpectation<TValue>
 * @mixin PendingArchExpectation
 */
final class Inspectation
{
    public Expectation $expectation;

    /**
     * Creates a new inspectation.
     *
     * @param  TValue  $value
     */
    public function __construct(public mixed $value)
    {
        $this->expectation = expect($this->value);
    }

    /**
     * Dynamically calls methods on the class or creates a new higher order expectation.
     *
     * @param  array<int, mixed>  $parameters
     * @return Expectation<TValue>|HigherOrderExpectation<Expectation<TValue>, TValue>
     */
    public function __call(string $method, array $parameters): Expectation|HigherOrderExpectation|PendingArchExpectation
    {
        return $this->expectation->__call($method, $parameters);
    }

    /**
     * Dynamically calls methods on the class without any arguments or creates a new higher order expectation.
     *
     * @return Expectation<TValue>|OppositeExpectation<TValue>|EachExpectation<TValue>|HigherOrderExpectation<Expectation<TValue>, TValue|null>|TValue
     */
    public function __get(string $name)
    {
        return $this->expectation->__get($name);
    }
}
