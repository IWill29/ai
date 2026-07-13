<?php

declare(strict_types=1);

namespace App\Http\Controllers\Chat;

use App\Domains\Chat\Contracts\AttachmentUploadService;
use App\Domains\Chat\Models\MessageAttachment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StoreAttachmentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AttachmentController extends Controller
{
    public function store(StoreAttachmentRequest $request, AttachmentUploadService $attachments): JsonResponse
    {
        $user = $request->user();

        $attachment = $attachments->store(
            accountId: (string) $user->account_id,
            userId: (string) $user->id,
            file: $request->file('file'),
        );

        return response()->json(['attachment' => $attachment]);
    }

    public function preview(MessageAttachment $attachment): StreamedResponse
    {
        $this->authorize('view', $attachment);

        return response()->stream(function () use ($attachment): void {
            echo Storage::disk('local')->get($attachment->storage_path);
        }, 200, [
            'Content-Type' => $attachment->mime_type,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
