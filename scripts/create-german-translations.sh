#!/bin/bash

# Script pour créer automatiquement les fichiers de traduction allemands
# basés sur les fichiers français existants

TRANSLATIONS_DIR="translations"
SOURCE_LANG="fr"
TARGET_LANG="de"

echo "🇩🇪 Création des fichiers de traduction allemands..."

# Compter les fichiers source
total_files=$(find $TRANSLATIONS_DIR -name "*.$SOURCE_LANG.yaml" -type f | wc -l)
echo "📊 $total_files fichiers français trouvés"

# Variables pour le suivi
created=0
skipped=0

# Parcourir tous les fichiers français
find $TRANSLATIONS_DIR -name "*.$SOURCE_LANG.yaml" -type f | while read fr_file; do
    # Générer le nom du fichier allemand
    de_file="${fr_file/.$SOURCE_LANG.yaml/.$TARGET_LANG.yaml}"
    
    # Obtenir juste le nom du fichier pour l'affichage
    basename_file=$(basename "$fr_file")
    
    if [ ! -f "$de_file" ]; then
        # Copier le fichier français vers l'allemand
        cp "$fr_file" "$de_file"
        echo "✅ Créé: ${basename_file/.$SOURCE_LANG.yaml/.$TARGET_LANG.yaml}"
        ((created++))
    else
        echo "⚠️  Existe déjà: ${basename_file/.$SOURCE_LANG.yaml/.$TARGET_LANG.yaml}"
        ((skipped++))
    fi
done

echo ""
echo "📈 Résumé:"
echo "   ✅ Fichiers créés: $created"
echo "   ⚠️  Fichiers ignorés: $skipped"
echo ""
echo "🎯 Prochaines étapes:"
echo "   1. Identifier les fichiers prioritaires (devis, enums, forms_common)"
echo "   2. Traduire manuellement ces fichiers clés"
echo "   3. Utiliser un service de traduction pour les autres"
echo ""
echo "💡 Fichiers prioritaires à traduire en premier:"
echo "   - translations/devis.de.yaml"
echo "   - translations/enums.de.yaml" 
echo "   - translations/forms_common.de.yaml"
echo "   - translations/services.de.yaml"
