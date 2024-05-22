<?php

require 'vendor/autoload.php';

use Basis\Nats\Client;
use Basis\Nats\Configuration;

function pretty ($var) {
  return gettype($var) . ' ' . json_encode(
    $var,
    JSON_UNESCAPED_SLASHES |       // Don't escape forward slashes. stripslashes() could be used afterwards instead
    JSON_UNESCAPED_UNICODE |       // Print unicode characters insteas of their encoding "€" vs "\u20ac"
    JSON_PRETTY_PRINT |            // Nice layout over several lines, human readable
    JSON_PARTIAL_OUTPUT_ON_ERROR | // Substitute whatever can not be printed
    JSON_INVALID_UTF8_SUBSTITUTE   // Convert invalid UTF-8 characters to \0xfffd (Unicode Character 'REPLACEMENT CHARACTER')
  );                               // Constants: https://www.php.net/manual/en/json.constants.php
}

// this is default options, you can override anyone
$configuration = new Configuration([
    'host' => 'localhost',
    'jwt' => null,
    'lang' => 'php',
    'pass' => null,
    'pedantic' => false,
    'port' => 4222,
    'reconnect' => true,
    'timeout' => 1,
    'token' => null,
    'user' => null,
    'nkey' => null,
    'verbose' => false,
    'version' => 'dev',
]);

// default delay mode is constant - first retry be in 1ms, second in 1ms, third in 1ms
$configuration->setDelay(0.001);

// linear delay mode - first retry be in 1ms, second in 2ms, third in 3ms, fourth in 4ms, etc...
$configuration->setDelay(0.001, Configuration::DELAY_LINEAR);

// exponential delay mode - first retry be in 10ms, second in 100ms, third in 1s, fourth if 10 seconds, etc...
$configuration->setDelay(0.01, Configuration::DELAY_EXPONENTIAL);


$client = new Client($configuration);

if ($client->ping()) {
	echo "pinged OK" . PHP_EOL;
} else {
	echo "pinged NOT ok" . PHP_EOL;
}

$client->subscribe('hello.request', function ($name) {
    return "Hello, " . $name;
});

// async interaction
$client->request('hello.request', 'Nekufa1', function ($response) {
    //var_dump($response); // Hello, Nekufa1
	echo "Response: " . pretty($response) . PHP_EOL;
});

$client->process(); // process request

// sync interaction (block until response get back)
$client->dispatch('hello.request', 'Nekufa2'); // Hello, Nekufa2