<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SNAPCheckAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $register = false;
		$cari = "register";
		if(preg_match("/$cari/i", $_SERVER['REQUEST_URI'])) {
            $register = true;
        }
        
        $id = $request->header('PartnerID');
        $partner = explode("-", $id);
        $GLOBALS['ClientID'] = $partner[0];
        $GLOBALS['PartnerID'] = $partner[1];
        $GLOBALS['Scramble'] = $partner[2];
        $GLOBALS['Authorization'] = $request->header('Authorization');
        $GLOBALS['TimeStamp'] = $request->header('TimeStamp');
        $GLOBALS['ExternalID'] = $request->header('ExternalID');
        $GLOBALS['Signature'] = $request->header('Signature');
        
        $GLOBALS['status'] = "01";
        $GLOBALS['message'] = "Mobile banking ini belum terdaftar.";
        $GLOBALS['baseUrl'] = "";
        $GLOBALS['endPointURL'] = "";
        $GLOBALS['hashKey'] = "";
        $GLOBALS['clientSecret'] = "";
        $GLOBALS['prefix'] = "";
        $GLOBALS['maxTransfer'] = 0;
        $GLOBALS['X-PARTNER-ID'] = "";
        $GLOBALS['clientPPOB'] = "";
        $GLOBALS['keyPPOB'] = "";

        $statusUser = "off";
        $maxTransfer = 0;
   		$qry = DB::select("select * from mob_client where client_code='$partner[0]'");
		foreach ($qry as $qrys){
			$GLOBALS['baseUrl'] = $qrys->base_url;
			$GLOBALS['clientSecret'] = $qrys->client_secret;
			$GLOBALS['hashKey'] = $qrys->hash_key;
            $GLOBALS['X-PARTNER-ID'] = $qrys->client_id;
            $prefix = $qrys->prefix;
            $prefix_dev = $qrys->prefix_dev;
            $GLOBALS['maxTransfer'] = $qrys->max_transfer;
		}

   		$qry = DB::select("select * from mob_user where client_id='$partner[0]' and partner_id='$partner[1]'");
		foreach ($qry as $qrys){
			$statusUser = $qrys->status;
		}

   		$qry = DB::select("select * from ppob_client where client_code='$partner[0]'");
		foreach ($qry as $qrys){
			$GLOBALS['clientPPOB'] = $qrys->client_id;
			$GLOBALS['keyPPOB'] = $qrys->client_key;
		}
		
		if (env('APP_POLICY') == "elink"){
            $GLOBALS['prefix'] = $prefix;
		}else if (env('APP_POLICY') == "development"){
            $GLOBALS['prefix'] = $prefix_dev;
		}else if (env('APP_POLICY') == "production"){
            $GLOBALS['prefix'] = $prefix;
		}
        if ($statusUser == "active"){ $GLOBALS['status'] = "00"; }   
        if ($register){ $GLOBALS['status'] = "00"; }   
        self::checkSignature();

		$_GET = json_decode($request->getContent(), true);
		return $next($request);

    }
    

    public function checkSignature(){

		$join = 'Partner-ID:'. $GLOBALS['PartnerID'] .'<!>External-ID:'. $GLOBALS['ExternalID'] .'<!>Time-Stamp:'. $GLOBALS['TimeStamp'];
		$signature = md5($join);
        if ($signature != $GLOBALS['Signature']){
            $GLOBALS['status'] = "02";
            $GLOBALS['message'] = "Invalid Signature.";
            $strData = 'partnerID:'. $GLOBALS['PartnerID'] .'|externalID:'. $GLOBALS['ExternalID'] .'|TimeStamp:'. $GLOBALS['TimeStamp'];
       		$requestMB = DB::update('insert into mob_access (client_id, partner_id, url, request, response, access_status) values (?,?,?,?,?,?)',
       		    array($GLOBALS['ClientID'], $GLOBALS['PartnerID'], "Signature invalid !", $GLOBALS['Signature'], $strData, $GLOBALS['status']));
        }

    }
}
