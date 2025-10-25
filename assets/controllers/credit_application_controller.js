import { Controller } from "@hotwired/stimulus"

// Connects to data-controller="credit-application"
export default class extends Controller {
    static targets = [
        "form",
        "employmentType", 
        "employmentDetails",
        "monthlyIncome",
        "monthlyExpenses", 
        "existingLoans",
        "capacityInfo",
        "loanAmount",
        "creditType", 
        "duration",
        "monthlyPayment",
        "totalCost",
        "simulationResults",
        "creditDescription",
        "rateDisplay",
        "descriptionDisplay",
        "monthlyPaymentDisplay",
        "loanAmountDisplay",
        "totalInterestsDisplay",
        "payoffDateDisplay"
    ]

    static values = {
        currentStep: Number,
        apiUrl: String
    }

    connect() {
        console.log("Credit Application Controller Connected")
        this.currentStep = this.currentStepValue || 1
        this.initializeTranslations()
        this.setupEventListeners()
        this.updateFormState()
    }

    initializeTranslations() {
        // Récupérer les traductions depuis les attributs data du formulaire
        this.translations = {
            'validation.required': this.element.dataset.translationRequired || 'Ce champ est obligatoire',
            'validation.email': this.element.dataset.translationEmail || 'Email invalide',
            'validation.phone': this.element.dataset.translationPhone || 'Numéro de téléphone invalide',
            'validation.postal': this.element.dataset.translationPostal || 'Code postal invalide',
            'validation.min': this.element.dataset.translationMin || 'Valeur minimum : %value%',
            'validation.max': this.element.dataset.translationMax || 'Valeur maximum : %value%'
        }
    }

    getTranslation(key) {
        return this.translations[key] || key
    }

    setupEventListeners() {
        // Step 3: Employment type change (maintenant situation financière)
        if (this.hasEmploymentTypeTarget) {
            this.employmentTypeTarget.addEventListener('change', () => {
                this.toggleEmploymentDetails()
            })
            this.toggleEmploymentDetails() // Initial state
        }

        // Step 3: Financial capacity calculation (maintenant situation financière)
        if (this.hasMonthlyIncomeTarget && this.hasMonthlyExpensesTarget) {
            this.monthlyIncomeTarget.addEventListener('input', () => {
                this.calculateCapacity()
            })
            this.monthlyExpensesTarget.addEventListener('input', () => {
                this.calculateCapacity()
            })
            if (this.hasExistingLoansTarget) {
                this.existingLoansTarget.addEventListener('input', () => {
                    this.calculateCapacity()
                })
            }
        }

        // Step 3: Credit calculation (maintenant Step 1)
        if (this.currentStep === 1) {
            if (this.hasLoanAmountTarget) {
                this.loanAmountTarget.addEventListener('input', () => {
                    this.debounce(this.calculateLoan.bind(this), 500)()
                })
            }
            if (this.hasCreditTypeTarget) {
                this.creditTypeTarget.addEventListener('change', () => {
                    this.updateCreditTypeInfo()
                    this.calculateLoan()
                })
                this.updateCreditTypeInfo() // Initial state
            }
            if (this.hasDurationTarget) {
                this.durationTarget.addEventListener('input', () => {
                    this.debounce(this.calculateLoan.bind(this), 500)()
                })
            }
        }

        // Form validation
        this.element.addEventListener('submit', (event) => {
            if (!this.validateCurrentStep()) {
                event.preventDefault()
                this.showValidationErrors()
            }
        })

        // Real-time validation
        this.setupRealTimeValidation()
    }

    updateFormState() {
        // Set loading state
        this.element.classList.remove('credit-form--loading')
        
        // Update progress indicators
        this.updateProgress()
        
        // Ne pas initialiser automatiquement les calculs au chargement
        // Les calculs se déclencheront lors de la saisie utilisateur
    }

    // Step 3 Methods (maintenant situation financière)
    toggleEmploymentDetails() {
        if (!this.hasEmploymentTypeTarget) return

        const selectedType = this.employmentTypeTarget.value
        const detailsElements = document.querySelectorAll('[data-target="employment-details"]')
        
        // Show employment details for certain types
        const showDetails = ['employee', 'civil_servant', 'freelancer', 'entrepreneur'].includes(selectedType)
        
        detailsElements.forEach(element => {
            element.style.display = showDetails ? 'block' : 'none'
            
            // Update required state of fields inside
            const inputs = element.querySelectorAll('input, select')
            inputs.forEach(input => {
                if (showDetails) {
                    input.removeAttribute('disabled')
                } else {
                    input.setAttribute('disabled', 'disabled')
                    input.value = ''
                }
            })
        })
    }

    calculateCapacity() {
        if (!this.hasMonthlyIncomeTarget || !this.hasMonthlyExpensesTarget) return

        const income = this.parseMoneyValue(this.monthlyIncomeTarget.value)
        const expenses = this.parseMoneyValue(this.monthlyExpensesTarget.value)
        const existingLoans = this.hasExistingLoansTarget ? 
            this.parseMoneyValue(this.existingLoansTarget.value) : 0

        if (income <= 0) return

        // Calculate debt ratio (should not exceed 33%)
        const totalCharges = expenses + existingLoans
        const debtRatio = (totalCharges / income) * 100
        const remainingCapacity = income - totalCharges

        // Update display
        const capacityInfo = document.querySelector('[data-target="capacity-info"]')
        if (capacityInfo) {
            capacityInfo.style.display = 'block'
            
            const debtRatioElement = document.querySelector('[data-capacity="debt-ratio"]')
            const remainingElement = document.querySelector('[data-capacity="remaining"]')
            
            if (debtRatioElement) {
                debtRatioElement.textContent = `${debtRatio.toFixed(1)}%`
                debtRatioElement.className = debtRatio > 33 ? 'text-red-600' : 'text-green-600'
            }
            
            if (remainingElement) {
                remainingElement.textContent = `${remainingCapacity.toLocaleString('fr-FR')} €`
                remainingElement.className = remainingCapacity > 0 ? 'text-green-600' : 'text-red-600'
            }
        }
    }

    // Step 3 Methods
    updateCreditTypeInfo() {
        if (!this.hasCreditTypeTarget) return

        const selectedOption = this.creditTypeTarget.selectedOptions[0]
        if (!selectedOption) return

        const rate = selectedOption.dataset.rate
        const description = selectedOption.dataset.description

        const descriptionElement = document.querySelector('[data-target="credit-description"]')
        if (descriptionElement && rate && description) {
            descriptionElement.style.display = 'block'
            
            const rateDisplay = document.querySelector('[data-rate-display]')
            const descDisplay = document.querySelector('[data-description-display]')
            
            if (rateDisplay) rateDisplay.textContent = rate
            if (descDisplay) descDisplay.textContent = description
        }

        // Update TAEG in simulation card
        if (this.hasPayoffDateDisplayTarget && rate) {
            this.payoffDateDisplayTarget.textContent = `${rate}%`
        }
    }

    async calculateLoan() {
        if (!this.hasLoanAmountTarget || !this.hasCreditTypeTarget || !this.hasDurationTarget) {
            console.log('Targets not available yet, skipping calculation')
            return
        }

        // Vérification que tous les champs sont remplis avant calcul
        const amountValue = this.loanAmountTarget.value.trim()
        const creditTypeValue = this.creditTypeTarget.value.trim()
        const durationValue = this.durationTarget.value.trim()

        // Si un des champs est vide, on cache les résultats sans erreur
        if (!amountValue || !creditTypeValue || !durationValue) {
            this.clearResults()
            return
        }

        const amount = this.parseMoneyValue(amountValue)
        const creditType = creditTypeValue
        const duration = parseInt(durationValue)

        console.log('Calculation values:', { amount, creditType, duration })

        // Validation des valeurs minimales
        if (amount < 500 || duration < 2) {
            this.clearResults()
            return
        }

        try {
            this.setLoading(true)

            // Construire l'URL API basée sur le chemin actuel
            const currentPath = window.location.pathname
            const basePath = currentPath.split('/').slice(0, 3).join('/') // /{locale}/{credit-path}
            const apiUrl = `${basePath}/api/calculate`

            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    loanAmount: amount,
                    creditType: creditType,
                    duration: duration
                })
            })

            if (!response.ok) {
                throw new Error('Erreur de calcul')
            }

            const data = await response.json()
            this.updateResults(data)

        } catch (error) {
            console.error('Erreur lors du calcul:', error)
            this.showError('Erreur lors du calcul du crédit')
        } finally {
            this.setLoading(false)
        }
    }

    calculate() {
        // Manual calculation trigger
        if (this.currentStep === 1) {
            this.calculateLoan()
        } else if (this.currentStep === 3) {
            this.calculateCapacity()
        }
    }

    updateResults(data) {
        // Update form fields (hidden)
        if (this.hasMonthlyPaymentTarget && data.monthlyPayment) {
            this.monthlyPaymentTarget.value = `${data.monthlyPayment} €`
        }
        
        if (this.hasTotalCostTarget && data.totalCost) {
            this.totalCostTarget.value = `${data.totalCost} €`
        }

        // Update card display elements with French formatting
        if (this.hasMonthlyPaymentDisplayTarget && data.monthlyPayment) {
            this.monthlyPaymentDisplayTarget.textContent = data.monthlyPayment.toLocaleString('fr-FR')
        }
        
        if (this.hasLoanAmountDisplayTarget && data.loanAmount) {
            this.loanAmountDisplayTarget.textContent = `${data.loanAmount.toLocaleString('fr-FR')} €`
        }
        
        if (this.hasTotalInterestsDisplayTarget && data.totalCost && data.loanAmount) {
            const interests = data.totalCost - data.loanAmount
            this.totalInterestsDisplayTarget.textContent = `${interests.toLocaleString('fr-FR')} €`
        }
        
        // Keep TAEG from credit type, don't overwrite with date
        if (this.hasPayoffDateDisplayTarget && this.hasCreditTypeTarget) {
            const selectedOption = this.creditTypeTarget.selectedOptions[0]
            if (selectedOption && selectedOption.dataset.rate) {
                this.payoffDateDisplayTarget.textContent = `${selectedOption.dataset.rate}%`
            }
        }

        // Show results section with animation
        if (this.hasSimulationResultsTarget) {
            this.simulationResultsTarget.style.display = 'block'
        }
    }

    clearResults() {
        // Clear hidden form fields
        if (this.hasMonthlyPaymentTarget) {
            this.monthlyPaymentTarget.value = ''
        }
        if (this.hasTotalCostTarget) {
            this.totalCostTarget.value = ''
        }

        // Clear display elements with French defaults
        if (this.hasMonthlyPaymentDisplayTarget) {
            this.monthlyPaymentDisplayTarget.textContent = '0'
        }
        if (this.hasLoanAmountDisplayTarget) {
            this.loanAmountDisplayTarget.textContent = '0 €'
        }
        if (this.hasTotalInterestsDisplayTarget) {
            this.totalInterestsDisplayTarget.textContent = '0 €'
        }
        if (this.hasPayoffDateDisplayTarget) {
            this.payoffDateDisplayTarget.textContent = '-%'
        }

        // Hide results section
        if (this.hasSimulationResultsTarget) {
            this.simulationResultsTarget.style.display = 'none'
        }
    }

    // Validation Methods
    validateCurrentStep() {
        const requiredFields = this.element.querySelectorAll('[data-validation*="required"]')
        let isValid = true

        requiredFields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false
            }
        })

        return isValid
    }

    validateField(field) {
        const validation = field.dataset.validation
        const value = field.value.trim()

        // Clear previous errors
        this.clearFieldError(field)

        if (validation.includes('required') && !value) {
            this.showFieldError(field, this.getTranslation('validation.required'))
            return false
        }

        if (validation.includes('email') && value && !this.isValidEmail(value)) {
            this.showFieldError(field, this.getTranslation('validation.email'))
            return false
        }

        if (validation.includes('phone') && value && !this.isValidPhone(value)) {
            this.showFieldError(field, this.getTranslation('validation.phone'))
            return false
        }

        if (validation.includes('postal') && value && !this.isValidPostalCode(value)) {
            this.showFieldError(field, this.getTranslation('validation.postal'))
            return false
        }

        const minMatch = validation.match(/min:(\d+)/)
        if (minMatch && value && parseFloat(value) < parseFloat(minMatch[1])) {
            this.showFieldError(field, this.getTranslation('validation.min').replace('%value%', minMatch[1]))
            return false
        }

        const maxMatch = validation.match(/max:(\d+)/)
        if (maxMatch && value && parseFloat(value) > parseFloat(maxMatch[1])) {
            this.showFieldError(field, this.getTranslation('validation.max').replace('%value%', maxMatch[1]))
            return false
        }

        return true
    }

    setupRealTimeValidation() {
        const validatedFields = this.element.querySelectorAll('[data-validation]')
        
        validatedFields.forEach(field => {
            field.addEventListener('blur', () => {
                this.validateField(field)
            })
            
            field.addEventListener('input', () => {
                this.clearFieldError(field)
            })
        })
    }

    showFieldError(field, message) {
        field.classList.add('credit-form__input--error')
        
        let errorElement = field.parentNode.querySelector('.credit-form__error')
        if (!errorElement) {
            errorElement = document.createElement('div')
            errorElement.className = 'credit-form__error'
            field.parentNode.appendChild(errorElement)
        }
        errorElement.textContent = message
    }

    clearFieldError(field) {
        field.classList.remove('credit-form__input--error')
        const errorElement = field.parentNode.querySelector('.credit-form__error')
        if (errorElement) {
            errorElement.remove()
        }
    }

    showValidationErrors() {
        this.element.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }

    // Utility Methods
    parseMoneyValue(value) {
        if (!value) return 0
        return parseFloat(value.toString().replace(/[^\d.-]/g, '')) || 0
    }

    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
        return emailRegex.test(email)
    }

    isValidPhone(phone) {
        const phoneRegex = /^\+?[0-9\s\-]{5,15}$/
        return phoneRegex.test(phone)
    }

    isValidPostalCode(postal) {
        const postalRegex = /^[a-zA-Z0-9]{1,7}$/
        return postalRegex.test(postal)
    }

    setLoading(loading) {
        if (loading) {
            this.element.classList.add('credit-form--loading')
        } else {
            this.element.classList.remove('credit-form--loading')
        }
    }

    showError(message) {
        // You could integrate with a toast notification system here
        alert(message)
    }

    updateProgress() {
        const progressBar = document.querySelector('.progress-bar__fill')
        if (progressBar) {
            const percentage = (this.currentStep / 3) * 100
            progressBar.style.width = `${percentage}%`
        }
    }

    debounce(func, wait) {
        let timeout
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout)
                func(...args)
            }
            clearTimeout(timeout)
            timeout = setTimeout(later, wait)
        }
    }
}
