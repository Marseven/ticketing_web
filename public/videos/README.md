# Vidéo de fond du Hero (page d'accueil)

Déposez ici le fichier **`hero.mp4`** pour l'afficher en arrière-plan animé du hero.

- Chemin attendu : `public/videos/hero.mp4` (servi en `/videos/hero.mp4`).
- Format recommandé : **MP4 (H.264)**, muet, ~10-20 s en boucle.
- Optimisation mobile : viser **< 3-5 Mo**, 720p suffit (le fond est assombri par un overlay). Compresser fortement.
- Un `poster`/fallback image est affiché tant que la vidéo n'est pas prête, et si elle échoue au chargement (ou si l'utilisateur a activé « réduire les animations »).

Alternative sans toucher au code : configurer une bannière Hero de **type vidéo** dans l'admin
(« Hero Banner »), sa vidéo prend alors le dessus sur `hero.mp4`.

Pour changer le chemin par défaut, voir `DEFAULT_HERO_VIDEO` dans
`resources/js/pages/Home.vue`.
