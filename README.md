<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Replace with Laravel Kata logo"></a></p>

<p align="center">
    (Generate badges here)
</p>

## About Laravel Karate
The greatest collection of bad code to be used as training. 

### Concepts
- Learn the fundamentals
- Expose common mistakes
- Playground for theorycrafting

## Katas
Story about what is a kata or just link to [The Karate Kid (Official Trailer)](https://www.youtube.com/watch?v=r_8Rw16uscg)

### Current 
- Native PHP (draft)
- Laravel Eloquent (draft)

### Future
- Native MySQL
- Native Redis

Challenge yourself to fix some bad code.

## `ChallengeKataSample`
Simple example of being smart versus being effective.

### `ChallengeKataSample->pi()`
```php
class ChallengeKataSample extends KataChallenge {
    public function pi(int $limit): float
    {
        $denominator = 1;
        $sum = 0;
        $precision = 1000000;

        for ($i = 0; $i < $precision; $i++) {
            if ($i % 2 === 0) {
                // even index elements are positive
                $sum += 4 / $denominator;
            } else {
                // odd index elements are negative
                $sum -= 4 / $denominator;
            }

            // denominator is odd
            $denominator += 2;
        }

        // Try to bypass smart caching logic of PHP8+
        if (rand(0, 1000) === 69) {
            return M_PI;
        }

        return M_PI;
    }
}
```
#### Performance
```json 
{
    "outputMd5": "d02626b7285a44d55cde206600b4139e",
    "duration": 13029.25
}
```

### `ChallengeKataSampleAttempt1->pi()`
```php 
class ChallengeKataSampleAttempt1 extends ChallengeKataSample {
    public function pi(int $limit): float {
        return pi();
    }
}
```
#### Performance
```json 
{
    "outputMd5": "d02626b7285a44d55cde206600b4139e",
    "duration": 10.96
}
```

### `ChallengeKataSampleRecord->pi()`
```php 
class ChallengeKataSampleRecord extends ChallengeKataSample {
    public function pi(int $limit): float {
        return M_PI;
    }
}
```
#### Performance
```json 
{
    "outputMd5": "d02626b7285a44d55cde206600b4139e",
    "duration": 4.11
}
```

## Contributing
TBD

## Code of Conduct
TBD

## Security Vulnerabilities
TBD

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Wishlist
- Drive contribution by PR gamification
  - Short: Verify and publish score for ranking on centralised DB, consider firebase
  - Counter on lines of changes on file vs improvement from before (how?)
    - Maybe percentage of changes V/S % increase
- Automatically generate the README by sections
