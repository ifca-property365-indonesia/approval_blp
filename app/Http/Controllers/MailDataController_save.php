<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MailDataController extends Controller
{
    public function receive(Request $request)
    {
        $dataFromExternal = $request->all();
        $module = $request->module;
        $controllerName = 'App\\Http\\Controllers\\' . $module . 'Controller';
        $methodName = 'processModule';
        $controllerInstance = new $controllerName();
        $result = $controllerInstance->$methodName($dataFromExternal);
        return $result;
    }

    public function processData($module='', $status='', $encrypt='')
    {
        Artisan::call('config:cache');
        Artisan::call('cache:clear');
        Cache::flush();
        $cacheKey = 'processData_' . $encrypt;

        // Check if the data is already cached
        if (Cache::has($cacheKey)) {
            // If cached data exists, clear it
            Cache::forget($cacheKey);
        }

        Log::info('Starting database query execution for processData');
        $data = Crypt::decrypt($encrypt);

        $msg = " ";
        $msg1 = " ";
        $notif = " ";
        $st = " ";
        $image = " ";

        Log::info('Decrypted data: ' . json_encode($data));

        $where = array(
            'doc_no'        => $data["doc_no"],
            'entity_cd'     => $data["entity_cd"],
            'level_no'      => $data["level_no"],
            'type'          => $data["type"],
            'module'        => $data["type_module"],
        );

        $query = DB::connection('BLP')
        ->table('mgr.cb_cash_request_appr')
        ->where($where)
        ->whereIn('status', array("A", "R", "C"))
        ->get();

        Log::info('First query result: ' . json_encode($query));

        if (count($query)>0) {
            $msg = 'You Have Already Made a Request to '.$data["text"].' No. '.$data["doc_no"] ;
            $notif = 'Restricted !';
            $st  = 'OK';
            $image = "double_approve.png";
            $msg1 = array(
                "Pesan" => $msg,
                "St" => $st,
                "notif" => $notif,
                "image" => $image
            );
            return view("email.after", $msg1);
        } else {
            $where2 = array(
                'doc_no'        => $data["doc_no"],
                'status'        => 'P',
                'entity_cd'     => $data["entity_cd"],
                'level_no'      => $data["level_no"],
                'type'          => $data["type"],
                'module'        => $data["type_module"],
            );

            $query2 = DB::connection('BLP')
            ->table('mgr.cb_cash_request_appr')
            ->where($where2)
            ->get();

            Log::info('Second query result: ' . json_encode($query2));

            if (count($query2) == 0) {
                $msg = 'There is no '.$data["text"].' with No. '.$data["doc_no"] ;
                $notif = 'Restricted !';
                $st  = 'OK';
                $image = "double_approve.png";
                $msg1 = array(
                    "Pesan" => $msg,
                    "St" => $st,
                    "notif" => $notif,
                    "image" => $image
                );
                return view("email.after", $msg1);
            } else {
                $name   = " ";
                $bgcolor = " ";
                $valuebt  = " ";
                if ($status == 'A') {
                    $name   = 'Approval';
                    $bgcolor = '#40de1d';
                    $valuebt  = 'Approve';
                } else if ($status == 'R') {
                    $name   = 'Revision';
                    $bgcolor = '#f4bd0e';
                    $valuebt  = 'Revise';
                } else {
                    $name   = 'Cancellation';
                    $bgcolor = '#e85347';
                    $valuebt  = 'Cancel';
                }
                $dataArray = Crypt::decrypt($encrypt);
                $data = array(
                    "status"    => $status,
                    "doc_no"    => $dataArray["doc_no"],
                    "email"     => $dataArray["email_address"],
                    "module"    => $module,
                    "encrypt"   => $encrypt,
                    "name"      => $name,
                    "bgcolor"   => $bgcolor,
                    "valuebt"   => $valuebt
                );
                if ( $dataArray["type"] == "Q" &&  $dataArray["type_module"] == 'PO' &&  ($dataArray["level_no"] == '1' || $dataArray["level_no"] == 1))
                {
                    return view('email/por/passcheckwithremark', $data);
                } else {
                    return view('email/passcheckwithremark', $data);
                }
                Artisan::call('config:cache');
            }
        }
    }

    public function getAccess(Request $request)
    {
        $dataFromExternal = $request->all();
        // Extracting parameters from the request
        $status = $request->status;
        $doc_no = $request->doc_no;
        $encrypt = $request->encrypt;
        $module = $request->module;
        $reason = $request->reason ?: 'no note'; // Default reason if empty
        try {
            $controllerName = 'App\\Http\\Controllers\\' . $module . 'Controller';
            $methodName = 'update';
            $arguments = [$status, $encrypt, $reason];
            $controllerInstance = new $controllerName();
            $result = call_user_func_array([$controllerInstance, $methodName], $arguments);
            return $result;

        } catch (\Exception $e) {
            \Log::error('Error in getAccess method: ' . $e->getMessage());
            $msg1 = array(
                "Pesan" => $e->getMessage(),
                "image" => "reject.png"
            );
            return view("email.after", $msg1);
        }
    }
}
