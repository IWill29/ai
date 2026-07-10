import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, KeyRound, Link2, ShieldCheck, Store } from 'lucide-react';
import ConnectStoreController from '@/actions/App/Http/Controllers/Stores/ConnectStoreController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { connect, index } from '@/routes/stores';

type Props = {
    scopes: string[];
};

const onboardingSteps = [
    {
        icon: Store,
        title: 'Open Shopify admin',
        description: 'Go to Settings → Apps and sales channels → Develop apps.',
    },
    {
        icon: ShieldCheck,
        title: 'Create a custom app',
        description: 'Name it "AgentStore" and configure Admin API access.',
    },
    {
        icon: KeyRound,
        title: 'Grant required scopes',
        description: 'Enable the scopes listed below so AgentStore can read and write store data.',
    },
    {
        icon: Link2,
        title: 'Install and copy credentials',
        description: 'Install the app, then copy the Admin API access token (shpat_…) and API secret key below.',
    },
];

export default function StoresConnect({ scopes }: Props) {
    return (
        <>
            <Head title="Connect Shopify Store" />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-8 p-4 md:p-6">
                <div className="flex items-center gap-3">
                    <Button variant="ghost" size="sm" asChild className="rounded-xl">
                        <Link href={index()}>
                            <ArrowLeft className="mr-2 size-4" />
                            Back to stores
                        </Link>
                    </Button>
                </div>

                <Heading
                    variant="small"
                    title="Connect your Shopify store"
                    description="Link a development or production store using a custom app Admin API token."
                />

                <Card className="rounded-2xl border-border/60 shadow-[0_4px_20px_-2px_rgb(0_0_0/0.08)]">
                    <CardHeader>
                        <CardTitle className="text-lg">Onboarding guide</CardTitle>
                        <CardDescription>
                            Follow these steps in your Shopify admin before connecting.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-5">
                        {onboardingSteps.map((step, index) => (
                            <div key={step.title} className="flex gap-4">
                                <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                                    <step.icon className="size-5" />
                                </div>
                                <div>
                                    <p className="font-medium text-foreground">
                                        {index + 1}. {step.title}
                                    </p>
                                    <p className="text-sm text-muted-foreground">{step.description}</p>
                                </div>
                            </div>
                        ))}

                        <div className="rounded-xl border border-border/60 bg-muted/30 p-4">
                            <p className="mb-3 text-sm font-medium text-foreground">Required scopes</p>
                            <div className="flex flex-wrap gap-2">
                                {scopes.map((scope) => (
                                    <Badge
                                        key={scope}
                                        variant="secondary"
                                        className="rounded-lg font-mono text-xs"
                                    >
                                        {scope}
                                    </Badge>
                                ))}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card className="rounded-2xl border-border/60 shadow-[0_4px_20px_-2px_rgb(0_0_0/0.08)]">
                    <CardHeader>
                        <CardTitle className="text-lg">Store credentials</CardTitle>
                        <CardDescription>
                            We validate your token before saving. Credentials are encrypted at rest.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            {...ConnectStoreController.store.form()}
                            options={{ preserveScroll: true }}
                            resetOnSuccess={['access_token', 'api_secret']}
                            className="space-y-6"
                        >
                            {({ processing, errors, wasSuccessful }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="domain">Store domain</Label>
                                        <Input
                                            id="domain"
                                            name="domain"
                                            placeholder="myshop.myshopify.com"
                                            autoComplete="off"
                                        />
                                        <InputError message={errors.domain} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Display name (optional)</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            placeholder="My Shopify store"
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
                                        <InputError message={errors.access_token} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="api_secret">API secret key</Label>
                                        <PasswordInput
                                            id="api_secret"
                                            name="api_secret"
                                            placeholder="Required for webhook verification"
                                            autoComplete="off"
                                        />
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
                                        className="rounded-xl bg-indigo-600 px-6 hover:bg-indigo-500"
                                    >
                                        {processing ? 'Validating…' : 'Connect store'}
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
