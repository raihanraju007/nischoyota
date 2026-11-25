-- Test database used by phpunit.xml (DB_DATABASE=nischoyota_testing).
CREATE DATABASE IF NOT EXISTS nischoyota_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON nischoyota_testing.* TO 'nischoyota'@'%';
