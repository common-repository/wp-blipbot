=== Plugin Name ===
Contributors: LukaszWiecek
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=lukasz%40wiecek%2ebiz&lc=PL&item_name=Darowizna&item_number=WP%2dBlipBot&currency_code=PLN&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: blip, bot, blipbot
Requires at least: 2.7
Tested up to: 2.9.2
Stable tag: 3.0.9

Wtyczka pozwalająca stworzyć własnego bota w serwisie [Blip.pl](http://blip.pl/).

== Description ==

Wtyczka **WP BlipBot** pozwala wysyłać do serwisu [Blip.pl](http://blip.pl/) informacje o publikowanych przez autora bloga postach. Wszelkie uwagi można zgłaszać na **Blipie** tagując wiadomości [#WP-BlipBot](http://www.blip.pl/tags/wpblipbot/), lub bezpośrednio do mnie [lukaszwiecek](http://www.blip.pl/users/lukaszwiecek/dashboard/).

Histora zmian dostępna jest [tutaj](http://wordpress.org/extend/plugins/wp-blipbot/other_notes/).

**Wymagania!**

Do poprawnego działania wtyczka wymaga dostępu do biblioteki **cURL** oraz serwera **PHP w wersji 5.2.0** lub wyższej.

== Installation ==

1. Skopiuj wszystkie pliki do katalogu **/wp-content/plugins/wp-blipbot/**
2. Aktywuj plugin w panelu administracyjnym
3. Skonfiruruj ustawienia pluginu w zakładce **Ustawienia/WP BlipBot**

== Screenshots ==

1. Strona konfiguracji wtyczki WP BlipBot

== Other Notes == 

**Historia wersji:**

* 3.0.9 Drobne poprawki w kodzie
* 3.0.7 Załatanie błędu zgłoszonego przez Kubofonista
* 3.0.6 Drobne poprawki w kodzie
* 3.0.5 Drobne poprawki w kodzie, zmiana niektórych funkcji (rozwiązanie problemu niedziałania wtyczki na niektórych hostingach)
* 3.0.3 Drobne poprawki w kodzie
* 3.0.2 Drobne poprawki w kodzie
* 3.0.1 Drobne poprawki w kodzie
* 3.0.0 Zmieniono sposób autoryzacji wtyczki w serwisie Blip. Teraz autoryzacja odbywa się przez OAuth (bez podawania hasła użytkownika)
* 2.6.1 Zastosowanie najnowszej wersji phpAPI; dodanie możliwości indywidualnej zmiany wysyłanego statusu dla każdego postu; dodanie obsługo blogów postawionych na domenach IDN
* 2.5.10 Wyrzucenie z kodu złośliwego chochlika, który wkradł się w wersji 2.5.9
* 2.5.9 Drobne poprawki w kodzie
* 2.5.8 Drobne poprawki w kodzie
* 2.5.7 Wyrzucenie z kodu złośliwego chochlika, który wkradł się w wersji 2.5.6
* 2.5.6 Usunięto błąd, który uniemożliwiał wtyczce wysłania statusu informującego o publikacji zaplanowanego postu. Ograniczono dostęp do ustawień wtyczki tylko dla administratora bloga
* 2.5.3 Drobne poprawki w kodzie
* 2.5.2 Drobne poprawki w kodzie
* 2.5.1 Naprawiono błąd, przez który wtyczka nie wysyłała statusów w trybie automatycznym
* 2.5.0 Dodano możliwość wysyłania statusów wraz z obrazkiem (obrazek można wybrać podczas edycji postów)
* 2.4.3 Poprawienie kodu wtyczki pod kątem zgodności z WordPress-em w wersji 2.7.1
* 2.4.2 Poprawienie małego błedu w arkuszu stylów, który powodował wyświetlanie niebieskiej obwódki wokół wszystkich nagłówków w panlu administracyjnym.
* 2.4.1 Drobne poprawki w kodzie
* 2.4.0 Zastosowanie nowszej wersji BlipAPI; zmiana nazwy wtyczki na "WP BlipBot"; gruntowne przebudowanie kodu wtyczki
* 2.2.20 Drobne poprawki w kodzie
* 2.2.19 Drobne poprawki w kodzie
* 2.2.18 Drobne poprawki w kodzie
* 2.2.17 Drobne poprawki w kodzie
* 2.2.16 Zmiana serwisu skracającego linki z http://a.gd na http://is.gd
* 2.2.15 Drobne poprawki w kodzie
* 2.2.14 Drobne poprawki w kodzie
* 2.2.12 Drobne poprawki w kodzie
* 2.2.6 Wyrzucenie z kodu złośliwego chochlika, który wkradł się w wersji 2.2.3
* 2.2.3 Załatanie błędu związanego z obsługą API Blipa
* 2.2.2 Powiadomienie właściciela bloga o nowych komentarzach dorobiło się możliwości konfiguracji i personalizacji
* 2.2.1 Drobne poprawki w kodzie
* 2.2.0 Wtyczka wzbogaciła się o możliwość powiadamiania właściciela bloga o nowych komentarzach poprzez kierowane wiadomości na **Blipie**
* 2.1.2 Wyeliminowano błąd powodujący konflikt z wtyczką **WP-PostRatings**
* 2.1.1 Drobne poprawki w kodzie wtyczki
* 2.1.0 Wprowadzono możliwość wyłączenia automatycznego wysyłania statusów po publikacji posta oraz ręcznego wysyłania statusów z poziomu panelu administratora. Dodatkowo dodano dwa nowe znaczniki, których można użyć w treści statusu - **%autor%** oraz **%tagi%**
* 2.0.7 Poprawiono błąd związany z dodawaniem w treści wiadomości znaku **\** przed takie znaki jak **"** i **'**
* 2.0.6 Drobne poprawki w kodzie
* 2.0.5 Część funkcji wtyczki zostało napisanych od zera
* 1.2.1 Wprowadzono wiele poprawek optymalizujących kod wtyczki
* 0.7.0 Pierwsza publicznie dostępna wersja wtyczki

== Frequently Asked Questions ==

= Czy WP-BlipBot ma jakieś specjalne wymagania? =
Tak. Do poprawnego działania wtyczki wymagane jest posiadanie serwera PHP w wersji 5.2 lub wyższej.
