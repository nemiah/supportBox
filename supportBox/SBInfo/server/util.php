<?php

class util {
	public static function dbConnection(){
		$DB = new \PhpFileDB();
		$DB->setFolder("/var/www/html/system/DBData/");
		$Q = $DB->pfdbQuery("SELECT * FROM Installation");
		$R = $DB->pfdbFetchAssoc($Q);
			
		$C = new mysqli($R["host"], $R["user"], $R["password"], $R["datab"]);
		
		return $C;
	}
	
	public static function serial() {
		$cpuinfo = file_get_contents("/proc/cpuinfo");

		$ex = explode("\n", trim($cpuinfo));

		$info = array();
		foreach($ex AS $line){
				$e = explode(":", $line);
				if(count($e) < 2)
						continue;

				$info[trim($e[0])] = trim($e[1]);
		}
		if(isset($info["Serial"]))
			return $info["Serial"];

		return rand(10000, 99999);
	}
	
	public static function sayHo($session){
		$session->publish('it.furtmeier.supportbox.pisays', [util::message("ho")], [], ["acknowledge" => true]);
		util::log("Saying 'ho' to server");
	}
	
	public static function sayImHere($session, $answerto){
		$session->publish('it.furtmeier.supportbox.'.strtolower($answerto), [util::message("I'm here")], [], ["acknowledge" => true]);
		util::log("Answering 'I'm here'");
	}
	
	public static function sayAllDone($session, $serial){
		$session->publish('it.furtmeier.supportbox.'.$serial, [util::message("AllDone")], [], ["acknowledge" => true]);
	}
	
	public static function message($method, $content = null){
		$message = new stdClass();
		$message->m = $method;
		if($content)
			$message->c = $content;
		$message->f = ltrim(util::serial(), "0");
		$message->t = time();

		return $message;
	}
	
	public static function isConnected($PID){
		exec("ps -p ".$PID, $out, $return);
		return $return == "0";
	}
	
	public static function log($message){
		echo mb_substr(date("F"), 0, 3).str_pad(date("d"), 3, " ", STR_PAD_LEFT)." ".date("H:i:s")." ".trim(shell_exec("hostname"))." supportBox: $message\n";
	}
	
	public static function error($message){
		$response = new stdClass();
		$response->status = "Error";
		$response->message = $message;
		
		return json_encode($response, JSON_UNESCAPED_UNICODE);
	}
	
	public static function ok($message, $data = null){
		$response = new stdClass();
		$response->status = "Ok";
		$response->message = $message;
		if($data)
			$response->data = $data;
		
		return json_encode($response, JSON_UNESCAPED_UNICODE);
	}
	
	public static function init($C){
		$Q = $C->query("SELECT * FROM Userdata WHERE UserID = -1 AND name = 'SBToken'");
		$RToken = $Q->fetch_object();
		if(!$RToken){
			sleep(3);
			exit(2);
		}

		#$serial = util::serial();
		$realm = "supportBox_1";
		$token = $RToken->wert;

		$url = "wss://".self::serverURL().":".self::serverPort()."/";

		Thruway\Logging\Logger::set(new Psr\Log\NullLogger());
		$connection = new \Thruway\Connection([
			"realm"   => $realm,
			"url"     => $url
		]);

		$client = $connection->getClient();
		$auth = new Thruway\Authentication\ClientWampCraAuthenticator("supportBox", $token);
		$client->setAuthId('supportBox');
		$client->addClientAuthenticator($auth);

		$connection->on('error', function($reason){
			print_r($reason);
		});
		
		return $connection;
	}
	
	public static function serverURL(){
		return "venus.supportbox.io";
	}
	
	public static function serverPort(){
		return 4444;
	}
	
	public static function cloud($C){
		$Q = $C->query("SELECT * FROM Userdata WHERE UserID = -1 AND name = 'SBCloud'");
		$RCloud = $Q->fetch_object();
		return $RCloud->wert;
	}
}