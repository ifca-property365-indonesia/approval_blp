<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ConnectController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Koneksi pakai koneksi 'BLP'
            DB::connection('BLP')->getPdo();

            $dbName = DB::connection('BLP')->getDatabaseName();
            return "✅ Berhasil konek ke database: " . $dbName;
        } catch (\Exception $e) {
            return "❌ Gagal konek ke database. Error: " . $e->getMessage();
        }
    }


    public function info()
    {
        phpinfo();
    }
}
