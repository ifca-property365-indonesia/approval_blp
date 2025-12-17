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
    }
    img {
      border: 0;
      display: block;
      line-height: 0;
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
      <td align="center" style="padding:40px 0;">

        <!--[if mso]>
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0">
        <tr><td>
        <![endif]-->

        <!-- Main Container -->
        <table role="presentation" width="100%" class="main-container" style="max-width:600px; background-color:#ffffff; border-collapse:collapse;">
          
          <!-- Header -->
          <tr>
            <td align="center" style="padding-bottom:25px;">
              <img src="{{ url('public/images/email_header.png') }}" 
                                        alt="logo" 
                                        height="56" 
                                        style="display:inline-block; border:0; outline:none; text-decoration:none;">
              <p style="font-size:16px; color:#026735; margin:10px 0 0;">{{ $dataArray['entity_name'] }}</p>
            </td>
          </tr>

          <!-- Content -->
          <tr>
            <td class="content" style="background-color:#e0e0e0; padding:30px; color:#000000; font-size:14px; line-height:22px;">
              <h5 style="font-size:20px; font-weight:400; margin:0 0 15px;">Dear {{ $dataArray['user_name'] }},</h5>
              <p style="margin:0 0 15px;">{{ $dataArray['detail_text'] }}</p>

              <!-- Detail Table -->
              <table role="presentation" cellpadding="4" cellspacing="0" border="0" width="100%" style="font-size:14px; color:#000000;">
                <tr>
                  <th style="border: 1px solid #000000;text-align: center;padding: 8px;">Nomor Dokumen</th>
                  <th style="border: 1px solid #000000;text-align: center;padding: 8px;">Rincian Pengajuan</th>
                  <th style="border: 1px solid #000000;text-align: center;padding: 8px;">Nominal Pengajuan</th>
                </tr>
               @if(isset($dataArray['type']) && is_array($dataArray['type']) && count($dataArray['type']) > 0)
                  <!-- Find and display the first merge -->

                  @if(isset($dataArray['type'][0]))
                      <tr>
                          <td style="border: 1px solid #000;padding: 5px;"> {{ $dataArray['doc_no'] }} </td>
                          <td style="border: 1px solid #000;padding: 5px;"> {{ $dataArray['type'][0] }} </td>
                          <td style="border: 1px solid #000;padding: 5px;"> {{ $dataArray['request_amt'][0] }} </td>
                      </tr>    
                  @endif

                  <!-- Display other merges -->
                  @for($i = 1; $i < count($dataArray['type']); $i++)
                      @if(isset($dataArray['type'][$i]))
                          <tr>
                            <td style="border: 1px solid #000;padding: 5px;"> {{ $dataArray['doc_no'] }} </td>
                            <td style="border: 1px solid #000;padding: 5px;"> {{ $dataArray['type'][$i] }} </td>
                            <td style="border: 1px solid #000;padding: 5px;"> {{ $dataArray['request_amt'][$i] }} </td>
                          </tr>
                      @endif
                  @endfor
              @endif
              </table>

              <!-- Attachments -->
              @if (!empty($dataArray['attachments']) && count($dataArray['attachments']) > 0)
                  <p style="margin:20px 0 10px;">To view detail transaction, please click the link below:</p>
                  @foreach($dataArray['attachments'] as $attachment)
                      <a href="{{ $attachment['url'] }}" target="_blank" style="color:#026735; text-decoration:none;">
                          {{ $attachment['file_name'] }}
                      </a><br>
                  @endforeach
              @endif

              <p style="margin:15px 0;">In case you need some clarification, kindly approach:<br>
                <a href="mailto:{{ $dataArray['clarify_email'] }}" style="color:#026735;">{{ $dataArray['clarify_user'] }}</a>
              </p>

              <p style="margin:15px 0;">
                <b>Thank you,</b><br>
                <a href="mailto:{{ $dataArray['sender_addr'] }}" style="color:#026735;">{{ $dataArray['sender_name'] }}</a>
              </p>

              <!-- Approval List -->
              @php $hasApproval = false; $counter = 0; @endphp
              @foreach($dataArray['approve_list'] as $key => $approve_list)
                @if($approve_list && $approve_list != 'EMPTY')
                  @if(!$hasApproval)
                    @php $hasApproval = true; @endphp
                    <p style="margin:15px 0;">This request approval has been approved by:<br>
                  @endif
                  {{ ++$counter }}. {{ $approve_list }}<br>
                @endif
              @endforeach
              @if($hasApproval)</p>@endif

              <p style="margin:15px 0;"><b>Please do not reply, this is an automated email.</b></p>

            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td align="center" style="padding:25px 10px 0; font-size:13px; color:#555555;">
              Copyright © 2023 IFCA Software. All rights reserved.
            </td>
          </tr>
        </table>

        <!--[if mso]>
        </td></tr></table>
        <![endif]-->

      </td>
    </tr>
  </table>
</body>
</html>