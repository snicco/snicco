<?php

declare(strict_types=1);

namespace Tests\EloquentBundle\integration;

use Illuminate\Support\Facades\DB;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\ConnectionInterface;
use Tests\Codeception\shared\FrameworkTestCase;
use Snicco\EloquentBundle\DatabaseServiceProvider;
use Illuminate\Database\ConnectionResolverInterface;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\Eloquent\Illuminate\MysqliConnection;
use Snicco\Component\Eloquent\Tests\fixtures\Model\Country;
use Snicco\Component\Eloquent\Illuminate\WPConnectionResolver;
use Illuminate\Contracts\Events\Dispatcher as IlluminateDispatcher;
use Snicco\Component\Eloquent\Tests\fixtures\Helper\WPDBTestHelpers;
use Snicco\Component\Eloquent\Tests\fixtures\Helper\WithTestTransactions;

class DatabaseServiceProviderTest extends FrameworkTestCase
{
    
    use WithTestTransactions;
    use WPDBTestHelpers;
    
    /**
     * @test
     */
    public function eloquent_is_booted()
    {
        $this->bootApp();
        
        $this->assertInstanceOf(
            WPConnectionResolver::class,
            $resolver = $this->app->resolve(ConnectionResolverInterface::class)
        );
        
        $this->assertSame($resolver, Model::getConnectionResolver());
        $this->assertInstanceOf(
            MysqliConnection::class,
            $connection = $this->app->resolve(ConnectionInterface::class)
        );
        
        $this->assertSame($connection, DB::connection());
    }
    
    /** @test */
    public function facades_can_be_disabled()
    {
        $this->withAddedConfig('database.enable_global_facades', false);
        $this->bootApp();
        
        $this->assertInstanceOf(
            WPConnectionResolver::class,
            $resolver = $this->app->resolve(ConnectionResolverInterface::class)
        );
        
        $this->assertSame($resolver, Model::getConnectionResolver());
        $this->assertInstanceOf(
            MysqliConnection::class,
            $connection = $this->app->resolve(ConnectionInterface::class)
        );
        
        $this->assertFalse(Container::getInstance()->has('db'));
    }
    
    /** @test */
    public function eloquent_events_are_used()
    {
        $this->bootApp();
        $this->assertInstanceOf(IlluminateDispatcher::class, $first = Model::getEventDispatcher());
        $this->assertInstanceOf(
            IlluminateDispatcher::class,
            $second = Container::getInstance()['events']
        );
        $this->assertSame($first, $second);
    }
    
    /** @test */
    public function eloquent_events_work()
    {
        $this->bootApp();
        
        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->app->resolve(EventDispatcher::class);
        $dispatcher->listen("eloquent.saving: ".Country::class, function (Country $country) {
            $country->name = 'spain';
        });
        
        $country = new Country();
        $country->name = 'germany';
        $country->continent = 'europe';
        $country->save();
        
        $country = $country->refresh();
        
        $this->assertSame('spain', $country->name);
    }
    
    /** @test */
    public function eloquent_events_work_with_mapped_events()
    {
        $this->bootApp();
        
        Country::observe(CountryObserver::class);
        
        $country = new Country();
        $country->name = 'germany';
        $country->continent = 'europe';
        $country->save();
        
        $GLOBALS['test']['deleted_country'] = '';
        
        $country->delete();
        
        $this->assertSame('germany', $GLOBALS['test']['deleted_country']);
    }
    
    /** @test */
    public function eloquent_observers_work()
    {
        $this->bootApp();
        
        Country::observe(CountryObserver::class);
    }
    
    protected function packageProviders() :array
    {
        return [
            DatabaseServiceProvider::class,
        ];
    }
    
}

class CountryObserver
{
    
    public function deleted(Country $country)
    {
        $GLOBALS['test']['deleted_country'] = $country->name;
    }
    
}