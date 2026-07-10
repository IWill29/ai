import type { PropsWithChildren } from 'react';
import SiteFooter from '@/components/marketing/site-footer';
import SiteHeader from '@/components/marketing/site-header';

export default function MarketingLayout({ children }: PropsWithChildren) {
    return (
        <div className="marketing-layout flex min-h-svh flex-col bg-background text-foreground dark:bg-[radial-gradient(ellipse_at_top,rgba(99,102,241,0.08),transparent_55%)]">
            <a
                href="#main-content"
                className="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[100] focus:rounded-xl focus:bg-background focus:px-4 focus:py-2 focus:shadow-lg focus:outline-none focus:ring-2 focus:ring-ring"
            >
                Skip to main content
            </a>
            <SiteHeader />
            <main id="main-content" className="flex-1">
                {children}
            </main>
            <SiteFooter />
        </div>
    );
}
