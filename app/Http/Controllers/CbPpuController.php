<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Mail\SendCbPpuMail;
use PDO;
use DateTime;
use Exception;

class CbPPuController extends Controller
{
    public function Mail(Request $request)
    {
        if (strpos($request->ppu_descs, "\n") !== false) {
            $ppu_descs = str_replace("\n", ' (', $request->ppu_descs) . ')';
        } else {
            $ppu_descs = $request->ppu_descs;
        }

        $list_of_urls = explode(',', $request->url_file);
        $list_of_files = explode(',', $request->file_name);

        $url_data = [];
        $file_data = [];

        foreach ($list_of_urls as $url) {
            $url_data[] = $url;
        }

        foreach ($list_of_files as $file) {
            $file_data[] = $file;
        }

        $list_of_approve = explode('; ',  $request->approve_exist);
        $approve_data = [];
        foreach ($list_of_approve as $approve) {
            $approve_data[] = $approve;
        }

        $ppu_amt = number_format($request->ppu_amt, 2, '.', ',');

        $dataArray = array(
            'module'        => 'CbPpu',
            'ppu_no'        => $request->ppu_no,
            'ppu_descs'     => $request->ppu_descs,
            'sender'        => $request->sender,
            'sender_addr'   => $request->sender_addr,
            'url_file'      => $url_data,
            'file_name'     => $file_data,
            'entity_name'   => $request->entity_name,
            'descs'         => $request->descs,
            "doc_link"      => $request->document_link,
            'user_name'     => $request->user_name,
            'reason'        => $request->reason,
            'pay_to'        => $request->pay_to,
            'forex'         => $request->forex,
            'ppu_amt'       => $ppu_amt,
            'approve_list'  => $approve_data,
            'clarify_user'  => $request->clarify_user,
            'clarify_email' => $request->clarify_email,
            'body'          => "Please approve Payment Request No. ".$request->ppu_no." for ".$ppu_descs,
            'subject'       => "Need Approval for Payment Request No.  ".$request->ppu_no,
        );

        $data2Encrypt = array(
            'entity_cd'     => $request->entity_cd,
            'project_no'    => $request->project_no,
            'doc_no'        => $request->doc_no,
            'trx_type'      => $request->trx_type,
            'level_no'      => $request->level_no,
            'usergroup'     => $request->usergroup,
            'user_id'       => $request->user_id,
            'supervisor'    => $request->supervisor,
            'email_address' => $request->email_addr,
            'type'          => 'U',
            'type_module'   => 'CB',
            'text'          => 'Payment Request'
        );

        $encryptedData = Crypt::encrypt($data2Encrypt);

        try {
            $emailAddresses = strtolower($request->email_addr);
            $approve_seq = $request->approve_seq;
            $entity_cd = $request->entity_cd;
            $doc_no = $request->doc_no;
            $level_no = $request->level_no;
            $entity_name = $request->entity_name;

            // Check if email addresses are provided and not empty
            if (!empty($emailAddresses)) {
                $email = $emailAddresses; // Since $emailAddresses is always a single email address (string)

                // Check if the email has been sent before for this document
                $cacheFile = 'email_sent_' . $approve_seq . '_' . $entity_cd . '_' . $doc_no . '_' . $level_no . '.txt';
                $cacheFilePath = storage_path('app/mail_cache/send_cbppu/' . date('Ymd') . '/' . $cacheFile);
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
                    $mail = Mail::to($email);
                    $mail->send(new SendCbPpuMail($encryptedData, $dataArray, 'IFCA SOFTWARE - ' . $entity_name));

                    // Mark email as sent
                    file_put_contents($cacheFilePath, 'sent');

                    // Log the success
                    Log::channel('sendmailapproval')->info('Email CB PPU doc_no '.$doc_no.' Entity ' . $entity_cd.' berhasil dikirim ke: ' . $email);
                    return 'Email berhasil dikirim ke: ' . $email;
                } else {
                    // Email was already sent
                    Log::channel('sendmailapproval')->info('Email CB PPU doc_no '.$doc_no.' Entity ' . $entity_cd.' already sent to: ' . $email);
                    return 'Email has already been sent to: ' . $email;
                }
            } else {
                // No email address provided
                Log::channel('sendmail')->warning("No email address provided for document " . $doc_no);
                return "No email address provided";
            }
        } catch (\Exception $e) {
            Log::channel('sendmail')->error('Gagal mengirim email: ' . $e->getMessage());
            return "Gagal mengirim email: " . $e->getMessage();
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
                "Pesan" => 'You Have Already Made a Request to '.$data["text"].' No. '.$data["doc_no"],
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
            "encrypt"   => $encrypt,
            "name"      => $statusData[0],
            "bgcolor"   => $statusData[1],
            "valuebt"   => $statusData[2]
        ];

        return view('email/cbppu/passcheckwithremark', $dataView);
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
        $sth = $pdo->prepare("SET NOCOUNT ON; EXEC mgr.x_send_mail_approval_cb_ppu ?, ?, ?, ?, ?, ?, ?, ?, ?, ?;");
        $sth->bindParam(1, $data["entity_cd"]);
        $sth->bindParam(2, $data["project_no"]);
        $sth->bindParam(3, $data["doc_no"]);
        $sth->bindParam(4, $data["trx_type"]);
        $sth->bindParam(5, $status);
        $sth->bindParam(6, $data["level_no"]);
        $sth->bindParam(7, $data["usergroup"]);
        $sth->bindParam(8, $data["user_id"]);
        $sth->bindParam(9, $data["supervisor"]);
        $sth->bindParam(10, $reason);
        $sth->execute();
        if ($sth == true) {
            $msg = "You have successfully ".$descstatus." the Payment Request No. ".$data["doc_no"];
            $notif = $descstatus."!";
            $st = 'OK';
            $image = $imagestatus;
        } else {
            $msg = "You failed to ".$descstatus." the Payment Request No.".$data["doc_no"];
            $notif = 'Fail to '.$descstatus.'!';
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
