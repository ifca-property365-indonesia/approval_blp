<?php

if (!function_exists('getEntityLogo')) {
    function getEntityLogo($entityName)
    {
        if (empty($entityName)) {
            return url('public/images/email_header.png');
        }

        $baseName = trim($entityName);

        // PRIORITAS SELALU _
        $primaryFile = str_replace(' ', '_', $baseName) . '.png';

        $primaryPath = public_path('images/logo/' . $primaryFile);

        // gunakan file _ jika ada
        if (file_exists($primaryPath)) {
            return url('public/images/logo/' . $primaryFile);
        }

        // fallback legacy lama
        $fallbackFiles = [
            $baseName . '.png',
            strtolower(str_replace(' ', '_', $baseName)) . '.png',
        ];

        foreach ($fallbackFiles as $file) {
            $path = public_path('images/logo/' . $file);

            if (file_exists($path)) {
                return url('public/images/logo/' . $file);
            }
        }

        return url('public/images/email_header.png');
    }
}