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

class FileService
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

        try {
            Log::info('Attempting to upload file.', [
                'originalName' => $file->getClientOriginalName(),
                'extension' => $file->getClientOriginalExtension(),
                'size' => $file->getSize(),
                'mimeType' => $file->getMimeType(),
                'userId' => $user->id,
            ]);
    
            $this->validateFile($file);
    
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $uniqueName = Str::uuid() . '.' . $extension;
            
            // Store original file
            $originalPath = Storage::disk('private')->putFileAs('photos/original', $file, $uniqueName);
            Log::info('Original file stored for user '. $user->id, ['path' => $originalPath]);
            
            // Create and store avatar
            $avatar = Image::make($file);
            $avatar->fit(self::AVATAR_SIZE, self::AVATAR_SIZE);
            
            $avatarFileName = Str::uuid() . '.' . $avatar->getClientOriginalExtension(); // Use avatar extension if different
            $avatarPath = 'photos/avatars/' . $avatarFileName;
            Storage::disk('private')->put($avatarPath, $avatar->encode()); // Encode as PNG for consistency
            Log::info('Avatar created and stored for user '. $user->id, ['path' => $avatarPath]);
    
            // Create file record
            $fileRecord = File::create([
                'name' => $originalName,
                'description' => $description ?? 'User profile photo',
                'format' => $extension,
                'size' => $file->getSize(),
                'path' => $originalPath,
                'original_name' => $originalName,
                'mime_type' => $file->getMimeType(),
            ]);
            Log::info('File record created successfully for user '. $user->id, ['file_id' => $fileRecord->id]);
    
            // Update user's photo
            $user->update(['photo_id' => $fileRecord->id]);
            Log::info('User photo_id updated successfully for user '. $user->id, ['user_id' => $user->id, 'photo_id' => $fileRecord->id]);

            DB::commit();
    
            return $fileRecord;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('File upload transaction failed for user '. $user->id, ['error' => $e->getMessage(), 'exception' => $e]);
            // Clean up stored files if transaction fails
            if (isset($originalPath)) { Storage::disk('private')->delete($originalPath); }
            if (isset($avatarPath)) { Storage::disk('private')->delete($avatarPath); }
            throw $e;
        }
    }

    public function deleteUserPhoto(User $user): void
    {
        DB::beginTransaction();

        try {
            if ($user->photo) {
                $originalPath = $user->photo->path;
                $avatarPath = $this->getAvatarPath($user->photo);

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
            Log::error('File deletion transaction failed for user '. $user->id, ['error' => $e->getMessage(), 'exception' => $e]);
            throw $e;
        }
    }

    public function downloadOriginalPhoto(File $file)
    {
        return Storage::disk('private')->get($file->path);
    }

    public function getAvatarPath(File $file): string
    {
        // Assuming avatar path structure is photos/avatars/{uuid}.png
        // We need to find the corresponding avatar file based on the original file record
        // This might require storing the avatar path in the 'files' table or deriving it.
        // For now, let's assume avatar path is derivable.
        
        // A better approach would be to store avatar file info in the DB as well, or link it.
        // Given the current structure, deriving is prone to errors if the avatar naming changes.

        // Let's refine this based on the upload logic: we generate a new UUID for the avatar.
        // This means we cannot simply derive the avatar path from the original file's path/name.
        // The most robust solution is to store the avatar file info in the database,
        // perhaps in a separate 'avatars' table or linked from the 'files' table.

        // **Revisiting the upload logic:** The avatar filename is derived from the original unique filename.
        // Let's make the avatar path derivable again based on the unique original filename.
        $pathInfo = pathinfo($file->path);
        $avatarFileName = $pathInfo['filename'] . '_avatar.' . 'png'; // Assuming avatar is always png
        return 'photos/avatars/' . $avatarFileName;
    }

    private function validateFile(UploadedFile $file): void
    {
        if ($file->getSize() > $this->maxFileSize) {
            throw new \Exception('File size (' . $file->getSize() . ' bytes) exceeds maximum allowed size (' . $this->maxFileSize . ' bytes)');
        }

        $image = Image::make($file);
        $width = $image->width();
        $height = $image->height();
        
        if (strpos($this->allowedAspectRatio, ':') === false) {
             // Handle case where aspect ratio is just a single number (e.g., 1 for 1:1)
             $expectedRatio = (float) $this->allowedAspectRatio;
        } else {
            list($ratioWidth, $ratioHeight) = explode(':', $this->allowedAspectRatio);
            if ($ratioHeight == 0) {
                 throw new \Exception('Invalid aspect ratio configuration');
            }
            $expectedRatio = (float) $ratioWidth / (float) $ratioHeight;
        }
       
        $actualRatio = $width / $height;

        if (abs($actualRatio - $expectedRatio) > 0.01) {
            throw new \Exception('Image aspect ratio (' . round($actualRatio, 2) . ') does not match required ratio (' . round($expectedRatio, 2) . ')');
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

        if ($zip->open($zipPath, \ZipArchive::CREATE) !== TRUE) {
             throw new \Exception('Cannot create zip archive: ' . $zipPath);
        }

        // Add photos
        $excelData = [];

        foreach ($users as $user) {
            if ($user->photo) {
                $originalPath = Storage::disk('private')->path($user->photo->path);
                $avatarPath = Storage::disk('private')->path($this->getAvatarPath($user->photo));
                
                $originalEntryName = "original/{$user->username}_{$user->photo->id}.{$user->photo->format}";
                $avatarEntryName = "avatars/{$user->username}_{$user->photo->id}_avatar.{$user->photo->format}";

                // Add original photo
                if (file_exists($originalPath)) {
                    $zip->addFile($originalPath, $originalEntryName);
                    Log::info('Added original photo to archive.', ['user' => $user->username, 'file' => $originalEntryName]);
                } else {
                    Log::warning('Original photo file not found for archiving.', ['user' => $user->username, 'path' => $originalPath]);
                }
                
                // Add avatar
                 if (file_exists($avatarPath)) {
                    $zip->addFile($avatarPath, $avatarEntryName);
                     Log::info('Added avatar to archive.', ['user' => $user->username, 'file' => $avatarEntryName]);
                } else {
                     Log::warning('Avatar file not found for archiving.', ['user' => $user->username, 'path' => $avatarPath]);
                }

                $excelData[] = [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'upload_date' => $user->photo->created_at ? $user->photo->created_at->toDateTimeString() : null,
                    'filename' => $originalEntryName,
                    'server_path' => $user->photo->path
                ];
            }
        }

        // Create and add Excel file
        if (!empty($excelData)) {
             $excel = new Spreadsheet();
            $sheet = $excel->getActiveSheet();
            // Add header row manually
            $header = ['User ID', 'Username', 'Upload Date', 'Filename in Archive', 'Server Path'];
            $sheet->fromArray([$header], null, 'A1');
             // Add data starting from the second row
            $sheet->fromArray($excelData, null, 'A2');
    
            $excelWriter = new Xlsx($excel);
            $excelPath = storage_path('app/temp/photos_metadata.xlsx');
            $excelWriter->save($excelPath);
            
            $zip->addFile($excelPath, 'metadata.xlsx');
            Log::info('Added metadata Excel file to archive.', ['path' => $excelPath]);
            
            unlink($excelPath);
        } else {
             Log::info('No photos with users found, skipping Excel file creation for archive.');
        }

        $zip->close();
        Log::info('Zip archive created.', ['path' => $zipPath]);
        
        return $zipPath;
    }
} 