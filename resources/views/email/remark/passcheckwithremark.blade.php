<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>IFCA - Bangun Laksana Persada</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ url('public/images/email_header.png') }}">
    
    <style>
        body {
            font-family: Arial;
        }
    </style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>

</head>

<body width="100%" style="margin: 0; padding: 0 !important; mso-line-height-rule: exactly; background-color: #ffffff;">
	<div style="width: 100%; background-color: #ffffff; text-align: center;">
        <table width="80%" border="0" cellpadding="0" cellspacing="0" bgcolor="#ffffff" style="margin-left: auto;margin-right: auto;" >
            <tr>
               <td style="padding: 40px 0;">
                    <table style="width:100%;max-width:620px;margin:0 auto;">
                        <tbody>
                            <tr>
                                <td align="center" style="padding-bottom:25px;">
                                <img src="{{ url('public/images/email_header.png') }}" 
                                        alt="logo" 
                                        height="56" 
                                        style="display:inline-block; border:0; outline:none; text-decoration:none;">
                                <p style="font-size: 16px; color: #026735; padding-top: 0px;">{{ $entity_name }}</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <table style="width:100%;max-width:620px;margin:0 auto;background-color:#e0e0e0;">
                        <tbody>
                            <tr>
                                <td style="text-align:center;padding: 50px 30px;">
                                    <img style="width:88px; margin-bottom:24px;" src="{{ url('public/images/double_approve.png') }}" alt="Verified">
                                    <p>Do you want to {{ $valuebt }} this request ?</p>

                                    <form id="frmEditor" class="form-horizontal" method="POST" action="{{ url('api/' . $link . '/getaccess') }}" enctype="multipart/form-data">
                                        @csrf

                                        <input type="hidden" name="status" value="{{ $status }}">
                                        <input type="hidden" name="doc_no" value="{{ $doc_no }}">
                                        <input type="hidden" name="encrypt" value="{{ $encrypt }}">
                                        <input type="hidden" name="email" value="{{ $email }}">

                                        {{-- Tampilkan textarea hanya jika status R atau C --}}
                                        @if ($status == 'R')
                                            <p>Please provide the reasons for requesting this revision</p>
                                            <div class="form-group">
                                                <textarea class="form-control" id="reason" name="reason" rows="3"></textarea>
                                            </div>
                                        @elseif ($status == 'C')
                                            <p>Please provide the reasons for requesting the cancellation of this revision</p>
                                            <div class="form-group">
                                                <textarea class="form-control" id="reason" name="reason" rows="3"></textarea>
                                            </div>
                                        @else
                                            {{-- Jika status bukan R atau C, kirim reason kosong --}}
                                            <input type="hidden" name="reason" value="no reason">
                                        @endif

                                        <input type="submit" class="btn" 
                                            style="background-color:{{ $bgcolor }};color:#ffffff;display:inline-block;
                                                    font-size:13px;font-weight:600;line-height:44px;text-align:center;
                                                    text-decoration:none;text-transform:uppercase;padding:0px 40px;margin:10px"
                                            value="{{ $valuebt }}">
                                    </form>
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
