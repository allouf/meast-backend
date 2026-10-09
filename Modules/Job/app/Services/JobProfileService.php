<?php

namespace Modules\Job\Services;

use App\Utilities\FileManager;
use Illuminate\Http\UploadedFile;
use Modules\Core\Models\User;
use Modules\Job\Models\JobProfile;
use Symfony\Component\HttpKernel\Exception\HttpException;

class JobProfileService
{
    private const AVATAR_DIR = 'job_profiles/avatar';
    private const COVER_DIR = 'job_profiles/cover';
    private const GALLERY_DIR = 'job_profiles/gallery';

    public function getOrCreateForUser(User $user): JobProfile
    {
        return JobProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['user_id' => $user->id]
        );
    }

    public function updateProfile(JobProfile $profile, array $data): JobProfile
    {
        $allowed = ['name', 'bio'];
        $profile->update(array_intersect_key($data, array_flip($allowed)));
        return $profile->fresh();
    }

    public function uploadAvatar(JobProfile $profile, UploadedFile $file): JobProfile
    {
        if ($profile->avatar) {
            FileManager::delete($profile->avatar);
        }
        $path = FileManager::upload(self::AVATAR_DIR, $file);
        $profile->update(['avatar' => $path]);
        return $profile->fresh();
    }

    public function uploadCover(JobProfile $profile, UploadedFile $file): JobProfile
    {
        if ($profile->cover) {
            FileManager::delete($profile->cover);
        }
        $path = FileManager::upload(self::COVER_DIR, $file);
        $profile->update(['cover' => $path]);
        return $profile->fresh();
    }

    public function addGalleryImage(JobProfile $profile, UploadedFile $file): JobProfile
    {
        return $this->addGalleryImages($profile, [$file]);
    }

    /** @param  array<UploadedFile>  $files */
    public function addGalleryImages(JobProfile $profile, array $files): JobProfile
    {
        $gallery = $profile->gallery ?? [];
        foreach ($files as $f) {
            $gallery[] = FileManager::upload(self::GALLERY_DIR, $f);
        }
        if (! empty($files)) {
            $profile->update(['gallery' => $gallery]);
        }
        return $profile->fresh();
    }

    public function removeGalleryImage(JobProfile $profile, int $index): JobProfile
    {
        $gallery = $profile->gallery ?? [];
        if (! isset($gallery[$index])) {
            throw new HttpException(404, __('job::messages.company_profile.gallery_image_not_found'));
        }
        FileManager::delete($gallery[$index]);
        array_splice($gallery, $index, 1);
        $profile->update(['gallery' => $gallery]);
        return $profile->fresh();
    }
}
