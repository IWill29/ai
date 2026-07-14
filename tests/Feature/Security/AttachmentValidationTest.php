<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Domains\Chat\Models\MessageAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

final class AttachmentValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_file_with_mismatched_magic_bytes(): void
    {
        Storage::fake('attachments');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->createWithContent('photo.jpg', '<?php echo "x"; ?>');

        $this->actingAs($user)
            ->postJson(route('attachments.store'), ['file' => $file])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    public function test_stores_file_in_tenant_scoped_path(): void
    {
        Storage::fake('attachments');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->createWithContent('photo.jpg', $this->jpegBytes());

        $this->actingAs($user)
            ->post(route('attachments.store'), ['file' => $file])
            ->assertOk();

        $attachment = MessageAttachment::query()->firstOrFail();

        $this->assertStringStartsWith($user->account_id.'/', $attachment->storage_path);
        Storage::disk('attachments')->assertExists($attachment->storage_path);
    }

    public function test_rejects_more_than_five_pending_attachments(): void
    {
        Storage::fake('attachments');

        $user = User::factory()->create();

        for ($index = 0; $index < 5; $index++) {
            MessageAttachment::query()->create([
                'id' => (string) Str::uuid(),
                'account_id' => $user->account_id,
                'uploaded_by' => $user->id,
                'filename' => "pending-{$index}.jpg",
                'mime_type' => 'image/jpeg',
                'size_bytes' => 100,
                'storage_path' => "{$user->account_id}/pending-{$index}.jpg",
                'status' => 'pending',
                'expires_at' => now()->addHour(),
            ]);
        }

        $file = UploadedFile::fake()->createWithContent('overflow.jpg', $this->jpegBytes());

        $this->actingAs($user)
            ->postJson(route('attachments.store'), ['file' => $file])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    private function jpegBytes(): string
    {
        return "\xFF\xD8\xFF\xE0".str_repeat("\x00", 32);
    }
}
