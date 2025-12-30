<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class SmtpConfigService
{
    /**
     * Ambil konfigurasi SMTP default (entity_cd = '01')
     */
    public static function getSmtpConfig()
    {
        $config = DB::table('mgr.smtp_configuration')
            ->where('entity_cd', '01')
            ->first();

        if ($config) {
            $config->is_fallback = true;   // ← tandai fallback
            return $config;
        }

        return null;
    }

    /**
     * Ambil konfigurasi berdasarkan entity_cd
     * Fallback ke entity '01'
     */
    public static function getConfigByEntity($entityCd)
    {
        // 1. Cari config sesuai entity
        $config = DB::table('mgr.smtp_configuration')
            ->where('entity_cd', $entityCd)
            ->first();

        // 2. Kalau ada, tandai bukan fallback
        if ($config) {
            $config->is_fallback = false;
            return $config;
        }

        // 3. Kalau tidak ada → fallback ke getSmtpConfig()
        return self::getSmtpConfig();
    }

    /**
     * Apply konfigurasi SMTP ke Laravel
     */
    public static function applyConfig($entityCd)
    {
        $config = self::getConfigByEntity($entityCd);

        // Jika tidak ada sama sekali → biarkan .env
        if (!$config) {
            return;
        }

        // SMTP override
        config([
            'mail.mailers.smtp.host'        => $config->host,
            'mail.mailers.smtp.port'        => $config->port,
            'mail.mailers.smtp.encryption'  => $config->encryption,
            'mail.mailers.smtp.username'    => $config->username,
            'mail.mailers.smtp.password'    => $config->password,
        ]);

        // FROM override:
        // - jika fallback → gunakan .env
        // - jika original → gunakan DB (jika ada)
        config([
            'mail.from.address' => $config->is_fallback
                ? config('mail.from.address')
                : ($config->from_address ?? config('mail.from.address')),

            'mail.from.name' => $config->is_fallback
                ? config('mail.from.name')
                : ($config->from_name ?? config('mail.from.name')),
        ]);
    }
}