<?php
if (!defined('ANON_ALLOWED_ACCESS')) exit;
/**
 * 注册路由
 * @param string $path 路由路径
 * @param callable $handler 处理函数
 * @return void
 */
$Router =  __DIR__ . '/../app/Router/';
Anon_Config::addRoute('', function () use ($Router) {
    require_once $Router . 'Home.php';
});

Anon_Config::addRoute('API/getFileList', function () use ($Router) {
    require_once $Router . 'API/getFileList.php';
});
Anon_Config::addRoute('API/getFileDownload', function () use ($Router) {
    require_once $Router . 'API/getFileDownload.php';
});
Anon_Config::addRoute('API/guestBook', function () use ($Router) {
    require_once $Router . 'API/guestBook.php';
});

/**
 * Anon默认路由
 */
// 安装程序
Anon_Config::addRoute('anon/install', function () {
    require_once __DIR__ . '/Modules/Install/Install.php';
});
// 退出登录
Anon_Config::addRoute('anon/logout', function () {
    Anon_Check::logout();
});