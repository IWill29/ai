<?php

declare(strict_types=1);

namespace App\Http\Controllers\Chat;

use App\Domains\Accounts\Models\OpenRouterCredential;
use App\Domains\AI\Contracts\AgentService;
use App\Domains\AI\Exceptions\AgentException;
use App\Domains\Billing\Exceptions\MessageLimitReachedException;
use App\Domains\Chat\Contracts\ChatService;
use App\Domains\Chat\Models\Conversation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StreamMessageRequest;
use App\Http\Support\SseEmitter;
use App\Support\SensitiveData;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class AgentStreamController extends Controller
{
    public function store(
        StreamMessageRequest $request,
        Conversation $conversation,
        AgentService $agent,
        ChatService $chat,
    ): StreamedResponse {
        $this->authorize('update', $conversation);

        $credential = OpenRouterCredential::query()
            ->where('account_id', $conversation->account_id)
            ->first();

        if ($credential === null || $credential->validated_at === null) {
            abort(403, 'Add your OpenRouter API key before using the agent.');
        }

        if ($request->filled('model')) {
            $chat->updateConversationModel($conversation->id, $request->string('model')->value());
        }

        return response()->stream(function () use ($request, $conversation, $agent): void {
            $emitter = new SseEmitter;

            try {
                $agent->runWithStream(
                    conversationId: $conversation->id,
                    userMessage: $request->string('message')->value(),
                    emit: fn (string $event, array $data) => $emitter->emit($event, $data),
                    attachmentIds: $request->input('attachment_ids', []),
                );
            } catch (MessageLimitReachedException|AgentException $e) {
                $emitter->emit('error', [
                    'message' => SensitiveData::sanitizeMessage($e->getMessage()),
                    'code' => class_basename($e),
                ]);
            } catch (Throwable $e) {
                $emitter->emit('error', ['message' => 'Agent run failed.', 'code' => 'agent_error']);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function resume(Conversation $conversation, AgentService $agent): StreamedResponse
    {
        $this->authorize('update', $conversation);

        return response()->stream(function () use ($conversation, $agent): void {
            $emitter = new SseEmitter;

            try {
                $agent->resumeWithStream(
                    conversationId: $conversation->id,
                    emit: fn (string $event, array $data) => $emitter->emit($event, $data),
                );
            } catch (Throwable $e) {
                $emitter->emit('error', ['message' => 'Agent resume failed.', 'code' => 'agent_resume_error']);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
