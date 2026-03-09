<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Maximum File Size
    |--------------------------------------------------------------------------
    |
    | Maximum size of the SAF-T XML file that can be uploaded (in MB).
    | This must be consistent with the PHP upload_max_filesize and
    | post_max_size settings in your php.ini.
    |
    */
    'max_file_size_mb' => env('SAFT_MAX_FILE_SIZE_MB', 100),

    /*
    | Same value but in kilobytes (used for Laravel validation).
    */
    'max_file_size_kb' => env('SAFT_MAX_FILE_SIZE_MB', 100) * 1024,

    /*
    |--------------------------------------------------------------------------
    | SAF-T PT Namespace
    |--------------------------------------------------------------------------
    |
    | The XML namespace used in SAF-T PT 1.04_01 files.
    |
    */
    'namespace' => 'urn:OECD:StandardAuditFile-Tax:PT_1.04_01',
];
