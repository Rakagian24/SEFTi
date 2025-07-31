import { ref, computed } from 'vue'
import { formatCurrency, parseCurrency } from '@/lib/currencyUtils'

export function useCurrencyInput(initialValue: string | number = '', currency: string = 'IDR') {
  const rawValue = ref(initialValue ? String(initialValue) : '')
  const currencyType = ref(currency)

  const formattedValue = computed(() => {
    return formatCurrency(rawValue.value, currencyType.value)
  })

  const handleInput = (event: Event) => {
    const input = event.target as HTMLInputElement
    const parsedValue = parseCurrency(input.value, currencyType.value)
    rawValue.value = parsedValue
  }

  const handleKeydown = (event: KeyboardEvent) => {
    const allowedKeys = [
      '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
      '.', ',', 'Backspace', 'Delete', 'Tab', 'Enter',
      'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'
    ]

    if (!allowedKeys.includes(event.key)) {
      event.preventDefault()
    }

    // Prevent multiple decimal points
    if (event.key === '.' && (event.target as HTMLInputElement).value.includes('.')) {
      event.preventDefault()
    }
  }

  const setValue = (value: string | number) => {
    rawValue.value = String(value)
  }

  const getValue = () => rawValue.value

  const getNumericValue = () => {
    const num = parseFloat(rawValue.value)
    return isNaN(num) ? 0 : num
  }

  return {
    rawValue,
    formattedValue,
    currencyType,
    handleInput,
    handleKeydown,
    setValue,
    getValue,
    getNumericValue
  }
}
