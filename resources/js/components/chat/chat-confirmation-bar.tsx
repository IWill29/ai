import { Button } from '@/components/ui/button';
import { toolLabel } from '@/lib/chat-tool-labels';
import type { ConfirmationPayload } from '@/types/chat';

type Props = Readonly<{
    payload: ConfirmationPayload;
    onConfirm: (actionStepId: string) => void;
    onCancel: (actionStepId: string) => void;
    isSubmitting?: boolean;
}>;

export default function ChatConfirmationBar({
    payload,
    onConfirm,
    onCancel,
    isSubmitting = false,
}: Props) {
    const descriptionPreview =
        payload.description_preview ??
        (payload.tool === 'update_product' && typeof payload.arguments.description === 'string'
            ? payload.arguments.description
            : undefined);

    return (
        <div
            className="mx-auto max-w-3xl px-4 pb-2 motion-safe:animate-in motion-safe:slide-in-from-bottom-4 motion-safe:duration-200"
            style={{ animationTimingFunction: 'var(--ease-out-strong)' }}
        >
            <div className="rounded-2xl border border-amber-500/30 bg-amber-500/5 p-4">
                <p className="text-sm font-medium">{payload.description}</p>

                {descriptionPreview && (
                    <p className="mt-2 line-clamp-3 text-xs text-muted-foreground">
                        New description: {descriptionPreview}
                    </p>
                )}

                {payload.image_previews && payload.image_previews.length > 0 && (
                    <div className="mt-2 flex flex-wrap gap-2">
                        {payload.image_previews.map((url) => (
                            <img
                                key={url}
                                src={url}
                                alt=""
                                className="h-12 w-12 rounded-lg border border-border/60 object-cover"
                            />
                        ))}
                    </div>
                )}

                <p className="mt-1 text-xs text-muted-foreground">
                    {toolLabel(payload.tool)} — review before confirming
                </p>

                <div className="mt-3 flex gap-2">
                    <Button
                        type="button"
                        disabled={isSubmitting}
                        onClick={() => onConfirm(payload.action_step_id)}
                        className="rounded-xl active:scale-[0.97] motion-reduce:active:scale-100 transition-transform duration-150"
                        style={{ transitionTimingFunction: 'var(--ease-out-strong)' }}
                    >
                        Confirm
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        disabled={isSubmitting}
                        onClick={() => onCancel(payload.action_step_id)}
                        className="rounded-xl active:scale-[0.97] motion-reduce:active:scale-100 transition-transform duration-150"
                        style={{ transitionTimingFunction: 'var(--ease-out-strong)' }}
                    >
                        Cancel
                    </Button>
                </div>
            </div>
        </div>
    );
}
