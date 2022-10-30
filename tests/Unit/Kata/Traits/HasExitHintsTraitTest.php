<?php

namespace Tests\Unit\Kata\Traits;

use App\Kata\Traits\HasExitHintsTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasExitHintsTraitTest extends TestCase
{
    use RefreshDatabase;
    use HasExitHintsTrait;

    public function test_is_true()
    {
        $this->assertTrue(true);
    }

    public function test_add_exit_hint()
    {
        $hint = sprintf(
            'Random message at: %s', now()->toDateTimeString()
        );
        $this->addExitHint($hint);
        $this->assertEquals(collect($hint), $this->getExitHints());
    }

    public function test_get_random_exit_hint()
    {
        $hints = collect();

        for ($iteration = 0; $iteration < 10; $iteration++) {
            $hints->push(sprintf(
                '%d. Random message at: %s', $iteration, now()->toDateTimeString()
            ));
        }

        $hints->each(fn ($hint) => $this->addExitHint($hint));
        $randomHint = $this->getRandomExitHint();
        $this->assertContains($randomHint, $hints);
    }
}
