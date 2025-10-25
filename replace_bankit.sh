#!/bin/bash

# Script pour remplacer toutes les occurrences de BankIT, BANKIT et BankIt par "Sedef Bk"
# Utilisation: ./replace_bankit.sh

echo "🔍 Début du remplacement de toutes les occurrences de BankIT, BANKIT et BankIt par 'Sedef Bk'..."
echo "📁 Dossiers traités: src/, templates/, translations/"
echo "================================================"

# Compteurs pour suivre les remplacements
count_bankit=0
count_bankIT=0
count_BANKIT=0
total_files=0

# Fonction pour compter les occurrences avant remplacement
count_occurrences() {
    local search_term="$1"
    local count=$(find ./src ./templates ./translations -type f \( -name "*.php" -o -name "*.twig" -o -name "*.js" -o -name "*.html" -o -name "*.css" -o -name "*.yaml" -o -name "*.yml" -o -name "*.md" -o -name "*.txt" -o -name "*.json" \) \
        -exec grep -l "$search_term" {} + 2>/dev/null | wc -l)
    echo $count
}

# Compter les occurrences initiales
echo "📊 Comptage des occurrences avant remplacement:"
initial_bankit=$(count_occurrences "BankIt")
initial_bankIT=$(count_occurrences "BankIT")
initial_BANKIT=$(count_occurrences "BANKIT")

echo "   - BankIt: $initial_bankit occurrences"
echo "   - BankIT: $initial_bankIT occurrences"  
echo "   - BANKIT: $initial_BANKIT occurrences"
echo ""

# Fonction pour effectuer le remplacement dans un fichier
replace_in_file() {
    local file="$1"
    local original_content
    local new_content
    local changes_made=false
    
    if [ -f "$file" ]; then
        original_content=$(cat "$file")
        new_content="$original_content"
        
        # Remplacer BankIt par Sedef Bk
        if echo "$new_content" | grep -q "BankIt"; then
            new_content=$(echo "$new_content" | sed 's/BankIt/Sedef Bank/g')
            changes_made=true
            ((count_bankit++))
        fi
        
        # Remplacer BankIT par Sedef Bk
        if echo "$new_content" | grep -q "BankIT"; then
            new_content=$(echo "$new_content" | sed 's/BankIT/Sedef Bank/g')
            changes_made=true
            ((count_bankIT++))
        fi
        
        # Remplacer BANKIT par Sedef Bk
        if echo "$new_content" | grep -q "BANKIT"; then
            new_content=$(echo "$new_content" | sed 's/BANKIT/Sedef Bank/g')
            changes_made=true
            ((count_BANKIT++))
        fi
        
        # Si des changements ont été effectués, écrire le nouveau contenu
        if [ "$changes_made" = true ]; then
            echo "$new_content" > "$file"
            echo "✅ Modifié: $file"
            ((total_files++))
        fi
    fi
}

# Parcourir uniquement les dossiers src, templates et translations
echo "🔄 Traitement des fichiers dans src/, templates/ et translations/..."
echo ""

# Trouver et traiter tous les fichiers dans les dossiers spécifiés
find ./src ./templates ./translations -type f \( -name "*.php" -o -name "*.twig" -o -name "*.js" -o -name "*.html" -o -name "*.css" -o -name "*.yaml" -o -name "*.yml" -o -name "*.md" -o -name "*.txt" -o -name "*.json" \) 2>/dev/null | while read -r file; do
    replace_in_file "$file"
done

# Attendre que tous les remplacements soient terminés
wait

echo ""
echo "📈 Résumé des remplacements effectués:"
echo "   - BankIt → Sedef Bk: dans les fichiers traités"
echo "   - BankIT → Sedef Bk: dans les fichiers traités"
echo "   - BANKIT → Sedef Bk: dans les fichiers traités"
echo ""

# Vérification finale
echo "🔍 Vérification finale des occurrences restantes:"
final_bankit=$(count_occurrences "BankIt")
final_bankIT=$(count_occurrences "BankIT")
final_BANKIT=$(count_occurrences "BANKIT")

echo "   - BankIt: $final_bankit occurrences restantes"
echo "   - BankIT: $final_bankIT occurrences restantes"
echo "   - BANKIT: $final_BANKIT occurrences restantes"
echo ""

if [ $final_bankit -eq 0 ] && [ $final_bankIT -eq 0 ] && [ $final_BANKIT -eq 0 ]; then
    echo "🎉 Succès! Tous les remplacements ont été effectués correctement."
else
    echo "⚠️  Attention: Il reste encore des occurrences. Vérifiez les fichiers manuellement."
    echo "   Les occurrences restantes peuvent être dans des fichiers exclus (vendor/, var/, etc.)"
fi

echo ""
echo "✨ Script terminé!"