<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PPOBController extends Controller
{

	function __construct(){
		$this->middleware('snapAccess', ['only' => ['CheckAccount','Payment']]);
	}

	public function CheckAccount(){
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
    		"customer_acc" => $_GET['customer_acc'],
    		"idpel" => $_GET['idpel'],
    		"nominal" => $_GET['nominal'],
    		"admin" => $_GET['admin'],
    	);		
    	$json = json_encode($data);

    	$url = $GLOBALS['baseUrl'] .'ppob/check';
        $accessToken = $GLOBALS['Authorization'];
        //$accessToken = self::getToken();

        $result = self::requestToServer($url, $json, $accessToken);
        return $result;

	}

	public function Payment(){
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

		$method = $_GET['method'];
		$produk = $_GET['produk'];
		$idpel = $_GET['idpel'];
		$ref = $GLOBALS['ExternalID'];
		$nominal = $_GET['nominal'];
		$admin = $_GET['admin'];
		$jenis = $_GET['jenis'];
		$periode = $_GET['periode'];
		$phone = $_GET['phone'];
		$appType = $_GET['app_type'];
        $period = date("Y-m");

    	$data = array(
    		"username" => $GLOBALS['username'],
    		"password" => $GLOBALS['password'],
    		"customer_acc" => $_GET['customer_acc'],
    		"customer_name" => $_GET['customer_name'],
    		"idpel" => $idpel,
    		"nominal" => $nominal,
    		"admin" => $admin,
    		"produk" => $produk,
    		"nama_produk" => $_GET['nama_produk'],
    		"method" => $method,
    		"app_type" => $appType,
    	);		
    	$json = json_encode($data);

        //Checking account dan saldo...
    	$url = $GLOBALS['baseUrl'] .'ppob/payment';
        $accessToken = $GLOBALS['Authorization'];
        $result = self::requestToServer($url, $json, $accessToken);
    	$respon = json_decode($result, true);
        if ($respon['status'] != "00"){
			return response()->json(
			[
				"status"  => $respon['status'],
				"message" => $respon['message'],
			]);
        }

        //Inquiry ato Posting PPOB...
        $request = self::requestPayment($method, $produk, $idpel, $ref, $nominal, $jenis, $periode, $phone);
    	$result = json_decode($request, true);
    	if ($result['rc'] == "00"){
            if ($method == "bayar"){
           		$insTrans = DB::update('insert into ppob_transaction (client_id, trans_no, produk, id_pel, nm_pel, credit, periode) values (?,?,?,?,?,?,?)',
           		    array($GLOBALS['clientPPOB'], $GLOBALS['ExternalID'], $produk, $idpel, $_GET['customer_name'], $nominal+$admin, $period));
                
            }
			return response()->json(
			[
				"status"  => $result['rc'],
				"message" => $result['status'],
				"fee"     => 0,
				"data"    => $request,
			]);
    	}

        //Jika posting dan tidak sukses, lakukan refund...
        if ($method == "bayar"){
        	$data = array(
        		"username" => $GLOBALS['username'],
        		"password" => $GLOBALS['password'],
        		"customer_acc" => $_GET['customer_acc'],
        		"trans_no" => $respon['trans_no'],
        	);		
        	$json = json_encode($data);
        	$url = $GLOBALS['baseUrl'] .'ppob/refund';
            $refund = self::requestToServer($url, $json, $GLOBALS['Authorization']);
        }
        
		return response()->json(
		[
			"status"  => $result['rc'],
			"message" => $result['status'],
		]);
    	
	}

	public function requestPayment($method, $produk, $idpel, $ref, $nominal, $jenis, $periode, $phone){
	    
    	$json = '{"method":"' .$method. '","uid":"' .env('PPOB_USER'). '","pin":"' .env('PPOB_PIN'). '","produk":"' .$produk. '","idpel":"' .$idpel. '","ref1":"' .$ref. '","nominal":"' .$nominal. '"}';
    	if($jenis == "topup" || $jenis == "paid") {
        	$json = '{"method":"' .$method. '","uid":"' .env('PPOB_USER'). '","pin":"' .env('PPOB_PIN'). '","produk":"' .$produk. '","idpel":"' .$idpel. '","ref1":"' .$ref. '"}';
    	}
    	if ($produk == "ASRBPJSKS"){
    	    //BPJS Kesehatan
    	    if ($method == "bayar"){
            	$json = '{"method":"' .$method. '","uid":"' .env('PPOB_USER'). '","pin":"' .env('PPOB_PIN'). '","produk":"' .$produk. '","idpel":"' .$idpel. '","ref1":"' .$ref. 
            	    '","periode":"' .$periode. '","hp":"' .$phone.   '"}';
    	    }else{
            	$json = '{"method":"' .$method. '","uid":"' .env('PPOB_USER'). '","pin":"' .env('PPOB_PIN'). '","produk":"' .$produk. '","idpel":"' .$idpel. '","ref1":"' .$ref. 
            	    '","periode":"' .$periode. '"}';
    	    }
    	}

        $url = "https://rajabiller.fastpay.co.id/transaksi/api_json.php";
    	$ch  = curl_init();
    	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    	curl_setopt($ch, CURLOPT_URL, $url);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
    	$output = curl_exec($ch);
    	curl_close($ch);
    	return $output;
    	
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
    	return $output;

	}

}