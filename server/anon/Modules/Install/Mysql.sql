-- 创建文件下载记录表
CREATE TABLE IF NOT EXISTS `{prefix}file_downloads` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT '记录ID',
    `file_path` VARCHAR(512) NOT NULL COMMENT '文件路径',
    `file_name` VARCHAR(255) NOT NULL COMMENT '文件名',
    `file_size` BIGINT UNSIGNED NOT NULL COMMENT '文件大小(字节)',
    `ip_address` VARCHAR(45) NOT NULL COMMENT '下载者IP',
    `user_agent` TEXT COMMENT '用户浏览器UA',
    `download_time` DATETIME NOT NULL COMMENT '下载时间',
    `user_id` INT UNSIGNED DEFAULT NULL COMMENT '用户ID(如果登录)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文件下载记录表';

-- 创建文件下载统计表
CREATE TABLE IF NOT EXISTS `{prefix}file_stats` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT '统计ID',
    `file_path` VARCHAR(512) NOT NULL UNIQUE COMMENT '文件路径',
    `download_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '下载次数',
    `last_download_time` DATETIME COMMENT '最后下载时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文件下载统计表';

-- 创建留言表
CREATE TABLE IF NOT EXISTS `{prefix}guestbook` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT '留言ID',
    `user_name` VARCHAR(255) NOT NULL COMMENT '留言者姓名',
    `user_email` VARCHAR(255) NOT NULL COMMENT '留言者邮箱',
    `content` TEXT NOT NULL COMMENT '留言内容',
    `ip_address` VARCHAR(45) NOT NULL COMMENT '留言者IP',
    `user_agent` TEXT COMMENT '用户浏览器UA',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '留言时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='留言板表';