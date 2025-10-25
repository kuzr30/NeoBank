import { Controller } from "@hotwired/stimulus"

// Connects to data-controller="dropdown"
export default class extends Controller {
  static targets = ["menu"]

  connect() {
    this.isOpen = false
    // Close dropdown when clicking outside
    document.addEventListener('click', this.closeOnOutsideClick.bind(this))
  }

  disconnect() {
    document.removeEventListener('click', this.closeOnOutsideClick.bind(this))
  }

  toggle(event) {
    event.preventDefault()
    event.stopPropagation()
    
    if (this.isOpen) {
      this.close()
    } else {
      this.open()
    }
  }

  open() {
    this.isOpen = true
    this.element.setAttribute('data-dropdown-open', '')
  }

  close() {
    this.isOpen = false
    this.element.removeAttribute('data-dropdown-open')
  }

  closeOnOutsideClick(event) {
    if (this.isOpen && !this.element.contains(event.target)) {
      this.close()
    }
  }
}
