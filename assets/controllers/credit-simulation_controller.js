import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
  static targets = [
    "creditType", 
    "amount", 
    "duration", 
    "rateDisplay",
    "monthlyPayment",
    "loanAmount",
    "monthlyDetail",
    "insuranceAmount",
    "interestAmount",
    "totalCost",
    "totalInsurance",
    "totalInterest",
    "hiddenAmount",
    "hiddenDuration",
    "hiddenCreditType"
  ]

  static values = {
    locale: String,
    perMonth: String
  }

  connect() {
    this.updateCalculation()
  }

  updateRate() {
    const selectedOption = this.creditTypeTarget.selectedOptions[0]
    const rate = selectedOption.dataset.rate
    const creditType = selectedOption.value
    
    if (rate && this.hasRateDisplayTarget) {
      this.rateDisplayTarget.textContent = `${rate}%`
    }

    // Mettre à jour le champ caché du type de crédit
    if (this.hasHiddenCreditTypeTarget) {
      this.hiddenCreditTypeTarget.value = creditType
    }
    
    this.updateCalculation()
  }

  updateCalculation() {
    try {
      // Récupérer les valeurs
      const amount = this.parseAmount(this.amountTarget.value)
      const duration = parseInt(this.durationTarget.value) || 0
      const selectedOption = this.creditTypeTarget.selectedOptions[0]
      const rate = parseFloat(selectedOption.dataset.rate) || 0
      const creditType = selectedOption.value

      // Mettre à jour les champs cachés pour le formulaire
      if (this.hasHiddenAmountTarget) this.hiddenAmountTarget.value = amount
      if (this.hasHiddenDurationTarget) this.hiddenDurationTarget.value = duration
      if (this.hasHiddenCreditTypeTarget) this.hiddenCreditTypeTarget.value = creditType

      // Valider les données
      if (!amount || amount <= 0 || !duration || duration <= 0 || !rate) {
        this.clearResults()
        return
      }

      // Durée en mois seulement
      const durationInMonths = duration

      // Calculer la mensualité
      const monthlyRate = rate / 100 / 12
      const monthlyPayment = (amount * monthlyRate * Math.pow(1 + monthlyRate, durationInMonths)) / 
                            (Math.pow(1 + monthlyRate, durationInMonths) - 1)

      // Calculer le coût total
      const totalCost = monthlyPayment * durationInMonths

      // Calculer l'assurance (0.34% du capital par an)
      const monthlyInsurance = (amount * 0.0034) / 12
      const totalInsurance = monthlyInsurance * durationInMonths

      // Calculer les intérêts (montant total payé - capital emprunté - assurance)
      const totalInterest = totalCost - amount - totalInsurance
      const monthlyInterest = monthlyPayment - monthlyInsurance - (amount / durationInMonths)

      // Afficher les résultats
      this.displayResults({
        amount,
        monthlyPayment,
        monthlyInsurance,
        monthlyInterest,
        totalCost,
        totalInsurance,
        totalInterest
      })

    } catch (error) {
      this.clearResults()
    }
  }

  parseAmount(value) {
    if (!value) return 0
    const cleanValue = value.toString().replace(/[^\d.,]/g, '').replace(',', '.')
    return parseFloat(cleanValue) || 0
  }

  formatCurrency(amount) {
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'EUR',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(amount)
  }

  formatNumber(number) {
    return new Intl.NumberFormat('fr-FR', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(number)
  }

  displayResults({ amount, monthlyPayment, monthlyInsurance, monthlyInterest, totalCost, totalInsurance, totalInterest }) {
    // Montant principal
    if (this.hasMonthlyPaymentTarget) {
      this.monthlyPaymentTarget.querySelector('.simulator__amount-value').textContent = this.formatNumber(monthlyPayment)
    }

    // Détails
    if (this.hasLoanAmountTarget) {
      this.loanAmountTarget.textContent = this.formatCurrency(amount)
    }

    if (this.hasMonthlyDetailTarget) {
      const perMonth = this.perMonthValue || '/mois'
      this.monthlyDetailTarget.textContent = `${this.formatNumber(monthlyPayment)}€${perMonth}`
    }

    if (this.hasInsuranceAmountTarget) {
      this.insuranceAmountTarget.textContent = `${this.formatNumber(monthlyInsurance)}€`
    }

    if (this.hasInterestAmountTarget) {
      this.interestAmountTarget.textContent = `${this.formatNumber(monthlyInterest)}€`
    }

    if (this.hasTotalCostTarget) {
      this.totalCostTarget.textContent = this.formatCurrency(totalCost)
    }

    if (this.hasTotalInsuranceTarget) {
      this.totalInsuranceTarget.textContent = this.formatCurrency(totalInsurance)
    }

    if (this.hasTotalInterestTarget) {
      this.totalInterestTarget.textContent = this.formatCurrency(totalInterest)
    }
  }

  clearResults() {
    if (this.hasMonthlyPaymentTarget) {
      this.monthlyPaymentTarget.querySelector('.simulator__amount-value').textContent = '0,00'
    }
    if (this.hasLoanAmountTarget) this.loanAmountTarget.textContent = '0,00€'
    if (this.hasMonthlyDetailTarget) this.monthlyDetailTarget.textContent = '0,00€'
    if (this.hasInsuranceAmountTarget) this.insuranceAmountTarget.textContent = '0,00€'
    if (this.hasInterestAmountTarget) this.interestAmountTarget.textContent = '0,00€'
    if (this.hasTotalCostTarget) this.totalCostTarget.textContent = '0,00€'
    if (this.hasTotalInsuranceTarget) this.totalInsuranceTarget.textContent = '0,00€'
    if (this.hasTotalInterestTarget) this.totalInterestTarget.textContent = '0,00€'
  }
}