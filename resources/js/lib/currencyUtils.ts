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
 * @param currency - Currency code (default: IDR)
 * @returns Formatted currency string
 */
export function formatCurrency(value: string | number, currency: string = 'IDR'): string {
  if (!value && value !== 0) return '';

  const config = CURRENCY_CONFIGS[currency] || CURRENCY_CONFIGS.IDR;

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

  // Tambahkan simbol mata uang sesuai currency
  switch (currency?.toUpperCase()) {
    case 'USD':
      return `$${formattedNumber}`;
    case 'EUR':
      return `€${formattedNumber}`;
    case 'SGD':
      return `S$${formattedNumber}`;
    case 'IDR':
    default:
      return `Rp ${formattedNumber}`;
  }
}

/**
 * Parse formatted currency string back to raw number
 * @param formattedValue - Formatted currency string
 * @param currency - Currency code (default: IDR)
 * @returns Raw number as string
 */
export function parseCurrency(formattedValue: string, currency: string = 'IDR'): string {
  if (!formattedValue) return '';

  const config = CURRENCY_CONFIGS[currency] || CURRENCY_CONFIGS.IDR;

  // Remove currency symbol
  let cleaned = formattedValue.replace(config.symbol, '');

  // Remove thousand separators
  cleaned = cleaned.replace(new RegExp(`\\${config.thousandSeparator}`, 'g'), '');

  // Ensure decimal separator is correct
  if (config.decimalSeparator !== '.') {
    cleaned = cleaned.replace(new RegExp(`\\${config.decimalSeparator}`, 'g'), '.');
  }

  // Only allow digits and decimal point
  cleaned = cleaned.replace(/[^\d.]/g, '');

  // Handle multiple decimal points (keep only the first one)
  const parts = cleaned.split('.');
  if (parts.length > 2) {
    cleaned = parts[0] + '.' + parts.slice(1).join('');
  }

  return cleaned;
}

/**
 * Validate if a string is a valid currency input
 * @param value - Input string to validate
 * @param currency - Currency code (default: IDR)
 * @returns True if valid currency input
 */
export function isValidCurrencyInput(value: string, currency: string = 'IDR'): boolean {
  if (!value) return true;

  const config = CURRENCY_CONFIGS[currency] || CURRENCY_CONFIGS.IDR;

  // Remove currency symbol
  let cleaned = value.replace(config.symbol, '');

  // Check for valid format: digits with optional thousand separators and decimal (up to 5 decimal places)
  const pattern = new RegExp(
    `^\\d{1,3}(\\${config.thousandSeparator}\\d{3})*(\\${config.decimalSeparator}\\d{0,5})?$`
  );

  return pattern.test(cleaned);
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
