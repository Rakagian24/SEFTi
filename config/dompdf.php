<?php

return [
    /*
    |--------------------------------------------------------------------------
    | DomPDF Options
    |--------------------------------------------------------------------------
    |
    | Available options: https://github.com/dompdf/dompdf#options
    |
    */

    'options' => [
        'isRemoteEnabled' => false,
        'isHtml5ParserEnabled' => true,
        'isFontSubsettingEnabled' => true,
        'defaultFont' => 'Arial',
        'chroot' => public_path(),
        'tempDir' => storage_path('app/temp'),
        'logOutputFile' => storage_path('logs/dompdf.log'),
        'debugKeepTemp' => false,
        'debugCss' => false,
        'debugLayout' => false,
        'debugLayoutLines' => false,
        'debugLayoutBlocks' => false,
        'debugLayoutInline' => false,
        'debugLayoutPaddingBox' => false,
        'pdfBackend' => 'CPDF',
        'defaultMediaType' => 'screen',
        'defaultPaperSize' => 'a4',
        'defaultPaperOrientation' => 'portrait',
        'dpi' => 96,
        'fontHeightRatio' => 0.9,
        'enablePhp' => false,
        'enableJavascript' => false,
        'enableRemote' => false,
        'fontCache' => storage_path('fonts/'),
        'tempDir' => storage_path('app/temp/'),
        'chroot' => realpath(base_path()),
        'allowUrlFopen' => false,
        'autoScriptToLang' => false,
        'autoLangToFont' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | DomPDF Fonts
    |--------------------------------------------------------------------------
    |
    | Configure custom fonts for DomPDF
    |
    */

    'fonts' => [
        'Arial' => [
            'normal' => 'arial.ttf',
            'bold' => 'arialbd.ttf',
            'italic' => 'ariali.ttf',
            'bold_italic' => 'arialbi.ttf',
        ],
        'Helvetica' => [
            'normal' => 'helvetica.ttf',
            'bold' => 'helveticab.ttf',
            'italic' => 'helveticai.ttf',
            'bold_italic' => 'helveticabi.ttf',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | DomPDF Default Options
    |--------------------------------------------------------------------------
    |
    | Default options for PDF generation
    |
    */

    'defaults' => [
        'paper_size' => 'a4',
        'orientation' => 'portrait',
        'dpi' => 96,
        'font_height_ratio' => 0.9,
    ],
];
