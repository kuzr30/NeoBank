import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = []

    connect() {
        // Initialiser le système de notifications
        this.initNotificationSystem()
        this.processNotifications()
    }

    initNotificationSystem() {
        // Créer l'API globale de notifications
        window.notify = {
            success: (message, options = {}) => this.show('success', message, { timeout: 5000, ...options }),
            error: (message, options = {}) => this.show('error', message, { timeout: 0, ...options }),
            warning: (message, options = {}) => this.show('warning', message, { timeout: 5000, ...options }),
            info: (message, options = {}) => this.show('info', message, { timeout: 5000, ...options }),
            show: (type, message, options = {}) => this.show(type, message, options)
        }

        // Alias pour compatibilité
        window.notyf = window.notify
        window.flasher = window.notify
    }

    processNotifications() {
        // Traiter toutes les notifications en attente
        const notificationData = document.querySelectorAll('.notification-data')
        
        notificationData.forEach(data => {
            const type = data.dataset.type
            const message = data.dataset.message
            const title = data.dataset.title
            const timeout = parseInt(data.dataset.timeout) || 5000
            const content = data.innerHTML.trim()

            if (message) {
                this.show(type, message, { timeout })
            } else if (title && content) {
                this.show(type, title, { timeout, content })
            }

            // Supprimer l'élément de données
            data.remove()
        })
    }

    show(type, message, options = {}) {
        const notification = this.createNotification(type, message, options)
        this.element.appendChild(notification)

        // Auto-hide si timeout > 0
        if (options.timeout > 0) {
            setTimeout(() => {
                this.hide(notification)
            }, options.timeout)
        }

        return notification
    }

    createNotification(type, message, options = {}) {
        const notification = document.createElement('div')
        notification.className = `notification notification--${type}`
        
        if (options.timeout > 0) {
            notification.classList.add('notification--with-progress')
            notification.style.setProperty('--progress-duration', options.timeout + 'ms')
        }

        const iconName = this.getIconName(type)
        
        notification.innerHTML = `
            <div class="notification__icon">${this.getIconSVG(iconName)}</div>
            <div class="notification__content">
                ${options.title ? `<div class="notification__title">${options.title}</div>` : ''}
                <div class="notification__message">${message}</div>
                ${options.content ? `<div class="notification__content-extra">${options.content}</div>` : ''}
            </div>
            <button class="notification__close" type="button">
                ${this.getIconSVG('x-mark')}
            </button>
        `

        // Ajouter l'événement de fermeture
        notification.querySelector('.notification__close').addEventListener('click', () => {
            this.hide(notification)
        })

        return notification
    }

    hide(notification) {
        notification.classList.add('notification--removing')
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove()
            }
        }, 300)
    }

    getIconName(type) {
        const iconMap = {
            'success': 'check-circle',
            'error': 'exclamation-triangle',
            'warning': 'exclamation-triangle',
            'info': 'information-circle'
        }
        return iconMap[type] || 'information-circle'
    }

    getIconSVG(name) {
        const iconMap = {
            'check-circle': `<svg viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>`,
            'exclamation-triangle': `<svg viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd" /></svg>`,
            'information-circle': `<svg viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm8.706-1.442c1.146-.573 2.437.463 2.126 1.706l-.709 2.836.042-.02a.75.75 0 01.67 1.34l-.04.022c-1.147.573-2.438-.463-2.127-1.706l.71-2.836-.042.02a.75.75 0 11-.67-1.34l.04-.022zM12 9a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd" /></svg>`,
            'x-mark': `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>`
        }
        return iconMap[name] || iconMap['information-circle']
    }
}
