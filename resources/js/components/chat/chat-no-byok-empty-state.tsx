import { Link } from '@inertiajs/react';
import { ArrowRight, KeyRound } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { openrouter } from '@/routes/settings';

const cardClass =
    'rounded-2xl border-border/60 bg-card shadow-[0_4px_20px_-2px_rgb(0_0_0/0.08)] dark:border-border/80';

export default function ChatNoByokEmptyState() {
    return (
        <div className="flex flex-1 items-center justify-center p-6">
            <Card className={cn(cardClass, 'max-w-lg border-dashed')}>
                <CardContent className="flex flex-col gap-4 px-6 py-8 text-center">
                    <div className="mx-auto flex size-12 items-center justify-center rounded-xl bg-indigo-500/10">
                        <KeyRound className="size-5 text-indigo-600 dark:text-indigo-400" aria-hidden />
                    </div>
                    <div className="space-y-2">
                        <h2 className="text-xl font-semibold tracking-tight">Add your OpenRouter key</h2>
                        <p className="text-sm leading-relaxed text-muted-foreground">
                            Chat runs on your own API key. Dashboard and store sync work without it, but
                            the agent needs a validated key before it can respond.
                        </p>
                    </div>
                    <Button asChild variant="brand" className="rounded-full">
                        <Link href={openrouter()} className="gap-2">
                            Open AI keys settings
                            <ArrowRight className="size-4" aria-hidden />
                        </Link>
                    </Button>
                </CardContent>
            </Card>
        </div>
    );
}
