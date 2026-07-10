import { Link } from '@inertiajs/react';
import { login, register } from '@/routes';

export default function SiteFooter() {
    const year = new Date().getFullYear();

    return (
        <footer className="border-t border-border/40 bg-background/80">
            <div className="mx-auto flex max-w-[var(--landing-max-width)] flex-col gap-8 px-4 py-12 md:flex-row md:items-start md:justify-between md:px-6">
                <div className="space-y-2">
                    <p className="text-sm font-semibold tracking-tight">AgentStore</p>
                    <p className="max-w-xs text-sm text-muted-foreground">
                        AI operations for Shopify merchants — mirror sync, dashboard, and chat with
                        confirmation before every write.
                    </p>
                </div>

                <div className="grid grid-cols-2 gap-8 text-sm sm:grid-cols-3">
                    <div className="space-y-3">
                        <p className="font-medium">Product</p>
                        <ul className="space-y-2 text-muted-foreground">
                            <li>
                                <a href="#features" className="hover:text-foreground">
                                    Features
                                </a>
                            </li>
                            <li>
                                <a href="#how-it-works" className="hover:text-foreground">
                                    How it works
                                </a>
                            </li>
                            <li>
                                <a href="#pricing" className="hover:text-foreground">
                                    Pricing
                                </a>
                            </li>
                            <li>
                                <a href="#faq" className="hover:text-foreground">
                                    FAQ
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div className="space-y-3">
                        <p className="font-medium">Account</p>
                        <ul className="space-y-2 text-muted-foreground">
                            <li>
                                <Link href={login()} className="hover:text-foreground">
                                    Log in
                                </Link>
                            </li>
                            <li>
                                <Link href={register()} className="hover:text-foreground">
                                    Register
                                </Link>
                            </li>
                        </ul>
                    </div>
                    <div className="space-y-3">
                        <p className="font-medium">Contact</p>
                        <p className="text-muted-foreground">
                            <a href="mailto:support@agentstore.app" className="hover:text-foreground">
                                support@agentstore.app
                            </a>
                        </p>
                    </div>
                </div>
            </div>
            <div className="border-t border-border/40 py-6 text-center text-xs text-muted-foreground">
                © {year} AgentStore. Shopify is a trademark of Shopify Inc.
            </div>
        </footer>
    );
}
