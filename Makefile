# 🏦 BankIt - Makefile Pro
.PHONY: help up down start stop db-create db-migrate db-reset make-migration install cache open deploy lint lint-php lint-yaml lint-twig lint-all translation-lint translation-check translation-debug translation-stats scheduled-emails mail-worker

up:
	@echo "🐳 Démarrage des containers..."
	@docker compose --env-file .env.local up -d
	@symfony serve -d

# 🐳 Arrête les containers
down:
	@echo "🐳 Arrêt des containers..."
	@docker compose down

# 🚀 Démarre le serveur Symfony
start:
	@echo "🚀 Démarrage du serveur Symfony..."
	@symfony serve -d

# ⛔ Arrête le serveur Symfony
stop:
	@echo "⛔ Arrêt du serveur..."
	@symfony server:stop || echo "Serveur arrêté"

# 🗄️ Crée la base de données
db-create:
	@echo "🗄️ Création de la base de données..."
	@php bin/console doctrine:database:create --if-not-exists

# 🗄️ Exécute les migrations
migrate:
	@echo "🗄️ Exécution des migrations..."
	@php bin/console doctrine:migrations:migrate --no-interaction

# 🔄 Reset complet de la base de données
db-reset:
	@echo "🔄 Reset de la base de données..."
	@php bin/console doctrine:database:drop --force --if-exists
	@php bin/console doctrine:database:create
	@php bin/console doctrine:migrations:migrate --no-interaction

# 📝 Crée une nouvelle migration
migration:
	@echo "📝 Création d'une nouvelle migration..."
	@php bin/console make:migration

# 📦 Installation complète
install:
	@echo "📦 Installation des dépendances..."
	@composer install
	@php bin/console cache:clear

# 🧹 Vide le cache
cache:
	@echo "🧹 Nettoyage du cache..."
	@rm -rf var/cache/* && php bin/console cache:clear && php bin/console cache:warmup && php bin/console asset-map:compile

# ==========================================
# 🔍 COMMANDES DE LINTING ET VALIDATION
# ==========================================

# 🔍 Linting complet de tous les fichiers
lint: lint-php lint-yaml lint-twig translation-lint
	@echo "✅ Tous les lints sont terminés avec succès!"

# Alias pour lint (compatibilité)
lint-all: lint

# 🐘 Linting des fichiers PHP
lint-php:
	@echo "🐘 Linting des fichiers PHP..."
	@echo "  → Validation de src/..."
	@find src -name "*.php" -exec php -l {} \; | grep -v "No syntax errors" || echo "  ✅ Fichiers src/ valides!"
	@echo "  → Validation de config/..."
	@find config -name "*.php" -exec php -l {} \; | grep -v "No syntax errors" || echo "  ✅ Fichiers config/ valides!"
	@if [ -d "tests" ]; then \
		echo "  → Validation de tests/..."; \
		find tests -name "*.php" -exec php -l {} \; | grep -v "No syntax errors" || echo "  ✅ Fichiers tests/ valides!"; \
	fi
	@echo "✅ Linting PHP terminé!"

# 📄 Linting des fichiers YAML (config et autres)
lint-yaml:
	@echo "📄 Linting des fichiers YAML..."
	@echo "  → Validation des fichiers config YAML..."
	@find config -name "*.yaml" -o -name "*.yml" | while read file; do \
		echo "    Validation de $$file..."; \
		php -r "yaml_parse_file('$$file') or die('Erreur dans $$file');" 2>/dev/null || python3 -c "import yaml; yaml.safe_load(open('$$file'))" || exit 1; \
	done
	@echo "  → Validation des autres fichiers YAML..."
	@find . -name "*.yaml" -o -name "*.yml" | grep -v vendor | grep -v var | grep -v translations | grep -v config | while read file; do \
		echo "    Validation de $$file..."; \
		php -r "yaml_parse_file('$$file') or die('Erreur dans $$file');" 2>/dev/null || python3 -c "import yaml; yaml.safe_load(open('$$file'))" || exit 1; \
	done
	@echo "✅ Linting YAML terminé!"

# 🎨 Linting des templates Twig
lint-twig:
	@echo "🎨 Linting des templates Twig..."
	@php bin/console lint:twig templates/
	@echo "✅ Linting Twig terminé!"

# 🌐 Linting des fichiers de traduction
translation-lint:
	@echo "🌐 Linting des fichiers de traduction..."
	@echo "  → Validation de la syntaxe YAML des traductions..."
	@for file in translations/*.yaml; do \
		echo "    Validation de $$file..."; \
		python3 -c "import yaml; yaml.safe_load(open('$$file'))" || exit 1; \
	done
	@echo "✅ Linting des traductions terminé!"

# 🔍 Vérification des traductions manquantes
translation-check:
	@echo "🔍 Vérification des traductions manquantes..."
	@echo "  → Vérification français..."
	@php bin/console debug:translation fr --only-missing || true
	@echo "  → Vérification néerlandais..."
	@php bin/console debug:translation nl --only-missing || true
	@echo "  → Vérification allemand..."
	@php bin/console debug:translation de --only-missing || true

# 🔧 Debug des traductions pour une locale
translation-debug:
	@echo "� Debug des traductions (utilisez: make translation-debug LOCALE=fr)"
	@php bin/console debug:translation $(or $(LOCALE),fr)

# 📊 Statistiques des traductions
translation-stats:
	@echo "📊 Statistiques des traductions..."
	@echo "  → Fichiers français:"
	@find translations -name "*.fr.yaml" | wc -l
	@echo "  → Fichiers néerlandais:"
	@find translations -name "*.nl.yaml" | wc -l
	@echo "  → Fichiers allemands:"
	@find translations -name "*.de.yaml" | wc -l
	@echo "  → Total des clés de traduction (estimé):"
	@find translations -name "*.yaml" -exec grep -E "^[[:space:]]*[^#].*:" {} \; | wc -l
	@echo "  → Fichiers de traduction par domaine:"
	@find translations -name "*.yaml" | sed 's/.*\/\([^.]*\)\..*\.yaml/\1/' | sort | uniq -c | sort -nr

# ==========================================
# 📧 AUTRES COMMANDES
# ==========================================

# 🗺️ Génération du sitemap
sitemap:
	@echo "🗺️ Génération du sitemap..."
	@php bin/console presta:sitemaps:dump public --base-url="https://sedef.fr"
	@echo "✅ Sitemap généré avec succès!"

# 🗺️ Génération du sitemap compressé
sitemap-gz:
	@echo "🗺️ Génération du sitemap compressé..."
	@php bin/console presta:sitemaps:dump public --base-url="https://sedef.fr" --gzip
	@echo "✅ Sitemap compressé généré avec succès!"

# 📧 Worker pour traiter les emails en arrière-plan
mail-worker:
	@echo "📧 Démarrage du worker Messenger pour traiter les emails..."
	@php bin/console messenger:consume async -vv

# 📧 Envoi des emails programmés (cron)
scheduled-emails:
	@echo "📧 Envoi des emails programmés..."
	@php bin/console app:scheduled-emails:send
	@echo "✅ Commande d'envoi des emails terminée!"
