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

## RuneLite plugin

- **Boss race op `context.kill_count` in plaats van Wise Old Man.** Uitgezocht
  2026-09-21; er is niets gebouwd. Wat er al is: `DROP_RACE` bestaat
  (`Event::EVENT_TYPES`, boss-metriek uit `BOSS_METRICS`) en telt de **meeste
  kills in het eventvenster**: `EventStandingsService::refresh()` vraagt Wise Old
  Man om `bosses.{metric}.kills` gained tussen start- en einddatum, en dat is de
  ranglijst. Er is geen "eerste die N kills haalt". Dat laatste kan al wel als
  bord- of bingotaak: een vak "Kill Zalcano" met `required_count` 5 telt de
  kills al op afzonderlijke `context.kill_count` (`TargetProgressService`).
  Waarom `kill_count` niet zomaar de bron wordt:
  - Een boss race zit niet in `RunelitePluginService::openTargets()` (alleen
    BINGO en SNAKES_LADDERS), dus de plugin krijgt geen bossnamen in `watch` en
    meldt die kills niet.
  - Wie de plugin niet draait telt dan niet mee, terwijl Wise Old Man iedereen
    meet. Twee bronnen in één ranglijst geeft een oneerlijke stand.
  - Een kill count uit de client is vervalsbaar (trust boundary in
    `docs/runelite-plugin.md`), en hier gaat het om een ranglijst, geen claim
    die een host kan beoordelen.
  Voorstel als het toch moet: de plugin-`kill_count` als **live aanvulling** op
  de Wise Old Man-stand, niet als vervanging. Per deelnemer de hoogste
  `kill_count` uit `plugin_completions` per baas (naam moet op de
  `BOSS_METRICS`-sleutel gemapt worden), en de plugin krijgt de racebazen via
  een nieuwe lijst in `/events` (bv. `races`), want `watch` is een lijst
  strings en verandert niet. Toon het als "live" naast de gesynchroniseerde
  waarde en laat Wise Old Man de rangorde beslissen. Kost een mapping
  npc-naam naar metriek en een plugin-wijziging om alle kills van een racebaas
  te melden, ook zonder tegel.
