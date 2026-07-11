const EXPONENTS: Record<string, number> = {
    JPY: 0,
    KRW: 0,
    VND: 0,
    CLP: 0,
    ISK: 0,
    UGX: 0,
    XAF: 0,
    XOF: 0,
    XPF: 0,
    RWF: 0,
    BIF: 0,
    DJF: 0,
    GNF: 0,
    KMF: 0,
    PYG: 0,
    BHD: 3,
    KWD: 3,
    OMR: 3,
    TND: 3,
    JOD: 3,
    IQD: 3,
    LYD: 3,
};

function exponentFor(currency: string): number {
    return EXPONENTS[currency.toUpperCase()] ?? 2;
}

export function formatMoney(minor: number, currency: string): string {
    const exp = exponentFor(currency);
    const major = minor / 10 ** exp;
    const formatted = major.toLocaleString(undefined, {
        minimumFractionDigits: exp,
        maximumFractionDigits: exp,
    });

    switch (currency) {
        case 'EUR':
            return `€${formatted}`;
        case 'USD':
            return `$${formatted}`;
        default:
            return `${currency} ${formatted}`;
    }
}
