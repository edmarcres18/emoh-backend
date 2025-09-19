/**
 * Currency utility functions for Philippine Peso (PHP) formatting
 */

/**
 * Format amount to Philippine Peso currency
 * @param amount - The amount to format
 * @param options - Additional formatting options
 * @returns Formatted currency string
 */
export function formatCurrency(amount: number | null | undefined, options: {
    showSymbol?: boolean;
    minimumFractionDigits?: number;
    maximumFractionDigits?: number;
    locale?: string;
} = {}): string {
    if (amount === null || amount === undefined || isNaN(Number(amount))) {
        return 'N/A';
    }

    const {
        showSymbol = true,
        minimumFractionDigits = 2,
        maximumFractionDigits = 2,
        locale = 'en-PH'
    } = options;

    const formatter = new Intl.NumberFormat(locale, {
        style: showSymbol ? 'currency' : 'decimal',
        currency: 'PHP',
        minimumFractionDigits,
        maximumFractionDigits,
    });

    return formatter.format(Number(amount));
}

/**
 * Format amount to Philippine Peso with custom symbol
 * @param amount - The amount to format
 * @param symbol - Custom currency symbol (default: ₱)
 * @returns Formatted currency string with custom symbol
 */
export function formatCurrencyWithSymbol(amount: number | null | undefined, symbol: string = '₱'): string {
    if (amount === null || amount === undefined || isNaN(Number(amount))) {
        return 'N/A';
    }

    const formatter = new Intl.NumberFormat('en-PH', {
        style: 'decimal',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    return `${symbol}${formatter.format(Number(amount))}`;
}

/**
 * Format amount for display in forms (without currency symbol)
 * @param amount - The amount to format
 * @returns Formatted number string
 */
export function formatCurrencyForInput(amount: number | null | undefined): string {
    if (amount === null || amount === undefined || isNaN(Number(amount))) {
        return '';
    }

    return Number(amount).toFixed(2);
}

/**
 * Parse currency string to number
 * @param currencyString - The currency string to parse
 * @returns Parsed number or null if invalid
 */
export function parseCurrency(currencyString: string): number | null {
    if (!currencyString || typeof currencyString !== 'string') {
        return null;
    }

    // Remove currency symbols and spaces
    const cleanString = currencyString.replace(/[₱$,\s]/g, '');
    const parsed = parseFloat(cleanString);
    
    return isNaN(parsed) ? null : parsed;
}

/**
 * Get currency symbol for Philippine Peso
 * @returns PHP currency symbol
 */
export function getCurrencySymbol(): string {
    return '₱';
}

/**
 * Get currency code for Philippine Peso
 * @returns PHP currency code
 */
export function getCurrencyCode(): string {
    return 'PHP';
}

/**
 * Get currency name for Philippine Peso
 * @returns PHP currency name
 */
export function getCurrencyName(): string {
    return 'Philippine Peso';
}

/**
 * Format large amounts with abbreviated units (K, M, B)
 * @param amount - The amount to format
 * @param precision - Number of decimal places for abbreviated amounts
 * @returns Formatted currency string with abbreviation
 */
export function formatCurrencyAbbreviated(amount: number | null | undefined, precision: number = 1): string {
    if (amount === null || amount === undefined || isNaN(Number(amount))) {
        return 'N/A';
    }

    const num = Number(amount);
    const symbol = getCurrencySymbol();

    if (num >= 1000000000) {
        return `${symbol}${(num / 1000000000).toFixed(precision)}B`;
    } else if (num >= 1000000) {
        return `${symbol}${(num / 1000000).toFixed(precision)}M`;
    } else if (num >= 1000) {
        return `${symbol}${(num / 1000).toFixed(precision)}K`;
    } else {
        return formatCurrency(num);
    }
}
