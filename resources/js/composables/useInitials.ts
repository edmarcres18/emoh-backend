export function getInitials(fullName?: string): string {
    if (!fullName || typeof fullName !== 'string') return '';

    // Filter out empty strings that may result from multiple spaces
    const names = fullName.trim().split(' ').filter(name => name.length > 0);

    if (names.length === 0) return '';
    if (names.length === 1) {
        const firstChar = names[0]?.[0];
        return firstChar ? firstChar.toUpperCase() : '';
    }

    const firstChar = names[0]?.[0];
    const lastChar = names[names.length - 1]?.[0];
    
    if (!firstChar || !lastChar) return '';
    
    return `${firstChar}${lastChar}`.toUpperCase();
}

export function useInitials() {
    return { getInitials };
}
