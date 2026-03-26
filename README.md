# Technische Entscheidungen

## Eigene Entity statt Custom Fields

Die API-Daten landen in einer eigenen Tabelle `wbm_product_type_data` mit OneToOne zum Produkt. Custom Fields sind für 30 Felder nicht performant/wartbar und direkte Spalten in der Product-Tabelle... nope. Also eigene Entity per `EntityExtension`, skaliert sauber und ist der empfohlene Weg (Zudem erlaubt eine eigene Entity die Nutzung nativer DAL-Flags wie ApiAware und SearchRanking).

## ES-Mapping: Nested statt Object

`wbmProductTypeData` ist im ES-Index als `nested` gemappt. Hätte gerne `object` genommen (simpler), aber der `CriteriaParser` erkennt die OneToOne-Assoziation und wickelt Aggregationen und Filter automatisch in `NestedAggregation`/`NestedQuery` ein. Mit `object`-Mapping knallt es dann zur Laufzeit. Also `nested`, weil Shopware es so erwartet.

## Keyword ohne Normalizer

Das `productType`-Keyword im ES-Mapping hat bewusst keinen `sw_lowercase_normalizer`. Normalerweise nimmt man den Shopware-Standard (`KEYWORD_FIELD`), aber dann kommen die Aggregation-Buckets im Storefront-Filter kleingeschrieben zurück -- "bücher" statt "Bücher". Die `.search`- und `.ngram`-Subfelder machen die Volltextsuche trotzdem case-insensitive, also kein Nachteil.

## ES-Integration per Decoration

Ich dekoriere `ElasticsearchProductDefinition` und `ProductAdminSearchIndexer` direkt, statt Events wie `ElasticsearchCustomFieldsMappingEvent` zu nutzen. Der Grund: Events geben nur Zugriff aufs Mapping, aber nicht auf `fetch()`. Ich muss aber beides erweitern: Mapping und die SQL-Query die Daten für den Index liefert. Decoration ist der einzige Weg das konsistent zu machen.

## Storefront-Filter: AbstractListingFilterHandler

Der Filter ist als `AbstractListingFilterHandler` gebaut, gleicher Mechanismus wie der Core-`ManufacturerListingFilterHandler`. Alternativ gäbe es den `ProductListingCollectFilterEvent`, aber dann müsste man die Filter-Exclusion-Logik für Multi-Select selbst bauen. Der Handler wird vom `AggregationListingProcessor` automatisch korrekt verwaltet.

## Admin-Suche: Score-Queries im JS

Die Produktsuche in der Admin-Produktliste (Katalog > Produkte) läuft über DAL-Score-Queries (`criteria.addQuery()`) im JS-Override. Das funktioniert unabhängig davon ob ES aktiv ist oder nicht, weil die Score-Queries auf SQL- und ES-Ebene greifen.

## Admin-Suche (Global): Eigener Admin-ES-Index

Die globale Admin-Suche (obere Suchleiste) nutzt einen separaten Admin-ES-Index mit flachen `text`/`textBoosted`-Feldern — komplett getrennt vom Storefront-Produktindex. Shopware stellt dafür den `AbstractAdminIndexer` bereit. Der `WbmProductAdminSearchIndexerDecorator` dekoriert den `ProductAdminSearchIndexer` und hängt `productType` an beide Felder an.

## Storefront-Suche: SHOULD statt MUST

Für die Bonus-Aufgabe (Storefront-Suche) werden Original-Query und `productType`-Match als `SHOULD`-Klauseln in einer `BoolQuery` kombiniert. Hätte die Original-Query auch als `MUST` setzen können mit `productType` als Boost, aber dann würden Produkte die NUR über `productType` matchen (z.B. Suche "Bücher" bei einem Produkt ohne "Bücher" im Namen) nicht gefunden. Mit reinen `SHOULD`-Klauseln muss mindestens eine matchen, und Produkte die beides treffen ranken höher.

## Version-ID in ES-Queries

Alle SQL-Queries in den ES-Decoratoren filtern nach `product_version_id = Defaults::LIVE_VERSION`. Muss bei RAW queries sein, weil die Tabelle versioniert ist und man sonst Draft-Daten aus dem Admin mit-indexieren würde.
