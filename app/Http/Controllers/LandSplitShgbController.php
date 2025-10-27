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

class LandSplitShgbController extends Controller
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
            // ====== Persiapan data ======
            // Ambil dan bersihkan URL
            $urlArray = array_filter(array_map('trim', explode(';', $request->url_link)), function($item) {
                return strtoupper($item) !== 'EMPTY' && $item !== '';
            });

            // Ambil file name yang sesuai dengan URL
            $fileArray = array_filter(array_map('trim', explode(';', $request->file_name)), function($item) {
                return strtoupper($item) !== 'EMPTY' && $item !== '';
            });

            // Jika jumlah URL dan file_name tidak sama, pastikan pasangannya sama
            $attachments = [];
            foreach ($urlArray as $key => $url) {
                if (isset($fileArray[$key])) {
                    $attachments[] = [
                        'url' => $url,
                        'file_name' => $fileArray[$key]
                    ];
                }
            }

            $list_of_approve = explode('; ', $request->approve_exist);
            $approve_data = [];
            foreach ($list_of_approve as $approve) {
                $approve_data[] = $approve;
            }

            $list_of_shgb_no = explode('; ', $request->shgb_no);
            $shgb_no_data = [];
            foreach ($list_of_shgb_no as $shgb_no) {
                $shgb_no_data[] = $shgb_no;
            }

            $list_of_shgb_no_bpn = explode('; ', $request->shgb_no_bpn);
            $shgb_no_bpn_data = [];
            foreach ($list_of_shgb_no_bpn as $shgb_no_bpn) {
                $shgb_no_bpn_data[] = $shgb_no_bpn;
            }

            $list_of_nop_no = explode('; ', $request->nop_no);
            $nop_no_data = [];
            foreach ($list_of_nop_no as $nop_no) {
                $nop_no_data[] = $nop_no;
            }

            $list_of_nib_no = explode('; ', $request->nib_no);
            $nib_no_data = [];
            foreach ($list_of_nib_no as $nib_no) {
                $nib_no_data[] = $nib_no;
            }

            $list_of_shgb_date = explode('; ', $request->shgb_date);
            $shgb_date_data = [];
            foreach ($list_of_shgb_date as $shgb_date) {
                $shgb_date_data[] = \Carbon\Carbon::parse($shgb_date)->format('d F Y');
            }

            $list_of_shgb_expired = explode('; ', $request->shgb_expired);
            $shgb_expired_data = [];
            foreach ($list_of_shgb_expired as $shgb_expired) {
                $shgb_expired_data[] = \Carbon\Carbon::parse($shgb_expired)->format('d F Y');
            }

            $list_of_shgb_area = explode('; ', $request->shgb_area);
            $shgb_area_data = [];
            foreach ($list_of_shgb_area as $shgb_area) {
                $shgb_area_data[] = number_format((float)$shgb_area, 2, '.', ',');
            }

            $list_of_split_status_hd = explode(';', $request->split_status_hd);
            $split_status_hd_data = [];
            foreach ($list_of_split_status_hd as $split_status_hd) {
                $split_status_hd_data[] = $split_status_hd;
            }
            
            $list_of_split_status_hdstr = explode(';', $request->split_status_hdstr);
            $split_status_hdstr_data = [];
            foreach ($list_of_split_status_hdstr as $split_status_hdstr) {
                $split_status_hdstr_data[] = $split_status_hdstr;
            }
            
            $list_of_split_status_dtstr = explode(';', $request->split_status_dtstr);
            $split_status_dtstr_data = [];
            foreach ($list_of_split_status_dtstr as $split_status_dtstr) {
                $split_status_hd_data[] = $split_status_dtstr;
            }

            $dataArray = [
                'user_id'               => $request->user_id,
                'level_no'              => $request->level_no,
                'entity_cd'             => $request->entity_cd,
                'doc_no'                => $request->doc_no,
                'approve_seq'           => $request->approve_seq,
                'email_addr'            => $request->email_addr,
                'user_name'             => $request->user_name,
                'sender_addr'           => $request->sender_addr,
                'sender_name'           => $request->sender_name,
                'entity_name'           => $request->entity_name,
                'attachments'           => $attachments,
                'descs'                 => $request->descs,
                'approve_list'          => $approve_data,
                'shgb_no'               => $shgb_no_data,
                'shgb_no_bpn'           => $shgb_no_bpn_data,
                'nop_no'                => $nop_no_data,
                'nib_no'                => $nib_no_data,
                'shgb_date'             => $shgb_date_data,
                'shgb_expired'          => $shgb_expired_data,
                'shgb_area'             => $shgb_area_data,
                'shgb_no_split'         => $request->shgb_no_split,
                'shgb_no_bpn_split'     => $request->shgb_no_bpn_split,
                'nop_no_split'          => $request->nop_no_split,
                'nib_no_split'          => $request->nib_no_split,
                'shgb_date_split'       => \Carbon\Carbon::parse($request->shgb_date_split)->format('d F Y'),
                'shgb_expired_split'    => \Carbon\Carbon::parse($request->shgb_expired_split)->format('d F Y'),
                'shgb_area_split'       => number_format((float)$request->shgb_area_split, 2, '.', ','),
                'remaining_area'        => number_format((float)$request->remaining_area, 2, '.', ','),
                'split_status_hd'       => $split_status_hd_data,
                'split_status_hdstr'    => $split_status_hdstr_data,
                'split_status_dtstr'    => $split_status_dtstr_data,
                'clarify_user'		    => $request->clarify_user,
                'clarify_email'		    => $request->clarify_email,
                'subject'               => "Need Approval for ".$request->doc_no,
                'link'                  => 'landsplitshgb',
            ];

            // dd($dataArray);

            $data2Encrypt = [
                'entity_cd'     => $request->entity_cd,
                'email_address' => $request->email_addr,
                'level_no'      => $request->level_no,
                'approve_seq'   => $request->approve_seq,
                'doc_no'        => $request->doc_no,
                'entity_name'   => $request->entity_name,
                'type'          => 'Q',
                'type_module'   => 'LM',
                'text'          => 'Land Split SHGB',
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
                $cacheFilePath = storage_path('app/mail_cache/send_Land_Split_SHGB/' . date('Ymd') . '/' . $cacheFile);
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
                    Log::channel('sendmailapproval')->info("Email Land Split SHGB doc_no $doc_no Entity $entity_cd berhasil dikirim ke: $email_address");

                    $callback['Pesan'] = "Email berhasil dikirim ke: $email_address";
                    $callback['Error'] = false;
                    $callback['Status']= 200;

                } else {
                    Log::channel('sendmailapproval')->info("Email Land Split SHGB doc_no $doc_no Entity $entity_cd sudah pernah dikirim ke: $email_address");

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
    
            $query2 = DB::connection('pakuwon')
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
        $pdo = DB::connection('pakuwon')->getPdo();
        $sth = $pdo->prepare("EXEC mgr.xrl_send_mail_approval_land_split_shgb ?, ?, ?, ?, ?");
        $success = $sth->execute([
            $data["entity_cd"],
            $data["doc_no"],
            $status,
            $data["level_no"],
            $reason
        ]);
        if ($success) {
            $msg = "You Have Successfully ".$descstatus." the Land Split SHGB No. ".$data["doc_no"];
            $notif = $descstatus." !";
            $st = 'OK';
            $image = $imagestatus;
        } else {
            $msg = "You Failed to ".$descstatus." the Land Split SHGB No.".$data["doc_no"];
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
