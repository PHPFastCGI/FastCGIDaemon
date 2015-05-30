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

## Current Status

This daemon is currently in early development stages and not considered stable. A
stable release is expected by September 2015.

Contributions and suggestions are welcome.
