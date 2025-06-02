# DeepReve

基于Vue3+PHP开发的简单(陋)下载盘

前端代码为 `client`

后端代码为 `server`

运行环境：

- PHP >= 7.4+
- MySQL >= 5.6+

运行方法：
1. 下载代码
2. 将`server`文件上传至站点根目录
3. 访问站点域名进行安装配置数据库

使用说明: 
1. 文件默认上传至`app/Uploads`目录下，可修改`DeepReve.php`中`$baseDir`的值
2. 如需自定义前端样式，则需要修改`client`下的`src/components/FileList.vue`