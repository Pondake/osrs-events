# Backlog

Wat er nog te doen is, en niets anders. Geen historie, geen afgevinkte items.

**Geopend 2026-08-30.** De vorige backlog was 5432 regels — 241 afgevinkte
items tegen 38 open, verspreid over ~90 kopjes waarvan 30 gedateerde secties
alleen al uit de laatste week. Hij was een werklogboek geworden en de open
items lagen erin verstopt. Die file staat ongewijzigd in
`docs/backlog-archive-2026-08.md`: **niets is verwijderd**, want een `[x]` is
"ik heb het gebouwd" en niet "het werkt" — dat tweede oordeel is aan de
eigenaar, en het archief is waar hij het maakt.

Twee dingen om te weten bij die verhuizing:

- **Codecommentaar dat naar `docs/backlog.md` verwijst bedoelt het archief.**
  Ruim tien plekken in `app/`, `database/` en `resources/js` doen dat als
  herkomstnotitie — dezelfde afspraak die `CLAUDE.md` al hanteert voor de
  verwijderde `stale/`-map.
- **De SSR-gotchas staan nu in `docs/ssr-gotchas.md`.** Dat was een staande
  referentie in een to-do-lijst, geen taak; `CLAUDE.md` wijst er nu direct
  naar.

Speculatief werk — nieuwe eventtypes, de CMS-layouteditor, de clan directory —
staat niet hier maar in `docs/ideas.md`. Dit bestand is alleen wat er op
afzienbare termijn opgepakt wordt.

**Werkafspraak, ongewijzigd:** een afgerond item krijgt `[x]` plus een notitie
over wat er gebouwd is, en blijft staan. Alleen de eigenaar verwijdert het,
nadat het geverifieerd is.

---

## 0. Live gaan

De rest van dit bestand hangt hier grotendeels achteraan. De SEO-sectie zei het
op 2026-08-21 al: **niets daarvan doet ertoe tot er gedeployed is** — en er is
sindsdien negen dagen aan werk bijgekomen dat ook niet live staat.

- [ ] **Live achter de pre-launch deur, met een oproep voor beta-testers.**
  Besloten 2026-08-30, en het lost de patstelling op die hiervoor stond: er kon
  niet aangekondigd worden omdat de site niet live was, en er kon niet live
  gegaan worden omdat de Jagex-vraag open stond. Live gaan mét de deur dicht
  doet allebei — vreemden zien een deur, geen app, en de aankondiging kan wel
  de wereld in.
  **Gebruik de pre-launch deur, niet full lockdown.** Die twee zijn los van
  elkaar (`EnsureSiteUnlocked`): de deur houdt de *app* dicht maar laat de
  landingspagina's, de guides en de CMS-pagina's gewoon staan — precies de
  pagina's die een zoekmachine indexeert en waar iemand vanuit een
  Discord-post op landt. Full lockdown laat niemand door behalve een admin en
  zou §2 en §3 zinloos maken zolang hij aanstaat.
  **Dit vervangt het geparkeerde JMod-punt.** Een zachte, publieke beta met de
  §8.1-disclaimer al op zijn plek nodigt uit dat een mod meekijkt als die dat
  wil, zonder dat er iemand benaderd hoeft te worden. Dat is een betere eerste
  indruk dan een DM met de vraag of het mag.
- [x] ~~**Discord-signup blijft geweigerd achter de deur, ook mét het
  wachtwoord.**~~ — gefixt 2026-08-30. `registrationClosed()` neemt nu de
  `Request` en leest de unlock-sessievlag, en is herschreven van één
  `||`-keten naar drie losse checks in volgorde van gezag:
  `registration_open` eerst (een expliciet nee van een admin wint van alles),
  dan full lockdown (nooit verzacht, door niets), dan pas de pre-launch deur —
  die alleen nog dicht is voor wie de vlag niet heeft. Drie nieuwe tests in
  `DiscordRegistrationLockTest` pinnen precies die rangorde vast, en de vier
  bestaande blijven groen: een échte vreemde zonder vlag wordt nog steeds
  geweigerd. Suite 752 groen, exit 0.
  **Niet in een browser bevestigd, en dat kan hier ook niet**: de flow vraagt
  een echte Discord-OAuth-round-trip met credentials die deze omgeving niet
  heeft. De tests mocken Socialite, wat voor dit gedrag de juiste
  verificatie is — maar de eerste echte beta-tester die via Discord binnenkomt
  is alsnog het bewijs dat telt.
  De oorspronkelijke analyse, bewaard omdat de redenering nog geldt:
  **De e-mailroute werkt al.** Wie het gedeelde wachtwoord intypt krijgt de
  sessievlag, `EnsureSiteUnlocked::isShutFor()` geeft daarna `false`, en de
  middleware laat álles door — inclusief `/register`, dat zelf alleen naar
  `registration_open` kijkt. Het wachtwoord ís daar dus al de uitnodiging,
  zonder één regel code erbij. Vastgepind in `SiteLockTest`
  (`the_shared_password_lets_a_newcomer_register`), samen met de tegenhanger:
  een admin die `registration_open` uitzet wint nog steeds van het
  wachtwoord.
  **Discord is de helft die niet meedoet.** `auth/discord/*` is uit de deur
  gesneden (anders kon niemand inloggen), dus die aanvraag wordt nooit door de
  middleware beoordeeld — en `DiscordController::registrationClosed()` leest
  `site_lock_enabled` rechtstreeks in plaats van de sessievlag. Een tester die
  het wachtwoord al heeft ingetypt en dan "Continue with Discord" kiest, wordt
  alsnog geweigerd (`DiscordRegistrationLockTest`:
  `a_stranger_cannot_make_an_account_through_discord_while_locked`).
  Voor een Discord-first beta was dat precies de verkeerde helft om dicht te
  hebben — één persoon kreeg twee verschillende antwoorden afhankelijk van
  welke knop hij koos, en Discord is de knop die een clan werkelijk gebruikt.
  Invite-links zijn overwogen en **afgevallen** (2026-08-30): het gedeelde
  wachtwoord is genoeg voor een beta van deze omvang, en een invite-tabel met
  adminscherm is meer machinerie dan de vraag rechtvaardigt.
- [ ] **Eigen Discord-server als thuisbasis voor de beta.** Aangemaakt
  2026-08-30 door de eigenaar. Wat hij oplevert, in volgorde van waarde:
  een plek om het beta-wachtwoord en de aankondiging te delen; **de kamer
  waarin de webhook-announcements eindelijk getest kunnen worden** (zie
  §5 — dat item vroeg om "een mens die naar de eerste post kijkt" en om een
  kamer die van jezelf is, en dit is er een); en een support-kanaal waardoor de
  ideeën-/feedbackpagina uit §3 minder dringend wordt dan hij nu lijkt.
  Kanaalindeling: read-only `#announcements` (waar de app-webhook in post),
  `#beta-feedback`, `#support`, `#general`, en een read-only `#dev-log`.
  **`#support` en `#bug-reports` zijn samengevoegd** — terecht opgemerkt dat
  die dubbelen: iemand die vastloopt weet zelf niet of het een bug of een
  vraag is, en twee kanalen dwingen hem die diagnose te stellen voordat hij
  hulp krijgt. Eén `#support` voor mensen; wat een echte bug blijkt wordt een
  GitHub-issue.
  `#dev-log` vult zichzelf: GitHub kan native naar Discord posten door `/github`
  achter een Discord-webhook-URL te plakken — issues, PR's en commits, nul
  code aan onze kant. De repo is publiek, dus dat werkt meteen. Dat maakt
  bugmeldingen zichtbaar in Discord zonder dat er een tweede plek ontstaat
  waar ze bijgehouden moeten worden.
- [ ] **Deploy.** `osrs-events.com` resolvet en is geïndexeerd, maar draait
  oude code: de copy die Google citeert is van vóór de rewrite, en
  `robots.txt` 404't in productie terwijl hij in de repo staat — wat zegt dat
  de deploy ouder is dan dat bestand. Let bij het deployen op gotcha 16 in
  `docs/ssr-gotchas.md`: een SSR-bundle die wel herbouwd maar niet herstart
  wordt blijft de vorige build serveren, en een gedeeltelijke SSR-uitval ziet
  er in een browser uit als niets aan de hand.
- [ ] **Livegangdatum vastleggen** zodra hij live gaat. Nodig voor het
  portfolio-artikel (veld `end_date` / livegang) en over een maand niet meer
  te achterhalen.
- [ ] **Announcement in de clan en op Reddit.** De eigenaar wil beide doen.
  Hangt achter de beslissing bovenaan, en Reddit wil een eigen leesronde — een
  linkdrop in een OSRS-sub is een ander gesprek dan een bericht in je eigen
  clan.
- [x] ~~**Per-announcement zichtbaarheidsschakelaar op het lock screen.**~~ —
  gebouwd 2026-08-30. Nieuwe setting `announcement_public`, standaard **uit**:
  een announcement is normaal geschreven voor wie de site al gebruikt, en het
  lock screen is de enige pagina die een vreemde kan bereiken. Aan, mag de
  deur wél iets zeggen — een livegangdatum, een oproep voor beta-testers, een
  Discord-link.
  De prop wordt nog steeds **withheld** in plaats van client-side verborgen:
  `HandleInertiaRequests` geeft de tekst alleen mee als de bezoeker langs de
  deur is óf de announcement publiek is gezet. Wat de browser bereikt is
  openbaar, wat de template er daarna mee doet is niet het slot.
  Het lock screen rendert de banner zelf, want `AppRoot` onderdrukt daar de
  chrome (`CHROMELESS_PAGES`). **Onderweg opgeruimd:** de kleurmaps
  (`BANNER_BG`/`BANNER_ICON`) stonden hardcoded in `AppRoot` terwijl
  `Support/announcement.js` juist bestaat omdat meerdere plekken dezelfde
  banner tekenen — met het lock screen werden dat er drie, dus die zijn
  verhuisd naar `bannerBgFor()`/`bannerIconFor()`.
  Geverifieerd tegen de echte server-HTML, als gast zonder sessie: met de
  schakelaar aan staat de tekst in de SSR-output van `/locked` (dus ook
  zonder JavaScript), met hem uit staat hij er niet in, en publiek zetten
  opent verder niets — `/events` blijft doorverwijzen naar de deur. Drie
  tests in `SiteLockTest` leggen precies die drie vast.
  **Ook een valse-groene test gevonden en gerepareerd**:
  `saving_the_settings_form_without_a_password_keeps_the_existing_one` postte
  het adminformulier met drie ontbrekende verplichte velden en toetste alleen
  `assertRedirect()` — maar een validatiefout ís ook een redirect, dus die
  test slaagde zonder dat het formulier ooit opsloeg. Gevonden doordat een
  vierde verplicht veld toevoegen een ándere test liet vallen terwijl deze
  groen bleef. Nu compleet, met `assertSessionHasNoErrors()`.

## 1. Legal — de eigen pass van de eigenaar

De pagina's zijn accuraat; dat was de technische helft. Dit is de andere. Geen
van deze is juridisch advies, en de eerste twee zijn oordeelsvragen en geen
implementatievragen. `docs/legal-review.md` houdt de toegepaste wijzigingen en
de kleinere open vragen bij.

- [x] **Gelicenseerde policy-bronnen bekijken in plaats van nog meer met de
  hand schrijven.** — besloten 2026-09-07: **niet overnemen, hooguit de
  indeling lenen**, precies de helft die hieronder al de voorkeur had. Geen
  tekstwijziging; wat dit item opleverde is de licentiecontrole eronder, en de
  reden om er niet nog een ronde in te steken.
  De oorspronkelijke afweging, bewaard omdat hij nog geldt:
  De huidige tekst is uit het schema geschreven: dat is zijn
  kracht en zijn plafond — hij beschrijft deze app precies en mist alle
  structuur en jurisdictie-boilerplate die een template meebrengt. **De eerste
  beslissing is welke helft waarvandaan komt**: houd de accurate beschrijving
  van wat er opgeslagen wordt, en laat een template de steiger eromheen
  leveren. In zijn geheel omwisselen ruilt iets waars in voor iets generieks.
  Leads om te controleren, niet te vertrouwen: permissief gelicenseerde
  policies van andere open projecten (die van Automattic zijn CC en breed
  overgenomen) en de policy-generators. **Controleer de licentievoorwaarden
  zelf** — "gratis te gebruiken" en "vrij aan te passen en onder je eigen naam
  te publiceren" zijn niet dezelfde toestemming.
  **Nagekeken 2026-09-07, en dit is precies zo'n geval:** `Automattic/legalmattic`
  staat onder **CC BY-SA 4.0**, niet onder een permissieve licentie. Attribution
  én ShareAlike — wie die tekst bewerkt moet naar Automattic linken *en* het
  resultaat onder dezelfde licentie publiceren. Voor een privacypagina is dat
  geen detail: je zet er een licentieverklaring onder die zelf uitleg vraagt.
  Dat maakt de keuze die hierboven al de voorkeur had ook de goedkoopste: neem
  hooguit de **volgorde en de kopjes** over — een indeling is geen beschermde
  tekst — en schrijf de alinea's zelf uit het schema, zoals nu. Dan raakt de
  licentie het nooit.
- [ ] **Geschreven voor de wereld, niet voor Nederland.** De spelersbasis zit
  waar OSRS zit, dus alles wat als EU-only ceremonie leest heeft de verkeerde
  vorm. Het goede nieuws: de twee feiten die dit het meest vereenvoudigen zijn
  al waar en staan er al — **er wordt niets verkocht, en er is geen analytics,
  advertising of third-party tracking van welke soort dan ook.** De meeste
  jurisdictie-specifieke machinerie klapt daarmee samen tot één zin. Die
  framing bewaken wanneer een template hem terug probeert te zetten.
- [x] **Of er überhaupt een persoonlijk e-mailadres gepubliceerd moet worden.**
  — besloten 2026-09-07: **`dev@absolit.nl` blijft op `/privacy` staan**, omdat
  daar een route moet zijn voor wie niet meer bij zijn account kan en de knop
  dus niet kan indrukken. Wat de vraag oploste was dat het adres al op
  `/about` stond: alleen bij `/privacy` weghalen publiceert niets minder, het
  haalt de route alleen weg bij de pagina waar iemand ernaar zoekt. Twee
  vervolgpunten hieronder, allebei nieuw: het adres bij `/about` weg, en later
  een fatsoenlijk adres in plaats van dit.
  De oorspronkelijke afweging, bewaard omdat hij nog geldt:
  Nu `mailto:dev@absolit.nl` op `/privacy`. *Vindbaar* en *gepubliceerd* zijn
  niet dezelfde handeling, en welke van de twee die pagina verricht is nog een
  keuze — het GitHub-account is publiek, dus wie wil komt er sowieso.
  **De feitelijke helft is wel af.** De alinea zei "vraag het en je account
  wordt verwijderd", wat waar was toen dat de enige route was; Settings →
  Account heeft sinds 2026-08-24 een verwijderknop. Op 2026-08-27 is
  `LegalPages::privacy()` herschreven en dat is meegelift in commit `909fdd9`
  (de guides-commit), waardoor het hier nooit is afgevinkt. De sectie "Your
  data is yours" wijst nu eerst naar Settings → Account — knop `Delete your
  account` → `/settings/account`, `variant: outline` — en het adres staat
  eronder als `Ask us to do it` in `ghost`, met een alinea die zegt wanneer je
  die nodig hebt (je komt niet meer bij de pagina). Nagelopen op 2026-09-07 in
  de browser op `localhost:8010/privacy`: beide links staan er met de juiste
  hrefs. `pages:sync-legal --diff` zegt lokaal "already matches", maar **op
  elke andere omgeving moet `php artisan pages:sync-legal` nog draaien** — het
  bestand aanpassen doet niets waar de paginarij al bestaat.
- [ ] **Een eigen adres in plaats van `dev@absolit.nl`.** Zoiets als
  `support@osrs-events.com` — bestaat nog niet, dus dit hangt achter een
  mailbox en niet achter een beslissing. Wanneer het er is: alleen `/privacy`
  (`LegalPages::privacy()`, gevolgd door `php artisan pages:sync-legal`).
  `/about` staat er sinds 2026-09-07 niet meer bij — die pagina publiceert
  geen adres meer en hoeft er ook geen terug te krijgen; contact loopt daar
  via Discord. Tot die tijd blijft het huidige adres op `/privacy` staan; een
  pagina zonder contactroute is slechter dan een pagina met een persoonlijke.
- [x] **`/about` opnieuw, en het e-mailadres gaat er daarbij af.** — gebouwd
  2026-09-07. De pagina is **van de CMS af**, dezelfde stap die de zes guides
  op 2026-08-27 zetten (commit `909fdd9`): een vaste route naar
  `LandingController::about()`, een `Pages/About.vue`, en de teksten in
  `lang/en.json`. Dat was ook het antwoord op de seeder-val — de derde optie
  naast "sync-command uitbreiden" en "met de hand in admin → Content", en de
  enige waarbij het adres verdwijnt zónder dat iemand ergens iets moet
  draaien. Een achtergebleven `about`-rij staat nu in `Page::PARTIAL_SLUGS`,
  dus hij is nergens meer te zien en er hoefde geen migratie content te
  verwijderen.
  **Wat de layout geworden is:** geen `u-page`/`u-page-section` meer, maar ook
  niet `GuideLayout` — die zijbalk is een inhoudsopgave en een lijst andere
  guides, en dit is geen guide. In plaats daarvan panelen met een titelbalk,
  die de OSRS-bevel uit `.landing-page` meepakken: de zes features in één
  kader met haarlijnen ertussen, privacy over de volle breedte, en daaronder
  Free en Support naast elkaar. De hele pagina past nu in ruim één scherm.
  **Het adres:** `about.support_email` en `about.support_body` zijn weg;
  contact loopt via de Discord-server, en dat paneel rendert alleen als er een
  invite is ingesteld — geen kopje "Support" boven niks. `about.disclaimer_body`
  is ook weg: die zei de Jagex-regel in eigen woorden terwijl de footer al
  `common.not_affiliated` toont, en twee versies van een verplichte
  attributie is hoe er één verkeerd raakt. `/privacy` is niet aangeraakt.
  **Onderweg gevonden:** `text-*` op een `<h1>`–`<h3>` doet in deze codebase
  niets. `About.vue` zet de maat daarom op een `<span>` erbinnen; het bredere
  probleem staat als eigen item in §6.
- [ ] **`/about` weer bewerkbaar maken vanuit de CMS.** Bewust ingeleverd op
  2026-09-07: de pagina staat nu in code, dus elke tekstwijziging kost een
  deploy in plaats van een formulier. Dat is precies wat je niet wilt op de
  ene pagina die je nog wel eens aanpast. Wat het echt vraagt is niet "zet hem
  terug in de `pages`-tabel" — dat is waar hij vandaan komt — maar de twee
  dingen oplossen die hem eruit joegen:
  1. **Bereik.** Een seeder met `firstOrCreate` bereikt een bestaande rij
     nooit, en dat is niet fout maar wél stil. Wat ontbreekt is dat iemand
     *ziet* dat repo en database uit elkaar lopen; `pages:sync-legal --diff`
     doet dat voor twee pagina's en niemand draait het uit zichzelf. Een
     diagnostiek-check die zegt "deze pagina wijkt af van wat de repo denkt"
     zou het probleem bij de wortel pakken, voor alle CMS-pagina's tegelijk.
  2. **Vorm.** De blokken-woordenschat rendert via `u-page-section` — dat is
     de marketing-vorm waar deze pagina juist vanaf moest. Terugzetten heeft
     pas zin als de renderer de paneelvorm óók kan, bijvoorbeeld als variant
     op het `section`-blok. Anders ruil je bewerkbaarheid weer in voor de
     layout die net is weggehaald.
  Tot dat er is: de teksten staan in `lang/en.json` onder `about.*` en de
  opbouw in `resources/js/Pages/About.vue`.
- [ ] **Bewaartermijn voor sessies en push-subscriptions.** De audit log heeft
  er een (90 dagen, uit `config/audit.php`, vastgepind door een test). Sessies
  verlopen en push-subscriptions worden dood gemarkeerd op een 404/410, maar
  geen van beide heeft een uitgesproken maximumleeftijd. Wil je daar een getal,
  dan heeft de code er eerst een nodig.
  **Wat de code nu doet, nagekeken 2026-09-07 — de twee helften kosten niet
  hetzelfde.** Sessies hebben het getal al: driver `database`,
  `SESSION_LIFETIME=120`, en Laravel's eigen GC ruimt rijen op die langer dan
  dat inactief zijn. Dat is een bewaartermijn van twee uur na laatste
  activiteit die alleen nog nergens uitgesproken wordt; die opschrijven kost
  één alinea en geen code. Push-subscriptions hebben het getal níet:
  `expired_at` markeert een dood abonnement maar niets verwijdert de rij, en
  `model:prune` is in `routes/console.php` bewust versmald tot alleen
  `AuditLog`. Daar is dus wél code voor nodig — `Prunable` op
  `PushSubscription`, een `config/push.php`-waarde, het model erbij in de
  schedule — en `last_used_at` staat al op de tabel, dus "zoveel dagen niet
  gebruikt" is meteen de bruikbaarste maat.
  **De sessiehelft is af (2026-09-07).** "Staying signed in" zegt nu dat een
  sessie die je met rust laat na 2 uur inactiviteit verdwijnt, en dat dat de
  hele bewaartermijn is. Het getal komt uit `config/session.php` in plaats van
  dat het is ingetypt: `LegalPages::sessionLifetime()` leest het en
  `LegalPagesTest::the_privacy_page_states_how_long_an_idle_session_is_kept`
  pint het vast, dezelfde constructie als de 90 dagen van de audit log — wie
  `SESSION_LIFETIME` verlaagt zonder de tekst aan te raken krijgt een rode
  test in plaats van een pagina die te veel belooft. Bevestigd op
  `localhost:8010/privacy` na `pages:sync-legal`.
  **Push-subscriptions blijven open**, want dat is de helft die eerst code
  vraagt; daarom staat dit item nog op `[ ]`.

## 2. SEO — on-page werk

Hangt volledig achter de deploy. Eén conclusie vooraf, zodat hij niet opnieuw
onderzocht wordt: **"OSRS events" en "runescape events" zijn geen winbare
termen.** Die SERP is van de OSRS Wiki (in-game random en holiday events) en
van Jagex zelf; de naam van het product botst met een informationele term die
de wiki bezit, en dat verkeer zou niet converteren als het aankwam. Behandel ze
als merktermen om op *gevonden* te worden, niet als termen om op te
concurreren. De winbare termen zijn de specifieke: `/osrs-event-ideas` staat
tegenover forumdraadjes en Fandom-wiki's zonder één sterke commerciële pagina
in de resultaten, en is de meest waarschijnlijke eerste winst. De echte
concurrentieset is de bingo-tooling (`osrsbingohub.com`, `aiobingo.com`,
`rune-bingo.com`), niet de wiki.

- [ ] **`Organization` + `WebSite` JSON-LD op de homepage.** Niets
  identificeert de site nu als entiteit, en dat is precies wat een
  navigationele merkquery nodig heeft. Hoogste waarde van deze lijst.
- [ ] **`/events` heeft geen schema en geen SeoHead-meta**, terwijl het een
  publieke, crawlbare index is — en de natuurlijke landingspagina voor "OSRS
  clan events".
- [ ] **`ItemList`/`Event`-schema op de publieke eventsindex.** De enige
  pagina waarvan de inhoud werkelijk een lijst met events *is*.
- [ ] **Landingspagina's zijn ~600 woorden elk.** Toereikend, niet
  overtuigend, voor iets waar concurrentie op zit.
- [ ] **Google Search Console koppelen, en zijn cijfers in de adminsectie
  tonen.** Twee stappen. Eerst: de site verifiëren (een meta-tag of een
  DNS-record) en de sitemap indienen zodra de deploy er staat. Dat alleen al
  beantwoordt vragen die nu open staan — is `robots.txt` gelezen, is de
  sitemap geaccepteerd, welke pagina's zijn geïndexeerd en welke uitgesloten
  en waarom, en verschijnt de site werkelijk op zijn eigen merknaam. Het is
  ook de enige manier om de conclusie hierboven over de onwinbare hoofdtermen
  met data te controleren in plaats van met een SERP-leesronde.
  Daarna: de **Search Analytics API** uitlezen naar een adminpagina —
  zoekopdrachten, vertoningen, klikken, gemiddelde positie per pagina. Zelfde
  vorm als `/admin/diagnostics` al heeft (een service plus een pagina die hem
  rendert), dus daar is een patroon voor. Let op de regel die voor die pagina
  al geldt: **niets wat een pagina toont mag een secret zijn** — de
  API-credentials horen in `.env` en nooit in een prop.
  **Bewust géén Google Analytics.** Search Console zet niets op de site: geen
  script, geen cookie, geen bezoekersdata, dus niets om in de privacyverklaring
  te melden en geen consent-vraag. Analytics doet dat allemaal wél, en breekt
  daarmee de ene zin waar de hele juridische sectie hierboven op rust — er
  wordt niets verkocht en er is geen analytics, advertising of tracking van
  welke soort dan ook. Dat is een dure ruil voor cijfers die de eigenaar
  alleen voor zichzelf bekijkt. Wil je toch bezoekersaantallen, tel ze dan
  server-side uit de eigen database (`audit_logs`, sessies,
  deelnemersaantallen): geen derde partij, geen cookie, geen beleidswijziging.
  Zelfde afweging als waarom advertenties in 2026-08 zijn afgeblazen.

## 3. Guides en content

- [ ] **Screenshots in de Snakes & Ladders-guide.** Twee gemarkeerde
  `<!-- Screenshot slot -->`-plekken in `SnakesAndLadders.vue` (de tile-editor
  midden in een wiki-search, en het levende bord). Een echt gevuld demobord
  ("Summer Grind Board", account `claude-demo@absolit.nl`, zie de docblock van
  die seeder) staat er speciaal voor klaar en rendert exact goed. Wat het de
  vorige keer blokkeerde: geen enkele tool in die sessie kreeg een gemaakte
  screenshot vanuit de browser op een lokaal leesbaar pad — Downloads, alle
  drie de Chrome-profielen en elke Claude-cachemap zijn doorzocht. **Zet er
  gewoon echte PNG's in `public/images/guides/` neer** in plaats van die
  zoektocht opnieuw te openen.
- [ ] **Bingo, Skill of the Month en Drop Race hebben geen guidepagina.**
  Alleen `/osrs-clan-events` (een algemeen "wat is dit platform"-overzicht, geen
  walkthrough per format) en `/osrs-event-ideas` bestaan. De S&L-guide is op
  2026-08-27 herschreven naar twee expliciet gelabelde sporen — **Een event
  draaien** (5 stappen) en **Spelen** (4 stappen), elk met eigen nummering —
  omdat één platte lijst host- en spelershandelingen door elkaar haalde zonder
  te signaleren dat het publiek halverwege wisselde. Dat is het model voor de
  andere drie. Statische content blijft hier de juiste keuze; niet wachten tot
  de CMS-blokkenwoordenschat dit aankan.
- [ ] **Guidesectie: hoe maak je een claim-screenshot die standhoudt.** Wat er
  zichtbaar moet zijn (gebruikersnaam, timestamp, het specifieke
  drop-bericht of collection-log-slot) wordt nu alleen geïmpliceerd door één
  regel copy in `bingo.claim_intro`. Verdient een echte sectie met
  voorbeeldafbeelding op de guidepagina's, niet een tooltip weggestopt in
  `BingoClaimModal.vue` — een afgewezen claim door een slechte screenshot is
  precies de wrijving waarvoor een guidepagina bestaat. Geldt even hard met of
  zonder RuneLite-plugin.
- [ ] **Ideeën-/feedbackpagina.** Een formulier (op de guides, in de footer of
  op een eigen route) waar iedereen een idee of stuk feedback kan indienen.
  Nodig: honeypot-veld, rate limiting, simpele spamheuristiek op titel +
  omschrijving (markeer waarschijnlijke spam in plaats van weigeren; verwijder
  alleen het ondubbelzinnige), een admin-CRUD om inzendingen te beoordelen, en
  bij een niet-spam-inzending zowel een mail aan de eigenaar als een
  pushnotificatie — via de `NotificationCategory`-catalogus en `PushNotifier`,
  volgens het patroon dat `CLAUDE.md` voor elke nieuwe categorie voorschrijft.

- [x] ~~**Instructiepagina voor betatesters.**~~ — gebouwd 2026-09-07 op
  `/beta` (`Beta.vue`, `LandingController::beta()`, `beta.*` in
  `lang/en.json`). Zelfde `GuideLayout` als de zes guides, met de twee sporen
  die de S&L-guide al hanteert: **Als host** (vijf stappen) en **Als speler**
  (vier), plus binnenkomen, hoe je iets meldt, wat al bekend is en twee
  huisregels.
  **Publiek, en met opzet vóór de deur.** De link wordt in Discord uitgedeeld
  aan mensen die het wachtwoord nog moeten intypen, dus een pagina áchter de
  deur zou alleen leesbaar zijn voor wie hem niet meer nodig heeft — daarom
  staat `beta` in `EnsureSiteUnlocked::PUBLIC_ROUTES`. Het wachtwoord staat er
  niet op, en de pagina zegt dat ook.
  **`noindex, follow`, en niet in de sitemap.** Een zoekresultaat naar een
  gesloten beta kost een bezoeker een klik en levert hem niets; `follow` omdat
  de guides waarnaar hij linkt wél geïndexeerd horen te worden.
  **Nieuw: de setting `discord_invite_url`** (admin → Site settings → Support,
  naast de Ko-fi-URL). De invite-URL hoort niet in deze repo — wie hem heeft
  loopt de server binnen — dus hij staat in de database van de omgeving die
  hem gebruikt. Leeg betekent geen knop, niet een knop naar niets. §7 hoeft
  dat veld dus niet opnieuw te verzinnen voor het lock screen en de
  landingspagina.
  Geverifieerd tegen de echte SSR-output op `localhost:8010`: 200 met de deur
  dicht en zonder sessie, `<meta name="robots" content="noindex, follow">` en
  de canonical in de server-HTML, `/beta` afwezig uit `/sitemap.xml`, alle zes
  ankers uit de zijbalk landen op een bestaande sectie, en de pagina leest op
  375px. Zes tests in `BetaPageTest`; suite 856 groen.
  **Wat er níet in zit:** het `noindex`-metatag zelf is niet in phpunit te
  toetsen — die komt uit Inertia's SSR-render, die onder phpunit niet draait.
  De docblock van die test legt uit waarom dat geen gat is maar een verkeerde
  plek om te meten.
  **Onderweg gevonden en gerepareerd:** de nieuwe setting liet
  `EventDurationTest::an_admin_can_save_a_short_form` vallen — die helper
  postte het formulier zonder het nieuwe veld, en de controller las de key
  onvoorwaardelijk uit `$data`, waar een `nullable`-regel hem weglaat als de
  request hem niet stuurt. Dat is een fatale fout die alsnog redirect zonder
  validatiemeldingen: het formulier lijkt op te slaan en er wordt niets
  geschreven. Nu `?? null`, en de helper stuurt het veld mee.
  **Openstaand, apart gemeld:** de browserconsole logt op élke pagina —
  homepage en bestaande guides incluis, dus los van deze pagina — meerdere
  keren `TypeError: Cannot read properties of undefined (reading 'startsWith')`
  uit Inertia's bundle. Niets is er zichtbaar stuk.

## 4. Vóór de RuneLite-plugin

De teaser staat sinds 2026-08-30 in beide claimformulieren (uitgeschakeld, met
een "Soon"-badge), dus de belofte staat al op het scherm. Deze twee zijn nu
goedkoop en achteraf duur.

- [ ] **`completed_via` zichtbaar maken in de UI**, niet alleen in het schema.
  Een host die een bingo-reviewwachtrij (`BingoReviewModal.vue`) of de
  afgeronde tegels van een S&L-bord bekijkt kan nu niet zien wat door de plugin
  gemeld is en wat handmatig geclaimd. Nodig vóórdat de plugin uitkomt, niet
  erna — anders ziet elke auto-completion eruit als een screenshotclaim, zonder
  enige manier om het later na te trekken.
- [ ] **Per-bord "vertrouw RuneLite"-toggle**, alleen voor eigenaar/host,
  standaard uit. Uit: een plugin-melding landt precies als een handmatige claim
  — zelfde `requires_approval`-poort, zelfde wachtrij, niets overgeslagen. Aan:
  de host verlengt expliciet clanvertrouwen naar zijn eigen spelers en een
  plugin-completion keurt zichzelf goed. Bewust een keuze die de *host* maakt
  over zijn *eigen* clangenoten, geen platformbrede regel — een weekendbordje
  en een competitief clankampioenschap zijn niet dezelfde
  vertrouwensbeslissing, en de toggle is wat ze laat verschillen in plaats van
  één regel voor allebei te kiezen. Vraagt een eigen `Board`-kolom
  (`trust_runelite_completions` of iets dergelijks) plus een tak in wat het
  completion-endpoint van de plugin wordt. **De schemabeslissing is nu goedkoop
  en wordt duur zodra er borden bestaan die er al completions op hebben.**

  Staande ontwerpregel die hierbij hoort en niet onderhandelbaar is: **de
  plugin mag nooit de enige route naar een tegel zijn** ("dit zal elke event
  raken"). Elke taak die een bord kan vragen blijft met de hand afrondbaar —
  en een taaksoort die alleen te verifiëren is door iets dat de plugin
  detecteert en een mens op een screenshot niet kan beoordelen, mag niet
  bestaan.

## 5. Openstaande verificatie

Twee dingen die alleen de eigenaar kan afvinken, allebei omdat ze een echte
tweede partij nodig hebben.

**Waar ze gedraaid worden: `staging.osrs-events.com`, niet Herd** (besloten
2026-09-07). Het item hieronder zei "moet via Herd", en dat klopte zolang
staging er niet was. Nu wel, en staging wint op vier punten die geen van
alle een kwestie van gemak zijn:

- Discord's unfurler moet de link zélf kunnen ophalen. `osrs-events.test`
  bestaat alleen op deze machine — `config('app.url')` is daar letterlijk
  `http://osrs-events.test`, dus dát is de URL die in het kanaal belandt en
  die voor iedereen behalve deze laptop nergens heen wijst. "Resolvet de
  link" is op Herd niet half te doen maar helemaal niet.
- Een tweede mens moet erbij kunnen. Een `.test`-domein resolvet niet buiten
  deze machine; staging is een link die je iemand stuurt.
- `.test` is bovendien afgesproken verboden terrein. Herd zou hier de ene
  genoemde uitzondering inroepen; staging maakt die vraag overbodig.
- Staging draait echte nginx+fpm mét wat ervoor staat, en een echte
  SSR-daemon op `develop`. Een proxy die SSE buffert is precies het soort
  productie-vormige fout die Herd niet kan laten zien — `X-Accel-Buffering:
  no` is in de suite vastgelegd, maar alleen tot aan nginx.

Wat het kost, eerlijk: de branch moet gedeployed zijn en het SSR-proces moet
écht herstart zijn (gotcha 16 — controleer op bytes en `<title>`, niet op hoe
de pagina eruitziet), er moet testdata op staging staan, en de
Discord-schakelaar moet daar aan (en na afloop weer uit). Herd blijft over
voor precies één ding: als staging blijkt te bufferen, zegt Herd of dat de
app is of de proxy. Noem het hardop als je daarvoor terug moet naar `.test`.

De premisse is opnieuw gemeten op 2026-09-07 en staat nog: met één
`EventSource` open op `artisan serve` kreeg een gewone `fetch()` naar
dezelfde origin in 12 seconden niets terug. Gotcha 15 is geen historische
noot.

- [ ] **Multi-viewertest** (geclaimd 2026-08-23). Twee echte browsers, één
  host en één speler, pauzeren en hervatten terwijl beiden kijken. Dat is de
  ene claim die niet vanuit `artisan serve` te maken is, en hij dekt meteen
  het gat dat de fingerprint-ronde openliet.

  *Wat inmiddels in de suite vastligt* (2026-09-07, `EventPauseTest`) — geen
  vervanging voor de test hieronder, wel het deel dat niet meer met de hand
  hoeft: `a_paused_event_still_streams_to_everyone_watching_it` (de stream
  overleeft de pauze — anders hoort niemand ooit de hervatting, voor
  uitgelogd, host én deelnemer) en `resuming_changes_the_live_fingerprint_back`
  (hervatten is een tweede wijziging, en de fingerprint keert terug naar de
  waarde van vóór de pauze). Eén browser is bovendien op 2026-09-07 lokaal
  gedraaid: pauze en hervatting kwamen allebei binnen de 3s-poll aan zonder
  reload, met de reden in de banner en de badge die van Running naar Paused
  en terug ging. Wat daar níet mee bewezen is, is het hele punt: twee
  gelijktijdige streams.

  *Draaiboek.* Twee gescheiden cookiejars — bij voorkeur twee mensen op twee
  machines, anders Chrome (host) naast Firefox (speler); incognito naast
  gewoon werkt ook maar bewijst minder over een tweede machine.
  1. Host: maak op staging een `SNAKES_LADDERS`-event, `access_mode` OPEN,
     lopend (startdatum in het verleden, einddatum in de toekomst).
  2. Speler: log in met een tweede account — een e-mailregistratie is genoeg,
     een tweede Discord-account hoeft niet — en klik **Join**. Dat de speler
     deelnemer is doet ertoe: de mail en de push hangen aan
     `EventParticipant`, niet aan wie er kijkt.
  3. Laat beide tabbladen **minstens 90 seconden** open vóór je iets doet. De
     server sluit elke stream na ~45s en de browser verbindt zelf opnieuw; een
     reconnect die stil niet opnieuw abonneert is precies wat je hier zoekt en
     wat je mist als je meteen op de knop drukt. Kijk in beide browsers naar
     de indicator in de kop: die hoort **Live** te zeggen met een kloppende
     stip, niet "Reconnecting…".
  4. Host: **Pause**, met een reden ingetypt (bijv. "Terug om 20:00"). De
     reden is het deel dat bewijst dat de fingerprint méér dan `paused_at`
     draagt.
  5. Kijk nu naar het scherm van de speler en raak het niet aan. Binnen ~3
     seconden, zonder reload: de banner verschijnt mét de reden, de badge gaat
     van Running naar Paused, de dobbelsteen en het aanvinken van tegels gaan
     dood, en de indicator blijft **Live** — hij mag geen "Reconnecting…"
     worden. In de console van de speler mag `Event stream went stale` niet
     verschijnen.
  6. Host: kijk naar de eigen flash. Die noemt hoeveel deelnemers gemaild
     zijn, en of het naar Discord ging. De host is zelf ook kijker: de pagina
     van de host hoort dezelfde banner te krijgen.
  7. Host: pas de reden aan terwijl het event gepauzeerd is. De speler hoort
     de nieuwe tekst te zien, weer zonder reload.
  8. Derde tabblad (mag dezelfde browser zijn): open het event nieuw tijdens
     de pauze. De banner hoort er direct te staan in de eerste render — niet
     pas na de eerste poll. Bekijk `view-source` als je twijfelt.
  9. Host: **Resume**. Bij de speler: banner weg, reden weg, badge terug op
     Running, dobbelsteen weer levend. Dit is de belangrijkste stap — de
     speler heeft nergens op gedrukt en heeft geen flash; de hervatting
     bereikt die persoon via de fingerprint of helemaal niet.
  10. Laat daarna beide tabbladen nog vijf minuten staan en kijk of de
      indicator Live blijft. Twee gelijktijdige streams die na een paar
      turnovers een worker-tekort oplopen zien er precies zo uit als een
      gezonde pagina, tot de indicator omslaat.

- [ ] **De Discord-webhook tegen een echte server proberen voordat hij aan
  gaat.** Gebouwd en bewust uitgeschakeld uitgeleverd (admin → Site settings →
  Discord announcements). Het is de enige feature die iets naar buiten stuurt
  op gezag van een host, een kamer vol mensen in die deze app nooit iets
  gevraagd hebben — die wil een mens die naar de eerste post kijkt, geen
  voorbijkomende test.

  *Stand van de vijf punten.* Punt 1 — leest het bericht goed in een echt
  kanaal — is afgevinkt door de changelog-post van 2026-08-30, die er goed
  uitkwam. De andere vier staan open, en van elk ligt inmiddels de helft vast
  die een test kan vastleggen (2026-09-07, `EventPauseTest`):

  | Punt | Test | Wat de test níet dekt |
  |---|---|---|
  | pingt niets | `an_event_titled_at_everyone_reaches_the_channel_without_pinging_it` — de titel komt letterlijk mee én `allowed_mentions` is leeg | of Discord het ook echt als niet-mention behandelt |
  | resolvet de link | `the_post_carries_an_absolute_link_to_the_event` | of de host achter die URL antwoordt, en of Discord hem unfurlt |
  | faalt stil | `a_revoked_webhook_breaks_neither_the_pause_nor_the_resume` (401 én 404), `a_webhook_that_never_answers_does_not_break_the_pause` (de `catch (Throwable)`, waar niets anders in de suite langskwam) | of Discord bij een verwijderde webhook werkelijk 401/404 geeft, en of 5s timeout genoeg is |
  | rate limit | `a_rate_limited_post_is_dropped_and_not_reported_as_posted` (429 wordt gedropt, en de bevestiging beweert níet dat het gepost is), `nothing_batches_or_spaces_a_burst_of_posts` (6 aankondigingen = 6 requests, geen bundeling, geen spreiding, geen throttle) | waar het plafond in het echt ligt |

  *Draaiboek.* **Gebruik een wegwerpserver of een privékanaal, niet het
  clankanaal.** Stap 3 hieronder is een opzettelijke poging om een hele server
  te pingen; die wil je niet per ongeluk zien slagen bij tweehonderd mensen.
  Vooraf: Serverinstellingen → Integraties → Webhooks → nieuwe webhook, URL
  kopiëren. Op staging: admin → Site settings → Discord announcements **aan**
  (en na afloop weer uit). Op het testevent: instellingen → webhook plakken.
  1. **Leest het goed.** Pauzeer met een reden en kijk naar de regel in het
     kanaal. Dit is een andere string dan de changelog-post: emoji, een vette
     titel en de reden erachter geplakt. Bekijk hem ook op Discord mobiel —
     `**vet**` en ⏸️/▶️/🏁 vallen daar anders uit. Doe daarna hervatten en
     annuleren, want dat zijn drie verschillende zinnen.
  2. **Resolvet de link.** De post hoort te unfurlen tot een embed met titel
     en omschrijving van het event, en klikken hoort op het event uit te
     komen. Controleer eerst de host in de geposte URL — staat daar niet
     `staging.osrs-events.com`, dan klopt `APP_URL` op staging niet en is de
     rest van deze stap zinloos. Let op: staat er basic auth of een
     wachtwoordmuur voor staging, dan krijgt de unfurler een 401 en zie je een
     kale link; dan verhuist deze stap naar productie of moet de crawler erin
     mogen.
  3. **Pingt niets.** Hernoem het testevent naar `@everyone bingo` en
     pauzeer. In het kanaal: de tekst `@everyone bingo` staat er letterlijk,
     en er is géén ping — geen highlight, geen badge, geen "1 mention".
     **Kijk mee vanuit de client van de tweede persoon**, niet die van de
     eigenaar van de webhook: een mention is alleen als mention zichtbaar bij
     iemand die hem zou krijgen. Wil je het rond hebben, herhaal dan met
     `@here` en met een echte rol-mention (`<@&rolid>`) in de titel.
  4. **Faalt stil.** Pauzeer het event, verwijder daarna de webhook in Discord
     — laat het veld in de app staan — en druk op **Resume**. Verwacht: de
     hervatting slaagt, de bevestiging noemt Discord *niet*, en er staat een
     gerapporteerde exception in `laravel.log` op staging. Draai het daarna
     nog eens met de netwerkkant: zet er een webhook-URL in van een host die
     niet antwoordt en kijk of het na 5 seconden doorloopt in plaats van de
     pauze op te houden.
  5. **Rate limit.** Discord hanteert voor een webhook informeel zo'n 5
     requests per 2 seconden; het punt van deze stap is het echte getal
     vinden, niet dat cijfer bevestigen. De app post één keer per finisher,
     zonder bundelen en zonder spreiden — anders dan de push-helft, die via
     `NotificationCategory` wél een throttle per entiteit heeft. Zet dus een
     CONTINUE-event op met zes of meer deelnemers op het laatste vakje, keur
     hun finish-claims vlak achter elkaar goed, en tel de regels in het
     kanaal. Verwachting die je probeert te falsifiëren: bij zes binnen twee
     seconden verdwijnt er minstens één, en niets zegt dat tegen wie dan ook.
     Valt dat zo uit, dan is de vraag die hier opengaat of `DiscordAnnouncer`
     een queue nodig heeft — noteer het antwoord hier, en bouw het niet
     tijdens de test.

- [ ] **De `startsWith`-TypeError in de console op elke pagina — hier niet te
  reproduceren, dus hij moet gevangen worden waar hij optreedt.** Gemeld
  2026-09-07: `TypeError: Cannot read properties of undefined (reading
  'startsWith')`, meerdere keren per paginalading, op `/`,
  `/osrs-snakes-and-ladders` en `/beta`, in een productiebuild met de
  SSR-daemon actief.

  *Wat uitgezocht is (2026-09-07):* het frame wijst naar
  `build/assets/inertia-C-vFvfZ4.js`. Die hash is ná een verse `pnpm build`
  ongewijzigd, dus dit is exact het bestand dat gemeld werd. Het is een
  vendorchunk (Vue 3.5.41, axios 1.19.0, `@inertiajs/core` 2.3.27,
  `@inertiajs/vue3` 2.3.27) — géén app-code, en **niet** de ULink-bug die
  bovenaan `Components/AppHeader.vue` beschreven staat: `page.url.startsWith(...)`
  van `@nuxt/ui`'s Inertia-`Link` zit in `Button-*.js`, een andere chunk.

  De chunk heeft precies veertien `.startsWith(`-aanroepen. Via de sourcemap
  teruggelegd op hun bron blijven er twee over die op *data* werken in plaats
  van op een prop-, attribuut- of eventnaam (die zijn per constructie een
  string), en die niet al een `typeof`-guard voor zich hebben staan zoals de
  drie in axios:

  - `@inertiajs/core` `buildDOMElement()` — `e.startsWith('<script ')` over de
    lijst head-elementen.
  - `@inertiajs/vue3` `addTitleElement()` — `e.find(e => e.startsWith('<title'))`.

  De drie in Inertia's `mergeDataIntoQueryString` doen eerst `t.toString()` en
  zouden dus een ándere melding geven ("reading 'toString'"), dus die vallen af.

  *Wat het waarschijnlijk kost:* weinig. `buildDOMElement()` draait binnen de
  gedebouncete `update()` van de head manager, in een `setTimeout` — een
  exception daar is onafgevangen (vandaar de console) maar breekt geen render;
  hij laat één flush van de `<head>`-tags vallen, en de volgende zet ze alsnog.
  Dat past op "meerdere keren per lading, niets zichtbaar stuk". `addTitleElement()`
  draait wél in de render van `<Head>`, en zou de meta van die pagina hélemaal
  wegnemen — dus als het die is, is het geen cosmetisch probleem maar een
  SEO-probleem. Welke van de twee het is, is niet vastgesteld.

  *Waarom het hier bleef staan:* niet reproduceerbaar in de browserpane, op de
  build van 11:02 met SSR-daemon actief, uitgelogd én ingelogd, op alle drie de
  gemelde pagina's, op 1440×900 en op mobiel, met de errorcapture bewezen werkend
  (een expres gegooide `startsWith` op undefined kwam wél binnen). Wat hier niet
  na te bootsen was: een sessie mét de ADMIN-rol, en een geregistreerde service
  worker (registratie faalt in de sandbox).

  *Wat er klaarstaat:* `vite.config.js` heeft nu `build.sourcemap` achter
  `VITE_SOURCEMAP=1`, en de build die er nu staat is ermee gemaakt. Reproduceer
  hem opnieuw en de console noemt bestand + regel in de originele bron in plaats
  van een kolom in een geminificeerde chunk. Plak die regel hier, dan is het
  binnen een minuut een fix in plaats van een analyse. Bouw daarna zonder de
  env-var terug, zodat er geen sourcemaps naast een deploy komen te liggen.

## 6. Klein werk

- [ ] **`text-*` op een kop doet niets — gemeten, niet vermoed.** Tailwind v4's
  preflight zet `h1`–`h3` op `font-size: inherit`, en `app.css` herstelt ze met
  gewone element-regels. Die regels staan **buiten elke cascade layer**, terwijl
  utility classes erin staan — en unlayered CSS wint altijd van layered CSS,
  hoe specifiek de utility ook oogt. Elke `text-*` op een kop is dus stil dood.
  Nagemeten 2026-09-07 op `localhost:8010/osrs-bingo`:
  | element | vraagt | krijgt |
  |---|---|---|
  | `GuideLayout` h1 | `text-3xl sm:text-4xl` | 30px, ook boven `sm` |
  | `GUIDE_PROSE.h3` | `text-lg` (18px) | 20px |
  | `GUIDE_PROSE.h2` | `text-2xl` | 24px — klopt, maar bij toeval |
  Dus: elke guide-titel is één stap kleiner dan bedoeld op elke breedte, en
  h3 één stap groter. Niemand heeft het gezien omdat h2 toevallig goed valt.
  **Blast radius geteld 2026-09-07** (statisch over `resources/js`): 37 koppen
  dragen een `text-*`; bij **27** daarvan doet die niets, verspreid over 16
  bestanden. Alle 27 renderen op dit moment **groter** dan gevraagd — de zes
  auth-pagina's vragen `text-2xl` op hun h1 en krijgen 30px, `AdminLayout`
  vraagt `text-xl` en krijgt 30px, `Boards/Index` en `Community/Index` vragen
  `text-xl` op h2 en krijgen 24px, `OnboardingModal` zes keer `text-lg` op h3
  en krijgt 20px. De overige 10 vragen toevallig precies de element-default.
  Twee manieren, en de keuze is de helft van het werk: de element-regels in
  `app.css` in `@layer base` zetten — één plek, en alle 27 krimpen in één klap
  naar wat er staat — of per geval de maat op een element zetten dat geen kop
  is, zoals `About.vue` nu doet. De eerste is de goede; hij verandert wel het
  uiterlijk van 16 bestanden tegelijk, dus die wil je naast elkaar zien.

- [x] **De dode `privacy.*`-sleutels uit `lang/en.json`.** — opgeruimd
  2026-09-07. Achtendertig sleutels, de hele namespace, overgebleven van de
  privacypagina van vóór de CMS-overstap; sinds `/privacy` uit
  `App\Support\LegalPages` komt verwees niets in `resources/js`, `app`,
  `database` of `routes` er nog naar. Ze waren niet alleen ongebruikt maar ook
  onwaar: `privacy.rights_body` zei nog "contact us to delete your account"
  (er is een knop), en `privacy.storage_body` beschreef een JWT in
  localStorage met zeven dagen geldigheid — de oude NestJS/Nuxt-stack, niet
  deze. `terms.*` en `about.*` zijn langs dezelfde lat gelegd en die worden
  allebei wél gebruikt. 99 backend- en 212 frontendtests groen.
- [x] ~~**Een event kon gewonnen worden zonder dat er iets gebeurde.**~~ —
  gebouwd 2026-09-03. De laatste tegel afvinken zette een ref in de browser en
  toonde een 🎉-modal die een refresh wiste. Er werd niets opgeslagen: niemand
  anders hoorde het, de winnaar hield een dobbelsteen die alleen nog zijn
  dagelijkse worp opsoupeerde (elke worp clampte naar waar hij al stond), en
  tweede plaats was niet alleen onzichtbaar maar **onkenbaar** — er stond geen
  tijdstempel om op te sorteren. Bingo had hetzelfde verhaal met een `hasWon`
  die per request opnieuw werd uitgerekend.
  Nu: een `event_finishes`-tabel (één rij per competitor, rank afgeleid uit de
  volgorde, niet opgeslagen), `EventFinishService` als enige plek die beslist
  wát afmaken betekent — voor S&L de laatste tegel *goedgekeurd*, voor bingo de
  wincondition van de kaart — en beide richtingen op: een afgekeurde claim of
  een slang haalt een finish weer weg.
  **De finish krijgt de tijd van indienen, nooit die van goedkeuren.** Anders
  wint op een bord met review wie de host toevallig het eerst aanklikt, en zou
  dezelfde avond een andere uitslag opleveren als hij de wachtrij in
  omgekeerde volgorde had afgewerkt. Rank volgt daaruit, dus die corrigeert
  zichzelf terwijl de wachtrij leeggewerkt wordt. Voor STOP is dat niet
  genoeg — een push kun je niet terugnemen — dus het **sluiten wacht** tot er
  niemand meer in de wachtrij zit die de koploper nog kan verslaan; tot dan is
  het event open, wat de eerlijke staat is. In de reviewmodals staat er nu
  boven zo'n claim wat hij is ("deze claim maakt het bord af", "deze claim
  wint de kaart") plus, als er meer kandidaten wachten, de hoeveelste hij is —
  met erbij dat de volgorde van goedkeuren niets uitmaakt.
  **Onderweg gevonden**: `completed_tiles.completed_at` kwam van de
  `useCurrent()`-default, dus van de klok van de *database* in de tijdzone van
  de database. Zolang het een weergavedatum was viel dat niet op; nu het
  bepaalt wie wint, wordt hij expliciet uit `now()` gezet.
  **Daarna gemeld, en de kern van dezelfde zaak**: sorteren op indientijd
  repareert het podium, maar niet wat iedereen te horen krijgt terwijl de
  wachtrij nog loopt. De host keurde de tweede inzending eerst goed en elke
  speler kreeg te zien dat dát team eerst thuis was — terwijl de claim die
  echt won nog ongeopend in de wachtrij stond. Een finish waarvan de plaats
  nog kan zakken is nu **voorlopig**: `announced_at` blijft leeg, de banner
  zwijgt, de felicitatiemodal wacht, en de kaart zegt "je run staat" zonder
  een plaats te noemen. Zodra niemand de koploper meer kan verslaan gaat
  alles alsnog uit, in de goede volgorde — een afwijzing zet dat net zo goed
  vast als een goedkeuring.
  Twee dingen die daarbij bovenkwamen: de eerste plaats kreeg nergens een
  echte **"je hebt gewonnen"** (team- en solovariant, en alleen als de plaats
  vaststaat), en `myFinish` stond niet in de reload van de live stream — dus
  bij een speler wiens winnende claim net was goedgekeurd werkte de banner
  wel bij en bleef de dobbelsteen staan. Ook `playerBoard.team_id` werd nooit
  meegestuurd, waardoor op een teamevent nooit te zien was welke rij in het
  zijbalk-klassement van jou was; die vergelijking liep op `undefined`.
  **Eén instelling erbij, twee waarden**, op het Format-tabblad en gedeeld door
  beide types: `finish_rule` = CONTINUE (standaard; het bord blijft open tot de
  einddatum en het podium vult zich op finishvolgorde) of STOP (de eerste
  finish sluit het event voor iedereen). STOP stempelt `closed_at`, en omdat
  `Event::isEnded()` die kolom meeneemt, sluiten rollen, claimen, afvinken en
  meedoen allemaal mee zonder één nieuwe check — `eventStatus()` in `board.js`
  spiegelt het aan de clientkant.
  Het Danger-tabblad heet nu **Status**: bovenaan waar het event staat
  (upcoming / live / gepauzeerd sinds / geëindigd / gesloten door een winnaar),
  daaronder dezelfde ladder plus de trede die ontbrak — pauzeren, **nu
  beëindigen** (met een bevestiging die het podium noemt), verwijderen. Het
  Manage-menu heeft er een eigen ingang voor, want dat is waar een host het
  vaakst voor binnenkomt. `POST /events/{event}/close` is de handmatige tweeling
  van wat de STOP-regel zelf doet: dezelfde kolom, dezelfde audit-actie,
  dezelfde aankondiging.
  **Twee echte bugs onderweg**, allebei door de nieuwe tests gevonden: de
  leaderboard gaf een fatale `null['at']` voor iedereen die nog niet klaar was
  (dus vrijwel altijd), en de finish-kaart las "1 place" omdat de rank als kaal
  getal in een `:place place`-string ging — er is nu een `ordinal()` in
  `board.js` naast `App\Support\Ordinal`, aan beide kanten getest.
  **En de melding die dit begon**: op een geëindigd event stond nog een kaart
  met de kop "Roll the dice" — met de dev-force-roll-knoppen eronder, want die
  zaten alleen achter de omgeving. Die kaart heet nu naar de staat waarin het
  event staat en biedt niets aan.
  29 feature-tests in `EventFinishTest`, plus `ordinal`/`closed_at` in
  `tests/js/support.test.js`.
  **Nagelopen 2026-09-07 met een e2e-walkthrough** (anoniem, twee racers, de
  host), op een gezaaid scenario dat je in een browser niet kunt naspelen:
  twee finishclaims in de wachtrij, de winnaar als eerste ingediend, en de
  host keurt de latere eerst goed. `dev:fixtures` zaait dat nu als "Photo
  finish", met drie accounts die kunnen inloggen — een claim kan alleen *nu*
  ingediend worden, dus het gat tussen twee inzendingen bestaat alleen als
  iets het zet.
  De kern hield stand: niets aangekondigd, event niet gesloten, plaats
  voorlopig, en na de tweede goedkeuring de goede volgorde. Drie dingen die
  eromheen zaten niet:
  - **Het klassement gaf de voorlopige leider tóch goud** — de finishkaart
    zweeg netjes, maar de zijbalk en `/leaderboard` lazen de plaats
    rechtstreeks van de finish. Dat is dezelfde bug als hierboven, één
    scherm verderop, en zichtbaar voor een toeschouwer zonder account.
    `settledPlace()` in `board.js` is nu de enige plek die die vraag
    beantwoordt, en `LeaderboardController` stuurt `finishProvisional` mee.
  - **Nummer twee kreeg te lezen dat hij de winnende run deed.**
    `board.finished_closed` ging naar elke plaats op een gesloten event;
    alleen de eerste hoort dat te lezen (`finishSubtitle()`).
  - **Een afgewezen finishclaim werd onzichtbaar zodra het event dicht was.**
    Eén `v-if` verborg de knop én het oordeel, dus wie het event verloor door
    een afwijzing mét toelichting zag helemaal niets. Handelen vraagt een
    lopend event, een uitspraak teruglezen niet — `claimAreaIsShown()`. De
    knoppen in de claimmodal zijn daarbij wél gesloten, anders bood die na
    de fix een "Try again" aan die de server toch weigert.
  2 feature-tests erbij en 3 in `tests/js/support.test.js`.

- [x] ~~**Het volledige menu flitste in beeld nadat het slot aanging.**~~ —
  gefixt 2026-08-31, gemeld vanaf staging. De conditie zat al zo vroeg
  mogelijk (server-side in `EnsureSiteUnlocked`); het probleem was de andere
  kant. Iemand had de site ingeladen vóórdat het slot aanging, dus zijn tab
  hield props van vóór het slot vast — volledige nav, user menu, alles.
  Inertia laat de huidige pagina bewust staan zolang een visit loopt, dus zijn
  volgende klik toonde de hele ingelogde site voor de duur van het verzoek en
  daarna pas het lock screen. **Het menu werd niet opgehaald; het stond er
  al.**
  De middleware geeft nu `Inertia::location()` in plaats van
  `redirect()->route()`: dat antwoordt een Inertia-request met 409 +
  `X-Inertia-Location`, wat de client omzet in een echte browsernavigatie —
  de verlopen pagina wordt afgebroken in plaats van eromheen hertekend. Een
  gewoon verzoek krijgt nog steeds een 302, dus verder verandert er niets.
  Zelfde mechanisme dat `DiscordController::redirect()` al gebruikt, om een
  andere reden.
  **Onderweg geleerd, en het staat in de test**: Inertia forceert die harde
  navigatie zelf al bij een asset-versiemismatch, dus ná een deploy was dit
  nooit zichtbaar. Het gat dat dit dicht is het slot omzetten *zonder* deploy —
  precies hoe het gemeld werd. De eerste versie van de test slaagde om die
  verkeerde reden (geen versieheader → Inertia's eigen 409), en stuurt nu de
  kloppende versie mee.
- [x] ~~**Een publiek event was niet te openen zonder account.**~~ — gefixt
  2026-08-31, gemeld door de eigenaar. `/events` is publiek en toont
  aanklikbare kaarten aan iedereen, maar `/events/{event}` zat achter
  `['auth', 'require-osrs-username']` — elke klik van een uitgelogde bezoeker
  landde dus op een login-redirect. Een lijst die je wel mag doorbladeren en
  niet mag openen is erger dan geen lijst.
  **De oorzaak was conceptueel**: "toegang" en "mogen lezen" waren één begrip.
  `access_mode` bepaalt wie mag *meedoen* en werd gebruikt om te bepalen wie
  mag *kijken*. Nu drie vragen in `BoardAccessService`:
  `canView()` (is het event listed — dan mag iedereen de pagina openen),
  `canSeeParticipants()` (mag je zien wíé er speelt) en het bestaande
  `hasAccess()` (mag je meedoen).
  **De regel voor namen is die van de eigenaar**: een listed event laat zijn
  voortgang altijd zien — een bord zonder stukken erop is niet het event waar
  iemand voor kwam — maar de spelers zijn anoniem tenzij het event OPEN is of
  je er zelf in zit. Iemand die zijn invite-only event op de lijst zet,
  adverteert dát het bestaat; hij publiceert niet de ledenlijst van zijn clan.
  Server-side geanonimiseerd, niet client-side verborgen: `user_id` en
  `team_id` gaan óók weg, want een id is een identiteit voor wie hem kan
  opzoeken.
  **De SSE-stream volgt de namen, niet de pagina.** Eén kanaal wordt door alle
  kijkers gedeeld en kan per definitie geen per-kijker-state dragen, dus het
  kan zichzelf niet voor de een wél en de ander niet anonimiseren. Op een
  listed invite-only event krijgt een vreemde daarom een pagina die zichzelf
  niet ververst; op een OPEN event is de stream gewoon publiek.
  Ook weg bij het lezen: `require-osrs-username`. Die poort bestaat om te
  voorkomen dat iemand *speelt* zonder naam om op te scoren, en lezen is niet
  spelen — een uitgelogde vreemde mocht de pagina al zien, dus hem eisen van
  een ingelogde lezer sloeg nergens op.
  Zes bestaande tests pinden de oude regel en zijn herschreven; vier nieuwe
  leggen de nieuwe vast, waaronder beide kanten van de anonimiteit. Eén
  daarvan was in eerste opzet vals-groen — "elke rij is anoniem" is vanzelf
  waar bij een lege tabel — en heeft nu een echte goedgekeurde claim.
  Live gecontroleerd als gast: een listed OPEN event geeft 200 met namen, en
  hetzelfde event op INVITE geeft 200 met `current_position` en
  `tilesRemaining` intact terwijl `user`, `team`, `user_id` en `team_id`
  allemaal `null` zijn. 763 backend / 174 frontend groen.
  **Het gevolg daarvan is meteen opgelost** (gevraagd 2026-08-31): de
  AccessGate verschijnt nu alleen nog bij een *unlisted* privé-event, en die
  hield het codeveld vast — dus bij een listed invite-only event had iemand
  met een kale code nergens meer om hem in te typen. Nieuwe
  `Components/InviteCodeCard.vue` staat direct onder de kop op alle drie de
  eventpagina's, aangestuurd door `BoardAccessService::needsInvite()`.
  Uitgelogd toont hij "sign in to use a code" en géén invoerveld — het
  join-endpoint zit achter `auth`, dus een code intypen zou je naar een login
  sturen en de code onderweg verliezen. Ingelogd zonder toegang krijg je het
  veld, dat naar hetzelfde endpoint post als de gate: één join-pad, niet twee.
  Drie tests dekken de drie standen (vreemde, deelnemer, OPEN-event).
  **Twee wees-sleutels opgeruimd**: `board.invite_only` en
  `board.invite_only_desc` bestonden al zonder gebruiker, en die tweede
  overschreef stilletjes de nieuwe copy — bij dubbele JSON-sleutels wint de
  laatste. Gevonden doordat de kaart de verkeerde tekst toonde.
  767 backend / 174 frontend groen.

- [x] ~~**Nuxt UI's date range picker voor het eventvenster.**~~ — gedaan
  2026-08-30. Nieuwe `Components/EventDateRange.vue`: één `u-popover` met een
  `u-calendar` in range-modus, in plaats van twee `<input type="date">`-velden
  die om twee beslissingen vroegen waar een host er één neemt. De oude
  volgorde-bewaking (`:min` op het tweede veld) is niet vervangen maar
  overbodig geworden — een range-kalender kan "einde vóór start" niet
  uitdrukken.
  **De styling die het item vroeg**: in range-modus draagt élke dag
  `data-selected`, en de solid-variant schildert dat als `bg-primary` — vandaar
  veertien gevulde swatches voor één periode. reka-ui markeert de uiteinden
  apart (`data-selection-start`/`-end`), dus die blijven solide en alles
  ertussen zakt naar een tint. Live gecontroleerd over een maandgrens heen:
  30 aug en 29 sept solide, alles daartussen subtiel.
  Onder de kalender staat de lengte in woorden ("31 days", via `transChoice`
  zodat "1 days" niet kan) — de duur is waar een host werkelijk over beslist.
  De picker sluit pas als beide uiteinden gekozen zijn, want de eerste klik
  wist het einde en tussentijds sluiten zou het gebaar afbreken.
  **Nieuwe dependency**: `@internationalized/date`, de peer-dependency die elke
  Nuxt UI datumcomponent nodig heeft en die nog niet geïnstalleerd was.
  **SSR-let-op**: `u-popover` en `u-calendar` raken allebei de `#imports`-barrel
  uit gotcha 6. Dit component is alleen veilig omdat het uitsluitend via
  `BoardSettingsModal` geladen wordt, dat overal achter
  `defineAsyncComponent` + `<client-only>` hangt. Staat als waarschuwing
  bovenaan het bestand.
  **Vier wees-sleutels opgeruimd**: `admin.start_date(_desc)` en
  `admin.end_date(_desc)` waren na deze wijziging nergens meer in gebruik, en
  `admin.date_range(_desc)` bleek al langer wees — met een beschrijving die
  ook nog feitelijk verouderd was ("the board remains playable outside these
  dates", terwijl `eeacce6` juist gating op afgelopen events bracht). Weg,
  zelfde afweging als bij de wees-`terms.*`-sleutels eerder.
  755 backend / 174 frontend groen.
- [x] ~~**Boss-icons via pets — de icons zelf.**~~ — gedaan 2026-08-31, en de
  aanname klopte: de pets zaten al in dezelfde package als de skill-icons.
  `@dava96/osrs-icons` heeft `petSnakeling`, `hellpuppy`, `vorki`, `olmlet`,
  `nexling` en de rest gewoon liggen — er was geen wiki-scrape nodig, alleen
  een mapping. `scripts/extract-osrs-icons.mjs` heeft er een `BOSS_PETS`-map
  bij en schrijft nu twee mappen in plaats van één.
  **61 van de 71 bossen hebben een icon.** De eerste ronde bleef op 56 steken
  omdat ik van de zeven nieuwste bossen aannam dat ze geen pet hadden; de
  eigenaar wees erop dat die wel degelijk op de wiki staan, en dat klopte.
  Nagekeken tegen de pet-lijst van de OSRS-wiki in plaats van uit het hoofd:
  Beef (Brutus), Dom (Doom of Mokhaiotl), Maggot marquess (Maggot King),
  Gull (Shellbane Gryphon) en Yami (Yama) zaten gewoon in de package onder
  díé namen. **De sprites zijn met het oog gecontroleerd** — `beef` staat er
  naast `beefFillet` en dat had makkelijk een lapje vlees kunnen zijn.
  De tien die overblijven zijn twee soorten antwoord. Acht droppen geen pet,
  nu geverifieerd tegen dezelfde wiki-lijst: Barrows, Bryophyta, Hespori, de
  Mimic, Obor, Lunar Chests en beide archaeologists. Twee **hebben** er wel
  een — Aggy (Mad Angel) en Bran (The Royal Titans) — maar die heeft de
  package nog niet uitgebracht. Allebei renderen als geen icon, want een
  verkeerd icon is erger dan geen.
  Een `null` in die map is dus een uitspraak, geen ontbrekende regel — het
  script slaat hem stil over en meldt alleen een exportnaam die níét oplost,
  want dat is een upstream-hernoeming en die moet luid zijn.
  Frontend: `metricIconUrl()` gaf bij `kind === 'boss'` altijd `null` terug en
  kent nu het bossenpad. Het script genereert `Support/bossIcons.js` met de
  56 die een bestand hebben — nodig omdat vragen om een PNG die nooit
  geschreven is een 404 en een gebroken plaatje in de pagina oplevert.
  Zelfde afspraak die `Support/iconCatalog.js` al met `vite.config.js` heeft:
  de build maakt het antwoord waar.
  Live gecontroleerd op een drop race: het pet-snakeling-sprite staat naast
  "Ranked by Zulrah kills" en in elke standings-rij.
- [x] ~~**Boss-icons: de CRUD.**~~ — gebouwd 2026-09-02. `/admin/boss-icons`
  toont alle 71 bossen met het icoon dat ze zouden tonen en waar dat vandaan
  komt, met een zoekveld en een "alleen zonder icoon"-filter — want die
  laatste groep is de enige reden om die pagina te openen.
  **De bron is de wiki-picker die er al lag** (`WikiIconPicker`), niet een
  tweede manier om een plaatje te kiezen: een boss-pet ís een wiki-afbeelding,
  en een admin een URL laten opzoeken en plakken zou werk zijn dat de app al
  kan doen. Plakken kan alsnog, in hetzelfde formulier.
  Resolutievolgorde zit in `BossIconService` en nergens anders: override uit
  `boss_icons` wint, anders de gecommitte pet-sprite, anders geen icoon.
  Dat laatste is een antwoord — acht bossen droppen geen pet. "Use default"
  gooit de override weg en laat de sprite terugkomen, wat voor 61 van de 71
  een echte pet is.
  De bestandscheck is een `file_exists` en geen tweede manifest: **de
  bestanden zíjn het manifest**, en een lijst die de map beschrijft is een
  lijst die ermee uit de pas kan lopen.
  Client-side wint een override ook, via `Composables/useMetricIcon.js` —
  alleen de overrides gaan als prop mee (normaal nul of twee), want welke
  sprites er zijn weet de browser al uit `Support/bossIcons.js`.
  **Aggy en Bran staan erin**, via de wiki, wat de aanleiding was: 63 van de
  71 hebben nu een icoon. Elf tests dekken de volgorde en de CRUD, waaronder
  dat een onbekende metric en een niet-http-URL geweigerd worden.
  Eén test slaagde eerst om de verkeerde reden — `actingAs()` blijft gelden
  voor de rest van een testmethode, dus de "uitgelogde" call mat nog steeds de
  ingelogde sessie. Nu een eigen test.
- [x] ~~**Boss-icons: de watcher.**~~ — gebouwd 2026-09-02.
  `php artisan boss-icons:suggest`, wekelijks ingepland (maandag 04:00, met
  `ScheduleHeartbeat` zoals de andere twee jobs). Zoekt voor elke boss zonder
  icoon een wiki-afbeelding en zet die **als voorstel** neer, bovenaan de
  CRUD onder "Waiting on you".
  **Voorstellen, nooit toepassen** — dat was al de regel in dit item vóórdat
  er iets van gebouwd was, en hij houdt: een verkeerd icoon op een live
  eventpagina is erger dan een leeg vakje. Een `suggested_url` wordt pas een
  `icon_url` als iemand op "Use this" klikt.
  Het zoekt op de náám van de boss, niet van de pet — die pet-naam is precies
  wat niemand weet bij een boss van vorige week, en dat is de reden dat het
  vakje leeg staat. Het voorstel is dus soms de boss zelf in plaats van zijn
  pet, en dat is prima: wie goedkeurt ziet het plaatje, en afwijzen kost één
  klik. Voor de acht bossen zonder pet is een plaatje van de boss zelf
  waarschijnlijk juist het goede antwoord.
  **Een afwijzing wordt onthouden** (`dismissed_url`), anders staat hetzelfde
  plaatje volgende maandag weer in de rij en wordt de wachtrij ruis die
  niemand meer leest. Een *ander* plaatje voor dezelfde boss mag wel opnieuw.
  Live doorlopen: acht voorstellen opgehaald, Barrows goedgekeurd (icoon
  staat), Bryophyta afgewezen, en een tweede run stelde niets voor met
  "same image was already turned down".
  **De cron pikt ook op wanneer het pakket bijtrekt** (gevraagd 2026-09-02),
  zonder het pakket te lezen — dat kan niet: hij draait op de server, waar
  `@dava96/osrs-icons` niet geïnstalleerd staat en zijn output gecommitte
  bestanden zijn. Hij kijkt naar het bestánd. Verschijnt er een sprite voor
  een boss die nu een wiki-override draagt, dan stelt hij voor die boss
  terug te geven aan het pakket. Goedkeuren **verwijdert** dan de override
  in plaats van het sprite-pad als handmatige waarde op te slaan — anders
  toont de boss het goede plaatje om de verkeerde reden en volgt hij het
  pakket daarna niet meer.
  De andere kant van dat loopje zit in `scripts/extract-osrs-icons.mjs`:
  `AWAITED_PETS` houdt de namen bij die het pakket nog mist (Aggy, Bran) en
  het script meldt bij elke run of ze er inmiddels in zitten. Dat is het
  enige moment waarop iemand dat kan weten, want het draait waar het
  pakket wél staat.
  Negen tests dekken de standen, waaronder dat een boss die het pakket al
  volgt nooit iets gevraagd wordt.
- [x] ~~**Wise Old Man API-key per host.**~~ — gebouwd 2026-08-30 en
  **teruggedraaid 2026-08-31 op verzoek van Wise Old Man zelf.** De eigenaar
  legde het voor; hun antwoord, en het is het juiste antwoord:
  1. meerdere keys leveren niets op als de backend alle requests doet — één
     key per site is beter, want dan kunnen zij zien hoe deze site het qua
     requests doet;
  2. per-gebruiker keys betekent dat wij andermans API-keys gaan verzamelen,
     wat zij liever niet hebben, of dat een key in de client belandt, wat
     sowieso niet moet.
  Punt 2 was de doorslag: het ontwerp had de key wel versleuteld en
  write-only, maar "goed bewaard" is nog steeds "bewaard", en dat is precies
  wat de eigenaar van die key niet gevraagd heeft.
  Verwijderd: de `users.wom_api_key`-kolom (migratie teruggedraaid en het
  bestand weg — hij was nog niet gecommit, dus geen drop-migratie nodig), de
  cast en `#[Hidden]`-regel, `Event::womApiKey()`,
  `WiseOldManService::usingKey()`/`effectiveKey()`, de pacing per event in
  `SyncEventStandings` en `EventStandingsService`, het veld in Settings →
  Connections met zijn route, en alle `profile.wom_key_*`-sleutels.
  **Blijft staan**: één key voor de hele deployment via `WOM_API_KEY` in
  `.env`, wat al bestond — `config/services.php` leest hem en
  `requestsPerMinute()` gaat vanzelf van 20 naar 100/min zodra hij er is. Er
  is dus niets te bouwen voor de key die de eigenaar binnenkort krijgt; hem in
  `.env` zetten is genoeg.
  **Blijft ook staan, en dat is de winst van deze ronde: de 429-bug.** Die
  stond los van de key-feature. `gained()` gaf `null` terug voor élke
  mislukking, en `refresh()` schreef daar `sync_error = 'not_tracked'` bij —
  waarvan de tekst luidt "Wise Old Man has no data for this account, search
  the name once on wiseoldman.net". Bij een rate limit is dat verkeerd advies
  over een account dat prima gevolgd wordt, en het legt de schuld bij de
  speler terwijl het probleem aan onze kant zit. De service waarschuwde in
  zijn eigen docblock al voor precies deze fout, maar alleen bij `isKnown()`.
  Nu een eigen `WiseOldManRateLimited`-exception, een eigen
  `rate_limited`-melding, en **bewust géén `synced_at`-stempel** — een rij die
  er gesynct uitziet is een rij die de volgende run niet vooraan zet, en het
  ontbreken van dat stempel is precies wat de herkansing regelt. Twee tests
  dekken beide richtingen.
  **Settings → Connections blijft ook** (gemaakt in dezelfde ronde): die
  pagina houdt nu alleen de Discord-koppeling. Dat is één kaart, dus als je
  hem liever terugvouwt in Account is dat een kleine wijziging — maar de
  splitsing stond op eigen benen ("hoe kom je binnen" tegenover "met welke
  diensten praat dit account") en er komt waarschijnlijk meer bij.
  758 backend / 174 frontend groen.
- [x] ~~**Discord-serverkiezer: sorteren op recent gebruikt.**~~ — gedaan
  2026-08-30. `BoardController::myGuilds()` sorteerde op `guild_name`, dus een
  account in dertig servers kreeg een alfabetische lijst waarin de twee servers
  waarin het werkelijk events draait stonden waar het alfabet ze neerzette.
  Nieuwe `guildRecency()` leest drie signalen — teams waar je lid van bent
  (`team_members.created_at`), events die je auteurt (`events.created_at` via
  `board_authors`) en blueprints die je voor een server opsloeg — neemt per
  server de meest recente, en zet die groep vooraan. De onaangeraakte rest
  houdt de alfabetische volgorde die de query al gaf. Drie losse queries in
  plaats van één union: de rijaantallen zijn per gebruiker en klein, en de
  samenvoegregel ("pak de laatste") is uitgeschreven duidelijker dan in SQL.
- [x] ~~**Discord-serverkiezer: servericon per regel.**~~ — gedaan 2026-08-30,
  en de "controleer eerst of het kan"-vraag bleek al beantwoord: `UserGuild`
  heeft sinds de eerste migratie een `guild_icon`-kolom, `DiscordController::
  syncGuilds()` vult hem bij elke login, en `/my-guilds` gaf hem al terug.
  Er was dus geen extra API-call nodig, alleen een renderende kant. Wat de
  kolom opslaat is Discord's **hash**, geen URL; `myGuilds()` bouwt er nu
  `icon_url` van (`a_`-prefix = animated = gif, de rest png), zodat de drie
  componenten die deze lijst tonen dat niet elk apart hoeven te weten.
  `avatar` toegevoegd aan de items in `TeamSettingsModal`, `BlueprintSaveModal`
  en `BoardSettings/AccessFields` — alleen wanneer de server werkelijk een icon
  heeft, want een lege `src` is een gebroken afbeelding en geen afwezige.
- [x] ~~**Teambewerkmodal: toon de gekoppelde Discord-server.**~~ — gedaan
  2026-08-30. Gevonden in de broncode van `@nuxt/ui`'s `Select.vue`: de
  uitgeklapte lijst rendert `item.avatar` (regel 235), maar de **dichtgeklapte
  knop rendert alleen de eigen `props.avatar`** (regel 188). Alleen icons aan
  de items hangen liet een bestaand team dus nog steeds zonder servericon zien
  — wat net het punt van dit item was. `TeamSettingsModal` bindt nu
  `:avatar="selectedGuildAvatar"` op de select zelf.
  **De tweede helft kan niet, en dat is een antwoord en geen uitstel:** deze
  app vraagt bij Discord-login `setScopes(['identify', 'guilds'])`. Met die
  scopes is er geen route naar de *ledenlijst* van een server —
  `/guilds/{id}/members` vereist een bot-token met de privileged
  GUILD_MEMBERS-intent, en `guilds.members.read` levert alleen je eigen
  lidmaatschap op. "Lid toevoegen"-suggesties uit een Discord-server vragen dus
  dat dit project een echte bot wordt die in die server gezet moet worden. Dat
  is een ander product, geen veld erbij — als je het wilt, hoort het als eigen
  item in `docs/ideas.md` en niet als restje van dit.
- [x] ~~**Een echte Snakes & Ladders-verbindings-SVG**~~ — gedaan 2026-09-02.
  **De premisse klopte niet meer:** de connector was al een bezier, geen
  rechte lijn. Wat er werkelijk stond was voor allebei dezelfde gestippelde
  pijl met een andere kleur — een legenda, geen tekening. Nu een ladder van
  twee bomen met sporten ertussen, en een slang met een spits toelopend
  kronkelend lijf en een kop op de tegel waar je op landt. De taper doet het
  werk dat de pijlpunt deed: hij zegt welke kant het op gaat zonder er een
  bij te tekenen.
  **Onderweg gevonden, en dit was het echte defect:** de overlay stond op de
  perkamentrand met zijn `p-3`, niet op het grid. Een viewBox-eenheid was dus
  een fractie van een vak dat 24px breder is dan het bord, en elke connector
  landde tot tien pixels naast zijn tegel — het ergst aan de randen, waar een
  slang eruitzag alsof hij buiten het bord begon. Grid en overlay zitten nu in
  hetzelfde `relative`-vakje.
  Geometrie in `Support/snakesLadders.js`, buiten de component omdat het
  rekenwerk is: `tileCenter` (boustrofedon, zelfde afleiding uit `position`
  als `orderedTiles`, dus ze kunnen niet uit elkaar lopen), `ladderPath`,
  `snakeBody`, `snakeHead`. De golf is een sinus onder een `sin(πt)`-envelop,
  wat de zijwaartse uitwijking aan beide uiteinden op nul dwingt: een slang
  begint en eindigt precies op de twee tegelmiddens, hoeveel golven er
  tussenin ook passen. Twaalf tests in `tests/js/snakesLadders.test.js`.
  **De kleuren zijn nagerekend, niet bekeken.** Het bord is amber perkament in
  light en bijna zwart in dark; green-500 op perkament haalde 1,4:1 en was
  alleen zichtbaar omdat het tegen een vlak veld afsteekt. De contour draagt nu
  het contrast (3,4:1 en hoger in beide thema's, de norm van WCAG 1.4.11 voor
  een graphic) en de vulling blijft doorschijnend, zodat een slang die zes
  tegels doorkruist de taken eronder niet wegpoetst.
  Gecontroleerd in een browser op 7×7 en 9×9, in light en dark, en door de
  SSR-bundel gehaald: de overlay komt met sporten en al uit de server-render.
  187 frontend groen.
- [ ] **Team-icoon: een keuze tussen een OSRS-wiki-icoon en een eigen
  upload.** Gevraagd 2026-08-30, en het lost twee dingen tegelijk op.
  Het begon als "ik kan geen icoon voor het team instellen" — maar nagemeten
  in een browser **werkt die functie gewoon**: `WikiIconPicker` in de
  teambewerkmodal geeft op "dragon scimitar" meteen echte wiki-resultaten mét
  icoontjes. Het probleem was vindbaarheid: het veld heet "Team icon" maar
  toont een zoekbalk met "Search the OSRS Wiki…", wat leest als een filter en
  niet als "hier kies je het plaatje" — de eigenaar van het project vond hem
  niet, dus een nieuwe gebruiker zeker niet.
  **Een expliciete toggle lost allebei op**: twee zichtbare bronnen ("OSRS
  Wiki" / "Eigen afbeelding") maken meteen duidelijk dát dit het icoonveld is
  én wat je ermee kunt. De wiki-kant bestaat al; de uploadkant niet.
  Wat uploaden erbij vraagt, en het is meer dan een veld: opslag
  (`storage/app/public` plus symlink, of een bucket), een groottelimiet,
  alleen echte afbeeldingsformaten, en herschalen zodat niemand een 8MB PNG
  als teamicoon zet. Plus een vraag die deze site nog nooit heeft gehad —
  **een geüploade afbeelding is de eerste vrije gebruikersinvoer die niet uit
  de OSRS-wiki komt**, dus er moet een antwoord zijn op wat er gebeurt als
  iemand iets ongepasts uploadt. Bij een besloten beta is dat klein, bij een
  open site niet.
  Nog te beslissen: geldt dit alleen voor teams, of ook voor de eigen
  gebruikersavatar (`users.avatar_url`, nu de Discord-avatar)? De opslag- en
  moderatiekant is voor allebei dezelfde, de UI niet.
- [x] ~~**Terugval voor een team zonder icoon.**~~ — gedaan 2026-09-02, als de
  voorgestelde mix: eigen `icon_url`, dan het icoon van de gekoppelde
  Discord-server, dan initialen. De open vraag is beantwoord zoals vermoed —
  **het eigen icoon wint**, het servericon is de terugval en niet iets dat er
  naast komt te staan.
  **De helft die er nog niet lag was het servericon zelf.** De serverkiezer
  haalde die hash wel op, maar de tabel `user_guilds` bevat alleen de servers
  van accounts die zijn ingelogd — een terugval die daar leest, toont het
  clanicoon aan een clangenoot en een leeg vak aan een vreemde op hetzelfde
  publieke event. Een avatar mag niet afhangen van wie er kijkt, dus de hash
  staat nu op het team (`teams.guild_icon`), geschreven op hetzelfde moment
  als `guild_name` en langs dezelfde controle: hij komt van Discord, niet uit
  het formulier. Hij verdwijnt ook mee als het team van de server af gaat.
  Een tweede migratie vult hem voor teams die al gekoppeld waren; zonder die
  stap zou het pas werken nadat iemand het team opnieuw opslaat.
  `DiscordCdn::guildIcon()` (uit `BoardController` gelicht, want er is nu een
  tweede aanroeper) bouwt de CDN-url, `Team::$appends` hangt hem aan elke
  payload, en de zes queries die teamkolommen bij naam noemen zijn verbreed —
  een kolom die je niet selecteert is stil `null`, en dat is precies hoe dit
  soort terugval ongemerkt niets doet.
  Aan de clientkant één `Components/TeamAvatar.vue` voor de teamlijst, de
  communitypagina en de deelnemerslijst; de leaderboardrij kan een team óf een
  speler zijn en houdt daarom zijn eigen keten — die **miste `alt`, en dat was
  het gemelde lege vak**: `UAvatar` bouwt zijn initialen uit `alt`, dus zonder
  die prop is er geen derde stap. In een browser nagemeten op beide takken:
  een team met servericon toont het plaatje (64×64 geladen), de twee zonder
  tonen hun initialen. 792 backend / 175 frontend groen.
- [ ] **Een andere speler teleporteert; alleen wie zelf gooit ziet de
  animatie.** Vastgesteld 2026-09-02 in een twee-tab-test met de eigenaar: hij
  zag mij "ineens naar 16" en daarna "van 1 naar 7", zonder één stap.
  **De oorzaak is dat de stream een positie draagt en geen beweging.**
  `SnakesLaddersChannel` stuurt `players` met hun `current_position`; de
  kijker rendert die opnieuw en het stuk staat er gewoon. De loopanimatie
  hangt aan `lastMove` uit de flash van je eigen worp, en die bestaat per
  definitie alleen voor degene die gooide.
  Wat het vraagt: de zet als **feit over het bord** meesturen — van, geland
  op, naar, en welk type sprong — plus een oplopend zet-id, zodat een kijker
  een nieuwe zet kan onderscheiden van een gewone hertekening. Dat past
  binnen de regels van het kanaal: een zet is niet per-kijker, hij is publiek,
  net als de positie die er nu al in zit. Aan de clientkant moet de walker
  daarna generiek worden — nu is hij "mijn stuk", en het moet "een stuk"
  worden.
  Let op de volgorde: de eigen speler krijgt de zet dan twee keer binnen (uit
  zijn eigen flash én uit de stream) en mag niet twee keer lopen.

- [ ] **Twintig spelers op één tegel.** Gevraagd 2026-09-02, meteen na het
  vergroten van de markers. De stapel toont er nu drie plus een `+N`-badge, en
  die markers zijn 24px met een ring van 2px geworden om überhaupt leesbaar te
  zijn op een donkere tegel. Op een 9×9 is een tegel ~70px, dus drie ervan
  plus de badge vullen hem al.
  Nog te beslissen: minder tonen (twee, of alleen een teller), stapelen met
  overlap zoals een avatar-groep, of de markers laten krimpen naarmate het
  drukker wordt — dat laatste botst met de reden dat ze net groter zijn
  gemaakt. Er is geen scherm waarop twintig gezichten op één vakje passen, dus
  dit is een keuze over wat je wegneemt, niet over hoe je ze inpast.

- [ ] **Een 9×9 bord op een telefoon is onwerkbaar.** Gemeld 2026-09-02 tijdens
  het werk aan de connectors: op 375px is een tegel ~36px met twee regels
  tekst erin, en dat is geen bord maar een raster van afkortingen. 7×7 zit er
  vlak tegenaan.
  De richting die de eigenaar noemt is **tegels groter maken en het bord
  horizontaal laten scrollen** in plaats van het te laten krimpen — het bord
  zit al in een `overflow-x-auto`, dus het gaat om de `min-w-*`-drempels per
  formaat, niet om nieuwe machinerie. Nog te bedenken: of dat ook voor 7×7
  geldt, en wat er dan met de leesbaarheid van de tegeltekst gebeurt.
  Let op één ding dat hier net omheen gebouwd is: die scroller knipt allebei
  de assen zodra één ervan niet `visible` is. Alles wat buiten het bord uit
  wil steken — de edit-pil deed dat — moet erbuiten staan.

- [ ] **Beslissen of `docs/README.md` terug naar de repo-root moet.** Nu
  rendert GitHub geen landings-readme voor de repo.

## 7. Discord-server

De server bestaat sinds 2026-08-30 (§0). Sindsdien hangt er een bot in die
vanuit Claude Code bestuurd wordt — een tweede applicatie naast de OAuth-app
van de site, bewust gescheiden zodat gerommel aan de botinstellingen nooit de
login van osrs-events.com kan breken. De installatie staat buiten deze repo
(`~/.claude/mcp/discord-mcp`), dus er is niets aan de app veranderd en er valt
hier ook niets aan te onderhouden.

**De indeling van de server staat in `docs/discord.md`** — kanalen, ID's, hoe
read-only werkt en wat er bewust niet is. Dat is referentie; hieronder staat
alleen wat er nog moet gebeuren.

Eén ding bepaalt wat er in deze lijst *kan* staan: de bot is
commando-in-resultaat-uit en luistert nergens op. Alles hieronder is opzet,
geen automatisering.

- [x] ~~**Kanalen aanmaken.**~~ — gedaan 2026-08-30 via de bot. `INFO` met
  read-only `#announcements` en `#dev-log`, `COMMUNITY` met `#general`
  (verplaatst uit de standaardcategorie), `#beta-feedback` en `#support`.
  Alle vijf hebben een topic; die staan in het Engels, net als de site.
  Read-only is een overwrite op `@everyone` — `MESSAGE_SEND` plus de drie
  thread-permissies geweigerd, reacties bewust wél toegestaan. De webhook
  hangt niet aan een lid en wordt door die weigering niet geraakt, dus die
  post er straks gewoon in. De lege standaardcategorieën en het ongebruikte
  voice-kanaal zijn dezelfde dag verwijderd.
- [ ] **De announcement-webhook koppelen.** Webhook `OSRS Events` is op
  2026-08-30 in `#announcements` aangemaakt; wat rest is de URL ophalen
  (Serverinstellingen → Integraties → Webhooks → Copy Webhook URL) en hem
  ergens invoeren. Die URL hoort nergens in deze repo: wie hem heeft mag in dat
  kanaal posten, dat is de hele authenticatie. Dit is de helft die
  §5 openliet: dat item vroeg om een echte kamer om in te posten, en die is er
  nu. Het aanzetten blijft daar staan — dit item levert alleen de kamer en de
  URL.
  **Let op, dit item beschreef een veld dat niet bestaat** (gecontroleerd
  2026-09-07). Er is geen site-brede webhook-URL: `Setting`
  kent alleen `discord_webhooks_enabled`, een **schakelaar**, en
  `DiscordAnnouncer` post naar `$event->discord_webhook_url` — een kolom op
  het event. De route is dus twee stappen en twee schermen:
  1. admin → Site settings → **Discord announcements** op **aan**. Zolang die
     uit staat is het veld hieronder verborgen en wordt er nooit iets verstuurd.
  2. Het event openen → **Settings** → tabblad **Access** → *Discord webhook*.
     Alleen zichtbaar bij **bewerken**, niet in de create-stepper.
  Daaruit volgt een echte ontwerpvraag die hier nog niet beantwoord is: één
  `#announcements`-webhook per event invoeren betekent dat elk event naar
  hetzelfde kanaal post. Wil je `#announcements` voor site-nieuws en de
  clankanalen voor events, dan is er een **site-brede** webhook nodig die
  `DiscordAnnouncer` nu niet kan lezen — dat is bouwwerk, geen invulveld, en
  het hoort als eigen item opgeschreven te worden voordat iemand eraan begint.
  **Het ophalen van de URL blijft handwerk, met opzet.** De bot kán een
  webhook-URL teruggeven, maar dan staat hij in een sessielog in plaats van
  alleen in de instellingen — en een webhook-URL is een schrijfrecht, geen
  adres. Kopiëren uit de Discord-UI is één klik en laat geen tweede kopie
  achter.
- [x] ~~**`/github` in `#dev-log`.**~~ — gedaan 2026-08-30. Discord-webhook
  `GitHub` in `#dev-log`, met `/github` achter de URL, geregistreerd op de repo
  via `gh api repos/Pondake/osrs-events/hooks` (hook `672396333`) voor de events
  `push`, `pull_request`, `issues`, `issue_comment` en `release`. Bewust niet
  `star`/`fork`/`watch` — die maken het kanaal luidruchtig zonder iets te
  zeggen. Geverifieerd: GitHub's `ping` kwam aan met **204**.

- [ ] **`@everyone` uitkleden.** Moet met de hand: de MCP-bot **weigert de
  `@everyone`-rol te bewerken** ("this operation is risky and restricted"), en
  dat is een terechte weigering — één verkeerd bitfield op die rol raakt elk lid
  tegelijk. Serverinstellingen → Rollen → `@everyone`, en dit eraf:
  **Mention @everyone** (het dringendst — nu kan elk lid dat binnenkomt de hele
  server pingen), Create Invite, Create Events, Create Expressions, Create
  Private Threads, Use Soundboard / External Sounds, Send Voice Messages,
  Create Polls, Use External Apps, Embedded Activities, Request to Speak.
  Laten staan: lezen, praten, reageren, threads in het openbaar, links en
  bestanden, externe emoji's, bijnaam wijzigen, en de voice-basis voor later.
  **Die lijst is op 2026-09-07 tegen de echte rol gehouden** (bitfield
  `2248473465835073`, uitgelezen via de bot en per bit vertaald): alle elf
  staan daadwerkelijk nog aan, er is er niet één die al uit stond, en er is
  ook niets bij dat de lijst mist. Eén naam is makkelijk te verwarren en staat
  daarom hier: **Use External Apps** moet eraf, **Use Application Commands**
  blijft — dat tweede is wat een gewone slash-command laat werken.
- [ ] **Verificatieniveau en contentfilter.** Serverinstellingen → Moderatie:
  verificatie op minstens *Low* (geverifieerd e-mailadres) en het contentfilter
  aan voor alle leden. Ook UI-werk; hier is geen tool voor. Valt weg als
  Community-modus aangaat — die eist allebei al.
- [ ] **AutoMod aanzetten** (Serverinstellingen → AutoMod, met de hand — de bot
  heeft er geen tool voor). Dit is Discord's
  eigen filter voor spam, mention-spam en trefwoorden — geen bot, geen code,
  en het is het enige "reageert vanzelf" dat deze server gaat hebben zolang er
  geen permanent draaiende bot is. De spam- en mention-spamregels zijn de twee
  die iets doen; een trefwoordenlijst is voor later.
- [x] ~~**Invite-link, en besluiten waar hij landt.**~~ — gedaan 2026-09-07 via
  de bot. Een **permanente** invite naar `#general`, `maxAge 0` en `maxUses 0`:
  de twee die er al waren verliepen allebei na 30 dagen, en een link die op de
  landingspagina staat mag niet stilletjes doodgaan. De URL staat nergens in
  deze repo — hij is ingevoerd in admin → Site settings → Discord invite link,
  en dát is waar hij hoort (zie het item hieronder voor waarom dat een
  instelling is en geen constante).
  Waar hij landt: **de landingspagina, de SiteLock-pagina en de betapagina**,
  alle drie via `/discord` in plaats van de invite zelf.
- [ ] **Servericoon en banner** op het sitelogo. Handwerk; daar is geen tool
  voor. De kanaalberichten staan er sinds 2026-08-30 — welkom met
  kanaalwegwijzer in `#general`, en in `#beta-feedback` en `#support` elk een
  bericht dat zegt wat daar nuttig is. **Niet vastgepind, met opzet**: Discord
  zet er een systeemregel bij ("X pinned a message to this channel") die
  rommeliger oogt dan het bericht zelf oplevert. De links in het
  welkomstbericht wijzen naar `.com`-pagina's die pas kloppen zodra `develop`
  live staat.

- [x] ~~**"Join our Discord" op de site.**~~ — gebouwd 2026-09-07. Drie dingen:
  - **`/discord`** (`routes/web.php`, `name('discord')`) stuurt door naar de
    invite uit `Setting::get('discord_invite_url')`, en **404't** als er geen
    staat. Dat is de link die je in een clanchat plakt. De omweg is het punt:
    de invite hoort bij één server, dus hij staat in de database van de
    omgeving die hem gebruikt en nooit in deze repo — een ingetrokken invite
    vervangen is dan een formulierveld en geen deploy. `redirect()->away()`,
    niet `to()`, anders krijgt de URL de eigen origin ervoor.
  - **`EnsureSiteUnlocked`** laat `discord` door, naast `beta`. Wie zonder
    wachtwoord voor de deur staat gaat juist naar de server om erom te vragen;
    een deur die die link naar zichzelf terugstuurt is een gesloten lus.
  - **De knop staat op `Home.vue` en `SiteLock.vue`**, allebei alleen als een
    admin een invite heeft ingevuld — een "join us" die nergens heen gaat leest
    als kapot in plaats van als nog-niet. Op de landingspagina staat hij in de
    hero naast de primaire knop, en hij blijft staan als de deur dicht is:
    dat is dan het enige waar een bezoeker nog op kan klikken. Op de
    SiteLock-pagina staat hij **boven** de admin-hint, met de regel "No
    password? Ask in the Discord" erbij — die pagina heeft geen header, geen
    nav en geen footer, dus er is verder niets te vinden.
  Keys `home.cta_discord`, `lock.discord_hint` en `lock.discord_cta` in
  `lang/en.json`. `tests/Feature/DiscordLinkTest.php` dekt de redirect, de
  404, de doorgang langs de deur, de prop op het lockscreen en dat noch de
  route noch de invite in de sitemap terechtkomt.

- [x] ~~**`#announcements` gevuld met een changelog.**~~ — gedaan 2026-08-30.
  Er waren nooit releases: geen tags, geen versienummers. `CHANGELOG.md` in de
  repo-root is daarom gereconstrueerd uit alle 223 commits, gegroepeerd per arc
  in plaats van per versie, met bovenaan de waarschuwing dat "gebouwd" niet
  hetzelfde is als "live". De post in `#announcements` is de leesbare
  samenvatting daarvan.
- [x] ~~**Privé `#dev-announcements` om de webhook in te testen.**~~ — gemaakt
  2026-09-07 via de bot, in `INFORMATION`. `@everyone` heeft er `VIEW_CHANNEL`
  geweigerd, dus de kamer is verborgen en niet alleen dicht. Er hangt een
  tweede webhook in, **`OSRS Events (test)`**: die plak je in het
  webhook-veld van een event als je wilt zien wat een post doet, terwijl de
  `#announcements`-webhook alleen in admin → Site settings gaat. In het kanaal
  staat een bericht dat dat uitlegt en de vier openstaande §5-checks opsomt
  (link resolven, niets pingen, stil falen bij een ingetrokken webhook, rate
  limit).
  De volgorde uit `docs/discord.md` is aangehouden en werkte: **eerst** de
  allow voor `OSRS Events Ops`, **daarna** pas de weigering op `@everyone` —
  andersom raakt de bot zijn eigen kanaal kwijt en kan hij dat niet
  terugdraaien. Drie dingen die hier al uit waren geleerd en die tijd
  schelen:
  1. **Een `@everyone`-deny op `MESSAGE_SEND` raakt de webhook niet, de bot
     wel.** Een webhook hangt niet aan een lid; de bot wel, dus die valt onder
     dezelfde weigering als iedereen.
  2. **De bot kan zichzelf dat recht niet teruggeven.** Een allow-overwrite voor
     zijn eigen rol werd geweigerd — Discord staat niet toe dat je een
     permissie uitdeelt die je in dát kanaal zelf niet hebt. Wil je de bot in
     een read-only kanaal laten posten, dan zet een mens die overwrite in de UI.
  3. **De announcement-webhook is bewezen werkend**: de changelog-post van
     2026-08-30 ging er doorheen en rendert goed in een echt kanaal. Dat is één
     van de vijf dingen die §5 wilde zien; de andere vier (link resolven, niets
     pingen, stil falen bij een ingetrokken webhook, rate limit) staan nog open
     en horen juist in dat privékanaal thuis.

- [ ] **Community-modus aanzetten.** Serverinstellingen → Enable Community.
  Levert in één klap drie dingen die anders een bot vragen: de
  **regelpoort** ("Before you can talk here…" — een nieuw lid moet de regels
  aanvinken voordat het mag praten), een **welkomstscherm**, en
  **announcement channels** waar andere servers `#announcements` kunnen
  volgen. Het dwingt tegelijk verificatieniveau ≥ *Low* en het contentfilter
  aan, dus het vinkt het item hierboven vanzelf af. Geen bot, geen code, wel
  handwerk — er is geen tool voor serverinstellingen.
- [ ] **`#support` als forumkanaal, zodra het druk genoeg wordt.** Wise Old Man
  doet intake met knoppen onder een botbericht; dat kan hier niet en dat is
  geen tijdelijke beperking (zie hieronder). Een **forumkanaal** met tags
  (Bug / Vraag / Account / Event) geeft hetzelfde: elke melding wordt een eigen
  thread, gefilterd en afsluitbaar, zonder bot. Nu nog niet doen — met een
  handvol betatesters is een forum met drie threads doder dan een kanaal.
  Let op: een tekstkanaal is niet om te bouwen naar een forum, dat is
  weggooien en opnieuw maken.

**Rollen zijn overwogen en voorlopig afgevallen.** Een `@beta-tester`-rol lijkt
logisch maar heeft geen werk te doen: de beta gaat op een gedeeld wachtwoord
(§0, invites zijn daar al afgevallen), dus er is niets om iemand toegang toe te
geven dat de serverdeur niet al regelt. Zodra er een kanaal komt dat niet voor
iedereen is, is dat het moment.

---

## Beantwoord bij het openen van deze backlog

Eén item uit de oude lijst was al opgelost en is daarom niet meegekomen:
**"Decide whether task tiles on Snakes & Ladders should require proof and host
approval"** (2026-08-25). De claim/approve-flow voor S&L-tegels is op
2026-08-30 gebouwd (commit `d122b7d`, plus de twee bugfixes die er direct op
volgden): `boards`/`completed_tiles` kregen hun eigen reviewkolommen,
`proof_url` is verplicht zodra een bord goedkeuring vraagt, en een afgewezen
claim kan opnieuw ingediend worden. De asymmetrie met bingo is bewust op één
punt bewaard — een afgekeurde bingo-square blijft op slot, een afgekeurde
S&L-tegel niet, omdat de speler daar op die tegel *staat* en anders nooit meer
kan rollen. Het archief houdt de oorspronkelijke redenering.
