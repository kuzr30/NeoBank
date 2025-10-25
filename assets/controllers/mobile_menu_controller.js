import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["overlay", "menu", "hamburger"]

    connect() {
        // Ensure Turbo doesn't interfere with menu state
        document.addEventListener("turbo:before-visit", this.close.bind(this))
    }

    disconnect() {
        document.removeEventListener("turbo:before-visit", this.close.bind(this))
    }

    toggle() {
        const isOpen = this.menuTarget.classList.contains('active')
        
        if (isOpen) {
            this.close()
        } else {
            this.open()
        }
    }

    open() {
        this.overlayTarget.classList.add('active')
        this.menuTarget.classList.add('active')
        
        // The hamburger target is the button itself, find the hamburger div inside
        const hamburgerDiv = this.hamburgerTarget.querySelector('.hamburger')
        if (hamburgerDiv) {
            hamburgerDiv.classList.add('active')
        }
        
        document.body.style.overflow = 'hidden'
    }

    close() {
        this.overlayTarget.classList.remove('active')
        this.menuTarget.classList.remove('active')
        
        // The hamburger target is the button itself, find the hamburger div inside
        const hamburgerDiv = this.hamburgerTarget.querySelector('.hamburger')
        if (hamburgerDiv) {
            hamburgerDiv.classList.remove('active')
        }
        
        document.body.style.overflow = ''
    }
}
