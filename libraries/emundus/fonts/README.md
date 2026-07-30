# Polices Unicode pour l'export PDF (Dompdf)

Les exports PDF de Tchooz sont générés par **Dompdf**. Pour afficher les
caractères non latins (cyrillique, CJK chinois/japonais/coréen…) une police
Unicode à large couverture doit être déposée dans ce répertoire.

## Fichier attendu

```
libraries/emundus/fonts/emundus-unicode.ttf
```

`Tchooz\Services\Export\Pdf\PdfFont` détecte automatiquement ce fichier :

- **présent** → utilisé comme police par défaut + injecté via `@font-face` ;
- **absent** → repli sur « DejaVu Sans » (fournie par Dompdf, couvre le latin et
  le cyrillique mais **pas** le CJK).

## Contraintes techniques (importantes)

1. **TrueType uniquement.** Le sous-setteur de Dompdf (`php-font-lib`) ne gère
   que les contours TrueType (`glyf`). Les polices CFF / OpenType-PostScript
   comme **Noto Sans CJK** ou **Source Han Sans** ne fonctionnent pas.
2. **Une seule police pour tout.** Dompdf ne fait aucun repli glyphe-par-glyphe :
   la police doit couvrir à elle seule latin + cyrillique + CJK.

## Polices recommandées (TrueType, libres)

| Police | Couverture | Licence |
|--------|-----------|---------|
| **Sarasa Gothic** (`sarasa-gothic-*.ttf`) | Latin + Cyrillique + Grec + CJK | OFL |
| **WenQuanYi Zen Hei** (`wqy-zenhei.ttf`) | Latin + Cyrillique + CJK | GPL+FE |
| **Droid Sans Fallback** (`DroidSansFallbackFull.ttf`) | Latin + Cyrillique (base) + CJK | Apache 2.0 |

Téléchargez l'une d'elles, renommez le `.ttf` en `emundus-unicode.ttf` et
déposez-le ici. Le cache de sous-set est régénéré automatiquement au premier
rendu.
