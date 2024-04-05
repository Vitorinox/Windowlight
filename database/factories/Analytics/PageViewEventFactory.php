<?php

namespace Database\Factories\Analytics;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Analytics\PageViewEvent>
 */
class PageViewEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'page' => $this->getPage(),
            'referrer' => $this->getReferrer(),
            'user_agent' => $this->getUserAgent(),
            'anonymous_id' => $this->getAnonymousId(),
        ];
    }

    public function getPage(): string
    {
        return $this->faker->url;
    }

    public function getReferrer(): string
    {
        return $this->faker->url;
    }

    public function getUserAgent(): string
    {
        return $this->faker->userAgent;
    }

    public function getAnonymousId(): string
    {
        $chanceOfBeingAnonymous = 60;
        $uniqueUsersCount = 10;

        // We factor in the unique user count, as the randomization affects the distribution
        $shouldBeAnonymous = $this->faker->boolean(($chanceOfBeingAnonymous - ($uniqueUsersCount * 3)) - 7.5);

        if ($shouldBeAnonymous) {
            return $this->faker->sha1;
        } else {
            // Return a subset of "known" users
            return substr(hash('sha256', rand(1, $uniqueUsersCount)), 0, 40);
        }
    }

    public function thisYear(): static
    {
        return $this->withInterval('-1 year');
    }

    public function pastSixMonths(): static
    {
        return $this->withInterval('-6 months');
    }

    public function pastThreeMonths(): static
    {
        return $this->withInterval('-3 months');
    }

    public function thisMonth(): static
    {
        return $this->withInterval('-1 month');
    }

    public function thisWeek(): static
    {
        return $this->withInterval('-1 week');
    }

    public function pastThreeDays(): static
    {
        return $this->withInterval('-3 days');
    }

    public function today(): static
    {
        return $this->withInterval('-1 day');
    }

    public function withInterval(string $interval): PageViewEventFactory
    {
        return $this->state(function () use ($interval): array {
            return [
                'created_at' => $this->faker->dateTimeBetween($interval, 'now')->format('Y-m-d H:i:s'),
            ];
        });
    }

    /** @return \Illuminate\Support\Collection<\Illuminate\Routing\Route> */
    protected function getRoutes(): Collection
    {
        static $routes = null;

        if ($routes === null) {
            // Get all registered routes
            $routes = Route::getRoutes();

            // Filter out only routes with a URL (excluding routes with parameters)
            $routes = collect($routes)->filter(function (\Illuminate\Routing\Route $route): bool {
                return $route->uri() !== '' && in_array('web', $route->middleware()) && !str_contains($route->uri(), '{')  && !str_starts_with($route->uri(), 'sanctum');
            });

            $routes = $routes->pluck('uri')->unique()->reverse()->values();

            // Add dynamic probability distribution to make earlier routes more likely to be selected
            // Each route is twice as likely to be selected as the next one
            $count = count($routes);
            $total_probability = 0;
            $probabilities = [];

            // Calculate probabilities based on the given distribution
            for ($i = 0; $i < $count; $i++) {
                $probability = pow(2, $i);
                $total_probability += $probability;
                $probabilities[] = $probability;
            }

            // Normalize probabilities so their sum equals 100
            $scaling_factor = 100 / $total_probability;
            $probabilities = array_map(function($prob) use ($scaling_factor) {
                return $prob * $scaling_factor;
            }, $probabilities);

            // Assign probabilities to strings
            $result = [];
            foreach ($routes as $index => $string) {
                $result[$string] = $probabilities[$index];
            }
            $result = array_reverse($result, true);

            $routes = collect($result);
        }
        return $routes;
    }
}
