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

The [Speedfony Bundle](https://github.com/PHPFastCGI/SpeedfonyBundle) integrates this daemon with the symfony2 framework.
The [Slim Adapter](https://github.com/PHPFastCGI/SlimAdapter) integrates this daemon with the Slim v3 framework.
The [Silex Adapter](https://github.com/PHPFastCGI/SilexAdapter) integrates this daemon with the Silex framework.

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

If you wish to configure your FastCGI application to work with the apache web server, you can use the apache FastCGI module to process manage your application.

This can be done by creating a FCGI script that launches your application and inserting a FastCgiServer directive into your virtual host configuration.

```sh
#!/bin/bash
php /path/to/command.php run
```

```
FastCgiServer /path/to/web/root/script.fcgi
```

By default, the daemon will listen on FCGI_LISTENSOCK_FILENO, but it can also be configured to listen on a TCP address. For example:

```sh
php /path/to/command.php run --port=5000 --host=localhost
```

If you are using a web server such as NGINX, you will need to use a process manager to monitor and run your application.
