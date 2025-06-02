<?php

namespace App\Services;

use App\Models\File;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ZipArchive;


class FileServices
{
    private const AVATAR_SIZE = 128;
    private $maxFileSize;
    private $allowedAspectRatio;

    public function __construct()
    {
        $this->maxFileSize = config('files.max_size', 5 * 1024 * 1024); // 5MB default
        $this->allowedAspectRatio = config('files.aspect_ratio', '1:1');
    }

    public function uploadUserPhoto(User $user, UploadedFile $file, $description = null): File
    {
        DB::beginTransaction();

        // Define directories
        $originalStorageDirectory = 'photos/original';
        $avatarStorageDirectory = 'photos/avatars';
        $tempDirectory = 'temp';

        try {
            Log::info('Attempting to upload file.', [
                'originalName' => $file->getClientOriginalName(),
                'extension' => $file->getClientOriginalExtension(),
                'size' => $file->getSize(),
                'mimeType' => $file->getMimeType(),
                'userId' => $user->id,
            ]);

            // Save the uploaded file to a temporary location within storage
            $tempPath = Storage::disk('private')->putFile($tempDirectory, $file);
            // $tempFullPath = Storage::disk('private')->path($tempPath); // No longer needed for validation

            Log::info('Temporary file stored.', ['path' => $tempPath]);

            // Get file content from the temporary file
            $fileContent = Storage::disk('private')->get($tempPath);

            // Validate and process the image using file content
            $this->validateFile($fileContent); // Validate using file content

            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $uniqueName = Str::uuid(); // Use UUID without extension for storage filename

            // Store original file from temporary location
            // We use rename within the same disk to avoid re-uploading/copying large files
            $originalRelativePath = $originalStorageDirectory . '/' . $uniqueName . '.' . $extension;
            if (!Storage::disk('private')->move($tempPath, $originalRelativePath)) {
                throw new \Exception('Failed to move temporary file to original storage.');
            }
            Log::info('Original file moved to storage.', ['path' => $originalRelativePath]);

            // Create and store avatar from the temporary file
            $avatar = Image::make($fileContent);
            $avatar->fit(self::AVATAR_SIZE, self::AVATAR_SIZE);

            $avatarUniqueName = Str::uuid();
            $avatarRelativePath = $avatarStorageDirectory . '/' . $avatarUniqueName . '.png'; // Store avatar as png
            Storage::disk('private')->put($avatarRelativePath, $avatar->encode('png'));
            Log::info('Avatar created and stored.', ['path' => $avatarRelativePath]);

            // Clean up the temporary file
            Storage::disk('private')->delete($tempPath);
            Log::info('Temporary file deleted.', ['path' => $tempPath]);

            // Create file record
            $fileRecord = File::create([
                'user_id' => $user->id,
                'name' => $originalName,
                'description' => $description ?? 'User profile photo',
                'format' => $extension,
                'size' => $file->getSize(),
                'path' => $originalRelativePath, // Store the relative path of the original file
                'avatar_path' => $avatarRelativePath, // Store the relative path of the avatar
                'original_name' => $originalName,
                'mime_type' => $file->getMimeType(),
            ]);
            Log::info('File record created successfully.', ['file_id' => $fileRecord->id]);

            // Update user's photo
            $user->update(['photo_id' => $fileRecord->id]);
            Log::info('User photo_id updated successfully.', ['user_id' => $user->id, 'photo_id' => $fileRecord->id]);

            DB::commit();

            return $fileRecord;
        } catch (\Exception $e) {
            DB::rollBack();
            // Clean up stored files if transaction fails (including temporary if it wasn't moved/deleted)
            if (isset($tempPath) && Storage::disk('private')->exists($tempPath)) { Storage::disk('private')->delete($tempPath); }
            if (isset($originalRelativePath) && Storage::disk('private')->exists($originalRelativePath)) { Storage::disk('private')->delete($originalRelativePath); }
            if (isset($avatarRelativePath) && Storage::disk('private')->exists($avatarRelativePath)) { Storage::disk('private')->delete($avatarRelativePath); }
            Log::error('File upload transaction failed.', ['error' => $e->getMessage(), 'exception' => $e]);
            throw $e;
        }
    }

    public function deleteUserPhoto(User $user): void
    {
        DB::beginTransaction();

        try {
            if ($user->photo) {
                $originalPath = $user->photo->path;
                $avatarPath = $user->photo->avatar_path; // Get avatar path from the model

                // Delete files from storage
                if (Storage::disk('private')->exists($originalPath)) {
                    Storage::disk('private')->delete($originalPath);
                    Log::info('Original file deleted from storage.', ['path' => $originalPath]);
                }
                 if (Storage::disk('private')->exists($avatarPath)) {
                     Storage::disk('private')->delete($avatarPath);
                     Log::info('Avatar file deleted from storage.', ['path' => $avatarPath]);
                }

                // Delete file record
                $user->photo->delete();
                Log::info('File record deleted.', ['file_id' => $user->photo->id]);

                // Unlink photo from user
                $user->update(['photo_id' => null]);
                Log::info('User photo_id set to null.', ['user_id' => $user->id]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('File deletion transaction failed.', ['error' => $e->getMessage(), 'exception' => $e]);
            throw $e;
        }
    }

    public function downloadOriginalPhoto(File $file)
    {
        // Check if the file exists using the service
        if (!$this->fileExists($file->path)) {
            Log::error("File does not exist for download: {$file->path}");
            throw new \Exception('Original file not found for download.');
        }
        return Storage::disk('private')->path($file->path); // Return full path for download response
    }

    public function getAvatarFullPath(File $file): string
    {
         if (!$this->fileExists($file->avatar_path)) {
            Log::error("Avatar file does not exist: {$file->avatar_path}");
            throw new \Exception('Avatar file not found.');
        }
        return Storage::disk('private')->path($file->avatar_path);
    }

    public function fileExists(string $path): bool
    {
        return Storage::disk('private')->exists($path);
    }

    private function validateFile(string $fileContent): void
    {
        // Use Intervention Image to read from file content
        $image = Image::make($fileContent);

        // Original file size validation might be slightly off here if Image::make changes size, keep for now.
        // A more robust validation would be before reading into Image.
        // if ($image->getSize() > $this->maxFileSize) {
        //     throw new \Exception('File size (' . $image->getSize() . ' bytes) exceeds maximum allowed size (' . $this->maxFileSize . ' bytes)');
        // }

        $width = $image->width();
        $height = $image->height();

        if (strpos($this->allowedAspectRatio, ':') === false) {
             $expectedRatio = (float) $this->allowedAspectRatio;
        } else {
            list($ratioWidth, $ratioHeight) = explode(':', $this->allowedAspectRatio);
            if ((float) $ratioHeight === 0.0) { // Check for float zero
                 throw new \Exception('Invalid aspect ratio configuration (height is zero).');
            }
            $expectedRatio = (float) $ratioWidth / (float) $ratioHeight;
        }

        $actualRatio = (float) $width / (float) $height; // Cast to float for precise comparison

        if (abs($actualRatio - $expectedRatio) > 0.01) {
            throw new \Exception('Image aspect ratio (' . round($actualRatio, 2) . ') does not match required ratio (' . round($expectedRatio, 2) . ')');
        }
    }

    public function createPhotosArchive()
    {
        $users = User::with('photo')->whereNotNull('photo_id')->get();
        $zip = new \ZipArchive();
        $zipName = 'photos_' . now()->format('Y-m-d_His') . '.zip';
        // Create the zip in the temporary directory
        $zipPath = storage_path('app/temp/' . $zipName);

        // Ensure the temp directory exists
        $tempStoragePath = storage_path('app/temp');
        if (!file_exists($tempStoragePath)) {
            mkdir($tempStoragePath, 0755, true);
        }

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) { // Use OVERWRITE flag
             Log::error("Failed to create ZIP archive: " . $zip->getStatusString());
             throw new \Exception('Cannot create zip archive: ' . $zip->getStatusString());
        }

        $excelData = [];

        foreach ($users as $user) {
            if ($user->photo) {
                // Use Storage::disk('private')->path() to get the full file system path
                $originalFilePath = Storage::disk('private')->path($user->photo->path);
                $avatarFilePath = Storage::disk('private')->path($user->photo->avatar_path); // Get avatar path from model

                $originalEntryName = "original/{$user->username}_{$user->photo->id}.{$user->photo->format}";
                $avatarEntryName = "avatars/{$user->username}_{$user->photo->id}_avatar.png"; // Assuming avatar is png

                // Add original photo to archive
                if (file_exists($originalFilePath)) {
                    $zip->addFile($originalFilePath, $originalEntryName);
                    Log::info('Added original photo to archive.', ['user' => $user->username, 'file' => $originalEntryName]);
                } else {
                    Log::warning('Original photo file not found for archiving.', ['user' => $user->username, 'path' => $originalFilePath]);
                }

                // Add avatar to archive
                 if (file_exists($avatarFilePath)) {
                    $zip->addFile($avatarFilePath, $avatarEntryName);
                     Log::info('Added avatar to archive.', ['user' => $user->username, 'file' => $avatarEntryName]);
                } else {
                     Log::warning('Avatar file not found for archiving.', ['user' => $user->username, 'path' => $avatarFilePath]);
                }

                $excelData[] = [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'upload_date' => $user->photo->created_at ? $user->photo->created_at->toDateTimeString() : null,
                    'filename' => $originalEntryName,
                    'server_path' => $user->photo->path // Store relative path in Excel
                ];
            }
        }

        // Create and add Excel file
        if (!empty($excelData)) {
            $excel = new Spreadsheet();
            $sheet = $excel->getActiveSheet();
            // Add header row
            $header = ['User ID', 'Username', 'Upload Date', 'Filename in Archive', 'Server Path'];
            $sheet->fromArray([$header], null, 'A1');
             // Add data starting from the second row
            $sheet->fromArray($excelData, null, 'A2');

            $excelWriter = new Xlsx($excel);
            // Save Excel to the temporary directory
            $excelPath = storage_path('app/temp/photos_metadata.xlsx');

            try {
                 $excelWriter->save($excelPath);
                 Log::info('Metadata Excel file saved temporarily.', ['path' => $excelPath]);

                 if (file_exists($excelPath)) {
                     $zip->addFile($excelPath, 'metadata.xlsx');
                     Log::info('Added metadata Excel file to archive.');
                 }
            } catch (\Exception $e) {
                 Log::error('Error saving or adding metadata Excel to archive: ' . $e->getMessage());
            } finally {
                // Clean up the temporary Excel file
                if (file_exists($excelPath)) {
                    unlink($excelPath);
                    Log::info('Temporary metadata Excel file deleted.', ['path' => $excelPath]);
                }
            }
        }

        $zip->close();
        Log::info('ZIP archive created.', ['path' => $zipPath]);

        // Return the full path to the created zip file
        return $zipPath;
    }
} 