<?php
require_once __DIR__ . "/../vendor/autoload.php";
use Nova\Mqtt\Mqtt;

$config = [
	'clientid' => uniqid(),
	'parameters' => [
		'address' => '192.168.111.15',
	    'port' => 1883,
	    'username' => 'rongbo',
	    'password' => '111'
	],
    'options' => [
    	'clean' => true,
    	'will' => NULL,
    	'mode' => 0,
    	'keepalive' => 10,
    	'timeout' => 30,
    ]
];
try{
	$mqtt = new Mqtt($config['clientid'],$config['parameters'],$config['options']);
	$rs = $mqtt->publish('abc','111');
	var_dump($mqtt);
	var_dump($rs);
}catch(\Exception $e){
	var_dump($e);
}