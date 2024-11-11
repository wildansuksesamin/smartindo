<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function(){
    return view('welcome');
}); 


Route::get('/test', function () {
    $resp = array(
        "status" => "oke",
        "environment" => "Laravel-Smartindo"
    );
    return $resp;
});

Route::get('/test-key', function () {
    $keyPrivate = file_get_contents("/home/giosoftech/public_html/smartindo/private_key.pem");
	$timeStamp = date("Y-m-d") .'T'. date("H:i:s") . '+08:00';

	$clientSecret = "zCQZC28e";
	$clientID   = "VA-213";

	$clientStamp = $clientID ."|". $timeStamp;
	openssl_sign($clientStamp, $signature, $keyPrivate, OPENSSL_ALGO_SHA256);
	$sign64 = base64_encode($signature);
    
    $resp = array(
        "status" => "oke",
        "signature" => $sign64
    );
    return $resp;
});
