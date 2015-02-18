<?php

/**
 *  Shows how to send multiple requests simultaneously
 */
    
require_once(dirname(__DIR__).'/vendor/autoload.php');

use SimpleHttpClient\SimpleHttpClient;

try{

	$client = new SimpleHttpClient([
		'host'=>'download.finance.yahoo.com',
		'port'=>80,
		'contentType'=>'text/csv'
	]);
	$start = microtime(true);

	$client->get('/d/quotes.csv?s=GOOG&f=csv');
	$client->get('/d/quotes.csv?s=YHOO&f=csv');
	//$client->post('/', 'k=v');
	//$client->put('/','key=val');
	//$client->delete('/','key2=val2');
	$client->fetch();

	echo PHP_EOL.'RAW RESULTS:'.PHP_EOL;
	echo '====================='.PHP_EOL;
	$buffers = $client->getBuffers(function($val){return $val;});
	var_dump($buffers);

	echo PHP_EOL.'BODY ONLY:'.PHP_EOL;
	echo '====================='.PHP_EOL;
	$buffers = $client->getBuffers('body');
	var_dump($buffers);

	echo PHP_EOL.'RAW HEADERS:'.PHP_EOL;
	echo '====================='.PHP_EOL;
	$buffers = $client->getBuffers('raw_headers');
	var_dump($buffers);

	echo PHP_EOL.'PARSED HEADERS:'.PHP_EOL;
	echo '====================='.PHP_EOL;
	$buffers = $client->getBuffers('parsed_headers');
	var_dump($buffers);

	$client->flush();

	echo 'FINISHED IN: '.(microtime(true)-$start).' SECONDS'.PHP_EOL;

}catch(\Exception $e){
	echo $e->getMessage().PHP_EOL;
}
