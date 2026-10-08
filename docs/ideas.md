# Ideeën

Werk dat echt is bedacht maar niet gepland. Alles hier is uitgezocht ver genoeg
om te weten wat het zou kosten, en bewust niet in de backlog gezet —
die lijst is alleen wat er op afzienbare termijn opgepakt wordt, en een idee
dat daartussen staat concurreert met werk dat wél af moet.

**Geopend 2026-08-30**, bij het opnieuw beginnen van de backlog. Verplaats een
item hierheen als het blijft liggen, en terug als het aan de beurt is.

---

## Nieuwe eventtypes

Vier formats die als marketingcopy op `/osrs-event-ideas` staan en verder
nergens — geen regel in `Event::EVENT_TYPES`, geen code. De pagina belooft ze
dus al aan wie hem leest.

- **Speedrun ladder** — een vaste set encounters, leden dienen tijden in, loopt
  onbeperkt door zonder einddatum. Het minst onderhoudsintensieve format van de
  vier; geschikt als permanent achtergrondevent tussen grotere events door.
- **Achievement diary- of questrace** — punten voor diary-tiers of quests
  afgerond binnen het eventvenster. Een van de weinige formats die nieuwere
  accounts bevoordeelt boven veteranen, omdat veteranen alles al af hebben —
  bruikbaar om een lichting nieuwe leden binnen te halen.
- **Battleship** — elk team verbergt schepen op een raster en vuurt door taken
  af te ronden. Erg sociaal, erg chatzwaar, en het vraagt een organisator die er
  dagelijks naar kijkt: dit format staat of valt met iemand die scheidsrechtert.
- **Collection log push** — bijhouden hoeveel collection-log-slots de hele clan
  samen vult tegen één gedeeld doel. Coöperatief in plaats van competitief, wat
  past bij clans waar een leaderboard mensen eerder afschrikt dan aantrekt.

## Community

Allebei staan ze als "Soon"-placeholder op `/community`, met uitleg in plaats
van lege ruimte. Wat die twee placeholders beschrijven is echt scopewerk, geen
vulcopy.

- **Globale leaderboards.** Site-brede ranglijsten over elk event waar een
  speler aan meedeed, in plaats van per event zoals `LeaderboardController` en
  `EventStandingsService` allebei nu werken. Concrete startvorm: totaal aantal
  afgeronde tegels over alle Snakes & Ladders-borden ooit, aantal events
  gehost tegenover meegespeeld, en een hall of fame voor de grootste enkele
  XP-winst en kill-count-winst die `EventStandingsService` ooit opnam — die
  cijfers bestaan al per event, dit is een aggregatievraag en geen nieuwe
  databron. Twee dingen om eerst te beslissen: is het all-time of heeft het een
  eigen venster (een seizoen?), en houdt een account dat door de
  verwijderflow is gegaan zijn plek als "Deleted player" — consistent met hoe
  de standings van een afgelopen event dat al doen — of valt het juist uit een
  *globale* ranglijst. Die vraag heeft deze app nog nooit hoeven beantwoorden,
  omdat elke ranglijst tot nu toe binnen één event viel.
- **Clan directory.** Een doorbladerbare, publieke gids van clans die open
  staan voor nieuwe leden. Iets anders dan `/teams`, dat alleen teams toont waar
  een account al in zit of een Discord-server mee deelt (`Team::scopeVisibleTo`)
  en dus onbruikbaar is om een clan te vinden waar je nog niet bij hoort.
  Nodig: een opt-in "publiek vermeld"-vlag op `Team` (standaard uit — een privé
  team blijft precies zo onzichtbaar als vandaag), een publieke indexpagina met
  ledenaantal, recente activiteit en aankomende events, en een echt
  aanmeldmechanisme voor een vreemde. **Dat mechanisme is het dragende deel** —
  vandaag is de enige route naar een team dat iemand die het al beheert je
  toevoegt (`TeamController::addMember`/`searchUsers`, allebei beperkt tot
  mensen die voor die beheerder al zichtbaar zijn). Een gids die alleen clans
  toont zonder manier om erin te komen is een lijst, geen gids.
- **Roster-historie per team.** Een team met leden A/B/C/D bij het ene event en
  A/B/D bij het volgende is nog steeds "hetzelfde team", en er is nu geen manier
  om te zien dat de bezetting veranderde. Waarschijnlijk vooral een UI-vraag en
  geen opslagvraag: de audit log registreert team- en ledenmutaties al, dus denk
  aan een lichtgewicht "roster door de tijd"-weergave per team in plaats van een
  nieuw versioneringssysteem.

## Platform

- **CMS / layout-editor** (roadmap fase 6). Elke publieke pagina bewerkbaar
  vanuit de adminsectie via een layout-editor die pagina's samenstelt uit Nuxt
  UI-page-elementen (`u-page-hero`, `u-page-section`, `u-page-feature`, …) in
  plaats van een vormvrije rich-text-blob — die componenten zijn al de
  woordenschat waarin de pagina's geschreven zijn, dus de editor hoort dezelfde
  taal te spreken. De opslag bestaat al (`pages`-tabel, één JSON
  `blocks`-document per pagina plus titel/subtitel/SEO/is_published, sinds
  2026-08-20). `/admin/content` bestaat als landingsplek en doet één eerlijk
  ding: de acht publieke pagina's inventariseren en vermelden dat ze nog
  hardcoded Vue zijn. Het doet bewust niet alsof er een editor is. **Grootste
  ongebouwde stuk van de roadmap**, en de reden dat guidepagina's voorlopig
  statisch blijven.
- **In-house iconset in plaats van (of naast) de live wiki-lookup** voor
  team- en taakicons, nu skill-icons bestaan en boss-icons in de backlog staan.
  Een wezenlijk andere vraag dan "kun je de wiki doorzoeken", wat al beantwoord
  is (`WikiController::searchGlobal()`, `WikiIconPicker.vue`).
- **Een Discord-bot, en wat er dan pas kan.** Op 2026-08-30 afgesloten als
  onmogelijk: "lid toevoegen"-suggesties uit de ledenlijst van een
  Discord-server kunnen niet met de scopes die deze app vraagt
  (`identify guilds`) — dat vereist een bot-token met de privileged
  GUILD_MEMBERS-intent. **Als er om een andere reden tóch een bot komt** — de
  eigenaar overweegt er een voor de eigen Discord-server en voor MCP-toegang —
  dan verandert die rekensom, en is dit het item dat opnieuw open mag. Let wel
  op wat het kost: een bot die in een clanserver gezet wordt om ledenlijsten te
  lezen is een wezenlijk grotere privacyclaim dan een login die alleen vraagt
  in welke servers je zit, en de privacyverklaring zou dat moeten zeggen.
- **Site-events spiegelen naar Discord scheduled events.** Een event dat op
  osrs-events.com wordt aangemaakt verschijnt dan als geplande activiteit in de
  gekoppelde Discord-server, met de starttijd en een link — precies de plek
  waar een clan toch al kijkt wat er vanavond is. **Dit kan niet met de
  webhook** die de announcements doet: een webhook mag alleen berichten posten,
  scheduled events vragen een bot-token in de app zelf. Dat is een wezenlijk
  andere architectuur dan wat er nu staat (zie ook het ledenlijst-item
  hierboven, dat op dezelfde muur stuit), en de reden dat dit hier staat en
  niet in de backlog. De ops-bot die de eigen server beheert is *niet* dezelfde
  laag: die draait lokaal en heeft geen weet van de app.
- **Een echte eisenronde voor de adminsectie.** Wat "adminfunctionaliteit"
  verder moet dekken is nooit uitgeschreven; er is steeds per stuk bijgebouwd.
  Verdient één keer goed nadenken in plaats van nog een gok — maar pas als er
  een aanleiding is, anders is het een ontwerp zonder gebruiker.
- **De guides en `/beta` naar de CMS, zonder dat ze lelijker worden.** Alle
  zeven zijn statische Vue-pagina's, en dat is twee keer bewust gekozen: de
  guides toen ze van `u-page-section` naar de huidige layout werden herschreven
  (zie `LandingController::snakesAndLadders()`, waar de FAQ ooit uit een
  `pages`-rij kwam), en `/beta` op 2026-09-07 opnieuw. De reden is beide keren
  dezelfde: de blokkenwoordenschat kan de twee-sporenlayout, de zijbalk en de
  quick facts niet, en een pagina die je in één bestand kunt lezen is makkelijker
  te herontwerpen dan een documentmodel.
  **Wat het zou opleveren is echt**: `/beta` verandert tijdens een beta wekelijks,
  en nu is elke tekstwijziging een commit plus een deploy met twee builds.
  **Wat er eerst moet gebeuren**, en dit is de kern van het idee: de vraag is
  niet "zet die tekst in de database" maar **welke blokken de CMS mist** om deze
  layout te halen zonder in te leveren op hoe hij eruitziet. Minstens: een
  twee-sporenblok met eigen nummering per spoor, iets voor de zijbalksecties en
  de quick-facts-tabel, en een `is_indexable`-vlag — die laatste ontbreekt sowieso
  en houdt nu elke gepubliceerde CMS-pagina automatisch in de sitemap
  (`SitemapController`), wat voor een betapagina precies verkeerd is.


## Zoekverkeer

Uitgewerkt op 2026-10-08. Het uitgangspunt staat in `docs/ROADMAP.md` (fase 5, augustus 2026): op *bingo* zit bijna alle vraag en ook alle concurrentie, op Snakes & Ladders zit nauwelijks vraag maar ook niemand anders. Elk plan hieronder is daarom long-tail: veel kleine zoekvragen waar nog geen goed antwoord op bestaat.

**Er is nog niets gemeten.** De site laadt geen analyticsscript, en Search Console is alleen te lezen als iemand er een export van maakt. Doe dat dus eerst: Prestaties → Zoekresultaten, laatste drie maanden, Queries en Pagina's exporteren naar Drive. Wat daar al vertoningen heeft, gaat voor. Is er later meer nodig, kies dan iets zonder cookies, zoals Plausible of Umami, en niet GA4, want daarvoor moet in de EU een toestemmingsbanner komen.

**Voorgestelde volgorde:** Hardcore Worlds (heeft een deadline), publieke events vindbaar maken, tegellijsten, tools, gidsen en als laatste de blueprintpagina's.

### Hardcore Worlds: deadline 4 november 2026

Een permanente nieuwe spelmodus, aangekondigd op de RuneFest 2026 Summit (3 oktober). De onderstaande punten komen uit samenvattingen van derden. De officiële nieuwspost en de wiki waren vanuit de sessie waarin dit is geschreven niet bereikbaar, dus **controleer elk feit via de `osrs`-MCP of de officiële nieuwspost voordat het in copy belandt**:

- aparte werelden met een eigen economie, iedereen begint met een nieuw personage;
- één leven, met de regels van Hardcore Group Ironman (de Fight Caves, de Inferno en Nightmare Zone tellen als onveilige dood);
- na een dood kies je tussen opnieuw beginnen of overstappen naar het hoofdspel, waarbij je alles houdt behalve wat je bij de dood verloor;
- geen XP- of dropboosts.

Dat is het beste moment voor een clanevent in jaren: iedereen begint op dezelfde dag vanaf nul. Daar kan dit product precies op inspelen.

- **Pagina `/osrs-hardcore-worlds-clan-events`.** Zelfde opzet als de andere gidsen (`GuideLayout`), met FAQ-JSON-LD via `View::share('jsonLd')` vanwege de `@context`-valkuil die in `LandingController` beschreven staat. De pagina krijgt een plek in `SitemapController::STATIC_PATHS` en een link vanaf `/osrs-event-ideas` en de home. Inhoud:
  - waarom een gedeelde start zich zo goed leent voor een clanevent;
  - drie of vier formats: een launch-race, een bingo voor verse accounts, "wie leeft het langst" en een teamrace;
  - een blok **huisregels voor de dood**. Een host moet kiezen wat er met de punten van een gestorven speler gebeurt (houden, bevriezen of kwijt), en of overstappen naar het hoofdspel betekent dat je uit het event ligt. Elke clan loopt daar tegenaan, en geen concurrent schrijft erover.
- **Blueprints:** "Hardcore Worlds launch sprint" (een variant op New Account Sprint) en "Fresh start bingo" in `EventBlueprintSeeder`. Die seeder werkt met `updateOrCreate` op de titel, dus controleer dat de deploy hem ook echt draait.
- **Tegellijst voor verse accounts:** de eerste pagina van het plan *Tegellijsten* hieronder, met tegels die in de eerste week haalbaar zijn.
- **Risico: Wise Old Man.** De standen van skill- en droprace komen uit de WOM-gains. Of WOM personages op Hardcore Worlds bij launch al bijhoudt, is onbekend; hebben die eigen hiscores, dan is dat helemaal onzeker. Bingo en Snakes & Ladders draaien op handmatige claims en werken hoe dan ook. De pagina zet die twee daarom voorop en noemt races pas als WOM ze bevestigd ondersteunt.
- **Niet voor launch:** een sterftefunctie, waarmee een host een speler "uit" zet of de plugin een dood meldt. Die is echt nuttig, maar te groot voor vier weken. Een host kan de regel in de beschrijving zetten en de speler verwijderen. Komt de functie later toch, dan moet de exacte doodsmelding in de chat uit de `osrs`-MCP komen en niet uit het geheugen.
- **Timing:** de pagina moet minstens twee weken vóór launch online staan, anders is hij nog niet gecrawld als de zoekpiek komt. Dien de URL na de deploy handmatig in bij Search Console. De modus is permanent, dus de pagina blijft daarna relevant.

### Publieke events vindbaar maken, en uitslagen

**De stand van nu.** Een gelijst event (`is_listed`) is zonder account te lezen (`BoardAccessService::canView()`). Voortgang is publiek op elk gelijst event, namen alleen op een OPEN event (`canSeeParticipants()`). Google ziet er toch niets van:

- `BoardShow.vue` en `Events/Participants.vue` hebben allebei vast `noindex, nofollow`;
- de sitemap laat events weg;
- de docblock van `SitemapController` zegt nog altijd "an event page needs a login", wat sinds de `canView`-wijziging niet meer klopt.

Het plan:

- **Eén regel, op één plek:** `Event::isIndexable()`. Waar is die regel als het event gelijst is, al begonnen is en minstens een paar deelnemers heeft. Een leeg of nog niet gestart event is een dunne pagina en blijft `noindex`. Achter de site-lock is niets indexeerbaar.
- `BoardShow.vue` zet de robots-tag op basis van een prop `indexable` die de server berekent. De pagina beslist dat niet zelf.
- **Titel en beschrijving** die iets zeggen: "<titel> — OSRS <type>" met de data, het aantal deelnemers en wie er voorstaat. Daarnaast JSON-LD `Event` (startDate, endDate, eventStatus, `OnlineEventAttendanceMode`, `VirtualLocation`), langs dezelfde Blade-route als de gidsen.
- **Sitemap:** indexeerbare events met `lastmod`, `daily` zolang ze lopen en `monthly` als ze voorbij zijn.
- **Een afgelopen event wordt een uitslagenpagina.** Bovenaan staat het podium plus een samenvatting, server-side gerenderd zodat een crawler het ziet. Dat is de link die clans delen, en gedeelde links zijn precies wat deze site nu mist.
- **De deelnemerspagina blijft `noindex`.** Op een OPEN event staan daar namen, en een RSN in Google naast een clannaam is een grotere publicatie dan dezelfde naam op de site zelf.
- **Beslissing voor de eigenaar:** wordt een gelijst event automatisch indexeerbaar, of komt er een aparte schakelaar "Tonen in zoekmachines"? Advies: een schakelaar die standaard aan staat voor gelijste events. Gelijst staan op `/events` is een kleinere belofte dan in Google staan, en zo hoeft er niets met terugwerkende kracht te veranderen voor events die al bestaan. Daarbij hoort één zin in de privacyverklaring.
- Later, los hiervan: een OG-afbeelding per event voor Discord-embeds. Begin met één statische afbeelding per type. Dynamisch genereren kost meer dan het nu oplevert.
- **Tests:** `SitemapTest` (wel en niet indexeerbaar), een featuretest op de robots-tag, en de bestaande e2e-crawl.

### Tegellijsten ("osrs bingo tile ideas")

Doelvragen: "osrs bingo tile ideas", "… ironman", "easy bingo tiles", "<boss> bingo tiles". Bingo is druk, maar tegellijsten zijn long-tail en dit is precies de data die de app al heeft.

**Blokkade:** een `Task` heeft alleen een titel, icoon, beschrijving en wikivelden. Er is geen categorie, moeilijkheid of iron-geschiktheid, dus een lijst "voor ironmen" valt niet te genereren. Er zijn twee routes:

- **a)** kolommen op `tasks`: `category` (boss, raid, skilling, minigame, clue, quest), `difficulty` (easy, medium, hard of elite) en `iron_friendly`, te bewerken op `/admin/tasks`;
- **b)** samengestelde lijsten in code, zoals `PluginTestSet`.

Advies: **a)**. Dan is er één bron, is elke lijst een query, en kan het tegelkeuzescherm in de editor er ook op filteren. Dat is productwaarde los van zoekverkeer.

- **Pagina's:** een hub `/osrs-bingo-tile-ideas` met subpagina's per lijst: `/easy`, `/ironman`, `/bossing`, `/skilling`, `/raids` en `/hardcore-worlds`. Per pagina:
  - een eigen, handgeschreven intro (waarom deze tegels, en hoe je een kaart in balans houdt);
  - de lijst, met iconen en wikilinks;
  - per tegel één regel toelichting;
  - een CTA "Gebruik deze lijst" (naar de aanmaakflow met de tegels voorgeselecteerd; controleer of de aanmaakmodal dat kan) en een CTA "Genereer een kaart" (naar de generator hieronder).
- **Ondergrens tegen dunne pagina's:** minstens ±25 tegels en een eigen intro per pagina. Een pagina per boss pas als die boss genoeg tegels heeft.
- **Het echte werk** is de 198 taken uit `TaskSeeder` indelen. Moeilijkheid en iron-geschiktheid zijn spelkennis, dus via de `osrs`-MCP, niet op gevoel.
- **Sitemap:** de lijstpagina's komen uit een vaste lijst slugs, zodat het faalt-dicht-principe van `STATIC_PATHS` blijft gelden. Copy onder `landing.tiles.*`.

### Gratis tools zonder account

Tools zijn wat er in clan-Discords gelinkt wordt, en links zijn wat de site mist. Elke tool krijgt onder het gereedschap zelf ook uitleg en een FAQ, zodat het geen kale widget is. Interactieve `@nuxt/ui`-delen gaan in `<ClientOnly>` (zie `docs/ssr-gotchas.md`). Anoniem gebruik schrijft niets naar de database.

1. **Bingokaartgenerator, `/osrs-bingo-card-generator`.**
   - Je kiest een grootte (5×5 of 7×7), een tegellijst, een moeilijkheidsmix en een vrij middenvak.
   - Je kunt één vak opnieuw laten trekken, de kaart als tekst kopiëren voor Discord, of hem met "Start dit als event" (na inloggen) openen in de aanmaakflow met de vakken ingevuld.
   - Een `?seed=` in de URL maakt een kaart deelbaar zonder iets op te slaan. Een kaart op een vaste seed kan server-side gerenderd worden als voorbeeld dat crawlers zien.
   - De trekking zit nu alleen in de devroute `/dev/events/{id}/fill-random` (`TileListEditor.vue`). Haal die keuzelogica naar een service die beide gebruiken.
   - Hangt af van de tegellijsten hierboven, voor de moeilijkheid.
2. **Teamverdeler, `/osrs-team-splitter`.**
   - Je plakt maximaal ±50 RSN's, kiest het aantal teams en waarop gebalanceerd wordt (EHP, EHB, totaal level of combat).
   - De gegevens komen uit `WiseOldManService::findPlayer()`. De verdeling is greedy: sorteren en dan steeds bij het lichtste team zetten. De uitkomst is te kopiëren voor Discord, en wie ingelogd is kan er de teams in een event mee maken.
   - **De kosten zitten in WOM:** 50 namen zijn 50 requests, binnen `requestsPerMinute()`. Nodig: lookups 24 uur cachen, een benoemde limiter per IP, verwerken in stukken met voortgang, en onbekende namen melden zonder dat de rest faalt. Lees vooraf de WOM-voorwaarden en of er een API-sleutel nodig is.
3. **Dropkanscalculator, `/osrs-drop-chance-calculator`.**
   - Invoer: droprate (1/x), kills per uur, aantal uur en teamgrootte. Uitvoer: de kans op minstens één drop, het verwachte aantal, en de kills voor 50% en 90% kans.
   - Pure rekenwerk in de browser, zonder data om bij te houden.
   - Algemene kanscalculators bestaan al. De invalshoek hier is een event plannen: "hoe lang moet mijn droprace duren voor deze drop bij dit team".
   - Vooraf ingevulde items komen hooguit later, met rates uit de `osrs`-MCP.

### Gidsen

Alleen onderwerpen waar deze site iets heeft wat de concurrenten niet hebben. De opzet is dezelfde `GuideLayout` als de bestaande gidsen. Elke gids linkt naar de tools en de tegellijsten, zodat de pagina's elkaar dragen.

- **`/osrs-clan-bingo-rules`**: een regeltemplate om te kopiëren naar Discord, met een kopieerknop. Behandelt screenshots met tijdstempel, teamnamen, geschillen en alts.
- **`/osrs-bingo-drop-verification`**: hoe je drops controleert, met screenshotconventies en RuneLite-verificatie. De plugin wordt alleen genoemd als `runelite_plugin_mode` op `live` staat. Een pagina die een plugin belooft die nog in test is, belooft te veel.
- **`/osrs-snakes-and-ladders-vs-bingo`**: welk format bij welke clan past, naar grootte, activiteit en duur. Weinig concurrentie, en het verbindt de twee sterkste pagina's.
- **`/how-to-run-an-osrs-clan-event`**: een checklist van aankondiging tot prijsuitreiking, als hub naar al het bovenstaande.

Liever één goede gids per twee weken dan vier tegelijk. Kijk in Search Console welke vertoningen krijgen voordat er meer bijkomen.

### Blueprintpagina's (laagste prioriteit)

`/osrs-event-ideas/{slug}` per blueprint, met `/osrs-event-ideas` als hub. Een blueprint heeft nu alleen een korte `description` en geen slug. Een eigen pagina vraagt per blueprint lange copy: regels, duur, scoring en prijssuggesties. Die copy komt in `lang/en.json` op slug, en een pagina bestaat alleen als die copy er is. Dat faalt dicht en voorkomt dunne pagina's. Begin met de blueprints waar zoekvraag naar is: Skill of the Week, Boss of the Week en de Hardcore Worlds-sprint. Mogelijk zijn deze pagina's beter samen te voegen met de tegellijst-hub dan apart te bestaan.
