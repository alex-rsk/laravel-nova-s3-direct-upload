<?php


namespace Alexrsk\S3DirectUpload;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;

class ToolServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->booted(function () {
            $this->routes();
        });

        Nova::serving(function (ServingNova $event) {
            Nova::script('s3-direct-upload', __DIR__.'/../dist/js/tool.js');
            Nova::style('s3-direct-upload', __DIR__.'/../dist/css/tool.css');
        });

        $this->publishes([
            __DIR__ . '/config/s3_direct_upload.php' => config_path('s3_direct_upload.php'),
        ], 's3-uploader-config');
    }

    /**
     * Register the tool's routes.
     *
     * @return void
     */
    protected function routes()
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::middleware(['nova'])
                ->prefix('nova-vendor/s3-direct-upload')
                ->group(__DIR__.'/../routes/api.php');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

    }
}
