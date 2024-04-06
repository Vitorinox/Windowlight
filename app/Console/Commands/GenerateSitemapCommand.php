<?php

namespace App\Console\Commands;

use App\Providers\HydeServiceProvider;
use Hyde\Foundation\Facades\Routes;
use Hyde\Framework\Actions\PostBuildTasks\GenerateRssFeed;
use Hyde\Framework\Actions\PostBuildTasks\GenerateSitemap;
use Hyde\Pages\InMemoryPage;
use Hyde\Support\Models\Route as HydeRoute;
use Illuminate\Console\Command;
use Illuminate\Routing\Route;

/**
 * @see \Hyde\Console\Commands\BuildRssFeedCommand
 */
class GenerateSitemapCommand extends Command
{
    /** @var string */
    protected $signature = 'build:sitemap';

    /** @var string */
    protected $description = 'Generate the sitemap';

    public function handle(): int
    {
        app()->register(HydeServiceProvider::class);

        // Mix in the Laravel routes into the Hyde routes
        $routes = [
            'home',
            'about',
            'examples',
            'analytics',
            'analytics.raw',
            'analytics.json',
        ];

        foreach ($routes as $name) {
            $route = app('router')->getRoutes()->getByName($name);
            $hydeRoute = new HydeRoute(new InMemoryPage($route->uri));
            Routes::addRoute($hydeRoute);
        }

        (new GenerateSitemap())->run($this->output);

        $this->postProcess();

        return Command::SUCCESS;
    }

    protected function postProcess(): void
    {
        // Decode the sitemap.xml file
        $sitemap = simplexml_load_file(base_path('public/sitemap.xml'));

        // Put any urls with posts/ last in the sitemap
        $posts = $sitemap->xpath('//url[contains(loc, "posts/")]');
        foreach ($posts as $post) {
            $url = $post->loc;
            unset($post->loc);
            $post->addChild('loc', $url);
        }

        // Save the sitemap.xml file
        $sitemap->asXML(base_path('public/sitemap.xml'));
    }
}
