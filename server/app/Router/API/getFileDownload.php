<?php
if (!defined('ANON_ALLOWED_ACCESS')) exit;
require_once __DIR__ . '/../../DeepReve.php';

// 配置
$baseDir = $DeepReve['upload']['baseDir'];
$allowedExtensions = $DeepReve['upload']['allowedExtensions'];

// 获取请求的文件名
$requestedFile = isset($_GET['name']) ? $_GET['name'] : '';

// 安全验证
if (empty($requestedFile)) {
    http_response_code(400);
    header('Content-Type: text/plain');
    die('Invalid file name');
}

// 解码并清理路径
$requestedFile = urldecode($requestedFile);
$requestedFile = rawurldecode($requestedFile); // 双重解码确保
$requestedFile = ltrim($requestedFile, '/');
$requestedFile = str_replace(['../', '..\\'], '', $requestedFile); // 防止目录遍历攻击

// 确保文件名是正确的UTF-8编码
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
    
    // 获取用户ID(如果有登录)
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // 记录下载
    $db->logFileDownload(
        $filePath,
        basename($filePath),
        filesize($filePath),
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT'],
        $userId
    );
    
    // 更新统计
    $db->updateFileStats($filePath);
} catch (Exception $e) {
    // 记录错误但不中断下载
    error_log("Failed to log download: " . $e->getMessage());
}

// 设置下载头
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode(basename($filePath)));
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

// 清空输出缓冲区
flush();
readfile($filePath);
exit;