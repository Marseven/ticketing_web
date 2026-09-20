# Vidéo de fond du Hero (page d'accueil)

Le fichier **`hero.mp4`** est la vidéo de fond animée du hero. Il est **versionné dans git** (il survit donc aux déploiements, contrairement à une bannière hero uploadée qui vit uniquement dans `storage/`).

- Chemin : `public/videos/hero.mp4` (servi en `/videos/hero.mp4`).
- Format : **MP4 (H.264)**, muet, ~10-20 s en boucle, **540p, < 1,5 Mo**. Garder
  ce budget : la vidéo est le plus gros fichier du site.
- **Poster / image de repli** : `public/images/hero-poster.jpg` (~30 Ko, une image
  de la vidéo). Affiché tant que la vidéo n'est pas prête, si elle échoue, ou si
  elle n'autoplay pas (navigateurs in-app WhatsApp/Instagram). Défini par
  `DEFAULT_HERO_IMAGE` dans `resources/js/pages/Home.vue`.
  Régénérer après remplacement de la vidéo :
  ```bash
  qlmanage -t -s 1600 -o /tmp public/videos/hero.mp4
  magick /tmp/hero.mp4.png -resize 1280x -quality 72 -strip -interlace Plane public/images/hero-poster.jpg
  ```

## Qui télécharge la vidéo ?

**Seuls les postes de bureau sur une connexion correcte.** `Home.vue` ne monte le
`<video>` que si l'écran fait ≥ 768 px, que la connexion n'est ni `2g`/`3g` ni en
mode économie de données, et que « mouvement réduit » est désactivé ; sinon seul le
poster est chargé. Sur mobile — 9 visiteurs sur 10, souvent via WhatsApp où
l'autoplay est bloqué de toute façon — la page d'accueil économise ainsi ~1 Mo.

## Précédence

Une **bannière Hero admin active** (Admin → Bannières hero) prend le dessus sur `hero.mp4`. ⚠️ Ces bannières sont uploadées dans `storage/app/public/hero_banners/` (git-ignoré) : elles ne survivent pas forcément à un reset de `storage/`. Pour un hero **stable**, privilégier `public/videos/hero.mp4` (versionné) et **désactiver la bannière hero admin**.

Chemin par défaut : `DEFAULT_HERO_VIDEO` dans `resources/js/pages/Home.vue`.
