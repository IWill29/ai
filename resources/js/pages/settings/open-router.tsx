import { Form, Head } from '@inertiajs/react';
import OpenRouterController from '@/actions/App/Http/Controllers/Settings/OpenRouterController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

type OpenRouterState = {
    configured: boolean;
    maskedKey: string | null;
    validatedAt: string | null;
    defaultModel: string | null;
};

type Props = {
    openRouter: OpenRouterState;
    suggestedModels: string[];
};

export default function OpenRouterSettings({ openRouter, suggestedModels }: Props) {
    return (
        <>
            <Head title="OpenRouter" />

            <h1 className="sr-only">OpenRouter settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="OpenRouter API key"
                    description="Bring your own key for AI chat. Required before your first chat message, not for dashboard or stores."
                />

                {openRouter.configured && (
                    <div className="rounded-xl border border-border/60 bg-muted/30 p-4 text-sm">
                        <div className="flex flex-wrap items-center gap-2">
                            <span className="font-medium text-foreground">
                                {openRouter.maskedKey}
                            </span>
                            {openRouter.validatedAt && (
                                <Badge variant="secondary">Validated</Badge>
                            )}
                        </div>
                        {openRouter.defaultModel && (
                            <p className="mt-2 text-muted-foreground">
                                Default model: {openRouter.defaultModel}
                            </p>
                        )}
                    </div>
                )}

                <Form
                    {...OpenRouterController.store.form()}
                    options={{ preserveScroll: true }}
                    resetOnSuccess={['api_key']}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="api_key">API key</Label>
                                <PasswordInput
                                    id="api_key"
                                    name="api_key"
                                    placeholder="sk-or-..."
                                    autoComplete="off"
                                />
                                <InputError message={errors.api_key} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="default_model">Default model (optional)</Label>
                                <select
                                    id="default_model"
                                    name="default_model"
                                    defaultValue={openRouter.defaultModel ?? ''}
                                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                >
                                    <option value="">Choose a model</option>
                                    {suggestedModels.map((model) => (
                                        <option key={model} value={model}>
                                            {model}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.default_model} />
                            </div>

                            <Button type="submit" disabled={processing}>
                                Validate &amp; save
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
