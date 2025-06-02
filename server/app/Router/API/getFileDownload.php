<?php
if (!defined('ANON_ALLOWED_ACCESS')) exit;
require_once __DIR__ . '/../../DeepReve.php';

// 配置
$baseDir = $DeepReve['upload']['baseDir'];
$allowedExtensions = $DeepReve['upload']['allowedExtensions'];

// 获取请求的文件名
$requestedFile = $_GET['name'] ?? '';

// 安全验证
if (empty($requestedFile)) {
    http_response_code(400);
    header('Content-Type: text/plain');
    die('Invalid file name');
}

// 解码并清理路径
$requestedFile = urldecode($requestedFile);
$requestedFile = rawurldecode($requestedFile);
$requestedFile = ltrim($requestedFile, '/');
$requestedFile = str_replace(['../', '..\\'], '', $requestedFile);
$requestedFile = mb_convert_encoding($requestedFile, 'UTF-8', 'UTF-8');

// 构建完整路径
$filePath = $baseDir . DIRECTORY_SEPARATOR . $requestedFile;

// 检查文件是否存在
if (!file_exists($filePath) || !is_file($filePath)) {
    http_response_code(404);
    header('Content-Type: text/plain');
    die('File not found');
}

// 检查文件类型是否允许
$extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
if (!in_array($extension, $allowedExtensions)) {
    http_response_code(403);
    header('Content-Type: text/plain');
    die('File type not allowed');
}

// 记录下载信息
try {
    $db = new Anon_Database();
    $clientIP = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
    
    $db->logFileDownload(
        $filePath,
        basename($filePath),
        filesize($filePath),
        $clientIP,
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    );
    
    $db->updateFileStats($filePath);
} catch (Exception $e) {
    error_log("Failed to log download: " . $e->getMessage());
}

// 关键修改点：简化响应头设置
header_remove(); // 清除所有现有头

// 仅设置必要的下载头
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
header('Content-Length: ' . filesize($filePath));

// 禁用缓存
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// 输出文件
readfile($filePath);
exit;