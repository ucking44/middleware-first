<?php

namespace App\Services;
//ini_set('memory_limit', '128M');
use App\Models\Enrollment;
use App\Models\EnrolReportLog;
use Illuminate\Mail\PendingMail;
use Illuminate\Support\Facades\Log;
use App\Services\EmailDispatcher;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Migrations\Migration;

class EnrolmentMigrationService extends MigrationService
{
    public static $username, $password;
    //public static $key = '!QAZXSW@#EDCVFR$';
    public static $key = '!QAZXSW@#EDCVFR$';
    public static $iv = '1234567891011121';
    public static $placeholders = array('$first_name', '$last_name', '$membership_id',  '$password', '$program', '$link','$pin');

    public function __construct()
    {
        //
    }

    public static function migrateEnrolments1() : string
    {
        //$this->key = '!QAZXSW@#EDCVFR$';
        //self::$username = 'diamondcustomer';
        self::$username = 'firstbank@1234';

        //self::$password = parent::string_encrypt('Di@mond10$#', self::$key,self::$iv);
        self::$password = parent::string_encrypt('ssw0rd20', self::$key,self::$iv);
        $data = [];

        $failure_count = 0;

        $success_count = 0;

        $company_details = new CompanyService(env('COMPANY_ID', 3));

        $company_details = $company_details->getCompanyDetails()->get();
        
        //dd($company_details);

        $pendingEnrolments = Enrollment::where('enrollment_status',0)->where('tries', '<=', 4)->select('first_name' ,'last_name', 'email','enrollment_status', 'tries', 'member_reference', 'branch_code', 'account_number', 'loyalty_number', 'pin', 'password')->limit(1000);//->get();//->where('tries', '<', 5);//->get();
//dd($pendingEnrolments->count());
       if ($pendingEnrolments->count()>0)
       {
            foreach($pendingEnrolments->get() as $pendingEnrolment)
            {
                //dd($pendingEnrolment);
                if(Enrollment::where('member_reference', $pendingEnrolment->member_reference)->where('enrollment_status',1))
                {
                    //CHECK MEMBER_REFERENCE EXISTS. IF YES, PUSH TO ACCOUNT_NUMBER TABLE ON PERX
    //$existingEnrolments = Enrollment::where('enrollment_status',0)->where('tries', '<=', 4)->where('member_reference', $pendingEnrolment->member_reference)
                          //->select('enrollment_status', 'tries', 'member_reference', 'account_number', 'loyalty_number');
//dd($existingEnrolments);
                    $accDataToPush = array(
                    'Company_username'=>self::$username,//$company_details->username? $company_details->username: 0,
                    'Company_password'=>self::$password,//$company_details->password?$company_details->password:0,
                    'Membership_ID'=>parent::string_encrypt($pendingEnrolment->loyalty_number, self::$key,self::$iv),
                    'Account_number'=>$pendingEnrolment->account_number,
                    'API_flag'=>'attachAcountNumber',

                    );

                   $repsonse2 = parent::pushToPERX(parent::$url, $accDataToPush, parent::$headerPayload);
                   //echo $repsonse2;
                   $mar = json_decode($repsonse2,true);
                   if($mar['status'] == 1001){
                       Enrollment::where('member_reference', $pendingEnrolment->member_reference)->update(['enrollment_status' => 1]);
                   }
                } 
                //else {
                    $pendingEnrolment->password ? $pendingEnrolment->password = $pendingEnrolment->password : $pendingEnrolment->password = '1234';

                    $pendingEnrolment->pin ? $pendingEnrolment->pin = $pendingEnrolment->pin : $pendingEnrolment->pin = '0000';

                    $pendingEnrolment->email ? $pendingEnrolment->email = $pendingEnrolment->email : $pendingEnrolment->email = $pendingEnrolment->loyalty_number . '@noemail.com';

                    $pendingEnrolment->branch_code ? $pendingEnrolment->branch_code = $pendingEnrolment->branch_code : $pendingEnrolment->branch_code = '000';

                    $arrayToPush = array(

                        'Company_username'=>self::$username,//$company_details->username? $company_details->username: 0,

                        'Company_password'=>self::$password,//$company_details->password?$company_details->password:0,

                        'Membership_ID'=>parent::string_encrypt($pendingEnrolment->loyalty_number, self::$key,self::$iv),

                        'Branch_code'=>$pendingEnrolment->branch_code,

                        'auto_gen_password'=>$pendingEnrolment->password?Hash::make($pendingEnrolment->password):Hash::make(1234),

                        'auto_gen_pin'=>$pendingEnrolment->pin?$pendingEnrolment->pin:'0000',

                        'API_flag'=>'enrol',
                    );

                    $resp = parent::pushToPERX(parent::$url, $arrayToPush, parent::$headerPayload);
//dd($resp);
                    if (parent::isJSON($resp))
                    {
                        $repsonse = json_decode($resp, true);

                    //dd($repsonse);

                        if ($repsonse)
                        {

                            EnrolReportLog::create([

                                'firstname' => $pendingEnrolment->first_name?$pendingEnrolment->first_name:'',

                                'lastname' => $pendingEnrolment->last_name?$pendingEnrolment->last_name:'',

                                'email' => $pendingEnrolment->email ? $pendingEnrolment->email : $pendingEnrolment->loyalty_number . '@noemail.com',

                                'customerid' => $pendingEnrolment->loyalty_number?$pendingEnrolment->loyalty_number:'undefined',

                                'branchcode' => $pendingEnrolment->branch_code?$pendingEnrolment->branch_code:'undefined',

                                'fileid' => 0,

                                'status_code' => $repsonse['status']?$repsonse['status']:'undefined',

                                'status_message' => $repsonse['Status_message']?$repsonse['Status_message']:'undefined'

                            ]);

                            if ($repsonse['status'] == 1001)
                            {
                                $success_count++;

                                //implement send mail

                                $values = array($pendingEnrolment->first_name, $pendingEnrolment->last_name, $pendingEnrolment->loyalty_number, $pendingEnrolment->password, parent::$program, parent::$link,$pendingEnrolment->pin);

                                EmailDispatcher::pendMails($pendingEnrolment->loyalty_number, "Customer Enrolment Notification", EmailDispatcher::buildEnrolmentTemplate(self::$placeholders, $values), 'services.loyalty@loyaltysolutionsnigeria.com');

                // SendNotificationService::sendMail($repsonse['Email_subject'], $repsonse['Email_body'], $repsonse['bcc_email_address']);
                      //SendNotificationService::sendMail('Customer Enrolment Notification', EmailDispatcher::buildEnrolmentTemplate(self::$placeholders, $values),'', $pendingEnrolment->email);

                                if( Enrollment::where('member_reference', $pendingEnrolment->member_reference)->update(['enrollment_status' => 1]))
                                {
                                    $data['message'] = 'data migrated ' . $success_count;
                                }
                                else{
                                    echo "...";
                                }

                            }
                            else {

                            if(Enrollment::where('member_reference', $pendingEnrolment->member_reference)->update(['tries' => $pendingEnrolment->tries + 1]))
                            {
                                //Log::info('failed to migrate '. $failure_count);
                                $data['message'] = 'data failed ' . $failure_count;
                            }
                            else{
                                echo "__ loced";
                            }

                            }

                        }
                        else{
                            $data['message'] = "no response from server";
                        }

                    }
                    else{
                        $data['format'] = "not json serialized";
                    }
        
           // }
}
        }
        else{
            $data['message'] = "no un-enroled customers found";
        }

        return json_encode($data);

    }

}

?>
