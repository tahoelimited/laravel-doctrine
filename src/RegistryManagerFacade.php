<?php namespace Mitch\LaravelDoctrine;

use Illuminate\Support\Facades\Facade;

class RegistryManagerFacade extends Facade {

    /**
     * Get the registered name of the component.
     * @return string
     */
    protected static function getFacadeAccessor() {
        return 'Doctrine\Common\Persistence\ManagerRegistry';
    }
} 
