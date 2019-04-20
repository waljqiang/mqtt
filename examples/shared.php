<?php
require_once __DIR__ . "/../vendor/autoload.php";

$config = [
	'clientid' => uniqid(),
	'parameters' => [
		'address' => '192.168.33.10',
	    'port' => 1883,
	    'username' => 'admin',
	    'password' => '726215'
	],
    'options' => [
    	'clean' => true,
    	'will' => NULL,
    	'mode' => 0,
    	'keepalive' => 10,
    	'timeout' => 30,
    ]
];