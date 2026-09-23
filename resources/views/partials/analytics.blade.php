@php
    $provider = strtolower((string) config('analytics.provider', 'none'));
    $domain = config('analytics.domain');
    $siteId = config('analytics.site_id');
    $scriptUrl = config('analytics.script_url');

    // Google Analytics dépose des cookies : il attend un consentement
    // explicite. Plausible et Umami n'en déposent aucun et n'identifient
    // personne, ils se chargent directement.
    $needsConsent = $provider === 'ga4';

    $configured = match ($provider) {
        'plausible' => (bool) $domain,
        'umami' => $siteId && $scriptUrl,
        'ga4' => (bool) $siteId,
        default => false,
    };
@endphp

@if ($configured)
    @if ($provider === 'plausible')
        <script defer
                data-domain="{{ $domain }}"
                src="{{ $scriptUrl ?: 'https://plausible.io/js/script.js' }}"></script>
    @endif

    @if ($provider === 'umami')
        <script defer
                data-website-id="{{ $siteId }}"
                src="{{ $scriptUrl }}"></script>
    @endif

    @if ($provider === 'ga4')
        {{-- Rien n'est chargé tant que la personne n'a pas accepté. Le choix
             est conservé localement, jamais envoyé à un serveur. --}}
        <script>
            (function () {
                var KEY = 'primea.analytics.consent';
                var ID = @json($siteId);

                function load() {
                    var s = document.createElement('script');
                    s.async = true;
                    s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(ID);
                    document.head.appendChild(s);

                    window.dataLayer = window.dataLayer || [];
                    window.gtag = function () { window.dataLayer.push(arguments); };
                    window.gtag('js', new Date());
                    // Pas de cookie publicitaire, et adresse IP tronquée.
                    window.gtag('config', ID, { anonymize_ip: true, allow_ad_personalization_signals: false });
                }

                function decision() {
                    try { return localStorage.getItem(KEY); } catch (e) { return null; }
                }

                function remember(value) {
                    try { localStorage.setItem(KEY, value); } catch (e) { /* navigation privée */ }
                }

                if (decision() === 'granted') { load(); return; }
                if (decision() === 'denied') { return; }

                document.addEventListener('DOMContentLoaded', function () {
                    var bar = document.createElement('div');
                    bar.setAttribute('role', 'dialog');
                    bar.setAttribute('aria-label', 'Mesure d\'audience');
                    bar.style.cssText = 'position:fixed;left:0;right:0;bottom:0;z-index:2147483000;'
                        + 'background:#fff;border-top:1px solid #e5e7eb;box-shadow:0 -4px 16px rgba(0,0,0,.08);'
                        + 'padding:16px;padding-bottom:calc(16px + env(safe-area-inset-bottom));'
                        + 'display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:center;'
                        + 'font-family:system-ui,-apple-system,sans-serif;font-size:14px;color:#374151;';

                    var text = document.createElement('p');
                    text.style.cssText = 'margin:0;flex:1 1 320px;line-height:1.45;';
                    text.innerHTML = 'Nous aimerions mesurer la fréquentation du site pour l\'améliorer. '
                        + 'Cette mesure dépose des cookies. '
                        + '<a href="/privacy" style="color:#272d63;font-weight:600;text-decoration:underline;">En savoir plus</a>.';

                    function button(label, background, color, value) {
                        var b = document.createElement('button');
                        b.type = 'button';
                        b.textContent = label;
                        b.style.cssText = 'min-height:44px;padding:10px 20px;border:0;border-radius:10px;cursor:pointer;'
                            + 'font-weight:600;font-size:14px;background:' + background + ';color:' + color + ';';
                        b.addEventListener('click', function () {
                            remember(value);
                            bar.remove();
                            if (value === 'granted') load();
                        });
                        return b;
                    }

                    bar.appendChild(text);
                    bar.appendChild(button('Refuser', '#f3f4f6', '#374151', 'denied'));
                    bar.appendChild(button('Accepter', '#272d63', '#fff', 'granted'));
                    document.body.appendChild(bar);
                });
            })();
        </script>
    @endif
@endif
