<?php
/**
 * utils/upload.php
 * 文件上传助手。仅允许图片/PDF，大小受控，存到 public/uploads/<module>/。
 * 返回相对路径 /uploads/<module>/<random>.<ext>。
 */

namespace Utils;

use Utils\DB;

class Upload
{
    /**
     * 保存上传文件，返回相对路径；失败抛出异常。
     *
     * @param array  $file   $_FILES['file'] 项
     * @param string $module 业务模块目录（org / leader / ...）
     * @throws \Exception
     */
    public static function saveUpload(array $file, string $module): string
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new \Exception('无效的上传请求');
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('上传失败，错误码 ' . $file['error']);
        }

        $originalName = basename($file['name'] ?? '');
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_EXT, true)) {
            throw new \Exception('不支持的文件类型：' . ($ext ?: '未知'));
        }
        if (!isset($file['size']) || $file['size'] > UPLOAD_MAX_SIZE) {
            throw new \Exception('文件超过大小限制（' . (UPLOAD_MAX_SIZE / 1024 / 1024) . 'MB）');
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new \Exception('非法上传文件');
        }

        $module = preg_replace('/[^a-zA-Z0-9_]/', '', $module) ?: 'misc';
        $destDir = ROOT_DIR . '/public/uploads/' . $module;
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $newName = bin2hex(random_bytes(12)) . '.' . $ext;
        $destPath = $destDir . '/' . $newName;
        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            throw new \Exception('文件保存失败');
        }

        return '/uploads/' . $module . '/' . $newName;
    }
}
