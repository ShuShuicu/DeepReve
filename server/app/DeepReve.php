<?php
/**
 * DeepReve配置
 */
if (!defined('ANON_ALLOWED_ACCESS')) exit;
$DeepReve = [
    'upload' => [
        'baseDir' => __DIR__ . '/Uploads', // 实际存储目录
        'allowedExtensions' => [ // 允许的文件类型
            'txt', 
            'pdf', 
            'doc', 
            'docx', 
            'xls', 
            'xlsx', 
            'jpg', 
            'png', 
            'zip'
        ],
        'fileTypes' => [
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'],
            'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'],
            'archive' => ['zip', 'rar', '7z', 'tar', 'gz'],
            'code' => ['php', 'js', 'css', 'html', 'json', 'xml']
        ]
    ],
];