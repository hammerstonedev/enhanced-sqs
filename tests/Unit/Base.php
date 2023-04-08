<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\EnhancedSqs\Tests\Unit;

use Hammerstone\EnhancedSqs\Providers\EnhancedSqsServiceProvider;
use Orchestra\Testbench\TestCase;

abstract class Base extends TestCase
{
    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetup($app)
    {
    }

    protected function getPackageProviders($app)
    {
        return [
            EnhancedSqsServiceProvider::class,
        ];
    }
}
