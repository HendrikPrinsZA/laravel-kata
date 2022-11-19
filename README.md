<p align="center">
  <a href="https://www.youtube.com/watch?v=r_8Rw16uscg" target="_blank">
    <img src="./public/laravel-kata-image.png" width="100%" alt="Replace with Laravel Kata logo">
  </a>
</p>

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

## About Laravel Kata
The greatest collection of bad code to be used as challenges to training.

### Concepts
- Learn the fundamentals
- Expose common mistakes
- Playground for theorycrafting

## Katas - [Introduction video](https://www.youtube.com/watch?v=r_8Rw16uscg)
Katas are code challenges focused on improving skill and technique. Some train programming fundamentals, while others focus on complex problem solving. Some are puzzles meant to test your creative problem solving, while others are based on real world coding scenarios.

The term was first coined by Dave Thomas, co-author of the book The Pragmatic Programmer as an acknowledgment to the Japanese concept of kata in the martial arts. Dave's version of the concept defines a code kata as an exercise in programming which helps a programmer sharpen their skills through practice and repetition. - [Codewars](https://docs.codewars.com/concepts/kata/)

### Scope 
- Sample (done)
- Native PHP (draft)
- Laravel Eloquent (draft)
- Native MySQL (pending)
- Native Redis (pending)

## Sample challenge
Sample challenge to calculate the value of `pi`.

### Baseline
```php
public function calculatePi(int $limit): float
{
  $denominator = 1;
  $sum = 0;
  for ($i = 0; $i < 100000; $i++) {
    $sum = ($i % 2 === 0)
      ? $sum + (4 / $denominator)
      : $sum - (4 / $denominator);
    $denominator += 2;
  }
  return round($sum, 2);
}
```

### Record
```php
public function calculatePi(int $limit): float
{
  return round(M_PI, 2);
}
```

### Report
```text
+-------------+------------------------------------------+--------------------------------------+
|             | Before                                   | Record                               |
+-------------+------------------------------------------+--------------------------------------+
| Code        | public function calculatePi(): float     | public function calculatePi(): float |
|             | {                                        | {                                    |
|             |     $denominator = 1;                    |     return round(M_PI, 2);           |
|             |     $sum = 0;                            | }                                    |
|             |     for ($i = 0; $i < 100000; $i++) {    |                                      |
|             |         $sum = ($i % 2 === 0)            |                                      |
|             |             ? $sum + (4 / $denominator)  |                                      |
|             |             : $sum - (4 / $denominator); |                                      |
|             |         $denominator += 2;               |                                      |
|             |     }                                    |                                      |
|             |     return round($sum, 2);               |                                      |
|             | }                                        |                                      |
|             |                                          |                                      |
| Outputs md5 | 60b3da123ce4f982e362d1fd843ecb0d         | 60b3da123ce4f982e362d1fd843ecb0d     |
| line_count  | 14                                       | 3                                    |
| violations  | 0                                        | 0                                    |
| duration    | 15933.131217957                          | 204.85305786133                      |
| iterations  | 189                                      | 10480                                |
| ----------- | ---------------------------------------- | ------------------------------------ |
| score       | 0.97684952701574                         | -0.094345035495324                   |
+-------------+------------------------------------------+--------------------------------------+
```

### Score breakdown
```text
+------------+-------------------+-----------------+-----------------+------------------+--------------------+------------+
| Field      | Report            | Stats (Before)  | Stats (Record)  | Score (Before)   | Score (Record)     | Field      |
+------------+-------------------+-----------------+-----------------+------------------+--------------------+------------+
| line_count | 3 (14)            | 14              | 3               | 0.85714285714286 | 0.33333333333333   | line_count |
| violations | 0 (0)             | 0               | 0               | 0.999999998      | 0.999999998        | violations |
| duration   | 204.85 (15933.13) | 15933.131217957 | 204.85305786133 | 0.9827432488429  | -0.34220149531666  | duration   |
| iterations | 10480 (189)       | 189             | 10480           | 0.98168427173176 | -0.015602287043318 | iterations |
| score      | -0.09 (0.98)      |                 |                 | 0.97684952701574 | -0.094345035495324 | score      |
+------------+-------------------+-----------------+-----------------+------------------+--------------------+------------+
```
