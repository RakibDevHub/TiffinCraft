<?php

class ImageHelper
{
    public static function uploadImage($file, $folderName)
    {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024;

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'File upload error'];
        }

        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'message' => 'Only JPG, PNG, and GIF images are allowed'];
        }

        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'Image size must be less than 5MB'];
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $uploadPath = UPLOADS_DIR . '/' . $folderName . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return ['success' => true, 'filename' => $filename];
        }

        return ['success' => false, 'message' => 'Failed to upload image'];
    }

    public static function deleteImage($filename, $folderName)
    {
        $filePath = UPLOADS_DIR . '/' . $folderName . '/' . $filename;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}
