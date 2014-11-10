Scrapper
====

Recursive video files downloader from server

# Installation

Through Composer, obviously:

```
composer require 2stech/scrapper
```

You can also use Scrapper without using Composer by registering an autoloader function:

```php
spl_autoload_register(function($class) {
    $prefix = 'Scrapper\\';

    if (stripos($class, $prefix) === false) {
        return;
    }

    $class = substr($class, strlen($prefix));
    $location = __DIR__ . 'src/Scrapper/' . str_replace('\\', '/', $class) . '.php';

    if (is_file($location)) {
        require_once($location);
    }
});
```