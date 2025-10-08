<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Type" content="application/pdf">
    <meta name="x-apple-disable-message-reformatting">
    <title></title>
    
    <link href="https://fonts.googleapis.com/css?family=Vollkorn:400,600" rel="stylesheet" type="text/css">
    <style>
        html, body {
            width: 100%;
            color: #000000 !important;
        }

        /* Normal font size for table */
        table {
        font-size: 14px; /* adjust as needed */
        }

        /* Media query for phone view */
        @media only screen and (max-width: 600px) {
        table {
            font-size: 2px; /* Adjust this value for smaller screens */
        }
        }
        
    </style>
    
</head>

<body width="100%" style="margin: 0; padding: 0 !important; mso-line-height-rule: exactly; background-color: #ffffff;color: #000000;">
	<div style="width: 100%; background-color: #e6f0eb; text-align: center;">
        <table width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#e6f0eb" style="margin-left: auto;margin-right: auto;" >
            <tr>
                <td style="padding: 40px 0;">
                    <table style="width:100%;max-width:600px;margin:0 auto;">
                        @include('template.header')
                    </table>
                    <table style="margin-left:100px;width:100%;max-width:1200px;margin:0 auto;background-color:#ffffff;">
                        <tbody>
                            <tr>
                                <td style="text-align:center;padding: 0px 30px 0px 20px">
                                    <h5 style="margin-bottom: 24px; color: #000000; font-size: 20px; font-weight: 400; line-height: 28px;">Untuk Bapak/Ibu {{ $data['user_name'] }}</h5>
                                    <p style="text-align:left;color: #000000; font-size: 14px;">Tolong berikan persetujuan untuk Pengajuan Pembayaran dengan detail :</p>
                                    <table cellpadding="0" cellspacing="0" style="text-align:left;width:100%;max-width:1000px;margin:0 auto;background-color:#ffffff;">
                                    <tr>
                                        <th class="no-wrap" style="border: 1px solid #dddddd;text-align: center;padding: 5px;">Nomor Dokumen</th>
                                        <th class="no-wrap" style="border: 1px solid #dddddd;text-align: center;padding: 5px;">Nama Pemilik</th>
                                        <th class="no-wrap" style="border: 1px solid #dddddd;text-align: center;padding: 5px;">Rincian Pengajuan</th>
                                        <th class="no-wrap" style="border: 1px solid #dddddd;text-align: center;padding: 5px;">NOP</th>
                                        <th class="no-wrap" style="border: 1px solid #dddddd;text-align: center;padding: 5px;">Periode SPH</th>
                                        <th class="no-wrap" style="border: 1px solid #dddddd;text-align: center;padding: 5px;">Nominal Pengajuan</th>
                                    </tr>
                                    @if(isset($data['type']) && is_array($data['type']) && count($data['type']) > 0)
                                    <!-- Find and display the first merge -->
                                    @if(isset($data['type'][0]))
                                        <tr>
                                            <td class="no-wrap" style="border: 1px solid #dddddd;padding: 5px;">{{ $data['doc_no'] }}</td>
                                            <td class="no-wrap" style="border: 1px solid #dddddd;padding: 5px;">{{ $data['owner'][0] }}</td>
                                            <td class="no-wrap" style="border: 1px solid #dddddd;padding: 5px;">{{ $data['type'][0] }}</td>
                                            <td class="no-wrap" style="border: 1px solid #dddddd;padding: 5px;">{{ $data['nop_no'][0] }}</td>
                                            <td class="no-wrap" style="border: 1px solid #dddddd;padding: 5px;">{{ $data['sph_trx_no'][0] }}</td>
                                            <td class="no-wrap" style="border: 1px solid #dddddd;padding: 5px;text-align: right;">Rp. {{ $data['request_amt'][0] }}</td>
                                        </tr>  
                                    @endif

                                    <!-- Display other merges -->
                                    @for($i = 1; $i < count($data['type']); $i++)
                                        @if(isset($data['owner'][$i], $data['type'][$i], $data['nop_no'][$i], $data['sph_trx_no'][$i], $data['request_amt'][$i]))
                                            <tr>
                                                <td class="no-wrap" style="border: 1px solid #dddddd;padding: 5px;">{{ $data['doc_no'] }}</td>
                                                <td class="no-wrap" style="border: 1px solid #dddddd;padding: 5px;">{{ $data['owner'][$i] }}</td>
                                                <td class="no-wrap" style="border: 1px solid #dddddd;padding: 5px;">{{ $data['type'][$i] }}</td>
                                                <td class="no-wrap" style="border: 1px solid #dddddd;padding: 5px;">{{ $data['nop_no'][$i] }}</td>
                                                <td class="no-wrap" style="border: 1px solid #dddddd;padding: 5px;">{{ $data['sph_trx_no'][$i] }}</td>
                                                <td class="no-wrap" style="border: 1px solid #dddddd;padding: 5px;text-align: right;">Rp. {{ $data['request_amt'][$i] }}</td>
                                            </tr>
                                        @endif
                                    @endfor
                                    <tr>
                                        <th class="no-wrap"></th>
                                        <th class="no-wrap"></th>
                                        <th class="no-wrap" id="total" colspan="3">Total Pengajuan : </th>
                                        <th class="no-wrap" style="border: 1px solid #dddddd;padding: 5px;text-align: right;">Rp. {{ $data['sum_amt'] }}</th>
                                    </tr>
                                @endif
                                    </table>
                                    <br>
                                    <p style="text-align:left;margin-bottom: 15px; color: #000000; font-size: 14px;">
                                        <b>Terimakasih,</b><br>
                                        {{ $data['sender_name'] }}
                                    </p>
                                    <br>
                                    <!--[if mso]>
                                    <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ url('api') }}/{{ $data['link'] }}/A/{{ $data['entity_cd'] }}/{{ $data['doc_no'] }}/{{ $data['level_no'] }}" style="height:44px;v-text-anchor:middle;width:200px;" arcsize="8%" stroke="f" fillcolor="#1ee0ac">
                                        <w:anchorlock/>
                                        <center style="color:#ffffff;font-family:sans-serif;font-size:13px;font-weight:bold;margin:10px;">Approve</center>
                                    </v:roundrect>
                                    <![endif]-->
                                    <!--[if !mso]-->
                                    <a href="{{ config('app.url') }}/api/{{ $data['link'] }}/A/{{ $data['entity_cd'] }}/{{ $data['doc_no'] }}/{{ $data['level_no'] }}" target="_blank" style="background-color:#1ee0ac;border-radius:4px;color:#ffffff;display:inline-block;font-size:13px;font-weight:600;line-height:44px;text-align:center;text-decoration:none;text-transform: uppercase; padding: 0px 40px;margin: 10px">Approve</a>
                                    <!--<![endif]-->

                                    <!--[if mso]>
                                    <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ url('api') }}/{{ $data['link'] }}/R/{{ $data['entity_cd'] }}/{{ $data['doc_no'] }}/{{ $data['level_no'] }}" style="height:44px;v-text-anchor:middle;width:200px;" arcsize="8%" stroke="f" fillcolor="#f4bd0e">
                                        <w:anchorlock/>
                                        <center style="color:#ffffff;font-family:sans-serif;font-size:13px;font-weight:bold;margin:10px;">Request Info</center>
                                    </v:roundrect>
                                    <![endif]-->
                                    <!--[if !mso]-->
                                    <a href="{{ config('app.url') }}/api/{{ $data['link'] }}/R/{{ $data['entity_cd'] }}/{{ $data['doc_no'] }}/{{ $data['level_no'] }}" target="_blank" style="background-color:#f4bd0e;border-radius:4px;color:#ffffff;display:inline-block;font-size:13px;font-weight:600;line-height:44px;text-align:center;text-decoration:none;text-transform: uppercase; padding: 0px 40px;margin: 10px">Request Info</a>
                                    <!--<![endif]-->

                                    <!--[if mso]>
                                    <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ url('api') }}/{{ $data['link'] }}/C/{{ $data['entity_cd'] }}/{{ $data['doc_no'] }}/{{ $data['level_no'] }}" style="height:44px;v-text-anchor:middle;width:200px;" arcsize="8%" stroke="f" fillcolor="#e85347">
                                        <w:anchorlock/>
                                        <center style="color:#ffffff;font-family:sans-serif;font-size:13px;font-weight:bold;margin:10px;">Reject</center>
                                    </v:roundrect>
                                    <![endif]-->
                                    <!--[if !mso]-->
                                    <a href="{{ config('app.url') }}/api/{{ $data['link'] }}/C/{{ $data['entity_cd'] }}/{{ $data['doc_no'] }}/{{ $data['level_no'] }}" target="_blank" style="background-color:#e85347;border-radius:4px;color:#ffffff;display:inline-block;font-size:13px;font-weight:600;line-height:44px;text-align:center;text-decoration:none;text-transform: uppercase; padding: 0px 40px;margin: 10px">Reject</a>
                                    <!--<![endif]-->
                                    <br>
                                    @php
                                        $hasAttachment = false;
                                    @endphp

                                    @foreach($data['url_file'] as $key => $url_file)
                                        @if($url_file !== '' && $data['file_name'][$key] !== '' && $url_file !== 'EMPTY' && $data['file_name'][$key] !== 'EMPTY')
                                            @if(!$hasAttachment)
                                                @php
                                                    $hasAttachment = true;
                                                @endphp
                                                <p style="text-align:left; margin-bottom: 15px; color: #000000; font-size: 14px;">
                                                    <b style="font-style:italic;">Untuk melihat lampiran, tolong klik tautan dibawah ini : </b><br>
                                            @endif
                                            <a href="{{ $url_file }}" target="_blank">{{ $data['file_name'][$key] }}</a><br>
                                        @endif
                                    @endforeach

                                    @if($hasAttachment)
                                        </p>
                                    @endif

                                    @php
                                        $hasApproval = false;
                                        $counter = 0;
                                    @endphp

                                    @foreach($data['approve_list'] as $key => $approve_list)
                                        @if($approve_list !== '' && $approve_list !== 'EMPTY')
                                            @if(!$hasApproval)
                                                @php
                                                    $hasApproval = true;
                                                @endphp
                                                <p style="text-align:left; margin-bottom: 15px; color: #000000; font-size: 14px;">
                                                    <span>Sudah disetujui oleh :</span><br>
                                            @endif
                                            {{ ++$counter }}. {{ $approve_list }} - {{ $data['approved_date'][$key] }}<br>
                                        @endif
                                    @endforeach

                                    @if($hasApproval)
                                        </p>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                        @include('template.footer')
                    </table>
                </td>
            </tr>
        </table>
        </div>
</body>
</html>