<?php
    
require_once(dirname(__DIR__).'/vendor/autoload.php');

use SimpleHttpClient\SimpleHttpClient;

try{

	$client = new SimpleHttpClient([
		'host'=>'www.datashovel.com',
		'port'=>80,
		'contentType'=>'text/html'
	]);
	$start = microtime(true);

	$client->get('/');
	$client->post('/', 'k=v');
	$client->put('/','key=val');
	$client->delete('/','key2=val2');
	$client->fetch();

	$buffers = $client->getBuffers(function($val){return $val;});
	var_dump($buffers);
	$buffers = $client->getBuffers('body');
	var_dump($buffers);
	$buffers = $client->getBuffers('headers');
	var_dump($buffers);

	$client->flush();

	echo 'FINISHED IN: '.(microtime(true)-$start).' SECONDS'.PHP_EOL;

}catch(\Exception $e){
	echo $e->getMessage().PHP_EOL;
}
