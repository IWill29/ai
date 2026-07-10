import { Link } from '@inertiajs/react';
import { Menu } from 'lucide-react';
import { lazy, Suspense, useState } from 'react';
import AppLogo from '@/components/app-logo';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { MARKETING_ROUTES } from '@/lib/marketing-routes';

const AppearanceToggle = lazy(() => import('@/components/appearance-toggle'));

const NAV_LINKS = [
    { href: '#features', label: 'Features' },
    { href: '#how-it-works', label: 'How it works' },
    { href: '#pricing', label: 'Pricing' },
    { href: '#faq', label: 'FAQ' },
] as const;

export default function SiteHeader() {
    const [mobileOpen, setMobileOpen] = useState(false);

    return (
        <header className="sticky top-0 z-50 border-b border-border/40 bg-background/95 md:bg-background/85 md:backdrop-blur-md md:supports-[backdrop-filter]:bg-background/70">
            <div className="mx-auto flex h-14 max-w-[var(--landing-max-width)] items-center justify-between gap-4 px-4 md:h-16 md:px-6">
                <Link href="/" className="min-w-0 shrink-0 rounded-lg outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <AppLogo />
                </Link>

                <nav
                    className="hidden items-center gap-8 text-sm text-muted-foreground md:flex"
                    aria-label="Primary"
                >
                    {NAV_LINKS.map(({ href, label }) => (
                        <a
                            key={href}
                            href={href}
                            className="rounded-md transition-colors duration-150 ease-out hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        >
                            {label}
                        </a>
                    ))}
                </nav>

                <div className="flex items-center gap-2">
                    <Suspense fallback={<div className="hidden size-9 sm:block" aria-hidden />}>
                        <AppearanceToggle showLabels={false} className="hidden sm:inline-grid" />
                    </Suspense>

                    <Button variant="ghost" asChild className="hidden rounded-xl sm:inline-flex">
                        <Link href={MARKETING_ROUTES.login} prefetch>
                            Log in
                        </Link>
                    </Button>
                    <Button asChild variant="brand" className="hidden rounded-full sm:inline-flex">
                        <Link href={MARKETING_ROUTES.register} prefetch>
                            Get started
                        </Link>
                    </Button>

                    <Button asChild variant="brand" size="sm" className="rounded-full sm:hidden">
                        <Link href={MARKETING_ROUTES.register} prefetch>
                            Start
                        </Link>
                    </Button>

                    <Sheet open={mobileOpen} onOpenChange={setMobileOpen}>
                        <SheetTrigger asChild>
                            <Button
                                variant="outline"
                                size="icon"
                                className="rounded-xl md:hidden"
                                aria-label="Open menu"
                            >
                                <Menu className="size-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="right" className="w-[min(100%,20rem)] rounded-l-2xl">
                            <SheetHeader>
                                <SheetTitle>Menu</SheetTitle>
                            </SheetHeader>
                            <nav className="mt-6 flex flex-col gap-1" aria-label="Mobile">
                                {NAV_LINKS.map(({ href, label }) => (
                                    <a
                                        key={href}
                                        href={href}
                                        onClick={() => setMobileOpen(false)}
                                        className="rounded-xl px-3 py-2.5 text-sm font-medium transition-colors duration-150 ease-out hover:bg-muted"
                                    >
                                        {label}
                                    </a>
                                ))}
                            </nav>
                            <div className="mt-6 flex flex-col gap-2 border-t border-border/50 pt-6">
                                <Suspense fallback={null}>
                                    <AppearanceToggle className="w-full" />
                                </Suspense>
                                <Button variant="outline" asChild className="rounded-xl">
                                    <Link href={MARKETING_ROUTES.login} onClick={() => setMobileOpen(false)}>
                                        Log in
                                    </Link>
                                </Button>
                                <Button asChild variant="brand" className="rounded-full">
                                    <Link href={MARKETING_ROUTES.register} onClick={() => setMobileOpen(false)}>
                                        Get started
                                    </Link>
                                </Button>
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>
            </div>
        </header>
    );
}
