<?php
    
require_once(dirname(__DIR__).'/vendor/autoload.php');

use SimpleHttpClient\SimpleHttpClient;

try{

	$base = new EventBase();
	$dns_base = new EventDnsBase($base, TRUE);

	$client = new SimpleHttpClient([
		'host'=>'www.datashovel.com',
		'port'=>80,
		'contentType'=>'text/html',
		'base'=>$base,
		'dns_base'=>$dns_base,
	]);

	$client->setCallback(function() use ($client,$base){
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
		$base->exit();
	});

	$start = microtime(true);
	$client->get('/');
	//$client->post('/', 'k=v');
	//$client->put('/','key=val');
	//$client->delete('/','key2=val2');
	$client->dispatch();

	$base->dispatch();

	echo 'FINISHED IN: '.(microtime(true)-$start).' SECONDS'.PHP_EOL;

}catch(\Exception $e){
	echo $e->getMessage().PHP_EOL;
}
