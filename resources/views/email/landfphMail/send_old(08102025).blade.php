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
        table {
            margin: 50 auto;
        }
    </style>
    
</head>

<body width="100%" style="margin: 0; padding: 0 !important; mso-line-height-rule: exactly; background-color: #ffffff;color: #000000;">
	<div style="width: 100%; background-color: #e6f0eb; text-align: center;">
        <table width="80%" border="0" cellpadding="0" cellspacing="0" bgcolor="#e6f0eb" style="margin-left: auto;margin-right: auto;" >
            <tr>
                <td style="padding: 40px 0;">
                    <table style="width:100%;max-width:600px;margin:0 auto;">
                        @include('template.header')
                    </table>
                    <table style="margin-left:200px;width:100%;max-width:800px;margin:0 auto;background-color:#ffffff;">
                        <tbody>
                            <tr>
                                <td style="text-align:center;padding: 0px 30px 0px 20px">
                                    <h5 style="margin-bottom: 24px; color: #000000; font-size: 20px; font-weight: 400; line-height: 28px;">Untuk Bapak/Ibu {{ $data['user_name'] }}</h5>
                                    <p style="text-align:left;color: #000000; font-size: 14px;">Tolong berikan persetujuan untuk Form Penawaran Harga dengan detail :</p>
                                    <table cellpadding="0" cellspacing="0" style="text-align:left;width:100%;max-width:800px;margin:0 auto;font-size: 14px;background-color:#ffffff; color: #000000;">
                                        <tr>
                                            <td>Nomor Dokumen</td>
                                            <td> : </td>
                                            <td> {{ $data['doc_no'] }} </td>
                                        </tr>
                                        <tr>
                                            <td>Nama PT</td>
                                            <td> : </td>
                                            <td> {{ $data['entity_name'] }} </td>
                                        </tr>
                                        <tr>
                                            <td>NOP</td>
                                            <td> : </td>
                                            <td> {{ $data['nop_no'] }} </td>
                                        </tr>
                                        <tr>
                                            <td>Nama SPPT</td>
                                            <td> : </td>
                                            <td> {{ $data['name_land'] }} </td>
                                        </tr>
                                        <tr>
                                            <td>Nama Pemilik</td>
                                            <td>:</td>
                                            <td>{{ $data['name_owner'] }}</td>
                                        </tr>
                                        <tr>
                                            <td>Tanggal FPH</td>
                                            <td>:</td>
                                            <td>{{ $data['transaction_date'] }}</td>
                                        </tr>
                                        <tr>
                                            <td>Harga yang disepakati</td>
                                            <td>:</td>
                                            <td style="text-align: right;">Rp. {{ $data['total_amt'] }}</td>
                                        </tr>
                                        <tr>
                                            <td>Uang Tanda Jadi</td>
                                            <td>:</td>
                                            <td style="text-align: right;">Rp. {{ $data['book_amt'] }}</td>
                                        </tr>
                                    </table>
                                    <br>
                                    <p style="text-align:left;margin-bottom: 15px; color: #000000; font-size: 14px;">
                                        <b>Terimakasih,</b><br>
                                        {{ $data['sender_name'] }}
                                    </p>
                                    <br>
                                    <a href="{{ url('api') }}/{{ $data['link'] }}/A/{{ $data['entity_cd'] }}/{{ $data['doc_no'] }}/{{ $data['level_no'] }}" style="background-color:#1ee0ac;border-radius:4px;color:#ffffff;display:inline-block;font-size:13px;font-weight:600;line-height:44px;text-align:center;text-decoration:none;text-transform: uppercase; padding: 0px 40px;margin: 10px">Approve</a>
                                    <a href="{{ url('api') }}/{{ $data['link'] }}/R/{{ $data['entity_cd'] }}/{{ $data['doc_no'] }}/{{ $data['level_no'] }}" style="background-color:#f4bd0e;border-radius:4px;color:#ffffff;display:inline-block;font-size:13px;font-weight:600;line-height:44px;text-align:center;text-decoration:none;text-transform: uppercase; padding: 0px 40px;margin: 10px">Request Info</a>
                                    <a href="{{ url('api') }}/{{ $data['link'] }}/C/{{ $data['entity_cd'] }}/{{ $data['doc_no'] }}/{{ $data['level_no'] }}" style="background-color:#e85347;border-radius:4px;color:#ffffff;display:inline-block;font-size:13px;font-weight:600;line-height:44px;text-align:center;text-decoration:none;text-transform: uppercase; padding: 0px 40px;margin: 10px">Reject</a>
                                    <br>
                                    @if ($data['url_link'] != 'EMPTY')
                                    <p style="text-align:left;margin-bottom: 15px; color: #000000; font-size: 14px">
                                    <b style="font-style:italic;">Untuk melihat lampiran, tolong klik tautan dibawah ini : </b><br>
                                        @if ( is_array($data['url_link']) || is_object($data['url_link']) )
                                            @foreach ($data['url_link'] as $tampil)
                                                <a href={{ $tampil }} target="_blank">{{ trim(str_replace('%20', ' ',substr($tampil, strrpos($tampil, '/') + 1))) }}</a><br><br>
                                            @endforeach
                                        @else
                                            <a href={{ $data['url_link'] }} target="_blank">{{ trim(str_replace('%20', ' ',substr($data['url_link'], strrpos($data['url_link'], '/') + 1))) }}</a><br><br>
                                        @endif
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