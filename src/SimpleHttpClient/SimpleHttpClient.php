<?php

namespace SimpleHttpClient;

class SimpleHttpClient
{
	public $host;
	public $port;
	public $client;

	public function __construct(Array $options = []){
		$this->host = $options['host'];
		$this->port = $options['port'];
		$this->user = $options['user'];
		$this->pass = $options['pass'];
		$this->contentType = $options['contentType'];
		$this->debug = (bool)$options['debug'];
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
		$request .= "\r\n".$payload;

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
