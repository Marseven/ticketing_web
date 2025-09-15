<?php
// Si c'est un asset (css, js, images), rediriger vers public/
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot|map)$/i', $_SERVER['REQUEST_URI'])) {
    header('Location: /public' . $_SERVER['REQUEST_URI'], true, 301);
    exit;
}

// Pour les autres requêtes, rediriger vers public/
header('Location: /public/', true, 301);
exit;
