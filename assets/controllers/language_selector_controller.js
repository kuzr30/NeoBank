import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["button", "menu", "arrow"]

    connect() {
        // Close menu on outside click
        this.outsideClickHandler = this.handleOutsideClick.bind(this)
        document.addEventListener("click", this.outsideClickHandler)
        
        // Close menu on escape key
        this.escapeHandler = this.handleEscapeKey.bind(this)
        document.addEventListener("keydown", this.escapeHandler)
        
        // Close menu on Turbo navigation
        document.addEventListener("turbo:before-visit", this.close.bind(this))
    }

    disconnect() {
        document.removeEventListener("click", this.outsideClickHandler)
        document.removeEventListener("keydown", this.escapeHandler)
        document.removeEventListener("turbo:before-visit", this.close.bind(this))
    }

    toggle(event) {
        event.preventDefault()
        event.stopPropagation()
        
        const isOpen = this.buttonTarget.getAttribute('aria-expanded') === 'true'
        
        if (isOpen) {
            this.close()
        } else {
            this.open()
        }
    }

    open() {
        this.buttonTarget.setAttribute('aria-expanded', 'true')
        this.menuTarget.classList.add('show')
        
        // Rotate arrow
        if (this.hasArrowTarget) {
            this.arrowTarget.style.transform = 'rotate(180deg)'
        }
    }

    close() {
        this.buttonTarget.setAttribute('aria-expanded', 'false')
        this.menuTarget.classList.remove('show')
        
        // Reset arrow
        if (this.hasArrowTarget) {
            this.arrowTarget.style.transform = 'rotate(0deg)'
        }
    }

    select(event) {
        // Optional: add loading state or analytics before navigation
        this.close()
        
        // Let the link handle the navigation naturally
        // The href will trigger the language switch
    }

    handleOutsideClick(event) {
        if (!this.element.contains(event.target)) {
            this.close()
        }
    }

    handleEscapeKey(event) {
        if (event.key === 'Escape') {
            this.close()
        }
    }
}
