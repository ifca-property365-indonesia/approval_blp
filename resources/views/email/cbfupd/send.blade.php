<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>IFCA - PWON</title>

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
            <td class="content" style="background-color:#e0e0e0; padding: 30px 30px; color:#000000; font-size:14px; line-height:22px;">
              <h5 style="font-size:20px; font-weight:400; margin:0 0 15px;">Dear {{ $dataArray['user_name'] }}, </h5>
              <p style="margin:0 0 15px;">Below is a propose to transfer that requires your approval :</p>

              <!-- Detail Table -->
              <p style="text-align:left; margin-bottom: 15px; margin-top: 0; color: #000000; font-size: 16px; list-style-type: circle;">
                    <b>{{ $dataArray['band_hd_descs'] }}</b><br>
                    From Bank : {{ $dataArray['bank_from'] }}<br>
                    To Bank : {{ $dataArray['bank_to'] }}<br>
                    With a total amount of IDR {{ $dataArray['dt_amount'] }}<br>
                    FUPB No.: {{ $dataArray['band_hd_no'] }}<br>
                </p>

              <!-- Attachments -->
              @php $hasAttachment = false; @endphp
              @foreach($dataArray['url_file'] as $key => $url_file)
                    @if($url_file !== '' && $dataArray['file_name'][$key] !== '' && $url_file !== 'EMPTY' && $dataArray['file_name'][$key] !== 'EMPTY')
                        @if(!$hasAttachment)
                            @php
                                $hasAttachment = true;
                            @endphp
                            <p style="text-align:left; margin-bottom: 15px; color: #000000; font-size: 16px;">
                                <span>To View a detailed Propose Transfer Bank To Bank, please click on the link below :</span><br>
                        @endif
                        <a href="{{ $url_file }}" target="_blank">{{ $dataArray['file_name'][$key] }}</a><br>
                    @endif
                @endforeach

              <!-- Buttons -->
              <div style="text-align: center; margin: 20px 0;">
                <a href="{{ config('app.url') }}/api/processdata/{{ $dataArray['module'] }}/A/{{ $encryptedData }}" style="display: inline-block; font-size: 13px; font-weight: 600; line-height: 20px; text-align: center; text-decoration: none; text-transform: uppercase; padding: 10px 40px; background-color: #1ee0ac; border-radius: 4px; color: #ffffff;">Approve</a>
                <a href="{{ config('app.url') }}/api/processdata/{{ $dataArray['module'] }}/R/{{ $encryptedData }}" style="display: inline-block; font-size: 13px; font-weight: 600; line-height: 20px; text-align: center; text-decoration: none; text-transform: uppercase; padding: 10px 40px; background-color: #f4bd0e; border-radius: 4px; color: #ffffff;">Revise</a>
                <a href="{{ config('app.url') }}/api/processdata/{{ $dataArray['module'] }}/C/{{ $encryptedData }}" style="display: inline-block; font-size: 13px; font-weight: 600; line-height: 20px; text-align: center; text-decoration: none; text-transform: uppercase; padding: 10px 40px; background-color: #e85347; border-radius: 4px; color: #ffffff;">Reject</a>
              </div>

              <p style="margin:15px 0;">In case you need some clarification, kindly approach:<br>
                <a href="mailto:{{ $dataArray['clarify_email'] }}" style="text-decoration: none; color: inherit;">
                    {{ $dataArray['clarify_user'] }}
                </a>
              </p>

              <p style="text-align:left;margin-bottom: 15px; color: #000000; font-size: 16px;">
                    <b>Thank you,</b><br>
                    <a href="mailto:{{ $dataArray['sender_addr'] }}" style="text-decoration: none; color: inherit;">
                        {{ $dataArray['sender'] }}
                    </a>
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