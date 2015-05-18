# FastCGI Daemon

![GitHub Issues](https://img.shields.io/github/issues/PHPFastCGI/FastCGIDaemon.svg)
![GitHub Stars](https://img.shields.io/github/stars/PHPFastCGI/FastCGIDaemon.svg)
![GitHub License](https://img.shields.io/badge/license-GPLv2-blue.svg)
[![Build Status](https://travis-ci.org/PHPFastCGI/FastCGIDaemon.svg?branch=reader-optimisation)](https://travis-ci.org/PHPFastCGI/FastCGIDaemon)
[![Coverage Status](https://coveralls.io/repos/PHPFastCGI/FastCGIDaemon/badge.svg?branch=master)](https://coveralls.io/r/PHPFastCGI/FastCGIDaemon?branch=master)

A FastCGI daemon written in PHP.

## Introduction

Using this daemon, applications can stay alive between HTTP requests whilst operating behind the protection of a FastCGI enabled web server.

The daemon requires a kernel to be defined that accepts requests and returns responses. Requests objects mimic the PHP superglobals and response objects must provide the status line, header lines and a message body.

The [Speedfony Bundle](https://github.com/PHPFastCGI/SpeedfonyBundle) integrates this daemon with the symfony2 framework.

## Current Status

This daemon is currently in early development stages and not considered stable. A
stable release is expected by September 2015.

Contributions and suggestions are welcome.
