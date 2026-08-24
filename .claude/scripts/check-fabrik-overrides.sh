#!/usr/bin/env bash
#
# Vérifie que les surcharges Tchooz sur Fabrik sont toujours en place.
# À lancer après CHAQUE mise à jour de com_fabrik (et après chaque merge qui en embarque une).
#
# Usage : .claude/scripts/check-fabrik-overrides.sh
# Sortie : 0 si tout est là, 1 si au moins une surcharge manque.
#
# Voir .claude/docs/fabrik-overrides.md pour le détail et le code à réappliquer.

set -uo pipefail
cd "$(git rev-parse --show-toplevel)" || exit 2

fail=0

# chk <libellé> <motif littéral> <fichier> [occurrences attendues, défaut 1]
# Le motif est traité en chaîne littérale (grep -F) : ne PAS échapper les $.
chk() {
	local label="$1" pattern="$2" file="$3" want="${4:-1}" got
	if [ ! -f "$file" ]; then
		printf '  \033[31mFICHIER ABSENT\033[0m  %s\n' "$label"
		printf '                  %s\n' "$file"
		fail=1
		return
	fi
	got=$(grep -cF -- "$pattern" "$file" | tr -d ' ')
	if [ "$got" = "$want" ]; then
		printf '  \033[32mOK\033[0m              %s\n' "$label"
	else
		printf '  \033[31mMANQUE\033[0m          %s  (%s/%s)\n' "$label" "$got" "$want"
		printf '                  %s\n' "$file"
		fail=1
	fi
}

# chk_after <libellé> <ancre> <nb lignes> <motif> <fichier> [occurrences attendues, défaut 1]
# Cherche <motif> dans les <nb lignes> qui suivent <ancre>. Sert à cibler UNE garde précise dans un
# fichier qui en contient plusieurs, dont certaines viennent de l'amont et ne sont pas à nous.
chk_after() {
	local label="$1" anchor="$2" span="$3" pattern="$4" file="$5" want="${6:-1}" got
	if [ ! -f "$file" ]; then
		printf '  \033[31mFICHIER ABSENT\033[0m  %s\n' "$label"
		fail=1
		return
	fi
	got=$(grep -A"$span" -F -- "$anchor" "$file" | grep -cF -- "$pattern")
	if [ "$got" = "$want" ]; then
		printf '  \033[32mOK\033[0m              %s\n' "$label"
	else
		printf '  \033[31mMANQUE\033[0m          %s  (%s/%s)\n' "$label" "$got" "$want"
		printf '                  %s  (après "%s")\n' "$file" "$anchor"
		fail=1
	fi
}

# chk_re : pour les bundles minifiés, dont le minifieur renomme les variables à chaque release.
chk_re() {
	local label="$1" pattern="$2" file="$3" want="${4:-1}" got
	if [ ! -f "$file" ]; then
		printf '  \033[31mFICHIER ABSENT\033[0m  %s\n' "$label"
		fail=1
		return
	fi
	got=$(grep -oE -- "$pattern" "$file" | wc -l | tr -d ' ')
	if [ "$got" = "$want" ]; then
		printf '  \033[32mOK\033[0m              %s\n' "$label"
	else
		printf '  \033[31mMANQUE\033[0m          %s  (%s/%s)\n' "$label" "$got" "$want"
		printf '                  %s\n' "$file"
		fail=1
	fi
}

echo
echo "Surcharges Tchooz sur Fabrik"
echo "============================"
echo
echo "Gardes CLI (Fabrik appelle des méthodes qui n'existent pas sur ConsoleApplication)"

chk "manifest : templateOverride() (getTemplate)" \
	"isClient('cli')" \
	administrator/components/com_fabrik/com_fabrik.manifest.class.php

chk "Worker : itemId() + getMenu() x3" \
	'!$app->isClient('"'"'administrator'"'"') && !$app->isCli()' \
	libraries/fabrik/fabrik/fabrik/Helpers/Worker.php \
	3

# list.php contient 4 gardes isCli, mais 3 viennent de l'amont 4.7.1 : on ne cible que la nôtre,
# celle de populateState() qui protège $this->app->getParams() (SiteApplication uniquement).
chk_after "list : populateState() (getParams)" \
	"protected function populateState()" 8 \
	'!$this->app->isCli()' \
	components/com_fabrik/models/list.php

chk_after "form : populateState() (getParams)" \
	"protected function populateState()" 8 \
	'!$this->app->isCli()' \
	components/com_fabrik/models/form.php

echo
echo "Administration"

chk "garde is_array(validations->plugin)" \
	'is_array($validations->plugin)' \
	administrator/components/com_fabrik/models/elements.php

chk "getFilterForm() (filtre groupe scopé au formulaire)" \
	"function getFilterForm" \
	administrator/components/com_fabrik/models/elements.php

chk "grouplist : lecture de sql_where" \
	"(string) \$this->element['sql_where']" \
	administrator/components/com_fabrik/models/fields/grouplist.php

chk "grouplist : application de sql_where" \
	'$query->where($sql_where)' \
	administrator/components/com_fabrik/models/fields/grouplist.php

echo
echo "Front / rendu"

chk "chargement custom_list_<tmpl>.js" \
	"custom_list_'.\$this->getTmpl()" \
	components/com_fabrik/models/list.php \
	2

chk "addoptions : listener icone add/close" \
	"addButton.addEventListener" \
	components/com_fabrik/layouts/element/fabrik-element-addoptions.php

chk "addoptions : balise </script> fermante" \
	"</script>" \
	components/com_fabrik/layouts/element/fabrik-element-addoptions.php

chk "filtre date range : markup Tailwind" \
	"tw-flex tw-flex-col tw-gap-2" \
	plugins/fabrik_element/jdate/layouts/fabrik-element-jdate-list-filter-range.php

echo
echo "Groupes répétables : label -> input (sinon la sélection va sur le 1er groupe)"

chk "checkbox.js cloned()" \
	"sub.getParent('label') || sub.getNext('label')" \
	plugins/fabrik_element/checkbox/checkbox.js

chk_re "checkbox-min.js cloned()" \
	'getParent\("label"\)\|\|[a-zA-Z_$]+\.getNext\("label"\)' \
	plugins/fabrik_element/checkbox/checkbox-min.js

chk "radiobutton.js cloned() + setName()" \
	"getParent('label') || " \
	plugins/fabrik_element/radiobutton/radiobutton.js \
	2

chk_re "radiobutton-min.js cloned() + setName()" \
	'getParent\("label"\)\|\|[a-zA-Z_$]+\.getNext\("label"\)' \
	plugins/fabrik_element/radiobutton/radiobutton-min.js \
	2

echo
echo "Databasejoin"

chk "isSameConnection (join_conn_id vide) #1793" \
	"isSameConnection" \
	plugins/fabrik_element/databasejoin/databasejoin.php \
	4

chk "form-final : isset(control) et non !empty() #1271" \
	'isset($d->control)' \
	plugins/fabrik_element/databasejoin/layouts/fabrik-element-databasejoin-form-final.php

echo
if [ "$fail" -eq 0 ]; then
	printf '\033[32mToutes les surcharges sont en place.\033[0m\n\n'
else
	printf '\033[31mDes surcharges manquent — voir .claude/docs/fabrik-overrides.md.\033[0m\n\n'
fi

exit "$fail"
