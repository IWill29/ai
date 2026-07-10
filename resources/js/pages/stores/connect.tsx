import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, Lock, Shield } from 'lucide-react';
import { useCallback, useRef, useState } from 'react';
import ConnectStoreController from '@/actions/App/Http/Controllers/Stores/ConnectStoreController';
import ConnectOnboardingGuide from '@/components/stores/connect-onboarding-guide';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { connect, index } from '@/routes/stores';

export default function StoresConnect() {
    const credentialsRef = useRef<HTMLDivElement>(null);
    const [credentialsFocused, setCredentialsFocused] = useState(false);

    const focusCredentials = useCallback(() => {
        setCredentialsFocused(true);
        credentialsRef.current?.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
    }, []);

    return (
        <>
            <Head title="Connect Shopify Store" />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 md:gap-8 md:p-6">
                <Button variant="ghost" size="sm" asChild className="w-fit rounded-xl">
                    <Link href={index()}>
                        <ArrowLeft className="mr-2 size-4" />
                        Back to stores
                    </Link>
                </Button>

                <div className="space-y-2">
                    <h1 className="text-2xl font-semibold tracking-tight text-foreground">
                        Connect your Shopify store
                    </h1>
                    <p className="max-w-xl text-sm leading-relaxed text-muted-foreground">
                        Link once — AgentStore mirrors orders, products, and customers so your AI
                        agent can help without touching live checkout.
                    </p>
                    <div className="flex flex-wrap gap-2 pt-1">
                        <span className="inline-flex items-center gap-1.5 rounded-full border border-border/60 bg-muted/40 px-3 py-1 text-xs text-muted-foreground">
                            <Shield className="size-3.5 text-emerald-600 dark:text-emerald-400" aria-hidden />
                            Encrypted at rest
                        </span>
                        <span className="inline-flex items-center gap-1.5 rounded-full border border-border/60 bg-muted/40 px-3 py-1 text-xs text-muted-foreground">
                            <Lock className="size-3.5" aria-hidden />
                            Validated before save
                        </span>
                        <span className="inline-flex items-center gap-1.5 rounded-full border border-border/60 bg-muted/40 px-3 py-1 text-xs text-muted-foreground">
                            ~5 min setup
                        </span>
                    </div>
                </div>

                <ConnectOnboardingGuide onReadyForCredentials={focusCredentials} />

                <Card
                    ref={credentialsRef}
                    id="connect-credentials"
                    className={cn(
                        'scroll-mt-6 rounded-2xl border-border/60 transition-[box-shadow,ring-color] duration-300 ease-out',
                        'shadow-[0_4px_20px_-2px_rgb(0_0_0/0.08)] dark:shadow-[0_8px_32px_-8px_rgb(0_0_0/0.55)]',
                        credentialsFocused && 'ring-2 ring-indigo-500/25',
                    )}
                >
                    <CardHeader>
                        <CardTitle className="text-lg">Your store credentials</CardTitle>
                        <CardDescription>
                            Paste what you copied from Shopify. We test the connection before
                            storing anything.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            {...ConnectStoreController.store.form()}
                            options={{ preserveScroll: true }}
                            resetOnSuccess={['access_token', 'api_secret']}
                            className="space-y-5"
                        >
                            {({ processing, errors, wasSuccessful }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="domain">Store URL</Label>
                                        <Input
                                            id="domain"
                                            name="domain"
                                            placeholder="myshop.myshopify.com"
                                            autoComplete="off"
                                            className="rounded-xl"
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            The address you use to sign in to Shopify admin.
                                        </p>
                                        <InputError message={errors.domain} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="name">
                                            Friendly name{' '}
                                            <span className="font-normal text-muted-foreground">
                                                (optional)
                                            </span>
                                        </Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            placeholder="My Shopify store"
                                            className="rounded-xl"
                                        />
                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="access_token">Admin API access token</Label>
                                        <PasswordInput
                                            id="access_token"
                                            name="access_token"
                                            placeholder="shpat_..."
                                            autoComplete="off"
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            From your custom app → API credentials.
                                        </p>
                                        <InputError message={errors.access_token} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="api_secret">API secret key</Label>
                                        <PasswordInput
                                            id="api_secret"
                                            name="api_secret"
                                            placeholder="From the same credentials page"
                                            autoComplete="off"
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            Keeps webhooks secure — never shared in chat.
                                        </p>
                                        <InputError message={errors.api_secret} />
                                    </div>

                                    {wasSuccessful && (
                                        <div className="flex items-center gap-2 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-300">
                                            <CheckCircle2 className="size-4 shrink-0" />
                                            Store connected successfully.
                                        </div>
                                    )}

                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        variant="brand"
                                        className="w-full rounded-xl sm:w-auto sm:px-8"
                                    >
                                        {processing ? 'Checking connection…' : 'Connect store'}
                                    </Button>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

StoresConnect.layout = {
    breadcrumbs: [
        { title: 'Stores', href: index() },
        { title: 'Connect', href: connect() },
    ],
};
