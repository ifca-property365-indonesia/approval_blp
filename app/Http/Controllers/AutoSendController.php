<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use App\Mail\SendPoSMail;
use App\Mail\FeedbackMail;
use App\Mail\StaffActionMail;
use App\Mail\StaffActionPoRMail;
use App\Mail\StaffActionPoSMail;
use Carbon\Carbon;
use PDO;
use DateTime;


class AutoSendController extends Controller
{
    public function index()
    {
        $query = DB::connection('BLP')
        ->table('mgr.cb_cash_request_appr')
        ->whereNull('sent_mail_date')
        ->where('status', 'P')
        ->whereNotNull('currency_cd')
        ->whereNotIn('entity_cd', ['DKY', 'DAN'])
        ->where('audit_date', '>=', DB::raw("CONVERT(datetime, '2024-03-28', 120)"))
        ->orderBy('doc_no', 'desc')
        ->get();

        foreach ($query as $data) {
            $entity_cd = $data->entity_cd;
            $exploded_values = explode(" ", $entity_cd);
            $project_no = implode('', $exploded_values) . '01';
            $doc_no = $data->doc_no;
            $trx_type = $data->trx_type;
            $level_no = $data->level_no;
            $user_id = $data->user_id;
            $type = $data->TYPE;
            $module = $data->module;
            $ref_no = $data->ref_no;
            $doc_date = $data->doc_date;
            $dateTime = new DateTime($doc_date);
            $supervisor = 'Y';
            $reason = '0';

            if ($type == 'U' && $module == "CB") {
                $exec = 'mgr.x_send_mail_approval_cb_ppu';
            } else if ($type == 'V' && $module == "CB") {
                $exec = 'mgr.x_send_mail_approval_cb_ppu_vvip';
            } else if ($type == 'Q' && $module == "PO") {
                $exec = 'mgr.x_send_mail_approval_po_request';
            }
            $whereUg = array(
                'user_name' => $user_id
            );

            $queryUg = DB::connection('BLP')
            ->table('mgr.security_groupings')
            ->where($whereUg)
            ->get();

            $user_group = $queryUg[0]->group_name;

            $wheresupervisor = array(
                'name' => $user_id
            );

            $querysupervisor = DB::connection('BLP')
            ->table('mgr.security_users')
            ->where($wheresupervisor)
            ->get();

            $supervisor = $querysupervisor[0]->supervisor;

            if ($level_no == 1) {
                if ($type == 'Q' && $module == "PO") {
                    $statussend = 'P';
                    $downLevel = '0';
                    $pdo = DB::connection('BLP')->getPdo();
                    $sth = $pdo->prepare("SET NOCOUNT ON; EXEC mgr.x_send_mail_approval_po_request ?, ?, ?, ?, ?, ?, ?, ?, ?;");
                    $sth->bindParam(1, $entity_cd);
                    $sth->bindParam(2, $project_no);
                    $sth->bindParam(3, $doc_no);
                    $sth->bindParam(4, $statussend);
                    $sth->bindParam(5, $downLevel);
                    $sth->bindParam(6, $user_group);
                    $sth->bindParam(7, $user_id);
                    $sth->bindParam(8, $supervisor);
                    $sth->bindParam(9, $reason);
                    $sth->execute();
                } else if (($type == 'D' && $module == "CB") || ($type == 'Y' && $module == "CM")) {
                    // Skip this condition, do nothing for type 'D' and module 'CB'
                    continue;  // This will skip the current iteration of the loop
                } else {
                    $statussend = 'P';
                    $downLevel = '0';
                    $pdo = DB::connection('BLP')->getPdo();
                    $sth = $pdo->prepare("SET NOCOUNT ON; EXEC ".$exec." ?, ?, ?, ?, ?, ?, ?, ?, ?, ?;");
                    $sth->bindParam(1, $entity_cd);
                    $sth->bindParam(2, $project_no);
                    $sth->bindParam(3, $doc_no);
                    $sth->bindParam(4, $trx_type);
                    $sth->bindParam(5, $statussend);
                    $sth->bindParam(6, $downLevel);
                    $sth->bindParam(7, $user_group);
                    $sth->bindParam(8, $user_id);
                    $sth->bindParam(9, $supervisor);
                    $sth->bindParam(10, $reason);
                    $sth->execute();
                }
            } else if ($level_no > 1){
                $downLevel  = $level_no - 1;
                $statussend = 'A';
                $wherebefore = array(
                    'doc_no' => $doc_no,
                    'entity_cd' => $entity_cd,
                    'level_no'  => $downLevel
                );
    
                $querybefore = DB::connection('BLP')
                ->table('mgr.cb_cash_request_appr')
                ->where($wherebefore)
                ->get();
    
                $level_data = $querybefore[0]->status;
                if ($level_data == 'A'){
                    if ($type == 'Q' && $module == "PO") {
                        $pdo = DB::connection('BLP')->getPdo();
                        $sth = $pdo->prepare("SET NOCOUNT ON; EXEC mgr.x_send_mail_approval_po_request ?, ?, ?, ?, ?, ?, ?, ?, ?;");
                        $sth->bindParam(1, $entity_cd);
                        $sth->bindParam(2, $project_no);
                        $sth->bindParam(3, $doc_no);
                        $sth->bindParam(4, $statussend);
                        $sth->bindParam(5, $downLevel);
                        $sth->bindParam(6, $user_group);
                        $sth->bindParam(7, $user_id);
                        $sth->bindParam(8, $supervisor);
                        $sth->bindParam(9, $reason);
                        $sth->execute();
                    } else if (($type == 'D' && $module == "CB") || ($type == 'Y' && $module == "CM")) {
                        // Skip this condition, do nothing for type 'D' and module 'CB'
                        continue;  // This will skip the current iteration of the loop
                    } else {
                        $pdo = DB::connection('BLP')->getPdo();
                        $sth = $pdo->prepare("SET NOCOUNT ON; EXEC ".$exec." ?, ?, ?, ?, ?, ?, ?, ?, ?, ?;");
                        $sth->bindParam(1, $entity_cd);
                        $sth->bindParam(2, $project_no);
                        $sth->bindParam(3, $doc_no);
                        $sth->bindParam(4, $trx_type);
                        $sth->bindParam(5, $statussend);
                        $sth->bindParam(6, $downLevel);
                        $sth->bindParam(7, $user_group);
                        $sth->bindParam(8, $user_id);
                        $sth->bindParam(9, $supervisor);
                        $sth->bindParam(10, $reason);
                        $sth->execute();
                    }
                }
            }
        }
    }
}
