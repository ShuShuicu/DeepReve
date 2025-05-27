<?php

/**
 * Anon Database
 */
if (!defined('ANON_ALLOWED_ACCESS')) exit;

class Anon_Database
{
    private $pdo;

    /**
     * 构造函数：初始化数据库连接
     */
    public function __construct()
    {
        $this->conn = new mysqli(
            ANON_DB_HOST,
            ANON_DB_USER,
            ANON_DB_PASSWORD,
            ANON_DB_DATABASE,
            ANON_DB_PORT
        );

        if ($this->conn->connect_error) {
            die("数据库连接失败: " . $this->conn->connect_error);
        }

        $this->conn->set_charset(ANON_DB_CHARSET);
    }
    /**
     * 执行查询并返回结果
     * @param string $sql SQL 查询语句
     * @return array 查询结果
     */
    public function query($sql)
    {
        $result = $this->conn->query($sql);
        if (!$result) {
            die("SQL 查询错误: " . $this->conn->error);
        }

        // 如果是 SELECT 查询，返回结果数组
        if ($result instanceof mysqli_result) {
            $rows = [];
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            return $rows;
        }

        // 返回受影响的行数
        return $this->conn->affected_rows;
    }

    /**
     * 准备并执行预处理语句
     * @param string $sql SQL 查询语句
     * @param array $params 参数数组
     * @return bool|mysqli_stmt 预处理语句对象
     */
    public function prepare($sql, $params = [])
    {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            die("SQL 预处理错误: " . $this->conn->error);
        }

        if (!empty($params)) {
            $types = str_repeat('s', count($params)); // 默认所有参数为字符串
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        return $stmt;
    }

    /**
     * 记录文件下载信息
     * @param string $filePath 文件路径
     * @param string $fileName 文件名
     * @param int $fileSize 文件大小
     * @param string $ip IP地址
     * @param string $userAgent 用户代理
     * @param int|null $userId 用户ID(可选)
     * @return bool 是否成功
     */
    public function logFileDownload($filePath, $fileName, $fileSize, $ip, $userAgent, $userId = null)
    {
        $sql = "INSERT INTO " . ANON_DB_PREFIX . "file_downloads 
            (file_path, file_name, file_size, ip_address, user_agent, download_time, user_id) 
            VALUES (?, ?, ?, ?, ?, NOW(), ?)";

        $stmt = $this->prepare($sql, [
            $filePath,
            $fileName,
            $fileSize,
            $ip,
            $userAgent,
            $userId
        ]);

        return $stmt->affected_rows > 0;
    }

    /**
     * 获取文件下载统计
     * @param string $filePath 文件路径
     * @return array|null 文件统计信息
     */
    public function getFileStats($filePath)
    {
        // 使用绝对路径匹配
        $sql = "SELECT download_count, last_download_time 
            FROM " . ANON_DB_PREFIX . "file_stats 
            WHERE file_path = ? 
            LIMIT 1";

        $stmt = $this->prepare($sql, [$filePath]);
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        // 如果没有记录，返回默认值
        return [
            'download_count' => 0,
            'last_download_time' => null
        ];
    }

    /**
     * 更新文件下载统计
     * @param string $filePath 文件路径
     * @return bool 是否成功
     */
    public function updateFileStats($filePath)
    {
        // 先尝试更新
        $sql = "UPDATE " . ANON_DB_PREFIX . "file_stats 
                SET download_count = download_count + 1, 
                    last_download_time = NOW() 
                WHERE file_path = ?";
        
        $stmt = $this->prepare($sql, [$filePath]);
        
        // 如果没有更新到记录，则插入新记录
        if ($stmt->affected_rows === 0) {
            $insertSql = "INSERT INTO " . ANON_DB_PREFIX . "file_stats 
                         (file_path, download_count, last_download_time) 
                         VALUES (?, 1, NOW())
                         ON DUPLICATE KEY UPDATE 
                         download_count = download_count + 1, 
                         last_download_time = NOW()";
            
            $stmt = $this->prepare($insertSql, [$filePath]);
        }
        
        return $stmt->affected_rows > 0;
    }

    /**
     * 添加留言
     * @param string $userName 用户名
     * @param string $userEmail 用户邮箱
     * @param string $content 留言内容
     * @param string $ip IP地址
     * @param string $userAgent 用户浏览器UA
     * @return bool 是否成功
     */
    public function addGuestbookEntry($userName, $userEmail, $content, $ip, $userAgent)
    {
        $sql = "INSERT INTO " . ANON_DB_PREFIX . "guestbook 
            (user_name, user_email, content, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->prepare($sql, [
            $userName,
            $userEmail,
            $content,
            $ip,
            $userAgent
        ]);

        return $stmt->affected_rows > 0;
    }

    /**
     * 获取留言列表
     * @param int $limit 每页数量
     * @param int $offset 偏移量
     * @return array 留言列表
     */
    public function getGuestbookEntries($limit = 10, $offset = 0)
    {
        $sql = "SELECT * FROM " . ANON_DB_PREFIX . "guestbook 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?";

        $stmt = $this->prepare($sql, [$limit, $offset]);
        $stmt->store_result();

        $entries = [];
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $name, $email, $content, $ip, $ua, $createdAt);
            while ($stmt->fetch()) {
                $entries[] = [
                    'id' => $id,
                    'user_name' => $name,
                    'user_email' => $email,
                    'content' => $content,
                    'ip_address' => $ip,
                    'user_agent' => $ua,
                    'created_at' => $createdAt
                ];
            }
        }

        return $entries;
    }

    /**
     * 获取留言总数
     * @return int 留言总数
     */
    public function getGuestbookCount()
    {
        $sql = "SELECT COUNT(*) FROM " . ANON_DB_PREFIX . "guestbook";
        $stmt = $this->prepare($sql);
        $stmt->store_result();
        $stmt->bind_result($count);
        $stmt->fetch();
        return $count;
    }

    /**
     * 关闭数据库连接
     */
    public function __destruct()
    {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
