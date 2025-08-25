<?php

namespace MDMasudSikdar\WpKits\Helpers;

use WP_Error;

/**
 * Class File
 *
 * Advanced file and upload utility for WordPress.
 *
 * Features:
 * ✅ Upload files safely using WordPress standards
 * ✅ Delete files from server
 * ✅ Get URL from absolute file path
 * ✅ Ensure directories exist
 * ✅ Validate file types
 *
 * @package MDMasudSikdar\WpKits\Helpers
 */
class File
{
    /**
     * Upload a file safely using WordPress standards.
     *
     * @param array  $file    File array from $_FILES.
     * @param string $subDir  Optional subdirectory inside uploads folder.
     *
     * @return array|WP_Error Returns uploaded file info on success, WP_Error on failure.
     *
     * @example
     * ```php
     * use MDMasudSikdar\WpKits\Helpers\File;
     *
     * $result = File::upload($_FILES['my_file'], 'myplugin');
     * if (is_wp_error($result)) {
     *     File::logError('Upload failed', ['error' => $result->get_error_message()]);
     * } else {
     *     echo 'Uploaded file URL: ' . $result['url'];
     * }
     * ```
     */
    public static function upload(array $file, string $subDir = ''): \WP_Error|array
    {
        // Check if the file array has a name; if not, return an error
        if (empty($file['name'])) {
            return new WP_Error('no_file', 'No file uploaded.');
        }

        // Include WordPress file handling functions
        require_once ABSPATH . 'wp-admin/includes/file.php';

        // Prepare upload overrides: skip form validation
        $uploadOverrides = ['test_form' => false];

        // If a subdirectory is specified, dynamically modify upload directory
        if ($subDir) {
            Hook::addFilterRaw('upload_dir', function ($dirs) use ($subDir) {
                // Append subdirectory to base directory
                $dirs['subdir'] = '/' . $subDir;
                // Update absolute path for uploads
                $dirs['path'] = $dirs['basedir'] . $dirs['subdir'];
                // Update URL for uploads
                $dirs['url']  = $dirs['baseurl'] . $dirs['subdir'];
                // Return modified directories
                return $dirs;
            });
        }

        // Handle the file upload using WordPress API
        $movefile = wp_handle_upload($file, $uploadOverrides);

        // Remove filter to prevent affecting other uploads
        if ($subDir) {
            remove_all_filters('upload_dir');
        }

        // If upload succeeded and no error exists, return upload info
        if ($movefile && !isset($movefile['error'])) {
            return $movefile;
        }

        // Otherwise, return WP_Error with the upload error message
        return new \WP_Error('upload_error', $movefile['error'] ?? 'Unknown error');
    }

    /**
     * Delete a file from the server.
     *
     * @param string $filePath Absolute path to the file.
     *
     * @return bool True if deleted, false otherwise.
     *
     * @example
     * ```php
     * use MDMasudSikdar\WpKits\Helpers\File;
     *
     * $deleted = File::delete(WP_CONTENT_DIR . '/uploads/myplugin/file.jpg');
     * echo $deleted ? 'File deleted' : 'File not found';
     * ```
     */
    public static function delete(string $filePath): bool
    {
        // Check if the file exists
        if (file_exists($filePath)) {
            // Delete the file and return true if successful
            return unlink($filePath);
        }

        // File does not exist, return false
        return false;
    }

    /**
     * Get the URL of a file from its absolute path.
     *
     * @param string $filePath Absolute file path.
     *
     * @return string Public URL of the file.
     *
     * @example
     * ```php
     * use MDMasudSikdar\WpKits\Helpers\File;
     *
     * $url = File::getUrl(WP_CONTENT_DIR . '/uploads/myplugin/file.jpg');
     * echo $url;
     * ```
     */
    public static function getUrl(string $filePath): string
    {
        // Get WordPress uploads directory info (paths and URLs)
        $uploads = wp_get_upload_dir();

        // Replace base directory with base URL to get public URL
        return str_replace($uploads['basedir'], $uploads['baseurl'], $filePath);
    }

    /**
     * Ensure a directory exists, creates it if missing.
     *
     * @param string $path Directory path.
     *
     * @return string Directory path.
     *
     * @example
     * ```php
     * use MDMasudSikdar\WpKits\Helpers\File;
     *
     * $dir = File::ensureDir(WP_CONTENT_DIR . '/uploads/myplugin/temp');
     * echo $dir;
     * ```
     */
    public static function ensureDir(string $path): string
    {
        // Check if directory exists
        if (!file_exists($path)) {
            // Create directory recursively if missing
            wp_mkdir_p($path);
        }

        // Return the directory path
        return $path;
    }

    /**
     * Validate a file MIME type.
     *
     * @param string $fileType MIME type to validate.
     * @param array  $allowedTypes Allowed MIME types array.
     *
     * @return bool True if type is allowed, false otherwise.
     *
     * @example
     * ```php
     * use MDMasudSikdar\WpKits\Helpers\File;
     *
     * $isValid = File::validateType('image/jpeg', ['image/jpeg', 'image/png']);
     * echo $isValid ? 'Valid type' : 'Invalid type';
     * ```
     */
    public static function validateType(string $fileType, array $allowedTypes = []): bool
    {
        // Check if the file MIME type exists in allowed types array
        return in_array($fileType, $allowedTypes, true);
    }

    /**
     * Internal logger for errors within the helper.
     *
     * @param string $message Error message.
     * @param array  $context Optional additional context.
     *
     * @example
     * ```php
     * use MDMasudSikdar\WpKits\Helpers\File;
     *
     * File::logError('Failed to upload file', ['file' => $_FILES['my_file']]);
     * ```
     */
    protected static function logError(string $message, array $context = []): void
    {
        // Only log if WP_DEBUG is defined and true
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        // Current timestamp for log entry
        $timestamp = date('Y-m-d H:i:s');

        // Current class name for context
        $class = static::class;

        // Convert context array to pretty JSON string
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context, JSON_PRETTY_PRINT) : '';

        // Capture backtrace for file and line number
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $location = $trace[1] ?? null ? sprintf(' [%s:%d]', $trace[1]['file'], $trace[1]['line']) : '';

        // Construct final log message
        $logMessage = sprintf("[%s] [%s]%s %s", $timestamp, $class, $location, $message . $contextStr);

        // Write to PHP error log
        error_log($logMessage);

        // Write to custom log file in wp-content
        @file_put_contents(WP_CONTENT_DIR . '/file-helper.log', $logMessage . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get the file name from a full path.
     *
     * @param string $filePath Absolute path or URL.
     *
     * @return string File name with extension.
     *
     * @example
     * ```php
     * $name = File::getFileName('/var/www/html/wp-content/uploads/myplugin/file.jpg');
     * echo $name; // file.jpg
     * ```
     */
    public static function getFileName(string $filePath): string
    {
        return basename($filePath);
    }

    /**
     * Get the file extension from a path or file name.
     *
     * @param string $filePath Absolute path or file name.
     *
     * @return string File extension without dot.
     *
     * @example
     * ```php
     * $ext = File::getFileExtension('/uploads/myplugin/file.jpg');
     * echo $ext; // jpg
     * ```
     */
    public static function getFileExtension(string $filePath): string
    {
        return pathinfo($filePath, PATHINFO_EXTENSION);
    }

    /**
     * Check if a file exists.
     *
     * @param string $filePath Absolute path.
     *
     * @return bool True if file exists, false otherwise.
     *
     * @example
     * ```php
     * $exists = File::isFileExists('/uploads/myplugin/file.jpg');
     * echo $exists ? 'File exists' : 'File missing';
     * ```
     */
    public static function isFileExists(string $filePath): bool
    {
        return file_exists($filePath);
    }

    /**
     * Get the file size in bytes.
     *
     * @param string $filePath Absolute path.
     *
     * @return int File size in bytes, 0 if file missing.
     *
     * @example
     * ```php
     * $size = File::getFileSize('/uploads/myplugin/file.jpg');
     * echo $size . ' bytes';
     * ```
     */
    public static function getFileSize(string $filePath): int
    {
        return self::isFileExists($filePath) ? filesize($filePath) : 0;
    }

    /**
     * Copy a file to a new location.
     *
     * @param string $sourcePath Absolute path of source file.
     * @param string $destPath   Absolute path of destination file.
     *
     * @return bool True if copy succeeded, false otherwise.
     *
     * @example
     * ```php
     * $copied = File::copy('/uploads/myplugin/file.jpg', '/uploads/myplugin/backup/file.jpg');
     * echo $copied ? 'File copied' : 'Copy failed';
     * ```
     */
    public static function copy(string $sourcePath, string $destPath): bool
    {
        // Ensure source file exists
        if (!self::isFileExists($sourcePath)) {
            return false;
        }

        // Ensure destination directory exists
        $destDir = dirname($destPath);
        self::ensureDir($destDir);

        // Copy the file
        return copy($sourcePath, $destPath);
    }

    /**
     * Move or rename a file to a new location.
     *
     * @param string $sourcePath Absolute path of source file.
     * @param string $destPath   Absolute path of destination file.
     *
     * @return bool True if move/rename succeeded, false otherwise.
     *
     * @example
     * ```php
     * $moved = File::move('/uploads/myplugin/file.jpg', '/uploads/myplugin/backup/file.jpg');
     * echo $moved ? 'File moved' : 'Move failed';
     * ```
     */
    public static function move(string $sourcePath, string $destPath): bool
    {
        // Ensure source file exists
        if (!self::isFileExists($sourcePath)) {
            return false;
        }

        // Ensure destination directory exists
        $destDir = dirname($destPath);
        self::ensureDir($destDir);

        // Use PHP rename to move or rename the file
        return rename($sourcePath, $destPath);
    }

    /**
     * Read the contents of a file.
     *
     * @param string $filePath Absolute path to the file.
     *
     * @return string|false File contents or false if file missing.
     *
     * @example
     * ```php
     * $content = File::read('/uploads/myplugin/file.txt');
     * echo $content;
     * ```
     */
    public static function read(string $filePath): string|false
    {
        // Return file_get_contents if file exists
        return self::isFileExists($filePath) ? file_get_contents($filePath) : false;
    }

    /**
     * Write content to a file.
     *
     * @param string $filePath Absolute path to the file.
     * @param string $content  Content to write.
     *
     * @return bool True on success, false on failure.
     *
     * @example
     * ```php
     * $written = File::write('/uploads/myplugin/file.txt', 'Hello World');
     * echo $written ? 'File written' : 'Write failed';
     * ```
     */
    public static function write(string $filePath, string $content): bool
    {
        // Ensure destination directory exists
        self::ensureDir(dirname($filePath));

        // Use file_put_contents to write content
        return file_put_contents($filePath, $content) !== false;
    }

    /**
     * Download a remote file to a local path.
     *
     * @param string $url      Remote file URL.
     * @param string $destPath Local path to save file.
     *
     * @return bool True if download succeeded, false otherwise.
     *
     * @example
     * ```php
     * $downloaded = File::download('https://example.com/file.jpg', '/uploads/file.jpg');
     * echo $downloaded ? 'Downloaded' : 'Download failed';
     * ```
     */
    public static function download(string $url, string $destPath): bool
    {
        // Get file content via file_get_contents
        $content = @file_get_contents($url);

        // Return false if failed
        if ($content === false) {
            return false;
        }

        // Ensure destination directory exists
        self::ensureDir(dirname($destPath));

        // Save content to file
        return file_put_contents($destPath, $content) !== false;
    }

    /**
     * Create a temporary file with optional content.
     *
     * @param string $prefix  Prefix for temp file name.
     * @param string $content Optional initial content.
     *
     * @return string|false Full path of temp file or false on failure.
     *
     * @example
     * ```php
     * $tmp = File::temp('myplugin_', 'Hello');
     * echo $tmp;
     * ```
     */
    public static function temp(string $prefix = 'tmp_', string $content = ''): string|false
    {
        // Generate temp file path
        $tempFile = wp_tempnam($prefix);

        // Return false if creation failed
        if ($tempFile === false) {
            return false;
        }

        // Write optional content
        if ($content !== '') {
            file_put_contents($tempFile, $content);
        }

        return $tempFile;
    }

    /**
     * Get the MIME type of a file.
     *
     * @param string $filePath Absolute path.
     *
     * @return string|false MIME type or false if not found.
     *
     * @example
     * ```php
     * $mime = File::getMime('/uploads/myplugin/file.jpg');
     * echo $mime; // image/jpeg
     * ```
     */
    public static function getMime(string $filePath): string|false
    {
        // Ensure file exists
        if (!self::isFileExists($filePath)) {
            return false;
        }

        // Use WordPress function to get MIME type
        return wp_check_filetype($filePath)['type'] ?? false;
    }

    /**
     * Check if the file is an image.
     *
     * @param string $filePath Absolute path.
     *
     * @return bool True if image, false otherwise.
     *
     * @example
     * ```php
     * $isImage = File::isImage('/uploads/myplugin/file.jpg');
     * echo $isImage ? 'Image' : 'Not image';
     * ```
     */
    public static function isImage(string $filePath): bool
    {
        // Get MIME type
        $mime = self::getMime($filePath);

        // Return true if MIME starts with 'image/'
        return $mime !== false && str_starts_with($mime, 'image/');
    }

    /**
     * Generate a unique file name in a directory to avoid overwriting.
     *
     * @param string $dir       Directory path.
     * @param string $fileName  Desired file name.
     *
     * @return string Unique file name.
     *
     * @example
     * ```php
     * $unique = File::uniqueName('/uploads/myplugin', 'file.jpg');
     * echo $unique; // file-1.jpg
     * ```
     */
    public static function uniqueName(string $dir, string $fileName): string
    {
        // Ensure directory exists
        self::ensureDir($dir);

        $name = pathinfo($fileName, PATHINFO_FILENAME);
        $ext  = pathinfo($fileName, PATHINFO_EXTENSION);
        $counter = 0;
        $uniqueName = $fileName;

        // Loop until we find a non-existing file name
        while (file_exists($dir . '/' . $uniqueName)) {
            $counter++;
            $uniqueName = $name . '-' . $counter . '.' . $ext;
        }

        return $uniqueName;
    }

    /**
     * Get all files in a directory optionally filtered by extension.
     *
     * @param string $dir       Directory path.
     * @param array  $extensions Optional array of allowed extensions (without dot).
     *
     * @return array List of file paths.
     *
     * @example
     * ```php
     * $files = File::getFiles('/uploads/myplugin', ['jpg','png']);
     * print_r($files);
     * ```
     */
    public static function getFiles(string $dir, array $extensions = []): array
    {
        // Ensure directory exists
        if (!is_dir($dir)) {
            return [];
        }

        $files = [];
        foreach (scandir($dir) as $file) {
            $path = $dir . '/' . $file;

            // Skip directories
            if (!is_file($path)) {
                continue;
            }

            // If extensions are specified, filter by them
            if (!empty($extensions) && !in_array(pathinfo($file, PATHINFO_EXTENSION), $extensions, true)) {
                continue;
            }

            $files[] = $path;
        }

        return $files;
    }

    /**
     * Delete all files in a directory optionally filtered by extension.
     *
     * @param string $dir        Directory path.
     * @param array  $extensions Optional array of allowed extensions (without dot).
     *
     * @return int Number of files deleted.
     *
     * @example
     * ```php
     * $count = File::deleteFiles('/uploads/myplugin/temp', ['txt']);
     * echo "Deleted $count files";
     * ```
     */
    public static function deleteFiles(string $dir, array $extensions = []): int
    {
        $count = 0;
        foreach (self::getFiles($dir, $extensions) as $file) {
            if (self::delete($file)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Append content to a file.
     *
     * @param string $filePath Absolute path to the file.
     * @param string $content  Content to append.
     *
     * @return bool True on success, false on failure.
     *
     * @example
     * ```php
     * $appended = File::append('/uploads/myplugin/log.txt', "New line\n");
     * echo $appended ? 'Appended' : 'Failed';
     * ```
     */
    public static function append(string $filePath, string $content): bool
    {
        // Ensure destination directory exists
        self::ensureDir(dirname($filePath));

        // Use file_put_contents with FILE_APPEND flag
        return file_put_contents($filePath, $content, FILE_APPEND) !== false;
    }

    /**
     * Read a JSON file and decode it.
     *
     * @param string $filePath Absolute path to JSON file.
     *
     * @return mixed Decoded JSON data or null if file missing/invalid.
     *
     * @example
     * ```php
     * $data = File::readJson('/uploads/myplugin/data.json');
     * print_r($data);
     * ```
     */
    public static function readJson(string $filePath): mixed
    {
        // Get file contents
        $content = self::read($filePath);
        if ($content === false) {
            return null;
        }

        // Decode JSON safely
        return json_decode($content, true);
    }

    /**
     * Write data to a JSON file.
     *
     * @param string $filePath Absolute path to JSON file.
     * @param mixed  $data     Data to encode and write.
     *
     * @return bool True on success, false on failure.
     *
     * @example
     * ```php
     * $written = File::writeJson('/uploads/myplugin/data.json', ['foo'=>'bar']);
     * echo $written ? 'Saved' : 'Failed';
     * ```
     */
    public static function writeJson(string $filePath, mixed $data): bool
    {
        // Encode data to pretty JSON
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Write JSON to file
        return self::write($filePath, $json);
    }

    /**
     * Convert an absolute file path to a public URL.
     *
     * @param string $filePath Absolute path to file.
     *
     * @return string Public URL or empty string if not in uploads.
     *
     * @example
     * ```php
     * $url = File::pathToUrl(WP_CONTENT_DIR . '/uploads/myplugin/file.jpg');
     * echo $url;
     * ```
     */
    public static function pathToUrl(string $filePath): string
    {
        $uploads = wp_get_upload_dir();
        $baseDir = trailingslashit($uploads['basedir']);
        $baseUrl = trailingslashit($uploads['baseurl']);

        // Return URL only if file is inside uploads folder
        if (str_starts_with($filePath, $baseDir)) {
            return $baseUrl . ltrim(str_replace($baseDir, '', $filePath), '/');
        }

        return '';
    }

    /**
     * Check if a file or directory is writable.
     *
     * @param string $path Absolute path.
     *
     * @return bool True if writable, false otherwise.
     *
     * @example
     * ```php
     * $writable = File::isWritable(WP_CONTENT_DIR . '/uploads/myplugin');
     * echo $writable ? 'Writable' : 'Not writable';
     * ```
     */
    public static function isWritable(string $path): bool
    {
        // Use PHP is_writable function
        return is_writable($path);
    }

    /**
     * Recursively delete a directory and all its contents.
     *
     * @param string $dir Directory path.
     *
     * @return bool True on success, false on failure.
     *
     * @example
     * ```php
     * $deleted = File::deleteDir('/uploads/myplugin/temp');
     * echo $deleted ? 'Directory deleted' : 'Failed';
     * ```
     */
    public static function deleteDir(string $dir): bool
    {
        // Check if directory exists
        if (!is_dir($dir)) {
            return false;
        }

        // Loop through all items in directory
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            // Recursively delete directories or unlink files
            if (is_dir($path)) {
                self::deleteDir($path);
            } else {
                unlink($path);
            }
        }

        // Remove the directory itself
        return rmdir($dir);
    }

    /**
     * Recursively copy a directory and all its contents.
     *
     * @param string $sourceDir Source directory.
     * @param string $destDir   Destination directory.
     *
     * @return bool True on success, false on failure.
     *
     * @example
     * ```php
     * $copied = File::copyDir('/uploads/myplugin/source', '/uploads/myplugin/backup');
     * echo $copied ? 'Directory copied' : 'Copy failed';
     * ```
     */
    public static function copyDir(string $sourceDir, string $destDir): bool
    {
        // Ensure source exists
        if (!is_dir($sourceDir)) {
            return false;
        }

        // Ensure destination directory exists
        self::ensureDir($destDir);

        // Loop through all items in source directory
        $items = scandir($sourceDir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $srcPath = $sourceDir . '/' . $item;
            $dstPath = $destDir . '/' . $item;
            if (is_dir($srcPath)) {
                self::copyDir($srcPath, $dstPath);
            } else {
                copy($srcPath, $dstPath);
            }
        }

        return true;
    }

    /**
     * Check if a path is a directory.
     *
     * @param string $path Absolute path.
     *
     * @return bool True if directory, false otherwise.
     *
     * @example
     * ```php
     * $isDir = File::isDir('/uploads/myplugin');
     * echo $isDir ? 'Directory' : 'Not a directory';
     * ```
     */
    public static function isDir(string $path): bool
    {
        return is_dir($path);
    }

    /**
     * Convert file size in bytes to human-readable format.
     *
     * @param int $bytes File size in bytes.
     * @param int $precision Number of decimal places.
     *
     * @return string Human-readable file size.
     *
     * @example
     * ```php
     * echo File::humanSize(1048576); // 1 MB
     * ```
     */
    public static function humanSize(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Sanitize a file name to make it safe for storage.
     *
     * @param string $fileName Original file name.
     *
     * @return string Sanitized file name.
     *
     * @example
     * ```php
     * $safe = File::sanitizeFileName('my unsafe file!.jpg');
     * echo $safe; // my-unsafe-file.jpg
     * ```
     */
    public static function sanitizeFileName(string $fileName): string
    {
        // Remove unwanted characters and replace spaces with dashes
        $fileName = preg_replace('/[^A-Za-z0-9_\-\.]/', '-', $fileName);
        $fileName = preg_replace('/-+/', '-', $fileName);
        return trim($fileName, '-');
    }

    /**
     * Create a unique temporary file in the WordPress temp directory.
     *
     * @param string $prefix Optional prefix for temp file.
     * @param string $content Optional initial content for the file.
     *
     * @return string|false Full path to temp file or false on failure.
     *
     * @example
     * ```php
     * $tmp = File::tempUnique('myplugin_', 'Hello');
     * echo $tmp;
     * ```
     */
    public static function tempUnique(string $prefix = 'tmp_', string $content = ''): string|false
    {
        // Generate temp file path using WordPress core function
        $tempFile = wp_tempnam($prefix);
        if ($tempFile === false) {
            return false;
        }

        // Write optional content to temp file
        if ($content !== '') {
            file_put_contents($tempFile, $content);
        }

        return $tempFile;
    }

    /**
     * Get the total size of a directory recursively.
     *
     * @param string $dir Directory path.
     *
     * @return int Total size in bytes.
     *
     * @example
     * ```php
     * $size = File::dirSize('/uploads/myplugin');
     * echo $size . ' bytes';
     * ```
     */
    public static function dirSize(string $dir): int
    {
        $size = 0;

        // Return 0 if not a directory
        if (!is_dir($dir)) {
            return $size;
        }

        // Loop through all items in directory
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $size += self::dirSize($path); // Recursive call for directories
            } else {
                $size += filesize($path); // Add file size
            }
        }

        return $size;
    }

    /**
     * Check if a file has an allowed type.
     *
     * @param string $filePath Absolute path to the file.
     * @param array  $allowedTypes Array of allowed MIME types.
     *
     * @return bool True if allowed, false otherwise.
     *
     * @example
     * ```php
     * $allowed = File::isAllowedType('/uploads/myplugin/file.jpg', ['image/jpeg','image/png']);
     * echo $allowed ? 'Allowed' : 'Not allowed';
     * ```
     */
    public static function isAllowedType(string $filePath, array $allowedTypes): bool
    {
        $mime = self::getMime($filePath);
        return $mime !== false && in_array($mime, $allowedTypes, true);
    }

    /**
     * Safely download a file using WordPress HTTP API.
     *
     * @param string $url      File URL.
     * @param string $destPath Absolute path to save file.
     *
     * @return bool True on success, false on failure.
     *
     * @example
     * ```php
     * $downloaded = File::safeDownload('https://example.com/file.jpg', '/uploads/file.jpg');
     * echo $downloaded ? 'Downloaded' : 'Failed';
     * ```
     */
    public static function safeDownload(string $url, string $destPath): bool
    {
        // Use WordPress HTTP API to fetch file
        $response = wp_remote_get($url, ['timeout' => 15, 'stream' => true, 'filename' => $destPath]);

        if (is_wp_error($response)) {
            return false;
        }

        return file_exists($destPath);
    }

    /**
     * Get the last modification time of a file.
     *
     * @param string $filePath Absolute path.
     *
     * @return int|false Timestamp of last modification or false if file missing.
     *
     * @example
     * ```php
     * $mtime = File::lastModified('/uploads/myplugin/file.jpg');
     * echo date('Y-m-d H:i:s', $mtime);
     * ```
     */
    public static function lastModified(string $filePath): int|false
    {
        return self::isFileExists($filePath) ? filemtime($filePath) : false;
    }

    /**
     * Create a ZIP archive from an array of files.
     *
     * @param array  $files   Array of absolute file paths to include.
     * @param string $zipPath Destination ZIP file path.
     *
     * @return bool True on success, false on failure.
     *
     * @example
     * ```php
     * $success = File::zip(['/uploads/file1.jpg','/uploads/file2.jpg'], '/uploads/archive.zip');
     * echo $success ? 'ZIP created' : 'Failed';
     * ```
     */
    public static function zip(array $files, string $zipPath): bool
    {
        if (!class_exists('ZipArchive')) {
            return false;
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        foreach ($files as $file) {
            if (self::isFileExists($file)) {
                $zip->addFile($file, basename($file));
            }
        }

        $zip->close();
        return file_exists($zipPath);
    }

    /**
     * Extract a ZIP archive to a specified directory.
     *
     * @param string $zipPath Path to the ZIP file.
     * @param string $destDir Destination directory.
     *
     * @return bool True on success, false on failure.
     *
     * @example
     * ```php
     * $extracted = File::unzip('/uploads/archive.zip', '/uploads/extracted');
     * echo $extracted ? 'Extracted' : 'Failed';
     * ```
     */
    public static function unzip(string $zipPath, string $destDir): bool
    {
        if (!class_exists('ZipArchive') || !self::isFileExists($zipPath)) {
            return false;
        }

        self::ensureDir($destDir);

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return false;
        }

        $zip->extractTo($destDir);
        $zip->close();

        return true;
    }

    /**
     * Get file name without its extension.
     *
     * @param string $filePath Absolute path or file name.
     *
     * @return string File name without extension.
     *
     * @example
     * ```php
     * $name = File::nameWithoutExtension('/uploads/myplugin/file.jpg');
     * echo $name; // file
     * ```
     */
    public static function nameWithoutExtension(string $filePath): string
    {
        return pathinfo($filePath, PATHINFO_FILENAME);
    }

    /**
     * Get detailed information about a file (size, MIME, extension).
     *
     * @param string $filePath Absolute path.
     *
     * @return array|false Array with keys: size, mime, extension, name. False if file missing.
     *
     * @example
     * ```php
     * $info = File::fileInfo('/uploads/myplugin/file.jpg');
     * print_r($info);
     * ```
     */
    public static function fileInfo(string $filePath): array|false
    {
        if (!self::isFileExists($filePath)) {
            return false;
        }

        return [
            'name'      => self::getFileName($filePath),
            'extension' => self::getFileExtension($filePath),
            'mime'      => self::getMime($filePath),
            'size'      => self::getFileSize($filePath),
        ];
    }

    /**
     * Create an empty file at the specified path.
     *
     * @param string $filePath Absolute path.
     *
     * @return bool True if created successfully, false otherwise.
     *
     * @example
     * ```php
     * $created = File::createEmpty('/uploads/myplugin/empty.txt');
     * echo $created ? 'File created' : 'Failed';
     * ```
     */
    public static function createEmpty(string $filePath): bool
    {
        self::ensureDir(dirname($filePath));
        return file_put_contents($filePath, '') !== false;
    }

    /**
     * Read a CSV file and return its data as an array.
     *
     * @param string $filePath Absolute path to CSV file.
     * @param string $delimiter CSV delimiter (default comma).
     *
     * @return array|false Array of rows or false if file missing.
     *
     * @example
     * ```php
     * $data = File::readCsv('/uploads/data.csv');
     * print_r($data);
     * ```
     */
    public static function readCsv(string $filePath, string $delimiter = ','): array|false
    {
        if (!self::isFileExists($filePath)) {
            return false;
        }

        $rows = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rows[] = $data;
            }
            fclose($handle);
        }

        return $rows;
    }

    /**
     * Write an array of data to a CSV file.
     *
     * @param string $filePath Absolute path to CSV file.
     * @param array  $data     Array of rows (each row is an array).
     * @param string $delimiter CSV delimiter (default comma).
     *
     * @return bool True on success, false on failure.
     *
     * @example
     * ```php
     * $written = File::writeCsv('/uploads/data.csv', [['name','email'],['John','john@example.com']]);
     * echo $written ? 'Saved' : 'Failed';
     * ```
     */
    public static function writeCsv(string $filePath, array $data, string $delimiter = ','): bool
    {
        self::ensureDir(dirname($filePath));

        if (($handle = fopen($filePath, 'w')) === false) {
            return false;
        }

        foreach ($data as $row) {
            fputcsv($handle, $row, $delimiter);
        }

        fclose($handle);
        return true;
    }

    /**
     * Convert an array to a CSV string.
     *
     * @param array  $data Array of rows (each row is an array).
     * @param string $delimiter CSV delimiter (default comma).
     *
     * @return string CSV formatted string.
     *
     * @example
     * ```php
     * $csv = File::arrayToCsv([['name','email'],['John','john@example.com']]);
     * echo $csv;
     * ```
     */
    public static function arrayToCsv(array $data, string $delimiter = ','): string
    {
        $fh = fopen('php://temp', 'r+');
        foreach ($data as $row) {
            fputcsv($fh, $row, $delimiter);
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        return $csv;
    }

    /**
     * Get the last access time of a file.
     *
     * @param string $filePath Absolute path.
     *
     * @return int|false Timestamp of last access or false if file missing.
     *
     * @example
     * ```php
     * $atime = File::lastAccess('/uploads/myplugin/file.jpg');
     * echo date('Y-m-d H:i:s', $atime);
     * ```
     */
    public static function lastAccess(string $filePath): int|false
    {
        return self::isFileExists($filePath) ? fileatime($filePath) : false;
    }

    /**
     * Generate a unique hashed file name based on original name and time.
     *
     * @param string $fileName Original file name.
     *
     * @return string Unique hashed file name.
     *
     * @example
     * ```php
     * $hashed = File::hashedName('myfile.jpg');
     * echo $hashed; // e.g., 5f2d1c9a7b3e4.jpg
     * ```
     */
    public static function hashedName(string $fileName): string
    {
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        $hash = md5($fileName . microtime(true));
        return $hash . ($ext ? '.' . $ext : '');
    }

    /**
     * Check if a file is an image based on its MIME type.
     *
     * @param string $filePath Absolute path.
     *
     * @return bool True if file is an image, false otherwise.
     *
     * @example
     * ```php
     * $isImage = File::isImageFile('/uploads/myplugin/file.jpg');
     * echo $isImage ? 'Image' : 'Not image';
     * ```
     */
    public static function isImageFile(string $filePath): bool
    {
        $mime = self::getMime($filePath);
        return $mime !== false && str_starts_with($mime, 'image/');
    }

    /**
     * Move a file to a new location safely with a unique name if file exists.
     *
     * @param string $sourcePath Source file path.
     * @param string $destDir    Destination directory.
     *
     * @return string|false Full path of moved file or false on failure.
     *
     * @example
     * ```php
     * $moved = File::moveSafe('/uploads/file.jpg', '/uploads/myplugin');
     * echo $moved;
     * ```
     */
    public static function moveSafe(string $sourcePath, string $destDir): string|false
    {
        if (!self::isFileExists($sourcePath)) {
            return false;
        }

        self::ensureDir($destDir);

        $fileName = self::uniqueName($destDir, self::getFileName($sourcePath));
        $destPath = $destDir . '/' . $fileName;

        return rename($sourcePath, $destPath) ? $destPath : false;
    }

    /**
     * Check if a file is writable.
     *
     * @param string $filePath Absolute path.
     *
     * @return bool True if writable, false otherwise.
     *
     * @example
     * ```php
     * $writable = File::isFileWritable('/uploads/myplugin/file.txt');
     * echo $writable ? 'Writable' : 'Not writable';
     * ```
     */
    public static function isFileWritable(string $filePath): bool
    {
        return is_writable($filePath);
    }

    /**
     * Get all files in a directory recursively.
     *
     * @param string $dir Directory path.
     * @param array  $extensions Optional array of allowed file extensions.
     *
     * @return array List of file paths.
     *
     * @example
     * ```php
     * $files = File::getFilesRecursive('/uploads/myplugin', ['jpg','png']);
     * print_r($files);
     * ```
     */
    public static function getFilesRecursive(string $dir, array $extensions = []): array
    {
        $files = [];

        if (!is_dir($dir)) {
            return $files;
        }

        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;

            if (is_dir($path)) {
                $files = array_merge($files, self::getFilesRecursive($path, $extensions));
            } elseif (empty($extensions) || in_array(pathinfo($path, PATHINFO_EXTENSION), $extensions, true)) {
                $files[] = $path;
            }
        }

        return $files;
    }

    /**
     * Read the first N lines of a file.
     *
     * @param string $filePath Absolute path.
     * @param int    $lines    Number of lines to read.
     *
     * @return array|false Array of lines or false if file missing.
     *
     * @example
     * ```php
     * $firstLines = File::readFirstLines('/uploads/myplugin/file.txt', 5);
     * print_r($firstLines);
     * ```
     */
    public static function readFirstLines(string $filePath, int $lines = 10): array|false
    {
        if (!self::isFileExists($filePath)) {
            return false;
        }

        $fh = fopen($filePath, 'r');
        if (!$fh) {
            return false;
        }

        $result = [];
        $count = 0;
        while (!feof($fh) && $count < $lines) {
            $result[] = fgets($fh);
            $count++;
        }

        fclose($fh);
        return $result;
    }

    /**
     * Check if a directory or file path is empty.
     *
     * @param string $path Directory or file path.
     *
     * @return bool True if empty, false otherwise.
     *
     * @example
     * ```php
     * $empty = File::isEmptyPath('/uploads/myplugin/temp');
     * echo $empty ? 'Empty' : 'Not empty';
     * ```
     */
    public static function isEmptyPath(string $path): bool
    {
        if (!file_exists($path)) {
            return true;
        }

        if (is_dir($path)) {
            $files = array_diff(scandir($path), ['.', '..']);
            return empty($files);
        }

        return filesize($path) === 0;
    }

    /**
     * Recursively remove all files and directories inside a path.
     *
     * @param string $path Directory path.
     *
     * @return bool True on success, false on failure.
     *
     * @example
     * ```php
     * $cleaned = File::cleanDir('/uploads/myplugin/temp');
     * echo $cleaned ? 'Cleaned' : 'Failed';
     * ```
     */
    public static function cleanDir(string $path): bool
    {
        if (!is_dir($path)) {
            return false;
        }

        foreach (scandir($path) as $item) {
            if ($item === '.' || $item === '..') continue;
            $fullPath = $path . '/' . $item;
            if (is_dir($fullPath)) {
                self::cleanDir($fullPath);
                rmdir($fullPath);
            } else {
                unlink($fullPath);
            }
        }

        return true;
    }

    /**
     * Safely copy a file to a new location.
     *
     * @param string $sourcePath Absolute path of source file.
     * @param string $destPath   Absolute path of destination file.
     *
     * @return bool True on success, false on failure.
     *
     * @example
     * ```php
     * $copied = File::copyFile('/uploads/file.jpg', '/uploads/myplugin/file.jpg');
     * echo $copied ? 'Copied' : 'Failed';
     * ```
     */
    public static function copyFile(string $sourcePath, string $destPath): bool
    {
        // Check if source file exists
        if (!self::isFileExists($sourcePath)) {
            return false;
        }

        // Ensure destination directory exists
        self::ensureDir(dirname($destPath));

        // Copy the file to destination path
        return copy($sourcePath, $destPath);
    }

    /**
     * Move a file to a new location.
     *
     * @param string $sourcePath Absolute path of source file.
     * @param string $destPath   Absolute path of destination file.
     *
     * @return bool True on success, false on failure.
     *
     * @example
     * ```php
     * $moved = File::moveFile('/uploads/file.jpg', '/uploads/myplugin/file.jpg');
     * echo $moved ? 'Moved' : 'Failed';
     * ```
     */
    public static function moveFile(string $sourcePath, string $destPath): bool
    {
        // Check if source file exists
        if (!self::isFileExists($sourcePath)) {
            return false;
        }

        // Ensure destination directory exists
        self::ensureDir(dirname($destPath));

        // Rename (move) the file to destination
        return rename($sourcePath, $destPath);
    }

    /**
     * Get the last modification time of a file.
     *
     * @param string $filePath Absolute path of file.
     *
     * @return int|false Timestamp or false if file missing.
     *
     * @example
     * ```php
     * $mtime = File::modificationTime('/uploads/myplugin/file.jpg');
     * echo date('Y-m-d H:i:s', $mtime);
     * ```
     */
    public static function modificationTime(string $filePath): int|false
    {
        // Return false if file does not exist
        if (!self::isFileExists($filePath)) {
            return false;
        }

        // Return file modification timestamp
        return filemtime($filePath);
    }

    /**
     * Get the owner of a file.
     *
     * @param string $filePath Absolute path of file.
     *
     * @return int|false User ID of file owner or false if file missing.
     *
     * @example
     * ```php
     * $owner = File::fileOwner('/uploads/myplugin/file.jpg');
     * echo $owner;
     * ```
     */
    public static function fileOwner(string $filePath): int|false
    {
        // Check if file exists
        if (!self::isFileExists($filePath)) {
            return false;
        }

        // Return owner user ID of the file
        return fileowner($filePath);
    }

    /**
     * Convert a relative path to an absolute path based on WordPress root.
     *
     * @param string $relativePath Relative path.
     *
     * @return string Absolute path.
     *
     * @example
     * ```php
     * $absolute = File::relativeToAbsolute('wp-content/uploads/file.jpg');
     * echo $absolute;
     * ```
     */
    public static function relativeToAbsolute(string $relativePath): string
    {
        // Remove leading slashes
        $relativePath = ltrim($relativePath, '/\\');

        // Concatenate WordPress ABSPATH with relative path
        return ABSPATH . $relativePath;
    }

    /**
     * Resize an image file to given dimensions.
     *
     * @param string $filePath Absolute path to the image.
     * @param int    $width    Target width.
     * @param int    $height   Target height.
     * @param bool   $crop     Whether to crop image (default false).
     *
     * @return string|false Path to resized image or false on failure.
     *
     * @example
     * ```php
     * $resized = File::resizeImage('/uploads/myplugin/file.jpg', 300, 200);
     * echo $resized;
     * ```
     */
    public static function resizeImage(string $filePath, int $width, int $height, bool $crop = false): string|false
    {
        // Check if file exists
        if (!self::isFileExists($filePath)) {
            return false;
        }

        // Include WordPress image functions
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        // Get file type info
        $imageEditor = wp_get_image_editor($filePath);
        if (is_wp_error($imageEditor)) {
            return false;
        }

        // Resize the image
        $imageEditor->resize($width, $height, $crop);

        // Save the resized image
        $resizedPath = $imageEditor->save();
        return $resizedPath['path'] ?? false;
    }

    /**
     * Create a thumbnail of an image.
     *
     * @param string $filePath Absolute path to the image.
     * @param int    $size     Thumbnail size in pixels (width and height).
     *
     * @return string|false Path to thumbnail or false on failure.
     *
     * @example
     * ```php
     * $thumb = File::createThumbnail('/uploads/myplugin/file.jpg', 150);
     * echo $thumb;
     * ```
     */
    public static function createThumbnail(string $filePath, int $size = 150): string|false
    {
        return self::resizeImage($filePath, $size, $size, true);
    }

    /**
     * Create a temporary file in WordPress temp directory.
     *
     * @param string $prefix Optional file prefix.
     *
     * @return string|false Full path of temporary file or false on failure.
     *
     * @example
     * ```php
     * $tmp = File::tempFile('myplugin_');
     * echo $tmp;
     * ```
     */
    public static function tempFile(string $prefix = 'tmp_'): string|false
    {
        // Generate temp file path using WordPress function
        $tempFile = wp_tempnam($prefix);

        // Return the path or false if failed
        return $tempFile ?: false;
    }

    /**
     * Lock a file to prevent concurrent writes.
     *
     * @param string $filePath Absolute path to file.
     *
     * @return resource|false File handle on success, false on failure.
     *
     * @example
     * ```php
     * $fh = File::lockFile('/uploads/myplugin/file.txt');
     * ```
     */
    public static function lockFile(string $filePath)
    {
        // Ensure the file exists
        self::ensureDir(dirname($filePath));
        if (!file_exists($filePath)) {
            file_put_contents($filePath, '');
        }

        // Open the file for writing
        $fh = fopen($filePath, 'c+');
        if ($fh === false) {
            return false;
        }

        // Try to acquire an exclusive lock
        if (!flock($fh, LOCK_EX)) {
            fclose($fh);
            return false;
        }

        return $fh;
    }

    /**
     * Unlock a previously locked file.
     *
     * @param resource $fh File handle returned by lockFile().
     *
     * @return void
     *
     * @example
     * ```php
     * File::unlockFile($fh);
     * ```
     */
    public static function unlockFile($fh): void
    {
        // Release the lock
        flock($fh, LOCK_UN);

        // Close the file handle
        fclose($fh);
    }

    /**
     * Cache content to a file in the plugin cache directory.
     *
     * @param string $fileName Name of cache file.
     * @param string $content  Content to cache.
     *
     * @return bool True on success, false on failure.
     *
     * @example
     * ```php
     * File::cacheFile('myplugin.txt', 'Hello World');
     * ```
     */
    public static function cacheFile(string $fileName, string $content): bool
    {
        $cacheDir = WP_CONTENT_DIR . '/cache/myplugin';
        self::ensureDir($cacheDir);

        $filePath = $cacheDir . '/' . self::sanitizeFileName($fileName);
        return file_put_contents($filePath, $content) !== false;
    }

    /**
     * Retrieve content from a cached file.
     *
     * @param string $fileName Name of cache file.
     *
     * @return string|false Content of file or false if missing.
     *
     * @example
     * ```php
     * $data = File::getCacheFile('myplugin.txt');
     * echo $data;
     * ```
     */
    public static function getCacheFile(string $fileName): string|false
    {
        $filePath = WP_CONTENT_DIR . '/cache/myplugin/' . self::sanitizeFileName($fileName);
        return self::isFileExists($filePath) ? file_get_contents($filePath) : false;
    }

    /**
     * Delete a cached file.
     *
     * @param string $fileName Name of cache file.
     *
     * @return bool True if deleted, false otherwise.
     *
     * @example
     * ```php
     * $deleted = File::deleteCacheFile('myplugin.txt');
     * echo $deleted ? 'Deleted' : 'Not found';
     * ```
     */
    public static function deleteCacheFile(string $fileName): bool
    {
        $filePath = WP_CONTENT_DIR . '/cache/myplugin/' . self::sanitizeFileName($fileName);
        return self::delete($filePath);
    }

    /**
     * Get the file extension in lowercase.
     *
     * @param string $filePath Absolute path or file name.
     *
     * @return string Lowercase extension.
     *
     * @example
     * ```php
     * $ext = File::extensionLower('/uploads/file.JPG');
     * echo $ext; // jpg
     * ```
     */
    public static function extensionLower(string $filePath): string
    {
        return strtolower(self::getFileExtension($filePath));
    }

    /**
     * Get the MIME type of a file with fallback to WordPress function.
     *
     * @param string $filePath Absolute path.
     *
     * @return string|false MIME type or false if unknown.
     *
     * @example
     * ```php
     * $mime = File::mimeType('/uploads/file.jpg');
     * echo $mime;
     * ```
     */
    public static function mimeType(string $filePath): string|false
    {
        // Try standard finfo first
        $mime = self::getMime($filePath);
        if ($mime !== false) {
            return $mime;
        }

        // Fallback to WordPress function
        $mime = wp_check_filetype($filePath)['type'] ?? false;
        return $mime ?: false;
    }

    /**
     * Convert file size in bytes to human-readable format.
     *
     * @param int $bytes File size in bytes.
     *
     * @return string Human-readable file size (KB, MB, GB).
     *
     * @example
     * ```php
     * echo File::humanFileSize(2048); // 2 KB
     * ```
     */
    public static function humanFileSize(int $bytes): string
    {
        // Define units
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $bytes;
        $unit = $units[0];

        // Iterate through units to find appropriate size
        foreach ($units as $i => $u) {
            if ($bytes < pow(1024, $i + 1)) {
                $unit = $u;
                $size = $bytes / pow(1024, $i);
                break;
            }
        }

        // Format with 2 decimal places
        return sprintf('%.2f %s', $size, $unit);
    }

    /**
     * Check if a file is readable.
     *
     * @param string $filePath Absolute path.
     *
     * @return bool True if readable, false otherwise.
     *
     * @example
     * ```php
     * $readable = File::isReadable('/uploads/myplugin/file.txt');
     * echo $readable ? 'Readable' : 'Not readable';
     * ```
     */
    public static function isReadable(string $filePath): bool
    {
        // File must exist and be readable
        return self::isFileExists($filePath) && is_readable($filePath);
    }

    /**
     * Get the total size of a directory recursively.
     *
     * @param string $dir Directory path.
     *
     * @return int Total size in bytes.
     *
     * @example
     * ```php
     * $size = File::directorySize('/uploads/myplugin');
     * echo File::humanFileSize($size);
     * ```
     */
    public static function directorySize(string $dir): int
    {
        $size = 0;

        // Check if path is a directory
        if (!is_dir($dir)) {
            return $size;
        }

        // Iterate through directory contents
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;

            // If directory, recurse
            if (is_dir($path)) {
                $size += self::directorySize($path);
            } elseif (is_file($path)) {
                $size += filesize($path);
            }
        }

        return $size;
    }

    /**
     * Move multiple files safely to a destination directory.
     *
     * @param array  $files   Array of absolute source file paths.
     * @param string $destDir Destination directory path.
     *
     * @return array Array of moved files with source as key and destination as value.
     *
     * @example
     * ```php
     * $movedFiles = File::moveMultiple(['/uploads/file1.jpg','/uploads/file2.jpg'], '/uploads/myplugin');
     * print_r($movedFiles);
     * ```
     */
    public static function moveMultiple(array $files, string $destDir): array
    {
        $results = [];

        // Ensure destination directory exists
        self::ensureDir($destDir);

        foreach ($files as $file) {
            if (self::isFileExists($file)) {
                $destPath = $destDir . '/' . self::uniqueName($destDir, self::getFileName($file));
                if (rename($file, $destPath)) {
                    $results[$file] = $destPath;
                }
            }
        }

        return $results;
    }

    /**
     * Download a remote file and save it locally.
     *
     * @param string $url       Remote file URL.
     * @param string $destDir   Destination directory.
     * @param string $fileName  Optional custom file name.
     *
     * @return string|false Full path of downloaded file or false on failure.
     *
     * @example
     * ```php
     * $downloaded = File::downloadRemote('https://example.com/file.jpg', WP_CONTENT_DIR . '/uploads/myplugin');
     * echo $downloaded;
     * ```
     */
    public static function downloadRemote(string $url, string $destDir, string $fileName = ''): string|false
    {
        // Ensure destination directory exists
        self::ensureDir($destDir);

        // Determine file name from URL if not provided
        if (!$fileName) {
            $fileName = basename(parse_url($url, PHP_URL_PATH));
        }

        $fileName = self::sanitizeFileName($fileName);
        $destPath = $destDir . '/' . self::uniqueName($destDir, $fileName);

        // Fetch remote content
        $content = wp_remote_get($url);
        if (is_wp_error($content)) {
            return false;
        }

        $body = wp_remote_retrieve_body($content);
        if (!$body) {
            return false;
        }

        // Save content to local file
        return file_put_contents($destPath, $body) !== false ? $destPath : false;
    }

    /**
     * Check if a file is an image based on extension.
     *
     * @param string $filePath Absolute path or file name.
     *
     * @return bool True if image extension, false otherwise.
     *
     * @example
     * ```php
     * $isImage = File::isImageExtension('/uploads/myplugin/file.png');
     * echo $isImage ? 'Yes' : 'No';
     * ```
     */
    public static function isImageExtension(string $filePath): bool
    {
        // Allowed image extensions
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff'];
        $ext = self::extensionLower($filePath);

        // Check if file extension is in allowed list
        return in_array($ext, $allowed, true);
    }

    /**
     * Read the first N bytes of a file.
     *
     * @param string $filePath Absolute path.
     * @param int    $bytes    Number of bytes to read.
     *
     * @return string|false File content or false if missing.
     *
     * @example
     * ```php
     * $head = File::readBytes('/uploads/myplugin/file.txt', 50);
     * echo $head;
     * ```
     */
    public static function readBytes(string $filePath, int $bytes = 1024): string|false
    {
        // Check file exists
        if (!self::isFileExists($filePath)) {
            return false;
        }

        // Open file for reading
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return false;
        }

        // Read specified bytes
        $content = fread($handle, $bytes);
        fclose($handle);

        return $content;
    }

    /**
     * Get the last N lines of a file.
     *
     * @param string $filePath Absolute path.
     * @param int    $lines    Number of lines to retrieve.
     *
     * @return array|false Array of lines or false if file missing.
     *
     * @example
     * ```php
     * $lastLines = File::readLastLines('/uploads/myplugin/log.txt', 5);
     * print_r($lastLines);
     * ```
     */
    public static function readLastLines(string $filePath, int $lines = 10): array|false
    {
        // Check file exists
        if (!self::isFileExists($filePath)) {
            return false;
        }

        $content = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($content === false) {
            return false;
        }

        // Return last N lines
        return array_slice($content, -$lines);
    }

    /**
     * Import a local file into WordPress media library.
     *
     * @param string $filePath Absolute path to the file.
     * @param int    $postId   Optional parent post ID.
     *
     * @return int|WP_Error Attachment ID or WP_Error on failure.
     *
     * @example
     * ```php
     * $attachId = File::importToMedia('/uploads/myplugin/file.jpg');
     * echo $attachId;
     * ```
     */
    public static function importToMedia(string $filePath, int $postId = 0): int|\WP_Error
    {
        // Check if file exists
        if (!self::isFileExists($filePath)) {
            return new \WP_Error('file_missing', 'File not found');
        }

        // Include WordPress media functions
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // Use media_handle_sideload to import
        $fileArray = [
            'name'     => self::getFileName($filePath),
            'tmp_name' => $filePath,
        ];

        return media_handle_sideload($fileArray, $postId);
    }

    /**
     * Clean up all temporary files in WordPress temp directory for plugin.
     *
     * @param string $prefix Optional prefix to identify plugin temp files.
     *
     * @return int Number of files deleted.
     *
     * @example
     * ```php
     * $deletedCount = File::cleanTempFiles('myplugin_');
     * echo $deletedCount;
     * ```
     */
    public static function cleanTempFiles(string $prefix = 'tmp_'): int
    {
        $deleted = 0;
        $tmpDir = wp_temp_dir();

        foreach (scandir($tmpDir) as $file) {
            if (str_starts_with($file, $prefix)) {
                $filePath = $tmpDir . '/' . $file;
                if (is_file($filePath) && unlink($filePath)) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    /**
     * Safely rename a file, optionally preventing overwrites.
     *
     * @param string $filePath Current file path.
     * @param string $newName  New file name.
     * @param bool   $unique   Ensure uniqueness if true.
     *
     * @return string|false New file path or false on failure.
     *
     * @example
     * ```php
     * $newPath = File::renameFile('/uploads/file.jpg', 'newfile.jpg', true);
     * echo $newPath;
     * ```
     */
    public static function renameFile(string $filePath, string $newName, bool $unique = true): string|false
    {
        // Check if file exists
        if (!self::isFileExists($filePath)) {
            return false;
        }

        $dir = dirname($filePath);
        if ($unique) {
            $newName = self::uniqueName($dir, $newName);
        }

        $newPath = $dir . '/' . $newName;

        return rename($filePath, $newPath) ? $newPath : false;
    }

    /**
     * Get the last access time of a file.
     *
     * @param string $filePath Absolute path.
     *
     * @return int|false Timestamp or false if file missing.
     *
     * @example
     * ```php
     * $atime = File::lastAccessTime('/uploads/myplugin/file.txt');
     * echo date('Y-m-d H:i:s', $atime);
     * ```
     */
    public static function lastAccessTime(string $filePath): int|false
    {
        return self::isFileExists($filePath) ? fileatime($filePath) : false;
    }

    /**
     * Get file permissions in octal format.
     *
     * @param string $filePath Absolute path.
     *
     * @return string|false Permissions string like '0755' or false if file missing.
     *
     * @example
     * ```php
     * $perm = File::filePermissions('/uploads/myplugin/file.txt');
     * echo $perm;
     * ```
     */
    public static function filePermissions(string $filePath): string|false
    {
        if (!self::isFileExists($filePath)) {
            return false;
        }

        return sprintf('%04o', fileperms($filePath) & 0777);
    }

    /**
     * Check if a file is older than specified seconds.
     *
     * @param string $filePath Absolute path.
     * @param int    $seconds  Age in seconds.
     *
     * @return bool True if file is older, false otherwise.
     *
     * @example
     * ```php
     * $old = File::isOlderThan('/uploads/myplugin/file.txt', 3600);
     * echo $old ? 'Old' : 'Recent';
     * ```
     */
    public static function isOlderThan(string $filePath, int $seconds): bool
    {
        if (!self::isFileExists($filePath)) {
            return false;
        }

        $mtime = filemtime($filePath);
        return (time() - $mtime) > $seconds;
    }

    /**
     * Touch a file to update modification time or create if missing.
     *
     * @param string $filePath Absolute path.
     *
     * @return bool True on success, false on failure.
     *
     * @example
     * ```php
     * File::touchFile('/uploads/myplugin/file.txt');
     * ```
     */
    public static function touchFile(string $filePath): bool
    {
        self::ensureDir(dirname($filePath));

        return touch($filePath);
    }

    /**
     * Append content to a file.
     *
     * @param string $filePath Absolute path.
     * @param string $content  Content to append.
     *
     * @return bool True on success, false on failure.
     *
     * @example
     * ```php
     * File::appendFile('/uploads/myplugin/log.txt', "New log line\n");
     * ```
     */
    public static function appendFile(string $filePath, string $content): bool
    {
        self::ensureDir(dirname($filePath));

        return file_put_contents($filePath, $content, FILE_APPEND | LOCK_EX) !== false;
    }

    /**
     * Check if a file is writable and readable.
     *
     * @param string $filePath Absolute path.
     *
     * @return bool True if file is readable and writable.
     *
     * @example
     * ```php
     * $ok = File::isReadWrite('/uploads/myplugin/file.txt');
     * echo $ok ? 'OK' : 'Not OK';
     * ```
     */
    public static function isReadWrite(string $filePath): bool
    {
        return self::isReadable($filePath) && is_writable($filePath);
    }

    /**
     * Duplicate a file with a new unique name.
     *
     * @param string $filePath Absolute path of original file.
     *
     * @return string|false New file path or false on failure.
     *
     * @example
     * ```php
     * $dup = File::duplicate('/uploads/myplugin/file.jpg');
     * echo $dup;
     * ```
     */
    public static function duplicate(string $filePath): string|false
    {
        if (!self::isFileExists($filePath)) {
            return false;
        }

        $dir = dirname($filePath);
        $newName = self::uniqueName($dir, self::getFileName($filePath));
        $newPath = $dir . '/' . $newName;

        return copy($filePath, $newPath) ? $newPath : false;
    }

    /**
     * Get the parent directory of a file or directory recursively.
     *
     * @param string $path Absolute path.
     * @param int    $levels Number of levels up.
     *
     * @return string Parent directory path.
     *
     * @example
     * ```php
     * $parent = File::parentDir('/uploads/myplugin/temp/file.txt', 2);
     * echo $parent;
     * ```
     */
    public static function parentDir(string $path, int $levels = 1): string
    {
        $parent = $path;

        // Traverse up the specified number of levels
        for ($i = 0; $i < $levels; $i++) {
            $parent = dirname($parent);
        }

        return $parent;
    }

    /**
     * Create a temporary unique directory in WordPress temp folder.
     *
     * @param string $prefix Optional directory prefix.
     *
     * @return string|false Full path of temporary directory or false on failure.
     *
     * @example
     * ```php
     * $tmpDir = File::tempDir('myplugin_');
     * echo $tmpDir;
     * ```
     */
    public static function tempDir(string $prefix = 'tmp_'): string|false
    {
        $tmpBase = wp_temp_dir();

        // Generate unique directory name
        $dirPath = $tmpBase . '/' . uniqid($prefix, true);

        // Create directory
        return self::ensureDir($dirPath);
    }

    /**
     * Clean all temporary directories created by the plugin.
     *
     * @param string $prefix Prefix used for temp directories.
     *
     * @return int Number of directories deleted.
     *
     * @example
     * ```php
     * $deleted = File::cleanTempDirs('myplugin_');
     * echo $deleted;
     * ```
     */
    public static function cleanTempDirs(string $prefix = 'tmp_'): int
    {
        $count = 0;
        $tmpBase = wp_temp_dir();

        foreach (scandir($tmpBase) as $item) {
            if (str_starts_with($item, $prefix)) {
                $dirPath = $tmpBase . '/' . $item;
                if (is_dir($dirPath) && self::deleteDir($dirPath)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Cache a remote file locally with optional expiration in seconds.
     *
     * @param string $url       Remote file URL.
     * @param string $cacheDir  Directory to store cached file.
     * @param int    $expires   Expiration time in seconds (default 3600).
     *
     * @return string|false Path to cached file or false on failure.
     *
     * @example
     * ```php
     * $cached = File::cacheRemote('https://example.com/file.jpg', WP_CONTENT_DIR . '/cache/myplugin');
     * echo $cached;
     * ```
     */
    public static function cacheRemote(string $url, string $cacheDir, int $expires = 3600): string|false
    {
        self::ensureDir($cacheDir);

        $fileName = self::sanitizeFileName(basename(parse_url($url, PHP_URL_PATH)));
        $filePath = $cacheDir . '/' . $fileName;

        // If cached file exists and is not expired, return it
        if (self::isFileExists($filePath) && (time() - filemtime($filePath)) < $expires) {
            return $filePath;
        }

        // Download and save the remote file
        return self::downloadRemote($url, $cacheDir, $fileName);
    }

    /**
     * Filter files in a directory by extension.
     *
     * @param string $dir       Directory path.
     * @param array  $extensions Array of allowed extensions.
     * @param bool   $recursive Whether to scan recursively.
     *
     * @return array List of file paths.
     *
     * @example
     * ```php
     * $images = File::filterFiles('/uploads/myplugin', ['jpg','png'], true);
     * print_r($images);
     * ```
     */
    public static function filterFiles(string $dir, array $extensions = [], bool $recursive = false): array
    {
        $results = [];

        if (!is_dir($dir)) {
            return $results;
        }

        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;

            if (is_dir($path) && $recursive) {
                $results = array_merge($results, self::filterFiles($path, $extensions, true));
            } elseif (is_file($path)) {
                if (empty($extensions) || in_array(self::extensionLower($path), $extensions, true)) {
                    $results[] = $path;
                }
            }
        }

        return $results;
    }

    /**
     * Copy a directory recursively with optional overwrite.
     *
     * @param string $src       Source directory.
     * @param string $dest      Destination directory.
     * @param bool   $overwrite Whether to overwrite existing files.
     *
     * @return bool True on success, false on failure.
     *
     * @example
     * ```php
     * $copied = File::copyDirRecursive('/uploads/myplugin', '/uploads/myplugin_backup', true);
     * ```
     */
    public static function copyDirRecursive(string $src, string $dest, bool $overwrite = false): bool
    {
        if (!is_dir($src)) {
            return false;
        }

        self::ensureDir($dest);

        foreach (scandir($src) as $item) {
            if ($item === '.' || $item === '..') continue;
            $srcPath = $src . '/' . $item;
            $destPath = $dest . '/' . $item;

            if (is_dir($srcPath)) {
                self::copyDirRecursive($srcPath, $destPath, $overwrite);
            } else {
                if (!file_exists($destPath) || $overwrite) {
                    copy($srcPath, $destPath);
                }
            }
        }

        return true;
    }

    /**
     * Move a directory recursively with optional overwrite.
     *
     * @param string $src       Source directory.
     * @param string $dest      Destination directory.
     * @param bool   $overwrite Whether to overwrite existing files.
     *
     * @return bool True on success, false on failure.
     *
     * @example
     * ```php
     * $moved = File::moveDirRecursive('/uploads/myplugin', '/uploads/myplugin_backup', true);
     * ```
     */
    public static function moveDirRecursive(string $src, string $dest, bool $overwrite = false): bool
    {
        if (!self::copyDirRecursive($src, $dest, $overwrite)) {
            return false;
        }

        return self::deleteDir($src);
    }

    /**
     * Generate MD5 hash of a file.
     *
     * @param string $filePath Absolute path.
     *
     * @return string|false MD5 hash or false if file missing.
     *
     * @example
     * ```php
     * $hash = File::fileMd5('/uploads/myplugin/file.txt');
     * echo $hash;
     * ```
     */
    public static function fileMd5(string $filePath): string|false
    {
        return self::isFileExists($filePath) ? md5_file($filePath) : false;
    }

    /**
     * Generate SHA1 hash of a file.
     *
     * @param string $filePath Absolute path.
     *
     * @return string|false SHA1 hash or false if file missing.
     *
     * @example
     * ```php
     * $hash = File::fileSha1('/uploads/myplugin/file.txt');
     * echo $hash;
     * ```
     */
    public static function fileSha1(string $filePath): string|false
    {
        return self::isFileExists($filePath) ? sha1_file($filePath) : false;
    }

    /**
     * Check if a file is empty.
     *
     * @param string $filePath Absolute path.
     *
     * @return bool True if empty or missing, false if contains data.
     *
     * @example
     * ```php
     * $empty = File::isEmptyFile('/uploads/myplugin/file.txt');
     * echo $empty ? 'Empty' : 'Not empty';
     * ```
     */
    public static function isEmptyFile(string $filePath): bool
    {
        return !self::isFileExists($filePath) || filesize($filePath) === 0;
    }

    /**
     * Create a file with given content, overwriting if exists.
     *
     * @param string $filePath Absolute path.
     * @param string $content  Content to write.
     *
     * @return bool True on success, false on failure.
     *
     * @example
     * ```php
     * File::createFile('/uploads/myplugin/newfile.txt', 'Hello World');
     * ```
     */
    public static function createFile(string $filePath, string $content = ''): bool
    {
        self::ensureDir(dirname($filePath));
        return file_put_contents($filePath, $content, LOCK_EX) !== false;
    }

    /**
     * Compare two files by MD5 hash.
     *
     * @param string $file1 Absolute path of first file.
     * @param string $file2 Absolute path of second file.
     *
     * @return bool True if files are identical, false otherwise.
     *
     * @example
     * ```php
     * $same = File::compareFiles('/uploads/file1.txt','/uploads/file2.txt');
     * echo $same ? 'Identical' : 'Different';
     * ```
     */
    public static function compareFiles(string $file1, string $file2): bool
    {
        if (!self::isFileExists($file1) || !self::isFileExists($file2)) {
            return false;
        }

        return self::fileMd5($file1) === self::fileMd5($file2);
    }

    /**
     * Get the file name without extension.
     *
     * @param string $filePath Absolute path or file name.
     *
     * @return string File name without extension.
     *
     * @example
     * ```php
     * $name = File::getFileNameNoExt('/uploads/myplugin/file.txt');
     * echo $name; // file
     * ```
     */
    public static function getFileNameNoExt(string $filePath): string
    {
        // Use pathinfo to get the filename without extension
        return pathinfo($filePath, PATHINFO_FILENAME);
    }

    /**
     * Read the entire content of a file safely.
     *
     * @param string $filePath Absolute path.
     *
     * @return string|false File content or false if unreadable.
     *
     * @example
     * ```php
     * $content = File::readFile('/uploads/myplugin/file.txt');
     * echo $content;
     * ```
     */
    public static function readFile(string $filePath): string|false
    {
        // Check if file exists
        if (!self::isFileExists($filePath)) {
            return false;
        }

        // Return file content
        return file_get_contents($filePath);
    }

    /**
     * Check if a directory is empty.
     *
     * @param string $dir Directory path.
     *
     * @return bool True if empty or does not exist, false otherwise.
     *
     * @example
     * ```php
     * $empty = File::isDirEmpty('/uploads/myplugin/temp');
     * echo $empty ? 'Empty' : 'Not empty';
     * ```
     */
    public static function isDirEmpty(string $dir): bool
    {
        // If directory does not exist, treat as empty
        if (!is_dir($dir)) {
            return true;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        return empty($files);
    }

    /**
     * Get the MIME type of a file.
     *
     * @param string $filePath Absolute path.
     *
     * @return string|false MIME type or false if file missing.
     *
     * @example
     * ```php
     * $mime = File::getMimeType('/uploads/myplugin/file.jpg');
     * echo $mime;
     * ```
     */
    public static function getMimeType(string $filePath): string|false
    {
        if (!self::isFileExists($filePath)) {
            return false;
        }

        // Use PHP's finfo for MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        return $mime;
    }

    /**
     * Create a backup copy of a file in the same directory.
     *
     * @param string $filePath Absolute path of the original file.
     *
     * @return string|false Path of backup file or false on failure.
     *
     * @example
     * ```php
     * $backup = File::backupFile('/uploads/myplugin/file.txt');
     * echo $backup;
     * ```
     */
    public static function backupFile(string $filePath): string|false
    {
        if (!self::isFileExists($filePath)) {
            return false;
        }

        $dir = dirname($filePath);
        $name = self::getFileNameNoExt($filePath);
        $ext = self::extensionLower($filePath);
        $backupName = $name . '-backup-' . time() . ($ext ? '.' . $ext : '');
        $backupPath = $dir . '/' . $backupName;

        return copy($filePath, $backupPath) ? $backupPath : false;
    }

    /**
     * Recursively list all files in a directory.
     *
     * @param string $dir Directory path.
     *
     * @return array List of file paths.
     *
     * @example
     * ```php
     * $files = File::listFilesRecursive('/uploads/myplugin');
     * print_r($files);
     * ```
     */
    public static function listFilesRecursive(string $dir): array
    {
        $results = [];

        if (!is_dir($dir)) {
            return $results;
        }

        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;

            if (is_dir($path)) {
                $results = array_merge($results, self::listFilesRecursive($path));
            } elseif (is_file($path)) {
                $results[] = $path;
            }
        }

        return $results;
    }

    /**
     * Read a file line by line into an array.
     *
     * @param string $filePath Absolute path.
     *
     * @return array|false Array of lines or false if file missing/unreadable.
     *
     * @example
     * ```php
     * $lines = File::readFileLines('/uploads/myplugin/file.txt');
     * print_r($lines);
     * ```
     */
    public static function readFileLines(string $filePath): array|false
    {
        // Check if file exists
        if (!self::isFileExists($filePath)) {
            return false;
        }

        $lines = [];
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return false;
        }

        // Read each line and trim newline characters
        while (($line = fgets($handle)) !== false) {
            $lines[] = rtrim($line, "\r\n");
        }

        fclose($handle);
        return $lines;
    }

    /**
     * Write multiple lines to a file, overwriting or creating new.
     *
     * @param string $filePath Absolute path.
     * @param array  $lines    Array of strings to write.
     *
     * @return bool True on success, false on failure.
     *
     * @example
     * ```php
     * File::writeFileLines('/uploads/myplugin/file.txt', ['Line 1','Line 2']);
     * ```
     */
    public static function writeFileLines(string $filePath, array $lines): bool
    {
        self::ensureDir(dirname($filePath));

        $content = implode(PHP_EOL, $lines) . PHP_EOL;
        return file_put_contents($filePath, $content, LOCK_EX) !== false;
    }

    /**
     * Check if a path is a symbolic link.
     *
     * @param string $path Absolute path.
     *
     * @return bool True if symbolic link, false otherwise.
     *
     * @example
     * ```php
     * $symlink = File::isSymlink('/uploads/myplugin/link.txt');
     * echo $symlink ? 'Yes' : 'No';
     * ```
     */
    public static function isSymlink(string $path): bool
    {
        return file_exists($path) && is_link($path);
    }

    /**
     * Safely delete a file if it exists.
     *
     * @param string $filePath Absolute path.
     *
     * @return bool True if deleted or not existing, false on failure.
     *
     * @example
     * ```php
     * $deleted = File::safeDelete('/uploads/myplugin/file.txt');
     * echo $deleted ? 'Deleted' : 'Failed';
     * ```
     */
    public static function safeDelete(string $filePath): bool
    {
        // If file doesn't exist, consider success
        if (!self::isFileExists($filePath)) {
            return true;
        }

        return unlink($filePath);
    }

    /**
     * Safely truncate a file to zero length.
     *
     * @param string $filePath Absolute path.
     *
     * @return bool True on success, false on failure.
     *
     * @example
     * ```php
     * $truncated = File::truncateFile('/uploads/myplugin/file.txt');
     * echo $truncated ? 'Truncated' : 'Failed';
     * ```
     */
    public static function truncateFile(string $filePath): bool
    {
        if (!self::isFileExists($filePath)) {
            return false;
        }

        return file_put_contents($filePath, '', LOCK_EX) !== false;
    }

    /**
     * Get file contents with a timeout for remote or local files.
     *
     * @param string $filePath File path or URL.
     * @param int    $timeout  Timeout in seconds (default 10).
     *
     * @return string|false File contents or false on failure.
     *
     * @example
     * ```php
     * $content = File::getFileContents('/uploads/myplugin/file.txt', 5);
     * ```
     */
    public static function getFileContents(string $filePath, int $timeout = 10): string|false
    {
        $context = stream_context_create(['http' => ['timeout' => $timeout]]);
        return @file_get_contents($filePath, false, $context);
    }

    /**
     * Generate SHA256 hash of a file.
     *
     * @param string $filePath Absolute path.
     *
     * @return string|false SHA256 hash or false if file missing.
     *
     * @example
     * ```php
     * $hash = File::fileSha256('/uploads/myplugin/file.txt');
     * echo $hash;
     * ```
     */
    public static function fileSha256(string $filePath): string|false
    {
        return self::isFileExists($filePath) ? hash_file('sha256', $filePath) : false;
    }

    /**
     * Check if a directory exists.
     *
     * @param string $dirPath Absolute path.
     *
     * @return bool True if directory exists, false otherwise.
     *
     * @example
     * ```php
     * $exists = File::dirExists('/uploads/myplugin');
     * echo $exists ? 'Yes' : 'No';
     * ```
     */
    public static function dirExists(string $dirPath): bool
    {
        return is_dir($dirPath);
    }

    /**
     * Duplicate a file to the same directory or a new directory.
     *
     * @param string $filePath Absolute path of the file to duplicate.
     * @param string $destDir  Optional destination directory; defaults to same directory.
     *
     * @return string|false Path of duplicated file or false on failure.
     *
     * @example
     * ```php
     * $copy = File::duplicateFile('/uploads/myplugin/file.txt');
     * echo $copy;
     * ```
     */
    public static function duplicateFile(string $filePath, string $destDir = ''): string|false
    {
        if (!self::isFileExists($filePath)) {
            return false;
        }

        if (!$destDir) {
            $destDir = dirname($filePath);
        }

        self::ensureDir($destDir);

        $uniqueName = self::uniqueName($destDir, basename($filePath));
        $destPath = $destDir . '/' . $uniqueName;

        return copy($filePath, $destPath) ? $destPath : false;
    }

    /**
     * Get file path relative to WP_CONTENT_DIR.
     *
     * @param string $filePath Absolute path.
     *
     * @return string Relative path or original if not inside wp-content.
     *
     * @example
     * ```php
     * $relative = File::relativeToContentDir(WP_CONTENT_DIR . '/uploads/myplugin/file.txt');
     * echo $relative;
     * ```
     */
    public static function relativeToContentDir(string $filePath): string
    {
        if (str_starts_with($filePath, WP_CONTENT_DIR)) {
            return substr($filePath, strlen(WP_CONTENT_DIR));
        }
        return $filePath;
    }

    /**
     * Safely append content to a file, creating it if it doesn't exist.
     *
     * @param string $filePath Absolute path.
     * @param string $content  Content to append.
     *
     * @return bool True on success, false on failure.
     *
     * @example
     * ```php
     * File::appendToFile('/uploads/myplugin/log.txt', "New log entry\n");
     * ```
     */
    public static function appendToFile(string $filePath, string $content): bool
    {
        self::ensureDir(dirname($filePath));
        return file_put_contents($filePath, $content, FILE_APPEND | LOCK_EX) !== false;
    }

    /**
     * Check if a path is a directory.
     *
     * @param string $path Absolute path.
     *
     * @return bool True if directory, false otherwise.
     *
     * @example
     * ```php
     * $isDir = File::isDirectory('/uploads/myplugin');
     * echo $isDir ? 'Yes' : 'No';
     * ```
     */
    public static function isDirectory(string $path): bool
    {
        return is_dir($path);
    }

    /**
     * Convert a file to a base64-encoded string.
     *
     * @param string $filePath Absolute path.
     *
     * @return string|false Base64 string or false if file missing.
     *
     * @example
     * ```php
     * $b64 = File::fileToBase64('/uploads/myplugin/file.jpg');
     * echo $b64;
     * ```
     */
    public static function fileToBase64(string $filePath): string|false
    {
        if (!self::isFileExists($filePath)) {
            return false;
        }

        $content = file_get_contents($filePath);
        return $content !== false ? base64_encode($content) : false;
    }

    /**
     * Get the dimensions of an image.
     *
     * @param string $filePath Absolute path.
     *
     * @return array|false [width, height] or false if not an image.
     *
     * @example
     * ```php
     * $dims = File::getImageDimensions('/uploads/myplugin/image.jpg');
     * print_r($dims);
     * ```
     */
    public static function getImageDimensions(string $filePath): array|false
    {
        if (!self::isFileExists($filePath)) {
            return false;
        }

        $size = getimagesize($filePath);
        return $size ? [$size[0], $size[1]] : false;
    }

    /**
     * Safely move an uploaded image to a destination folder.
     *
     * @param array  $file     $_FILES array for the image.
     * @param string $destDir  Destination directory.
     *
     * @return string|false Path of moved image or false on failure.
     *
     * @example
     * ```php
     * $path = File::moveUploadedImage($_FILES['image'], WP_CONTENT_DIR . '/uploads');
     * echo $path;
     * ```
     */
    public static function moveUploadedImage(array $file, string $destDir): string|false
    {
        $uploaded = self::upload($file);
        if (is_wp_error($uploaded)) {
            return false;
        }

        return $uploaded['file'] ?? false;
    }

    /**
     * Generate a thumbnail for an image.
     *
     * @param string $filePath Absolute path to the original image.
     * @param int    $width    Thumbnail width.
     * @param int    $height   Thumbnail height.
     *
     * @return string|false Path to thumbnail or false on failure.
     *
     * @example
     * ```php
     * $thumb = File::generateThumbnail('/uploads/myplugin/image.jpg', 150, 150);
     * echo $thumb;
     * ```
     */
    public static function generateThumbnail(string $filePath, int $width, int $height): string|false
    {
        if (!self::isFileExists($filePath) || !self::isImage($filePath)) {
            return false;
        }

        $dir = dirname($filePath);
        $name = self::getFileNameNoExt($filePath);
        $ext = self::extensionLower($filePath);

        $thumbPath = $dir . '/' . $name . '-thumb.' . $ext;

        if (self::resizeImage($filePath, $width, $height)) {
            copy($filePath, $thumbPath);
            return $thumbPath;
        }

        return false;
    }

    /**
     * Normalize a file path by removing redundant slashes.
     *
     * @param string $path File path.
     *
     * @return string Normalized path.
     *
     * @example
     * ```php
     * $normalized = File::normalizePath('/uploads//myplugin///file.txt');
     * echo $normalized;
     * ```
     */
    public static function normalizePath(string $path): string
    {
        return preg_replace('#/+#', '/', str_replace('\\', '/', $path));
    }

    /**
     * Check if a file is readable and writable.
     *
     * @param string $filePath Absolute path.
     *
     * @return bool True if both readable and writable, false otherwise.
     *
     * @example
     * ```php
     * $rw = File::isReadableWritable('/uploads/myplugin/file.txt');
     * echo $rw ? 'Yes' : 'No';
     * ```
     */
    public static function isReadableWritable(string $filePath): bool
    {
        return self::isReadable($filePath) && is_writable($filePath);
    }

    /**
     * Get MIME type from a file extension.
     *
     * @param string $extension File extension.
     *
     * @return string MIME type or application/octet-stream if unknown.
     *
     * @example
     * ```php
     * $mime = File::mimeTypeFromExtension('jpg');
     * echo $mime; // image/jpeg
     * ```
     */
    public static function mimeTypeFromExtension(string $extension): string
    {
        $map = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'pdf'  => 'application/pdf',
            'txt'  => 'text/plain',
            'csv'  => 'text/csv',
            'json' => 'application/json',
        ];

        $ext = strtolower($extension);
        return $map[$ext] ?? 'application/octet-stream';
    }

    /**
     * Force download a file to the browser.
     *
     * @param string $filePath Absolute path.
     * @param string $fileName Optional name for the download.
     *
     * @return void
     *
     * @example
     * ```php
     * File::downloadFile('/uploads/myplugin/file.pdf', 'document.pdf');
     * ```
     */
    public static function downloadFile(string $filePath, string $fileName = ''): void
    {
        if (!self::isFileExists($filePath)) {
            return;
        }

        if (!$fileName) {
            $fileName = basename($filePath);
        }

        header('Content-Description: File Transfer');
        header('Content-Type: ' . mime_content_type($filePath));
        header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    /**
     * Create a versioned temporary file in WP temp directory.
     *
     * @param string $prefix Optional prefix.
     * @param string $ext    Optional extension.
     * @param int    $version Optional version number.
     *
     * @return string Full path to versioned temp file.
     *
     * @example
     * ```php
     * $tmpFile = File::versionedTempFile('myplugin_', 'txt', 2);
     * ```
     */
    public static function versionedTempFile(string $prefix = 'tmp_', string $ext = '', int $version = 1): string
    {
        $file = self::tempFile($prefix, $ext);
        if ($version > 1) {
            $file = preg_replace('/(\.' . preg_quote($ext, '/') . ')$/', '-' . $version . '$1', $file);
        }
        return $file;
    }

    /**
     * Merge multiple files into one.
     *
     * @param array  $files Array of absolute file paths.
     * @param string $dest  Destination file path.
     *
     * @return bool True on success, false on failure.
     *
     * @example
     * ```php
     * File::mergeFiles(['/file1.txt','/file2.txt'], '/merged.txt');
     * ```
     */
    public static function mergeFiles(array $files, string $dest): bool
    {
        self::ensureDir(dirname($dest));
        $handle = fopen($dest, 'w');
        if ($handle === false) return false;

        foreach ($files as $file) {
            if (self::isFileExists($file)) {
                $content = file_get_contents($file);
                fwrite($handle, $content);
            }
        }

        fclose($handle);
        return true;
    }

    /**
     * Create a zip archive from an array of files.
     *
     * @param array  $files Array of absolute file paths.
     * @param string $zipPath Destination zip file path.
     *
     * @return bool True on success, false on failure.
     *
     * @example
     * ```php
     * File::createZip(['/file1.txt','/file2.txt'], '/backup.zip');
     * ```
     */
    public static function createZip(array $files, string $zipPath): bool
    {
        if (!class_exists('ZipArchive')) {
            return false;
        }

        self::ensureDir(dirname($zipPath));

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        foreach ($files as $file) {
            if (self::isFileExists($file)) {
                $zip->addFile($file, basename($file));
            }
        }

        return $zip->close();
    }

    /**
     * Extract a zip archive to a destination directory.
     *
     * @param string $zipPath Absolute path to zip file.
     * @param string $destDir Destination directory.
     *
     * @return bool True on success, false on failure.
     *
     * @example
     * ```php
     * File::extractZip('/backup.zip', '/uploads/restore');
     * ```
     */
    public static function extractZip(string $zipPath, string $destDir): bool
    {
        if (!class_exists('ZipArchive') || !self::isFileExists($zipPath)) {
            return false;
        }

        self::ensureDir($destDir);

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return false;
        }

        $result = $zip->extractTo($destDir);
        $zip->close();
        return $result;
    }

    /**
     * Restore a backup file to its original location.
     *
     * @param string $backupPath Absolute path to backup file.
     * @param string $originalPath Original file path to restore.
     *
     * @return bool True on success, false on failure.
     *
     * @example
     * ```php
     * $restored = File::restoreBackup('/uploads/myplugin/file-backup-123.txt','/uploads/myplugin/file.txt');
     * ```
     */
    public static function restoreBackup(string $backupPath, string $originalPath): bool
    {
        if (!self::isFileExists($backupPath)) {
            return false;
        }

        self::ensureDir(dirname($originalPath));
        return copy($backupPath, $originalPath);
    }

    /**
     * Check if a file is currently locked by another process.
     *
     * @param string $filePath Absolute path.
     *
     * @return bool True if locked, false otherwise.
     *
     * @example
     * ```php
     * $locked = File::isFileLocked('/uploads/myplugin/file.txt');
     * ```
     */
    public static function isFileLocked(string $filePath): bool
    {
        if (!self::isFileExists($filePath)) {
            return false;
        }

        $fp = @fopen($filePath, 'r+');
        if ($fp === false) return true;

        $locked = !flock($fp, LOCK_EX | LOCK_NB);
        fclose($fp);
        return $locked;
    }

    /**
     * Generate a unique versioned filename in a directory.
     *
     * @param string $dir      Directory path.
     * @param string $fileName Original file name.
     * @param int    $version  Optional version number.
     *
     * @return string Unique versioned file name.
     *
     * @example
     * ```php
     * $versioned = File::versionedFileName('/uploads/myplugin', 'file.txt', 2);
     * ```
     */
    public static function versionedFileName(string $dir, string $fileName, int $version = 1): string
    {
        $name = self::getFileNameNoExt($fileName);
        $ext = self::extensionLower($fileName);
        $versioned = $name . ($version > 1 ? '-' . $version : '') . ($ext ? '.' . $ext : '');

        while (file_exists($dir . '/' . $versioned)) {
            $version++;
            $versioned = $name . '-' . $version . ($ext ? '.' . $ext : '');
        }

        return $versioned;
    }

    /**
     * Create a symbolic link.
     *
     * @param string $target Target file or folder.
     * @param string $link   Link path.
     *
     * @return bool True on success, false otherwise.
     *
     * @example
     * ```php
     * File::symlink('/real/file.txt', '/link/file.txt');
     * ```
     */
    public static function symlink(string $target, string $link): bool
    {
        if (file_exists($link)) {
            return false;
        }
        return @symlink($target, $link);
    }

    /**
     * Update file modified timestamp (like touch command).
     *
     * @param string $filePath Absolute path.
     *
     * @return bool True on success, false on failure.
     *
     * @example
     * ```php
     * File::touch('/uploads/file.txt');
     * ```
     */
    public static function touch(string $filePath): bool
    {
        self::ensureDir(dirname($filePath));
        return touch($filePath);
    }

    /**
     * Truncate a file (clear its contents).
     *
     * @param string $filePath Absolute file path.
     *
     * @return bool True on success, false otherwise.
     *
     * @example
     * ```php
     * File::truncate('/uploads/log.txt');
     * ```
     */
    public static function truncate(string $filePath): bool
    {
        if (!self::isFileExists($filePath)) {
            return false;
        }
        return file_put_contents($filePath, '') !== false;
    }

    /**
     * Get free disk space of a directory.
     *
     * @param string $dir Directory path.
     *
     * @return string|false Human-readable free space or false on error.
     *
     * @example
     * ```php
     * echo File::diskFreeSpace('/uploads');
     * ```
     */
    public static function diskFreeSpace(string $dir): string|false
    {
        if (!is_dir($dir)) return false;
        $bytes = disk_free_space($dir);
        return $bytes !== false ? self::humanFileSize($bytes) : false;
    }

    /**
     * Sideload a file from URL and attach to media library.
     *
     * @param string $url  File URL to sideload.
     * @param int    $postId Optional post ID to attach media.
     *
     * @return int|WP_Error Attachment ID on success, WP_Error on failure.
     *
     * @example
     * ```php
     * $id = File::sideloadToMedia('https://example.com/image.jpg', 123);
     * ```
     */
    public static function sideloadToMedia(string $url, int $postId = 0): int|\WP_Error
    {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $tmp = download_url($url);
        if (is_wp_error($tmp)) return $tmp;

        $file = [
            'name'     => basename($url),
            'type'     => mime_content_type($tmp),
            'tmp_name' => $tmp,
            'error'    => 0,
            'size'     => filesize($tmp),
        ];

        $id = media_handle_sideload($file, $postId);
        if (is_wp_error($id)) {
            @unlink($tmp);
        }
        return $id;
    }

    /**
     * Get attachment file path by attachment ID.
     *
     * @param int $attachmentId Attachment ID.
     *
     * @return string|false File path or false if not found.
     *
     * @example
     * ```php
     * $path = File::getAttachmentPath(45);
     * ```
     */
    public static function getAttachmentPath(int $attachmentId): string|false
    {
        return get_attached_file($attachmentId);
    }

    /**
     * Get attachment URL by attachment ID.
     *
     * @param int $attachmentId Attachment ID.
     *
     * @return string|false File URL or false if not found.
     *
     * @example
     * ```php
     * $url = File::getAttachmentUrl(45);
     * ```
     */
    public static function getAttachmentUrl(int $attachmentId): string|false
    {
        return wp_get_attachment_url($attachmentId);
    }

    /**
     * Regenerate attachment metadata.
     *
     * @param int $attachmentId Attachment ID.
     *
     * @return array|WP_Error Metadata array or error.
     *
     * @example
     * ```php
     * $meta = File::regenerateAttachmentMeta(45);
     * ```
     */
    public static function regenerateAttachmentMeta(int $attachmentId): array|\WP_Error
    {
        $filePath = get_attached_file($attachmentId);
        if (!$filePath) {
            return new \WP_Error('file_missing', 'Attachment file not found.');
        }
        $meta = wp_generate_attachment_metadata($attachmentId, $filePath);
        if (!$meta) {
            return new \WP_Error('meta_failed', 'Could not generate metadata.');
        }
        wp_update_attachment_metadata($attachmentId, $meta);
        return $meta;
    }

    /**
     * Get all registered MIME types in WordPress.
     *
     * @return array List of MIME types.
     *
     * @example
     * ```php
     * $mimes = File::getAllowedMimes();
     * ```
     */
    public static function getAllowedMimes(): array
    {
        return get_allowed_mime_types();
    }

    /**
     * Check if a file path is inside WordPress uploads folder.
     *
     * @param string $filePath File path.
     *
     * @return bool True if inside uploads folder, false otherwise.
     *
     * @example
     * ```php
     * $inside = File::isInUploads('/var/www/html/wp-content/uploads/myplugin/file.jpg');
     * ```
     */
    public static function isInUploads(string $filePath): bool
    {
        $uploads = wp_get_upload_dir();
        return str_starts_with($filePath, $uploads['basedir']);
    }

    /**
     * Get relative path inside uploads directory.
     *
     * @param string $filePath Absolute path.
     *
     * @return string|false Relative path or false if not in uploads.
     *
     * @example
     * ```php
     * $relative = File::relativeToUploads('/var/www/html/wp-content/uploads/2025/01/pic.jpg');
     * ```
     */
    public static function relativeToUploads(string $filePath): string|false
    {
        $uploads = wp_get_upload_dir();
        if (!str_starts_with($filePath, $uploads['basedir'])) {
            return false;
        }
        return ltrim(str_replace($uploads['basedir'], '', $filePath), '/');
    }

    /**
     * Move a file to uploads folder and return URL.
     *
     * @param string $sourcePath Absolute file path.
     * @param string $subDir     Optional subdirectory inside uploads.
     *
     * @return string|WP_Error File URL or error.
     *
     * @example
     * ```php
     * $url = File::moveToUploads('/tmp/file.png', 'myplugin');
     * ```
     */
    public static function moveToUploads(string $sourcePath, string $subDir = ''): string|\WP_Error
    {
        if (!self::isFileExists($sourcePath)) {
            return new \WP_Error('not_found', 'Source file does not exist.');
        }

        $uploads = wp_get_upload_dir();
        $destDir = $uploads['basedir'] . ($subDir ? '/' . $subDir : '');
        self::ensureDir($destDir);

        $destPath = $destDir . '/' . basename($sourcePath);
        if (!rename($sourcePath, $destPath)) {
            return new \WP_Error('move_failed', 'Could not move file.');
        }

        return self::getUrl($destPath);
    }

    /**
     * Crop an image to exact dimensions.
     *
     * @param string $filePath Absolute path of source image.
     * @param int    $x        X start coordinate.
     * @param int    $y        Y start coordinate.
     * @param int    $width    Crop width.
     * @param int    $height   Crop height.
     *
     * @return string|WP_Error Path to cropped image or error.
     *
     * @example
     * ```php
     * $cropped = File::cropImage('/uploads/pic.jpg', 0, 0, 100, 100);
     * ```
     */
    public static function cropImage(string $filePath, int $x, int $y, int $width, int $height): string|\WP_Error
    {
        $editor = wp_get_image_editor($filePath);
        if (is_wp_error($editor)) return $editor;

        $editor->crop($x, $y, $width, $height);
        $destPath = $editor->generate_filename('cropped');
        $saved = $editor->save($destPath);

        return $saved ? $destPath : new \WP_Error('crop_failed', 'Failed to crop image.');
    }

    /**
     * Convert an image to another format (jpg, png, gif, webp).
     *
     * @param string $filePath Absolute path of source image.
     * @param string $format   Target format (jpg|png|gif|webp).
     *
     * @return string|WP_Error Converted file path or error.
     *
     * @example
     * ```php
     * $webp = File::convertImage('/uploads/pic.jpg', 'webp');
     * ```
     */
    public static function convertImage(string $filePath, string $format): string|\WP_Error
    {
        $editor = wp_get_image_editor($filePath);
        if (is_wp_error($editor)) return $editor;

        $destPath = $editor->generate_filename(null, null, $format);
        $saved = $editor->save($destPath, $format);

        return $saved ? $destPath : new \WP_Error('convert_failed', 'Failed to convert image.');
    }

    /**
     * Generate multiple image sizes (like WordPress thumbnails).
     *
     * @param string $filePath Source image.
     * @param array  $sizes    Array of size arrays (width, height, crop).
     *
     * @return array|WP_Error Array of generated files or error.
     *
     * @example
     * ```php
     * $sizes = [
     *     ['width' => 150, 'height' => 150, 'crop' => true],
     *     ['width' => 300, 'height' => 200, 'crop' => false],
     * ];
     * $generated = File::generateSizes('/uploads/pic.jpg', $sizes);
     * ```
     */
    public static function generateSizes(string $filePath, array $sizes): array|\WP_Error
    {
        $editor = wp_get_image_editor($filePath);
        if (is_wp_error($editor)) return $editor;

        $results = [];
        foreach ($sizes as $size) {
            $editor->resize($size['width'], $size['height'], $size['crop'] ?? false);
            $destPath = $editor->generate_filename("{$size['width']}x{$size['height']}");
            $saved = $editor->save($destPath);
            if ($saved) $results[] = $saved;
        }

        return $results;
    }

    /**
     * Create a placeholder transparent PNG image.
     *
     * @param int    $width  Width of placeholder.
     * @param int    $height Height of placeholder.
     * @param string $filePath Destination file path.
     *
     * @return string|WP_Error Path of generated file or error.
     *
     * @example
     * ```php
     * $placeholder = File::createPlaceholder(200, 100, '/uploads/blank.png');
     * ```
     */
    public static function createPlaceholder(int $width, int $height, string $filePath): string|\WP_Error
    {
        $image = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $bg);
        imagesavealpha($image, true);

        if (imagepng($image, $filePath)) {
            imagedestroy($image);
            return $filePath;
        }

        return new \WP_Error('placeholder_failed', 'Could not create placeholder image.');
    }

    /**
     * Watermark an image with text.
     *
     * @param string $filePath Image path.
     * @param string $text     Watermark text.
     *
     * @return string|WP_Error Path of watermarked file or error.
     *
     * @example
     * ```php
     * $wm = File::addWatermark('/uploads/pic.jpg', '© MySite');
     * ```
     */
    public static function addWatermark(string $filePath, string $text): string|\WP_Error
    {
        $editor = wp_get_image_editor($filePath);
        if (is_wp_error($editor)) return $editor;

        $size = $editor->get_size();
        $editor->text($text, $size['width'] - 100, $size['height'] - 20, 12, '000000');
        $destPath = $editor->generate_filename('watermarked');
        $saved = $editor->save($destPath);

        return $saved ? $destPath : new \WP_Error('watermark_failed', 'Failed to add watermark.');
    }

    /**
     * Optimize an image using WordPress editor (re-save).
     *
     * @param string $filePath Image path.
     *
     * @return string|WP_Error Optimized file path or error.
     *
     * @example
     * ```php
     * $optimized = File::optimizeImage('/uploads/pic.jpg');
     * ```
     */
    public static function optimizeImage(string $filePath): string|\WP_Error
    {
        $editor = wp_get_image_editor($filePath);
        if (is_wp_error($editor)) return $editor;

        $saved = $editor->save($filePath);
        return $saved ? $filePath : new \WP_Error('optimize_failed', 'Failed to optimize image.');
    }

    /**
     * Check if an image is larger than given dimensions.
     *
     * @param string $filePath Image path.
     * @param int    $width    Max width.
     * @param int    $height   Max height.
     *
     * @return bool True if larger, false otherwise.
     *
     * @example
     * ```php
     * $large = File::isLargerThan('/uploads/pic.jpg', 1920, 1080);
     * ```
     */
    public static function isLargerThan(string $filePath, int $width, int $height): bool
    {
        $size = getimagesize($filePath);
        if (!$size) return false;
        return $size[0] > $width || $size[1] > $height;
    }

    /**
     * Get image aspect ratio.
     *
     * @param string $filePath Image path.
     *
     * @return float|false Aspect ratio (width/height) or false.
     *
     * @example
     * ```php
     * $ratio = File::getAspectRatio('/uploads/pic.jpg');
     * ```
     */
    public static function getAspectRatio(string $filePath): float|false
    {
        $size = getimagesize($filePath);
        if (!$size || $size[1] == 0) return false;
        return $size[0] / $size[1];
    }

    /**
     * Get MD5 hash of a file.
     *
     * @param string $filePath Path to file.
     *
     * @return string|false MD5 hash or false if file not found.
     *
     * @example
     * ```php
     * $hash = File::getMd5('/uploads/file.txt');
     * ```
     */
    public static function getMd5(string $filePath): string|false
    {
        return file_exists($filePath) ? md5_file($filePath) : false;
    }

    /**
     * Get SHA1 hash of a file.
     *
     * @param string $filePath Path to file.
     *
     * @return string|false SHA1 hash or false if file not found.
     *
     * @example
     * ```php
     * $hash = File::getSha1('/uploads/file.txt');
     * ```
     */
    public static function getSha1(string $filePath): string|false
    {
        return file_exists($filePath) ? sha1_file($filePath) : false;
    }

    /**
     * Get file extension.
     *
     * @param string $filePath Path to file.
     *
     * @return string Extension (without dot).
     *
     * @example
     * ```php
     * $ext = File::getExtension('/uploads/file.txt');
     * ```
     */
    public static function getExtension(string $filePath): string
    {
        return pathinfo($filePath, PATHINFO_EXTENSION);
    }

    /**
     * Create a temporary file.
     *
     * @param string $prefix Optional file prefix.
     *
     * @return string|false Path to temporary file or false.
     *
     * @example
     * ```php
     * $tmp = File::createTempFile('myplugin_');
     * ```
     */
    public static function createTempFile(string $prefix = 'tmp_'): string|false
    {
        return tempnam(sys_get_temp_dir(), $prefix);
    }

    /**
     * Scan a directory recursively and return all files.
     *
     * @param string $dir Directory path.
     *
     * @return array List of file paths.
     *
     * @example
     * ```php
     * $files = File::scanDirRecursive(WP_CONTENT_DIR . '/uploads/myplugin');
     * ```
     */
    public static function scanDirRecursive(string $dir): array
    {
        $result = [];
        if (!is_dir($dir)) {
            return $result;
        }
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $result = array_merge($result, self::scanDirRecursive($path));
            } else {
                $result[] = $path;
            }
        }
        return $result;
    }

    /**
     * Count files in a directory recursively.
     *
     * @param string $dir Directory path.
     *
     * @return int Number of files.
     *
     * @example
     * ```php
     * $count = File::countFiles(WP_CONTENT_DIR . '/uploads/myplugin');
     * ```
     */
    public static function countFiles(string $dir): int
    {
        return count(self::scanDirRecursive($dir));
    }

    /**
     * Get free disk space where WordPress is installed.
     *
     * @return float|false Free space in bytes or false.
     *
     * @example
     * ```php
     * $space = File::freeDiskSpace();
     * ```
     */
    public static function freeDiskSpace(): float|false
    {
        return disk_free_space(ABSPATH);
    }

    /**
     * Get total disk space where WordPress is installed.
     *
     * @return float|false Total space in bytes or false.
     *
     * @example
     * ```php
     * $space = File::totalDiskSpace();
     * ```
     */
    public static function totalDiskSpace(): float|false
    {
        return disk_total_space(ABSPATH);
    }

    /**
     * Create an .htaccess file to block direct access.
     *
     * @param string $dir Directory path.
     *
     * @return bool True if created, false otherwise.
     *
     * @example
     * ```php
     * File::protectDir(WP_CONTENT_DIR . '/uploads/private');
     * ```
     */
    public static function protectDir(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }
        $htaccessPath = rtrim($dir, '/') . '/.htaccess';
        $rules = "Order deny,allow\nDeny from all";
        return (bool) file_put_contents($htaccessPath, $rules);
    }

    /**
     * Safely list only files (no directories) from a directory.
     *
     * @param string $dir Directory path.
     *
     * @return array List of file paths.
     *
     * @example
     * ```php
     * $files = File::listFilesOnly(WP_CONTENT_DIR . '/uploads/myplugin');
     * ```
     */
    public static function listFilesOnly(string $dir): array
    {
        $files = [];
        if (!is_dir($dir)) {
            return $files;
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_file($path)) {
                $files[] = $path;
            }
        }
        return $files;
    }

    /**
     * Change file permissions.
     *
     * @param string $filePath Path to the file.
     * @param int    $mode Octal permission (e.g. 0644).
     *
     * @return bool True on success, false otherwise.
     *
     * @example
     * ```php
     * File::chmod(WP_CONTENT_DIR . '/uploads/test.txt', 0644);
     * ```
     */
    public static function chmod(string $filePath, int $mode): bool
    {
        return chmod($filePath, $mode);
    }

    /**
     * Get file permissions in octal format.
     *
     * @param string $filePath Path to the file.
     *
     * @return string File permissions (e.g. "0644").
     *
     * @example
     * ```php
     * $perm = File::getPermissions(WP_CONTENT_DIR . '/uploads/test.txt');
     * ```
     */
    public static function getPermissions(string $filePath): string
    {
        return substr(sprintf('%o', fileperms($filePath)), -4);
    }

    /**
     * Get file owner user ID.
     *
     * @param string $filePath Path to the file.
     *
     * @return int|false User ID or false on failure.
     *
     * @example
     * ```php
     * $owner = File::getOwner(WP_CONTENT_DIR . '/uploads/test.txt');
     * ```
     */
    public static function getOwner(string $filePath): int|false
    {
        return fileowner($filePath);
    }

    /**
     * Get file group ID.
     *
     * @param string $filePath Path to the file.
     *
     * @return int|false Group ID or false on failure.
     *
     * @example
     * ```php
     * $group = File::getGroup(WP_CONTENT_DIR . '/uploads/test.txt');
     * ```
     */
    public static function getGroup(string $filePath): int|false
    {
        return filegroup($filePath);
    }

    /**
     * Tail a file (get last N lines).
     *
     * @param string $filePath Path to the file.
     * @param int    $lines Number of lines.
     *
     * @return array List of last N lines.
     *
     * @example
     * ```php
     * $last = File::tail(WP_CONTENT_DIR . '/debug.log', 20);
     * ```
     */
    public static function tail(string $filePath, int $lines = 10): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        $f = fopen($filePath, "r");
        $cursor = -1;
        $output = [];
        $line = '';

        fseek($f, $cursor, SEEK_END);
        $char = fgetc($f);

        while ($lines > 0 && ftell($f) > 1) {
            if ($char === "\n") {
                $lines--;
                if ($line !== '') {
                    array_unshift($output, strrev($line));
                    $line = '';
                }
            } else {
                $line .= $char;
            }
            fseek($f, $cursor--, SEEK_END);
            $char = fgetc($f);
        }
        if ($line !== '') {
            array_unshift($output, strrev($line));
        }
        fclose($f);

        return $output;
    }

    /**
     * Read a file in chunks.
     *
     * @param string $filePath Path to the file.
     * @param int    $chunkSize Bytes per chunk.
     *
     * @return \Generator Yields each chunk.
     *
     * @example
     * ```php
     * foreach (File::readChunks(WP_CONTENT_DIR . '/bigfile.txt', 1024) as $chunk) {
     *     echo $chunk;
     * }
     * ```
     */
    public static function readChunks(string $filePath, int $chunkSize = 1024): \Generator
    {
        $handle = fopen($filePath, "rb");
        if ($handle === false) {
            return;
        }
        while (!feof($handle)) {
            yield fread($handle, $chunkSize);
        }
        fclose($handle);
    }

    /**
     * Get MD5 hash of a file.
     *
     * @param string $filePath Path to the file.
     *
     * @return string|false MD5 hash or false on failure.
     *
     * @example
     * ```php
     * $hash = File::md5(WP_CONTENT_DIR . '/uploads/test.txt');
     * ```
     */
    public static function md5(string $filePath): string|false
    {
        return file_exists($filePath) ? md5_file($filePath) : false;
    }

    /**
     * Get SHA1 hash of a file.
     *
     * @param string $filePath Path to the file.
     *
     * @return string|false SHA1 hash or false on failure.
     *
     * @example
     * ```php
     * $hash = File::sha1(WP_CONTENT_DIR . '/uploads/test.txt');
     * ```
     */
    public static function sha1(string $filePath): string|false
    {
        return file_exists($filePath) ? sha1_file($filePath) : false;
    }

    /**
     * Generate checksum (CRC32) of a file.
     *
     * @param string $filePath Path to the file.
     *
     * @return string|false CRC32 hash or false on failure.
     *
     * @example
     * ```php
     * $crc = File::crc32(WP_CONTENT_DIR . '/uploads/test.txt');
     * ```
     */
    public static function crc32(string $filePath): string|false
    {
        return file_exists($filePath) ? hash_file('crc32b', $filePath) : false;
    }

    /**
     * Compare two files by hash (MD5).
     *
     * @param string $file1 Path to first file.
     * @param string $file2 Path to second file.
     *
     * @return bool True if identical, false otherwise.
     *
     * @example
     * ```php
     * $same = File::compare(WP_CONTENT_DIR . '/uploads/a.txt', WP_CONTENT_DIR . '/uploads/b.txt');
     * ```
     */
    public static function compare(string $file1, string $file2): bool
    {
        if (!file_exists($file1) || !file_exists($file2)) {
            return false;
        }
        return md5_file($file1) === md5_file($file2);
    }

    /**
     * Check if file is duplicate of another.
     *
     * @param string $filePath Path to the file.
     * @param array  $otherFiles Array of file paths to check against.
     *
     * @return string|null Returns path of duplicate or null if none found.
     *
     * @example
     * ```php
     * $dup = File::findDuplicate(WP_CONTENT_DIR . '/uploads/test.txt', [WP_CONTENT_DIR . '/uploads/backup.txt']);
     * ```
     */
    public static function findDuplicate(string $filePath, array $otherFiles): ?string
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $hash = md5_file($filePath);
        foreach ($otherFiles as $file) {
            if (file_exists($file) && md5_file($file) === $hash) {
                return $file;
            }
        }
        return null;
    }

    /**
     * Verify file checksum against expected hash.
     *
     * @param string $filePath Path to file.
     * @param string $expected Expected hash value.
     * @param string $algo Hashing algorithm (md5, sha1, sha256).
     *
     * @return bool True if matches, false otherwise.
     *
     * @example
     * ```php
     * $ok = File::verifyChecksum(WP_CONTENT_DIR . '/uploads/test.txt', 'abc123...', 'sha256');
     * ```
     */
    public static function verifyChecksum(string $filePath, string $expected, string $algo = 'md5'): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $hash = hash_file($algo, $filePath);
        return hash_equals($expected, $hash);
    }

    /**
     * Get list of duplicate files in a directory.
     *
     * @param string $dir Directory path.
     *
     * @return array List of duplicate groups.
     *
     * @example
     * ```php
     * $dups = File::scanDuplicates(WP_CONTENT_DIR . '/uploads');
     * ```
     */
    public static function scanDuplicates(string $dir): array
    {
        $hashes = [];
        $duplicates = [];

        foreach (glob($dir . '/*') as $file) {
            if (is_file($file)) {
                $hash = md5_file($file);
                if (isset($hashes[$hash])) {
                    $duplicates[$hash][] = $file;
                } else {
                    $hashes[$hash] = $file;
                }
            }
        }

        return $duplicates;
    }

    /**
     * Get file line count.
     *
     * @param string $filePath Path to file.
     *
     * @return int Number of lines or 0 if not found.
     *
     * @example
     * ```php
     * $lines = File::lineCount(WP_CONTENT_DIR . '/uploads/test.txt');
     * ```
     */
    public static function lineCount(string $filePath): int
    {
        if (!file_exists($filePath)) {
            return 0;
        }

        $count = 0;
        $handle = fopen($filePath, "r");
        while (!feof($handle)) {
            fgets($handle);
            $count++;
        }
        fclose($handle);

        return $count;
    }

    /**
     * Get file word count.
     *
     * @param string $filePath Path to file.
     *
     * @return int Number of words or 0 if not found.
     *
     * @example
     * ```php
     * $words = File::wordCount(WP_CONTENT_DIR . '/uploads/test.txt');
     * ```
     */
    public static function wordCount(string $filePath): int
    {
        if (!file_exists($filePath)) {
            return 0;
        }

        $content = file_get_contents($filePath);
        return str_word_count(strip_tags($content));
    }

    /**
     * Get file extension in lowercase.
     *
     * @param string $filePath Path to file.
     *
     * @return string Extension without dot.
     *
     * @example
     * ```php
     * $ext = File::extension(WP_CONTENT_DIR . '/uploads/photo.PNG'); // "png"
     * ```
     */
    public static function extension(string $filePath): string
    {
        return strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    }

    /**
     * Get human-readable file type description.
     *
     * @param string $filePath Path to file.
     *
     * @return string Description (e.g., "Image", "Text").
     *
     * @example
     * ```php
     * $type = File::typeDescription(WP_CONTENT_DIR . '/uploads/music.mp3');
     * ```
     */
    public static function typeDescription(string $filePath): string
    {
        $mime = self::mimeType($filePath);

        if (!$mime) {
            return 'Unknown';
        }

        if (str_starts_with($mime, 'image/')) {
            return 'Image';
        }
        if (str_starts_with($mime, 'video/')) {
            return 'Video';
        }
        if (str_starts_with($mime, 'audio/')) {
            return 'Audio';
        }
        if (str_starts_with($mime, 'text/')) {
            return 'Text';
        }

        return ucfirst(explode('/', $mime)[0]);
    }

    /**
     * Extract EXIF data from an image (if supported).
     *
     * @param string $filePath Path to image.
     *
     * @return array EXIF data or empty array.
     *
     * @example
     * ```php
     * $exif = File::exif(WP_CONTENT_DIR . '/uploads/photo.jpg');
     * ```
     */
    public static function exif(string $filePath): array
    {
        return (function_exists('exif_read_data') && file_exists($filePath))
            ? @exif_read_data($filePath) ?: []
            : [];
    }

    /**
     * Get image dimensions.
     *
     * @param string $filePath Path to image.
     *
     * @return array|null Width & height or null.
     *
     * @example
     * ```php
     * $dim = File::dimensions(WP_CONTENT_DIR . '/uploads/photo.jpg');
     * ```
     */
    public static function dimensions(string $filePath): ?array
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $size = getimagesize($filePath);
        return $size ? ['width' => $size[0], 'height' => $size[1]] : null;
    }

    /**
     * Check if file is text (plain or similar).
     *
     * @param string $filePath Path to file.
     *
     * @return bool True if text-based.
     *
     * @example
     * ```php
     * if (File::isText(WP_CONTENT_DIR . '/uploads/readme.txt')) { ... }
     * ```
     */
    public static function isText(string $filePath): bool
    {
        $mime = self::mimeType($filePath);
        return $mime && (str_starts_with($mime, 'text/') || $mime === 'application/json' || $mime === 'application/xml');
    }

    /**
     * Detect encoding of a text file.
     *
     * @param string $filePath Path to file.
     *
     * @return string|null Encoding or null.
     *
     * @example
     * ```php
     * $enc = File::encoding(WP_CONTENT_DIR . '/uploads/readme.txt');
     * ```
     */
    public static function encoding(string $filePath): ?string
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);
        return mb_detect_encoding($content, mb_detect_order(), true) ?: null;
    }

    /**
     * Get file content preview (first N lines).
     *
     * @param string $filePath Path to file.
     * @param int $lines Number of lines to preview.
     *
     * @return string Preview text.
     *
     * @example
     * ```php
     * $preview = File::preview(WP_CONTENT_DIR . '/uploads/readme.txt', 5);
     * ```
     */
    public static function preview(string $filePath, int $lines = 10): string
    {
        if (!file_exists($filePath)) {
            return '';
        }

        $handle = fopen($filePath, 'r');
        $output = '';
        $count = 0;

        while (!feof($handle) && $count < $lines) {
            $output .= fgets($handle);
            $count++;
        }

        fclose($handle);
        return $output;
    }

    /**
     * Split a file into smaller chunks.
     *
     * @param string $filePath Path to file.
     * @param int $chunkSize Chunk size in bytes.
     * @param string $outputDir Directory to save chunks.
     *
     * @return array List of chunk file paths.
     *
     * @example
     * ```php
     * $chunks = File::split(WP_CONTENT_DIR . '/uploads/bigfile.zip', 1024 * 1024, WP_CONTENT_DIR . '/uploads/chunks');
     * ```
     */
    public static function split(string $filePath, int $chunkSize, string $outputDir): array
    {
        self::ensureDir($outputDir);

        $handle = fopen($filePath, 'rb');
        $parts  = [];
        $i      = 0;

        while (!feof($handle)) {
            $chunkPath = $outputDir . '/part_' . $i++;
            $parts[]   = $chunkPath;
            $chunk     = fread($handle, $chunkSize);
            file_put_contents($chunkPath, $chunk);
        }

        fclose($handle);
        return $parts;
    }

    /**
     * Merge multiple chunks into a single file.
     *
     * @param array $chunks List of chunk file paths.
     * @param string $outputFile Path to merged file.
     *
     * @return bool True if merged successfully.
     *
     * @example
     * ```php
     * File::merge($chunks, WP_CONTENT_DIR . '/uploads/merged.zip');
     * ```
     */
    public static function merge(array $chunks, string $outputFile): bool
    {
        $handle = fopen($outputFile, 'wb');

        foreach ($chunks as $chunk) {
            if (file_exists($chunk)) {
                fwrite($handle, file_get_contents($chunk));
            }
        }

        fclose($handle);
        return file_exists($outputFile);
    }

    /**
     * Compress a file using gzip.
     *
     * @param string $filePath Path to file.
     * @param string|null $outputFile Optional output path.
     *
     * @return string|false Compressed file path or false.
     *
     * @example
     * ```php
     * $gz = File::compressGzip(WP_CONTENT_DIR . '/uploads/data.json');
     * ```
     */
    public static function compressGzip(string $filePath, ?string $outputFile = null): string|false
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $outputFile = $outputFile ?: $filePath . '.gz';

        $in  = fopen($filePath, 'rb');
        $out = gzopen($outputFile, 'wb9');

        while (!feof($in)) {
            gzwrite($out, fread($in, 1024 * 512));
        }

        fclose($in);
        gzclose($out);

        return $outputFile;
    }

    /**
     * Decompress a gzip file.
     *
     * @param string $filePath Path to gzip file.
     * @param string|null $outputFile Optional output path.
     *
     * @return string|false Decompressed file path or false.
     *
     * @example
     * ```php
     * $file = File::decompressGzip(WP_CONTENT_DIR . '/uploads/data.json.gz');
     * ```
     */
    public static function decompressGzip(string $filePath, ?string $outputFile = null): string|false
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $outputFile = $outputFile ?: preg_replace('/\.gz$/', '', $filePath);

        $in  = gzopen($filePath, 'rb');
        $out = fopen($outputFile, 'wb');

        while (!gzeof($in)) {
            fwrite($out, gzread($in, 1024 * 512));
        }

        gzclose($in);
        fclose($out);

        return $outputFile;
    }

    /**
     * Tar archive creation.
     *
     * @param array $files List of files.
     * @param string $tarPath Output TAR file.
     *
     * @return string|false TAR path or false.
     *
     * @example
     * ```php
     * File::tar([WP_CONTENT_DIR . '/file1.txt'], WP_CONTENT_DIR . '/archive.tar');
     * ```
     */
    public static function tar(array $files, string $tarPath): string|false
    {
        try {
            $phar = new \PharData($tarPath);
            foreach ($files as $file) {
                if (file_exists($file)) {
                    $phar->addFile($file, basename($file));
                }
            }
            return $tarPath;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Extract TAR archive.
     *
     * @param string $tarPath Path to TAR file.
     * @param string $extractTo Output directory.
     *
     * @return bool True if successful.
     *
     * @example
     * ```php
     * File::untar(WP_CONTENT_DIR . '/archive.tar', WP_CONTENT_DIR . '/extracted');
     * ```
     */
    public static function untar(string $tarPath, string $extractTo): bool
    {
        try {
            $phar = new \PharData($tarPath);
            self::ensureDir($extractTo);
            $phar->extractTo($extractTo, null, true);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Add a text watermark to an image.
     *
     * @param string $filePath Path to original image.
     * @param string $outputPath Path to watermarked image.
     * @param string $text Watermark text.
     * @param int $fontSize Font size.
     * @param string $color Hex color code (#000000).
     *
     * @return bool True if successful.
     *
     * @example
     * ```php
     * File::addTextWatermark(WP_CONTENT_DIR . '/uploads/photo.jpg', WP_CONTENT_DIR . '/uploads/photo_marked.jpg', '© MySite', 14, '#FFFFFF');
     * ```
     */
    public static function addTextWatermark(string $filePath, string $outputPath, string $text, int $fontSize = 12, string $color = '#000000'): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $image = wp_get_image_editor($filePath);
        if (is_wp_error($image)) {
            return false;
        }

        // Get image size
        $size = $image->get_size();
        $width  = $size['width'];
        $height = $size['height'];

        // Create GD resource to overlay text
        $resource = imagecreatefromstring(file_get_contents($filePath));
        $col = sscanf($color, "#%02x%02x%02x");
        $textColor = imagecolorallocate($resource, $col[0], $col[1], $col[2]);

        // Place text at bottom-right corner
        $margin = 10;
        imagestring($resource, 5, $width - (strlen($text) * $fontSize / 2) - $margin, $height - $fontSize - $margin, $text, $textColor);

        // Save image back
        imagejpeg($resource, $outputPath, 90);
        imagedestroy($resource);

        return file_exists($outputPath);
    }

    /**
     * Generate a thumbnail for an image.
     *
     * @param string $filePath Path to image.
     * @param int $width Width.
     * @param int $height Height.
     *
     * @return string|false Path to thumbnail or false.
     *
     * @example
     * ```php
     * $thumb = File::thumbnail(WP_CONTENT_DIR . '/uploads/photo.jpg', 150, 150);
     * ```
     */
    public static function thumbnail(string $filePath, int $width, int $height): string|false
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $image = wp_get_image_editor($filePath);
        if (is_wp_error($image)) {
            return false;
        }

        $image->resize($width, $height, true);
        $saved = $image->save();
        return $saved['path'] ?? false;
    }

    /**
     * Get file name without extension.
     *
     * @param string $filePath Path to file.
     *
     * @return string File name.
     *
     * @example
     * ```php
     * echo File::filename('document.pdf'); // document
     * ```
     */
    public static function filename(string $filePath): string
    {
        return pathinfo($filePath, PATHINFO_FILENAME);
    }

    /**
     * Rotate an image by degrees.
     *
     * @param string $filePath Path to original image.
     * @param string $outputPath Path to rotated image.
     * @param float $angle Rotation angle (clockwise).
     *
     * @return bool True if rotated successfully.
     *
     * @example
     * ```php
     * File::rotateImage(WP_CONTENT_DIR . '/uploads/photo.jpg', WP_CONTENT_DIR . '/uploads/photo_rotated.jpg', 90);
     * ```
     */
    public static function rotateImage(string $filePath, string $outputPath, float $angle): bool
    {
        if (!file_exists($filePath)) return false;

        $image = wp_get_image_editor($filePath);
        if (is_wp_error($image)) return false;

        $image->rotate($angle);
        return !is_wp_error($image->save($outputPath));
    }

    /**
     * Flip an image horizontally or vertically.
     *
     * @param string $filePath Path to image.
     * @param string $outputPath Path to flipped image.
     * @param string $mode "horizontal" or "vertical".
     *
     * @return bool True if flipped successfully.
     *
     * @example
     * ```php
     * File::flipImage(WP_CONTENT_DIR . '/uploads/photo.jpg', WP_CONTENT_DIR . '/uploads/photo_flipped.jpg', 'horizontal');
     * ```
     */
    public static function flipImage(string $filePath, string $outputPath, string $mode = 'horizontal'): bool
    {
        if (!file_exists($filePath)) return false;

        $image = wp_get_image_editor($filePath);
        if (is_wp_error($image)) return false;

        $image->flip($mode);
        return !is_wp_error($image->save($outputPath));
    }

    /**
     * Convert image to grayscale.
     *
     * @param string $filePath Source image.
     * @param string $outputPath Output image.
     *
     * @return bool True if successful.
     *
     * @example
     * ```php
     * File::grayscale(WP_CONTENT_DIR . '/uploads/photo.jpg', WP_CONTENT_DIR . '/uploads/photo_gray.jpg');
     * ```
     */
    public static function grayscale(string $filePath, string $outputPath): bool
    {
        if (!file_exists($filePath)) return false;

        $image = wp_get_image_editor($filePath);
        if (is_wp_error($image)) return false;

        $image->set_quality(100);
        $image->set_output_format('jpg'); // ensure jpeg output

        $resource = imagecreatefromstring(file_get_contents($filePath));
        imagefilter($resource, IMG_FILTER_GRAYSCALE);
        imagejpeg($resource, $outputPath, 90);
        imagedestroy($resource);

        return file_exists($outputPath);
    }

    /**
     * Merge multiple images into one (vertical stack).
     *
     * @param array $files List of image paths.
     * @param string $outputPath Output merged image.
     *
     * @return bool True if merged successfully.
     *
     * @example
     * ```php
     * File::mergeImages([WP_CONTENT_DIR.'/a.jpg', WP_CONTENT_DIR.'/b.jpg'], WP_CONTENT_DIR.'/merged.jpg');
     * ```
     */
    public static function mergeImages(array $files, string $outputPath): bool
    {
        if (empty($files)) return false;

        $images = [];
        $width  = 0;
        $height = 0;

        // Load images and calculate final width & height
        foreach ($files as $file) {
            if (!file_exists($file)) continue;
            $img = imagecreatefromstring(file_get_contents($file));
            $images[] = $img;
            $width = max($width, imagesx($img));
            $height += imagesy($img);
        }

        if (empty($images)) return false;

        $merged = imagecreatetruecolor($width, $height);
        $y = 0;
        foreach ($images as $img) {
            imagecopy($merged, $img, 0, $y, 0, 0, imagesx($img), imagesy($img));
            $y += imagesy($img);
            imagedestroy($img);
        }

        imagejpeg($merged, $outputPath, 90);
        imagedestroy($merged);

        return file_exists($outputPath);
    }

    /**
     * Read first N bytes of a file.
     *
     * @param string $filePath Path to file.
     * @param int $bytes Number of bytes to read.
     *
     * @return string File content snippet.
     *
     * @example
     * ```php
     * $head = File::readHead(WP_CONTENT_DIR . '/uploads/data.txt', 100);
     * ```
     */
    public static function readHead(string $filePath, int $bytes = 1024): string
    {
        if (!file_exists($filePath)) return '';
        $handle = fopen($filePath, 'rb');
        $content = fread($handle, $bytes);
        fclose($handle);
        return $content;
    }

    /**
     * Read last N bytes of a file.
     *
     * @param string $filePath Path to file.
     * @param int $bytes Number of bytes.
     *
     * @return string File tail content.
     *
     * @example
     * ```php
     * $tail = File::readTail(WP_CONTENT_DIR . '/uploads/data.txt', 1024);
     * ```
     */
    public static function readTail(string $filePath, int $bytes = 1024): string
    {
        if (!file_exists($filePath)) return '';

        $size = filesize($filePath);
        $handle = fopen($filePath, 'rb');
        fseek($handle, max(0, $size - $bytes));
        $content = fread($handle, $bytes);
        fclose($handle);
        return $content;
    }

    /**
     * Replace content inside a file.
     *
     * @param string $filePath Path to file.
     * @param string $search Text to search.
     * @param string $replace Replacement text.
     *
     * @return bool True if replaced successfully.
     *
     * @example
     * ```php
     * File::replaceContent(WP_CONTENT_DIR . '/uploads/data.txt', 'foo', 'bar');
     * ```
     */
    public static function replaceContent(string $filePath, string $search, string $replace): bool
    {
        if (!file_exists($filePath)) return false;

        $content = file_get_contents($filePath);
        $content = str_replace($search, $replace, $content);
        return file_put_contents($filePath, $content) !== false;
    }

    /**
     * Prepend content to a file.
     *
     * @param string $filePath Path to file.
     * @param string $content Content to prepend.
     *
     * @return bool True if successful.
     *
     * @example
     * ```php
     * File::prependContent(WP_CONTENT_DIR . '/uploads/data.txt', "HEADER\n");
     * ```
     */
    public static function prependContent(string $filePath, string $content): bool
    {
        if (!file_exists($filePath)) return false;

        $oldContent = file_get_contents($filePath);
        return file_put_contents($filePath, $content . $oldContent) !== false;
    }

    /**
     * Check if file is empty.
     *
     * @param string $filePath Path to file.
     *
     * @return bool True if empty or non-existent.
     *
     * @example
     * ```php
     * if (File::isEmpty(WP_CONTENT_DIR . '/uploads/data.txt')) { ... }
     * ```
     */
    public static function isEmpty(string $filePath): bool
    {
        return !file_exists($filePath) || filesize($filePath) === 0;
    }

    /**
     * Backup a file by copying it with timestamp.
     *
     * @param string $filePath Path to original file.
     * @param string|null $backupDir Directory to store backup (default same dir).
     *
     * @return string|false Path to backup or false.
     *
     * @example
     * ```php
     * $backup = File::backup(WP_CONTENT_DIR . '/uploads/data.txt');
     * ```
     */
    public static function backup(string $filePath, ?string $backupDir = null): string|false
    {
        if (!file_exists($filePath)) return false;

        $backupDir = $backupDir ?: dirname($filePath);
        self::ensureDir($backupDir);

        $backupPath = $backupDir . '/' . pathinfo($filePath, PATHINFO_FILENAME) . '_' . time() . '.' . pathinfo($filePath, PATHINFO_EXTENSION);
        return copy($filePath, $backupPath) ? $backupPath : false;
    }

    /**
     * Recursively list all files in a directory.
     *
     * @param string $dir Directory path.
     * @param array $extensions Optional filter by file extensions.
     *
     * @return array List of file paths.
     *
     * @example
     * ```php
     * $files = File::scanDir(WP_CONTENT_DIR . '/uploads', ['jpg', 'png']);
     * ```
     */
    public static function scanDir(string $dir, array $extensions = []): array
    {
        $result = [];
        if (!is_dir($dir)) return $result;

        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        foreach ($items as $item) {
            if ($item->isFile()) {
                $ext = strtolower(pathinfo($item->getFilename(), PATHINFO_EXTENSION));
                if (empty($extensions) || in_array($ext, $extensions)) {
                    $result[] = $item->getPathname();
                }
            }
        }
        return $result;
    }

    /**
     * Move a directory recursively.
     *
     * @param string $src Source directory.
     * @param string $dst Destination directory.
     *
     * @return bool True if moved successfully.
     *
     * @example
     * ```php
     * File::moveDir(WP_CONTENT_DIR . '/uploads/source', WP_CONTENT_DIR . '/uploads/dest');
     * ```
     */
    public static function moveDir(string $src, string $dst): bool
    {
        if (!is_dir($src)) return false;

        self::copyDir($src, $dst);
        return self::deleteDir($src);
    }

    /**
     * Count total directories inside a directory recursively.
     *
     * @param string $dir Directory path.
     *
     * @return int Number of subdirectories.
     *
     * @example
     * ```php
     * $dirs = File::countDirs(WP_CONTENT_DIR . '/uploads');
     * ```
     */
    public static function countDirs(string $dir): int
    {
        if (!is_dir($dir)) return 0;

        $count = 0;
        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($items as $item) {
            if ($item->isDir()) $count++;
        }
        return $count;
    }

    /**
     * Get the newest file in a directory recursively.
     *
     * @param string $dir Directory path.
     *
     * @return string|null File path or null.
     *
     * @example
     * ```php
     * $latest = File::latestFile(WP_CONTENT_DIR . '/uploads');
     * ```
     */
    public static function latestFile(string $dir): ?string
    {
        if (!is_dir($dir)) return null;

        $latestTime = 0;
        $latestFile = null;

        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($items as $item) {
            if ($item->isFile() && $item->getMTime() > $latestTime) {
                $latestTime = $item->getMTime();
                $latestFile = $item->getPathname();
            }
        }

        return $latestFile;
    }

    /**
     * Get the oldest file in a directory recursively.
     *
     * @param string $dir Directory path.
     *
     * @return string|null File path or null.
     *
     * @example
     * ```php
     * $oldest = File::oldestFile(WP_CONTENT_DIR . '/uploads');
     * ```
     */
    public static function oldestFile(string $dir): ?string
    {
        if (!is_dir($dir)) return null;

        $oldestTime = PHP_INT_MAX;
        $oldestFile = null;

        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($items as $item) {
            if ($item->isFile() && $item->getMTime() < $oldestTime) {
                $oldestTime = $item->getMTime();
                $oldestFile = $item->getPathname();
            }
        }

        return $oldestFile;
    }

    /**
     * Filter files by size.
     *
     * @param string $dir Directory path.
     * @param int|null $minSize Minimum size in bytes.
     * @param int|null $maxSize Maximum size in bytes.
     *
     * @return array List of files matching size criteria.
     *
     * @example
     * ```php
     * $files = File::filterBySize(WP_CONTENT_DIR . '/uploads', 1024, 1048576);
     * ```
     */
    public static function filterBySize(string $dir, ?int $minSize = null, ?int $maxSize = null): array
    {
        $result = [];
        if (!is_dir($dir)) return $result;

        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($items as $item) {
            if ($item->isFile()) {
                $size = $item->getSize();
                if (($minSize === null || $size >= $minSize) && ($maxSize === null || $size <= $maxSize)) {
                    $result[] = $item->getPathname();
                }
            }
        }
        return $result;
    }

    /**
     * Filter files by modification date.
     *
     * @param string $dir Directory path.
     * @param int|null $after Timestamp for files modified after.
     * @param int|null $before Timestamp for files modified before.
     *
     * @return array List of files matching date criteria.
     *
     * @example
     * ```php
     * $files = File::filterByDate(WP_CONTENT_DIR . '/uploads', strtotime('-7 days'), time());
     * ```
     */
    public static function filterByDate(string $dir, ?int $after = null, ?int $before = null): array
    {
        $result = [];
        if (!is_dir($dir)) return $result;

        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($items as $item) {
            if ($item->isFile()) {
                $mtime = $item->getMTime();
                if (($after === null || $mtime >= $after) && ($before === null || $mtime <= $before)) {
                    $result[] = $item->getPathname();
                }
            }
        }
        return $result;
    }

    /**
     * Search files by name pattern (regex).
     *
     * @param string $dir Directory path.
     * @param string $pattern Regex pattern to match filenames.
     *
     * @return array List of matching files.
     *
     * @example
     * ```php
     * $files = File::searchByName(WP_CONTENT_DIR . '/uploads', '/^image_\d+\.jpg$/');
     * ```
     */
    public static function searchByName(string $dir, string $pattern): array
    {
        $result = [];
        if (!is_dir($dir)) return $result;

        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($items as $item) {
            if ($item->isFile() && preg_match($pattern, $item->getFilename())) {
                $result[] = $item->getPathname();
            }
        }
        return $result;
    }

    /**
     * Search files containing specific text.
     *
     * @param string $dir Directory path.
     * @param string $text Text to search for.
     *
     * @return array List of matching files.
     *
     * @example
     * ```php
     * $files = File::searchByContent(WP_CONTENT_DIR . '/uploads', 'TODO');
     * ```
     */
    public static function searchByContent(string $dir, string $text): array
    {
        $result = [];
        if (!is_dir($dir)) return $result;

        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($items as $item) {
            if ($item->isFile()) {
                $content = file_get_contents($item->getPathname());
                if (strpos($content, $text) !== false) {
                    $result[] = $item->getPathname();
                }
            }
        }
        return $result;
    }

    /**
     * Get file permissions in octal format.
     *
     * @param string $filePath Path to file.
     *
     * @return string|false Octal permissions or false.
     *
     * @example
     * ```php
     * echo File::permissions(WP_CONTENT_DIR . '/uploads/data.txt');
     * ```
     */
    public static function permissions(string $filePath): string|false
    {
        if (!file_exists($filePath)) return false;
        return substr(sprintf('%o', fileperms($filePath)), -4);
    }

    /**
     * Touch multiple files (update modification time).
     *
     * @param array $files List of file paths.
     *
     * @return bool True if all touched successfully.
     *
     * @example
     * ```php
     * File::touchMultiple([WP_CONTENT_DIR.'/a.txt', WP_CONTENT_DIR.'/b.txt']);
     * ```
     */
    public static function touchMultiple(array $files): bool
    {
        foreach ($files as $file) {
            if (!self::touchFile($file)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Replace file extension.
     *
     * @param string $filePath Original file path.
     * @param string $newExt New extension without dot.
     *
     * @return string New file path.
     *
     * @example
     * ```php
     * $newFile = File::replaceExtension(WP_CONTENT_DIR.'/data.txt','md');
     * ```
     */
    public static function replaceExtension(string $filePath, string $newExt): string
    {
        $info = pathinfo($filePath);
        return $info['dirname'] . '/' . $info['filename'] . '.' . $newExt;
    }

    /**
     * Get all file paths recursively with a given prefix.
     *
     * @param string $dir Directory path.
     * @param string $prefix Prefix filter.
     *
     * @return array Matching files.
     *
     * @example
     * ```php
     * $files = File::prefixFiles(WP_CONTENT_DIR.'/uploads','img_');
     * ```
     */
    public static function prefixFiles(string $dir, string $prefix): array
    {
        $result = [];
        if (!is_dir($dir)) return $result;

        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($items as $item) {
            if ($item->isFile() && str_starts_with($item->getFilename(), $prefix)) {
                $result[] = $item->getPathname();
            }
        }
        return $result;
    }
}
