<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Domains\Stores\Enums\Platform;
use App\Domains\Stores\Models\StoreConnection;
use App\Domains\Stores\Models\StoreCredential;
use App\Jobs\DeleteAccountJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\SeedsPlans;
use Tests\TestCase;

final class AuditTrailTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPlans;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPlans();
    }

    public function test_store_disconnect_is_audited(): void
    {
        $user = User::factory()->create();
        $connection = $this->createConnection($user);

        $this->actingAs($user)
            ->delete(route('stores.destroy', $connection))
            ->assertRedirect(route('stores.index'));

        $this->assertDatabaseHas('audit_logs', [
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'action' => 'store.disconnect',
        ]);
    }

    public function test_password_update_is_audited(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('security.edit'))
            ->put(route('user-password.update'), [
                'current_password' => 'password',
                'password' => 'new-password-12',
                'password_confirmation' => 'new-password-12',
            ])
            ->assertRedirect(route('security.edit'));

        $this->assertDatabaseHas('audit_logs', [
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'action' => 'password.update',
        ]);
    }

    public function test_account_delete_records_audit_log(): void
    {
        Bus::fake([DeleteAccountJob::class]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertRedirect(route('home'));

        $this->assertDatabaseHas('audit_logs', [
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'action' => 'account.delete',
        ]);
    }

    public function test_attachment_upload_is_audited(): void
    {
        Storage::fake('attachments');

        $user = User::factory()->create();
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
            'photo.jpg',
            "\xFF\xD8\xFF\xE0".str_repeat("\x00", 32),
        );

        $this->actingAs($user)
            ->post(route('attachments.store'), ['file' => $file])
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'action' => 'attachment.upload',
        ]);
    }

    public function test_openrouter_key_save_is_audited(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/key' => Http::response(['data' => ['label' => 'test']], 200),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('settings.openrouter'))
            ->post(route('settings.openrouter.store'), [
                'api_key' => 'sk-or-v1-test-key-abcdefghij',
                'default_model' => null,
            ])
            ->assertRedirect(route('settings.openrouter'));

        $this->assertDatabaseHas('audit_logs', [
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'action' => 'openrouter.key.save',
        ]);
    }

    private function createConnection(User $user): StoreConnection
    {
        $connection = StoreConnection::query()->create([
            'account_id' => $user->account_id,
            'platform' => Platform::Shopify->value,
            'name' => 'Demo',
            'domain' => 'demo.myshopify.com',
            'status' => 'active',
        ]);

        StoreCredential::query()->create([
            'store_connection_id' => $connection->id,
            'access_token' => 'shpat_test_token_1234567890',
        ]);

        return $connection;
    }
}
