import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';
import AgentWorkflowSection, { type AgentStep } from '@/components/marketing/agent-workflow-section';
import FaqSection from '@/components/marketing/faq-section';
import FeaturesSection from '@/components/marketing/features-section';
import HeroSection from '@/components/marketing/hero-section';
import PricingSection, { type PlanRow } from '@/components/marketing/pricing-section';
import MarketingLayout from '@/layouts/marketing-layout';

type FaqItem = {
    q: string;
    a: string;
};

type Props = {
    plans: PlanRow[];
    faqs: FaqItem[];
    agentSteps: AgentStep[];
    canonicalUrl: string;
};

const META_DESCRIPTION =
    'Connect Shopify, sync your catalog, and run store operations with AI. Dashboard KPIs, chat with confirmation before every write, and BYOK via OpenRouter.';

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
                {faqs.length > 0 && (
                    <script
                        head-key="faq-jsonld"
                        type="application/ld+json"
                        dangerouslySetInnerHTML={{ __html: faqJsonLd(faqs) }}
                    />
                )}
            </Head>

            <HeroSection />
            <FeaturesSection />
            {agentSteps.length > 0 && <AgentWorkflowSection steps={agentSteps} />}
            {plans.length > 0 && <PricingSection plans={plans} />}
            {faqs.length > 0 && <FaqSection faqs={faqs} />}
        </>
    );
}

LandingIndex.layout = (page: ReactNode) => (
    <MarketingLayout>{page}</MarketingLayout>
);
