<?php
namespace Nova\Mqtt;

/* Mqtt */
class Mqtt {

    private $socket; 			/* holds the socket	*/
	private $msgid = 1;			/* counter for message id */
	public $timesinceping;		/* host unix time, used to detect disconects */
	public $topics = array(); 	/* used to store currently subscribed topics */
	public $clientid;			/* client id sent to brocker */

    public $cafile;
    
    private $parameters = [
        'address' => '127.0.0.1',
    	'port' => 1883,
    	'username' => '',
    	'password' => ''
    ];

    private $options = [
        'mode' => 0,
        'keepalive' => 10,
        'timeout' => 30,
        'will' => [],            /* stores the will of the client */
        'clean' => true,
        'cafile' => ''
    ];

    /*
	Table of available operations using the MQTT protocol...
	*/
	var $operations = [
		'MQTT_CONNECT' => 1,
	    'MQTT_CONNACK' => 2,
	    'MQTT_PUBLISH' => 3,
	    'MQTT_PUBACK' => 4,
	    'MQTT_PUBREC' => 5,
	    'MQTT_PUBREL' => 6,
	    'MQTT_PUBCOMP' => 7,
	    'MQTT_SUBSCRIBE' => 8,
	    'MQTT_SUBACK' => 9,
	    'MQTT_UNSUBSCRIBE' => 10,
	    'MQTT_UNSUBACK' => 11,
	    'MQTT_PINGREC' => 12,
	    'MQTT_PINGRESP' => 13,
	    'MQTT_DISCONNECT' => 14
	];

	public function __construct($clientID,$parameters = [],$options = []){
		$this->broker($clientID,$parameters,$options);
	}

	/* sets the broker details */
	public function broker($clientID,$parameters = [],$options = []){
        $this->clientid = $clientID;
    	$this->parameters = $this->arrayToObject(array_intersect_key($parameters,$this->parameters));
        $this->options = $this->arrayToObject(array_intersect_key($options,$this->options));
	}

	function connect_auto($clean = true, $will = NULL, $username = NULL, $password = NULL){
		while($this->connect($clean, $will, $username, $password)==false){
			sleep(10);
		}
		return true;
	}

	/* connects to the broker 
		inputs: $clean: should the client send a clean session flag */
	public function connect(){
		if (isset($this->options->cafile) && !empty($this->options->cafile)) {
			$socketContext = stream_context_create(["ssl" => [
				"verify_peer_name" => true,
				"cafile" => $this->options->cafile
				]]);
			$this->socket = stream_socket_client("tls://" . $this->paramters->address . ":" . $this->parameters->port, $errno, $errstr, $this->options->timeout, STREAM_CLIENT_CONNECT, $socketContext);
		} else {
			$this->socket = stream_socket_client("tcp://" . $this->parameters->address . ":" . $this->parameters->port, $errno, $errstr, $this->options->timeout, STREAM_CLIENT_CONNECT);
		}

		if (!$this->socket ) {
		    throw new \Exception("$errstr",$errno);
		}

		stream_set_timeout($this->socket, $this->options->timeout);
		stream_set_blocking($this->socket, $this->options->mode);

		$i = 0;
		$buffer = "";

		$buffer .= chr(0x00); $i++;
		$buffer .= chr(0x06); $i++;
		$buffer .= chr(0x4d); $i++;
		$buffer .= chr(0x51); $i++;
		$buffer .= chr(0x49); $i++;
		$buffer .= chr(0x73); $i++;
		$buffer .= chr(0x64); $i++;
		$buffer .= chr(0x70); $i++;
		$buffer .= chr(0x03); $i++;

		//No Will
		$var = 0;
		if($this->options->clean) $var+=2;

		//Add will info to header
		if($this->options->will != NULL){
			$var += 4; // Set will flag
			$var += ($this->options->will->qos << 3); //Set will qos
			if($this->options->will->retain)	$var += 32; //Set will retain
		}

		if($this->parameters->username != NULL) $var += 128;	//Add username to header
		if($this->parameters->password != NULL) $var += 64;	//Add password to header

		$buffer .= chr($var); $i++;

		//Keep alive
		$buffer .= chr($this->options->keepalive >> 8); $i++;
		$buffer .= chr($this->options->keepalive & 0xff); $i++;

		$buffer .= $this->strwritestring($this->clientid,$i);

		//Adding will to payload
		if($this->options->will != NULL){
			$buffer .= $this->strwritestring($this->options->will->topic,$i);  
			$buffer .= $this->strwritestring($this->options->will->content,$i);
		}

		if($this->parameters->username) $buffer .= $this->strwritestring($this->parameters->username,$i);
		if($this->parameters->password) $buffer .= $this->strwritestring($this->parameters->password,$i);

		$head = "  ";
		$head{0} = chr(0x10);
		$head{1} = chr($i);

		fwrite($this->socket, $head, 2);
		fwrite($this->socket,  $buffer);

	 	$string = $this->read(4);

		if(ord($string{0})>>4 == 2 && $string{3} == chr(0)){
			//success 
		}else{	
			throw new \Exception(sprintf("Connection failed! (Error: 0x%02x 0x%02x)\n",ord($string{0}),ord($string{3})),500);
		}

		$this->timesinceping = time();

		return true;
	}

	/* read: reads in so many bytes */
	public function read($int = 8192, $nb = false){

		//	print_r(socket_get_status($this->socket));
		
		$string="";
		$togo = $int;
		
		if($nb){
			return fread($this->socket, $togo);
		}
			
		while (!feof($this->socket) && $togo>0) {
			$fread = fread($this->socket, $togo);
			$string .= $fread;
			$togo = $int - strlen($string);
		}	
		return $string;
	}

	/* subscribe: subscribes to topics */
	function subscribe($topics, $qos = 0){
		$i = 0;
		$buffer = "";
		$id = $this->msgid;
		$buffer .= chr($id >> 8);  $i++;
		$buffer .= chr($id % 256);  $i++;

		foreach($topics as $key => $topic){
			$buffer .= $this->strwritestring($key,$i);
			$buffer .= chr($topic["qos"]);  $i++;
			$this->topics[$key] = $topic; 
		}

		$cmd = 0x80;
		//$qos
		$cmd +=	($qos << 1);


		$head = chr($cmd);
		$head .= chr($i);
		
		fwrite($this->socket, $head, 2);
		fwrite($this->socket, $buffer, $i);
		$string = $this->read(2);
		
		$bytes = ord(substr($string,1,1));
		$string = $this->read($bytes);
	}

	/* ping: sends a keep alive ping */
	function ping(){
			$head = " ";
			$head = chr(0xc0);		
			$head .= chr(0x00);
			fwrite($this->socket, $head, 2);
			if($this->debug) echo "ping sent\n";
	}

	/* disconnect: sends a proper disconect cmd */
	function disconnect(){
			$head = " ";
			$head{0} = chr(0xe0);		
			$head{1} = chr(0x00);
			fwrite($this->socket, $head, 2);
	}

	/* close: sends a proper disconect, then closes the socket */
	function close(){
	 	$this->disconnect();
		stream_socket_shutdown($this->socket, STREAM_SHUT_WR);	
	}

	/* publish: publishes $content on a $topic */
	public function publish($topic, $content, $qos = 0, $retain = 0){
        $rs = true;
		$i = 0;
		$buffer = "";
		$buffer .= $this->strwritestring($topic,$i);

		//$buffer .= $this->strwritestring($content,$i);

		if($qos){
			$id = $this->msgid++;
			$buffer .= chr($id >> 8);  $i++;
		 	$buffer .= chr($id % 256);  $i++;
		}

		$buffer .= $content;
		$i+=strlen($content);


		$head = " ";
		$cmd = 0x30;
		if($qos) $cmd += $qos << 1;
		if($retain) $cmd += 1;

		$head{0} = chr($cmd);		
		$head .= $this->setmsglength($i);

		fwrite($this->socket, $head, strlen($head));
		fwrite($this->socket, $buffer, $i);

        if ($qos > 0){
			 $hdr = $this->read_fixed_header();
			 //var_dump($hdr);
			  if ($hdr){
				 /* is this a QoS level 1 message being sent?      */
				 if ($qos == 1) {
					/* Yup, so we should get a PUBACK response message...    */
					if ($hdr['mtype'] == $this->operations['MQTT_PUBACK']) {
						$len = $this->read_remaining_length();
						if ($len > 0) {
							$response = $this->read($len);
						}
						//var_dump($len);
						if ($len < 2) {
							$result = false;
						} else {
							if(function_exists($puback)){
								call_user_func($puback,$topic,$content);
							}
						}
					} else {
						$result = false;
					}
				}
				if ($qos == 2){
					$result = false;
				}
			}
        }
        return $rs;
    }
    
    protected function read_fixed_header() {
        $rc = false;
        $response = $this->read(1);
        if (strlen($response) > 0) {
            $fields = unpack('Cbyte1', $response);
            $x = $fields['byte1'];
            $ret = $x % 2;
            $x -= $ret;
            $qos = ($x % 8) / 2;
            $x -= ($qos * 2);
            $dup = ($x % 16) / 8;
            $x -= ($dup * 8);
            $mtype = $x / 16;
            //if ($this->debug) t("SAMConnection_MQTT.read_fixed_header() mtype=$mtype, dup=$dup, qos=$qos, retain=$ret");
            $rc = array('mtype' => $mtype, 'dup' => $dup, 'qos' => $qos, 'retain' => $ret);
        }
        return $rc;
    }

	/* message: processes a recieved topic */
	function message($msg){
		 	$tlen = (ord($msg{0})<<8) + ord($msg{1});
			$topic = substr($msg,2,$tlen);
			$msg = substr($msg,($tlen+2));
			$found = 0;
			foreach($this->topics as $key=>$top){
				if( preg_match("/^".str_replace("#",".*",
						str_replace("+","[^\/]*",
							str_replace("/","\/",
								str_replace("$",'\$',
									$key))))."$/",$topic) ){
					if(is_callable($top['function'])){
						call_user_func($top['function'],$topic,$msg);
						$found = 1;
					}
				}
			}

			if($this->debug && !$found) echo "msg recieved but no match in subscriptions\n";
	}

	/* proc: the processing loop for an "allways on" client 
		set true when you are doing other stuff in the loop good for watching something else at the same time */	
	function proc( $loop = true){

		if(1){
			$sockets = array($this->socket);
			$w = $e = NULL;
			$cmd = 0;
			
				//$byte = fgetc($this->socket);
			if(feof($this->socket)){
				if($this->debug) echo "eof receive going to reconnect for good measure\n";
				fclose($this->socket);
				$this->connect_auto(false);
				if(count($this->topics))
					$this->subscribe($this->topics);	
			}
			
			$byte = $this->read(1, true);
			
			if(!strlen($byte)){
				if($loop){
					usleep(100000);
				}
			 
			}else{ 
			
				$cmd = (int)(ord($byte)/16);
				if($this->debug) echo "Recevid: $cmd\n";

				$multiplier = 1; 
				$value = 0;
				do{
					$digit = ord($this->read(1));
					$value += ($digit & 127) * $multiplier; 
					$multiplier *= 128;
					}while (($digit & 128) != 0);

				if($this->debug) echo "Fetching: $value\n";
				
				if($value)
					$string = $this->read($value);
				
				if($cmd){
					switch($cmd){
						case 3:
							$this->message($string);
						break;
					}

					$this->timesinceping = time();
				}
			}

			if($this->timesinceping < (time() - $this->keepalive )){
				if($this->debug) echo "not found something so ping\n";
				$this->ping();	
			}
			

			if($this->timesinceping<(time()-($this->keepalive*2))){
				if($this->debug) echo "not seen a package in a while, disconnecting\n";
				fclose($this->socket);
				$this->connect_auto(false);
				if(count($this->topics))
					$this->subscribe($this->topics);
			}

		}
		return 1;
	}

	/* getmsglength: */
	function getmsglength(&$msg, &$i){

		$multiplier = 1; 
		$value = 0 ;
		do{
		  $digit = ord($msg{$i});
		  $value += ($digit & 127) * $multiplier; 
		  $multiplier *= 128;
		  $i++;
		}while (($digit & 128) != 0);

		return $value;
	}


	/* setmsglength: */
	public function setmsglength($len){
		$string = "";
		do{
		  $digit = $len % 128;
		  $len = $len >> 7;
		  // if there are more digits to encode, set the top bit of this digit
		  if ( $len > 0 )
		    $digit = ($digit | 0x80);
		  $string .= chr($digit);
		}while ( $len > 0 );
		return $string;
	}

	/* strwritestring: writes a string to a buffer */
	protected function strwritestring($str, &$i){
		$ret = " ";
		$len = strlen($str);
		$msb = $len >> 8;
		$lsb = $len % 256;
		$ret = chr($msb);
		$ret .= chr($lsb);
		$ret .= $str;
		$i += ($len+2);
		return $ret;
    }
    
    protected function read_remaining_length() {
        $rc = 0;
        $m = 1;
        while (!feof($this->socket)) {
            $byte = fgetc($this->socket);
            $fields = unpack('Ca', $byte);
            $x = $fields['a'];
            if ($x < 128) {
                $rc += $x * $m;
                break;
            } else {
                $rc += (($x - 128) * $m);
            }
            $m *= 128;
        }
        return $rc;
    }

	function printstr($string){
		$strlen = strlen($string);
			for($j=0;$j<$strlen;$j++){
				$num = ord($string{$j});
				if($num > 31) 
					$chr = $string{$j}; else $chr = " ";
				printf("%4d: %08b : 0x%02x : %s \n",$j,$num,$num,$chr);
			}
    }
    
    private function arrayToObject($arr){
    	return json_decode(json_encode($arr));
    }
}
