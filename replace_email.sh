#!/bin/bash

# Script pour remplacer grenu@sedef.fr par grenu@sedef.fr
# Auteur: Assistant
# Date: $(date +"%Y-%m-%d")

echo "🔄 Remplacement de grenu@sedef.fr par grenu@sedef.fr..."
echo "============================================================"

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Compteurs
TOTAL_FILES=0
TOTAL_REPLACEMENTS=0

echo "🔍 Recherche des fichiers contenant grenu@sedef.fr..."
echo

# Recherche et remplacement
files_with_email=$(find . -type f \( -name "*.php" -o -name "*.twig" -o -name "*.yaml" -o -name "*.yml" -o -name "*.js" -o -name "*.css" -o -name "*.md" -o -name "*.txt" -o -name "*.json" -o -name "*.env*" \) \
    -not -path "./vendor/*" \
    -not -path "./var/*" \
    -not -path "./node_modules/*" \
    -not -path "./.git/*" \
    -not -path "./migrations_backup_*/*" \
    -exec grep -l "grenu@sedef.fr" {} \; 2>/dev/null)

if [ -z "$files_with_email" ]; then
    echo -e "${YELLOW}ℹ️  Aucun fichier trouvé contenant grenu@sedef.fr${NC}"
    exit 0
fi

echo -e "${BLUE}📋 Fichiers trouvés:${NC}"
echo "$files_with_email"
echo

for file in $files_with_email; do
    if [ -f "$file" ]; then
        echo -e "${BLUE}📄 Traitement: ${NC}$file"
        
        # Compter les occurrences avant
        count_before=$(grep -c "grenu@sedef.fr" "$file" 2>/dev/null || echo "0")
        
        if [ "$count_before" -gt 0 ]; then
            echo -e "   ${YELLOW}🔍 Occurrences trouvées: $count_before${NC}"
            
            # Effectuer le remplacement
            if [[ "$OSTYPE" == "darwin"* ]]; then
                # macOS
                sed -i '' 's/no-reply@pro-olinda\.com/grenu@sedef.fr/g' "$file"
            else
                # Linux
                sed -i 's/no-reply@pro-olinda\.com/grenu@sedef.fr/g' "$file"
            fi
            
            # Vérifier le remplacement
            count_after=$(grep -c "grenu@sedef.fr" "$file" 2>/dev/null || echo "0")
            echo -e "   ${GREEN}✅ Remplacé! Nouvelles occurrences de grenu@sedef.fr: $count_after${NC}"
            
            ((TOTAL_FILES++))
            ((TOTAL_REPLACEMENTS += count_before))
        else
            echo -e "   ${YELLOW}ℹ️  Aucune occurrence trouvée${NC}"
        fi
        echo
    else
        echo -e "   ${RED}❌ Fichier non trouvé: $file${NC}"
    fi
done

echo "============================================================"
echo -e "${GREEN}✅ Remplacement terminé!${NC}"
echo -e "${YELLOW}📊 Statistiques:${NC}"
echo -e "   - Fichiers modifiés: $TOTAL_FILES"
echo -e "   - Remplacements effectués: $TOTAL_REPLACEMENTS"
echo

# Vérification finale
echo -e "${BLUE}🔍 Vérification finale...${NC}"
remaining_files=$(find . -type f \( -name "*.php" -o -name "*.twig" -o -name "*.yaml" -o -name "*.yml" -o -name "*.js" -o -name "*.css" -o -name "*.md" -o -name "*.txt" -o -name "*.json" -o -name "*.env*" \) \
    -not -path "./vendor/*" \
    -not -path "./var/*" \
    -not -path "./node_modules/*" \
    -not -path "./.git/*" \
    -not -path "./migrations_backup_*/*" \
    -exec grep -l "grenu@sedef.fr" {} \; 2>/dev/null)

if [ -n "$remaining_files" ]; then
    echo -e "${RED}⚠️  Attention: Il reste des occurrences dans:${NC}"
    echo "$remaining_files"
else
    echo -e "${GREEN}✅ Aucune occurrence de 'grenu@sedef.fr' trouvée!${NC}"
fi

# Afficher les fichiers qui contiennent maintenant grenu@sedef.fr
echo
echo -e "${BLUE}📋 Fichiers contenant maintenant grenu@sedef.fr:${NC}"
updated_files=$(find . -type f \( -name "*.php" -o -name "*.twig" -o -name "*.yaml" -o -name "*.yml" -o -name "*.js" -o -name "*.css" -o -name "*.md" -o -name "*.txt" -o -name "*.json" -o -name "*.env*" \) \
    -not -path "./vendor/*" \
    -not -path "./var/*" \
    -not -path "./node_modules/*" \
    -not -path "./.git/*" \
    -not -path "./migrations_backup_*/*" \
    -exec grep -l "grenu@sedef.fr" {} \; 2>/dev/null)

if [ -n "$updated_files" ]; then
    echo "$updated_files"
else
    echo -e "${YELLOW}Aucun fichier trouvé avec grenu@sedef.fr${NC}"
fi

echo
echo -e "${GREEN}🎉 Script terminé avec succès!${NC}"