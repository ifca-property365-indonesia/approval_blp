<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Mail\SendLandMail;
use Exception;
use Carbon\Carbon;

class PlOverwriteController extends Controller
{
    public function index(Request $request)
    {
        $callback = [
            'data'  => null,
            'Error' => false,
            'Pesan' => '',
            'Status'=> 200
        ];

        try {
            $list_of_approve = explode('; ', $request->approve_exist);
            $approve_data = [];
            foreach ($list_of_approve as $approve) {
                $approve_data[] = $approve;
            }


            $dataArray = [
                'user_id'               => $request->user_id,
                'level_no'              => $request->level_no,
                'entity_cd'             => $request->entity_cd,
                'project_no'            => $request->project_no,
                'doc_no'                => $request->doc_no,
                'approve_seq'           => $request->approve_seq,
                'email_addr'            => $request->email_addr,
                'user_name'             => $request->user_name,
                'sender_addr'           => $request->sender_addr,
                'sender_name'           => $request->sender_name,
                'entity_name'           => $request->entity_name,
                'descs'                 => $request->descs,
                'approve_list'          => $approve_data,
                'amount'                => number_format((float)$request->amount, 2, '.', ','),
                'clarify_user'		    => $request->sender_name,
                'clarify_email'		    => $request->sender_addr,
                'subject'               => "Need Approval for ".$request->doc_no,
                'link'                  => 'ploverwrite',
            ];

            // dd($dataArray);

            $data2Encrypt = [
                'entity_cd'     => $request->entity_cd,
                'project_no'    => $request->project_no,
                'email_address' => $request->email_addr,
                'level_no'      => $request->level_no,
                'approve_seq'   => $request->approve_seq,
                'doc_no'        => $request->doc_no,
                'entity_name'   => $request->entity_name,
                'type'          => 'A',
                'type_module'   => 'PL',
                'text'          => 'Overwrite Actual Inventory',
            ];

            $encryptedData = Crypt::encrypt($data2Encrypt);

            // isi callback data secara konsisten
            $callback['data'] = [
                'payload'   => $dataArray,
                'encrypted' => $encryptedData
            ];

            // ====== Proses kirim email ======
            $email_address = strtolower($request->email_addr);
            $approve_seq = $request->approve_seq;
            $entity_cd   = $request->entity_cd;
            $doc_no      = $request->doc_no;
            $level_no    = $request->level_no;

            if (!empty($email_address)) {
                $cacheFile = 'email_sent_' . $approve_seq . '_' . $entity_cd . '_' . $doc_no . '_' . $level_no . '.txt';
                $cacheFilePath = storage_path('app/mail_cache/send_Land_PL_Overwrite/' . date('Ymd') . '/' . $cacheFile);
                $cacheDirectory = dirname($cacheFilePath);

                if (!file_exists($cacheDirectory)) {
                    mkdir($cacheDirectory, 0755, true);
                }

                $lockFile = $cacheFilePath . '.lock';
                $lockHandle = fopen($lockFile, 'w');
                if (!flock($lockHandle, LOCK_EX)) {
                    fclose($lockHandle);
                    throw new Exception('Failed to acquire lock');
                }

                if (!file_exists($cacheFilePath)) {
                    // kirim email
                    Mail::to($email_address)->send(new SendLandMail($encryptedData, $dataArray));

                    file_put_contents($cacheFilePath, 'sent');
                    Log::channel('sendmailapproval')->info("Email Overwrite Actual Inventory doc_no $doc_no Entity $entity_cd berhasil dikirim ke: $email_address");

                    $callback['Pesan'] = "Email berhasil dikirim ke: $email_address";
                    $callback['Error'] = false;
                    $callback['Status']= 200;

                } else {
                    Log::channel('sendmailapproval')->info("Email Overwrite Actual Inventory doc_no $doc_no Entity $entity_cd sudah pernah dikirim ke: $email_address");

                    $callback['Pesan'] = "Email sudah pernah dikirim ke: $email_address";
                    $callback['Error'] = false;
                    $callback['Status']= 201;
                }
            } else {
                Log::channel('sendmail')->warning("No email address provided for document $doc_no");

                $callback['Pesan'] = "No email address provided";
                $callback['Error'] = true;
                $callback['Status']= 400;
            }

        } catch (\Exception $e) {
            Log::channel('sendmail')->error("Gagal mengirim email: " . $e->getMessage());

            $callback['Pesan'] = "Gagal mengirim email: " . $e->getMessage();
            $callback['Error'] = true;
            $callback['Status']= 500;
        }

        return response()->json($callback, $callback['Status']);
    }

    public function processData($status='', $encrypt='')
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

        $query = 0;
        $query2 = 0;
        
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
            'approve_seq'   => $data["approve_seq"],
            'level_no'      => $data["level_no"],
            'type'          => $data["type"],
            'module'        => $data["type_module"],
        );

        $query = DB::connection('BLP')
        ->table('mgr.cb_cash_request_appr')
        ->where($where)
        ->whereIn('status', ["A", "R", "C"])
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
                "image" => $image,
                "entity_name"   => $data["entity_name"],
            );
            return view("email.remark.after", $msg1);
        } else {
            $where2 = array(
                'doc_no'        => $data["doc_no"],
                'status'        => 'P',
                'entity_cd'     => $data["entity_cd"],
                'approve_seq'   => $data["approve_seq"],
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
                    "image" => $image,
                    "entity_name"   => $data["entity_name"],
                );
                return view("email.remark.after", $msg1);
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
                $data = array(
                    "status"    => $status,
                    "doc_no"    => $data["doc_no"],
                    "email"     => $data["email_address"],
                    "encrypt"   => $encrypt,
                    "name"      => $name,
                    "bgcolor"   => $bgcolor,
                    "valuebt"   => $valuebt,
                    "link"      => "landsplitshgb",
                    "entity_name"   => $data["entity_name"],
                );
                return view('email/remark/passcheckwithremark', $data);
            }
        }
    }

    public function getaccess(Request $request)
    {
        $data = Crypt::decrypt($request->encrypt);

        $status = $request->status;

        $reasonget = $request->reason;

        $descstatus = " ";
        $imagestatus = " ";

        $msg = " ";
        $msg1 = " ";
        $notif = " ";
        $st = " ";
        $image = " ";

        if (trim($reasonget) == '') {
            $reason = 'no reason';
        } else {
            $reason = $reasonget;
        }

        if ($status == "A") {
            $descstatus = "Approved";
            $imagestatus = "approved.png";
        } else if ($status == "R") {
            $descstatus = "Revised";
            $imagestatus = "revise.png";
        } else {
            $descstatus = "Cancelled";
            $imagestatus = "reject.png";
        }
        $pdo = DB::connection('BLP')->getPdo();
        $sth = $pdo->prepare("EXEC mgr.xrl_send_mail_approval_pl_overwrite ?, ?, ?, ?, ?, ?");
        $success = $sth->execute([
            $data["entity_cd"],
            $data["project_no"],
            $data["doc_no"],
            $status,
            $data["level_no"],
            $reason
        ]);
        if ($success) {
            $msg = "You Have Successfully ".$descstatus." the Overwrite Actual Inventory No. ".$data["doc_no"];
            $notif = $descstatus." !";
            $st = 'OK';
            $image = $imagestatus;
        } else {
            $msg = "You Failed to ".$descstatus." the Overwrite Actual Inventory No.".$data["doc_no"];
            $notif = 'Fail to '.$descstatus.' !';
            $st = 'FAIL';
            $image = "reject.png";
        }
        $msg1 = array(
            "Pesan" => $msg,
            "St" => $st,
            "notif" => $notif,
            "image" => $image,
            'entity_name'   => $request->entity_name,
        );
        return view("email.remark.after", $msg1);
    }
}
