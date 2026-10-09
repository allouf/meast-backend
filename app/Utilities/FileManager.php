<?php

namespace App\Utilities;

use App\Models\Category;
use App\Models\Provider;
use App\Models\ProviderCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileManager
{
    /*********************************************************************************************************
     * Uploads a file to the specified directory.

     *********************************************************************************************************/
    public static function upload(string $dir, UploadedFile $file): string
    {
        $fileName = self::generateUniqueFileName($file);
        self::ensureDirectoryExists($dir);
        $disk = self::getDisk();
        Storage::disk($disk)->putFileAs($dir, $file, $fileName);
        return $dir . '/' . $fileName;
    }

    /*********************************************************************************************************
     * Updates a file in the specified directory. If an old file path is provided, it will be deleted.

     *********************************************************************************************************/
    public static function update(string $dir, UploadedFile $newFile, ?string $oldFilePath = null): string
    {
        if ($oldFilePath) {
            self::delete($oldFilePath);
        }
        return self::upload($dir, $newFile);
    }

    /*********************************************************************************************************
     * Updates multiple files in the specified directory. If old files are provided, they will be deleted.

     *********************************************************************************************************/
    public static function multiUpdate(string $dir, array $newFiles, ?string $oldFilesJson = null): string
    {
        $oldFiles = $oldFilesJson ? json_decode($oldFilesJson, true) : [];
        foreach ($oldFiles as $oldFile) {
            self::delete($oldFile);
        }
        $newFilePaths = [];
        foreach ($newFiles as $file) {
            $newFilePaths[] = self::upload($dir, $file);
        }
        return json_encode($newFilePaths);
    }

    /*********************************************************************************************************
     * Deletes a file from the storage.
     *********************************************************************************************************/
    public static function delete(string $filePath): bool
    {
        $disk = self::getDisk();
        $cleanPath = str_replace("storage/", "", $filePath);

        if (Storage::disk($disk)->exists($cleanPath)) {
            return Storage::disk($disk)->delete($cleanPath);
        }
        return false;
    }

    /*********************************************************************************************************
     * Renames a file in the storage.

     *********************************************************************************************************/
    public static function rename(string $filePath, string $newName): string
    {
        $disk = self::getDisk();
        $dir = dirname($filePath);
        $newFilePath = $dir . '/' . $newName;
        if (Storage::disk($disk)->exists($filePath)) {
            Storage::disk($disk)->move($filePath, $newFilePath);
            return $newFilePath;
        }
        return $filePath;
    }




    /*********************************************************************************************************
     * Generates a unique file name for the uploaded file.

     *********************************************************************************************************/
    protected static function generateUniqueFileName(UploadedFile $file): string
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $sanitizedName = preg_replace('/[^a-zA-Z0-9-_]/', '_', $originalName);
        return time() . '_' . $sanitizedName . '.' . $extension;
    }

    /*********************************************************************************************************
     * Ensures that the specified directory exists in the storage.

     *********************************************************************************************************/
    protected static function ensureDirectoryExists(string $dir): void
    {
        $disk = self::getDisk();
        if (!Storage::disk($disk)->exists($dir)) {
            Storage::disk($disk)->makeDirectory($dir);
        }
    }

    /*********************************************************************************************************
     * Returns the public URL of a stored file.
     *********************************************************************************************************/
    public static function getUrl(string $path): string
    {
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $cleanPath = str_replace('storage/', '', $path);
        $disk = self::getDisk();

        return Storage::disk($disk)->url($cleanPath);
    }

    /*********************************************************************************************************
     * Returns the full storage path for a file.
     *********************************************************************************************************/
    protected static function getFilePath(string $dir, string $fileName): string
    {
        return $dir . '/' . $fileName;
    }

    /*********************************************************************************************************
     * Decodes a JSON string of attachment names into an array.

     *********************************************************************************************************/
    protected static function decodeAttachmentNames(string $attachmentNames): array
    {
        return json_decode($attachmentNames, true) ?? [];
    }

    /*********************************************************************************************************
     * Extracts the original file name from a unique file name.

     *********************************************************************************************************/
    public static function getOriginalName(string $uniqueName): string
    {
        $basename = basename($uniqueName);

        $pos = strpos($basename, '_');
        return $pos !== false ? substr($basename, $pos + 1) : $basename;
    }


    /*********************************************************************************************************
     * Returns the size of a file in a human-readable format.

     *********************************************************************************************************/
    public static function getFileSize(string $fullPath): string
    {
        $disk = self::getDisk();
        $relativePath = str_replace('storage' . '/', '', $fullPath);

        if (!Storage::disk($disk)->exists($relativePath)) {
            throw new \Exception("File not found at: $relativePath");
        }

        $bytes = Storage::disk($disk)->size($relativePath);

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }


    /*********************************************************************************************************
     * Returns the file extension of a file.

     *********************************************************************************************************/
    public static function getFileExtension(string $filePath): string
    {
        return pathinfo($filePath, PATHINFO_EXTENSION);
    }

    /*********************************************************************************************************
     * Checks if a file exists in the storage.

     *********************************************************************************************************/
    public static function fileExists(string $filePath): bool
    {
        $disk = self::getDisk();
        return Storage::disk($disk)->exists($filePath);
    }

    /*********************************************************************************************************
     * Copies a file from one location to another in the storage.

     *********************************************************************************************************/
    public static function copyFile(string $sourcePath, string $destinationPath): bool
    {
        $disk = self::getDisk();
        return Storage::disk($disk)->copy($sourcePath, $destinationPath);
    }

    /*********************************************************************************************************
     * Moves a file from one location to another in the storage.

     *********************************************************************************************************/
    public static function moveFile(string $sourcePath, string $destinationPath): bool
    {
        $disk = self::getDisk();
        return Storage::disk($disk)->move($sourcePath, $destinationPath);
    }

    /*********************************************************************************************************
     * Returns the MIME type of a file.

     *********************************************************************************************************/
    public static function getMimeType(string $filePath): ?string
    {
        $disk = self::getDisk();
        return Storage::disk($disk)->mimeType($filePath);
    }

    /*********************************************************************************************************
     * Returns the last modified time of a file.

     *********************************************************************************************************/
    public static function getLastModified(string $filePath): int
    {
        $disk = self::getDisk();
        return Storage::disk($disk)->lastModified($filePath);
    }
    protected static function getDisk(): string
    {
        return config('filesystems.default', 'public');
    }
    /*********************************************************************************************************
     * Returns the type of a file.

     *********************************************************************************************************/
    public static function getFileType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        } elseif (str_starts_with($mimeType, 'video/')) {
            return 'video';
        } elseif (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        } elseif (str_contains($mimeType, 'pdf') || str_contains($mimeType, 'msword') || str_contains($mimeType, 'officedocument')) {
            return 'document';
        }
        throw new \Exception("Unsupported file type: $mimeType");
    }

    /*********************************************************************************************************
     * Returns the type of a file based on its file path/extension.

     *********************************************************************************************************/
    public static function getFileTypeFromPath(string $filePath): string
    {
        $extension = strtolower(self::getFileExtension($filePath));

        $types = [
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'],
            'video' => ['mp4', 'mov', 'avi', 'wmv', 'flv', 'mkv', 'webm'],
            'audio' => ['mp3', 'wav', 'ogg', 'aac', 'm4a'],
            'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt']
        ];

        foreach ($types as $type => $extensions) {
            if (in_array($extension, $extensions)) {
                return $type;
            }
        }

        return 'unknown';
    }
}
