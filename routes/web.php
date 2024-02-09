<?php
// ini_set('memory_liit', '-1');
// ini_Set('maximum_execuion_time', '1000');
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailReportController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\MigrationController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\TestCurl;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\EmailChannelController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportManagementController;
use App\Models\Transaction;
use App\Models\Enrollment;
use App\Models\PendingEmails;
use App\Http\Middleware\EnsureTokenIsValid;
use App\Http\Middleware\HSTS;
use App\Http\Middleware\HttpRedirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\TransactionMigrationService as TMS;

//use Artisan;
//AuthController

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

Route::get('testing', function(){
    $transactions = Transaction::all();

    dd($transactions);
});

Route::get('/', function () {
//     Artisan::call('migrate',
//  array(
//    '--path' => 'database/migrations',
//    ));
 return "Middleware is running on Badh... Guy system and Uc King: " . now() ;
});

//total dumped
//staging done
// blank membership

//Route::middleware([HttpRedirect::class])->group(function () {
Route::post('send-mail-v2', [EmailChannelController::class, 'channelMail']); //half done
Route::resource('runcron2', MigrationController::class); //half done
Route::get('whoami', [EnrollmentController::class, 'whoAmI']); //done
Route::get('mid', [EnrollmentController::class, 'whoAmI2']); //done
Route::get('staged-dumped', [NotificationController::class, 'staged_dumped']);
Route::get('blank-membership', [NotificationController::class, 'blank_membership']);
Route::get('staging-done', [NotificationController::class, 'staging_done']);
Route::get('get-customer-id', [EnrollmentController::class, 'getCustomerId']);
Route::get('stran2', function(){
	// return TransactionMigrationService::migrateTransaction2();
	return TMS::migrateTransaction2();
});
Route::resource('run-stats', StatsController::class);

Route::get('cron_id', function(Request $request){

	$transactions = Transaction::where('status', 0)->get();
	foreach($transactions as $transaction){
		Transaction::where('id', $transaction->id)->update(['cron_id'=>rand(1, $request->limit)]);
	}
});

Route::get('runcronid/{cron_id}', function($cron_id){
	return TMS::migrateTransactionCron($cron_id
	);
});

Route::get('allow_me', function(){

    return view('stats.allow-me');

});



Route::post('/allow_me', function(Request $request){

    if($request->access == "LSLonlyPass"){

        $request->session()->put('is_allowed', true);

        return json_encode(array('url'=> url('run-stats')));

    }else{

        return redirect('/allow_me');

    }

});

Route::get('/customer_count', function(Request $request){
            $unique_count = DB::table($request->table)

                 ->select($request->column)->distinct()

                 ->get();
				 echo "Total customers :  <br> unique customers :".  $unique_count->count();

});


Route::get('/transactions_count', function(Request $request){
            $unique_count = DB::table($request->table)->where('transaction_date', $request->date);
				 echo "Total customers :  <br> unique customers :".  $unique_count->count();

});


Route::get('/customer_count2', function(Request $request){
            $unique_count = DB::table('enrollments')->select(DB::raw('loyalty_number FROM enrollments GROUP BY loyalty_number'))->get();
			echo $unique_count->count();

});

//SELECT loyalty_number FROM enrollments GROUP BY loyalty_number
//});
// Route::middleware([EnsureTokenIsValid::class])->group(function () {
//     Route::get('whoamii', [EnrollmentController::class, 'whoAmI']);
//     Route::resource('runcronn', MigrationController::class);
//     Route::get('email-log', [EmailReportController::class, 'index']);
//     Route::resource('run-stats', StatsController::class);
//     Route::get('report-gen', [ReportManagementController::class, 'index']);
// });
