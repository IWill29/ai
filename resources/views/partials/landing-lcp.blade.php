@if(($page['component'] ?? '') === 'landing/index')
    <style>
        #landing-lcp-fallback {
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: oklch(0.145 0 0);
            background: oklch(1 0 0);
        }

        html.dark #landing-lcp-fallback {
            color: oklch(0.93 0.012 277);
            background: oklch(0.12 0.022 277);
        }

        #landing-lcp-fallback.is-hidden {
            display: none;
        }

        body.landing-loading #app {
            visibility: hidden;
            position: absolute;
            width: 0;
            height: 0;
            overflow: hidden;
        }

        body.landing-loaded #app {
            visibility: visible;
            position: static;
            width: auto;
            height: auto;
            overflow: visible;
        }

        #landing-lcp-fallback .lcp-header {
            height: 3.5rem;
            border-bottom: 1px solid oklch(0.922 0 0 / 0.4);
        }

        html.dark #landing-lcp-fallback .lcp-header {
            border-bottom-color: oklch(0.28 0.032 277 / 0.4);
        }

        #landing-lcp-fallback .lcp-shell {
            max-width: 72rem;
            margin: 0 auto;
            padding: 2.5rem 1rem 3rem;
            text-align: center;
        }

        #landing-lcp-fallback .lcp-h1 {
            max-width: 56rem;
            margin: 0 auto;
            font-size: 1.875rem;
            font-weight: 600;
            letter-spacing: -0.025em;
            line-height: 1.2;
        }

        #landing-lcp-fallback .lcp-p {
            max-width: 42rem;
            margin: 0.75rem auto 0;
            font-size: 1rem;
            line-height: 1.625;
            color: oklch(0.556 0 0);
        }

        html.dark #landing-lcp-fallback .lcp-p {
            color: oklch(0.62 0.025 277);
        }

        #landing-lcp-fallback .lcp-mockup-slot {
            max-width: 72rem;
            margin: 2rem auto 0;
            min-height: 22rem;
        }

        @media (min-width: 640px) {
            #landing-lcp-fallback .lcp-shell {
                padding-top: 3rem;
            }

            #landing-lcp-fallback .lcp-h1 {
                font-size: 2.25rem;
            }

            #landing-lcp-fallback .lcp-p {
                margin-top: 1rem;
                font-size: 1.125rem;
            }

            #landing-lcp-fallback .lcp-mockup-slot {
                margin-top: 2.5rem;
                min-height: 22rem;
            }
        }

        @media (min-width: 768px) {
            #landing-lcp-fallback .lcp-header {
                height: 4rem;
            }

            #landing-lcp-fallback .lcp-h1 {
                font-size: 3rem;
            }
        }

        @media (min-width: 1024px) {
            #landing-lcp-fallback .lcp-mockup-slot {
                margin-top: 3.5rem;
                min-height: 26rem;
            }
        }
    </style>

    <div id="landing-lcp-fallback" aria-hidden="true">
        <div class="lcp-header"></div>
        <div class="lcp-shell">
            <h1 class="lcp-h1">Run your Shopify store with AI</h1>
            <p class="lcp-p">
                Connect your store, ask in plain English, and let the agent handle orders,
                products, and inventory — with confirmation before every write.
            </p>
            <div class="lcp-mockup-slot"></div>
        </div>
    </div>
@endif
