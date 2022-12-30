<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use JsonException;

class GainsService
{
    public function __construct(
        protected KataService $kataService
    ) {
    }

    public function getGains(
        ?string $challenge,
        ?string $method
    ): Collection {
        $gains = collect();

        /** @var string $challengeName */
        foreach ($this->kataService->getChallenges() as $challengeName) {
            if (! is_null($challenge) && $challengeName !== $challenge) {
                continue;
            }

            /** @var string $methodName */
            foreach ($this->kataService->getChallengeMethods($challengeName) as $methodName) {
                if (! is_null($method) && $methodName !== $method) {
                    continue;
                }

                $gains->push($this->getChallengeMethodGains($challengeName, $methodName));
            }
        }

        return $gains;
    }

    public function getChallengeMethodGains(
        string $challenge,
        string $method
    ): array {
        $errors = [];
        $suggestions = [];
        $path = sprintf(
            'gains/%s-%s.json',
            $challenge,
            $method
        );
        $gains = Storage::disk('public')->get($path);

        if (is_null($gains)) {
            $errors[] = sprintf(
                'Gains file not found (%s)',
                asset(sprintf('storage/%s', $path)),
            );
            $suggestions[] = 'Run the following command `npm run benchmark:kata`';
        }

        if (! is_null($gains)) {
            try {
                $gains = json_decode($gains, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                $errors[] = sprintf(
                    'Unable to decode file. %s (%s)',
                    $exception->getMessage(),
                    asset(sprintf('storage/%s', $path)),
                );
                $suggestions[] = 'Run the following command `npm run benchmark:kata`';
            }
        }

        return [
            'success' => empty($errors),
            'gains' => $gains,
            'errors' => $errors,
            'suggestions' => $suggestions,
        ];
    }
}
