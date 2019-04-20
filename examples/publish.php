<?php
require_once __DIR__ .'/shared.php';
use Nova\Mqtt\Mqtt;

try{
	$mqtt = new Mqtt($config['clientid'],$config['parameters'],$config['options']);
	$rs = $mqtt->publish('abc','111');
	var_dump($mqtt);
	var_dump($rs);
}catch(\Exception $e){
	var_dump($e);
}