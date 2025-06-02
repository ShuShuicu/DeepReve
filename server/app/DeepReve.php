<?php
/**
 * DeepReve配置
 */
if (!defined('ANON_ALLOWED_ACCESS')) exit;
$DeepReve = [
    'upload' => [
        'baseDir' => __DIR__ . '/Uploads', // 实际存储目录
        'allowedExtensions' => [ // 允许的文件类型
            'txt', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 
            'jpg', 'png', 'webp', 'zip',
            'mp3', 'wav', 'ogg', 'flac', 'aac',
            'mp4', 'mov', 'avi', 'mkv', 'webm'
        ],
        'fileTypes' => [ // 匹配文件类型
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'],
            'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'],
            'archive' => ['zip', 'rar', '7z', 'tar', 'gz'],
            'code' => ['php', 'js', 'css', 'html', 'json', 'xml'],
            'audio' => ['mp3', 'wav', 'ogg', 'flac', 'aac', 'm4a'],
            'video' => ['mp4', 'mov', 'avi', 'mkv', 'webm', 'flv', 'wmv']
        ]
    ],
];