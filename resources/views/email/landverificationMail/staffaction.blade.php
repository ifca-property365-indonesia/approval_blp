<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>IFCA - PWON</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ url('public/images/pakuwon_icon.ico') }}">
    
    <style>
        body {
            font-family: Arial;
        }
    </style>
    

</head>

<body width="100%" style="margin: 0; padding: 0 !important; mso-line-height-rule: exactly; background-color: #ffffff;">
	<div style="width: 100%; background-color: #ffffff; text-align: center;">
        <table width="80%" border="0" cellpadding="0" cellspacing="0" bgcolor="#ffffff" style="margin-left: auto;margin-right: auto;" >
            <tr>
               <td style="padding: 40px 0;">
                    <table style="width:100%;max-width:620px;margin:0 auto;">
                        <tbody>
                            <tr>
                                <td style="text-align: center; padding-bottom:25px">
                                    <img width = "130" src="{{ url('public/images/PWON-logo.png') }}" alt="logo">
                                    <p style="font-size: 16px; color: #026735; padding-top: 0px;">{{ $data['entity_name'] }}</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <table style="width:100%;max-width:620px;margin:0 auto;background-color:#e0e0e0;">
                        <tbody>
                            <tr>
                                <td style="text-align:center; padding: 50px 30px;">
                                    <h5 style="text-align:left; margin-bottom: 24px; color: #000000; font-size: 20px; font-weight: 400; line-height: 28px;">
                                        Dear {{ $data['user_name'] }},
                                    </h5>

                                    <p style="text-align:left; margin-bottom: 15px; color: #000000; font-size: 16px;">
                                        {{ $data['bodyEMail'] }}
                                    </p>

                                    <!-- Attachments -->
                                    @if (!empty($data['attachments']) && count($data['attachments']) > 0)
                                        <p style="text-align:left; margin:20px 0 10px; color:#000000; font-size:16px;">
                                            To view detail transaction, please click the link below:
                                        </p>
                                        @foreach($data['attachments'] as $attachment)
                                            <p style="text-align:left; margin:0 0 5px;">
                                                <a href="{{ $attachment['url'] }}" target="_blank" style="color:#026735; text-decoration:none; font-size:16px;">
                                                    {{ $attachment['file_name'] }}
                                                </a>
                                            </p>
                                        @endforeach
                                    @endif
                                    <!-- /Attachments -->

                                    <br>

                                    <p style="text-align:left; margin-bottom: 15px; color: #000000; font-size: 16px;">
                                        <b>Thank you,</b><br>
                                        {{ $data['staff_act_send'] }}
                                    </p>
                                </td>
                            </tr>
                        </tbody>

                    </table>
                    <table style="width:100%;max-width:620px;margin:0 auto;">
                        <tbody>
                            <tr>
                                <td style="text-align: center; padding:25px 20px 0;">
                                    <p style="font-size: 13px;">Copyright © 2023 IFCA Software. All rights reserved.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
               </td>
            </tr>
        </table>
    </div>
</body>
</html>