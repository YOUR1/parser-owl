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
| OWL 2 — Class Constructs | 11 | 21 | 52% |
| OWL 2 — Property Constructs | 11 | 17 | 65% |
| OWL 2 — Ontology Metadata | 0 | 5 | 0% |
| OWL 2 — Individuals | 0 | 4 | 0% |
| OWL 2 — Data Ranges | 0 | 4 | 0% |
| RDFS | 8 | 13 | 62% |
| Serialization — RDF/XML | 7 | 12 | 58% |
| Serialization — Turtle | 8 | 10 | 80% |
| Serialization — N-Triples | 5 | 6 | 83% |
| Test Coverage | 11 | 20+ | ~55% |
| **Overall (weighted)** | | | **~51%** |

---

## OWL 2 Web Ontology Language

Reference: [OWL 2 W3C Recommendation](https://www.w3.org/TR/owl2-overview/)

### Class Constructs

| Feature | Status | Location | Tests |
|---|---|---|---|
| `owl:Class` detection | implemented | `ClassExtractor:23-26` | `OwlParserTest:44` |
| `rdfs:subClassOf` extraction | implemented | `ClassExtractor:66` | `OwlParserTest:96` |
| `owl:Restriction` (blank node) | implemented | `OwlParser:68-95`, `RdfParser:157-213` | `OwlParserTest:162` |
| `owl:onProperty` | implemented | `RdfParser:176`, `OwlParser:85` | `OwlParserTest:162` |
| `owl:someValuesFrom` | implemented | `RdfParser:182` | — |
| `owl:allValuesFrom` | implemented | `OwlParser:89` | — |
| `owl:hasValue` | implemented | `OwlParser:86` | — |
| `owl:cardinality` | implemented | `OwlParser:87` | — |
| `owl:minCardinality` | implemented | `OwlParser:87` | `OwlParserTest:174` |
| `owl:maxCardinality` | implemented | `OwlParser:87` | — |
| `owl:unionOf` (in restrictions/ranges) | partial | `RdfParser:191`, `PropertyExtractor:271-277` | — |
| `owl:equivalentClass` | not implemented | listed in `ResourceHelperTrait:115` but not surfaced | — |
| `owl:disjointWith` | not implemented | listed in `ResourceHelperTrait:116` but not surfaced | — |
| `owl:intersectionOf` | not implemented | detected in `ResourceHelperTrait:259` but not extracted | — |
| `owl:complementOf` | not implemented | detected in `ResourceHelperTrait:249` but not extracted | — |
| `owl:oneOf` (enumerated classes) | not implemented | — | — |
| `owl:qualifiedCardinality` | not implemented | — | — |
| `owl:minQualifiedCardinality` | not implemented | — | — |
| `owl:maxQualifiedCardinality` | not implemented | — | — |
| `owl:onClass` | not implemented | — | — |
| `owl:hasSelf` | not implemented | — | — |

### Property Constructs

| Feature | Status | Location | Tests |
|---|---|---|---|
| `owl:ObjectProperty` | implemented | `PropertyExtractor:33` | `OwlParserTest:112-113` |
| `owl:DatatypeProperty` | implemented | `PropertyExtractor:32` | `OwlParserTest:107-108` |
| `owl:AnnotationProperty` | implemented | `PropertyExtractor:34` | — |
| `owl:FunctionalProperty` | implemented | `PropertyExtractor:35`, `OwlParser:55` | `OwlParserTest:69-73` |
| `owl:InverseFunctionalProperty` | implemented | `OwlParser:56` | `OwlParserTest:136` |
| `owl:TransitiveProperty` | implemented | `OwlParser:57` | `OwlParserTest:142` |
| `owl:SymmetricProperty` | implemented | `OwlParser:58` | `OwlParserTest:131` |
| `owl:inverseOf` | implemented | `PropertyExtractor:94`, `OwlParser:60-62` | `OwlParserTest:138` |
| `rdfs:subPropertyOf` | implemented | `PropertyExtractor:93` | — |
| `rdfs:domain` | implemented | `PropertyExtractor:91` | — |
| `rdfs:range` (with union + comment fallback) | implemented | `PropertyExtractor:77-82` | — |
| `owl:AsymmetricProperty` | not implemented | — | — |
| `owl:ReflexiveProperty` | not implemented | — | — |
| `owl:IrreflexiveProperty` | not implemented | — | — |
| `owl:equivalentProperty` | not implemented | listed in `ResourceHelperTrait:120` but not surfaced | — |
| `owl:propertyChainAxiom` | not implemented | — | — |
| `owl:propertyDisjointWith` | not implemented | — | — |

### Ontology-Level Metadata

| Feature | Status | Location | Tests |
|---|---|---|---|
| `owl:Ontology` declaration | not implemented | — | — |
| `owl:imports` | not implemented | — | — |
| `owl:versionIRI` | not implemented | — | — |
| `owl:versionInfo` | not implemented | — | — |
| `owl:deprecated` | not implemented | listed in `ResourceHelperTrait:122` but not surfaced | — |

### Individuals / Instances

| Feature | Status | Location | Tests |
|---|---|---|---|
| `owl:NamedIndividual` | not implemented | — | — |
| `owl:sameAs` | not implemented | — | — |
| `owl:differentFrom` | not implemented | — | — |
| `owl:AllDifferent` | not implemented | — | — |

### Data Ranges

| Feature | Status | Location | Tests |
|---|---|---|---|
| `owl:DataRange` | not implemented | — | — |
| `owl:onDatatype` | not implemented | — | — |
| `owl:withRestrictions` | not implemented | — | — |
| `owl:datatypeComplementOf` | not implemented | — | — |

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

11 test cases in `OwlParserTest.php` exercising the OwlParser via the Pest framework.

| Area | Tested | Not Tested |
|---|---|---|
| OWL content detection | `canParse` positive, negative, namespace variants | — |
| Supported formats | `getSupportedFormats` returns `owl` | — |
| Class extraction (Turtle) | URI, label, description, parent_classes | Multilingual labels, custom annotations |
| Property extraction (Turtle) | Datatype, object, functional types | Annotation properties, union domain/range |
| OWL property characteristics | Symmetric, inverse functional, transitive detection | Asymmetric, reflexive, irreflexive |
| OWL restrictions | minCardinality via subClassOf blank node | allValuesFrom, hasValue, exact cardinality |
| RDF/XML format | Class and property extraction | Detailed field verification |
| Empty ontology | Empty classes, properties | — |
| Error handling | Invalid content throws exception | Specific error messages, partial failures |
| Helper methods | `hasOwlType` | `normalizeArray`, `extractOwlRestrictions` |
| **Not covered** | — | N-Triples parsing, PrefixExtractor, format auto-detection, handler priority, range-from-comments, union classes in domain/range, ResourceHelperTrait methods, large file handling, encoding edge cases |

---

## Architecture Notes

The implementation follows a **handler-extractor pattern**:

- **3 format handlers** parse raw content into `ParsedRdf` value objects (via EasyRdf + SimpleXML fallback for RDF/XML).
- **3 extractors** pull semantic entities from the parsed graph (prefixes, classes, properties).
- **OwlParser** extends `RdfParser` adding OWL-specific property characteristics and restriction extraction from metadata.

Key design decisions affecting coverage:

1. Heavy reliance on **EasyRdf** for Turtle and N-Triples means format-level coverage depends on EasyRdf's own spec compliance.
2. **SimpleXML fallback** for RDF/XML provides robust extraction but limits support to features expressible via XPath queries.
3. **No reasoning engine** -- the parser extracts declared structure only; inferred axioms (e.g., class hierarchy closure) are out of scope.
4. **Custom annotations** capture non-standard properties, partially compensating for features not explicitly modeled (e.g., `rdfs:seeAlso`, `skos:prefLabel`).

---

## Highest-Impact Gaps

1. **OWL ontology metadata** (`owl:Ontology`, `owl:imports`, `owl:versionIRI`) -- 0% coverage, needed for multi-ontology workflows.
2. **Individuals** (`owl:NamedIndividual`) -- common in populated ontologies, currently ignored entirely.
3. **Qualified cardinality restrictions** (`owl:qualifiedCardinality`, `owl:onClass`) -- used in precise OWL 2 ontologies.
4. **Class expressions** (`owl:equivalentClass`, `owl:disjointWith`, `owl:intersectionOf`) -- partially detected in helpers but never surfaced in output.
5. **N-Triples and PrefixExtractor** have zero dedicated test cases.
