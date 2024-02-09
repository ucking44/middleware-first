<?php

namespace App\Http\Controllers;
use App\Models\Comapny;

use Illuminate\Http\Request;

class CustomerController extends Controller
{
    //public static $key = '!QAZXSW@#EDCVFR$';
    public static $iv = '1234567891011121';
    
    public function getCustomerId(Request $request)
    {
        $encriptionKey = Company::where('status', 1)->first();
        if($request->cif == '')
        {
            //
        }
        
    }
    
    
    
    public function stringDecript($var)
    {
        //
        
        //$encryptedHex = "b88b3bf464";
 
        $key = "!QAZXSW@#EDCVFR$";
         
        $iv = "1234567891011121";
         
        $decryptedText = openssl_decrypt(hex2bin($var), 'AES-128-CTR', $key, OPENSSL_RAW_DATA, $iv);
         
        return $decryptedText;
    }
}
