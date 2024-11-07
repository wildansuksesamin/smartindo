<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransferBankController extends Controller
{

	function __construct(){

	    $GLOBALS['respStatus'] = "";
	    $GLOBALS['respMessage'] = "";
	    $GLOBALS['respToName'] = "";
	    
		$this->middleware('snapAccess', ['only' => 
		['CheckAccount', 'InquiryTransfer', 'PostingTransfer']]);

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
        $endPointURL = "/v1.0/account-inquiry-external";
        
        $refNo = $GLOBALS['ExternalID'];
        $GLOBALS['BillNo'] = "EIS" .date("Hisdmy") .substr(uniqid(rand(), FALSE), -5);
        $bankCode = $_GET['bank_code'];
        $destiNo =  $_GET['to_acc'];
        $virtualNo = $GLOBALS['prefix'] .str_replace(".", "", $_GET['customer_acc']);
        $amount = "15000.00";
        $timeStamp = $GLOBALS['TimeStamp'];

        $bankCode = "129";
        $destiNo =  "0100205002244";
        $timeStamp = $GLOBALS['TimeStamp'];

    	$data = '{"partnerReferenceNo":"' .$refNo. '","beneficiaryBankCode":"' .$bankCode. '","beneficiaryAccountNo":"' .$destiNo. '","additionalInfo":{"accountNumber":"' 
    		.$virtualNo. '","amount":"' .$amount. '","dateTime":"' .$timeStamp. '"}}';
    	$datahash = hash("sha256", $data);
    	
        $tokenBPD = self::getTokenBPD();
    	$strToSigns = "POST". ":" .$endPointURL. ":" .$tokenBPD. ":" .$datahash. ":" .$timeStamp;
    	$signatureBPD = base64_encode(hash_hmac("sha512", $strToSigns, $GLOBALS['clientSecret'], true));
        $channelID = $tokenBPD .'<>'. $signatureBPD;
        
    	$data = array(
    		"username" => $GLOBALS['username'],
    		"password" => $GLOBALS['password'],
    		"customer_acc" => $virtualNo,
    		"to_acc" => $destiNo,
    		"bank_code" => $bankCode,
    		"amount" => $amount,
    		"data" => $data,
    	);		
    	$json = json_encode($data);

    	$url = $GLOBALS['baseUrl'] .'transfer-snap/check-account';
    	$accessToken = $GLOBALS['Authorization'];

        $result = self::requestToServer($url, $json, $accessToken, $channelID);
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
        $endPointURL = "/v1.0/account-inquiry-external";

        $refNo = $GLOBALS['ExternalID'];
        $GLOBALS['BillNo'] = "EIS" .date("Hisdmy") .substr(uniqid(rand(), FALSE), -5);
        $bankCode = $_GET['bank_code'];
        $destiNo =  $_GET['to_acc'];
        $virtualNo = $GLOBALS['prefix'] .str_replace(".", "", $_GET['customer_acc']);
        $amount = $_GET['amount'] .".00";
        $timeStamp = $GLOBALS['TimeStamp'];

        $bankCode = "129";
        $destiNo =  "0100205002244";
        $timeStamp = $GLOBALS['TimeStamp'];

    	$data = '{"partnerReferenceNo":"' .$refNo. '","beneficiaryBankCode":"' .$bankCode. '","beneficiaryAccountNo":"' .$destiNo. '","additionalInfo":{"accountNumber":"' 
    		.$virtualNo. '","amount":"' .$amount. '","dateTime":"' .$timeStamp. '"}}';
    	$datahash = hash("sha256", $data);
    	
        $tokenBPD = self::getTokenBPD();
    	$strToSigns = "POST". ":" .$endPointURL. ":" .$tokenBPD. ":" .$datahash. ":" .$timeStamp;
    	$signatureBPD = base64_encode(hash_hmac("sha512", $strToSigns, $GLOBALS['clientSecret'], true));
        $channelID = $tokenBPD .'<>'. $signatureBPD;
        
    	$data = array(
    		"username" => $GLOBALS['username'],
    		"password" => $GLOBALS['password'],
    		"customer_acc" => $virtualNo,
    		"to_acc" => $destiNo,
    		"bank_code" => $bankCode,
    		"amount" => $amount,
    		"data" => $data,
    	);		
    	$json = json_encode($data);

    	$url = $GLOBALS['baseUrl'] .'transfer-snap/inquiryTransfer';
    	$accessToken = $GLOBALS['Authorization'];
        $accessToken = self::getToken();

        $result = self::requestToServer($url, $json, $accessToken, $channelID);
   		$requestMB = DB::update('insert into mob_transfer (account_no, account_name, bank_code, destination_no, destination_name, amount, reference_no, rc, description, request) values (?,?,?,?,?,?,?,?,?,?)',
   		    array($virtualNo, '', $bankCode, $destiNo, $GLOBALS['respToName'], $amount, $GLOBALS['ExternalID'], $GLOBALS['respStatus'], $GLOBALS['respMessage'], 'inquirySNAP'));
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
        $endPointURL = "/v1.0/transfer-interbank";

        $refNo = $GLOBALS['ExternalID'];
        $GLOBALS['BillNo'] = "EIS" .date("Hisdmy") .substr(uniqid(rand(), FALSE), -5);
        $bankCode = $_GET['bank_code'];
        $destiNo =  $_GET['to_acc'];
        $destiName =  $_GET['to_name'];
        $remark =  $_GET['remark'];
        $virtualNo = $GLOBALS['prefix'] .str_replace(".", "", $_GET['customer_acc']);
        $virtualName =  $_GET['customer_name'];
        $amount = $_GET['amount'] .".00";
        $timeStamp = $GLOBALS['TimeStamp'];

        $bankCode = "129";
        $destiNo =  "0100205002244";
        $timeStamp = $GLOBALS['TimeStamp'];
        
    	$data = '{"partnerReferenceNo":"' .$refNo. '","beneficiaryAccountName":"' .$destiName. '","beneficiaryAccountNo":"' .$destiNo. '","beneficiaryBankCode":"' .$bankCode. '","sourceAccountNo":"' 
    	    .$virtualNo. '","transactionDate":"' .$timeStamp. '","amount":{"value":"' .$amount. '","currency":"IDR"},"additionalInfo":{"terminalType":"","terminalId":""}}';
    	$datahash = hash("sha256", $data);
    	
        $tokenBPD = self::getTokenBPD();
    	$strToSigns = "POST". ":" .$endPointURL. ":" .$tokenBPD. ":" .$datahash. ":" .$timeStamp;
    	$signatureBPD = base64_encode(hash_hmac("sha512", $strToSigns, $GLOBALS['clientSecret'], true));
        $channelID = $tokenBPD .'<!>'. $signatureBPD;
        
    	$data = array(
    		"username" => $GLOBALS['username'],
    		"password" => $GLOBALS['password'],
    		"customer_acc" => $virtualNo,
    		"customer_name" => $virtualName,
    		"to_acc" => $destiNo,
    		"to_name" => $destiName,
    		"bank_code" => $bankCode,
    		"amount" => $amount,
    		"remark" => $remark,
    		"data" => $data,
    	);		
    	$json = json_encode($data);

    	$url = $GLOBALS['baseUrl'] .'transfer-snap/postingTransfer';
    	$accessToken = $GLOBALS['Authorization'];
        $accessToken = self::getToken();

        $result = self::requestToServer($url, $json, $accessToken, $channelID);
   		$requestMB = DB::update('insert into mob_transfer (account_no, account_name, bank_code, destination_no, destination_name, amount, reference_no, rc, description, request) values (?,?,?,?,?,?,?,?,?,?)',
   		    array($virtualNo, $virtualName, $bankCode, $destiNo, $GLOBALS['respToName'], $amount, $GLOBALS['ExternalID'], $GLOBALS['respStatus'], $GLOBALS['respMessage'], 'postingSNAP'));
        return $result;
        
	}

	public function requestToServer($url, $data, $accessToken, $channelID){

        $partnerID = $GLOBALS['X-PARTNER-ID'];
        $timeStamp = $GLOBALS['TimeStamp'];

    	$join = "username:" .$GLOBALS['username'] ."#password:". $GLOBALS['password'];
    	$body = hash("sha256", $join);
    	$strToSign = $partnerID. "#" .$accessToken. "#" .$body. "#" .$timeStamp;
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
    		'X-EXTERNAL-ID:' . $GLOBALS['BillNo'],
    		'CHANNEL-ID:' .$channelID) 
    	);
    	
    	$output = curl_exec($ch); 
    	curl_close($ch);
   		$requestMB = DB::update('insert into mob_access (client_id, partner_id, url, request, response, access_status) values (?,?,?,?,?,?)',
   		    array($GLOBALS['ClientID'], $GLOBALS['PartnerID'], $url, $data, $output, $GLOBALS['status']));

    	$result = json_decode($output, true);
	    $GLOBALS['respStatus'] = $result['status'];
	    $GLOBALS['respMessage'] = $result['message'];
	    $GLOBALS['respToName'] = isset($result['destinationAccountName']) ? $result['destinationAccountName'] : '';
    	
    	print_r($output);

	}

	public function getTokenBPD(){

    	$url = $GLOBALS['baseUrl'] .'transfer-snap/access-token';
        $timeStamp = $GLOBALS['TimeStamp'];
        $partnerID = $GLOBALS['X-PARTNER-ID'];
        
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
    		'X-TIMESTAMP:' . $timeStamp,
    		'X-PARTNER-ID:' . $partnerID)
    	);
    		
    	$output = curl_exec($ch); 
    	curl_close($ch);
    	$result = json_decode($output, true);
    	return $result['accessToken'];

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