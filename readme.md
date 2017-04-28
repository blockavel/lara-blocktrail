# blockavel/lara-blocktrail

A Laravel package/facade for the blocktrail API PHP SDK.

This repository implements a simple Service Provider of the blocktrail client, and makes it easily accessible via a Facade in Laravel >= 5. 

See [@blocktrial/blocktrail-sdk-php](https://github.com/blocktrail/blocktrail-sdk-php) and the [Blocktrail PHP API docs](https://www.blocktrail.com/api/docs/lang/php) for more information about the PHP wrapper of the Block.io API and its interfaces.

## Requirements

Create an account at [blocktrail](https://www.blocktrail.com/dev/signup) and take note of your API keys under Developer > Settings > API Keys.

The Blocktrail SDK requires the 'mcrypt', 'gmp', and 'cURL' extensions for PHP as well as the 'bcmath' library. To enable these, please see:

-[mCrypt Installation Guide](http://php.net/manual/en/mcrypt.installation.php)

-[GMP Installation Guide](http://php.net/manual/en/gmp.installation.php)

-[cURL Installation Guide](http://php.net/manual/en/curl.installation.php)

-[bcmath Installation Guide](http://php.net/manual/en/book.bc.php)

## Installation using [Composer](https://getcomposer.org)

In your terminal application move to the root directory of your laravel project using the cd command and require the project as a dependency using composer.

composer require blockavel/lara-blocktrail

This will add the following lines to your composer.json and download the project and its dependencies to your projects ./vendor directory:

```javascript
// 

./composer.json
{
    "name": "blockavel/lara-blocktrail",
    "description": "A dummy project used to test the Laravel Blocktrail Facade.",

    // ...

    "require": {
        "php": ">=5.5.9",
        "laravel/framework": "5.2.*",
        "blockavel/lara-blocktrail": "1.0.*",
        // ...
    },

    //...
}
```

## Usage

In order to use the static interface we must customize the application configuration to tell the system where it can find the new service. Open the file config/app.php and add the following lines ([a], [b]):

```php

// config/app.php

return [

    // ...

    'providers' => [

        // ...

        /*
         * Package Service Providers...
         */
        Blockavel\LaraBlocktrail\LaraBlocktrailServiceProvider::class, // [a]

        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,

    ],

    // ...

    'aliases' => [

        'App' => Illuminate\Support\Facades\App::class,
        'Artisan' => Illuminate\Support\Facades\Artisan::class,

        // ...

        'LaraBlocktrail' => 'Blockavel\LaraBlocktrail\LaraBlocktrailFacade', // [b]
        'Hash' => Illuminate\Support\Facades\Hash::class,

        // ...
    ],

];


```

## Publish Vendor

lara-blocktrail requires a connection configuration. To get started, you'll need to publish all vendor assets by running:

php artisan vendor:publish

This will create a config/larablocktrail.php file in your app that you can modify to set your configuration. Make sure you check for changes compared to the original config file after an upgrade.

Now you should be able to use the facade within your application. Ex:

```php
namespace App;

use Illuminate\Database\Eloquent\Model;

class LaraBlocktrailTest extends Model
{
    public function test()
    {
        return \LaraBlocktrail::getClient();
    }
}

```
## Testing

Unit Tests are created with PHPunit and orchestra/testbench, they can be ran with ./vendor/bin/phpunit.

## Contributing

Find an area you can help with and do it. Open source is about collaboration and open participation. 
Try to make your code look like what already exists or better and submit a pull request. Also, if
you have any ideas on how to make the code better or on improving the scope and functionality please
contact any of the contributors.

## License

MIT License.