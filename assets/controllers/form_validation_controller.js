import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["passwordConfirm"]
    
    connect() {
        console.log("Form validation controller connected")
        this.setupValidation()
    }
    
    setupValidation() {
        // Ajouter la classe d'erreur aux inputs avec erreurs existantes (soumission du formulaire)
        const errorElements = document.querySelectorAll('.form-error-wrapper')
        errorElements.forEach((errorEl) => {
            if (errorEl.textContent.trim()) {
                const formGroup = errorEl.closest('.form-group')
                if (formGroup) {
                    const input = formGroup.querySelector('.form-input')
                    const wrapper = formGroup.querySelector('.form-input-wrapper')
                    
                    if (input) {
                        input.classList.add('is-invalid')
                    }
                    if (wrapper) {
                        wrapper.classList.add('has-error')
                    }
                }
            }
        })
        
        // Gestion des inputs - supprimer les erreurs lors de la saisie
        const inputs = this.element.querySelectorAll('.form-input')
        inputs.forEach((input) => {
            // Marquer comme "touché" lors du premier focus
            input.addEventListener('focus', () => {
                input.setAttribute('data-touched', 'true')
            })
            
            // Supprimer les erreurs lors de la saisie
            input.addEventListener('input', () => {
                this.clearFieldErrors(input)
            })
            
            // Validation lors de la perte de focus (blur) seulement si le champ a été touché
            input.addEventListener('blur', () => {
                if (input.getAttribute('data-touched') === 'true') {
                    this.validateField(input)
                }
            })
        })
        
        // Validation des mots de passe
        this.setupPasswordValidation()
    }
    
    setupPasswordValidation() {
        const password1 = document.getElementById('password1')
        const password2 = document.getElementById('password2')
        
        if (password1 && password2) {
            const validatePasswords = () => {
                const pass1 = password1.value
                const pass2 = password2.value
                
                // Ne valider que si le deuxième champ a été touché et contient du texte
                if (password2.getAttribute('data-touched') === 'true' && pass1 && pass2 && pass1 !== pass2) {
                    password2.classList.add('is-invalid')
                    password2.closest('.form-input-wrapper')?.classList.add('has-error')
                    
                    // Ajouter un message d'erreur s'il n'existe pas
                    let errorMsg = document.querySelector('.password-mismatch-error')
                    if (!errorMsg) {
                        errorMsg = document.createElement('div')
                        errorMsg.className = 'form-error-wrapper password-mismatch-error'
                        errorMsg.textContent = 'Les mots de passe ne correspondent pas'
                        password2.parentElement.parentElement.appendChild(errorMsg)
                    }
                } else {
                    password2.classList.remove('is-invalid')
                    password2.closest('.form-input-wrapper')?.classList.remove('has-error')
                    
                    // Supprimer le message d'erreur de correspondance
                    const errorMsg = document.querySelector('.password-mismatch-error')
                    if (errorMsg) {
                        errorMsg.remove()
                    }
                }
            }
            
            password1.addEventListener('input', validatePasswords)
            password2.addEventListener('input', validatePasswords)
            password2.addEventListener('blur', validatePasswords)
        }
    }
    
    clearFieldErrors(input) {
        input.classList.remove('is-invalid')
        const wrapper = input.closest('.form-input-wrapper')
        if (wrapper) {
            wrapper.classList.remove('has-error')
        }
        
        // Supprimer les messages d'erreur personnalisés
        const customErrors = input.closest('.form-group').querySelectorAll('.password-mismatch-error')
        customErrors.forEach(err => err.remove())
    }
    
    validateField(input) {
        const isRequired = input.hasAttribute('required')
        const isEmpty = !input.value.trim()
        
        if (isRequired && isEmpty) {
            input.classList.add('is-invalid')
            const wrapper = input.closest('.form-input-wrapper')
            if (wrapper) {
                wrapper.classList.add('has-error')
            }
        }
    }
}
