import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['form', 'submitButton', 'fileInput', 'filePreview'];
    static values = {
        uploadingText: String,
        defaultText: String
    }

    connect() {
        this.defaultButtonText = this.submitButtonTarget.innerHTML;
    }

    async submitForm(event) {
        event.preventDefault();

        // Désactiver le bouton et montrer l'état de chargement
        this.submitButtonTarget.disabled = true;
        this.submitButtonTarget.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Envoi en cours...';

        // Vérifier si au moins un fichier a été sélectionné
        const hasFiles = Array.from(this.fileInputTargets).some(input => input.files.length > 0);
        
        if (!hasFiles) {
            this.showError('Veuillez sélectionner au moins un document');
            this.resetButton();
            return;
        }

        try {
            const formData = new FormData(this.formTarget);
            const response = await fetch(this.formTarget.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (response.ok) {
                // Redirection en cas de succès
                window.location.href = result.redirect;
            } else {
                this.showError(result.message || 'Une erreur est survenue');
                this.resetButton();
            }
        } catch (error) {
            console.error('Erreur lors de la soumission:', error);
            this.showError('Une erreur est survenue lors de l\'envoi des documents');
            this.resetButton();
        }
    }

    showError(message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger mt-3';
        alertDiv.role = 'alert';
        alertDiv.innerHTML = message;
        
        // Insérer l'alerte au début du formulaire
        this.formTarget.insertBefore(alertDiv, this.formTarget.firstChild);
        
        // Faire défiler jusqu'à l'erreur
        alertDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
        
        // Supprimer l'alerte après 5 secondes
        setTimeout(() => alertDiv.remove(), 5000);
    }

    resetButton() {
        this.submitButtonTarget.disabled = false;
        this.submitButtonTarget.innerHTML = this.defaultButtonText;
    }

    // Prévisualisation des fichiers
    previewFile(event) {
        const input = event.target;
        const previewId = input.dataset.previewTarget;
        const preview = document.getElementById(previewId);
        
        if (input.files && input.files[0]) {
            const file = input.files[0];
            preview.textContent = `${file.name} (${this.formatFileSize(file.size)})`;
            preview.classList.add('kyc-submit__file-preview--visible');
        } else {
            preview.textContent = '';
            preview.classList.remove('kyc-submit__file-preview--visible');
        }
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
}
