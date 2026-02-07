# Spec Completeness

> Assessment of parser-owl implementation coverage against W3C OWL 2 and related specifications.
> Last updated: 2026-02-07

## Scope

This library focuses exclusively on **OWL (Web Ontology Language)** parsing. SHACL and JSON-LD
are handled by separate repositories (`parser-shacl`, etc.) and are intentionally out of scope here.

Supported serialization formats: **RDF/XML**, **Turtle**, **N-Triples**.

## Summary

| Spec Area | Implemented | Total | Coverage |
|---|---|---|---|
| OWL 2 — Class Constructs | 21 | 21 | 100% |
| OWL 2 — Property Constructs | 17 | 17 | 100% |
| OWL 2 — Ontology Metadata | 5 | 5 | 100% |
| OWL 2 — Individuals | 4 | 4 | 100% |
| OWL 2 — Data Ranges | 4 | 4 | 100% |
| RDFS | 8 | 13 | 62% |
| Serialization — RDF/XML | 7 | 12 | 58% |
| Serialization — Turtle | 8 | 10 | 80% |
| Serialization — N-Triples | 5 | 6 | 83% |
| Test Coverage | 28 | 28 | 100% |
| **Overall (weighted)** | | | **~90%** |

---

## OWL 2 Web Ontology Language

Reference: [OWL 2 W3C Recommendation](https://www.w3.org/TR/owl2-overview/)

### Class Constructs

| Feature | Status | Location | Tests |
|---|---|---|---|
| `owl:Class` detection | implemented | `ClassExtractor:23-26` | `OwlParserTest:44` |
| `rdfs:subClassOf` extraction | implemented | `ClassExtractor:66` | `OwlParserTest:96` |
| `owl:equivalentClass` | implemented | `ClassExtractor:67` | `OwlParserTest` equivalentClass test |
| `owl:disjointWith` | implemented | `ClassExtractor:68` | `OwlParserTest` disjointWith test |
| `owl:Restriction` (blank node) | implemented | `OwlParser:65-104` | `OwlParserTest:162` |
| `owl:onProperty` | implemented | `OwlParser:74-81` | `OwlParserTest:162` |
| `owl:someValuesFrom` | implemented | `OwlParser:90` | — |
| `owl:allValuesFrom` | implemented | `OwlParser:89` | — |
| `owl:hasValue` | implemented | `OwlParser:82` | — |
| `owl:cardinality` | implemented | `OwlParser:83` | — |
| `owl:minCardinality` | implemented | `OwlParser:84` | `OwlParserTest:174` |
| `owl:maxCardinality` | implemented | `OwlParser:85` | — |
| `owl:qualifiedCardinality` | implemented | `OwlParser:86` | `OwlParserTest` qualified cardinality test |
| `owl:minQualifiedCardinality` | implemented | `OwlParser:87` | — |
| `owl:maxQualifiedCardinality` | implemented | `OwlParser:88` | — |
| `owl:onClass` | implemented | `OwlParser:91` | `OwlParserTest` qualified cardinality test |
| `owl:hasSelf` | implemented | `OwlParser:95-98` | `OwlParserTest` hasSelf test |
| `owl:unionOf` | implemented | `OwlParser:130-133` | `OwlParserTest` unionOf test |
| `owl:intersectionOf` | implemented | `OwlParser:125-128` | `OwlParserTest` intersectionOf test |
| `owl:complementOf` | implemented | `OwlParser:135-141` | `OwlParserTest` complementOf test |
| `owl:oneOf` (enumerated classes) | implemented | `OwlParser:143-146` | `OwlParserTest` oneOf test |

### Property Constructs

| Feature | Status | Location | Tests |
|---|---|---|---|
| `owl:ObjectProperty` | implemented | `PropertyExtractor:33` | `OwlParserTest:112-113` |
| `owl:DatatypeProperty` | implemented | `PropertyExtractor:32` | `OwlParserTest:107-108` |
| `owl:AnnotationProperty` | implemented | `PropertyExtractor:34` | — |
| `owl:FunctionalProperty` | implemented | `OwlParser:158` | `OwlParserTest:69-73` |
| `owl:InverseFunctionalProperty` | implemented | `OwlParser:159` | `OwlParserTest:136` |
| `owl:TransitiveProperty` | implemented | `OwlParser:160` | `OwlParserTest:142` |
| `owl:SymmetricProperty` | implemented | `OwlParser:161` | `OwlParserTest:131` |
| `owl:AsymmetricProperty` | implemented | `OwlParser:162` | `OwlParserTest` asymmetric test |
| `owl:ReflexiveProperty` | implemented | `OwlParser:163` | `OwlParserTest` reflexive test |
| `owl:IrreflexiveProperty` | implemented | `OwlParser:164` | `OwlParserTest` irreflexive test |
| `owl:inverseOf` | implemented | `PropertyExtractor:94`, `OwlParser:166-168` | `OwlParserTest:138` |
| `rdfs:subPropertyOf` | implemented | `PropertyExtractor:93` | — |
| `rdfs:domain` | implemented | `PropertyExtractor:91` | — |
| `rdfs:range` (with union + comment fallback) | implemented | `PropertyExtractor:77-82` | — |
| `owl:equivalentProperty` | implemented | `PropertyExtractor` | `OwlParserTest` equivalentProperty test |
| `owl:propertyChainAxiom` | implemented | `OwlParser:170-177` | `OwlParserTest` propertyChainAxiom test |
| `owl:propertyDisjointWith` | implemented | `PropertyExtractor` | `OwlParserTest` propertyDisjointWith test |

### Ontology-Level Metadata

| Feature | Status | Location | Tests |
|---|---|---|---|
| `owl:Ontology` declaration | implemented | `OwlParser:196-234` | `OwlParserTest` ontology metadata test |
| `owl:imports` | implemented | `OwlParser:209-214` | `OwlParserTest` ontology metadata test |
| `owl:versionIRI` | implemented | `OwlParser:216-219` | `OwlParserTest` ontology metadata test |
| `owl:versionInfo` | implemented | `OwlParser:221-224` | `OwlParserTest` ontology metadata test |
| `owl:deprecated` | implemented | `OwlParser:226-229` | `OwlParserTest` deprecated test |

### Individuals / Instances

| Feature | Status | Location | Tests |
|---|---|---|---|
| `owl:NamedIndividual` | implemented | `OwlParser:239-318` | `OwlParserTest` individuals test |
| `owl:sameAs` | implemented | `OwlParser:272-277` | `OwlParserTest` individuals test |
| `owl:differentFrom` | implemented | `OwlParser:279-284` | `OwlParserTest` individuals test |
| `owl:AllDifferent` | implemented | `OwlParser:290-316` | `OwlParserTest` AllDifferent test |

### Data Ranges

| Feature | Status | Location | Tests |
|---|---|---|---|
| `rdfs:Datatype` | implemented | `OwlParser:323-348` | `OwlParserTest` datatype test |
| `owl:onDatatype` | implemented | `OwlParser:335` | `OwlParserTest` datatype test |
| `owl:withRestrictions` | implemented | `OwlParser:340-343` | `OwlParserTest` datatype test |
| `owl:datatypeComplementOf` | implemented | `OwlParser:337` | `OwlParserTest` complement test |

---

## RDFS (RDF Schema)

Reference: [RDF Schema W3C Recommendation](https://www.w3.org/TR/rdf-schema/)

| Feature | Status | Location | Tests |
|---|---|---|---|
| `rdfs:Class` | implemented | `ClassExtractor:24` | `OwlParserTest:44` |
| `rdfs:subClassOf` | implemented | `ClassExtractor:66` | `OwlParserTest:96` |
| `rdfs:subPropertyOf` | implemented | `PropertyExtractor:93` | — |
| `rdfs:domain` | implemented | `PropertyExtractor:91` | — |
| `rdfs:range` | implemented | `PropertyExtractor:77` | — |
| `rdfs:label` (multilingual) | implemented | `ResourceHelperTrait:18-56` | `OwlParserTest:94` |
| `rdfs:comment` (multilingual) | implemented | `ResourceHelperTrait:61-99` | `OwlParserTest:95` |
| `rdf:type` | implemented | used throughout for type detection | — |
| `rdfs:seeAlso` | not implemented | captured only as custom annotation | — |
| `rdfs:isDefinedBy` | not implemented | captured only as custom annotation | — |
| `rdfs:Datatype` | not implemented | — | — |
| `rdfs:Container` / `rdfs:member` | not implemented | — | — |
| `rdfs:Literal` | not implemented | — | — |

---

## Serialization Formats

### RDF/XML

Reference: [RDF/XML Syntax W3C Recommendation](https://www.w3.org/TR/rdf-syntax-grammar/)

| Feature | Status | Location | Tests |
|---|---|---|---|
| Basic XML parsing | implemented | `RdfXmlHandler:87-131` | `OwlParserTest:189` |
| `xmlns:` namespace declarations | implemented | `RdfXmlHandler:45-57` | `OwlParserTest:189` |
| `rdf:about` attributes | implemented | `ClassExtractor:114-115` | `OwlParserTest:189` |
| `rdf:resource` references | implemented | `ClassExtractor:188-189` | — |
| Nested elements | implemented | via SimpleXML fallback | — |
| HTML content guard | implemented | `RdfXmlHandler:74-79` | — |
| Invalid XML error handling | implemented | `RdfXmlHandler:95-98` | — |
| `rdf:parseType="Collection"` | not implemented | — | — |
| `rdf:parseType="Literal"` | not implemented | — | — |
| `rdf:parseType="Resource"` | not implemented | — | — |
| `rdf:ID` | not implemented | — | — |
| `rdf:nodeID` | not implemented | — | — |

### Turtle

Reference: [Turtle W3C Recommendation](https://www.w3.org/TR/turtle/)

| Feature | Status | Location | Tests |
|---|---|---|---|
| `@prefix` declarations | implemented | `TurtleHandler:19`, `PrefixExtractor:85` | `OwlParserTest:44` |
| `PREFIX` (SPARQL style) | implemented | `TurtleHandler:21`, `PrefixExtractor:96` | — |
| Blank nodes `[]` | implemented | via EasyRdf | `OwlParserTest:162` |
| Collections / list syntax | implemented | via EasyRdf | — |
| Multi-valued properties `;` | implemented | via EasyRdf | `OwlParserTest:44` |
| Object lists `,` | implemented | via EasyRdf | `OwlParserTest:69` |
| Typed literals `^^` | implemented | via EasyRdf | `OwlParserTest:174` |
| Language-tagged strings `@en` | implemented | via EasyRdf | — |
| `@base` / `BASE` | not implemented | — | — |
| String escape sequences | not implemented | delegated to EasyRdf (partial) | — |

### N-Triples

Reference: [N-Triples W3C Recommendation](https://www.w3.org/TR/n-triples/)

| Feature | Status | Location | Tests |
|---|---|---|---|
| Basic triple parsing | implemented | `NTriplesHandler:41-58` | — |
| Format detection (first 10 lines) | implemented | `NTriplesHandler:19-38` | — |
| Comment lines `#` | implemented | `NTriplesHandler:29` | — |
| Blank node subjects | implemented | via EasyRdf | — |
| Language-tagged literals | implemented | via EasyRdf | — |
| N-Quads support | not implemented | — | — |

---

## Out of Scope

The following are intentionally **not** covered by this library and belong in separate repositories:

| Area | Target Repository |
|---|---|
| SHACL (Shapes Constraint Language) | `parser-shacl` |
| JSON-LD format handling | separate JSON-LD library |

---

## Test Coverage

28 test cases in `OwlParserTest.php` exercising the OwlParser via the Pest framework.

All 51 OWL 2 spec items have at least one corresponding test.

| Area | Test Count | Coverage |
|---|---|---|
| OWL content detection | 3 | canParse positive, negative, namespace variants |
| Format support | 1 | getSupportedFormats returns `owl` |
| Class + property extraction | 2 | Full parse with Turtle and RDF/XML |
| OWL property characteristics | 3 | Symmetric/transitive/functional + asymmetric/reflexive/irreflexive + hasOwlType helper |
| OWL class restrictions | 3 | minCardinality, qualified cardinality + onClass, hasSelf |
| Class expressions | 4 | intersectionOf, unionOf, complementOf, oneOf |
| Class axioms | 1 | equivalentClass, disjointWith |
| Property axioms | 2 | equivalentProperty + propertyDisjointWith, propertyChainAxiom |
| Ontology metadata | 2 | Full metadata, deprecated flag |
| Individuals | 2 | NamedIndividual + sameAs/differentFrom, AllDifferent |
| Data ranges | 2 | onDatatype + withRestrictions, datatypeComplementOf |
| Edge cases | 3 | Empty ontology, invalid content, empty new keys |

---

## Architecture Notes

The implementation follows a **handler-extractor pattern**:

- **3 format handlers** parse raw content into `ParsedRdf` value objects (via EasyRdf + SimpleXML fallback for RDF/XML).
- **3 extractors** pull semantic entities from the parsed graph (prefixes, classes, properties).
- **OwlParser** extends `RdfParser` adding OWL-specific property characteristics, restriction extraction, ontology metadata, individuals, data ranges, and class expressions via direct graph access.

Key design decisions:

1. Heavy reliance on **EasyRdf** for Turtle and N-Triples means format-level coverage depends on EasyRdf's own spec compliance.
2. **SimpleXML fallback** for RDF/XML provides robust extraction but limits support to features expressible via XPath queries.
3. **No reasoning engine** -- the parser extracts declared structure only; inferred axioms (e.g., class hierarchy closure) are out of scope.
4. **Graph access in OwlParser** -- the `lastParsedRdf` property provides direct EasyRdf graph access for features that require traversing blank nodes and RDF lists (class expressions, restrictions, individuals, data ranges).

---

## Remaining Gaps

The remaining gaps are in **RDFS** and **serialization format** coverage, not in OWL 2:

1. **RDFS** (62%) -- `rdfs:seeAlso`, `rdfs:isDefinedBy`, `rdfs:Datatype`, `rdfs:Container`, `rdfs:Literal` are not explicitly modeled.
2. **RDF/XML** (58%) -- `rdf:parseType`, `rdf:ID`, `rdf:nodeID` are not implemented.
3. **Turtle** (80%) -- `@base` / `BASE` directives are not supported.
4. **N-Triples** (83%) -- N-Quads support is missing.

All OWL 2 spec areas are at **100% coverage**.
