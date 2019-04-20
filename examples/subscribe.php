<?php
require_once __DIR__ .'/shared.php';
use Nova\Mqtt\Mqtt;

try{
	$mqtt = new Mqtt($config['clientid'],$config['parameters'],$config['options']);
	$rs = $mqtt->subscribe(['abc' => [
		'qos' => 0,
		'function' => 'callback'
	]],'callback');
	var_dump($mqtt);
	var_dump($rs);
}catch(\Exception $e){
	var_dump($e);
}

function callback($topic,$msg){
	echo "Msg Recieved: " . date("r") . "\n";
	echo "Topic: {$topic}\n\n";
	echo "\t$msg\n\n";
}