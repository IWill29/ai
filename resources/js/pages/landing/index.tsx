import { Head } from '@inertiajs/react';
import { lazy, Suspense  } from 'react';
import type {ReactNode} from 'react';
import type { AgentStep } from '@/components/marketing/agent-workflow-section';
import HeroSection from '@/components/marketing/hero-section';
import type { PlanRow } from '@/components/marketing/pricing-section';
import SectionPlaceholder from '@/components/marketing/section-placeholder';
import MarketingLayout from '@/layouts/marketing-layout';

const FeaturesSection = lazy(() => import('@/components/marketing/features-section'));
const AgentWorkflowSection = lazy(() => import('@/components/marketing/agent-workflow-section'));
const PricingSection = lazy(() => import('@/components/marketing/pricing-section'));
const FaqSection = lazy(() => import('@/components/marketing/faq-section'));

type FaqItem = {
    q: string;
    a: string;
};

type Props = Readonly<{
    plans: PlanRow[];
    faqs: FaqItem[];
    agentSteps: AgentStep[];
    canonicalUrl: string;
}>;

const META_DESCRIPTION =
    'Connect Shopify, sync your catalog, and run store operations with AI. Dashboard KPIs, chat with confirmation before every write, and BYOK via OpenRouter.';

const SPECULATION_RULES = JSON.stringify({
    prefetch: [
        {
            source: 'list',
            urls: ['/register', '/login'],
            eagerness: 'moderate',
        },
    ],
});

function faqJsonLd(faqs: FaqItem[]): string {
    return JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'FAQPage',
        mainEntity: faqs.map(({ q, a }) => ({
            '@type': 'Question',
            name: q,
            acceptedAnswer: {
                '@type': 'Answer',
                text: a,
            },
        })),
    });
}

export default function LandingIndex({ plans, faqs, agentSteps, canonicalUrl }: Props) {
    return (
        <>
            <Head title="Run your Shopify store with AI">
                <meta head-key="description" name="description" content={META_DESCRIPTION} />
                <meta
                    head-key="og:title"
                    property="og:title"
                    content="AgentStore — Run your Shopify store with AI"
                />
                <meta head-key="og:description" property="og:description" content={META_DESCRIPTION} />
                <meta head-key="og:type" property="og:type" content="website" />
                <meta head-key="og:url" property="og:url" content={canonicalUrl} />
                <link head-key="canonical" rel="canonical" href={canonicalUrl} />
                <script
                    head-key="speculation-rules"
                    type="speculationrules"
                    dangerouslySetInnerHTML={{ __html: SPECULATION_RULES }}
                />
                {faqs.length > 0 && (
                    <script
                        head-key="faq-jsonld"
                        type="application/ld+json"
                        dangerouslySetInnerHTML={{ __html: faqJsonLd(faqs) }}
                    />
                )}
            </Head>

            <HeroSection />

            <Suspense fallback={<SectionPlaceholder minHeight="28rem" />}>
                <FeaturesSection />
            </Suspense>

            {agentSteps.length > 0 && (
                <Suspense fallback={<SectionPlaceholder minHeight="36rem" />}>
                    <AgentWorkflowSection steps={agentSteps} />
                </Suspense>
            )}

            {plans.length > 0 && (
                <Suspense fallback={<SectionPlaceholder minHeight="24rem" />}>
                    <PricingSection plans={plans} />
                </Suspense>
            )}

            {faqs.length > 0 && (
                <Suspense fallback={<SectionPlaceholder minHeight="20rem" />}>
                    <FaqSection faqs={faqs} />
                </Suspense>
            )}
        </>
    );
}

LandingIndex.layout = (page: ReactNode) => (
    <MarketingLayout>{page}</MarketingLayout>
);
