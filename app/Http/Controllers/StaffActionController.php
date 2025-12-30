<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;
use App\Mail\FeedbackMail;
use App\Mail\StaffActionMail;
use App\Mail\StaffActionPoRMail;
use App\Mail\StaffActionPoSMail;
use App\Mail\SendNextLandReqeuest;
use Carbon\Carbon;
use Exception;

class StaffActionController extends Controller
{
    public function staffaction(Request $request)
    {
        $callback = array(
            'Error' => false,
            'Pesan' => '',
            'Status' => 200
        );

        $action = ''; // Initialize $action
        $bodyEMail = '';

        if (strcasecmp($request->status, 'R') == 0) {

            $action = 'Revision';
            $bodyEMail = 'Please revise '.$request->descs.' No. '.$request->doc_no.' with the reason : '.$request->reason;

        } else if (strcasecmp($request->status, 'C') == 0){
            
            $action = 'Cancellation';
            $bodyEMail = $request->descs.' No. '.$request->doc_no.' has been cancelled with the reason : '.$request->reason;

        } else if (strcasecmp($request->status, 'A') == 0) {
            $action = 'Approval';
            $bodyEMail = 'Your Request '.$request->descs.' No. '.$request->doc_no.' has been Approved';
        }

        $EmailBack = array(
            'doc_no'            => $request->doc_no,
            'action'            => $action,
            'reason'            => $request->reason,
            'descs'             => $request->descs,
            'subject'		    => $request->subject,
            'bodyEMail'		    => $bodyEMail,
            'user_name'         => $request->user_name,
            'staff_act_send'    => $request->staff_act_send,
            'entity_name'       => $request->entity_name,
            'entity_cd'         => $request->entity_cd,
            'action_date'       => Carbon::now('Asia/Jakarta')->format('d-m-Y H:i')
        );
        $emailAddresses = strtolower($request->email_addr);
        $doc_no = $request->doc_no;
        $entity_name = $request->entity_name;
        $entity_cd = $request->entity_cd;
        $status = $request->status;
        $approve_seq = $request->approve_seq;
        try {
            $emailAddress = strtolower($request->email_addr);
            $doc_no = $request->doc_no;
            $entity_name = $request->entity_name;
            $entity_cd = $request->entity_cd;
            $status = $request->status;
            $approve_seq = $request->approve_seq;
            
            // Check if email address is provided and not empty
            if (!empty($emailAddress)) {
                // Check if the email has been sent before for this document
                $cacheFile = 'email_feedback_sent_' . $approve_seq . '_' . $entity_cd . '_' . $doc_no . '_' . $status . '.txt';
                $cacheFilePath = storage_path('app/mail_cache/feedbackStaffAction/' . date('Ymd') . '/' . $cacheFile);
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
                    Mail::to($emailAddress)->send(new StaffActionMail($EmailBack));
                    
                    // Mark email as sent
                    file_put_contents($cacheFilePath, 'sent');
                    Log::channel('sendmailfeedback')->info('Email Feedback doc_no ' . $doc_no . ' Entity ' . $entity_cd . ' berhasil dikirim ke: ' . $emailAddress);
                    return "Email berhasil dikirim ke: " . $emailAddress;
                }
            } else {
                Log::channel('sendmail')->warning("Tidak ada alamat email untuk feedback yang diberikan");
                Log::channel('sendmail')->warning($doc_no);
                return "Tidak ada alamat email untuk feedback yang diberikan";
            }
        } catch (\Exception $e) {
            Log::channel('sendmail')->error('Gagal mengirim email: ' . $e->getMessage());
            return "Gagal mengirim email: " . $e->getMessage();
        }
        
    }

    public function staffaction_por(Request $request)
    {
        $callback = array(
            'Error' => false,
            'Pesan' => '',
            'Status' => 200
        );
        
        $action = ''; // Initialize $action
        $bodyEMail = '';
        
        if (strcasecmp($request->status, 'R') == 0) {
        
            $action = 'Revision';
            $bodyEMail = 'Please revise ' . $request->descs . ' No. ' . $request->doc_no . ' with the reason : ' . $request->reason;
        
        } else if (strcasecmp($request->status, 'C') == 0) {
        
            $action = 'Cancellation';
            $bodyEMail = $request->descs . ' No. ' . $request->doc_no . ' has been cancelled with the reason : ' . $request->reason;
        
        } else if (strcasecmp($request->status, 'A') == 0) {
            $action = 'Approval';
            $bodyEMail = 'Your Request ' . $request->descs . ' No. ' . $request->doc_no . ' has been Approved with the Note : ' . $request->reason;
        }
        
        $list_of_urls = explode('; ', $request->url_file);
        $list_of_files = explode('; ', $request->file_name);
        $list_of_doc = explode('; ', $request->doc_link);
        
        $url_data = [];
        $file_data = [];
        $doc_data = [];
        
        foreach ($list_of_urls as $url) {
            $url_data[] = $url;
        }
        
        foreach ($list_of_files as $file) {
            $file_data[] = $file;
        }
        
        foreach ($list_of_doc as $doc) {
            $doc_data[] = $doc;
        }
        
        $EmailBack = array(
            'doc_no'            => $request->doc_no,
            'action'            => $action,
            'reason'            => $request->reason,
            'descs'             => $request->descs,
            'subject'           => $request->subject,
            'bodyEMail'         => $bodyEMail,
            'user_name'         => $request->user_name,
            'staff_act_send'    => $request->staff_act_send,
            'entity_name'       => $request->entity_name,
            'status'            => $request->status,
            'entity_cd'         => $request->entity_cd,
            'url_file'          => $url_data,
            'file_name'         => $file_data,
            'doc_link'          => $doc_data,
            'action_date'       => Carbon::now('Asia/Jakarta')->format('d-m-Y H:i')
        );
        
        $emailAddresses = strtolower($request->email_addr);
        $email_cc = $request->email_cc;
        $entity_cd = $request->entity_cd;
        $entity_name = $request->entity_name;
        $doc_no = $request->doc_no;
        $status = $request->status;
        $approve_seq = $request->approve_seq;
        
        try {
            $emailAddresses = strtolower($request->email_addr);
            $entity_cd = $request->entity_cd;
            $entity_name = $request->entity_name;
            $doc_no = $request->doc_no;
            $status = $request->status;
            $approve_seq = $request->approve_seq;
            $email_cc = $request->email_cc;
	    try {
                // Attempt to parse using a common format
                $date_approved = Carbon::createFromFormat('M  j Y h:iA', $request->date_approved)->format('Ymd');
            } catch (\Exception $e) {
                // Fallback if the format doesn't match
                try {
                    // Attempt another format if needed
                    $date_approved = Carbon::createFromFormat('Y-m-d H:i:s', $request->date_approved)->format('Ymd');
                } catch (\Exception $e) {
                    // Handle error or provide a default
                    $date_approved = Carbon::now()->format('Ymd');
                }
            }
        
            // Check if email addresses are provided and not empty
            if (!empty($emailAddresses)) {
                // Explode the email addresses string into an array
                $emails = explode(';', $emailAddresses);
        
                // Initialize CC emails array
                $cc_emails = [];
        
                // Only process CC emails if the status is 'A'
                if (strcasecmp($status, 'A') == 0 && !empty($email_cc)) {
                    // Explode the CC email addresses strings into arrays and remove duplicates
                    $cc_emails = array_unique(explode(';', $email_cc));
        
                    // Remove the main email addresses from the CC list
                    $cc_emails = array_diff($cc_emails, $emails);
                }
        
                // Set up the email object
                $mail = new StaffActionPoRMail($EmailBack);
                foreach ($cc_emails as $cc_email) {
                    $mail->cc(trim($cc_email));
                }
        
                $emailSent = false;
        
                // Check if the email has been sent before for this document
                $cacheFile = 'email_feedback_sent_' . $approve_seq . '_' . $entity_cd . '_' . $doc_no . '_' . $status . '.txt';
                $cacheFilePath = storage_path('app/mail_cache/feedbackPOR/' . $date_approved . '/' . $cacheFile);
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
                    Mail::to($emails)->send($mail);
        
                    // Mark email as sent
                    file_put_contents($cacheFilePath, 'sent');
                    $sentTo = implode(', ', $emails);
                    $ccList = implode(', ', $cc_emails);
        
                    $logMessage = 'Email Feedback ' . $action . ' doc_no ' . $doc_no . ' Entity ' . $entity_cd . ' berhasil dikirim ke: ' . $sentTo;
                    if (!empty($cc_emails)) {
                        $logMessage .= ' & CC ke : ' . $ccList;
                    }
        
                    Log::channel('sendmailfeedback')->info($logMessage);
                    $emailSent = true;
                }
        
                if ($emailSent) {
                    return "Email berhasil dikirim ke: " . $sentTo . ($cc_emails ? " & CC ke : " . $ccList : "");
                } else {
                    return "Email sudah dikirim sebelumnya.";
                }
            } else {
                Log::channel('sendmail')->warning('Tidak ada alamat email yang diberikan.');
                return "Tidak ada alamat email yang diberikan.";
            }
        } catch (\Exception $e) {
            Log::channel('sendmail')->error('Gagal mengirim email: ' . $e->getMessage());
            return "Gagal mengirim email. Cek log untuk detailnya.";
        }              
    }

    public function staffaction_pos(Request $request)
    {
        $callback = array(
            'Error' => false,
            'Pesan' => '',
            'Status' => 200
        );

        $action = ''; // Initialize $action
        $bodyEMail = '';

        if (strcasecmp($request->status, 'R') == 0) {

            $action = 'Revision';
            $bodyEMail = 'Please revise '.$request->descs.' No. '.$request->doc_no.' with the reason : '.$request->reason;

        } else if (strcasecmp($request->status, 'C') == 0){
            
            $action = 'Cancellation';
            $bodyEMail = $request->descs.' No. '.$request->doc_no.' has been cancelled with the reason : '.$request->reason;

        } else if (strcasecmp($request->status, 'A') == 0) {
            $action = 'Approval';
            $bodyEMail = 'Your Request '.$request->descs.' No. '.$request->doc_no.' has been Approved';
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

        $EmailBack = array(
            'doc_no'            => $request->doc_no,
            'action'            => $action,
            'reason'            => $request->reason,
            'descs'             => $request->descs,
            'subject'		    => $request->subject,
            'bodyEMail'		    => $bodyEMail,
            'user_name'         => $request->user_name,
            'staff_act_send'    => $request->staff_act_send,
            'entity_name'       => $request->entity_name,
            'entity_cd'         => $request->entity_cd,
            'url_file'          => $url_data,
            'file_name'         => $file_data,
            'action_date'       => Carbon::now('Asia/Jakarta')->format('d-m-Y H:i')
        );
        $emailAddresses = strtolower($request->email_addr);
        $doc_no = $request->doc_no;
        $entity_name = $request->entity_name;
        $entity_cd = $request->entity_cd;
        $status = $request->status;
        $approve_seq = $request->approve_seq;
        try {
            $emailAddress = strtolower($request->email_addr);
            $doc_no = $request->doc_no;
            $entity_name = $request->entity_name;
            $entity_cd = $request->entity_cd;
            $status = $request->status;
            $approve_seq = $request->approve_seq;
        
            if (!empty($emailAddress)) {
                // Check if the email has been sent before for this document
                $cacheFile = 'email_feedback_sent_' . $approve_seq . '_' . $entity_cd . '_' . $doc_no . '_' . $status . '.txt';
                $cacheFilePath = storage_path('app/mail_cache/feedbackPOS/' . date('Ymd') . '/' . $cacheFile);
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
                    Mail::to($emailAddress)->send(new StaffActionPoSMail($EmailBack));
        
                    // Mark email as sent
                    file_put_contents($cacheFilePath, 'sent');
                    Log::channel('sendmailfeedback')->info('Email Feedback doc_no ' . $doc_no . ' Entity ' . $entity_cd . ' berhasil dikirim ke: ' . $emailAddress);
                    return 'Email berhasil dikirim ke: ' . $emailAddress;
                }
            } else {
                Log::channel('sendmail')->warning("Tidak ada alamat email untuk feedback yang diberikan");
                Log::channel('sendmail')->warning($doc_no);
                return "Tidak ada alamat email untuk feedback yang diberikan";
            }
        } catch (\Exception $e) {
            Log::channel('sendmail')->error('Gagal mengirim email: ' . $e->getMessage());
            return "Gagal mengirim email. Cek log untuk detailnya.";
        }
              
    }

    public function fileexist(Request $request)
    {
        $file_name = $request->file_name;
        $folder_name = $request->folder_name;

        // Connect to FTP server
        $ftp_server = "10.41.41.112";
        $ftp_conn = ftp_connect($ftp_server, 2121) or die("Could not connect to $ftp_server");

        // Log in to FTP server
        $ftp_user_name = "ifca-att";
        $ftp_user_pass = "1fc41fc4";
        $login = ftp_login($ftp_conn, $ftp_user_name, $ftp_user_pass);

        $file = "ifca-att/".$folder_name."/".$file_name;

        if (ftp_size($ftp_conn, $file) > 0) {
            echo "Ada File";
        } else {
            echo "Tidak Ada File";
        }

        ftp_close($ftp_conn);
    }

    public function feedback_land(Request $request)
    {
        $callback = [
            'Error'  => false,
            'Pesan'  => '',
            'Status' => 200,
        ];

        $action = '';
        $bodyEMail = '';

        if (strcasecmp($request->status, 'R') == 0) {
            $action = 'Revision';
            $bodyEMail = 'Please revise ' . $request->descs . ' with the reason : ' . $request->reason;
        } else if (strcasecmp($request->status, 'C') == 0) {
            $action = 'Cancellation';
            $bodyEMail = $request->descs . ' has been cancelled with the reason : ' . $request->reason;
        } else if (strcasecmp($request->status, 'A') == 0) {
            $action = 'Approval';
            $bodyEMail = 'Your Request ' . $request->descs . ' has been Approved ';
        }

        // ====== Persiapan data ======
        $urlArray = array_filter(array_map('trim', explode(';', $request->url_link)), function ($item) {
            return strtoupper($item) !== 'EMPTY' && $item !== '';
        });

        $fileArray = array_filter(array_map('trim', explode(';', $request->file_name)), function ($item) {
            return strtoupper($item) !== 'EMPTY' && $item !== '';
        });

        $attachments = [];
        foreach ($urlArray as $key => $url) {
            if (isset($fileArray[$key])) {
                $attachments[] = [
                    'url' => $url,
                    'file_name' => $fileArray[$key]
                ];
            }
        }

        $EmailBack = [
            'doc_no'          => $request->doc_no,
            'action'          => $action,
            'reason'          => $request->reason,
            'descs'           => $request->descs,
            'subject'         => $request->subject,
            'bodyEMail'       => $bodyEMail,
            'user_name'       => $request->user_name,
            'staff_act_send'  => $request->staff_act_send,
            'descs_send'      => $request->descs_send,
            'entity_name'     => $request->entity_name,
            'status'          => $request->status,
            'entity_cd'       => $request->entity_cd,
            'attachments'     => $attachments,
            'folderlink'      => $request->descs_send.'Mail',
            'action_date'     => Carbon::now('Asia/Jakarta')->format('d-m-Y H:i')
        ];

        try {
            $emailAddresses = strtolower($request->email_addr);
            $entity_cd = $request->entity_cd;
            $doc_no = $request->doc_no;
            $status = $request->status;
            $approve_seq = $request->approve_seq;

            if (!empty($emailAddresses)) {
                $emails = explode(';', $emailAddresses);
                $mail = new FeedbackMail($EmailBack);
                $emailSent = false;

                $cacheFile = 'email_feedback_sent_' . $approve_seq . '_' . $entity_cd . '_' . $doc_no . '_' . $status . '.txt';
                $cacheFilePath = storage_path('app/mail_cache/feedback/'.$request->descs_send.'/' . date('Ymd') . '/' . $cacheFile);
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
                    Mail::to($emails)->send($mail);
                    file_put_contents($cacheFilePath, 'sent');

                    $sentTo = implode(', ', $emails);
                    $logMessage = "Email Feedback {$action} doc_no {$doc_no} Entity {$entity_cd} berhasil dikirim ke: {$sentTo}";
                    Log::channel('sendmailfeedback')->info($logMessage);

                    $emailSent = true;
                }

                if ($emailSent) {
                    $callback['Error'] = false;
                    $callback['Pesan'] = 'Email berhasil dikirim.';
                    $callback['Status'] = 200;
                } else {
                    $callback['Error'] = false;
                    $callback['Pesan'] = 'Email sudah dikirim sebelumnya.';
                    $callback['Status'] = 200;
                }

            } else {
                Log::channel('sendmail')->warning('Tidak ada alamat email yang diberikan.');
                $callback['Error'] = true;
                $callback['Pesan'] = 'Tidak ada alamat email yang diberikan.';
                $callback['Status'] = 400;
            }

        } catch (Exception $e) {
            Log::channel('sendmail')->error('Gagal mengirim email: ' . $e->getMessage());
            $callback['Error'] = true;
            $callback['Pesan'] = 'Gagal mengirim email. Cek log untuk detailnya.';
            $callback['Status'] = 500;
        }

        return response()->json($callback, $callback['Status']);
    }

    public function feedback_land_request(Request $request)
    {
        $callback = [
            'Error'  => false,
            'Pesan'  => '',
            'Status' => 200,
        ];

        try {
            $action = '';
            $bodyEMail = '';
            
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

            $list_of_request_amt = explode(';', $request->request_amt);
            $request_amt_data = [];
            foreach ($list_of_request_amt as $request_amt) {
                $request_amt_data[] = number_format($request_amt, 2, '.', ',');
            }

            $list_of_name_owner = explode(';', $request->name_owner);
            $name_owner_data = [];
            foreach ($list_of_name_owner as $name_owner) {
                $name_owner_data[] = $name_owner;
            }

            $list_of_nop_no = explode(';', $request->nop_no);
            $nop_no_data = [];
            foreach ($list_of_nop_no as $nop_no) {
                $nop_no_data[] = $nop_no;
            }

            $list_of_sph_trx_no = explode(';', $request->sph_trx_no);
            $sph_trx_no_data = [];
            foreach ($list_of_sph_trx_no as $sph_trx_no) {
                $sph_trx_no_data[] = $sph_trx_no;
            }

            $list_of_type = explode(';', $request->type);
            $type_data = [];
            foreach ($list_of_type as $type) {
                $type_data[] = $type;
            }

            $paymentMap = [
                'A' => 'Tolong proses AP Advance dengan detail :',
                'I' => 'Tolong proses AP Invoice dengan detail :',
            ];

            $subjectMap = [
                'A' => 'Land Transaction AP Advance No. ' . $request->doc_no,
                'I' => 'Land Transaction AP Invoice No. ' . $request->doc_no,
            ];

            $detailText = $paymentMap[$request->payment_cd]
                ?? 'Tolong proses Pengajuan Pembayaran dengan detail :';

            $subject = $subjectMap[$request->payment_cd]
                ?? 'Land Transaction No. ' . $request->doc_no;


            $dataArray = [
                'user_id'           => $request->user_id,
                'level_no'          => $request->level_no,
                'entity_cd'         => $request->entity_cd,
                'doc_no'            => $request->doc_no,
                'approve_seq'       => $request->approve_seq,
                'email_addr'        => $request->email_addr,
                'user_name'         => $request->user_name,
                'sender_addr'       => $request->sender_addr,
                'sender_name'       => $request->sender_name,
                'entity_name'       => $request->entity_name,
                'attachments'       => $attachments,
                'descs'             => $request->descs,
                'approve_list'      => $approve_data,
                'type'              => $type_data,
                'name_owner'        => $name_owner_data,
                'nop_no'            => $nop_no_data,
                'sph_trx_no'        => $sph_trx_no_data,
                'request_amt'       => $request_amt_data,
                'clarify_user'		=> $request->sender_name,
                'clarify_email'		=> $request->sender_addr,
                'detail_text'       => $detailText,
                'subject'           => $subject,   // ✅ STRING
                'link'              => 'landrequest',
            ];

            $callback['data'] = [
                'payload'   => $dataArray
            ];

            // ====== Proses kirim email ======
            $approve_seq = $request->approve_seq;
            $entity_cd   = $request->entity_cd;
            $doc_no      = $request->doc_no;
            $level_no    = $request->level_no;

            /**
             * Pecah email jadi array
             * contoh: "a@mail.com; b@mail.com"
             */
            $emailAddresses = array_filter(
                array_map(
                    'trim',
                    explode(';', strtolower($request->email_addr))
                )
            );

            if (!empty($emailAddresses)) {

                foreach ($emailAddresses as $email_address) {

                    $emailKey = md5($email_address); // aman dari karakter aneh

                    $cacheFile = 'email_sent_' 
                        . $approve_seq . '_' 
                        . $entity_cd . '_' 
                        . $doc_no . '_' 
                        . $level_no . '_' 
                        . $emailKey . '.txt';

                    $cacheFilePath = storage_path(
                        'app/mail_cache/email_terusan/send_Land_Request/' . date('Ymd') . '/' . $cacheFile
                    );

                    $cacheDirectory = dirname($cacheFilePath);

                    if (!file_exists($cacheDirectory)) {
                        mkdir($cacheDirectory, 0755, true);
                    }

                    // ===============================
                    // LOCK FILE (ANTI DOUBLE SEND)
                    // ===============================
                    $lockFile   = $cacheFilePath . '.lock';
                    $lockHandle = fopen($lockFile, 'w');

                    if (!$lockHandle || !flock($lockHandle, LOCK_EX)) {
                        if ($lockHandle) fclose($lockHandle);
                        throw new Exception('Failed to acquire lock');
                    }

                    // ===============================
                    // KIRIM EMAIL JIKA BELUM ADA CACHE
                    // ===============================
                    if (!file_exists($cacheFilePath)) {

                        Mail::to($email_address)
                            ->send(new SendNextLandReqeuest($dataArray));

                        file_put_contents($cacheFilePath, 'sent');

                        Log::channel('sendmailapproval')->info(
                            "Email Terusan Land Request doc_no {$doc_no} Entity {$entity_cd} berhasil dikirim ke: {$email_address}"
                        );

                        $callback['Pesan'] = "Email berhasil dikirim ke: $email_address";
                        $callback['Error'] = false;
                        $callback['Status']= 200;

                    } else {

                        Log::channel('sendmailapproval')->info(
                            "Email Terusan Land Request doc_no {$doc_no} Entity {$entity_cd} sudah pernah dikirim ke: {$email_address}"
                        );

                        $callback['Pesan'] = "Email sudah pernah dikirim ke: $email_address";
                        $callback['Error'] = true;
                        $callback['Status']= 400;
                    }
                }
            } else {
                Log::channel('sendmail')->warning(
                    "No email address provided for document {$doc_no}"
                );

                $callback = [
                    'Pesan'  => 'No email address provided',
                    'Error'  => true,
                    'Status' => 400
                ];
            }
        } catch (\Exception $e) {
            Log::channel('sendmail')->error("Gagal mengirim email: " . $e->getMessage());

            $callback['Pesan'] = "Gagal mengirim email: " . $e->getMessage();
            $callback['Error'] = true;
            $callback['Status']= 500;
        }

        return response()->json($callback, $callback['Status']);
    }
}
