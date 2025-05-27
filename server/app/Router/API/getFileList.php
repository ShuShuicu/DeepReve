<?php
if (!defined('ANON_ALLOWED_ACCESS')) exit;
require_once __DIR__ . '/../../DeepReve.php';

class DirectoryScanner
{
    private $rootDir;
    private $db;

    public function __construct($rootDir)
    {
        $this->rootDir = rtrim($rootDir, DIRECTORY_SEPARATOR);
        $this->db = new Anon_Database();
    }

    public function scan()
    {
        if (!is_dir($this->rootDir)) {
            throw new InvalidArgumentException("Directory does not exist");
        }

        return $this->scanDirectory($this->rootDir, '');
    }

    private function scanDirectory($dir, $parentName)
    {
        $result = [
            'folders' => [],
            'files' => []
        ];

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($this->shouldSkipItem($item)) {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $result['folders'][] = [
                    'name' => $item,
                    'type' => 'folder',
                    'modified' => $this->formatDate(filemtime($path)),
                    'items' => $this->scanDirectory($path, $item)
                ];
            } else {
                $stats = $this->db->getFileStats($path);
                
                // 生成dir字段：如果有父文件夹则用"父文件夹/文件名"，否则直接文件名
                $dirField = $parentName ? $parentName . '/' . $item : $item;
                
                $result['files'][] = [
                    'name' => $item,
                    'type' => $this->getFileType($item),
                    'size' => $this->formatSize(filesize($path)),
                    'dir' => $dirField,
                    'bytes' => filesize($path),
                    'modified' => $this->formatDate(filemtime($path)),
                    'extension' => pathinfo($item, PATHINFO_EXTENSION),
                    'download_count' => $stats ? $stats['download_count'] : 0,
                    'last_download_time' => $stats ? $stats['last_download_time'] : null
                ];
            }
        }

        // 按名称排序
        usort($result['folders'], fn($a, $b) => strcmp($a['name'], $b['name']));
        usort($result['files'], fn($a, $b) => strcmp($a['name'], $b['name']));

        return $result;
    }

    private function shouldSkipItem($item)
    {
        return $item === '.' || $item === '..';
    }

    private $fileTypes = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'],
        'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'],
        'archive' => ['zip', 'rar', '7z', 'tar', 'gz'],
        'code' => ['php', 'js', 'css', 'html', 'json', 'xml']
    ];

    private function getFileType($filename)
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        foreach ($this->fileTypes as $type => $extensions) {
            if (in_array($extension, $extensions)) {
                return $type;
            }
        }

        return 'file';
    }

    private function formatSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            return $bytes . ' bytes';
        } elseif ($bytes == 1) {
            return '1 byte';
        } else {
            return '0 bytes';
        }
    }

    private function formatDate($timestamp)
    {
        return date('Y-m-d H:i:s', $timestamp);
    }

    public function getJsonResult()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header('Content-Type: application/json');
        
        try {
            $data = $this->scan();
            return json_encode([
                'success' => true,
                'data' => $data
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (Exception $e) {
            return json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}

// 使用示例
try {
    $scanner = new DirectoryScanner($DeepReve['upload']['baseDir']);
    echo $scanner->getJsonResult();
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
