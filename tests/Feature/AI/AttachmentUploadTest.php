<?php

declare(strict_types=1);

namespace Tests\Feature\AI;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesAgentFixtures;
use Tests\TestCase;

class AttachmentUploadTest extends TestCase
{
    use CreatesAgentFixtures;
    use RefreshDatabase;

    public function test_uploads_attachment_for_account(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('product.jpg', 200, 200);

        $response = $this->actingAs($user)->post(route('attachments.store'), [
            'file' => $file,
        ]);

        $response->assertOk();
        $response->assertJsonPath('attachment.filename', 'product.jpg');
    }
}
