<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\ImageFileValidator;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

final class ImageFileValidatorTest extends TestCase
{
    public function test_detects_jpeg_magic_bytes(): void
    {
        $file = UploadedFile::fake()->createWithContent('photo.jpg', $this->jpegBytes());

        $this->assertSame('image/jpeg', (new ImageFileValidator)->detectMime($file));
    }

    public function test_rejects_non_image_content(): void
    {
        $file = UploadedFile::fake()->createWithContent('evil.jpg', '<?php echo "x"; ?>');

        $this->assertNull((new ImageFileValidator)->detectMime($file));
    }

    private function jpegBytes(): string
    {
        return "\xFF\xD8\xFF\xE0".str_repeat("\x00", 32);
    }
}
