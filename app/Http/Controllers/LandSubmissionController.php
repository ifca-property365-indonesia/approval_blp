<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Mail\LandSubmissionEmail;

class LandSubmissionController extends Controller
{
    public function mail(Request $request)
    {
        $list_of_urls = explode(';', $request->url_file);

        $url_data = [];
        foreach ($list_of_urls as $url) {
            $url_data[] = $url;
        }

        $list_of_files = explode(';', $request->file_name);

        $file_data = [];
        foreach ($list_of_files as $file) {
            $file_data[] = $file;
        }

        $list_of_type = explode(';', $request->type);

        $type_data = [];
        foreach ($list_of_type as $type) {
            $type_data[] = $type;
        }

        $list_of_owner = explode(';', $request->owner);

        $owner_data = [];
        foreach ($list_of_owner as $owner) {
            $owner_data[] = $owner;
        }

        $list_of_nop_no = explode(';', $request->nop_no);

        $nop_no_data = [];
        foreach ($list_of_nop_no as $nop_no) {
            $nop_no_data[] = $nop_no;
        }

        $list_of_request_amt = explode(';', $request->request_amt);
        
        $request_amt_data = [];
        foreach ($list_of_request_amt as $amt) {
            $formatted_amt = number_format((float)$amt, 2, '.', ',');
            $request_amt_data[] = $formatted_amt;
        }

        $list_of_approve = explode('; ',  $request->approve_exist);
        $approve_data = [];
        foreach ($list_of_approve as $approve) {
            $approve_data[] = $approve;
        }

        $list_of_approve_date = explode('; ',  $request->approved_date);
        $approve_date_data = [];
        foreach ($list_of_approve_date as $approve_date) {
            $approve_date_data[] = $approve_date;
        }
        

        $dataArray = array(
            'entity_cd'         => $request->entity_cd,
            'entity_name'       => $request->entity_name,
            'doc_no'            => $request->doc_no,
            'level_no'          => $request->level_no,
            'approved_seq'      => $request->approved_seq,
            'descs'             => $request->descs,
            'ref_no'            => $request->ref_no,
            'user_id'           => $request->user_id,
            'user_name'         => $request->user_name,
            'email_addr'        => $request->email_addr,
            'sender'            => $request->sender,
            'sender_addr'       => $request->sender_addr,
	        'desc_sub'          => $request->desc_sub,
            'request_amt'       => implode(', ', $request_amt_data),
            'submission_no'     => $request->submission_no,
            'url_file'          => $url_data,
            'file_name'         => $file_data,
            'approve_list'      => $approve_data,
            'clarify_user'      => $request->clarify_user,
            'clarify_email'     => $request->clarify_email,
            'link'              => 'landsubmission',
        );

        $data2Encrypt = array(
            'entity_cd'         => $request->entity_cd,
            'doc_no'            => $request->doc_no,
            'level_no'          => $request->level_no,
            'user_id'           => $request->user_id,
            'email_addr'        => $request->email_addr,
            'entity_name'       => $request->entity_name,
            'type'              => 'E',
            'type_module'       => 'LM',
            'text'              => 'Land Submission'
        );
        Artisan::call('config:cache');
        Artisan::call('cache:clear');
        Cache::flush();
        // Melakukan enkripsi pada $dataArray
        $encryptedData = Crypt::encrypt($data2Encrypt);

        try {
            $emailAddress = strtolower($request->email_addr);
            $approved_seq = $request->approved_seq;
            $entity_cd = $request->entity_cd;
            $doc_no = $request->doc_no;
            $level_no = $request->level_no;
            $entity_name = $request->entity_name;

            if (!empty($emailAddress)) {
                // Check if the email has been sent before for this document
                $cacheFile = 'email_sent_' . $approved_seq . '_' . $entity_cd . '_' . $doc_no . '_' . $level_no . '.txt';
                $cacheFilePath = storage_path('app/mail_cache/send_Land_Submission/' . date('Ymd') . '/' . $cacheFile);
                $cacheDirectory = dirname($cacheFilePath);

                // Ensure the directory exists
                if (!file_exists($cacheDirectory)) {
                    mkdir($cacheDirectory, 0755, true);
                }

                // Acquire an exclusive lock
                $lockFile = $cacheFilePath . '.lock';
                $lockHandle = fopen($lockFile, 'w');
                if (!flock($lockHandle, LOCK_EX)) {
                    // Failed to acquire lock, handle appropriately
                    fclose($lockHandle);
                    throw new Exception('Failed to acquire lock');
                }

                if (!file_exists($cacheFilePath)) {
                    // Send email
                    Mail::to($emailAddress)
                        ->send(new LandSubmissionEmail($encryptedData, $dataArray, 'IFCA SOFTWARE - '.$entity_name));

                    // Mark email as sent
                    file_put_contents($cacheFilePath, 'sent');

                    // Log the success
                    Log::channel('sendmailapproval')->info('Email Land Submission doc_no '.$doc_no.' Entity ' . $entity_cd.' berhasil dikirim ke: ' . $emailAddress);
                    return 'Email berhasil dikirim ke: ' . $emailAddress;
                } else {
                    // Email was already sent
                    Log::channel('sendmailapproval')->info('Email Land Submission doc_no '.$doc_no.' Entity ' . $entity_cd.' already sent to: ' . $emailAddress);
                    return 'Email has already been sent to: ' . $emailAddress;
                }
            } else {
                // No email address provided
                Log::channel('sendmail')->warning("No email address provided for document " . $doc_no);
                return "No email address provided";
            }
        } catch (\Exception $e) {
            // Error occurred
            Log::channel('sendmail')->error('Failed to send email: ' . $e->getMessage());
            return "Failed to send email: " . $e->getMessage();
        }
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
                "Pesan" => 'You Have Already Made a Request to Land Submission No. '.$data["doc_no"],
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
                "Pesan" => 'There is no Land Submission with No. '.$data["doc_no"],
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
            "status"        => $status,
            "encrypt"       => $encrypt,
            "name"          => $statusData[0],
            "bgcolor"       => $statusData[1],
            "valuebt"       => $statusData[2],
            "entity_name"   => $data["entity_name"]
        ];

        return view('email/landsubmission/passcheckwithremark', $dataView);
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
        $sth = $pdo->prepare("SET NOCOUNT ON; EXEC mgr.xrl_send_mail_approval_land_submission ?, ?, ?, ?, ?;");
        $sth->bindParam(1, $data["entity_cd"]);
        $sth->bindParam(2, $data["doc_no"]);
        $sth->bindParam(3, $status);
        $sth->bindParam(4, $data["level_no"]);
        $sth->bindParam(5, $reason);
        $sth->execute();
        if ($sth == true) {
            $msg = "You Have Successfully ".$descstatus." the Land Submission No. ".$data["doc_no"];
            $notif = $descstatus." !";
            $st = 'OK';
            $image = $imagestatus;
        } else {
            $msg = "You Failed to ".$descstatus." the Land Submission No.".$data["doc_no"];
            $notif = 'Fail to '.$descstatus.' !';
            $st = 'OK';
            $image = "reject.png";
        }
        $msg1 = array(
            "Pesan" => $msg,
            "St" => $st,
            "notif" => $notif,
            "image" => $image,
            "entity_name"   => $data["entity_name"]
        );
        return view("email.after", $msg1);
    }
}
