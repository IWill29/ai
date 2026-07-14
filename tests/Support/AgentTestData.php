<?php

declare(strict_types=1);

namespace Tests\Support;

final class AgentTestData
{
    public const DEFAULT_MODEL = 'openai/gpt-4o-mini';

    public const ORDER_100 = 'gid://shopify/Order/100';

    public const ORDER_1 = 'gid://shopify/Order/1';

    public const ORDER_200 = 'gid://shopify/Order/200';

    public const MERCHANT_MESSAGE_REMEMBER_BRIEF = 'Remember that I want brief answers';

    public const MEMORY_PREFERENCE_WANT_BRIEF = 'Merchant preference: I want brief answers';

    public const MEMORY_PREFERENCE_KEEP_BRIEF = 'Merchant preference: keep answers brief';

    public const MEMORY_PREFERENCE_BRIEF_ANSWERS = 'Merchant preference: brief answers';

    public const MEMORY_CONFIRMED_FULFILL_ORDER_100 = 'Merchant confirmed: Fulfill order on gid://shopify/Order/100';

    public const MEMORY_FULFILL_PHRASE = 'Fulfill order';

    public const CHAT_FULFILL_ORDER_100 = 'Fulfill order 100';

    public const CHAT_FULFILL_ORDER_200 = 'Fulfill order 200';

    public const CHAT_FULFILL_ORDER_1 = 'Fulfill order 1';

    public const SSE_STATUS_COMPLETED = '"status":"completed"';

    public const SSE_STATUS_AWAITING_CONFIRMATION = '"status":"awaiting_confirmation"';

    public const SSE_CONFIRMATION_REQUIRED = 'confirmation_required';

    public const SSE_EVENT_CONFIRMATION_REQUIRED = 'event: confirmation_required';

    public const MEMORY_PREFERENCE_EUR_FORMATTING = 'Merchant preference: use EUR formatting';

    public const MERCHANT_MESSAGE_EUR_FORMATTING = 'Always use EUR formatting';
}
