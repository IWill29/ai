// Components
import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

const VERIFICATION_LINK_SENT = 'verification-link-sent';

type SharedProps = {
    flash?: {
        error?: string;
        status?: string;
    };
};

type PageWithFlash = SharedProps & {
    flash?: {
        status?: string;
    };
};

type Props = {
    status?: string;
};

function VerificationEmailSentNotice() {
    return (
        <div className="mb-4 space-y-1 rounded-xl border border-emerald-500/25 bg-emerald-500/10 px-4 py-3 text-center text-sm text-emerald-700 dark:text-emerald-300">
            <p className="font-medium">We sent a verification link to your email address.</p>
            <p>Check your inbox and click the link to activate your account.</p>
        </div>
    );
}

export default function VerifyEmail({ status }: Props) {
    const page = usePage<SharedProps>();
    const sessionFlash = page.props.flash;
    const inertiaFlash = (page as PageWithFlash).flash;
    const [resent, setResent] = useState(false);
    const [processing, setProcessing] = useState(false);

    useEffect(() => {
        return router.on('flash', (event) => {
            const flash = (event as CustomEvent).detail?.flash as { status?: string } | undefined;

            if (flash?.status === VERIFICATION_LINK_SENT) {
                setResent(true);
            }
        });
    }, []);

    const linkSent =
        status === VERIFICATION_LINK_SENT ||
        sessionFlash?.status === VERIFICATION_LINK_SENT ||
        inertiaFlash?.status === VERIFICATION_LINK_SENT ||
        resent;

    const resendVerificationEmail = () => {
        setProcessing(true);

        router.post(
            send.url(),
            {},
            {
                preserveScroll: true,
                preserveState: false,
                onSuccess: () => setResent(true),
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <>
            <Head title="Email verification" />

            {sessionFlash?.error && !linkSent && (
                <div className="mb-4 space-y-2 rounded-xl border border-destructive/25 bg-destructive/5 px-4 py-3 text-left text-sm text-destructive">
                    <p className="font-medium">{sessionFlash.error}</p>
                    <ol className="list-decimal space-y-1 pl-4 text-destructive/90">
                        <li>
                            Click <span className="font-medium">Resend verification email</span> below.
                        </li>
                        <li>Open the newest email in your inbox.</li>
                        <li>
                            Use the link while logged in here — same browser session,{' '}
                            <span className="font-medium">http://127.0.0.1:8000</span> (not localhost).
                        </li>
                    </ol>
                </div>
            )}

            {linkSent && <VerificationEmailSentNotice />}

            <div className="space-y-6 text-center">
                <Button
                    disabled={processing}
                    variant={sessionFlash?.error && !linkSent ? 'default' : 'secondary'}
                    type="button"
                    onClick={resendVerificationEmail}
                >
                    {processing && <Spinner />}
                    Resend verification email
                </Button>

                <TextLink href={logout()} className="mx-auto block text-sm">
                    Log out
                </TextLink>
            </div>
        </>
    );
}

VerifyEmail.layout = {
    title: 'Email verification',
    description: 'Confirm your email address to access your workspace.',
};
