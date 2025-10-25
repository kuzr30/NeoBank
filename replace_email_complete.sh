#!/bin/bash

# Script pour remplacer grenu@sedef.fr par grenu@sedef.fr


echo "🔄 Remplacement complet de grenu@sedef.fr par grenu@sedef.fr..."
echo "========================================================================"

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Compteurs
TOTAL_FILES=0
TOTAL_REPLACEMENTS=0

echo "🔍 Recherche exhaustive de tous les fichiers contenant grenu@sedef.fr..."
echo

# Recherche dans TOUS les fichiers (sauf .git)
files_with_email=$(find . -type f \
    -not -path "./.git/*" \
    -exec grep -l "grenu@sedef.fr" {} \; 2>/dev/null)

if [ -z "$files_with_email" ]; then
    echo -e "${YELLOW}ℹ️  Aucun fichier trouvé contenant grenu@sedef.fr${NC}"
    echo "🔍 Recherche des fichiers contenant pro-olinda (pour vérification)..."
    
    # Recherche alternative pour pro-olinda
    files_with_olinda=$(find . -type f \
        -not -path "./.git/*" \
        -exec grep -l "pro-olinda" {} \; 2>/dev/null)
    
    if [ -n "$files_with_olinda" ]; then
        echo -e "${BLUE}📋 Fichiers contenant 'pro-olinda':${NC}"
        for file in $files_with_olinda; do
            echo "   $file"
            grep -n "pro-olinda" "$file" | head -3
            echo
        done
    fi
    
    exit 0
fi

echo -e "${BLUE}📋 Fichiers trouvés contenant grenu@sedef.fr:${NC}"
for file in $files_with_email; do
    echo "   $file"
done
echo

# Demander confirmation pour les fichiers sensibles
echo -e "${YELLOW}⚠️  Voulez-vous continuer le remplacement? (y/N)${NC}"
read -r response
if [[ ! "$response" =~ ^[Yy]$ ]]; then
    echo "❌ Annulé par l'utilisateur"
    exit 0
fi

echo
echo "🔄 Traitement en cours..."

for file in $files_with_email; do
    if [ -f "$file" ]; then
        echo -e "${BLUE}📄 Traitement: ${NC}$file"
        
        # Compter les occurrences avant
        count_before=$(grep -c "grenu@sedef.fr" "$file" 2>/dev/null || echo "0")
        
        if [ "$count_before" -gt 0 ]; then
            echo -e "   ${YELLOW}🔍 Occurrences trouvées: $count_before${NC}"
            
            # Montrer les lignes qui vont être modifiées
            echo -e "   ${BLUE}📝 Aperçu des lignes à modifier:${NC}"
            grep -n "grenu@sedef.fr" "$file" | head -3
            
            # Sauvegarder le fichier original
            cp "$file" "${file}.backup"
            
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
            echo -e "   ${GREEN}✅ Remplacé! Occurrences de grenu@sedef.fr: $count_after${NC}"
            echo -e "   ${BLUE}💾 Sauvegarde créée: ${file}.backup${NC}"
            
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

echo "========================================================================"
echo -e "${GREEN}✅ Remplacement terminé!${NC}"
echo -e "${YELLOW}📊 Statistiques:${NC}"
echo -e "   - Fichiers modifiés: $TOTAL_FILES"
echo -e "   - Remplacements effectués: $TOTAL_REPLACEMENTS"
echo

# Vérification finale
echo -e "${BLUE}🔍 Vérification finale...${NC}"
remaining_files=$(find . -type f \
    -not -path "./.git/*" \
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
updated_files=$(find . -type f \
    -not -path "./.git/*" \
    -exec grep -l "grenu@sedef.fr" {} \; 2>/dev/null | head -10)

if [ -n "$updated_files" ]; then
    for file in $updated_files; do
        echo "   $file"
    done
else
    echo -e "${YELLOW}Aucun fichier trouvé avec grenu@sedef.fr${NC}"
fi

echo
echo -e "${GREEN}🎉 Script terminé avec succès!${NC}"

# Nettoyer les sauvegardes si souhaité
echo
echo -e "${YELLOW}🗑️  Voulez-vous supprimer les fichiers de sauvegarde .backup? (y/N)${NC}"
read -r cleanup_response
if [[ "$cleanup_response" =~ ^[Yy]$ ]]; then
    find . -name "*.backup" -type f -delete
    echo -e "${GREEN}✅ Fichiers de sauvegarde supprimés${NC}"
fi