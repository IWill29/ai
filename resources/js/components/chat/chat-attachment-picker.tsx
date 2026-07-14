import { Paperclip, X } from 'lucide-react';
import { useRef } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { uploadAttachment } from '@/lib/chat-api';
import type { PendingAttachment } from '@/types/chat';

const MAX_FILES = 5;
const MAX_BYTES = 5 * 1024 * 1024;
const ACCEPTED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

type PreviewProps = Readonly<{
    items: PendingAttachment[];
    onRemove: (id: string) => void;
}>;

export function ChatAttachmentPreview({ items, onRemove }: PreviewProps) {
    if (items.length === 0) {
        return null;
    }

    return (
        <div className="flex flex-wrap gap-2 px-3 pt-3">
            {items.map((item) => (
                <div
                    key={item.id}
                    className="relative h-14 w-14 overflow-hidden rounded-lg border border-border/60"
                >
                    <img
                        src={item.previewUrl}
                        alt={item.filename}
                        className="h-full w-full object-cover"
                    />
                    <button
                        type="button"
                        onClick={() => onRemove(item.id)}
                        className="absolute right-0.5 top-0.5 rounded-full bg-background/80 p-0.5 active:scale-[0.97]"
                        aria-label={`Remove ${item.filename}`}
                    >
                        <X className="size-3" aria-hidden />
                    </button>
                </div>
            ))}
        </div>
    );
}

type PickerProps = Readonly<{
    attachments: PendingAttachment[];
    onAdd: (attachment: PendingAttachment) => void;
    disabled?: boolean;
}>;

export default function ChatAttachmentPicker({
    attachments,
    onAdd,
    disabled = false,
}: PickerProps) {
    const inputRef = useRef<HTMLInputElement>(null);

    const uploadFiles = async (files: File[]) => {
        const remaining = MAX_FILES - attachments.length;

        if (remaining <= 0) {
            toast.error('You can attach up to 5 images.');

            return;
        }

        for (const file of files.slice(0, remaining)) {
            if (!ACCEPTED_TYPES.includes(file.type)) {
                toast.error(`${file.name}: use JPEG, PNG, or WebP.`);

                continue;
            }

            if (file.size > MAX_BYTES) {
                toast.error(`${file.name} is larger than 5 MB.`);

                continue;
            }

            try {
                const uploaded = await uploadAttachment(file);
                onAdd({
                    id: uploaded.id,
                    previewUrl: uploaded.previewUrl,
                    filename: uploaded.filename,
                });
            } catch {
                toast.error(`Could not upload ${file.name}.`);
            }
        }
    };

    return (
        <>
            <input
                ref={inputRef}
                type="file"
                accept="image/jpeg,image/png,image/webp"
                multiple
                className="hidden"
                onChange={(event) => {
                    void uploadFiles(Array.from(event.target.files ?? []));
                    event.target.value = '';
                }}
            />
            <Button
                type="button"
                size="icon"
                variant="ghost"
                disabled={disabled || attachments.length >= MAX_FILES}
                onClick={() => inputRef.current?.click()}
                aria-label="Attach product images"
                className="size-8 rounded-xl active:scale-[0.97] motion-reduce:active:scale-100 transition-transform duration-150"
                style={{ transitionTimingFunction: 'var(--ease-out-strong)' }}
            >
                <Paperclip className="size-4" aria-hidden />
            </Button>
        </>
    );
}

export async function handleAttachmentDrop(
    files: FileList | File[],
    attachments: PendingAttachment[],
    onAdd: (attachment: PendingAttachment) => void,
): Promise<void> {
    const remaining = MAX_FILES - attachments.length;

    for (const file of Array.from(files).slice(0, remaining)) {
        if (!ACCEPTED_TYPES.includes(file.type)) {
            toast.error(`${file.name}: use JPEG, PNG, or WebP.`);

            continue;
        }

        if (file.size > MAX_BYTES) {
            toast.error(`${file.name} is larger than 5 MB.`);

            continue;
        }

        try {
            const uploaded = await uploadAttachment(file);
            onAdd({
                id: uploaded.id,
                previewUrl: uploaded.previewUrl,
                filename: uploaded.filename,
            });
        } catch {
            toast.error(`Could not upload ${file.name}.`);
        }
    }
}
