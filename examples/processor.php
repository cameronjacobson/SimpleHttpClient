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
		$buffers = $client->getBuffers('parsed_headers');
		echo PHP_EOL.'PARSED HEADERS:'.PHP_EOL;
		echo '====================='.PHP_EOL;
		foreach($buffers as $buffer){
			var_dump($buffer);
		}

		$client->flush();
		$base->exit();
	});

	$client->setProcessor(function(callable $cb) use ($client,$base){
		if($client->getCount() < 2){
			$client->setHost('www.google.com');
			$client->get('/');
			$client->dispatch();
		}
		if($client->isDone()){
			$cb();
		}
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
