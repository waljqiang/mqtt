<?php
require_once __DIR__ .'/shared.php';
use Waljqiang\Mqtt\Mqtt;

try{
	$mqtt = new Mqtt($config['parameters'],$config['options']);
	//$mqtt->debug = true;
	if(!$mqtt->connect($config['clientid']))
		exit(-1);
	$rs = $mqtt->publish('abc','111');
	$mqtt->close();
	var_dump($mqtt);
	var_dump($rs);
}catch(\Exception $e){
	var_dump($e);
}