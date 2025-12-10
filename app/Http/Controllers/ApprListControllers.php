<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use PDO;

class ApprListControllers extends Controller
{
    public function index()
    {
        return view('apprlist.index'); // Pastikan file view ini ada di resources/views/apprlist/index.blade.php
    }

    public function getData()
    {
        $query = DB::connection('BLP')
            ->table('mgr.cb_cash_request_appr')
            ->where('status', 'P')
            ->whereNotNull('currency_cd')
            ->whereNotNull('sent_mail_date')
            ->whereRaw("LTRIM(RTRIM(entity_cd)) NOT LIKE '%[^0-9]%'")
            ->where('sent_mail_date', '<=', DB::raw("DATEADD(DAY, 1, GETDATE())")) // Hingga akhir hari ini
            ->where('audit_date', '>=', DB::raw("CONVERT(datetime, '2024-03-28', 120)"));

        return DataTables::of($query)->make(true);
    }
    
    public function sendData(Request $request)
    {
        $entity_cd   = $request->input('entity_cd');
        $doc_no      = $request->input('doc_no');
        $user_id     = $request->input('user_id');
        $level_no    = $request->input('level_no');
        $approve_seq = $request->input('approve_seq');

        Log::channel('resend')->info("Received Data: ", compact('entity_cd', 'doc_no', 'user_id'));

        $db = DB::connection('BLP');

        // === Get Required Data ===
        $query = $db->table('mgr.cb_cash_request_appr')
            ->where(compact('entity_cd', 'doc_no', 'user_id'))
            ->first();

        $project_no = optional($db->table('mgr.pl_project')
                    ->where('entity_cd', $entity_cd)
                    ->first())->project_no;

        $user_group = optional($db->table('mgr.security_groupings')
                    ->where('user_name', $user_id)
                    ->first())->group_name;

        $spv = optional($db->table('mgr.security_users')
                    ->where('name', $user_id)
                    ->first())->supervisor;

        // === Prepare Data From Query ===
        $trx_type  = optional($query)->trx_type;
        $type      = optional($query)->TYPE;
        $module    = optional($query)->module;
        $ref_no    = optional($query)->ref_no;
        $trx_date  = optional($query)->doc_date ? Carbon::parse($query->doc_date)->format('d-m-Y') : null;
        $level_no  = optional($query)->level_no;
        $reason    = '0';

        // Status + Down Level
        if ($level_no == 1) {
            $statussend = 'P';
            $downLevel = '0';
        } else {
            $downLevel = $level_no - 1;
            $statussend = 'A';
        }

        // === Helper untuk Eksekusi Stored Procedure ===
        $executeProcedure = function ($procedure, $params) use ($db) {
            $pdo = $db->getPdo();
            $placeholder = implode(', ', array_fill(0, count($params), '?'));
            $sql = "SET NOCOUNT ON; EXEC $procedure $placeholder;";

            Log::info("Executing Procedure", ['proc' => $procedure, 'params' => $params]);

            $stmt = $pdo->prepare($sql);
            foreach ($params as $i => $param) {
                $stmt->bindValue($i + 1, $param);
            }

            if ($stmt->execute()) {
                return response()->json(['message' => 'SUCCESS']);
            }
            return response()->json(['message' => 'FAILED'], 400);
        };

        // === Helper untuk Delete File ===
        $deleteCacheFile = function ($directory, $pattern) {
            $base = base_path()."/storage/app/mail_cache/$directory";
            $txt  = "$base/{$pattern}.txt";
            $lock = "$base/{$pattern}.txt.lock";

            if (file_exists($txt))  unlink($txt);
            if (file_exists($lock)) unlink($lock);
        };

        // === Mapping Module & Type ke Procedure dan Params ===
        $pattern = "email_sent_{$approve_seq}_{$entity_cd}_{$doc_no}_{$level_no}";
        $date = date('Ymd');

        $routes = [

            // ========== CB ==========
            'CB' => [
                'E' => [
                    "dir" => "send_cbfupd/$date",
                    "proc" => "mgr.x_send_mail_approval_cb_fupd",
                    "params" => [$entity_cd, $project_no, $doc_no, $trx_type, $statussend, $downLevel, $user_group, $user_id, $spv, $reason]
                ],
                'U' => [
                    "dir" => "send_cbppu/$date",
                    "proc" => "mgr.x_send_mail_approval_cb_ppu",
                    "params" => [$entity_cd, $project_no, $doc_no, $trx_type, $statussend, $downLevel, $user_group, $user_id, $spv, $reason]
                ],
                'V' => [
                    "dir" => "send_cbppuvvip/$date",
                    "proc" => "mgr.x_send_mail_approval_cb_ppu_vvip",
                    "params" => [$entity_cd, $project_no, $doc_no, $trx_type, $statussend, $downLevel, $user_group, $user_id, $spv, $reason]
                ],
                'D' => [
                    "dir" => "send_cbrpb/$date",
                    "proc" => "mgr.x_send_mail_approval_cb_rpb",
                    "params" => [$entity_cd, $project_no, $doc_no, $trx_type, $statussend, $downLevel, $user_group, $user_id, $spv, $reason]
                ],
                'G' => [
                    "dir" => "send_cbrum/$date",
                    "proc" => "mgr.x_send_mail_approval_cb_rum",
                    "params" => [$entity_cd, $project_no, $doc_no, $trx_type, $statussend, $downLevel, $user_group, $user_id, $spv, $reason]
                ], 
            ],

            // ========== CM ==========
            'CM' => [
                'C' => [
                    "dir" => "send_cmclose/$date",
                    "proc" => "mgr.xrl_send_mail_approval_cm_contractclose",
                    "params" => [$entity_cd, $project_no, $doc_no, $ref_no, $statussend, $downLevel, $user_group, $user_id, $spv, $reason]
                ],
                'B' => [
                    "dir" => "send_cmdone/$date",
                    "proc" => "mgr.xrl_send_mail_approval_cm_contractdone",
                    "params" => [$entity_cd, $project_no, $doc_no, $ref_no, $statussend, $downLevel, $user_group, $user_id, $spv, $reason]
                ],
                'E' => [
                    "dir" => "send_cmentry/$date",
                    "proc" => "mgr.xrl_send_mail_approval_cm_contract_entry",
                    "params" => [$entity_cd, $project_no, $doc_no, $ref_no, $statussend, $downLevel, $user_group, $user_id, $spv, $reason]
                ],
                'A' => [
                    "dir" => "send_cmprogress/$date",
                    "proc" => "mgr.xrl_send_mail_approval_cm_progress",
                    "params" => [$entity_cd, $project_no, $doc_no, $ref_no, $statussend, $downLevel, $user_group, $user_id, $spv, $reason]
                ],
                'F' => [
                    "dir" => "send_cmprogresswu/$date",
                    "proc" => "mgr.xrl_send_mail_approval_cm_progress_with_unit",
                    "params" => [$entity_cd, $project_no, $doc_no, $ref_no, $statussend, $downLevel, $user_group, $user_id, $spv, $reason]
                ],
                
                
            ],

            // ========== TM ==========
            'TM' => [
                'R' => [
                    "dir" => "send_contract_renew/$date",

                    // Stored Procedure Renewal
                    "proc" => "mgr.xrl_send_mail_approval_tm_contractrenew",

                    // Params, termasuk renew_no (harus dihitung sebelum routing)
                    "params" => function () use ($db, $entity_cd, $project_no, $doc_no, $ref_no, $statussend, $downLevel, $user_group, $user_id, $spv, $reason) {

                        // Ambil renew_no
                        $queryrenewno = $db->table('mgr.pm_tenancy_renew')
                            ->where('entity_cd', $entity_cd)
                            ->where('project_no', $project_no)
                            ->where('tenant_no', $ref_no)
                            ->first(['renew_no']);

                        $renew_no = optional($queryrenewno)->renew_no;

                        return [
                            $entity_cd, $project_no, $doc_no, $ref_no, $renew_no,
                            $statussend, $downLevel, $user_group, $user_id, $spv, $reason
                        ];
                    }
                ]
            ],

            // ========== LM ==========
            'LM' => [
                'D' => [
                    "dir" => "send_Land_Boundary/$date",
                    "proc" => "mgr.xrl_send_mail_approval_land_boundary",
                    "params" => [$entity_cd, $doc_no, $statussend, $downLevel, $reason]
                ],
                'O' => [
                    "dir" => "send_Land_Ccancel_NOP/$date",
                    "proc" => "mgr.xrl_send_mail_approval_land_cancel_nop",
                    "params" => [$entity_cd, $doc_no, $statussend, $downLevel, $reason]
                ],
                '1' => [
                    "dir" => "send_Land_Change/$date",
                    "proc" => "mgr.xrl_send_mail_approval_land_change_name",
                    "params" => [$entity_cd, $doc_no, $statussend, $downLevel, $reason]
                ],
                '1' => [
                    "dir" => "send_Land_Change_Name/$date",
                    "proc" => "mgr.xrl_send_mail_approval_land_change_name",
                    "params" => [$entity_cd, $doc_no, $statussend, $downLevel, $reason]
                ]
            ],

            // ========== PO ==========
            'PO' => [
                'Q' => [
                    "dir" => "send_porequeset/$date",
                    "proc" => "mgr.x_send_mail_approval_po_request",
                    "params" => [$entity_cd, $project_no, $doc_no, $statussend, $downLevel, $user_group, $user_id, $spv, $reason]
                ],
                'S' => [
                    "dir" => "send_pos/$date",
                    "proc" => "mgr.x_send_mail_approval_po_selection",
                    "params" => [$entity_cd, $project_no, $doc_no, $ref_no, $trx_date, $statussend, $downLevel, $user_group, $user_id, $spv, $reason]
                ],
                'A' => [
                    "dir" => "send_porder/$date",
                    "proc" => "mgr.x_send_mail_approval_po_order",
                    "params" => [$entity_cd, $project_no, $doc_no, $trx_type, $statussend, $downLevel, $user_group, $user_id, $spv, $reason]
                ]
            ]
        ];

        // === CHECK ROUTE ===
        if (!isset($routes[$module][$type])) {
            return response()->json(['message' => 'INVALID REQUEST'], 400);
        }

        $route = $routes[$module][$type];

        // Hapus file cache
        $deleteCacheFile($route["dir"], $pattern);

        // Jalankan stored procedure
        return $executeProcedure($route["proc"], $route["params"]);
    }
}
