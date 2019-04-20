<?php
require_once __DIR__ .'/shared.php';
use Nova\Mqtt\Mqtt;

try{
	$mqtt = new Mqtt($config['clientid'],$config['parameters'],$config['options']);
	$mqtt->connect();
	$rs = $mqtt->publish('abc','111');
	var_dump($mqtt);
	var_dump($rs);
	$mqtt->close();
}catch(\Exception $e){
	var_dump($e);
}