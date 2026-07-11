import { Form, Link } from '@inertiajs/react';
import {
    ArrowRight,
    CheckCircle2,
    Lock,
    MessageSquare,
    Shield,
    Sparkles,
    Wallet,
} from 'lucide-react';
import OpenRouterController from '@/actions/App/Http/Controllers/Settings/OpenRouterController';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import AiKeysGuide from '@/components/settings/ai-keys-guide';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { index as chatIndex } from '@/routes/chat';

const cardClass =
    'rounded-2xl border-border/60 bg-card shadow-[0_4px_20px_-2px_rgb(0_0_0/0.08)] dark:border-border/80 dark:shadow-[0_8px_32px_-8px_rgb(0_0_0/0.55)]';

const selectClass =
    'flex h-10 w-full rounded-xl border border-input bg-background px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30';

type OpenRouterState = {
    configured: boolean;
    maskedKey: string | null;
    validatedAt: string | null;
    defaultModel: string | null;
};

type Props = Readonly<{
    openRouter: OpenRouterState;
    suggestedModels: string[];
}>;

const MODEL_HINTS: Record<string, string> = {
    'openai/gpt-4o-mini': 'Fast & affordable — great for everyday chat',
    'anthropic/claude-3.5-haiku': 'Quick and thoughtful replies',
    'deepseek/deepseek-chat': 'Strong value for longer conversations',
    'openai/gpt-4o': 'Higher quality when you need more depth',
    'anthropic/claude-3.5-sonnet': 'Balanced speed and reasoning',
    'anthropic/claude-3-opus': 'Best for complex store questions',
};

const RECOMMENDED_MODEL = 'openai/gpt-4o-mini';

function formatModelOption(model: string): string {
    const hint = MODEL_HINTS[model];
    const shortName = model.includes('/') ? model.split('/').slice(1).join('/') : model;

    if (!hint) {
        return shortName;
    }

    return `${shortName} — ${hint}`;
}

function formatValidatedDate(iso: string): string {
    return new Date(iso).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

export default function AiKeysSetupPanel({ openRouter, suggestedModels }: Props) {
    return (
        <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 md:gap-8 md:p-6">
            <div className="space-y-3">
                <p className="max-w-xl text-sm leading-relaxed text-muted-foreground">
                    Bring your own OpenRouter key to unlock AI chat. Your dashboard and stores work
                    without it — chat needs a key so you stay in control of model costs.
                </p>
                <div className="flex flex-wrap gap-2">
                    <span className="inline-flex items-center gap-1.5 rounded-full border border-border/60 bg-muted/40 px-3 py-1 text-xs text-muted-foreground">
                        <Lock className="size-3.5 text-emerald-600 dark:text-emerald-400" aria-hidden />
                        Encrypted at rest
                    </span>
                    <span className="inline-flex items-center gap-1.5 rounded-full border border-border/60 bg-muted/40 px-3 py-1 text-xs text-muted-foreground">
                        <Shield className="size-3.5" aria-hidden />
                        Validated before save
                    </span>
                    <span className="inline-flex items-center gap-1.5 rounded-full border border-border/60 bg-muted/40 px-3 py-1 text-xs text-muted-foreground">
                        <Wallet className="size-3.5" aria-hidden />
                        Billed by OpenRouter, not us
                    </span>
                </div>
            </div>

            {openRouter.configured && (
                <Card
                    className={cn(
                        cardClass,
                        'overflow-hidden border-emerald-500/25 bg-emerald-500/[0.04]',
                        'motion-safe:transition-[opacity,transform] motion-safe:duration-200 motion-safe:ease-out',
                        'motion-safe:starting:opacity-0 motion-safe:starting:translate-y-2',
                    )}
                >
                    <CardContent className="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                        <div className="flex gap-3">
                            <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/15">
                                <CheckCircle2
                                    className="size-5 text-emerald-600 dark:text-emerald-400"
                                    strokeWidth={2}
                                    aria-hidden
                                />
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm font-semibold text-foreground">Key connected</p>
                                <div className="flex flex-wrap items-center gap-2">
                                    <code className="rounded-md bg-muted/60 px-2 py-0.5 font-mono text-xs text-foreground">
                                        {openRouter.maskedKey}
                                    </code>
                                    {openRouter.validatedAt && (
                                        <Badge
                                            variant="secondary"
                                            className="rounded-lg bg-emerald-500/10 text-emerald-800 dark:text-emerald-200"
                                        >
                                            Validated
                                        </Badge>
                                    )}
                                </div>
                                {openRouter.defaultModel && (
                                    <p className="text-xs text-muted-foreground">
                                        Default model:{' '}
                                        <span className="font-medium text-foreground">
                                            {openRouter.defaultModel}
                                        </span>
                                    </p>
                                )}
                                {openRouter.validatedAt && (
                                    <p className="text-xs text-muted-foreground">
                                        Last checked {formatValidatedDate(openRouter.validatedAt)}
                                    </p>
                                )}
                            </div>
                        </div>
                        <Button asChild variant="brand" className="w-full shrink-0 rounded-full sm:w-auto">
                            <Link href={chatIndex()} className="gap-2">
                                <MessageSquare className="size-4" aria-hidden />
                                Open chat
                                <ArrowRight className="size-4" aria-hidden />
                            </Link>
                        </Button>
                    </CardContent>
                </Card>
            )}

            {!openRouter.configured && <AiKeysGuide />}

            <Card className={cn(cardClass, 'relative overflow-hidden')}>
                <div
                    aria-hidden
                    className="pointer-events-none absolute inset-x-0 top-0 h-24 bg-[radial-gradient(ellipse_at_top,rgba(99,102,241,0.1),transparent_70%)] dark:bg-[radial-gradient(ellipse_at_top,rgba(99,102,241,0.16),transparent_72%)]"
                />
                <CardHeader className="relative">
                    <div className="flex items-start gap-3">
                        <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-indigo-500/10">
                            <Sparkles
                                className="size-5 text-indigo-600 dark:text-indigo-400"
                                strokeWidth={2}
                                aria-hidden
                            />
                        </div>
                        <div className="space-y-1">
                            <CardTitle className="text-lg">
                                {openRouter.configured ? 'Update your key' : 'Your API key'}
                            </CardTitle>
                            <CardDescription>
                                {openRouter.configured
                                    ? 'Paste a new key anytime — we re-validate before replacing the saved one.'
                                    : 'Paste the key from OpenRouter. We test it live, then store it encrypted.'}
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent className="relative">
                    <Form
                        {...OpenRouterController.store.form()}
                        options={{ preserveScroll: true }}
                        resetOnSuccess={['api_key']}
                        className="space-y-5"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="api_key">API key</Label>
                                    <PasswordInput
                                        id="api_key"
                                        name="api_key"
                                        placeholder="sk-or-v1-..."
                                        autoComplete="off"
                                        className="rounded-xl"
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        Starts with{' '}
                                        <code className="rounded bg-muted/60 px-1 py-0.5 font-mono text-[11px]">
                                            sk-or-
                                        </code>
                                        {' '}
                                        Never share it — we only use it for your chat requests.
                                    </p>
                                    <InputError message={errors.api_key} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="default_model">Default model (optional)</Label>
                                    <select
                                        id="default_model"
                                        name="default_model"
                                        defaultValue={openRouter.defaultModel ?? RECOMMENDED_MODEL}
                                        className={selectClass}
                                    >
                                        <option value="">No default — pick in chat</option>
                                        {suggestedModels.map((model) => (
                                            <option key={model} value={model}>
                                                {formatModelOption(model)}
                                                {model === RECOMMENDED_MODEL ? ' · Recommended' : ''}
                                            </option>
                                        ))}
                                    </select>
                                    <p className="text-xs text-muted-foreground">
                                        You can switch models per message in chat. This just sets your
                                        starting preference.
                                    </p>
                                    <InputError message={errors.default_model} />
                                </div>

                                <div className="flex flex-col gap-2 border-t border-border/50 pt-5 sm:flex-row sm:items-center sm:justify-between">
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        variant="brand"
                                        className="w-full rounded-full sm:w-auto sm:px-8"
                                    >
                                        {processing ? 'Validating…' : 'Validate & save'}
                                    </Button>
                                    <p className="text-center text-xs text-muted-foreground sm:text-right">
                                        Takes a few seconds · Key never logged
                                    </p>
                                </div>
                            </>
                        )}
                    </Form>
                </CardContent>
            </Card>

            {openRouter.configured && <AiKeysGuide />}
        </div>
    );
}
