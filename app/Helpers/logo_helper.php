<?php

if (!function_exists('getEntityLogo')) {
    function getEntityLogo($entityName)
    {
        // 🔥 handle null / kosong
        if (empty($entityName)) {
            return url('public/images/email_header.png');
        }

        $baseName = trim($entityName);

        $fileNames = [
            $baseName . '.png',
            str_replace(' ', '_', $baseName) . '.png',
            strtolower(str_replace(' ', '_', $baseName)) . '.png',
        ];

        foreach ($fileNames as $file) {
            $path = public_path('images/logo/' . $file);
            if (file_exists($path)) {
                return url('public/images/logo/' . $file);
            }
        }

        return url('public/images/email_header.png'); // default
    }
}