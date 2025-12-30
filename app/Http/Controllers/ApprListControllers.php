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
        // 1. Subquery utama yang berisi ROW_NUMBER()
        $sub = DB::connection('BLP')
            ->table('mgr.cb_cash_request_appr')
            ->select(
                '*',
                DB::raw("
                    ROW_NUMBER() OVER (
                        PARTITION BY doc_no, entity_cd, approve_seq
                        ORDER BY level_no ASC
                    ) AS rn
                ")
            )
            ->where('status', 'P')
            ->whereNotNull('currency_cd')
            ->whereRaw("LTRIM(RTRIM(entity_cd)) NOT LIKE '%[^0-9]%'");


        // 2. Bungkus subquery agar bisa memakai WHERE rn = 1
        $result = DB::connection('BLP')
            ->table(DB::raw("({$sub->toSql()}) AS cte"))
            ->mergeBindings($sub)
            ->where('rn', 1)
            ->orderBy('doc_no', 'desc')
            ->get();

        return DataTables::of($result)->make(true);
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
                ]
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
                'D' => [
                    "dir" => "send_varianorder/$date",
                    "proc" => "mgr.xrl_send_mail_approval_cm_varianorder",
                    "params" => [$entity_cd, $project_no, $doc_no, $ref_no, $statussend, $downLevel, $user_group, $user_id, $spv, $reason]
                ]
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
                    "dir" => "send_Land_Change_Name/$date",
                    "proc" => "mgr.xrl_send_mail_approval_land_change_name",
                    "params" => [$entity_cd, $doc_no, $statussend, $downLevel, $reason]
                ],
                'U' => [
                    "dir" => "send_Land_Extension_SHGB/$date",
                    "proc" => "mgr.xrl_send_mail_approval_land_extension_shgb",
                    "params" => [$entity_cd, $doc_no, $statussend, $downLevel, $reason]
                ],
                'F' => [
                    "dir" => "send_Land_Fph/$date",
                    "proc" => "mgr.xrl_send_mail_approval_land_fph",
                    "params" => [$entity_cd, $doc_no, $statussend, $downLevel, $reason]
                ],
                'M' => [
                    "dir" => "send_Land_Handover_Legal/$date",
                    "proc" => "mgr.xrl_send_mail_approval_land_handover_legal",
                    "params" => [$entity_cd, $doc_no, $statussend, $downLevel, $reason]
                ],
                'H' => [
                    "dir" => "send_Land_Handover_SHGB/$date",
                    "proc" => "mgr.xrl_send_mail_approval_land_handover_shgb",
                    "params" => [$entity_cd, $doc_no, $statussend, $downLevel, $reason]
                ],
                'B' => [
                    "dir" => "send_Land_Map/$date",
                    "proc" => "mgr.xrl_send_mail_approval_land_map",
                    "params" => [$entity_cd, $doc_no, $statussend, $downLevel, $reason]
                ],
                'K' => [
                    "dir" => "send_Land_Measuring/$date",
                    "proc" => "mgr.xrl_send_mail_approval_land_measuring",
                    "params" => [$entity_cd, $doc_no, $statussend, $downLevel, $reason]
                ],
                'A' => [
                    "dir" => "send_Land_Measuring_SFT/$date",
                    "proc" => "mgr.xrl_send_mail_approval_land_measuring_sft",
                    "params" => [$entity_cd, $doc_no, $statussend, $downLevel, $reason]
                ],
                'J' => [
                    "dir" => "send_Land_Merge_SHGB/$date",
                    "proc" => "mgr.xrl_send_mail_approval_land_sft_merge_shgb",
                    "params" => [$entity_cd, $doc_no, $statussend, $downLevel, $reason]
                ],
                'R' => [
                    "dir" => "send_Land_Request/$date",
                    "proc" => "mgr.xrl_send_mail_approval_land_request",
                    "params" => [$entity_cd, $doc_no, $statussend, $downLevel, $reason]
                ],
                'Y' => [
                    "dir" => "send_Land_SFT_BPHTB/$date",
                    "proc" => "mgr.xrl_send_mail_approval_land_sft_bphtb",
                    "params" => [$entity_cd, $doc_no, $statussend, $downLevel, $reason]
                ],
                'X' => [
                    "dir" => "send_Land_SFT_Propose/$date",
                    "proc" => "mgr.xrl_send_mail_approval_land_sft_propose",
                    "params" => [$entity_cd, $doc_no, $statussend, $downLevel, $reason]
                ],
                'Z' => [
                    "dir" => "send_Land_SFT_SHGB/$date",
                    "proc" => "mgr.xrl_send_mail_approval_land_sft_shgb",
                    "params" => [$entity_cd, $doc_no, $statussend, $downLevel, $reason]
                ],
                'S' => [
                    "dir" => "send_Land_SPH/$date",
                    "proc" => "mgr.xrl_send_mail_approval_land_sph",
                    "params" => [$entity_cd, $doc_no, $statussend, $downLevel, $reason]
                ],
                'Q' => [
                    "dir" => "send_Land_Split_SHGB/$date",
                    "proc" => "mgr.xrl_send_mail_approval_land_split_shgb",
                    "params" => [$entity_cd, $doc_no, $statussend, $downLevel, $reason]
                ],
                'E' => [
                    "dir" => "send_Land_Submission/$date",
                    "proc" => "mgr.xrl_send_mail_approval_land_submission",
                    "params" => [$entity_cd, $doc_no, $statussend, $downLevel, $reason]
                ],
                'V' => [
                    "dir" => "send_Land_Verification/$date",
                    "proc" => "mgr.xrl_send_mail_approval_land_Verification",
                    "params" => [$entity_cd, $doc_no, $statussend, $downLevel, $reason]
                ],
                '2' => [
                    "dir" => "send_Land_Verification_Payment/$date",
                    "proc" => "mgr.xrl_send_mail_approval_land_verification_payment",
                    "params" => [$entity_cd, $doc_no, $statussend, $downLevel, $reason]
                ]
            ],

            // ========== PL ==========
            'PL' => [
                'X' => [
                    "dir" => "send_pl_budget_lyman/$date",
                    "proc" => "mgr.xrl_send_mail_approval_pl_budget_lyman",
                    "params" => [$entity_cd, $project_no, $doc_no, $statussend, $downLevel, $user_id]
                ],
                'Y' => [
                    "dir" => "send_pl_budget_revision/$date",
                    "proc" => "mgr.xrl_send_mail_approval_pl_budget_revision",
                    "params" => [$entity_cd, $project_no, $doc_no, $trx_type, $statussend, $downLevel, $user_id]
                ],
                'A' => [
                    "dir" => "send_Land_PL_Overwrite/$date",
                    "proc" => "mgr.xrl_send_mail_approval_pl_overwrite",
                    "params" => [$entity_cd, $project_no, $doc_no, $statussend, $downLevel, $reason]
                ]
            ],

            // ========== PO ==========
            'PO' => [
                'A' => [
                    "dir" => "send_porder/$date",
                    "proc" => "mgr.x_send_mail_approval_po_order",
                    "params" => [$entity_cd, $project_no, $doc_no, $trx_type, $statussend, $downLevel, $user_group, $user_id, $spv, $reason]
                ],
                'Q' => [
                    "dir" => "send_porequeset/$date",
                    "proc" => "mgr.x_send_mail_approval_po_request",
                    "params" => [$entity_cd, $project_no, $doc_no, $statussend, $downLevel, $user_group, $user_id, $spv, $reason]
                ],
                'S' => [
                    "dir" => "send_pos/$date",
                    "proc" => "mgr.x_send_mail_approval_po_selection",
                    "params" => [$entity_cd, $project_no, $doc_no, $ref_no, $trx_date, $statussend, $downLevel, $user_group, $user_id, $spv, $reason]
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
