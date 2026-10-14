<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Аватар: квадрат 256×256 по центру, JPEG. С телефона приходят фото по 5 МБ — храним маленькое.
 */
class Avatar
{
    public const SIZE = 256;

    public static function store(UploadedFile $file, User $user): string
    {
        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($source === false) {
            throw ValidationException::withMessages(['avatar' => 'Не получилось открыть картинку. Попробуйте JPG или PNG.']);
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $side = min($width, $height);

        $square = imagecreatetruecolor(self::SIZE, self::SIZE);
        imagefill($square, 0, 0, imagecolorallocate($square, 255, 255, 255));
        imagecopyresampled(
            $square, $source,
            0, 0, intdiv($width - $side, 2), intdiv($height - $side, 2),
            self::SIZE, self::SIZE, $side, $side,
        );

        ob_start();
        imagejpeg($square, null, 85);
        $jpeg = (string) ob_get_clean();

        $path = "avatars/{$user->id}-".Str::random(8).'.jpg';
        Storage::disk('public')->put($path, $jpeg);

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->forceFill(['avatar_path' => $path])->save();

        return $path;
    }

    public static function remove(User $user): void
    {
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->forceFill(['avatar_path' => null])->save();
        }
    }
}
