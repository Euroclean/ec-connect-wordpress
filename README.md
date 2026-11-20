# EC Connect WordPress 0.6.0

Průvodce připojením webu k EC Hubu. EC Hub používá jen standardní REST API WordPressu
s heslem aplikace uživatele `ec-hub`; plugin nemá vlastní API ani přihlašování.

- Nastavení → EC Connect → zvolit rozsah (Přispěvatel / Autor / Editor) → Připojit k EC Hubu.
  Plugin založí uživatele `ec-hub` (označený meta `ec_hub_connect_managed`), nastaví roli,
  vytvoří heslo aplikace „EC Hub“ (app_id 7c1e4f52-2b9d-4a63-8f0e-5d3a9b6c1e27) a jednou
  zobrazí párovací kód `ECHUB-WP1.<base64url JSON {v,site,rest,user,password}>`.
- Uživatel `ec-hub` se nemůže přihlásit formulářem ani resetovat heslo (wp_authenticate_user,
  allow_password_reset); heslo aplikace se ověřuje jinou cestou jádra a funguje.
- Odpojit = zrušení hesla aplikace + odebrání role (autorství obsahu zůstává).
- Zpřístupnění v API: u vybraných typů obsahu / taxonomií bez REST zapne `show_in_rest`
  (register_post_type_args / register_taxonomy_args); typ si ponechá dosavadní editor.
- Yoast SEO (0.3.0): když je Yoast aktivní, zaregistruje `_yoast_wpseo_title`, `_yoast_wpseo_metadesc`,
  `_yoast_wpseo_focuskw` jako meta v REST API pro všechny typy obsahu (register_post_meta, show_in_rest,
  auth_callback = edit_post daného příspěvku, sanitace textu). Hub tak zapisuje SEO titulek, popis a klíčové slovo.
- Kalendář Stachethemes 5.x (0.4.0): volba „Povolit EC Hubu správu kalendáře“ (volba `ec_hub_connect_stec`).
  Jen pro uživatele ec-hub přes veřejné filtry Stachethemes: `manage_stec_event`, `manage_stec_cal|cat|loc|org|gst`
  a `stec_user_permissions_list` (doplní oprávnění vyskytující se v kalendáři a autory akcí/položek – „Soukromé“
  = autor). Na akcích ani v nastavení kalendáře se nic nemění. Jiná hlavní verze než 5.x se nenapojí (varování v nastavení).
- Pojistka (0.5.0): filtr `user_has_cap` – ec-hub nikdy nemá správcovská oprávnění (pluginy, šablony, uživatelé,
  nastavení, soubory, aktualizace, import/export) a ve výchozím stavu ani `unfiltered_html` (WordPress obsah čistí – kses).
  Volba `ec_hub_connect_unfiltered_html` to může povolit. REST pole `ec_hub_connect` u uživatele (jen sám ec-hub,
  kontext edit): verze pluginu, unfiltered_html, calendar. Zpřístupnění v API jen pro veřejné typy/taxonomie;
  Yoast pole jen v kontextu úprav.
- 0.5.1: hlavička `X-EC-Hub-Connect` (verze, unfiltered, calendar, admin) u každé odpovědi REST API pro ec-hub – Hub zná nastavení bez dotazu navíc; texty bez „Znovu načíst“.
- Odinstalace: zruší heslo EC Hub, odebere roli, smaže volby (včetně volby 0.1.0).
- Nahrazuje pilotní plugin „EC Hub Connect“ 0.1.0 (složka ec-hub-connect, vlastní REST API ec-hub/v1): ten se před instalací deaktivuje a smaže; 0.2.0 při spuštění smaže i jeho zbylou volbu.

Balení: ZIP se složkou `ec-connect-wordpress/` uvnitř (bez README a tests), nahrává se nerozbalený
přes Pluginy → Instalovat → Nahrát plugin.
Testy: `php tests/harness.php ec-connect-wordpress/ec-connect-wordpress.php` (atrapy funkcí WordPressu).

## Aktualizace (0.6.0)

Plugin se aktualizuje standardní cestou WordPressu pro pluginy mimo katalog (hlavička `Update URI`, WordPress 5.8+):

- Jednou za 12 hodin si přečte `update.json` z tohoto repozitáře (větev `main`).
- Je-li tam novější verze, WordPress nabídne „Aktualizovat nyní“; funguje i „Povolit automatické aktualizace“.
- Balíček se stahuje jen z vydání tohoto repozitáře (`releases/download/vX.Y.Z/ec-connect-wordpress-X.Y.Z.zip`)
  a před instalací se ověří jeho SHA-256 proti `update.json`. Nesouhlasí-li, aktualizace se neprovede.

Vydání nové verze: nahrát zdrojové soubory, vytvořit release `vX.Y.Z` s přiloženým ZIPem a nakonec upravit `update.json`
(verze, adresa balíčku, SHA-256 ZIPu).
