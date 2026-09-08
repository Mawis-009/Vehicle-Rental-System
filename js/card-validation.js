/**
 * Credit/Debit Card Validation using the Mod-10 (Luhn) Algorithm
 * 
 * The Luhn algorithm verifies that a card number has a valid checksum.
 * This does NOT confirm that the card is active, has funds, or belongs to the user.
 * For actual payment processing, use a PCI-compliant payment gateway.
 */

/**
 * Validate a card number using the Luhn/Mod-10 algorithm
 * @param {string} cardNumber - The card number (may contain spaces or hyphens)
 * @returns {boolean} - True if valid checksum, false otherwise
 */
function luhnValidate(cardNumber) {
    // Remove spaces and hyphens
    const cleaned = cardNumber.replace(/[\s\-]/g, '');
    
    // Check that it contains only digits
    if (!/^\d+$/.test(cleaned)) {
        return false;
    }
    
    // Card number should be between 13 and 19 digits
    if (cleaned.length < 13 || cleaned.length > 19) {
        return false;
    }
    
    // Luhn Algorithm Implementation
    let sum = 0;
    let isEven = false;
    
    // Loop from right to left
    for (let i = cleaned.length - 1; i >= 0; i--) {
        let digit = parseInt(cleaned[i], 10);
        
        if (isEven) {
            digit *= 2;
            if (digit > 9) {
                digit -= 9;
            }
        }
        
        sum += digit;
        isEven = !isEven;
    }
    
    return (sum % 10) === 0;
}

/**
 * Detect the card type based on the card number prefix
 * @param {string} cardNumber - The card number
 * @returns {object} - {type: string, name: string} or null
 */
function detectCardType(cardNumber) {
    const cleaned = cardNumber.replace(/[\s\-]/g, '');
    
    const patterns = [
        { type: 'visa',       name: 'Visa',             pattern: /^4/ },
        { type: 'mastercard', name: 'Mastercard',       pattern: /^5[1-5]/ },
        { type: 'amex',       name: 'American Express', pattern: /^3[47]/ },
        { type: 'discover',   name: 'Discover',         pattern: /^6(?:011|5)/ },
        { type: 'diners',     name: 'Diners Club',      pattern: /^3(?:0[0-5]|[68])/ },
        { type: 'jcb',        name: 'JCB',              pattern: /^35(?:2[89]|[3-8])/ }
    ];
    
    for (const card of patterns) {
        if (card.pattern.test(cleaned)) {
            return { type: card.type, name: card.name };
        }
    }
    
    return null;
}

/**
 * Format card number with spaces every 4 digits
 * @param {string} value - Raw card number input
 * @returns {string} - Formatted card number
 */
function formatCardNumber(value) {
    const cleaned = value.replace(/\D/g, '');
    const groups = cleaned.match(/.{1,4}/g);
    return groups ? groups.join(' ') : cleaned;
}

/**
 * Initialize card validation on a form
 * Call this function when the payment page loads.
 */
function initCardValidation() {
    const cardInput = document.getElementById('card_number');
    const cardFeedback = document.getElementById('card_feedback');
    const cardTypeDisplay = document.getElementById('card_type');
    const cardForm = document.getElementById('card_payment_form');
    
    if (!cardInput) return;
    
    // Real-time validation as user types
    cardInput.addEventListener('input', function(e) {
        // Format the card number
        const cursorPos = this.selectionStart;
        const oldLength = this.value.length;
        this.value = formatCardNumber(this.value);
        const newLength = this.value.length;
        
        // Adjust cursor position after formatting
        const diff = newLength - oldLength;
        this.setSelectionRange(cursorPos + diff, cursorPos + diff);
        
        const raw = this.value.replace(/\s/g, '');
        
        // Detect and display card type
        if (cardTypeDisplay) {
            const cardType = detectCardType(raw);
            if (cardType && raw.length >= 2) {
                cardTypeDisplay.textContent = cardType.name;
                cardTypeDisplay.className = 'card-type card-type-' + cardType.type;
            } else {
                cardTypeDisplay.textContent = '';
                cardTypeDisplay.className = 'card-type';
            }
        }
        
        // Validate and show feedback
        if (cardFeedback) {
            if (raw.length >= 13) {
                if (luhnValidate(raw)) {
                    cardFeedback.textContent = '✓ Valid card number';
                    cardFeedback.className = 'card-feedback valid';
                    cardInput.classList.remove('is-invalid');
                    cardInput.classList.add('is-valid');
                } else {
                    cardFeedback.textContent = '✗ Invalid card number';
                    cardFeedback.className = 'card-feedback invalid';
                    cardInput.classList.remove('is-valid');
                    cardInput.classList.add('is-invalid');
                }
            } else {
                cardFeedback.textContent = '';
                cardFeedback.className = 'card-feedback';
                cardInput.classList.remove('is-valid', 'is-invalid');
            }
        }
    });
    
    // Validate expiry date
    const expiryInput = document.getElementById('card_expiry');
    if (expiryInput) {
        expiryInput.addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            this.value = value;
        });
    }
    
    // Form submission validation
    if (cardForm) {
        cardForm.addEventListener('submit', function(e) {
            const raw = cardInput.value.replace(/[\s\-]/g, '');
            const cardName = document.getElementById('card_name');
            const expiry = document.getElementById('card_expiry');
            let isValid = true;
            
            // Clear previous errors
            clearCardErrors();
            
            // Validate card number with Luhn
            if (!raw || !luhnValidate(raw)) {
                showCardError(cardInput, 'Please enter a valid card number.');
                isValid = false;
            }
            
            // Validate cardholder name
            if (cardName && !cardName.value.trim()) {
                showCardError(cardName, 'Cardholder name is required.');
                isValid = false;
            }
            
            // Validate expiry date
            if (expiry) {
                const expiryVal = expiry.value.trim();
                if (!expiryVal || !/^\d{2}\/\d{2}$/.test(expiryVal)) {
                    showCardError(expiry, 'Enter a valid expiry date (MM/YY).');
                    isValid = false;
                } else {
                    const [month, year] = expiryVal.split('/');
                    const expDate = new Date(2000 + parseInt(year), parseInt(month), 0);
                    if (expDate < new Date()) {
                        showCardError(expiry, 'Card has expired.');
                        isValid = false;
                    }
                }
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
}

/**
 * Show validation error for card fields
 */
function showCardError(input, message) {
    input.classList.add('is-invalid');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'form-error';
    errorDiv.textContent = message;
    input.parentNode.appendChild(errorDiv);
}

/**
 * Clear all card validation errors
 */
function clearCardErrors() {
    document.querySelectorAll('#card_payment_form .is-invalid').forEach(el => {
        el.classList.remove('is-invalid');
    });
    document.querySelectorAll('#card_payment_form .form-error').forEach(el => {
        el.remove();
    });
}

// Initialize card validation when DOM is ready
document.addEventListener('DOMContentLoaded', initCardValidation);
