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

class FileService
{
    private const AVATAR_SIZE = 128;

    public function uploadUserPhoto(User $user, UploadedFile $file): File
    {
        Log::info('Attempting to upload file.', [
            'originalName' => $file->getClientOriginalName(),
            'extension' => $file->getClientOriginalExtension(),
            'size' => $file->getSize(),
            'mimeType' => $file->getMimeType(),
            'userId' => $user->id,
        ]);

        try {
            $this->validateFile($file);
        } catch (\Exception $e) {
            Log::error('File validation failed for user '. $user->id, ['error' => $e->getMessage()]);
            throw $e;
        }

        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $uniqueName = Str::uuid() . '.' . $extension;
        
        // Store original file
        try {
            $path = $file->storeAs('photos/original', $uniqueName, 'private');
            Log::info('Original file stored for user '. $user->id, ['path' => $path]);
        } catch (\Exception $e) {
            Log::error('Error storing original file for user '. $user->id, ['error' => $e->getMessage()]);
            throw new \Exception('Error storing original file.', 0, $e);
        }
        
        // Create and store avatar
        try {
            $avatar = Image::make($file);
            $avatar->fit(self::AVATAR_SIZE, self::AVATAR_SIZE);
            $avatarData = $avatar->encode('png');
            
            $avatarPath = 'photos/avatars/' . Str::uuid() . '.png';
            Storage::disk('private')->put($avatarPath, $avatarData);
            Log::info('Avatar created and stored for user '. $user->id, ['path' => $avatarPath]);
        } catch (\Exception $e) {
            Log::error('Error creating or storing avatar for user '. $user->id, ['error' => $e->getMessage()]);
            // Clean up original file if avatar creation fails
            Storage::disk('private')->delete($path);
            throw new \Exception('Error processing image.', 0, $e);
        }

        // Create file record
        try {
            Log::info('Attempting to create file record for user '. $user->id, [
                'name' => $originalName,
                'description' => 'User profile photo',
                'format' => $extension,
                'size' => $file->getSize(),
                'path' => $path,
            ]);
            $fileRecord = File::create([
                'name' => $originalName,
                'description' => 'User profile photo',
                'format' => $extension,
                'size' => $file->getSize(),
                'path' => $path,
            ]);
            Log::info('File record created successfully for user '. $user->id, ['file_id' => $fileRecord->id]);
        } catch (\Exception $e) {
            Log::error('Error creating file record for user '. $user->id, ['error' => $e->getMessage()]);
            // Clean up stored files if database record creation fails
            Storage::disk('private')->delete($path);
            Storage::disk('private')->delete($avatarPath);
            throw new \Exception('Error saving file information.', 0, $e);
        }

        // Update user's photo
        try {
            Log::info('Attempting to update user photo_id for user '. $user->id, ['file_id' => $fileRecord->id]);
            $user->update(['photo_id' => $fileRecord->id]);
            Log::info('User photo_id updated successfully for user '. $user->id, ['user_id' => $user->id, 'photo_id' => $fileRecord->id]);
        } catch (\Exception $e) {
            Log::error('Error updating user photo_id for user '. $user->id, ['user_id' => $user->id, 'file_id' => $fileRecord->id, 'error' => $e->getMessage()]);
            // Decide if you want to delete the file record and stored files here too
            // For now, we'll leave them for debugging, but in production, you might want to clean up
            throw new \Exception('Error linking photo to user.', 0, $e);
        }

        return $fileRecord;
    }

    public function deleteUserPhoto(User $user): void
    {
        if ($user->photo) {
            Storage::disk('private')->delete($user->photo->path);
            Storage::disk('private')->delete(str_replace('original', 'avatars', $user->photo->path));
            $user->photo->delete();
            $user->update(['photo_id' => null]);
        }
    }

    public function downloadOriginalPhoto(File $file)
    {
        return Storage::disk('private')->get($file->path);
    }

    public function getAvatarPath(File $file): string
    {
        return str_replace('original', 'avatars', $file->path);
    }

    private function validateFile(UploadedFile $file): void
    {
        $maxFileSize = env('MAX_FILE_SIZE', 5242880); // 5MB default
        $allowedAspectRatio = env('ALLOWED_ASPECT_RATIO', 1); // 1:1 default

        if ($file->getSize() > $maxFileSize) {
            throw new \Exception('File size exceeds the maximum allowed size of ' . ($maxFileSize / 1024 / 1024) . 'MB');
        }

        $image = Image::make($file);
        $width = $image->width();
        $height = $image->height();
        
        if (abs($width / $height - $allowedAspectRatio) > 0.01) {
            throw new \Exception('Image aspect ratio must be ' . $allowedAspectRatio . ':1');
        }
    }

    public function createPhotosArchive()
    {
        $users = User::with('photo')->whereNotNull('photo_id')->get();
        $zip = new \ZipArchive();
        $zipName = 'photos_' . now()->format('Y-m-d_His') . '.zip';
        $zipPath = storage_path('app/temp/' . $zipName);
        
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip->open($zipPath, \ZipArchive::CREATE);

        // Add photos to zip
        foreach ($users as $user) {
            if ($user->photo) {
                $originalPath = storage_path('app/private/' . $user->photo->path);
                $avatarPath = storage_path('app/private/' . $this->getAvatarPath($user->photo));
                
                if (file_exists($originalPath)) {
                    $zip->addFile($originalPath, "original/{$user->name}_{$user->photo->id}.{$user->photo->format}");
                }
                
                if (file_exists($avatarPath)) {
                    $zip->addFile($avatarPath, "avatars/{$user->name}_{$user->photo->id}_avatar.{$user->photo->format}");
                }
            }
        }

        // Create Excel file with metadata
        $excelData = $users->map(function ($user) {
            return [
                'user_id' => $user->id,
                'username' => $user->name,
                'upload_date' => $user->photo->created_at,
                'filename' => "{$user->name}_{$user->photo->id}.{$user->photo->format}",
                'server_path' => $user->photo->path
            ];
        });

        $excel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $excel->getActiveSheet();
        $sheet->fromArray($excelData->toArray(), null, 'A1');
        $excelWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($excel);
        $excelPath = storage_path('app/temp/photos_metadata.xlsx');
        $excelWriter->save($excelPath);
        
        $zip->addFile($excelPath, 'metadata.xlsx');
        $zip->close();
        
        unlink($excelPath);
        
        return $zipPath;
    }
} 