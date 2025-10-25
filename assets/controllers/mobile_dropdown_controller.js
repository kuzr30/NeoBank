import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["submenu", "chevron"]

    toggle() {
        const isOpen = this.submenuTarget.classList.contains('active')
        
        // Close all other dropdowns first
        this.closeOtherDropdowns()
        
        if (isOpen) {
            this.close()
        } else {
            this.open()
        }
    }

    open() {
        this.submenuTarget.classList.add('active')
        if (this.hasChevronTarget) {
            this.chevronTarget.style.transform = 'rotate(180deg)'
        }
    }

    close() {
        this.submenuTarget.classList.remove('active')
        if (this.hasChevronTarget) {
            this.chevronTarget.style.transform = 'rotate(0deg)'
        }
    }

    closeOtherDropdowns() {
        // Find all other mobile dropdown controllers and close them
        const mobileMenu = this.element.closest('.mobile-menu')
        if (mobileMenu) {
            const otherDropdowns = mobileMenu.querySelectorAll('[data-controller*="mobile-dropdown"]')
            otherDropdowns.forEach(dropdown => {
                if (dropdown !== this.element && dropdown.controller) {
                    dropdown.controller.close()
                }
            })
        }
    }
}
