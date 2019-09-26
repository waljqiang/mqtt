<?php
require_once __DIR__ .'/shared.php';
use Waljqiang\Mqtt\Mqtt;

try{
	$mqtt = new Mqtt($config['clientid'],$config['parameters'],$config['options']);
	//$mqtt->debug = true;
	if(!$mqtt->connect())
		exit(-1);
	$mqtt->subscribe(['abc' => ['qos'=>0,'function' => 'procmsg']]);
	while($mqtt->proc()){

	}
	$mqtt->close();
}catch(\Exception $e){
	var_dump($e);
}

function procmsg($topic,$msg){
	var_dump($topic);
	var_dump($msg);
}