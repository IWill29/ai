import { Form } from '@inertiajs/react';
import { AlertTriangle, Trash2 } from 'lucide-react';
import { useRef } from 'react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

const cardClass =
    'rounded-2xl border-border/60 bg-card shadow-[0_4px_20px_-2px_rgb(0_0_0/0.08)] dark:border-border/80 dark:shadow-[0_8px_32px_-8px_rgb(0_0_0/0.55)]';

export default function DeleteUser() {
    const passwordInput = useRef<HTMLInputElement>(null);

    return (
        <Card
            className={cn(
                cardClass,
                'overflow-hidden border-destructive/25 bg-destructive/[0.03]',
            )}
        >
            <CardHeader className="pb-4">
                <div className="flex items-start gap-3">
                    <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-destructive/10">
                        <Trash2
                            className="size-5 text-destructive"
                            strokeWidth={2}
                            aria-hidden
                        />
                    </div>
                    <div className="space-y-1">
                        <CardTitle className="text-lg text-destructive">Delete account</CardTitle>
                        <CardDescription>
                            Permanently remove your workspace, connected stores, synced data, and
                            API keys. This cannot be undone.
                        </CardDescription>
                    </div>
                </div>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="flex gap-3 rounded-xl border border-destructive/20 bg-destructive/[0.06] p-4">
                    <AlertTriangle
                        className="mt-0.5 size-4 shrink-0 text-destructive"
                        aria-hidden
                    />
                    <div className="space-y-1 text-sm">
                        <p className="font-medium text-destructive">Before you continue</p>
                        <p className="leading-relaxed text-muted-foreground">
                            All store connections and mirrored data will be purged. Export anything
                            you need from Shopify first — we cannot restore your account later.
                        </p>
                    </div>
                </div>

                <Dialog>
                    <DialogTrigger asChild>
                        <Button
                            variant="destructive"
                            className="rounded-full"
                            data-test="delete-user-button"
                        >
                            Delete account
                        </Button>
                    </DialogTrigger>
                    <DialogContent className="rounded-2xl sm:max-w-md">
                        <DialogTitle>Delete your account?</DialogTitle>
                        <DialogDescription>
                            This permanently deletes your workspace and all synced store data.
                            Type your password to confirm.
                        </DialogDescription>

                        <Form
                            {...ProfileController.destroy.form()}
                            options={{ preserveScroll: true }}
                            onError={() => passwordInput.current?.focus()}
                            resetOnSuccess
                            className="space-y-5"
                        >
                            {({ resetAndClearErrors, processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="delete-password">Password</Label>
                                        <PasswordInput
                                            id="delete-password"
                                            name="password"
                                            ref={passwordInput}
                                            placeholder="Your current password"
                                            autoComplete="current-password"
                                            className="rounded-xl"
                                        />
                                        <InputError message={errors.password} />
                                    </div>

                                    <DialogFooter className="gap-2 sm:gap-0">
                                        <DialogClose asChild>
                                            <Button
                                                variant="outline"
                                                className="rounded-xl"
                                                onClick={() => resetAndClearErrors()}
                                            >
                                                Cancel
                                            </Button>
                                        </DialogClose>

                                        <Button
                                            variant="destructive"
                                            disabled={processing}
                                            className="rounded-xl"
                                            asChild
                                        >
                                            <button
                                                type="submit"
                                                data-test="confirm-delete-user-button"
                                            >
                                                {processing ? 'Deleting…' : 'Delete account'}
                                            </button>
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    </DialogContent>
                </Dialog>
            </CardContent>
        </Card>
    );
}
