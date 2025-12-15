@php
    $baseUrl = rtrim(config('app.url'), '/');
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>IFCA - Bangun Laksana Persada</title>
  <style type="text/css">
    /* Reset */
    body, table, td, p {
      margin: 0;
      padding: 0;
      mso-line-height-rule: exactly;
    }
    img {
      border: 0;
      display: block;
      line-height: 0;
      -ms-interpolation-mode: bicubic;
    }
    table {
      border-collapse: collapse;
      mso-table-lspace: 0pt;
      mso-table-rspace: 0pt;
    }
    /* Mobile responsive */
    @media screen and (max-width: 600px) {
      .main-container {
        width: 100% !important;
      }
      .button {
        display: block !important;
        width: 100% !important;
        margin-bottom: 10px !important;
      }
      .content {
        padding: 20px !important;
      }
    }
  </style>
</head>

<body style="margin:0; padding:0; background-color:#ffffff; font-family: Arial, Helvetica, sans-serif;">

  <!-- Background Table -->
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#ffffff;">
    <tr>
      <td align="center" style="padding:40px 10px;">

        <!-- Main Container -->
        <table role="presentation" width="600" class="main-container" cellpadding="0" cellspacing="0" border="0" style="width:600px; background-color:#ffffff; border-collapse:collapse; border:1px solid #dcdcdc;">
          
          <!-- Header -->
          <tr>
            <td align="center" style="padding:25px 10px;">
              <img src="{{ url('public/images/email_header.png') }}" 
                   alt="logo" 
                   width="180" 
                   style="display:block; border:0; outline:none; text-decoration:none;">
              <p style="font-size:16px; color:#026735; margin:10px 0 0; font-weight:bold;">{{ $dataArray['entity_name'] }}</p>
            </td>
          </tr>

          <!-- Content -->
          <tr>
            <td class="content" style="background-color:#f7f7f7; padding:30px 40px; color:#000000; font-size:14px; line-height:22px; border-top:1px solid #dddddd; border-bottom:1px solid #dddddd;">
              
              <h5 style="font-size:20px; font-weight:400; margin:0 0 15px;">Dear {{ $dataArray['user_name'] }},</h5>
              <p style="margin:0 0 15px;">Tolong berikan persetujuan untuk Proses Penggabungan SHGB dengan detail berikut:</p>

              <!-- Detail Table -->
              <table role="presentation" cellpadding="6" cellspacing="0" border="0" width="100%" style="font-size:14px; color:#000000; background-color:#ffffff; border:1px solid #dcdcdc; border-radius:4px;">
                <tr><td width="40%" style="padding:6px 10px;">Nomor Dokumen</td><td width="2%" style="padding:6px;">:</td><td style="padding:6px 10px;">{{ $dataArray['doc_no'] }}</td></tr>
                <tr><td style="padding:6px 10px;">Nomor SHGB (Merge)</td><td style="padding:6px;">:</td><td style="padding:6px 10px;">{{ $dataArray['merge_ref_no'] }}</td></tr>
                <tr><td style="padding:6px 10px;">Luas SHGB (Merge)</td><td style="padding:6px;">:</td><td style="padding:6px 10px;">{{ $dataArray['merge_area'] }}</td></tr>
                <tr><td style="padding:6px 10px;">Tanggal Penggabungan SHGB</td><td style="padding:6px;">:</td><td style="padding:6px 10px;">{{ $dataArray['transaction_date'] }}</td></tr>

                @if(isset($dataArray['shgb_ref_no']) && is_array($dataArray['shgb_ref_no']) && count($dataArray['shgb_ref_no']) > 0)
                  @if(isset($dataArray['shgb_ref_no'][0]))
                      <tr>
                        <td style="padding:6px 10px;">SHGB yang digabungkan</td>
                        <td style="padding:6px;">:</td>
                        <td style="padding:6px 10px;">{{ $dataArray['shgb_ref_no'][0] }}</td>
                      </tr>  
                  @endif
                  @foreach(array_slice($dataArray['shgb_ref_no'], 1) as $merge)
                      <tr>
                        <td style="padding:6px 10px;"></td>
                        <td style="padding:6px;"></td>
                        <td style="padding:6px 10px;">{{ $merge }}</td>
                      </tr>
                  @endforeach
                @endif
              </table>

              <!-- Attachments -->
              @if (!empty($dataArray['attachments']) && count($dataArray['attachments']) > 0)
                <p style="margin:20px 0 10px;">Untuk melihat detail transaksi, klik tautan di bawah ini:</p>
                @foreach($dataArray['attachments'] as $attachment)
                  <a href="{{ $attachment['url'] }}" target="_blank" style="color:#026735; text-decoration:none;">
                    {{ $attachment['file_name'] }}
                  </a><br>
                @endforeach
              @endif

              <!-- Buttons -->
              <table role="presentation" align="center" style="margin:25px auto;">
                <tr>
                  <td align="center" style="padding:5px;">
                    <a href="{{ $baseUrl }}/{{ $dataArray['link'] }}/A/{{ $encryptedData }}" class="button" style="display:inline-block; font-size:13px; font-weight:600; text-transform:uppercase; text-decoration:none; background-color:#1ee0ac; color:#ffffff; padding:10px 30px; border-radius:3px;">Approve</a>
                  </td>
                  <td align="center" style="padding:5px;">
                    <a href="{{ $baseUrl }}/{{ $dataArray['link'] }}/R/{{ $encryptedData }}" class="button" style="display:inline-block; font-size:13px; font-weight:600; text-transform:uppercase; text-decoration:none; background-color:#f4bd0e; color:#ffffff; padding:10px 30px; border-radius:3px;">Revise</a>
                  </td>
                  <td align="center" style="padding:5px;">
                    <a href="{{ $baseUrl }}/{{ $dataArray['link'] }}/C/{{ $encryptedData }}" class="button" style="display:inline-block; font-size:13px; font-weight:600; text-transform:uppercase; text-decoration:none; background-color:#e85347; color:#ffffff; padding:10px 30px; border-radius:3px;">Reject</a>
                  </td>
                </tr>
              </table>

              <p style="margin:15px 0;">Jika butuh klarifikasi, silakan hubungi:<br>
                <a href="mailto:{{ $dataArray['clarify_email'] }}" style="color:#026735;">{{ $dataArray['clarify_user'] }}</a>
              </p>

              <p style="margin:15px 0;">
                <b>Terima kasih,</b><br>
                <a href="mailto:{{ $dataArray['sender_addr'] }}" style="color:#026735;">{{ $dataArray['sender_name'] }}</a>
              </p>

              <!-- Approval List -->
              @php $hasApproval = false; $counter = 0; @endphp
              @foreach($dataArray['approve_list'] as $key => $approve_list)
                @if($approve_list && $approve_list != 'EMPTY')
                  @if(!$hasApproval)
                    @php $hasApproval = true; @endphp
                    <p style="margin:15px 0;">Permintaan ini telah disetujui oleh:<br>
                  @endif
                  {{ ++$counter }}. {{ $approve_list }}<br>
                @endif
              @endforeach
              @if($hasApproval)</p>@endif

              <p style="margin:15px 0;"><b>Mohon tidak membalas email ini, karena dikirim otomatis.</b></p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td align="center" style="padding:25px 10px 30px; font-size:13px; color:#555555;">
              Copyright © 2023 IFCA Software. All rights reserved.
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>