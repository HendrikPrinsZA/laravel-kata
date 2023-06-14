<?php

use App\Traits\HasExitHintsTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    Config::set('laravel-kata.show-hints', true);
    Config::set('laravel-kata.show-hints-extended', true);

    $this->instance = new class
    {
        use HasExitHintsTrait;

        protected $command;

        public function __construct()
        {
            $this->command = new class
            {
                public function info(string $message): void
                {
                }

                public function warn(string $message): void
                {
                }
            };
        }

        public function addHint(string $message): void
        {
            $this->addExitHint($message);
        }

        public function getHints(): Collection
        {
            return $this->getExitHints();
        }

        public function getRandomHint(): string
        {
            return $this->getRandomExitHint();
        }

        public function addViolations(array $violations): void
        {
            $this->addExitHintsFromViolations($violations);
        }
    };
});

it('can get hints', function () {
    $hint = sprintf('The hint at %s', now()->toDateTimeString());
    $this->instance->addHint($hint);
    expect($this->instance)
        ->getHints()
        ->toBeInstanceOf(Collection::class)
        ->toContain($hint);
});

it('can get random hint', function () {
    $hint = sprintf('The hint at %s', now()->toDateTimeString());
    $this->instance->addHint($hint);
    expect($this->instance)
        ->getRandomHint()->toBe($hint);
});

it('can add violations', function () {
    $violation = [
        'beginLine' => 11,
        'endLine' => 11,
        'package' => null,
        'function' => null,
        'class' => null,
        'method' => null,
        'description' => 'Avoid variables with short names like $a. Configured minimum length is 3.',
        'rule' => 'ShortVariable',
        'ruleSet' => 'Naming Rules',
        'externalInfoUrl' => 'https://phpmd.org/rules/naming.html#shortvariable',
        'priority' => 3,
    ];

    $this->instance->addViolations([$violation]);

    $expected = <<<'STR'
    ### Naming Rules (ShortVariable)
    Avoid variables with short names like $a. Configured minimum length is 3.

    https://phpmd.org/rules/naming.html#shortvariable

    (i) {"class":null,"method":null,"function":null,"beginLine":11,"endLine":11}
    STR;
    expect($this->instance)
        ->getRandomHint()->toBe($expected);
});
