<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransferLPDController extends Controller
{

	function __construct(){
	    
	    $GLOBALS['respStatus'] = "";
	    $GLOBALS['respMessage'] = "";
	    $GLOBALS['respToName'] = "";
	    
		$this->middleware('snapAccess', ['only' => ['CheckAccount', 'InquiryTransfer', 'PostingTransfer']]);
		
	}

	public function CheckAccount(){
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
    		"customer_acc" => $_GET['customer_acc'],
    	);		
    	$json = json_encode($data);

    	$url = $GLOBALS['baseUrl'] .'transfer-lpd/check-account';

        $accessToken = $GLOBALS['Authorization'];
        $result = self::requestToServer($url, $json, $accessToken);
        return $result;
        
	}

	public function InquiryTransfer(){
	    
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

    	$join = "username:" .$GLOBALS['username'] ."|password:". $GLOBALS['password'] ."|timestamp:". $GLOBALS['TimeStamp'];
    	$body = hash("sha256", $join);
    	$strToSign = $_GET['customer_acc'] ."|". $_GET['to_acc'] ."|". $_GET['amount'] ."|". $body. "|" .$GLOBALS['TimeStamp'];
    	$hashCode = base64_encode(hash_hmac("sha512", $strToSign, $GLOBALS['clientSecret'], true));

    	$data = array(
    		"username" => $GLOBALS['username'],
    		"password" => $GLOBALS['password'],
    		"customer_acc" => $_GET['customer_acc'],
    		"to_acc" => $_GET['to_acc'],
    		"amount" => $_GET['amount'],
    		"remark" => $_GET['remark'],
    		"hash_code" => $hashCode,
    	);		
    	$json = json_encode($data);

    	$url = $GLOBALS['baseUrl'] .'transfer-lpd/inquiryTransfer';

        $accessToken = self::getToken();
        $result = self::requestToServer($url, $json, $accessToken);
   		$requestMB = DB::update('insert into mob_transfer (account_no, account_name, bank_code, destination_no, destination_name, amount, reference_no, rc, description, request) values (?,?,?,?,?,?,?,?,?,?)',
   		    array($_GET['customer_acc'], '', 'LPD', $_GET['to_acc'], $GLOBALS['respToName'], $_GET['amount'], $GLOBALS['ExternalID'], $GLOBALS['respStatus'], $GLOBALS['respMessage'], 'inquiryAR'));
        
        return $result;
        
	}

	public function PostingTransfer(){
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

    	$join = "username:" .$GLOBALS['username'] ."|password:". $GLOBALS['password'] ."|timestamp:". $GLOBALS['TimeStamp'];
    	$body = hash("sha256", $join);
    	$strToSign = $_GET['customer_acc'] ."|". $_GET['to_acc'] ."|". $_GET['amount'] ."|". $body. "|" .$GLOBALS['TimeStamp'];
    	$hashCode = base64_encode(hash_hmac("sha512", $strToSign, $GLOBALS['clientSecret'], true));

    	$data = array(
    		"username" => $GLOBALS['username'],
    		"password" => $GLOBALS['password'],
    		"customer_acc" => $_GET['customer_acc'],
    		"customer_name" => $_GET['customer_name'],
    		"to_acc" => $_GET['to_acc'],
    		"amount" => $_GET['amount'],
    		"remark" => $_GET['remark'],
    		"hash_code" => $hashCode,
    	);		
    	$json = json_encode($data);

    	$url = $GLOBALS['baseUrl'] .'transfer-lpd/postingTransfer';

        $accessToken = self::getToken();
        $result = self::requestToServer($url, $json, $accessToken);
   		$requestMB = DB::update('insert into mob_transfer (account_no, account_name, bank_code, destination_no, destination_name, amount, reference_no, rc, description, request) values (?,?,?,?,?,?,?,?,?,?)',
   		    array($_GET['customer_acc'], $_GET['customer_name'], 'LPD', $_GET['to_acc'], $GLOBALS['respToName'], $_GET['amount'], $GLOBALS['ExternalID'], $GLOBALS['respStatus'], $GLOBALS['respMessage'], 'postingAR'));
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
    	$result = json_decode($output, true);
	    $GLOBALS['respStatus'] = $result['status'];
	    $GLOBALS['respMessage'] = $result['message'];
	    $GLOBALS['respToName'] = isset($result['to_name']) ? $result['to_name'] : '';
    	
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