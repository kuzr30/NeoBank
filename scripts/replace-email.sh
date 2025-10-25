#!/bin/bash

# Script pour remplacer noreply@bankit.com par grenu@sedef.fr
# Usage: ./scripts/replace-email.sh

echo "🔄 Remplacement de noreply@bankit.com par grenu@sedef.fr"
echo "=================================================="

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Compteur de fichiers modifiés
modified_files=0

# Fonction pour remplacer dans un fichier
replace_in_file() {
    local file="$1"
    if [ -f "$file" ]; then
        # Vérifier si le fichier contient l'ancien email
        if grep -q "noreply@bankit.com" "$file"; then
            echo -e "${YELLOW}Modification:${NC} $file"
            # Faire une sauvegarde
            cp "$file" "$file.bak"
            # Remplacer l'email
            sed -i 's/noreply@bankit\.com/grenu@sedef.fr/g' "$file"
            ((modified_files++))
            echo -e "${GREEN}✓${NC} Modifié avec succès"
        fi
    fi
}

# Liste des fichiers à vérifier et modifier
echo -e "${BLUE}Recherche et remplacement en cours...${NC}"

# Rechercher tous les fichiers contenant noreply@bankit.com
while IFS= read -r -d '' file; do
    # Exclure les fichiers de sauvegarde, cache, vendor, etc.
    if [[ ! "$file" =~ \.(bak|log|cache)$ ]] && 
       [[ ! "$file" =~ /vendor/ ]] && 
       [[ ! "$file" =~ /var/cache/ ]] && 
       [[ ! "$file" =~ /node_modules/ ]] && 
       [[ ! "$file" =~ \.git/ ]]; then
        replace_in_file "$file"
    fi
done < <(find . -type f -exec grep -l "noreply@bankit\.com" {} \; -print0 2>/dev/null)

echo ""
echo "=================================================="
echo -e "${GREEN}✅ Remplacement terminé !${NC}"
echo -e "${BLUE}📊 Nombre de fichiers modifiés:${NC} $modified_files"

if [ $modified_files -gt 0 ]; then
    echo ""
    echo -e "${YELLOW}📋 Fichiers de sauvegarde créés:${NC}"
    find . -name "*.bak" -type f 2>/dev/null | head -10
    
    echo ""
    echo -e "${BLUE}💡 Pour supprimer les sauvegardes:${NC}"
    echo "   find . -name '*.bak' -type f -delete"
    
    echo ""
    echo -e "${GREEN}🔍 Vérification des changements:${NC}"
    echo "   ./scripts/find-email.sh"
else
    echo -e "${GREEN}ℹ️  Aucun fichier à modifier trouvé.${NC}"
fi

echo ""
echo -e "${BLUE}🚀 N'oubliez pas de vider le cache Symfony:${NC}"
echo "   make cache"