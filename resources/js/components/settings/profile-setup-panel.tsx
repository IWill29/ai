import { Form, router, usePage } from '@inertiajs/react';
import { Mail, ShieldCheck, UserRound } from 'lucide-react';
import { useEffect, useState } from 'react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { send } from '@/routes/verification';
import type { Auth } from '@/types';

const cardClass =
    'rounded-2xl border-border/60 bg-card shadow-[0_4px_20px_-2px_rgb(0_0_0/0.08)] dark:border-border/80 dark:shadow-[0_8px_32px_-8px_rgb(0_0_0/0.55)]';

type PageProps = {
    auth: Auth;
    flash?: {
        status?: string;
    };
};

type Props = Readonly<{
    mustVerifyEmail: boolean;
    status?: string;
}>;

export default function ProfileSetupPanel({ mustVerifyEmail, status }: Props) {
    const page = usePage<PageProps>();
    const { auth } = page.props;
    const sessionFlash = page.props.flash;
    const inertiaFlash = (page as PageProps & { flash?: { status?: string } }).flash;
    const [resent, setResent] = useState(false);
    const emailUnverified = mustVerifyEmail && auth.user.email_verified_at === null;
    const verificationLinkSent =
        status === 'verification-link-sent' ||
        sessionFlash?.status === 'verification-link-sent' ||
        inertiaFlash?.status === 'verification-link-sent' ||
        resent;

    useEffect(() => {
        return router.on('flash', (event) => {
            const flash = (event as CustomEvent).detail?.flash as { status?: string } | undefined;

            if (flash?.status === 'verification-link-sent') {
                setResent(true);
            }
        });
    }, []);

    const resendVerificationEmail = () => {
        router.post(
            send.url(),
            {},
            {
                preserveScroll: true,
                preserveState: false,
                onSuccess: () => setResent(true),
            },
        );
    };

    return (
        <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 md:gap-8 md:p-6">
            <div className="space-y-3">
                <p className="max-w-xl text-sm leading-relaxed text-muted-foreground">
                    This is how you sign in and how AgentStore addresses you across the workspace.
                    Changes apply immediately after you save.
                </p>
                <div className="flex flex-wrap gap-2">
                    <span className="inline-flex items-center gap-1.5 rounded-full border border-border/60 bg-muted/40 px-3 py-1 text-xs text-muted-foreground">
                        <UserRound className="size-3.5 text-indigo-600 dark:text-indigo-400" aria-hidden />
                        Shown in your workspace
                    </span>
                    <span className="inline-flex items-center gap-1.5 rounded-full border border-border/60 bg-muted/40 px-3 py-1 text-xs text-muted-foreground">
                        <Mail className="size-3.5" aria-hidden />
                        Used to sign in
                    </span>
                    <span className="inline-flex items-center gap-1.5 rounded-full border border-border/60 bg-muted/40 px-3 py-1 text-xs text-muted-foreground">
                        <ShieldCheck className="size-3.5 text-emerald-600 dark:text-emerald-400" aria-hidden />
                        Verify email for full access
                    </span>
                </div>
            </div>

            <Card className={cn(cardClass, 'relative overflow-hidden')}>
                <div
                    aria-hidden
                    className="pointer-events-none absolute inset-x-0 top-0 h-24 bg-[radial-gradient(ellipse_at_top,rgba(99,102,241,0.1),transparent_70%)] dark:bg-[radial-gradient(ellipse_at_top,rgba(99,102,241,0.16),transparent_72%)]"
                />
                <CardHeader className="relative">
                    <div className="flex items-start gap-3">
                        <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-indigo-500/10">
                            <UserRound
                                className="size-5 text-indigo-600 dark:text-indigo-400"
                                strokeWidth={2}
                                aria-hidden
                            />
                        </div>
                        <div className="space-y-1">
                            <CardTitle className="text-lg">Profile details</CardTitle>
                            <CardDescription>
                                Your name and email for this workspace. Email changes may require
                                verification before you can continue.
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent className="relative">
                    <Form
                        {...ProfileController.update.form()}
                        options={{ preserveScroll: true }}
                        className="space-y-5"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>
                                    <Input
                                        id="name"
                                        className="rounded-xl"
                                        defaultValue={auth.user.name}
                                        name="name"
                                        required
                                        autoComplete="name"
                                        placeholder="Your name"
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        Appears in the sidebar and across your account.
                                    </p>
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email address</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        className="rounded-xl"
                                        defaultValue={auth.user.email}
                                        name="email"
                                        required
                                        autoComplete="username"
                                        placeholder="you@company.com"
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                {emailUnverified && (
                                    <div className="rounded-xl border border-amber-500/25 bg-amber-500/[0.06] p-4 text-sm">
                                        <p className="text-amber-950 dark:text-amber-100">
                                            Your email is not verified yet.{' '}
                                            <button
                                                type="button"
                                                onClick={resendVerificationEmail}
                                                className="font-medium underline decoration-amber-500/40 underline-offset-4 transition-colors duration-150 ease-out hover:decoration-current"
                                            >
                                                Resend verification email
                                            </button>
                                        </p>
                                        {verificationLinkSent && (
                                            <p className="mt-2 font-medium text-emerald-700 dark:text-emerald-300">
                                                A new verification link has been sent.
                                            </p>
                                        )}
                                    </div>
                                )}

                                <div className="flex flex-col gap-2 border-t border-border/50 pt-5 sm:flex-row sm:items-center sm:justify-between">
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        variant="brand"
                                        className="w-full rounded-full sm:w-auto sm:px-8"
                                        data-test="update-profile-button"
                                    >
                                        {processing ? 'Saving…' : 'Save changes'}
                                    </Button>
                                    <p className="text-center text-xs text-muted-foreground sm:text-right">
                                        Email updates may sign you out elsewhere
                                    </p>
                                </div>
                            </>
                        )}
                    </Form>
                </CardContent>
            </Card>

            <DeleteUser />
        </div>
    );
}
