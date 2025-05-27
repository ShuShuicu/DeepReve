<?php
if (!defined('ANON_ALLOWED_ACCESS')) exit;

// 设置跨域头
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $db = new Anon_Database();
    
    // 处理POST请求 - 提交留言
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 获取输入数据
        $input = json_decode(file_get_contents('php://input'), true);
        
        // 验证必填字段
        if (empty($input['user_name']) || empty($input['user_email']) || empty($input['content'])) {
            throw new Exception('用户名、邮箱和内容为必填项');
        }
        
        // 基本验证
        $userName = trim($input['user_name']);
        $userEmail = trim($input['user_email']);
        $content = trim($input['content']);
        
        if (strlen($userName) > 255) {
            throw new Exception('用户名过长');
        }
        
        if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('邮箱格式不正确');
        }
        
        if (strlen($content) > 2000) {
            throw new Exception('留言内容过长');
        }
        
        // 获取客户端信息
        $ip = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // 添加留言
        $success = $db->addGuestbookEntry($userName, $userEmail, $content, $ip, $userAgent);
        
        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => '留言提交成功'
            ]);
        } else {
            throw new Exception('留言提交失败');
        }
        
        exit;
    }
    
    // 处理GET请求 - 获取留言列表
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        
        // 限制每页数量范围
        $limit = max(1, min(50, $limit));
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;
        
        // 获取留言列表和总数
        $entries = $db->getGuestbookEntries($limit, $offset);
        $total = $db->getGuestbookCount();
        
        echo json_encode([
            'success' => true,
            'data' => $entries,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ]
        ], JSON_PRETTY_PRINT);
        
        exit;
    }
    
    // 其他请求方法
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}