<?php
/**
 * Project developer and primary maintainer: Mohammadreza Majidi (RM).
 * GitHub: https://github.com/MREZA-MJDi
 * See /AUTHORSHIP.md for attribution and /AGENTS.md for contributor guardrails.
 */


use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
