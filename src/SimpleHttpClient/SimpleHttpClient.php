<?php

namespace SimpleHttpClient;

class SimpleHttpClient
{
	protected $host;
	protected $port;
	protected $client;
	protected $user;
	protected $pass;
	protected $contentType;
	protected $debug;

	private $validKeys = [
		'host', 'port', 'user', 'pass', 'contentType', 'debug'
	];

	public function __construct(Array $options = []){
		$this->host = $options['host'];
		$this->port = $options['port'];
		$this->user = $options['user'];
		$this->pass = $options['pass'];
		$this->contentType = $options['contentType'];
		$this->debug = (bool)$options['debug'];
	}

	public function setUser($user){
		$this->user = $user;
	}

	public function setPassword($pass){
		$this->pass = $pass;
	}

	public function setHost($host){
		$this->host = $host;
	}

	public function getOptions(){
		$options = [];
		foreach($this->validKeys as $key){
			$options[$key] = $this->$key;
		}
		return $options;
	}

	public function setPort($port){
		$this->port = $port;
	}

	public function setDebug($port){
		$this->debug = $debug;
	}

	public function setContentType($contentType){
		$this->contentType = $contentType;
	}

	public function setOption($key, $value){
		if(in_array($k, $this->validKeys)){
			$this->$k = $v;
		}
		else{
			throw new \Exception('Invalid option: '.$k);
		}
	}

	public function setOptions(Array $options){
		foreach($options as $k => $v){
			$this->setOption($k, $v);
		}
	}

	public function get($path){
		return $this->sendRequest('GET',$path);
	}

	public function put($path,$body){
		return $this->sendRequest('PUT',$path,$body);
	}

	public function post($path,$body){
		return $this->sendRequest('POST',$path,$body);
	}

	public function custom($method,$path,$body){
		return $this->sendRequest($method,$path,$body);
	}

	public function delete($path){
		return $this->sendRequest('DELETE', $path);
	}

	public function sendRequest($method, $url, $body = '')
	{
		$client = stream_socket_client('tcp://'.$this->host.':'.$this->port, $errno, $errstr, 30);

		if (!$client) {
			throw new \Exception($errno.':'.$errstr);
		}

		$headers = array(
			'%s %s HTTP/1.1',
			'Host: %s %d',
			'%sContent-Type: %s',
			'Connection: close'
		);
		$request = sprintf(
			implode("\r\n",$headers)."\r\n",
			$method,
			$url,
			$this->host,
			$this->port,
			empty($this->user) ? '' : 'Authorization: Basic '.base64_encode($this->user.':'.$this->pass)."\r\n",
			@$this->contentType ?: 'application/json'
		);

		if (!empty($body)) {
			$request .= 'Content-Length: ' . strlen($body) . "\r\n";
		}
		$request .= "\r\n".$body;

		if($this->debug){
			echo 'SENDING: '.PHP_EOL;
			echo $request;
		}

		fwrite($client, $request);

		$buffer = '';

		while (!feof($client)) {
			$buffer .= fgets($client);
		}

		if($this->debug){
			echo 'RECEIVING: '.PHP_EOL;
			echo $buffer;
		}

		list($headers, $body) = explode("\r\n\r\n", $buffer);

		return ['headers' => $headers, 'body' => $body];
	}
}
