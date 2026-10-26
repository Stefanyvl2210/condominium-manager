<?php

namespace App\Providers;

use App\Models\Condominium;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(Condominium::class, fn () => Condominium::resolveCurrent());
    }

    public function boot(): void
    {
        Model::automaticallyEagerLoadRelationships();
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
        Model::preventAccessingMissingAttributes(! $this->app->isProduction());
    }
}
