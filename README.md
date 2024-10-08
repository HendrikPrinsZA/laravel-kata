<p>
  <a href="https://dl.circleci.com/status-badge/redirect/gh/HendrikPrinsZA/laravel-kata/tree/main">
    <img src="https://dl.circleci.com/status-badge/img/gh/HendrikPrinsZA/laravel-kata/tree/main.svg?style=shield">
  </a>
  <a href="https://wakatime.com/badge/user/69e63dcc-57e6-4f88-914d-47f7875e06e1/project/87ef3f09-e831-47e5-8f10-99255f5649a8">
    <img src="https://wakatime.com/badge/user/69e63dcc-57e6-4f88-914d-47f7875e06e1/project/87ef3f09-e831-47e5-8f10-99255f5649a8.svg" alt="wakatime">
  </a>
  <a href="https://codecov.io/github/HendrikPrinsZA/laravel-kata">
    <img src="https://codecov.io/github/HendrikPrinsZA/laravel-kata/branch/main/graph/badge.svg?token=LH2CA7W0AT"/> 
  </a>
</p>

# Laravel Kata
The greatest collection of the worst code.

### Concepts
- Practice the fundamentals
- Expose common mistakes
- Lab to theorycraft mechanics

### Dependencies
- Docker
- Composer
- NPM

## Getting started
```
npm i
npm run restart

./vendor/bin/sail kata:run
```

## Sample challenge
Calculate the value of pi.

### Solution A
```php
namespace App\Challenges\A;

class Sample
{
    public function calculatePi(): float
    {
        $denominator = 1;
        $sum = 0;

        for ($i = 0; $i < 100000; $i++) {
            $sum = ($i % 2 === 0)
                ? $sum + (4 / $denominator)
                : $sum - (4 / $denominator);

            $denominator += 2;
        }

        return $this->return(round($sum, 2));
    }
}
```

### Solution B
```php
namespace App\Challenges\B;

class Sample
{
    public function calculatePi(): float
    {
        return $this->return(round(M_PI, 2));
    }
}
```

### Report
<p align="center">
  <img src="./public/images/sample-report-min.jpg" width="100%" alt="Sample report">
</p>

## [What are katas?](https://www.youtube.com/watch?v=r_8Rw16uscg)
Katas are code challenges focused on improving skill and technique. Some train programming fundamentals, while others focus on complex problem solving. Some are puzzles meant to test your creative problem solving, while others are based on real world coding scenarios.

> The term was first coined by Dave Thomas, co-author of the book The Pragmatic Programmer as an acknowledgment to the Japanese concept of kata in the martial arts. Dave's version of the concept defines a code kata as an exercise in programming which helps a programmer sharpen their skills through practice and repetition. - [Codewars](https://docs.codewars.com/concepts/kata/)
