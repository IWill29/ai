import { Form } from '@inertiajs/react';
import { Fingerprint, Lock, Shield, ShieldCheck } from 'lucide-react';
import { useRef } from 'react';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import SecurityPasswordGuide from '@/components/settings/security-password-guide';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

const cardClass =
    'rounded-2xl border-border/60 bg-card shadow-[0_4px_20px_-2px_rgb(0_0_0/0.08)] dark:border-border/80 dark:shadow-[0_8px_32px_-8px_rgb(0_0_0/0.55)]';

type Props = Readonly<{
    passwordRules: string;
}>;

export default function SecurityPasswordPanel({ passwordRules }: Props) {
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    return (
        <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 md:gap-8 md:p-6">
            <div className="space-y-3">
                <p className="max-w-xl text-sm leading-relaxed text-muted-foreground">
                    Your password protects your store mirror, chat history, and API keys. Update
                    it anytime — we verify your current password before saving a new one.
                </p>
                <div className="flex flex-wrap gap-2">
                    <span className="inline-flex items-center gap-1.5 rounded-full border border-border/60 bg-muted/40 px-3 py-1 text-xs text-muted-foreground">
                        <Lock className="size-3.5 text-emerald-600 dark:text-emerald-400" aria-hidden />
                        Hashed, never stored plain
                    </span>
                    <span className="inline-flex items-center gap-1.5 rounded-full border border-border/60 bg-muted/40 px-3 py-1 text-xs text-muted-foreground">
                        <Shield className="size-3.5" aria-hidden />
                        Current password required
                    </span>
                    <span className="inline-flex items-center gap-1.5 rounded-full border border-border/60 bg-muted/40 px-3 py-1 text-xs text-muted-foreground">
                        <Fingerprint className="size-3.5" aria-hidden />
                        Stays signed in on this device
                    </span>
                </div>
            </div>

            <SecurityPasswordGuide />

            <Card className={cn(cardClass, 'relative overflow-hidden')}>
                <div
                    aria-hidden
                    className="pointer-events-none absolute inset-x-0 top-0 h-24 bg-[radial-gradient(ellipse_at_top,rgba(99,102,241,0.1),transparent_70%)] dark:bg-[radial-gradient(ellipse_at_top,rgba(99,102,241,0.16),transparent_72%)]"
                />
                <CardHeader className="relative">
                    <div className="flex items-start gap-3">
                        <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-indigo-500/10">
                            <ShieldCheck
                                className="size-5 text-indigo-600 dark:text-indigo-400"
                                strokeWidth={2}
                                aria-hidden
                            />
                        </div>
                        <div className="space-y-1">
                            <CardTitle className="text-lg">Update password</CardTitle>
                            <CardDescription>
                                Enter your current password, then choose a new one. You’ll stay
                                signed in here after saving.
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent className="relative">
                    <Form
                        {...SecurityController.update.form()}
                        options={{ preserveScroll: true }}
                        resetOnError={['password', 'password_confirmation', 'current_password']}
                        resetOnSuccess
                        onError={(errors) => {
                            if (errors.password) {
                                passwordInput.current?.focus();
                            }

                            if (errors.current_password) {
                                currentPasswordInput.current?.focus();
                            }
                        }}
                        className="space-y-5"
                    >
                        {({ errors, processing }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="current_password">Current password</Label>
                                    <PasswordInput
                                        id="current_password"
                                        ref={currentPasswordInput}
                                        name="current_password"
                                        className="rounded-xl"
                                        autoComplete="current-password"
                                        placeholder="Your current password"
                                    />
                                    <InputError message={errors.current_password} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="password">New password</Label>
                                    <PasswordInput
                                        id="password"
                                        ref={passwordInput}
                                        name="password"
                                        className="rounded-xl"
                                        autoComplete="new-password"
                                        placeholder="At least 12 characters"
                                        passwordrules={passwordRules}
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        Use a long passphrase you don’t use anywhere else.
                                    </p>
                                    <InputError message={errors.password} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="password_confirmation">Confirm new password</Label>
                                    <PasswordInput
                                        id="password_confirmation"
                                        name="password_confirmation"
                                        className="rounded-xl"
                                        autoComplete="new-password"
                                        placeholder="Repeat your new password"
                                        passwordrules={passwordRules}
                                    />
                                    <InputError message={errors.password_confirmation} />
                                </div>

                                <div className="flex flex-col gap-2 border-t border-border/50 pt-5 sm:flex-row sm:items-center sm:justify-between">
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        variant="brand"
                                        className="w-full rounded-full sm:w-auto sm:px-8"
                                        data-test="update-password-button"
                                    >
                                        {processing ? 'Saving…' : 'Update password'}
                                    </Button>
                                    <p className="text-center text-xs text-muted-foreground sm:text-right">
                                        Other sessions stay active until they sign out
                                    </p>
                                </div>
                            </>
                        )}
                    </Form>
                </CardContent>
            </Card>
        </div>
    );
}
