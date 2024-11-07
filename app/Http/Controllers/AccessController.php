<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use app\Helpers\MBankingHelper;

class AccessController extends Controller
{

	function __construct(){
		$this->middleware('snapAccess', ['only' => ['Token', 'Register', 'Login', 'ChangePass', 'ChangePIN']]);
	}

	public function Token(){
        /*
            Status      : Valid
            Last Update : 20-09-2024
            AR=a2,AB=a3,PP=66,TB=73,KR=42,CR=c2
        */
        if ($GLOBALS['status'] != "00"){
        
			return response()->json(
			[
				"status"  => $GLOBALS['status'],
				"message" => $GLOBALS['message'],
			]);
                
        }

        $scramble = "";
		$modul = explode("#", "a21#a31#661#730#420#c20");
		for ($i = 0; $i <= 5; $i++) {
	        $scr = md5($GLOBALS['ClientID'].$GLOBALS['TimeStamp'].$GLOBALS['ExternalID'].str_pad((($i+10)*73)-65, 5, "0", STR_PAD_LEFT));
	        $even = self::isEven($i);
	        if ($even){
    		    $scramble .= substr($scr, 0, 13) .$modul[$i] ;
	        }else{
    		    $scramble .= substr($scr, -13) .$modul[$i] ;
	        }
		}

		return response()->json(
		[
			"status"  => "00",
			"message" => "Sukses",
			"token" => $scramble,
		]);

	}

	public function Register(){
        /*
            Status      : Valid
            Last Update : 20-09-2024
        */
        if ($GLOBALS['status'] != "00"){
        
			return response()->json(
			[
				"status"  => $GLOBALS['status'],
				"message" => $GLOBALS['message'],
			]);
                
        }

    	$url = $GLOBALS['baseUrl'] .'access/register';
        $accessToken = self::getToken($GLOBALS['PartnerID'], $GLOBALS['TimeStamp'], $_GET['username'], $_GET['password']);

        $result = self::requestToServer($url, $_GET['username'], $_GET['password'], $accessToken);
        if ($GLOBALS['status'] == "80"){
       		$requestMB = DB::update('insert into mob_user (client_id, partner_id) values (?,?)',
       		    array($GLOBALS['ClientID'], $GLOBALS['PartnerID']));
        }
        return $result;
        
	}

	public function Login(){
        /*
            Status      : Valid
            Last Update : 20-09-2024
        */

        if ($GLOBALS['status'] != "00"){
        
			return response()->json(
			[
				"status"  => $GLOBALS['status'],
				"message" => $GLOBALS['message'],
			]);
                
        }

    	$url = $GLOBALS['baseUrl'] .'access/login';
        $accessToken = self::getToken($GLOBALS['PartnerID'], $GLOBALS['TimeStamp'], $_GET['username'], $_GET['password']);
        $result = self::requestToServer($url, $_GET['username'], $_GET['password'], $accessToken);
        return $result;

	}

	public function ChangePass(){

        if ($GLOBALS['status'] != "00"){
        
			return response()->json(
			[
				"status"  => $GLOBALS['status'],
				"message" => $GLOBALS['message'],
			]);
                
        }
        $pass = explode("#", $_GET['password']);
    	$url = $GLOBALS['baseUrl'] .'access/change-pass';
        //$accessToken = self::getToken($GLOBALS['PartnerID'], $GLOBALS['TimeStamp'], $_GET['username'], $pass[0]);
        $accessToken = $GLOBALS['Authorization'];
        $result = self::requestToServer($url, $_GET['username'], $_GET['password'], $accessToken);
        return $result;

	}

	public function ChangePIN(){

        if ($GLOBALS['status'] != "00"){
        
			return response()->json(
			[
				"status"  => $GLOBALS['status'],
				"message" => $GLOBALS['message'],
			]);
                
        }

        $pass = explode("#", $_GET['password']);
    	$url = $GLOBALS['baseUrl'] .'access/change-pin';
        //$accessToken = self::getToken($GLOBALS['PartnerID'], $GLOBALS['TimeStamp'], $_GET['username'], $pass[0]);
        $accessToken = $GLOBALS['Authorization'];
        $result = self::requestToServer($url, $_GET['username'], $_GET['password'], $accessToken);
        return $result;

	}

	public function requestToServer($url, $username, $password, $accessToken){

        $partnerID = $GLOBALS['PartnerID'];
        $timeStamp = $GLOBALS['TimeStamp'];
        $refNo = $GLOBALS['ExternalID'];

    	$mydata = array(
    		"username" => $username,
    		"password" => $password,
    	);		
    	$data = json_encode($mydata);
    
    	$join = "username:" .$username ."|password:". $password;
    	$body = hash("sha256", $join);
    	$strToSign = $partnerID. "|" .$accessToken. "|" .$body. "|" .$timeStamp;
    	$signature = base64_encode(hash_hmac("sha512", $strToSign, $GLOBALS['clientSecret'], true));
    	
    	$ch = curl_init(); 
    	curl_setopt($ch, CURLOPT_HEADER, 0);
    	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    	curl_setopt($ch, CURLOPT_POST, 1);
    	curl_setopt($ch, CURLOPT_URL, $url);
    	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 	
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);	
    	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    		'Content-Type: application/json',
    		'Authorization:Bearer ' .$accessToken,
    		'X-TIMESTAMP:' . $timeStamp,
    		'X-SIGNATURE:' . $signature,
    		'X-PARTNER-ID:' . $partnerID,
    		'X-EXTERNAL-ID:' . $refNo) 
    	);
    	
    	$output = curl_exec($ch); 
    	$result = json_decode($output, true);
    	$GLOBALS['status'] = $result['status'];
    	$info = curl_getinfo($ch);	
    	
    	if (!curl_errno($ch)) {
       		$requestMB = DB::update('insert into mob_access (client_id, partner_id, url, request, response, access_status) values (?,?,?,?,?,?)',
       		    array($GLOBALS['ClientID'], $GLOBALS['PartnerID'], $url, $data, $output, $GLOBALS['status']));
        	curl_close($ch);
        	print_r($output);
    	}else{
    		if ($info['http_code'] != 200){
    			$output = '{"timestamp":"' .$timeStamp. '","responseCode":' .$info['http_code']. ',"responseMessage":"URL API e-Link tidak sesuai","path":"' .$url. '"}';
           		$requestMB = DB::update('insert into mob_access (client_id, partner_id, url, request, response, access_status) values (?,?,?,?,?,?)',
           		    array($GLOBALS['ClientID'], $GLOBALS['PartnerID'], $url, $data, $output, $GLOBALS['status']));
            	curl_close($ch);
    			print_r($output);
    		}else{
    			$output = '{"timestamp":"' .$timeStamp. '","responseCode":"","responseMessage":"Host Bank offline","path":"' .$url. '"}';
           		$requestMB = DB::update('insert into mob_access (client_id, partner_id, url, request, response, access_status) values (?,?,?,?,?,?)',
           		    array($GLOBALS['ClientID'], $GLOBALS['PartnerID'], $url, $data, $output, $GLOBALS['status']));
            	curl_close($ch);
    			print_r($output);
    		}
    	}

	}

	public function getToken($clientID, $timeStamp, $username, $password){

    	$url = $GLOBALS['baseUrl'] .'access/token';

        $keyPrivate = config("app.private_key_str");

    	$clientStamp = $clientID ."<G>". $timeStamp;
    	openssl_sign($clientStamp, $signature, $keyPrivate, OPENSSL_ALGO_SHA256);
    	$sign64 = base64_encode($signature);
    	
    	$data = array(
    		"username" => $username,
    		"password" => $password,
    	);		
    	$data_string = json_encode($data);

    	$ch = curl_init(); 
    	curl_setopt($ch, CURLOPT_HEADER, 0);
    	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    	curl_setopt($ch, CURLOPT_POST, 1);
    	curl_setopt($ch, CURLOPT_URL, $url);
    	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 	
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);	
    	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    		'Content-Type: application/json',
    		'X-TIMESTAMP:' . $timeStamp,
    		'X-CLIENT-KEY:' . $clientID,
    		'X-SIGNATURE:' . $sign64)
    	);
    		
    	$output = curl_exec($ch); 
    	curl_close($ch);
    	$result = json_decode($output, true);
    	return $result['accessToken'];

	}
	
	public function Unscramble($acak){

		$detik = substr(substr($acak, 0, 4), -2);
		$menit = substr(substr($acak, -4), 0, 2);
	    $ndet = self::getValue($detik);
	    $nmen = self::getValue($menit);

		$ack = substr($acak, 4, (strlen($acak)-4));
		$nomor = substr($ack, 0, (strlen($ack)-4));

        $unique = "";
        $time = "";
        $tm = "";
		for ($j=1; $j<=100; $j++) {
			$valid = true;
			for ($i = 1; $i <= 8; $i++) {
				$unique .= md5("Seminyak" . str_pad($ndet*$i, 4, "0", STR_PAD_LEFT) . str_pad($nmen*$i, 4, "0", STR_PAD_LEFT));
				$tm .= md5("SMYK" . str_pad($ndet+($i*10), 3, "0", STR_PAD_LEFT) ."MSE". str_pad($nmen+($i*10), 3, "0", STR_PAD_LEFT));
			}
			for ($k=0; $k<strlen($unique)-4; $k++){
    		    $u = substr($unique, $k, 4);
        		$n = strpos($unique, $u, 0);
    		    $m = strpos($unique, $u, $n+1);
    		    if ($m > 0){
    			   $valid = false;
    			   break;
    		    }
			}
			if ($valid){
				break;
			}
		}

		for ($i=0; $i<strlen($tm); $i++){
			$t = substr($tm, $i, 1);
			if (is_numeric($t) == 1 && $t != "0"){
				$time .= $t;
				if (strlen($time) > 100){
					break;
				}
			}
		}

		$j = 0;
		$k = 0;
		$len = strlen($nomor)/4;
		$norek = "";
		$cari = "";
		for ($i=0; $i<$len; $i++){
			$no = substr($nomor, $j, 4);
			$n = strpos($unique, $no);
			$t = substr($time, $k, 2);
			$norek .= chr($n-$t);
			$j = $j + 4;
			$k = $k + 2;
		}
        return $norek;

	}

	public function getValue($nilai) {
		$a = intval(substr($nilai, 0, 1));
		$b = intval(substr($nilai, 1, 1));
		$c = ($a*10) + $b;
		return $c;
	}	

	public function isEven($nilai) {
		if ($nilai % 2 == 0){ 
		   return true;
		}else {
		   return false;
		}
   	}

}