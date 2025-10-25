import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["canvas", "clearButton", "acceptCheckbox", "signButton", "signatureData", "form"]
    
    connect() {
        this.isDrawing = false
        this.hasSignature = false
        
        // Configuration du canvas
        this.ctx = this.canvasTarget.getContext('2d')
        this.setupCanvas()
        this.setupEventListeners()
        
        // Redimensionner le canvas au chargement et lors du redimensionnement
        this.resizeCanvas()
        window.addEventListener('resize', () => this.resizeCanvas())
    }
    
    disconnect() {
        window.removeEventListener('resize', () => this.resizeCanvas())
    }
    
    setupCanvas() {
        this.ctx.strokeStyle = '#1e293b'
        this.ctx.lineWidth = 2
        this.ctx.lineCap = 'round'
        this.ctx.lineJoin = 'round'
    }
    
    setupEventListeners() {
        // Events souris
        this.canvasTarget.addEventListener('mousedown', (e) => this.startDrawing(e))
        this.canvasTarget.addEventListener('mousemove', (e) => this.draw(e))
        this.canvasTarget.addEventListener('mouseup', () => this.stopDrawing())
        this.canvasTarget.addEventListener('mouseout', () => this.stopDrawing())
        
        // Events tactiles
        this.canvasTarget.addEventListener('touchstart', (e) => {
            e.preventDefault()
            this.startDrawing(e)
        })
        this.canvasTarget.addEventListener('touchmove', (e) => {
            e.preventDefault()
            this.draw(e)
        })
        this.canvasTarget.addEventListener('touchend', (e) => {
            e.preventDefault()
            this.stopDrawing()
        })
    }
    
    resizeCanvas() {
        const rect = this.canvasTarget.getBoundingClientRect()
        this.canvasTarget.width = rect.width
        this.canvasTarget.height = rect.height
        this.setupCanvas() // Reconfigurez le style après redimensionnement
    }
    
    startDrawing(e) {
        this.isDrawing = true
        this.ctx.beginPath()
        this.draw(e)
    }
    
    draw(e) {
        if (!this.isDrawing) return
        
        const rect = this.canvasTarget.getBoundingClientRect()
        const x = (e.clientX || e.touches[0].clientX) - rect.left
        const y = (e.clientY || e.touches[0].clientY) - rect.top
        
        this.ctx.lineTo(x, y)
        this.ctx.stroke()
        
        this.hasSignature = true
        this.updateSignButton()
    }
    
    stopDrawing() {
        if (this.isDrawing) {
            this.isDrawing = false
            this.ctx.beginPath()
        }
    }
    
    clear() {
        this.ctx.clearRect(0, 0, this.canvasTarget.width, this.canvasTarget.height)
        this.hasSignature = false
        this.updateSignButton()
    }
    
    acceptTermsChanged() {
        this.updateSignButton()
    }
    
    updateSignButton() {
        this.signButtonTarget.disabled = !this.acceptCheckboxTarget.checked || !this.hasSignature
    }
    
    submitForm(e) {
        e.preventDefault()
        
        if (!this.hasSignature || !this.acceptCheckboxTarget.checked) {
            alert('Veuillez signer le contrat et accepter les conditions.')
            return
        }
        
        // Préparer les données de signature
        this.signatureDataTarget.value = this.canvasTarget.toDataURL()
        
        // Désactiver le bouton pendant l'envoi
        this.signButtonTarget.disabled = true
        this.signButtonTarget.textContent = 'Signature en cours...'
        
        // Créer FormData avec les données du formulaire
        const formData = new FormData(this.formTarget)
        
        // Envoyer via AJAX
        fetch(this.formTarget.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Succès - rediriger vers la page de succès
                if (data.redirectUrl) {
                    window.location.href = data.redirectUrl
                } else {
                    // Fallback si pas d'URL de redirection
                    alert(data.message)
                    window.location.reload()
                }
            } else {
                // Erreur - afficher le message
                alert(data.message || 'Une erreur est survenue lors de la signature.')
                this.signButtonTarget.disabled = false
                this.signButtonTarget.textContent = 'Signer le contrat'
            }
        })
        .catch(error => {
            console.error('Erreur lors de la signature:', error)
            alert('Une erreur technique est survenue. Veuillez réessayer.')
            this.signButtonTarget.disabled = false
            this.signButtonTarget.textContent = 'Signer le contrat'
        })
    }
}
