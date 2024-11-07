<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransfereLinkController extends Controller
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
				"message" => "Mobile Signature invalid !",
			]);
                
        }
        $customer = explode("-", $_GET['customer_pass']);
        $GLOBALS['username'] = $customer[0];
        $GLOBALS['password'] = $customer[1];

        $toAcc = $_GET['to_acc'];
        if ( strlen($toAcc) > 20){
            $toAcc = self::Decrypt($toAcc);
        }
    	$dateTime = date("YmdHis");
    	$customerAcc = $GLOBALS['prefix'] . str_replace(".", "", $_GET['customer_acc']);
    	$amount = "15000";
    	$data = array(
    		"username" => $GLOBALS['username'],
    		"password" => $GLOBALS['password'],
    		"customer_acc" => $customerAcc,
    		"to_acc" => $_GET['to_acc'],
    		"bank_code" => $_GET['bank_code'],
    		"amount" => $amount,
    		"date_time" => $dateTime,
    	);		
    	$json = json_encode($data);

    	$url = $GLOBALS['baseUrl'] .'transfer-bank/check-account';
        $accessToken = $GLOBALS['Authorization'];

        $datacrypto = $customerAcc.$amount.$dateTime.$GLOBALS['ExternalID'].$_GET['bank_code'].$toAcc.$GLOBALS['hashKey'];
    	$hashCode = hash('sha256', $datacrypto, false);
        $channelID = $hashCode .'<>'. $accessToken;
        
        $result = self::requestToServer($url, $json, $accessToken, $channelID);
        return $result;
        
	}


	public function InquiryTransfer(){
        if ($GLOBALS['status'] != "00"){
			return response()->json(
			[
				"status"  => "39",
				"message" => "Mobile Signature invalid !",
			]);
        }
        $customer = explode("-", $_GET['customer_pass']);
        $GLOBALS['username'] = $customer[0];
        $GLOBALS['password'] = $customer[1];

        $toAcc = $_GET['to_acc'];
        if ( strlen($toAcc) > 20){
            $toAcc = self::Decrypt($toAcc);
        }

    	$dateTime = date("YmdHis");
    	$customerAcc = $GLOBALS['prefix'] . str_replace(".", "", $_GET['customer_acc']);
    	$amount = $_GET['amount'];
    	$data = array(
    		"username" => $GLOBALS['username'],
    		"password" => $GLOBALS['password'],
    		"customer_acc" => $customerAcc,
    		"to_acc" => $_GET['to_acc'],
    		"bank_code" => $_GET['bank_code'],
    		"amount" => $amount,
    		"date_time" => $dateTime,
    	);		
    	$json = json_encode($data);

    	$url = $GLOBALS['baseUrl'] .'transfer-bank/inquiryTransfer';
        $accessToken = $GLOBALS['Authorization'];
        $accessToken = self::getToken();

        $datacrypto = $customerAcc.$amount.$dateTime.$GLOBALS['ExternalID'].$_GET['bank_code'].$toAcc.$GLOBALS['hashKey'];
    	$hashCode = hash('sha256', $datacrypto, false);
        $channelID = $hashCode .'<>'. $accessToken;
        $result = self::requestToServer($url, $json, $accessToken, $channelID);
   		$requestMB = DB::update('insert into mob_transfer (account_no, account_name, bank_code, destination_no, destination_name, amount, reference_no, rc, description, request) values (?,?,?,?,?,?,?,?,?,?)',
   		    array($customerAcc, '', $_GET['bank_code'], $_GET['to_acc'], $GLOBALS['respToName'], $_GET['amount'], $GLOBALS['ExternalID'], $GLOBALS['respStatus'], $GLOBALS['respMessage'], 'inquiryAB'));
        
        return $result;

	}

	public function PostingTransfer(){
        if ($GLOBALS['status'] != "00"){
        
			return response()->json(
			[
				"status"  => "39",
				"message" => "Mobile Signature invalid !",
			]);
                
        }

        $customer = explode("-", $_GET['customer_pass']);
        $GLOBALS['username'] = $customer[0];
        $GLOBALS['password'] = $customer[1];

        $toAcc = $_GET['to_acc'];
        if ( strlen($toAcc) > 20){
            $toAcc = self::Decrypt($toAcc);
        }

    	$dateTime = date("YmdHis");
    	$customerAcc = $GLOBALS['prefix'] . str_replace(".", "", $_GET['customer_acc']);
    	$amount = $_GET['amount'];
    	$data = array(
    		"username" => $GLOBALS['username'],
    		"password" => $GLOBALS['password'],
    		"customer_acc" => $customerAcc,
    		"customer_name" => $_GET['customer_name'],
    		"to_acc" => $_GET['to_acc'],
    		"to_name" => $_GET['to_name'],
    		"bank_code" => $_GET['bank_code'],
    		"amount" => $amount,
    		"date_time" => $dateTime,
    	);		
    	$json = json_encode($data);

    	$url = $GLOBALS['baseUrl'] .'transfer-bank/postingTransfer';
        $accessToken = $GLOBALS['Authorization'];
        $accessToken = self::getToken();

        $datacrypto = $customerAcc.$amount.$dateTime.$GLOBALS['ExternalID'].$_GET['bank_code'].$toAcc.$_GET['to_name'].$GLOBALS['hashKey'];
    	$hashCode = hash('sha256', $datacrypto, false);
        $channelID = $hashCode .'<>'. $accessToken;
        $result = self::requestToServer($url, $json, $accessToken, $channelID);
   		$requestMB = DB::update('insert into mob_transfer (account_no, account_name, bank_code, destination_no, destination_name, amount, reference_no, rc, description, request) values (?,?,?,?,?,?,?,?,?,?)',
   		    array($customerAcc, $_GET['customer_name'], $_GET['bank_code'], $_GET['to_acc'], $GLOBALS['respToName'], $_GET['amount'], $GLOBALS['ExternalID'], $GLOBALS['respStatus'], $GLOBALS['respMessage'], 'inquiryAB'));
        
        return $result;

	}

	public function requestToServer($url, $data, $accessToken, $channelID){

    	$join = "username:" .$GLOBALS['username'] ."#password:". $GLOBALS['password'];
    	$body = hash("sha256", $join);
    	$strToSign = $GLOBALS['PartnerID']. "#" .$accessToken. "#" .$body. "#" .$GLOBALS['TimeStamp'];
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
    		'X-TIMESTAMP:' . $GLOBALS['TimeStamp'],
    		'X-SIGNATURE:' . $signature,
    		'X-PARTNER-ID:' . $GLOBALS['PartnerID'],
    		'X-EXTERNAL-ID:' . $GLOBALS['ExternalID'],
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

	public function Decrypt($acak){

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
				if (strlen($time) > 50){
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
			$n = strrpos($unique, $no);
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

}