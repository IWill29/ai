<?php

declare(strict_types=1);

namespace App\Http\Controllers\Chat;

use App\Domains\Chat\Contracts\AttachmentUploadService;
use App\Domains\Chat\Models\MessageAttachment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StoreAttachmentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AttachmentController extends Controller
{
    public function store(StoreAttachmentRequest $request, AttachmentUploadService $attachments): JsonResponse
    {
        $this->authorize('create', MessageAttachment::class);

        $user = $request->user();

        try {
            $attachment = $attachments->store(
                accountId: (string) $user->account_id,
                userId: (string) $user->id,
                file: $request->file('file'),
            );
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'file' => $exception->getMessage(),
            ]);
        }

        return response()->json(['attachment' => $attachment]);
    }

    public function preview(MessageAttachment $attachment): StreamedResponse
    {
        $this->authorize('view', $attachment);

        return response()->stream(function () use ($attachment): void {
            echo Storage::disk((string) config('agent.attachment.disk', 'attachments'))
                ->get($attachment->storage_path);
        }, 200, [
            'Content-Type' => $attachment->mime_type,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
