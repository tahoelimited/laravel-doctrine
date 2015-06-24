# Doctrine 2 for Laravel 5

A **forked implementation of [laravel-doctrine](https://github.com/mitchellvanw/laravel-doctrine)** that melts with Laravel 5.

## Documentation

As this is a **forked version** [the documentation](https://github.com/mitchellvanw/laravel-doctrine) still applies to most of the package. Please read [the original documentation](https://github.com/mitchellvanw/laravel-doctrine/wiki) and [README](https://github.com/mitchellvanw/laravel-doctrine) before using this fork.

## Forked Changes, Improvements, and Functionality

1. [What's New?](#whats-new)
2. [Installation](#installation)
3. [Using different metadata drivers](#using-different-metadata-drivers)
4. [Using multiple entity managers](#using-multiple-entity-managers)
5. [Using DoctrineExtensions](#using-doctrineextensions)
5. [New Doctrine Configuration Reference](#new-doctrine-configuration-reference)
6. [Issues and Contributing](#issues-and-contributing)


## What's New?

**Fixes for Laravel 5 support**

* [Fixes for native auth functionality](https://github.com/mitchellvanw/laravel-doctrine/pull/100)
* [Loading correct contracts for `UserProvider`](https://github.com/mitchellvanw/laravel-doctrine/pull/102) (and [here](https://github.com/mitchellvanw/laravel-doctrine/pull/117) and [here](https://github.com/mitchellvanw/laravel-doctrine/pull/106))
* [Fixed service provider for l5 compatibility](https://github.com/mitchellvanw/laravel-doctrine/pull/113)
* [Missing use statement](https://github.com/mitchellvanw/laravel-doctrine/pull/110)

**New Functionality**

* [Support for multiple entity managers](https://github.com/mitchellvanw/laravel-doctrine/pull/55) so you can use different db connections (thanks [**npmarrin!**](https://github.com/npmarrin))
* [Support for standard and simple drivers (XML, YAML, or annotations)](https://github.com/FoxxMD/laravel-doctrine/pull/3)   (thanks [**evopix!**](https://github.com/evopix))
* [Migrations and mapping conversion console commands](https://github.com/FoxxMD/laravel-doctrine/pull/4)  (thanks [**evopix!**](https://github.com/evopix))
* Prefixes for sqlite config mapping
* Added cache:clear artisan commands
* **Backwards compatibility with all current doctrine configs using annotations**

## Installation

Begin by installing the package through Composer. Edit your project's `composer.json` to require `mitchellvanw/laravel-doctrine`.

```php
"require": {
    "mitchellvanw/laravel-doctrine": "dev-l5",
    "doctrine/orm": "2.5.*@dev"
},
  "repositories": [
    {
      "type": "git",
      "url": "https://github.com/FoxxMD/laravel-doctrine.git"
    }
  ]
```

Next use Composer to update your project from the the Terminal:

```php
php composer.phar update
```

**Caveats**

At the moment Doctrine\ORM version 2.5 is still in beta. As a result the composer install may require you to change
the `minimum-stability` in your `composer.json` to `dev`.

If you don't want to affect the stability of the rest of the packages, you can add the following property in your `composer.json`:

`"prefer-stable": true`

Once the package has been installed you'll need to add the service provider. Open your `app/config/app.php` configuration file, and add a new item to the `providers` array.

```php
'Mitch\LaravelDoctrine\LaravelDoctrineServiceProvider'
```

After This you'll need to add the facade. Open your `app/config/app.php` configuration file, and add a new item to the `aliases` array.

```php
'EntityManager' => 'Mitch\LaravelDoctrine\EntityManagerFacade',
'RegistryManager' => 'Mitch\LaravelDoctrine\RegistryManagerFacade'
```

It's recommended to publish the package configuration.

```php
php artisan config:publish mitchellvanw/laravel-doctrine --path=vendor/mitchellvanw/laravel-doctrine/config
```

##Using different metadata drivers

Doctrine provides [several drivers](https://doctrine-orm.readthedocs.org/en/latest/reference/metadata-drivers.html) that can be used to map table information to entity classes. For more information see [sections 19, 20, and 21 of the doctrine reference guide](https://doctrine-orm.readthedocs.org/en/latest/index.html#reference-guide).

**A default doctrine config will use the annotation driver. This is the way laravel-doctrine works out of the box. If this is all you need you can continue to use the documentation provided by [laravel-doctrine's wiki](https://github.com/mitchellvanw/laravel-doctrine/wiki/Doctrine-Configuration).**

To use a different driver edit the `metadata` property of the entity manager you want to use the driver with (in `doctrine.config`)

    'entity_managers' => [
        'default' => [
            ...
            'metadata' => [
                'simple' => false,
                'driver' => 'yaml', //xml or yaml or annotation (ANNOTATION IS DEFAULT)
                'paths' => [
                    base_path('app/Models/mappings') //all base paths to mapping directories go here
                ],
                'extension' => '.dcm.yml' //extension for mapping files if not using simple driver
            ],
        ],
      ]

Refer to the doctrine reference guide on how to set up each driver.

##Using multiple entity managers


If you use the regular `EntityManager` facade you will receive the `default` EM defined in your doctrine config. 
To use multiple entity managers
* Use the `RegistryManager` facade or
* Inject `ManagerRegistry` into your controller

**Using the facade**

    use RegistryManager;
    public function __construct()
    {
        parent::__construct();
        $this->_em = RegistryManager::getManager('tracking'); //gets 'tracking' EM
        $this->_em = RegistryManager::getManager(); //gets 'default' EM

        $this->inventoryRepo = $this->_em->getRepository('app\Models\Inventory');
    }

**Using DI**

    public function __construct(ManagerRegistry $reg)
    {
        parent::__construct();
        $this->_em = $reg->getManager('tracking'); //gets 'tracking' EM
        $this->_em = $reg->getManager(); //gets 'default' EM

        $this->inventoryRepo = $this->_em->getRepository('app\Models\Inventory');
    }
    
## Using DoctrineExtensions

[DoctrineExtensions](https://github.com/beberlei/DoctrineExtensions) is a set of extensions to Doctrine 2 that add support for additional query functions available in MySQL and Oracle. To learn more about what additional functionality is added visit [the package's github page](https://github.com/beberlei/DoctrineExtensions).

To use DoctrineExtensions install the package with composer:

    composer require beberlei/DoctrineExtensions
    
Configuring extensions is easy and follows the pattern used by [DoctrineExtensions in its own config](https://github.com/beberlei/DoctrineExtensions/blob/master/config/mysql.yml). 
In your doctrine config simply add a `dql` array to every entity manager you want extensions accessible from or add the array to the top-level of your configuration to have it applied to all entity managers.

    'dql' => [
        'numeric_functions' => [
            'ROUND' => 'DoctrineExtensions\Query\Mysql\Round',
            'POWER' => 'DoctrineExtensions\Query\Mysql\Power'
            ...
        ],
        'string_functions' => [
            'REPLACE' => 'DoctrineExtensions\Query\Mysql\Replace'
            ...
        ],
        'datetime_functions' => [
            'DATE' => 'DoctrineExtensions\Query\Mysql\Date'
            ...
        ]
    ]

That's it! Use DoctrineExtensions as you normally would.

## New Doctrine Configuration Reference

A complete sample of doctrine configuration taking advantage of all new functionality, with comments.

    return [
        'default_connection' => 'default',
        'entity_managers' => [
            'default' => [ //MUST have an entity_managers entry for 'default'
                'connection' => 'rdsConnection',
                'cache_provider' => null,
                'repository' => 'Doctrine\ORM\EntityRepository',
                'logger' => null,
                'metadata' => [
                    'simple' => false,
                    'driver' => 'yaml', //xml or yaml or annotation (ANNOTATION IS DEFAULT)
                    'paths' => [
                        base_path('app/Models/mappings') //all base paths to mapping directories go here
                    ],
                    'extension' => '.dcm.yml' //extension for mapping files if not using simple driver
                ],
                //Only usable if DoctrineExtensions is installed
                /*'dql' => [
                    'numeric_functions' => [
                        'ROUND' => 'DoctrineExtensions\Query\Mysql\Round',
                        'POWER' => 'DoctrineExtensions\Query\Mysql\Power'
                        ...
                    ],
                    'string_functions' => [
                        'REPLACE' => 'DoctrineExtensions\Query\Mysql\Replace'
                        ...
                    ],
                    'datetime_functions' => [
                        'DATE' => 'DoctrineExtensions\Query\Mysql\Date'
                        ...
                    ]
                ]*/
            ],
            'tracking' => [
                'connection' => 'trackingConnection',
                'cache_provider' => null,
                'repository' => 'Doctrine\ORM\EntityRepository',
                'simple_annotations' => false,
                'logger' => null,
                'metadata' => [
                    'simple' => false,
                    'driver' => 'annotation'
                    //paths is not necessary for annotation
                ],
            ],
        ],
        'proxy' => [
            'auto_generate' => true, //create proxy files automatically (turn off for production)
            'directory' => base_path('storage/proxies'), //store them outside of default directory
            'namespace' => null
        ],
        //'cache_provider' => 'apc',
        //'logger' => new \Doctrine\DBAL\Logging\EchoSQLLogger()
        //'dql' => ...
    ];


#Issues and Contributing

## Issues?

If you have issues **related to changes made in this forked version** please open an issue **[on this repository](https://github.com/FoxxMD/laravel-doctrine/issues)**.

If your issue is general or related to functionality that exists in the original repo [please direct your questions there](https://github.com/mitchellvanw/laravel-doctrine/issues).

## Contributing and Fork Status

This fork **is not an official implementation of laravel-doctrine for Laravel 5** so I cannot gaurantee that it will continue to work or or be maintained in the future as Laravel changes, HOWEVER I use it on a daily basis with production code and so will keep it up to date as long as it is relevant for me.

I am happy to accept PRs and any other contributions from the community and will respond in a timely manner. I am open to accepting collaborators as well.

## License

This package is licensed under the [MIT license](https://github.com/mitchellvanw/laravel-doctrine/blob/master/LICENSE).
