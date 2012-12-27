<?php

require_once(dirname(__DIR__).'/vendor/autoload.php');

use SimpleHttpClient\SimpleHttpClient;

$client = new SimpleHttpClient([
	'host'=>'datashovel.com',
	'port'=>80,
	'contentType'=>'text/html',
	'debug'=>true
]);

try{
	var_dump($client->get('/'));
	var_dump($client->put('/', 'PUT lskdlksjf'));
	var_dump($client->post('/','POST lksjdflj'));
	var_dump($client->delete('/'));
}catch(\Exception $e){
	echo $e->getMessage().PHP_EOL;
}
