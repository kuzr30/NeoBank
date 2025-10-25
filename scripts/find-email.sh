#!/bin/bash

# Script pour trouver toutes les occurrences de noreply@bankit.com
# Usage: ./scripts/find-email.sh

echo "🔍 Recherche de 'noreply@bankit.com' dans tous les fichiers..."
echo "=================================================="

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Compteur de résultats
count=0

# Recherche avec grep récursif, en excluant certains dossiers
echo -e "${BLUE}Recherche en cours...${NC}\n"

# Rechercher dans tous les fichiers, en excluant les dossiers de cache, vendor, node_modules
results=$(grep -r "noreply@bankit.com" \
    --exclude-dir=vendor \
    --exclude-dir=node_modules \
    --exclude-dir=var \
    --exclude-dir=.git \
    --exclude="*.log" \
    --exclude="*.cache" \
    --color=always \
    .)

if [ -n "$results" ]; then
    echo -e "${GREEN}✅ Résultats trouvés :${NC}\n"
    
    # Afficher les résultats avec numérotation
    while IFS= read -r line; do
        ((count++))
        echo -e "${YELLOW}[$count]${NC} $line"
    done <<< "$results"
    
    echo -e "\n${GREEN}📊 Total : $count occurrence(s) trouvée(s)${NC}"
    
    # Résumé par type de fichier
    echo -e "\n${BLUE}📋 Résumé par extension de fichier :${NC}"
    echo "$results" | cut -d: -f1 | sed 's/.*\.//' | sort | uniq -c | sort -nr
    
    # Résumé par dossier
    echo -e "\n${BLUE}📁 Résumé par dossier :${NC}"
    echo "$results" | cut -d: -f1 | xargs -I {} dirname {} | sort | uniq -c | sort -nr
    
else
    echo -e "${RED}❌ Aucune occurrence de 'noreply@bankit.com' trouvée${NC}"
fi

echo -e "\n${BLUE}🔍 Recherche terminée !${NC}"