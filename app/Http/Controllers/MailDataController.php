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
        Log::info('Starting database query execution for processData');

        // Dekripsi hanya sekali
        $data = Crypt::decrypt($encrypt);
        Log::info('Decrypted data: ' . json_encode($data));

        // Cek cache sebelum query ke database
        $where = [
            'doc_no'    => $data["doc_no"],
            'entity_cd' => $data["entity_cd"],
            'level_no'  => $data["level_no"],
            'type'      => $data["type"],
            'module'    => $data["type_module"],
        ];

        $exists = DB::connection('BLP')
            ->table('mgr.cb_cash_request_appr')
            ->where($where)
            ->whereIn('status', ["A", "R", "C"])
            ->exists(); // Lebih efisien daripada count()

        if ($exists) {
            $msg1 = [
                "Pesan" => 'You Have Already Made a Request to '.$data["text"].' No. '.$data["doc_no"],
                "St" => 'OK',
                "notif" => 'Restricted !',
                "image" => "double_approve.png"
            ];
            return view("email.after", $msg1);
        }

        // Query kedua
        $where2 = array_merge($where, ['status' => 'P']);

        $exists2 = DB::connection('BLP')
        ->table('mgr.cb_cash_request_appr')
        ->where($where2)
        ->exists();

        if (!$exists2) {
            $msg1 = [
                "Pesan" => 'There is no '.$data["text"].' with No. '.$data["doc_no"],
                "St" => 'OK',
                "notif" => 'Restricted !',
                "image" => "double_approve.png"
            ];
            return view("email.after", $msg1);
        }

        // Tentukan status dan parameter untuk tampilan
        $statusOptions = [
            'A' => ['Approval', '#40de1d', 'Approve'],
            'R' => ['Revision', '#f4bd0e', 'Revise'],
            'C' => ['Cancellation', '#e85347', 'Cancel']
        ];

        $statusData = $statusOptions[$status] ?? $statusOptions['C'];

        $dataView = [
            "status"    => $status,
            "doc_no"    => $data["doc_no"],
            "email"     => $data["email_address"],
            "module"    => $module,
            "encrypt"   => $encrypt,
            "name"      => $statusData[0],
            "bgcolor"   => $statusData[1],
            "valuebt"   => $statusData[2]
        ];

        if ($data["type"] == "Q" && $data["type_module"] == 'PO' && in_array($data["level_no"], ['1', 1])) {
            return view('email/por/passcheckwithremark', $dataView);
        } else {
            return view('email/passcheckwithremark', $dataView);
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
