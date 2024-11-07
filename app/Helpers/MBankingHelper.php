<?php

namespace App\Helpers;
use Illuminate\Support\Facades\DB;

class MBankingHelper {

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