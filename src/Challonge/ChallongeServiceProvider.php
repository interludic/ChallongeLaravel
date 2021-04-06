<?php

namespace Interludic\Challonge;

use Illuminate\Support\ServiceProvider;

/**
 *
 * Challonge ServiceProvider
 *
 * @category   Laravel Challonge
 * @package    Interludic/Challonge
 * @copyright  Copyright (c) 2013 - 2017 Interludic (http://www.Interludic.com.au)
 * @author     Interludic <info@Interludic.com.au>
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt    LGPL
 */
class ChallongeServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */

    public function boot()
    {
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->bindChallonge();
    }

    /**
     * Bind Challonge class
     * @return void
     */
    protected function bindChallonge()
    {
        // Bind the Challonge class and inject its dependencies

        $this->app->singleton(Challonge::class, function ($app) {
            return new Challonge(CHALLONGE_KEY);
        });


        // $this->app->alias('challonge', Challonge::class);
    }


    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'challonge',
        ];
    }
}
