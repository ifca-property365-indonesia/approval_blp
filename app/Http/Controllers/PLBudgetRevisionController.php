<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Mail\SendPLRevisionMail;
use PDO;
use DateTime;

class PLBudgetRevisionController extends Controller
{
    public function Mail(Request $request)
    {
        $amount = number_format( $request->amount , 2 , '.' , ',' );

        $list_of_approve = explode('; ',  $request->approve_exist);
        $approve_data = [];
        foreach ($list_of_approve as $approve) {
            $approve_data[] = $approve;
        }

        $list_of_urls = explode('; ', $request->url_file);
        $list_of_files = explode('; ', $request->file_name);

        $url_data = [];
        $file_data = [];

        foreach ($list_of_urls as $url) {
            $url_data[] = $url;
        }

        foreach ($list_of_files as $file) {
            $file_data[] = $file;
        }

        $dataArray = array(
            'descs'         => $request->descs,
            'entity_name'   => $request->entity_name,
            'project_name'  => $request->project_name,
            'trx_type'      => $request->trx_type,
            'amount'        => $amount,
            'doc_no'        => $request->doc_no,
            'user_name'     => $request->user_name,
            'sender'        => $request->sender,
            'module'        => $request->module,
            'url_file'      => $url_data,
            'file_name'     => $file_data,
            'approve_list'  => $approve_data,
            'clarify_user'  => $request->clarify_user,
            'clarify_email' => $request->clarify_email,
            'sender_addr'   => $request->sender_addr,
            'body'          => "Please approve RAB Budget Revision No. ".$request->doc_no." project ".$request->project_name. " with Amount ".$amount,
            'subject'       => "Need Approval for RAB Budget Revision No. ".$request->doc_no,
        );

        $data2Encrypt = array(
            'entity_cd'     => $request->entity_cd,
            'project_no'    => $request->project_no,
            'email_address' => $request->email_addr,
            'entity_name'   => $request->entity_name,
            'level_no'      => $request->level_no,
            'trx_type'      => $request->trx_type,
            'doc_no'        => $request->doc_no,
            'user_id'       => $request->user_id,
            'type'          => 'Y',
            'type_module'   => 'PL',
            'text'          => 'Budget Revision'
        );

        // Melakukan enkripsi pada $dataArray
        $encryptedData = Crypt::encrypt($data2Encrypt);

        try {
            $emailAddresses = strtolower($request->email_addr);
            $approve_seq = $request->approve_seq;
            $entity_cd = $request->entity_cd;
            $doc_no = $request->doc_no;
            $status = $request->status;
            $level_no = $request->level_no;
            $entity_name = $request->entity_name;

            // Check if email addresses are provided and not empty
            if (!empty($emailAddresses)) {
                $email = $emailAddresses; // Since $emailAddresses is always a single email address (string)

                // Check if the email has been sent before for this document
                $cacheFile = 'email_sent_' . $approve_seq . '_' . $entity_cd . '_' . $doc_no . '_' . $status . '_' . $level_no . '.txt';
                $cacheFilePath = storage_path('app/mail_cache/send_pl_budget_revision/' . date('Ymd') . '/' . $cacheFile);
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
                    Mail::to($email)
                        ->send(new SendPLRevisionMail($encryptedData, $dataArray, 'IFCA SOFTWARE - '.$entity_name));

                    // Mark email as sent
                    file_put_contents($cacheFilePath, 'sent');

                    // Log the success
                    Log::channel('sendmailapproval')->info('Email PL Budget Revision doc_no '.$doc_no.' Entity ' . $entity_cd.' berhasil dikirim ke: ' . $email);
                    return 'Email berhasil dikirim ke: ' . $email;
                } else {
                    // Email was already sent
                    Log::channel('sendmailapproval')->info('Email PL Budget Revision doc_no '.$doc_no.' Entity ' . $entity_cd.' already sent to: ' . $email);
                    return 'Email has already been sent to: ' . $email;
                }
            } else {
                Log::channel('sendmail')->warning("Tidak ada alamat email yang diberikan");
                Log::channel('sendmail')->info($doc_no);
                return "Tidak ada alamat email yang diberikan";
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
            'trx_type'      => $data["trx_type"],
            'module'        => $data["type_module"],
        ];

        $exists = DB::connection('BLP')
        ->table('mgr.cb_cash_request_appr')
        ->where($where)
        ->whereIn('status', ["A", "R", "C"])
        ->exists();

        if ($exists) {
            $msg1 = [
                "Pesan" => 'You Have Already Made a Request to PL Budget Revision No. '.$data["doc_no"],
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
                "Pesan" => 'There is no PL Budget Revision with No. '.$data["doc_no"],
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

        return view('email/plrevision/passcheckwithremark', $dataView);
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
        $sth = $pdo->prepare("SET NOCOUNT ON; EXEC mgr.xrl_send_mail_approval_pl_budget_revision ?, ?, ?, ?, ?, ?, ?;");
        $sth->bindParam(1, $data["entity_cd"]);
        $sth->bindParam(2, $data["project_no"]);
        $sth->bindParam(3, $data["doc_no"]);
        $sth->bindParam(4, $data["trx_type"]);
        $sth->bindParam(5, $status);
        $sth->bindParam(6, $data["level_no"]);
        $sth->bindParam(7, $data["user_id"]);
        $sth->execute();
        if ($sth == true) {
            $msg = "You Have Successfully ".$descstatus." the PL Budget Revision No. ".$data["doc_no"];
            $notif = $descstatus." !";
            $st = 'OK';
            $image = $imagestatus;
        } else {
            $msg = "You Failed to ".$descstatus." the PL Budget Revision No.".$data["doc_no"];
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
