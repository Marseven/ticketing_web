@component('mail::message', ['theme' => 'primea'])
# Vérifiez votre adresse email

Bonjour **{{ $user->name }}**,

Merci de vous être inscrit sur **Primea** ! Pour commencer à profiter de nos événements exclusifs, nous devons vérifier votre adresse email.

@component('mail::button', ['url' => $verificationUrl, 'color' => 'blue'])
Vérifier mon email
@endcomponent

Ce lien de vérification expirera dans **60 minutes**.

Si vous n'avez pas créé de compte sur Primea, aucune action n'est requise de votre part.

---

**Besoin d'aide ?**

Si vous rencontrez des difficultés pour cliquer sur le bouton, copiez et collez l'URL suivante dans votre navigateur :

{{ $verificationUrl }}

Cordialement,<br>
L'équipe {{ config('app.name') }}

@component('mail::subcopy')
Si vous n'arrivez pas à cliquer sur le bouton "Vérifier mon email", copiez et collez cette URL dans votre navigateur : [{{ $verificationUrl }}]({{ $verificationUrl }})
@endcomponent
@endcomponent