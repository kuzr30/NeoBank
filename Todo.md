PLAN D'ACTION RECOMMANDÉ
Semaine 1-2: Urgences
✅ Externaliser routeTranslations et segmentTranslations en YAML
✅ Fixer priorités EventSubscribers (éviter race conditions)
✅ Simplifier switchLocaleInUrl() avec router Symfony
✅ Tests de régression sur changement langue


Semaine 3-4: Consolidation
✅ Supprimer TranslationService (garder seulement Professional...)
✅ Fusionner ExceptionLocaleSubscriber dans LocaleSubscriber
✅ Documenter architecture finale
✅ Former équipe sur nouveau système


Mois 2-3: Optimisations
✅ Implémenter JMSTranslationBundle
✅ Cache Redis pour traductions (actuellement en mémoire PHP)
✅ CI/CD: check complétude traductions
✅ Refactor routes contrôleurs avec custom attributes