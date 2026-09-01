# Ideeën

Werk dat echt is bedacht maar niet gepland. Alles hier is uitgezocht ver genoeg
om te weten wat het zou kosten, en bewust niet in `docs/backlog.md` gezet —
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
