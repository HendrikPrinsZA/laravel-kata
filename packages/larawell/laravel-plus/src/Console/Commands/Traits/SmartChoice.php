<?php

namespace Larawell\LaravelPlus\Console\Commands\Traits;

use Illuminate\Support\Facades\Cache;

trait SmartChoice
{
    public function smartChoice(
        string $question,
        array $choices,
        array $default = [],
    ): array {
        $key = sprintf('smart-command:%s', md5(json_encode(func_get_args())));
        if (Cache::has($key)) {
            $default = Cache::get($key);
        }

        $newChoices = [];
        foreach ($choices as $choiceKey => $choiceValue) {
            $newChoices[sprintf('#%s', $choiceKey)] = $choiceValue;
        }

        $choices = $this->_multiChoice($question, $newChoices, $default);

        Cache::set($key, $choices);

        $newChoices = [];
        foreach ($choices as $choiceKey => $choiceValue) {
            $newChoices[substr($choiceKey, 1)] = $choiceValue;
        }

        return $newChoices;
    }

    private function _multiChoice(
        string $question,
        array $choices,
        array $chosen
    ): array {
        $displayChoices = [];
        foreach ($choices as $choiceKey => $choiceTitle) {
            $displayChoices[$choiceKey] = in_array($choiceKey, array_keys($chosen))
                ? sprintf('<info>%s</info>', $choiceTitle)
                : sprintf('<comment>%s</comment>', $choiceTitle);
        }

        $displayChoices = array_merge($displayChoices, [
            '' => '- - - - - - - - - -',
            't' => 'Toggle all',
            'n' => 'Next',
        ]);

        $displayQuestion = empty($chosen)
            ? $question
            : sprintf('%s (%d)', $question, count($chosen));

        /** @var \Illuminate\Console\Command $this */
        $choice = $this->choice($displayQuestion, $displayChoices);
        if (empty($choice)) {
            $this->warn('Nothing selected');

            return $this->_multiChoice($question, $choices, $chosen);
        }

        if ($choice === 't') {
            $chosen = count($chosen) === count($choices) ? [] : $choices;

            return $this->_multiChoice($question, $choices, $chosen);
        }

        if ($choice === 'n') {
            return $chosen;
        }

        if (isset($chosen[$choice])) {
            unset($chosen[$choice]);
        } else {
            $chosen[$choice] = $choices[$choice];
        }

        return $this->_multiChoice($question, $choices, $chosen);
    }
}
