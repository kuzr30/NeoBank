import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["form", "submitBtn"]
    static values = { 
        submitUrl: String 
    }

    connect() {
        console.log("Card subscription controller connecté")
    }

    async submit(event) {
        event.preventDefault()
        
        console.log("Soumission du formulaire de carte...")
        
        // Désactiver le bouton pour éviter les double-clics
        this.submitBtnTarget.disabled = true
        this.submitBtnTarget.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi en cours...'
        
        try {
            // Récupérer les données du formulaire
            const formData = new FormData(this.formTarget)
            
            console.log("Données du formulaire:", Object.fromEntries(formData))
            
            // Envoyer la requête POST
            const response = await fetch(this.submitUrlValue, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            
            console.log("Réponse reçue:", response.status)
            
            if (response.ok) {
                // Lire la réponse JSON
                const result = await response.json()
                console.log("Données de réponse:", result)
                
                if (result.success) {
                    // Succès - afficher le message et rediriger
                    this.showSuccess(result.message)
                    setTimeout(() => {
                        window.location.href = result.redirect || '/fr/banking/cartes'
                    }, 2000)
                } else {
                    // Erreur métier
                    this.showError(result.message || "Une erreur est survenue.")
                }
            } else {
                // Erreur HTTP
                console.error("Erreur lors de la soumission:", response.status)
                this.showError("Une erreur est survenue lors de l'envoi de votre demande.")
            }
            
        } catch (error) {
            console.error("Erreur réseau:", error)
            this.showError("Erreur de connexion. Veuillez réessayer.")
        } finally {
            // Réactiver le bouton
            this.submitBtnTarget.disabled = false
            this.submitBtnTarget.innerHTML = '<i class="fas fa-paper-plane"></i> Envoyer ma demande'
        }
    }
    
    showError(message) {
        // Afficher un message d'erreur
        const alertDiv = document.createElement('div')
        alertDiv.className = 'alert alert--error'
        alertDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${message}`
        
        // Insérer l'alerte en haut du formulaire
        this.formTarget.insertBefore(alertDiv, this.formTarget.firstChild)
        
        // Supprimer l'alerte après 5 secondes
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.parentNode.removeChild(alertDiv)
            }
        }, 5000)
    }
    
    showSuccess(message) {
        // Afficher un message de succès
        const alertDiv = document.createElement('div')
        alertDiv.className = 'alert alert--success'
        alertDiv.style.cssText = 'background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 1rem; border-radius: 8px; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;'
        alertDiv.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`
        
        // Insérer l'alerte en haut du formulaire
        this.formTarget.insertBefore(alertDiv, this.formTarget.firstChild)
        
        // Supprimer l'alerte après 5 secondes
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.parentNode.removeChild(alertDiv)
            }
        }, 5000)
    }
}
