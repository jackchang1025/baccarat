CREATE DATABASE IF NOT EXISTS `mineadmin` DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_unicode_ci;

-- 授权
GRANT ALL PRIVILEGES ON `mineadmin`.* TO 'root'@'%' IDENTIFIED BY '12345678';
FLUSH PRIVILEGES;

-- 使用数据库
USE `mineadmin`;

-- 这里可以添加其他初始化SQL语句 
