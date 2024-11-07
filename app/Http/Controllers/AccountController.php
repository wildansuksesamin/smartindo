<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AccountController extends Controller
{

	function __construct(){
		$this->middleware('snapAccess', ['only' => 
		['DeleteAccount', 'SaveAccount']]);
	}

	public function SaveAccount(){
        if ($GLOBALS['status'] != "00"){
        
			return response()->json(
			[
				"status"  => $GLOBALS['status'],
				"message" => $GLOBALS['message'],
			]);
                
        }
        $customer = explode("-", $_GET['customer_pass']);
        $GLOBALS['username'] = $customer[0];
        $GLOBALS['password'] = $customer[1];

    	$data = array(
    		"username" => $GLOBALS['username'],
    		"password" => $GLOBALS['password'],
    		"customer_id" => $_GET['customer_id'],
    		"customer_acc" => $_GET['customer_acc'],
    		"customer_name" => $_GET['customer_name'],
    	);		
    	$json = json_encode($data);

    	$url = $GLOBALS['baseUrl'] .'account/save';

        $accessToken = $GLOBALS['Authorization'];
        $result = self::requestToServer($url, $json, $accessToken);
        return $result;
        
	}

	public function DeleteAccount(){
        if ($GLOBALS['status'] != "00"){
        
			return response()->json(
			[
				"status"  => $GLOBALS['status'],
				"message" => $GLOBALS['message'],
			]);
                
        }
        $customer = explode("-", $_GET['customer_pass']);
        $GLOBALS['username'] = $customer[0];
        $GLOBALS['password'] = $customer[1];

    	$data = array(
    		"username" => $GLOBALS['username'],
    		"password" => $GLOBALS['password'],
    		"customer_id" => $_GET['customer_id'],
    		"customer_acc" => $_GET['customer_acc'],
    	);		
    	$json = json_encode($data);

    	$url = $GLOBALS['baseUrl'] .'account/delete';

        $accessToken = $GLOBALS['Authorization'];
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
    	print_r($output);

	}

}