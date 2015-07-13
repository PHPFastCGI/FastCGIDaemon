# FastCGI Daemon

![GitHub Issues](https://img.shields.io/github/issues/PHPFastCGI/FastCGIDaemon.svg)
![GitHub Stars](https://img.shields.io/github/stars/PHPFastCGI/FastCGIDaemon.svg)
![GitHub License](https://img.shields.io/badge/license-GPLv2-blue.svg)
[![Build Status](https://travis-ci.org/PHPFastCGI/FastCGIDaemon.svg?branch=master)](https://travis-ci.org/PHPFastCGI/FastCGIDaemon)
[![Coverage Status](https://coveralls.io/repos/PHPFastCGI/FastCGIDaemon/badge.svg?branch=master)](https://coveralls.io/r/PHPFastCGI/FastCGIDaemon?branch=master)

A FastCGI daemon written in PHP.

## Introduction

Using this daemon, applications can stay alive between HTTP requests whilst operating behind the protection of a FastCGI enabled web server.

The daemon requires a handler to be defined that accepts PSR-7 requests and returns PSR-7 responses.

The [Speedfony Bundle](https://github.com/PHPFastCGI/SpeedfonyBundle) integrates this daemon with the symfony2 framework.
The [Slimmer package](https://github.com/PHPFastCGI/Slimmer) integrates this daemon with the Slim v3 framework.

## Usage

Below is an example of a simple 'Hello, World!' FastCGI application in PHP.

```php
<?php // command.php

// Include the composer autoloader
require_once dirname(__FILE__) . '/../vendor/autoload.php';

use PHPFastCGI\FastCGIDaemon\Command\DaemonRunCommand;
use PHPFastCGI\FastCGIDaemon\DaemonFactory;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Console\Application;
use Zend\Diactoros\Response\HtmlResponse;

// Create the dependencies for the DaemonRunCommand

// Dependency 1: The daemon factory
$daemonFactory = new DaemonFactory();

// Dependency 2: A simple kernel. This is the core of your application
$kernel = function (ServerRequestInterface $request) {
    return new HtmlResponse('<h1>Hello, World!</h1>');
};

// Create an instance of DaemonRunCommand using the daemon factory and the kernel
$command = new DaemonRunCommand('run', 'Run a FastCGI daemon', $daemonFactory, $kernel);

// Create a symfony console application and add the command
$consoleApplication = new Application();
$consoleApplication->add($command);

// Run the symfony console application
$consoleApplication->run();
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

If you are using a web server such as nginx, you will need to use a process manager to monitor and run your application.

## Current Status

This daemon is currently in early development stages and not considered stable. A stable release is expected by September 2015.

Contributions and suggestions are welcome.
