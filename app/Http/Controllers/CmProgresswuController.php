<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Mail\SendCmProgresswuMail;
use App\Services\SmtpConfigService;
use PDO;
use DateTime;

class CmProgresswuController extends Controller
{
    public function Mail(Request $request)
    {
        $callback = [
            'data'  => null,
            'Error' => false,
            'Pesan' => '',
            'Status'=> 200
        ];

        $lockHandle = null; // ✅ TAMBAHAN

        try {
            // ================= DATA PREP =================
            $curr_progress = number_format($request->curr_progress, 2, '.', ',');
            $prev_progress = number_format($request->prev_progress, 2, '.', ',');
            $amount = number_format($request->amount, 2, '.', ',');
            $prev_progress_amt = number_format($request->prev_progress_amt, 2, '.', ',');

            $approve_data = explode('; ', $request->approve_exist);
            $url_data  = explode(',', $request->url_file);
            $file_data = explode(',', $request->file_name);

            $dataArray = [
                'sender'            => $request->sender,
                'entity_cd'         => $request->entity_cd,
                'project_no'        => $request->project_no,
                'doc_no'            => $request->doc_no,
                'entity_name'       => $request->entity_name,
                'descs'             => $request->descs,
                'user_name'         => $request->user_name,
                'progress_no'       => $request->progress_no,
                'surveyor'          => $request->surveyor,
                'doc_link'          => $request->url_link,
                'curr_cd'           => $request->curr_cd,
                'contract_desc'     => $request->contract_desc,
                'curr_progress'     => $curr_progress,
                'approve_seq'       => $request->approve_seq,
                'amount'            => $amount,
                'prev_progress'     => $prev_progress,
                'prev_progress_amt' => $prev_progress_amt,
                'contract_no'       => $request->contract_no,
                'module'            => $request->module,
                'approve_list'      => $approve_data,
                'url_file'          => $url_data,
                'file_name'         => $file_data,
                'clarify_user'      => $request->clarify_user,
                'clarify_email'     => $request->clarify_email,
                'sender_addr'       => $request->sender_addr,
                'body'              => "Please approve Contract Progress No. {$request->doc_no} for {$request->descs}",
                'subject'           => "Need Approval for Contract Progress No. {$request->doc_no}",
            ];

            $encryptedData = Crypt::encrypt([
                'entity_cd'     => $request->entity_cd,
                'project_no'    => $request->project_no,
                'email_address' => $request->email_addr,
                'level_no'      => $request->level_no,
                'doc_no'        => $request->doc_no,
                'ref_no'        => $request->ref_no,
                'usergroup'     => $request->usergroup,
                'user_id'       => $request->user_id,
                'supervisor'    => $request->supervisor,
                'type'          => 'F',
                'type_module'   => 'CM',
                'text'          => 'Contract Progress'
            ]);

            $callback['data'] = [
                'payload'   => $dataArray,
                'encrypted' => $encryptedData
            ];

            // ================= EMAIL PROCESS =================
            $email = trim($request->email_addr); // ✅ TAMBAHAN (lebih aman)
            $approve_seq = $request->approve_seq;
            $entity_cd = $request->entity_cd;
            $doc_no = $request->doc_no;
            $level_no = $request->level_no;
            $entity_name = $request->entity_name;

            if (!empty($email)) {

                $cacheFile = "email_sent_{$approve_seq}_{$entity_cd}_{$doc_no}_{$level_no}.txt";
                $cacheFilePath = storage_path('app/mail_cache/send_cmprogresswu/' . date('Ymd') . '/' . $cacheFile);
                $cacheDirectory = dirname($cacheFilePath);

                if (!file_exists($cacheDirectory)) {
                    mkdir($cacheDirectory, 0755, true);
                }

                $lockFile = $cacheFilePath . '.lock';
                $lockHandle = fopen($lockFile, 'w'); // ✅ TAMBAHAN

                if (!flock($lockHandle, LOCK_EX)) {
                    throw new Exception('Failed to acquire lock');
                }

                if (!file_exists($cacheFilePath)) {

                    Mail::to($email)
                        ->send(new SendCmProgresswuMail(
                            $encryptedData,
                            $dataArray,
                            'IFCA SOFTWARE - ' . $entity_name
                        ));

                    // ✅ TAMBAHAN: pastikan email benar-benar terkirim
                    if (count(Mail::failures()) > 0) {
                        throw new Exception('Email gagal dikirim');
                    }

                    // ===================== UPDATE DB =====================

                    $updated = DB::connection('BLP')
                        ->table('mgr.cb_cash_request_appr')
                        ->where('doc_no', $doc_no)
                        ->where('level_no', $level_no)
                        ->where('approve_seq', $approve_seq)
                        ->where('entity_cd', $entity_cd)
                        ->where(function ($q) {
                            $q->whereNull('sent_mail')
                            ->orWhere('sent_mail', '<>', 'Y');
                        })
                        ->update([
                            'sent_mail'      => 'Y',
                            'sent_mail_date' => DB::raw('GETDATE()'),
                        ]);

                    if ($updated > 0) {

                        // ===================== INSERT LOG AZURE =====================
                        DB::connection('BLP')->statement("
                            EXEC mgr.x_send_mail_approval_azure_ins
                                ?, ?, ?, ?, ?, ?, ?
                        ", [
                            $entity_cd,
                            $doc_no,
                            'F',
                            'CM',
                            $level_no,
                            $encryptedData,
                            'cmprogresswu'
                        ]);

                        Log::channel('sendmailapproval')
                            ->info("Approval UPDATED & logged: doc_no=$doc_no level=$level_no entity=$entity_cd");

                    } else {
                        // Idempotent: kemungkinan sudah diproses sebelumnya
                        Log::channel('sendmailapproval')
                            ->warning("Approval already processed: doc_no=$doc_no level=$level_no entity=$entity_cd");
                    }

                    file_put_contents($cacheFilePath, 'sent');

                    Log::channel('sendmailapproval')
                        ->info("Email CM Progress doc_no $doc_no Entity $entity_cd terkirim ke $email");

                    $callback['Pesan']  = "Email berhasil dikirim ke: $email";
                    $callback['Error']  = false;
                    $callback['Status'] = 200;

                } else {
                    $callback['Pesan']  = "Email sudah pernah dikirim ke: $email";
                    $callback['Error']  = false;
                    $callback['Status'] = 201;
                }
            } else {
                throw new Exception('No email address provided');
            }

        } catch (\Exception $e) {
            Log::channel('sendmail')->error("Gagal mengirim email: " . $e->getMessage());

            $callback['Pesan']  = $e->getMessage();
            $callback['Error']  = true;
            $callback['Status'] = 500;

        } finally {
            // ✅ TAMBAHAN PALING PENTING
            if ($lockHandle) {
                flock($lockHandle, LOCK_UN);
                fclose($lockHandle);
            }
        }

        return response()->json($callback, $callback['Status']);
    }

    public function processData($status='', $encrypt='')
    {

        $data = Crypt::decrypt($encrypt);
        Log::info('Decrypted data: ' . json_encode($data));

        $where = [
            'doc_no'        => $data["doc_no"],
            'entity_cd'     => $data["entity_cd"],
            'level_no'      => $data["level_no"],
            'type'          => $data["type"],
            'module'        => $data["type_module"],
        ];

        $exists = DB::connection('BLP')
        ->table('mgr.cb_cash_request_appr')
        ->where($where)
        ->whereIn('status', ["A", "R", "C"])
        ->exists();

        if ($exists) {
            $msg1 = [
                "Pesan" => 'You Have Already Made a Request to Contract Progress No. ' . $data["doc_no"],
                "St" => 'OK',
                "notif" => 'Restricted !',
                "image" => "double_approve.png"
            ];
            return view("email.after", $msg1);
        }

        $where2 = array_merge($where, ['status' => 'P']);
        

        $exists2 = DB::connection('BLP')
            ->table('mgr.cb_cash_request_appr')
            ->where($where2)
            ->exists();

        if (!$exists2) {
            $msg1 = [
                "Pesan" => 'There is no Contract Progress with No. ' . $data["doc_no"],
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
            "encrypt"   => $encrypt,
            "name"      => $statusData[0],
            "bgcolor"   => $statusData[1],
            "valuebt"   => $statusData[2]
        ];

        return view('email/cmprogresswu/passcheckwithremark', $dataView);
    }

    public function update(Request $request)
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

        if ($reasonget == '' || $reasonget == NULL) {
            $reason = '0';
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
        $sth = $pdo->prepare("SET NOCOUNT ON; EXEC mgr.xrl_send_mail_approval_cm_progress_with_unit ?, ?, ?, ?, ?, ?, ?, ?, ?, ?;");
        $sth->bindParam(1, $data["entity_cd"]);
        $sth->bindParam(2, $data["project_no"]);
        $sth->bindParam(3, $data["doc_no"]);
        $sth->bindParam(4, $data["ref_no"]);
        $sth->bindParam(5, $status);
        $sth->bindParam(6, $data["level_no"]);
        $sth->bindParam(7, $data["usergroup"]);
        $sth->bindParam(8, $data["user_id"]);
        $sth->bindParam(9, $data["supervisor"]);
        $sth->bindParam(10, $reason);
        $sth->execute();
        if ($sth == true) {
            $msg = "You Have Successfully ".$descstatus." the Contract Progress No. ".$data["doc_no"];
            $notif = $descstatus." !";
            $st = 'OK';
            $image = $imagestatus;
        } else {
            $msg = "You Failed to ".$descstatus." the Contract Progress No.".$data["doc_no"];
            $notif = 'Fail to '.$descstatus.' !';
            $st = 'OK';
            $image = "reject.png";
        }
        $msg1 = array(
            "Pesan" => $msg,
            "St" => $st,
            "notif" => $notif,
            "image" => $image
        );
        return view("email.after", $msg1);
    }
}