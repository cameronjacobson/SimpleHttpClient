<?php

namespace SimpleHttpClient;

use \Event;
use \EventBase;
use \EventUtil;
use \EventBuffer;
use \EventDnsBase;
use \EventBufferEvent;
use \EventHttpConnection;
use \EventHttpRequest;
use \SplQueue;

class SimpleHttpClient
{
	protected $scheme;
	protected $host;
	protected $port;
	protected $client;
	protected $user;
	protected $pass;
	protected $contentType;
	protected $debug;
	protected $multi;
	protected $queue;
	private $count = 0;
	private $buffers = array();
	private $errors = array();
	private $filter;

	const MIN_WATERMARK = 512;

	private $validKeys = [
		'scheme', 'host', 'port', 'user', 'pass', 'contentType', 'debug', 'multi'
	];

	public function getBuffers($filter = null){
		$return = array();
		foreach($this->buffers as $key=>$value){
			if(is_string($filter)){
				$return[$key] = $this->commonFilters($filter, $value);
			}
			elseif(is_callable($filter)){
				$return[$key] = $filter($value);
			}
			else{
				return $this->buffers;
			}
		}
		return $return;
	}

	public function getCount(){
		return $this->count;
	}

	public function flush(){
		$this->count = 0;
		$this->buffers = array();
	}

	private function commonFilters($filterName, $value){
		switch($filterName){
			case 'headers':
				list($header,$body) = explode("\r\n\r\n",$value,2);
				return $header;
				break;
			case 'body':
				list($header,$body) = explode("\r\n\r\n",$value,2);
				return $body;
				break;
		}
	}

	public function __construct(Array $options = []){
		$this->host = $options['host'];
		$this->port = $options['port'];
		$this->user = $options['user'];
		$this->pass = $options['pass'];
		$this->contentType = $options['contentType'] ?: 'application/json';
		$this->debug = empty($options['debug']) ? false : true;
		$this->multi = empty($options['multi']) ? null : $options['multi'];
		$this->queue = new SplQueue();
		$this->base = new EventBase();
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

	public function setDebug($debug){
		$this->debug = $debug;
	}

	public function setMulti($multi){
		$this->multi = $multi;
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

	public function delete($path, $body){
		return $this->sendRequest('DELETE', $path, $body);
	}

	public function fetch(){
		if(!empty($this->queue)){
			foreach($this->queue as $fn){
				$fn();
				$this->queue->dequeue();
			}
			$this->base->dispatch();
		}
	}

	public function sendRequest($method, $url, $body = '') {
		$host = $this->host;
		$port = $this->port;
		$base = $this->base;
		$count = ++$this->count;
		$fn = function() use($base, $host, $port, $method, $url, $body, $count) {

			$dns_base = new EventDnsBase($base, TRUE);

			$readcb = function($bev, $count){
				$bev->readBuffer($bev->input);
				$this->buffers[$count] = empty($this->buffers[$count]) ? '' : $this->buffers[$count];
				while($line = $bev->input->read(SimpleHttpClient::MIN_WATERMARK * 2)){
					$this->buffers[$count] .= $line;
				}
			};

			$eventcb = function($bev, $events, $count){
				if($events & (EventBufferEvent::ERROR | EventBufferEvent::EOF)){
					if($events & EventBufferEvent::ERROR){
						$this->errors[$count] = 'DNS error: '.$bev->getDnsErrorString().PHP_EOL;
					}
					if($events & EventBufferEvent::EOF){
						$this->buffers[$count] .= $bev->input->read(SimpleHttpClient::MIN_WATERMARK * 10);
					}
				}
			};

			$readcb->bindTo($this);
			$eventcb->bindTo($this);

			$bev = new EventBufferEvent($this->base, NULL,
			    EventBufferEvent::OPT_CLOSE_ON_FREE | EventBufferEvent::OPT_DEFER_CALLBACKS,
			    $readcb, NULL, $eventcb, $count
			);
			$bev->setWatermark(Event::READ|Event::WRITE, SimpleHttpClient::MIN_WATERMARK, 0);

			$bev->enable(Event::READ | Event::WRITE);

			$output = $bev->output; 

			if (!$output->add(
			    "{$method} {$url} HTTP/1.0\r\n".
			    "Host: {$this->host}:{$this->port}\r\n".
				(empty($this->user) ? '' : 'Authorization: Basic '.base64_encode($this->user.':'.$this->pass)."\r\n").
				"Content-Type: {$this->contentType}\r\n".
				'Content-Length: ' . strlen($body) . "\r\n".
			    "Connection: Close\r\n\r\n{$body}"
			)) {
			    exit("Failed adding request to output buffer\n");
			}

			if (!$bev->connectHost($dns_base, $this->host, $this->port, EventUtil::AF_UNSPEC)) {
			    exit("Can't connect to host {$this->host}\n");
			}

		};
		$fn->bindTo($this);
		$this->queue->enqueue($fn);
	}
}
