# FastCGI Daemon

[![Latest Stable Version](https://poser.pugx.org/phpfastcgi/fastcgi-daemon/v/stable)](https://packagist.org/packages/phpfastcgi/fastcgi-daemon)
[![Build Status](https://travis-ci.org/PHPFastCGI/FastCGIDaemon.svg?branch=master)](https://travis-ci.org/PHPFastCGI/FastCGIDaemon)
[![Coverage Status](https://coveralls.io/repos/PHPFastCGI/FastCGIDaemon/badge.svg?branch=master)](https://coveralls.io/r/PHPFastCGI/FastCGIDaemon?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/PHPFastCGI/FastCGIDaemon/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/PHPFastCGI/FastCGIDaemon/?branch=master)
[![Total Downloads](https://poser.pugx.org/phpfastcgi/fastcgi-daemon/downloads)](https://packagist.org/packages/phpfastcgi/fastcgi-daemon)

A FastCGI daemon written in PHP. Visit the [project website](http://phpfastcgi.github.io/) for more documentation and guides.

Check out the [project performance benchmarks](http://phpfastcgi.github.io/general/2015/08/24/phpfastcgi-benchmarks-symfony-silex-slim.html) to see how we got a "Hello, World!" Slim application to handle 5,500 rq/s.

## Introduction

Using this daemon, applications can stay alive between HTTP requests whilst operating behind the protection of a FastCGI enabled web server.

The daemon requires a handler to be defined that accepts request objects and returns PSR-7 or HttpFoundation responses.

The [Speedfony Bundle](https://github.com/PHPFastCGI/SpeedfonyBundle) integrates this daemon with the Symfony2 framework.
The [Slim Adapter](https://github.com/PHPFastCGI/SlimAdapter) integrates this daemon with the Slim v3 framework.
The [Silex Adapter](https://github.com/PHPFastCGI/SilexAdapter) integrates this daemon with the Silex framework.

There is also an un-official [ZF2 Adapter](https://github.com/Okeanrst/FastCGIZF2Adapter) that integrates this daemon with Zend Framework 2.

## Current Status

This project is currently in early stages of development and not considered stable. Importantly, this library currently lacks support for uploaded files.

Contributions and suggestions are welcome.

## Usage

Below is an example of a simple 'Hello, World!' FastCGI application in PHP.

```php
<?php // command.php

// Include the composer autoloader
require_once dirname(__FILE__) . '/../vendor/autoload.php';

use PHPFastCGI\FastCGIDaemon\ApplicationFactory;
use PHPFastCGI\FastCGIDaemon\Http\RequestInterface;
use Zend\Diactoros\Response\HtmlResponse;

// A simple kernel. This is the core of your application
$kernel = function (RequestInterface $request) {
    // $request->getServerRequest()         returns PSR-7 server request object
    // $request->getHttpFoundationRequest() returns HTTP foundation request object
    return new HtmlResponse('<h1>Hello, World!</h1>');
};

// Create your Symfony console application using the factory
$application = (new ApplicationFactory)->createApplication($kernel);

// Run the Symfony console application
$application->run();
```

## Server Configuration

### NGINX

With NGINX, you need to use a process manager such as [supervisord](http://supervisord.org/) to manage instances of your application. Have a look at [AstroSplash](http://astrosplash.com/) for an [example supervisord configuration](https://github.com/AndrewCarterUK/AstroSplash/blob/master/supervisord.conf).

Below is an example of the modification that you would make to the [Symfony NGINX configuration](https://www.nginx.com/resources/wiki/start/topics/recipes/symfony/). The core principle is to replace the PHP-FPM reference with one to a cluster of workers.

```nginx
# This shows the modifications that you would make to the Symfony NGINX configuration
# https://www.nginx.com/resources/wiki/start/topics/recipes/symfony/

upstream workers {
    server localhost:5000;
    server localhost:5001;
    server localhost:5002;
    server localhost:5003;
}

server {
    # ...

    location ~ ^/app\.php(/|$) {
        # ...
        fastcgi_pass workers;
        # ...
    }

    # ...
}
```

### Apache

If you wish to configure your FastCGI application to work with the apache web server, you can use the apache FastCGI module to process manage your application.

This can be done by creating a FCGI script that launches your application and inserting a FastCgiServer directive into your virtual host configuration.

Here is an example `script.fcgi`:

```sh
#!/bin/bash
php /path/to/application.php run
```

Or with Symfony:

```sh
#!/bin/bash
php /path/to/bin/console speedfony:run --env=prod
```

In your configuration, you can use the [FastCgiServer](https://web.archive.org/web/20150913190020/http://www.fastcgi.com/mod_fastcgi/docs/mod_fastcgi.html#FastCgiServer) directive to inform Apache of your application.

```
FastCgiServer /path/to/script.fcgi
```

By default, the daemon will listen on FCGI_LISTENSOCK_FILENO, but it can also be configured to listen on a TCP address. For example:


```sh
#!/bin/bash
php /path/to/application.php run --port=5000 --host=localhost
```

Or with Symfony:

```sh
#!/bin/bash
php /path/to/bin/console speedfony:run --env=prod --port=5000 --host=localhost
```
