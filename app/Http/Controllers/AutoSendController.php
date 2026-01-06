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
        $dataList = DB::connection('BLP')
            ->table('mgr.cb_cash_request_appr')
            ->whereNull('sent_mail_date')
            ->where('status', 'P')
            ->whereNotIn('entity_cd', ['DKY', 'DAN', 'KIA'])
            ->orderByDesc('doc_no')
            ->get();

        $spMap = [
            'CB' => [
                'U' => 'mgr.x_send_mail_approval_cb_ppu',
                'V' => 'mgr.x_send_mail_approval_cb_ppu_vvip',
                'D' => 'mgr.x_send_mail_approval_cb_rpb',
                'E' => 'mgr.x_send_mail_approval_cb_fupd',
                'G' => 'mgr.x_send_mail_approval_cb_rum',
            ],
            'PO' => [
                'A' => 'mgr.x_send_mail_approval_po_order',
                'Q' => 'mgr.x_send_mail_approval_po_request',
            ],
            'LM' => [
                'F' => 'mgr.xrl_send_mail_approval_land_fph',
                'M' => 'mgr.xrl_send_mail_approval_land_handover_legal',
                'H' => 'mgr.xrl_send_mail_approval_land_handover_shgb',
                'B' => 'mgr.xrl_send_mail_approval_land_map',
                'K' => 'mgr.xrl_send_mail_approval_land_measuring',
                'A' => 'mgr.xrl_send_mail_approval_land_measuring_sft',
                'R' => 'mgr.xrl_send_mail_approval_land_request',
                'W' => 'mgr.xrl_send_mail_approval_land_request_legal',
                'T' => 'mgr.xrl_send_mail_approval_land_sertifikat',
                'Y' => 'mgr.xrl_send_mail_approval_land_sft_bphtb',
                'J' => 'mgr.xrl_send_mail_approval_land_sft_merge_shgb',
                'X' => 'mgr.xrl_send_mail_approval_land_sft_propose',
                'Z' => 'mgr.xrl_send_mail_approval_land_sft_shgb',
                'S' => 'mgr.xrl_send_mail_approval_land_sph',
                'Q' => 'mgr.xrl_send_mail_approval_land_split_shgb',
                'E' => 'mgr.xrl_send_mail_approval_land_submission',
                'V' => 'mgr.xrl_send_mail_approval_land_verification',
                '2' => 'mgr.xrl_send_mail_approval_land_verification_payment',
            ],
            'CM' => [
                'E' => 'mgr.xrl_send_mail_approval_cm_contract_entry',
                'C' => 'mgr.xrl_send_mail_approval_cm_contractclose',
                'B' => 'mgr.xrl_send_mail_approval_cm_contractdone',
                'A' => 'mgr.xrl_send_mail_approval_cm_progress',
                'F' => 'mgr.xrl_send_mail_approval_cm_progress_with_unit',
                'D' => 'mgr.xrl_send_mail_approval_cm_varianorder',
            ],
        ];

        foreach ($dataList as $data) {

            // Skip kondisi tertentu
            if (
                ($data->TYPE === 'Y' && $data->module === 'CM')
            ) {
                continue;
            }

            $exec = $spMap[$data->module][$data->TYPE] ?? null;
            if (!$exec) {
                continue;
            }

            $entity_cd = $data->entity_cd;
            $doc_no    = $data->doc_no;
            $level_no  = $data->level_no;
            $trx_type  = $data->trx_type;
            $user_id   = $data->user_id;
            $ref_no    = $data->ref_no;
            $reason    = '0';

            // ===== LEVEL 1 =====
            if ($level_no == 1) {

                if ($data->module === 'LM') {
                    $this->execSpLM($exec, $entity_cd, $doc_no, 'P', 0, $reason);
                    continue;
                } else if ( $data->module === 'CM') {
                    $this->execSpref_no($exec, $data, 'P', 0, $reason);
                    continue;
                } else if ( $data->module === 'PL') {
                    $this->execPL($exec, $data, 'P', 0, $reason);
                    continue;
                }
                

                // CB / PO
                $this->execSpDefault($exec, $data, 'P', 0, $reason);
            }

            // ===== LEVEL > 1 =====
            else {
                $downLevel = $level_no - 1;

                $prevStatus = DB::connection('BLP')
                    ->table('mgr.cb_cash_request_appr')
                    ->where([
                        'doc_no'    => $doc_no,
                        'entity_cd' => $entity_cd,
                        'level_no'  => $downLevel
                    ])
                    ->value('status');

                if ($prevStatus !== 'A') {
                    continue;
                }

                if ($data->module === 'LM') {
                    $this->execSpLM($exec, $entity_cd, $doc_no, 'A', $downLevel, $reason);
                    continue;
                } else if ( $data->module === 'CM') {
                    $this->execSpref_no($exec, $data, 'A', $downLevel, $reason);
                    continue;
                } else if ( $data->module === 'PL') {
                    $this->execPL($exec, $data, 'A', $downLevel, $reason);
                    continue;
                }

                // CB / PO
                $this->execSpDefault($exec, $data, 'A', $downLevel, $reason);
            }
        }
    }

    // =========================
    // SP UNTUK CB & PO Selain PO Selection(10 PARAM)
    // =========================
    private function execSpDefault($sp, $data, $status, $downLevel, $reason)
    {
        $project_no = str_replace(' ', '', $data->entity_cd) . '01';

        $user_group = DB::connection('BLP')
            ->table('mgr.security_groupings')
            ->where('user_name', $data->user_id)
            ->value('group_name');

        $supervisor = DB::connection('BLP')
            ->table('mgr.security_users')
            ->where('name', $data->user_id)
            ->value('supervisor');

        $pdo = DB::connection('BLP')->getPdo();

        $sql = "SET NOCOUNT ON; EXEC {$sp} ?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $data->entity_cd,
            $project_no,
            $data->doc_no,
            $data->trx_type,
            $status,
            $downLevel,
            $user_group,
            $data->user_id,
            $supervisor,
            $reason
        ]);
    }

    // =========================
    // SP KHUSUS LM (5 PARAM)
    // =========================
    private function execSpLM($sp, $entity_cd, $doc_no, $status, $level, $reason)
    {
        $pdo = DB::connection('BLP')->getPdo();

        $sql = "SET NOCOUNT ON; EXEC {$sp} ?, ?, ?, ?, ?";
        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $entity_cd,
            $doc_no,
            $status,
            $level,
            $reason
        ]);
    }

    // =========================
    // SP UNTUK CM (10 PARAM)
    // =========================
    private function execSpref_no($sp, $data, $status, $downLevel, $reason)
    {
        $project_no = str_replace(' ', '', $data->entity_cd) . '01';

        // ref_no tetap EMPTY STRING (bukan NULL)
        $refNo = is_string($data->ref_no)
            ? trim($data->ref_no)
            : '';

        $user_group = DB::connection('BLP')
            ->table('mgr.security_groupings')
            ->where('user_name', $data->user_id)
            ->value('group_name');

        $supervisor = DB::connection('BLP')
            ->table('mgr.security_users')
            ->where('name', $data->user_id)
            ->value('supervisor');

        $pdo = DB::connection('BLP')->getPdo();

        $sql = "SET NOCOUNT ON; EXEC {$sp} ?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $data->entity_cd,
            $project_no,
            $data->doc_no,
            $refNo,           // ← SELALU '' jika kosong
            $status,
            $downLevel,
            $user_group,
            $data->user_id,
            $supervisor,
            $reason
        ]);
    }
}

