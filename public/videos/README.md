# Vidéo de fond du Hero (page d'accueil)

Le fichier **`hero.mp4`** est la vidéo de fond animée du hero. Il est **versionné dans git** (il survit donc aux déploiements, contrairement à une bannière hero uploadée qui vit uniquement dans `storage/`).

- Chemin : `public/videos/hero.mp4` (servi en `/videos/hero.mp4`).
- Format : **MP4 (H.264)**, muet, ~10-20 s en boucle, **< 3-5 Mo**, 720p.
- **Poster / image de repli** : `public/images/hero-poster.jpg` (marine Primea). Affiché tant que la vidéo n'est pas prête, si elle échoue, ou si elle n'autoplay pas (navigateurs in-app WhatsApp/Instagram). Défini par `DEFAULT_HERO_IMAGE` dans `resources/js/pages/Home.vue`.

## Précédence

Une **bannière Hero admin active** (Admin → Bannières hero) prend le dessus sur `hero.mp4`. ⚠️ Ces bannières sont uploadées dans `storage/app/public/hero_banners/` (git-ignoré) : elles ne survivent pas forcément à un reset de `storage/`. Pour un hero **stable**, privilégier `public/videos/hero.mp4` (versionné) et **désactiver la bannière hero admin**.

Chemin par défaut : `DEFAULT_HERO_VIDEO` dans `resources/js/pages/Home.vue`.
