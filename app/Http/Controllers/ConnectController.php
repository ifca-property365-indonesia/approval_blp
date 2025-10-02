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
            // Coba koneksi pakai default connection di config/database.php (ambil dari .env)
            DB::connection()->getPdo();

            // Jika sukses
            $dbName = DB::connection()->getDatabaseName();
            return "✅ Berhasil konek ke database: " . $dbName;
        } catch (Exception $e) {
            return "❌ Gagal konek ke database. Error: " . $e->getMessage();
        }
    }
}
