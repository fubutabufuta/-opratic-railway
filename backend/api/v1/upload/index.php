<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/database.php';

class FileUploadAPI
{
    private $conn;
    private $uploadDir;

    public function __construct()
    {
        try {
            $database = new Database();
            $this->conn = $database->getConnection();

            // Upload klasörünü oluştur
            $this->uploadDir = '../../uploads/';
            if (!file_exists($this->uploadDir)) {
                mkdir($this->uploadDir, 0755, true);
            }

            // Alt klasörleri oluştur
            $subDirs = ['profiles', 'licenses', 'vehicles', 'attachments'];
            foreach ($subDirs as $dir) {
                $path = $this->uploadDir . $dir . '/';
                if (!file_exists($path)) {
                    mkdir($path, 0755, true);
                }
            }
        } catch (Exception $e) {
            error_log("Upload API initialization failed: " . $e->getMessage());
            $this->conn = null;
        }
    }

    public function handleRequest()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(405, ['error' => 'Method not allowed']);
            return;
        }

        try {
            $this->uploadFile();
        } catch (Exception $e) {
            $this->sendResponse(500, ['error' => $e->getMessage()]);
        }
    }

    private function uploadFile()
    {
        // POST parametrelerini kontrol et
        $userId = $_POST['user_id'] ?? null;
        $fileType = $_POST['file_type'] ?? null; // profile_photo, driving_license, vehicle_photo, quote_attachment

        if (!$userId || !$fileType) {
            $this->sendResponse(400, ['error' => 'user_id ve file_type gerekli']);
            return;
        }

        // Dosya kontrolü
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->sendResponse(400, ['error' => 'Dosya yükleme hatası']);
            return;
        }

        $file = $_FILES['file'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        // Dosya türü kontrolü
        if (!in_array($file['type'], $allowedTypes)) {
            $this->sendResponse(400, ['error' => 'Desteklenmeyen dosya türü. Sadece JPG, PNG, GIF ve PDF dosyaları kabul edilir.']);
            return;
        }

        // Dosya boyutu kontrolü
        if ($file['size'] > $maxSize) {
            $this->sendResponse(400, ['error' => 'Dosya boyutu 5MB\'dan büyük olamaz']);
            return;
        }

        // Dosya türüne göre klasör belirle
        $subDir = $this->getSubDirectory($fileType);
        $uploadPath = $this->uploadDir . $subDir . '/';

        // Benzersiz dosya adı oluştur
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $fileType . '_' . $userId . '_' . time() . '.' . $extension;
        $fullPath = $uploadPath . $filename;

        // Dosyayı kaydet
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            $this->sendResponse(500, ['error' => 'Dosya kaydetme hatası']);
            return;
        }

        // Database'e kaydet
        if ($this->conn !== null) {
            try {
                $stmt = $this->conn->prepare("
                    INSERT INTO file_uploads 
                    (user_id, file_type, original_filename, stored_filename, file_path, file_size, mime_type)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");

                $relativePath = '/uploads/' . $subDir . '/' . $filename;

                $stmt->execute([
                    $userId,
                    $fileType,
                    $file['name'],
                    $filename,
                    $relativePath,
                    $file['size'],
                    $file['type']
                ]);

                // User tablosunu güncelle
                if ($fileType === 'profile_photo') {
                    $updateStmt = $this->conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                    $updateStmt->execute([$relativePath, $userId]);
                } elseif ($fileType === 'driving_license') {
                    $updateStmt = $this->conn->prepare("UPDATE users SET driving_license_photo = ? WHERE id = ?");
                    $updateStmt->execute([$relativePath, $userId]);
                }

                $this->sendResponse(200, [
                    'success' => true,
                    'message' => 'Dosya başarıyla yüklendi',
                    'file_path' => $relativePath,
                    'file_id' => $this->conn->lastInsertId()
                ]);
            } catch (Exception $e) {
                // Database hatası durumunda dosyayı sil
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
                $this->sendResponse(500, ['error' => 'Database kayıt hatası: ' . $e->getMessage()]);
            }
        } else {
            // Database olmadan da çalışsın
            $relativePath = '/uploads/' . $subDir . '/' . $filename;
            $this->sendResponse(200, [
                'success' => true,
                'message' => 'Dosya başarıyla yüklendi (local)',
                'file_path' => $relativePath,
                'file_id' => time()
            ]);
        }
    }

    private function getSubDirectory($fileType)
    {
        switch ($fileType) {
            case 'profile_photo':
                return 'profiles';
            case 'driving_license':
                return 'licenses';
            case 'vehicle_photo':
                return 'vehicles';
            case 'quote_attachment':
                return 'attachments';
            default:
                return 'general';
        }
    }

    private function sendResponse($code, $data)
    {
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit();
    }
}

$api = new FileUploadAPI();
$api->handleRequest();
