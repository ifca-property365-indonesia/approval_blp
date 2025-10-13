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
              <img src="{{ url('public/images/email_header.png') }}" alt="logo" height="56px" style="display:block; border:0; outline:none; text-decoration:none;">
              <p style="font-size:16px; color:#026735; margin:10px 0 0;">PT BANGUN LAKSANA PERSADA</p>
            </td>
          </tr>

        <!-- Content -->
        <tr>
            <td style="text-align:center;padding: 50px 30px;">
                <img style="width:88px; margin-bottom:24px;" src="{{ url('public/images/double_approve.png') }}" alt="Verified">
                <p>Do you want to {{ $valuebt }} this request ?</p>
                <form id="frmEditor" class="form-horizontal" method="POST" action="{{ url('/api/getaccess') }}" enctype="multipart/form-data">
                @csrf
                <input type="text" id="status" name="status" value="<?php echo $status?>" hidden>
                <input type="text" id="doc_no" name="doc_no" value="<?php echo $doc_no?>" hidden>
                <input type="text" id="encrypt" name="encrypt" value="<?php echo $encrypt?>" hidden>
                <input type="text" id="module" name="module" value="<?php echo $module?>" hidden>
                <input type="text" id="email" name="email" value="<?php echo $email?>" hidden>
                <?php if ($status != 'A'): ?>
                    <?php if ($status == 'R'): ?>
                        <p>Please provide the reasons for requesting this revision</p>
                    <?php elseif ($status == 'C'): ?>
                        <p>Please provide the reasons for requesting the cancellation of this revision</p>
                    <?php endif; ?>
                    <div class="form-group">
                        <textarea class="form-control" id="reason" name="reason" rows="3"></textarea>
                    </div>
                <?php endif; ?>
                <input type="submit" class="btn" style="background-color:<?php echo $bgcolor?>;color:#ffffff;display:inline-block;font-size:13px;font-weight:600;line-height:44px;text-align:center;text-decoration:none;text-transform: uppercase; padding: 0px 40px;margin: 10px" value=<?php echo $valuebt?>>
                </form>
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