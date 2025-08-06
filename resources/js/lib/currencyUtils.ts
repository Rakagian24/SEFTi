/**
 * Currency formatting utilities
 */

export interface CurrencyConfig {
  symbol: string;
  thousandSeparator: string;
  decimalSeparator: string;
  decimalPlaces: number;
}

export const CURRENCY_CONFIGS: Record<string, CurrencyConfig> = {
  IDR: {
    symbol: 'Rp ',
    thousandSeparator: ',',
    decimalSeparator: '.',
    decimalPlaces: 0 // Will be determined dynamically
  },
  USD: {
    symbol: '$',
    thousandSeparator: ',',
    decimalSeparator: '.',
    decimalPlaces: 0 // Will be determined dynamically
  }
};

/**
 * Format number to currency string with thousand separators
 * @param value - Raw number value (can be string or number)
 * @returns Formatted currency string
 */
export function formatCurrency(value: string | number): string {
  if (!value && value !== 0) return '';

  // Convert to number and handle NaN
  const numValue = Number(value);
  if (isNaN(numValue)) return '';

  // Format tanpa rounding - tampilkan decimal sesuai aslinya
  let formattedNumber: string;

  if (Number.isInteger(numValue)) {
    // Jika integer, tampilkan tanpa decimal
    formattedNumber = numValue.toLocaleString('en-US');
  } else {
    // Jika ada decimal, tampilkan sesuai aslinya tanpa rounding
    const decimalPlaces = (numValue.toString().split('.')[1] || '').length;
    formattedNumber = numValue.toLocaleString('en-US', {
      minimumFractionDigits: decimalPlaces,
      maximumFractionDigits: decimalPlaces,
    });
  }

  // Return hanya angka dengan pemisah ribuan, tanpa simbol mata uang
  return formattedNumber;
}

/**
 * Parse formatted currency string back to raw number
 * @param formattedValue - Formatted currency string
 * @returns Raw number as string
 */
export function parseCurrency(formattedValue: string): string {
  if (!formattedValue) return '';

  // Remove thousand separators (comma)
  const cleaned = formattedValue.replace(/,/g, '');

  // Only allow digits and decimal point
  const cleaned2 = cleaned.replace(/[^\d.]/g, '');

  // Handle multiple decimal points (keep only the first one)
  const parts = cleaned2.split('.');
  if (parts.length > 2) {
    return parts[0] + '.' + parts.slice(1).join('');
  }

  return cleaned2;
}

/**
 * Validate if a string is a valid currency input
 * @param value - Input string to validate
 * @returns True if valid currency input
 */
export function isValidCurrencyInput(value: string): boolean {
  if (!value) return true;

  // Check for valid format: digits with optional thousand separators and decimal (up to 5 decimal places)
  const pattern = /^\d{1,3}(,\d{3})*(\.\d{0,5})?$/;

  return pattern.test(value);
}

/**
 * Get currency symbol for a given currency code
 * @param currency - Currency code
 * @returns Currency symbol
 */
export function getCurrencySymbol(currency: string = 'IDR'): string {
  const config = CURRENCY_CONFIGS[currency] || CURRENCY_CONFIGS.IDR;
  return config.symbol;
}
