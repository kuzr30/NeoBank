import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["tab", "content"]
    static values = { 
        activeTab: String,
        baseUrl: String
    }

    connect() {
        // Initialiser avec l'onglet actif au chargement
        this.showActiveTab()
    }

    async switchTab(event) {
        event.preventDefault()
        
        const clickedTab = event.currentTarget
        const tabName = clickedTab.dataset.tab
        
        // Ne pas recharger si c'est déjà l'onglet actif
        if (tabName === this.activeTabValue) {
            return
        }

        // Si on clique sur l'onglet principal (comptes), recharger la page complète pour éviter les problèmes de duplication
        if (clickedTab.dataset.tabType === 'main') {
            window.location.href = this.getCurrentLocaleUrl('/banking/dashboard')
            return
        }

        // Mettre à jour l'état des onglets
        this.updateTabStates(tabName)
        
        // Charger le contenu de l'onglet
        await this.loadTabContent(tabName)
        
        // Mapper les identifiants internes vers les URLs pour l'historique du navigateur  
        const locale = this.getCurrentLocale()
        const tabToUrlMap = {
            'ribs': this.getLocalizedUrl('ribs', locale),
            'comptes': this.getLocalizedUrl('comptes', locale),
            'cartes': this.getLocalizedUrl('cartes', locale),
            'virements': this.getLocalizedUrl('virements', locale),
            'epargne': this.getLocalizedUrl('epargne', locale),
            'credits': this.getLocalizedUrl('credits', locale),
            'assurances': this.getLocalizedUrl('assurances', locale)
        }
        const urlPath = tabToUrlMap[tabName] || tabName
        
        // Mettre à jour l'URL sans recharger la page
        window.history.pushState(null, '', this.getCurrentLocaleUrl(`/banking/${urlPath}`))
        
        this.activeTabValue = tabName
    }

    updateTabStates(activeTabName) {
        this.tabTargets.forEach(tab => {
            if (tab.dataset.tab === activeTabName) {
                tab.classList.add('banking__nav-tab--active')
                tab.classList.remove('banking__nav-tab--inactive')
            } else {
                tab.classList.remove('banking__nav-tab--active')
                tab.classList.add('banking__nav-tab--inactive')
            }
        })
    }

    async loadTabContent(tabName) {
        const contentContainer = this.contentTarget
        const locale = this.getCurrentLocale()
        
        // Mapper les identifiants internes vers les URLs localisées
        const urlPath = this.getLocalizedUrl(tabName, locale)
        
        // Afficher un indicateur de chargement
        contentContainer.innerHTML = this.getLoadingHTML()
        
        try {
            const response = await fetch(this.getCurrentLocaleUrl(`/banking/${urlPath}`), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                }
            })
            
            if (response.ok) {
                const html = await response.text()
                contentContainer.innerHTML = html
                
                // Déclencher un événement personnalisé pour notifier le chargement
                this.dispatch('tabLoaded', { 
                    detail: { 
                        tabName: tabName,
                        content: html 
                    } 
                })
            } else {
                contentContainer.innerHTML = this.getErrorHTML(tabName)
            }
        } catch (error) {
            console.error('Erreur lors du chargement de l\'onglet:', error)
            contentContainer.innerHTML = this.getErrorHTML(tabName)
        }
    }

    showActiveTab() {
        // Déterminer l'onglet actif depuis l'URL avec support de la locale
        const currentPath = window.location.pathname
        // Extraire le chemin après /locale/banking/
        const pathMatch = currentPath.match(/^\/[a-z]{2}\/banking\/(.+)$/)
        let currentTab = pathMatch ? pathMatch[1] : 'comptes'
        
        // Mapper les URLs vers les identifiants internes
        const locale = this.getCurrentLocale()
        const urlToTabMap = this.getUrlToTabMap(locale)
        
        // Utiliser le mapping si disponible, sinon garder la valeur originale
        currentTab = urlToTabMap[currentTab] || currentTab
        
        this.activeTabValue = currentTab
        this.updateTabStates(currentTab)
    }

    getLoadingHTML() {
        return `
            <div class="banking__loading">
                <div class="banking__loading-spinner"></div>
                <p class="banking__loading-text">Chargement en cours...</p>
            </div>
        `
    }

    getErrorHTML(tabName) {
        return `
            <div class="banking__error">
                <div class="banking__error-icon">⚠️</div>
                <h3 class="banking__error-title">Erreur de chargement</h3>
                <p class="banking__error-message">
                    Impossible de charger la section ${this.getTabDisplayName(tabName)}.
                    Veuillez réessayer ultérieurement.
                </p>
                <button class="banking__error-retry" onclick="location.reload()">
                    Réessayer
                </button>
            </div>
        `
    }

    getTabDisplayName(tabName) {
        const displayNames = {
            'comptes': 'Comptes',
            'cartes': 'Cartes',
            'virements': 'Virements', 
            'mes-beneficiaires': 'RIB/IBAN',
            'epargne': 'Épargne',
            'credits': 'Crédits',
            'assurances': 'Assurances'
        }
        return displayNames[tabName] || tabName
    }

    // Méthode pour construire les URLs avec la locale actuelle
    getCurrentLocaleUrl(path) {
        // Récupérer la locale depuis l'URL actuelle
        const currentPath = window.location.pathname
        const localeMatch = currentPath.match(/^\/([a-z]{2})\//)
        const currentLocale = localeMatch ? localeMatch[1] : 'fr'
        
        // Construire l'URL avec la locale
        return `/${currentLocale}${path}`
    }

    // Méthode pour récupérer la locale actuelle
    getCurrentLocale() {
        const currentPath = window.location.pathname
        const localeMatch = currentPath.match(/^\/([a-z]{2})\//)
        return localeMatch ? localeMatch[1] : 'fr'
    }

    // Méthode pour obtenir l'URL localisée pour un onglet
    getLocalizedUrl(tabName, locale) {
        const localizationMap = {
            'ribs': {
                'fr': 'mes-beneficiaires',
                'nl': 'mijn-begunstigden', 
                'en': 'my-beneficiaries',
                'de': 'meine-begunstigten',
                'es': 'mis-beneficiarios'
            },
            'comptes': {
                'fr': 'comptes',
                'nl': 'rekeningen',
                'en': 'accounts',
                'de': 'konten',
                'es': 'cuentas'
            },
            'cartes': {
                'fr': 'cartes',
                'nl': 'kaarten',
                'en': 'cards',
                'de': 'karten',
                'es': 'tarjetas'
            },
            'virements': {
                'fr': 'virements',
                'nl': 'overboekingen',
                'en': 'transfers',
                'de': 'uberweisungen',
                'es': 'transferencias'
            },
            'epargne': {
                'fr': 'epargne',
                'nl': 'sparen',
                'en': 'savings',
                'de': 'sparen',
                'es': 'ahorros'
            },
            'credits': {
                'fr': 'credits',
                'nl': 'kredieten',
                'en': 'credits',
                'de': 'kredite',
                'es': 'creditos'
            },
            'assurances': {
                'fr': 'assurances',
                'nl': 'verzekeringen',
                'en': 'insurances',
                'de': 'versicherungen',
                'es': 'seguros'
            }
        }
        
        return localizationMap[tabName] ? localizationMap[tabName][locale] : tabName
    }

    // Méthode pour obtenir le mapping inverse (URL -> onglet)
    getUrlToTabMap(locale) {
        const urlToTabMap = {
            'dashboard': 'comptes',
            // Comptes
            'comptes': 'comptes',
            'rekeningen': 'comptes',
            'accounts': 'comptes',
            'konten': 'comptes',
            'cuentas': 'comptes',
            // Cartes
            'cartes': 'cartes',
            'kaarten': 'cartes',
            'cards': 'cartes',
            'karten': 'cartes',
            'tarjetas': 'cartes',
            // Virements
            'virements': 'virements',
            'overboekingen': 'virements',
            'transfers': 'virements',
            'uberweisungen': 'virements',
            'transferencias': 'virements',
            // RIBs
            'mes-beneficiaires': 'ribs',
            'mijn-begunstigden': 'ribs',
            'my-beneficiaries': 'ribs',
            'meine-begunstigten': 'ribs',
            'mis-beneficiarios': 'ribs',
            // Épargne
            'epargne': 'epargne',
            'sparen': 'epargne',
            'savings': 'epargne',
            'ahorros': 'epargne',
            // Crédits
            'credits': 'credits',
            'kredieten': 'credits',
            'kredite': 'credits',
            'creditos': 'credits',
            // Assurances
            'assurances': 'assurances',
            'verzekeringen': 'assurances',
            'insurances': 'assurances',
            'versicherungen': 'assurances',
            'seguros': 'assurances'
        }
        
        return urlToTabMap
    }

    // Méthode pour gérer les raccourcis clavier
    handleKeyboard(event) {
        if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
            const currentIndex = this.tabTargets.findIndex(tab => 
                tab.classList.contains('banking__nav-tab--active')
            )
            
            let nextIndex
            if (event.key === 'ArrowLeft') {
                nextIndex = currentIndex > 0 ? currentIndex - 1 : this.tabTargets.length - 1
            } else {
                nextIndex = currentIndex < this.tabTargets.length - 1 ? currentIndex + 1 : 0
            }
            
            const nextTab = this.tabTargets[nextIndex]
            if (nextTab) {
                nextTab.click()
                nextTab.focus()
            }
        }
    }
}
