<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Exception;

class DatabaseConnectionController extends Controller
{
    public function checkConnection()
    {
        try {
            // Test the custom database connection
            DB::connection('BLP')->getPdo();

            // If successful
            return response()->json(['status' => 'success', 'message' => 'Connected to Matahari database'], 200);
        } catch (Exception $e) {
            // If connection fails
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
