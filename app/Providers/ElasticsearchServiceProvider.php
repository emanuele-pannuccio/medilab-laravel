<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Client;

class ElasticsearchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(Client::class, function ($app) {
            $config = $app['config']->get('services.elasticsearch');
            $builder = ClientBuilder::create()
                ->setHosts([$config['host']])
                ->setHttpClientOptions([
                    'headers' => [
                        'Accept'       => 'application/json',
                        'Content-Type' => 'application/json'
                    ]
                ]);
            if (!empty($config['api_key'])) {
                $builder->setApiKey($config['api_key']);
            } 
            // 2. Altrimenti, se esistono username E password, usa Basic Auth.
            elseif (!empty($config['username']) && !empty($config['password'])) {
                $builder->setBasicAuthentication($config['username'], $config['password']);
            }

            return $builder->build();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
