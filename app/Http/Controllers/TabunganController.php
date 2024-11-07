<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TabunganController extends Controller
{

	function __construct(){
		$this->middleware('snapAccess', ['only' => 
		['ListAccount', 'HistoryAccount', 'HistoryDeposito','ListPinjaman','HistoryPinjaman']]);
	}

	public function ListAccount(){
        if ($GLOBALS['status'] != "00"){
        
			return response()->json(
			[
				"status"  => "39",
				"message" => "Signature invalid !",
			]);
                
        }
        $customer = explode("-", $_GET['customer_pass']);
        $GLOBALS['username'] = $customer[0];
        $GLOBALS['password'] = $customer[1];

    	$data = array(
    		"username" => $GLOBALS['username'],
    		"password" => $GLOBALS['password'],
    		"customer_id" => $_GET['customer_id'],
    	);		
    	$json = json_encode($data);

    	$url = $GLOBALS['baseUrl'] .'tabungan/list-account';

        $accessToken = $GLOBALS['Authorization'];
        //$accessToken = self::getToken();
        $result = self::requestToServer($url, $json, $accessToken);
        return $result;
        
	}

	public function HistoryAccount(){
        if ($GLOBALS['status'] != "00"){
        
			return response()->json(
			[
				"status"  => "39",
				"message" => "Signature invalid !",
			]);
                
        }
        $customer = explode("-", $_GET['customer_pass']);
        $GLOBALS['username'] = $customer[0];
        $GLOBALS['password'] = $customer[1];

    	$data = array(
    		"username" => $GLOBALS['username'],
    		"password" => $GLOBALS['password'],
    		"customer_acc" => $_GET['customer_acc'],
    		"start_date" => $_GET['start_date'],
    		"end_date" => $_GET['end_date'],
    	);		
    	$json = json_encode($data);

    	$url = $GLOBALS['baseUrl'] .'tabungan/history-account';

        $accessToken = $GLOBALS['Authorization'];
        //$accessToken = self::getToken();
        $result = self::requestToServer($url, $json, $accessToken);
        return $result;
        
	}

	public function HistoryDeposito(){
        if ($GLOBALS['status'] != "00"){
        
			return response()->json(
			[
				"status"  => "39",
				"message" => "Signature invalid !",
			]);
                
        }
        $customer = explode("-", $_GET['customer_pass']);
        $GLOBALS['username'] = $customer[0];
        $GLOBALS['password'] = $customer[1];

    	$data = array(
    		"username" => $GLOBALS['username'],
    		"password" => $GLOBALS['password'],
    		"customer_acc" => $_GET['customer_acc'],
    	);		
    	$json = json_encode($data);

    	$url = $GLOBALS['baseUrl'] .'deposito/history-account';

        $accessToken = $GLOBALS['Authorization'];
        //$accessToken = self::getToken();
        $result = self::requestToServer($url, $json, $accessToken);
        return $result;
        
	}

	public function ListPinjaman(){
        if ($GLOBALS['status'] != "00"){
        
			return response()->json(
			[
				"status"  => "39",
				"message" => "Signature invalid !",
			]);
                
        }
        $customer = explode("-", $_GET['customer_pass']);
        $GLOBALS['username'] = $customer[0];
        $GLOBALS['password'] = $customer[1];

    	$data = array(
    		"username" => $GLOBALS['username'],
    		"password" => $GLOBALS['password'],
    		"customer_id" => $_GET['customer_id'],
    	);		
    	$json = json_encode($data);

    	$url = $GLOBALS['baseUrl'] .'pinjaman/list-account';

        $accessToken = $GLOBALS['Authorization'];
        //$accessToken = self::getToken();
        $result = self::requestToServer($url, $json, $accessToken);
        return $result;
        
	}

	public function HistoryPinjaman(){
        if ($GLOBALS['status'] != "00"){
        
			return response()->json(
			[
				"status"  => "39",
				"message" => "Signature invalid !",
			]);
                
        }
        $customer = explode("-", $_GET['customer_pass']);
        $GLOBALS['username'] = $customer[0];
        $GLOBALS['password'] = $customer[1];

    	$data = array(
    		"username" => $GLOBALS['username'],
    		"password" => $GLOBALS['password'],
    		"customer_acc" => $_GET['customer_acc'],
    	);		
    	$json = json_encode($data);

    	$url = $GLOBALS['baseUrl'] .'pinjaman/history-account';

        $accessToken = $GLOBALS['Authorization'];
        //$accessToken = self::getToken();
        $result = self::requestToServer($url, $json, $accessToken);
        return $result;
        
	}

	public function requestToServer($url, $data, $accessToken){

        $partnerID = $GLOBALS['PartnerID'];
        $timeStamp = $GLOBALS['TimeStamp'];
        $refNo = $GLOBALS['ExternalID'];


    	$join = "username:" .$GLOBALS['username'] ."|password:". $GLOBALS['password'];
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
    	curl_close($ch);

   		$requestMB = DB::update('insert into mob_access (client_id, partner_id, url, request, response, access_status) values (?,?,?,?,?,?)',
   		    array($GLOBALS['ClientID'], $GLOBALS['PartnerID'], $url, $data, $output, $GLOBALS['status']));
    	
    	print_r($output);

	}

	public function getToken(){

    	$url = $GLOBALS['baseUrl'] .'access/token';

        $keyPrivate = config("app.private_key_str");

    	$clientStamp = $GLOBALS['PartnerID'] ."<G>". $GLOBALS['TimeStamp'];
    	openssl_sign($clientStamp, $signature, $keyPrivate, OPENSSL_ALGO_SHA256);
    	$sign64 = base64_encode($signature);
    	
    	$data = array(
    		"username" => $GLOBALS['username'],
    		"password" => $GLOBALS['password'],
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
    		'X-TIMESTAMP:' . $GLOBALS['TimeStamp'],
    		'X-CLIENT-KEY:' . $GLOBALS['PartnerID'],
    		'X-SIGNATURE:' . $sign64)
    	);
    		
    	$output = curl_exec($ch); 
    	curl_close($ch);
    	$result = json_decode($output, true);
    	return $result['accessToken'];

	}
	

}