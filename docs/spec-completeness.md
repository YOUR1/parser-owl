# Spec Completeness

> Assessment of parser-owl implementation coverage against W3C OWL 2 and related specifications.
> Last updated: 2026-02-20 (Epic 13 complete)

## Scope

This library focuses exclusively on **OWL (Web Ontology Language)** parsing. SHACL and JSON-LD
are handled by separate repositories (`parser-shacl`, etc.) and are intentionally out of scope here.

Supported serialization formats: **RDF/XML**, **Turtle**, **N-Triples** (inherited from `parser-rdf`).

## Summary

| Spec Area | Implemented | Total | Coverage |
|---|---|---|---|
| OWL 2 — Class Expressions | 15 | 15 | 100% |
| OWL 2 — Data Property Restrictions | 4 | 6 | 67% |
| OWL 2 — Class Axioms | 4 | 4 | 100% |
| OWL 2 — Object Property Axioms | 15 | 15 | 100% |
| OWL 2 — Data Property Axioms | 6 | 6 | 100% |
| OWL 2 — Data Ranges | 7 | 7 | 100% |
| OWL 2 — Ontology Metadata | 8 | 9 | 89% |
| OWL 2 — Individuals & Assertions | 10 | 10 | 100% |
| OWL 2 — Annotations | 3 | 6 | 50% |
| OWL 2 — Keys & Datatype Definitions | 2 | 2 | 100% |
| RDFS | 9 | 13 | 69% |
| Serialization — RDF/XML | 7 | 12 | 58% |
| Serialization — Turtle | 8 | 10 | 80% |
| Serialization — N-Triples | 5 | 6 | 83% |
| **Overall OWL 2 (weighted)** | **74** | **80** | **~93%** |

---

## OWL 2 Web Ontology Language

Reference: [OWL 2 Structural Specification](https://www.w3.org/TR/owl2-syntax/), [OWL 2 RDF Mapping](https://www.w3.org/TR/owl2-mapping-to-rdf/)

### Class Expressions — Object Property Side (15/15)

| Feature | Status | Location | Tests |
|---|---|---|---|
| `owl:Class` detection | implemented | inherited from `parser-rdf` ClassExtractor | Unit/OwlParserTest, Conformance |
| `owl:intersectionOf` | implemented | `OwlClassEnhancer` | Unit/OwlClassEnhancerTest, Conformance/ClassExpression |
| `owl:unionOf` | implemented | `OwlClassEnhancer` | Unit/OwlClassEnhancerTest, Conformance/ClassExpression |
| `owl:complementOf` | implemented | `OwlClassEnhancer` | Unit/OwlClassEnhancerTest, Conformance/ClassExpression |
| `owl:oneOf` (enumerated classes) | implemented | `OwlClassEnhancer` | Unit/OwlClassEnhancerTest, Conformance/ClassExpression |
| `owl:someValuesFrom` | implemented | `OwlClassEnhancer` | Unit/OwlClassEnhancerTest, Conformance/Restriction |
| `owl:allValuesFrom` | implemented | `OwlClassEnhancer` | Unit/OwlClassEnhancerTest, Conformance/Restriction |
| `owl:hasValue` (literal + URI) | implemented | `OwlClassEnhancer` | Unit/OwlClassEnhancerTest, Conformance/Restriction |
| `owl:hasSelf` | implemented | `OwlClassEnhancer` | Unit/OwlClassEnhancerTest, Conformance/Restriction |
| `owl:cardinality` (unqualified) | implemented | `OwlClassEnhancer` | Unit/OwlClassEnhancerTest, Conformance/Restriction |
| `owl:minCardinality` (unqualified) | implemented | `OwlClassEnhancer` | Unit/OwlClassEnhancerTest, Conformance/Restriction |
| `owl:maxCardinality` (unqualified) | implemented | `OwlClassEnhancer` | Unit/OwlClassEnhancerTest, Conformance/Restriction |
| `owl:qualifiedCardinality` + `owl:onClass` | implemented | `OwlClassEnhancer` | Unit/OwlClassEnhancerTest, Conformance/Restriction |
| `owl:minQualifiedCardinality` + `owl:onClass` | implemented | `OwlClassEnhancer` | Unit/OwlClassEnhancerTest |
| `owl:maxQualifiedCardinality` + `owl:onClass` | implemented | `OwlClassEnhancer` | Unit/OwlClassEnhancerTest |

### Class Expressions — Data Property Side (4/6)

These use the same restriction mechanism as object property restrictions. The parser does not distinguish between object and data property restrictions -- the same `owl:Restriction` + `owl:onProperty` pattern is matched for both.

| Feature | Status | Location | Tests |
|---|---|---|---|
| `DataSomeValuesFrom` | implemented | `OwlClassEnhancer` (via `owl:someValuesFrom`) | -- |
| `DataAllValuesFrom` | implemented | `OwlClassEnhancer` (via `owl:allValuesFrom`) | -- |
| `DataHasValue` | implemented | `OwlClassEnhancer` (via `owl:hasValue`) | -- |
| Qualified data cardinality + `owl:onDataRange` | implemented | `OwlClassEnhancer`, `OwlPropertyEnhancer` | Unit/OwlPropertyEnhancerTest (Resolved in Story 13.2) |
| `owl:onProperties` (n-ary data restrictions) | **not implemented** | deferred -- low usage, complex RDF pattern | -- |
| Unqualified data cardinality | implemented | shares code with object cardinality | -- |

### Class Axioms (4/4)

| Feature | Status | Location | Tests |
|---|---|---|---|
| `rdfs:subClassOf` | implemented | inherited from `parser-rdf`; blank node filtering in `OwlClassEnhancer` | Conformance/ClassExpression |
| `owl:equivalentClass` (named + bnode class expressions) | implemented | `OwlClassEnhancer` | Unit/OwlClassEnhancerTest, Conformance/ClassExpression |
| `owl:disjointWith` + `owl:AllDisjointClasses` | implemented | `OwlClassEnhancer` | Unit/OwlClassEnhancerTest, Conformance/ClassExpression |
| `owl:disjointUnionOf` | implemented | `OwlClassEnhancer::extractDisjointUnionOf()` | Unit/OwlClassEnhancerTest (Resolved in Story 13.1) |

### Object Property Axioms (15/15)

| Feature | Status | Location | Tests |
|---|---|---|---|
| `owl:ObjectProperty` type detection | implemented | `OwlPropertyEnhancer` | Unit/OwlPropertyEnhancerTest, Conformance/Property |
| `rdfs:subPropertyOf` | implemented | inherited from `parser-rdf` | -- |
| `owl:propertyChainAxiom` (RDF list traversal) | implemented | `OwlPropertyEnhancer` | Unit/OwlPropertyEnhancerTest, Conformance/Property |
| `owl:equivalentProperty` | implemented | `OwlPropertyEnhancer` | Unit/OwlPropertyEnhancerTest, Conformance/Property |
| `owl:propertyDisjointWith` | implemented | `OwlPropertyEnhancer` | Unit/OwlPropertyEnhancerTest, Conformance/Property |
| `owl:inverseOf` | implemented | `OwlPropertyEnhancer` | Unit/OwlPropertyEnhancerTest, Conformance/Property |
| `rdfs:domain` | implemented | inherited from `parser-rdf` | -- |
| `rdfs:range` | implemented | inherited from `parser-rdf` | -- |
| `owl:FunctionalProperty` | implemented | `OwlPropertyEnhancer` | Unit/OwlPropertyEnhancerTest, Conformance/Property |
| `owl:InverseFunctionalProperty` | implemented | `OwlPropertyEnhancer` | Unit/OwlPropertyEnhancerTest, Conformance/Property |
| `owl:TransitiveProperty` | implemented | `OwlPropertyEnhancer` | Unit/OwlPropertyEnhancerTest, Conformance/Property |
| `owl:SymmetricProperty` | implemented | `OwlPropertyEnhancer` | Unit/OwlPropertyEnhancerTest, Conformance/Property |
| `owl:AsymmetricProperty` | implemented | `OwlPropertyEnhancer` | Unit/OwlPropertyEnhancerTest, Conformance/Property |
| `owl:ReflexiveProperty` | implemented | `OwlPropertyEnhancer` | Unit/OwlPropertyEnhancerTest, Conformance/Property |
| `owl:IrreflexiveProperty` | implemented | `OwlPropertyEnhancer` | Unit/OwlPropertyEnhancerTest, Conformance/Property |
| `owl:AllDisjointProperties` | implemented | `OwlPropertyEnhancer::processAllDisjointProperties()` | Unit/OwlPropertyEnhancerTest (Resolved in Story 13.2) |

### Data Property Axioms (6/6)

These share implementation with object property axioms -- the same code handles both since the RDF predicates are identical.

| Feature | Status | Location | Tests |
|---|---|---|---|
| `owl:DatatypeProperty` type detection | implemented | `OwlPropertyEnhancer` | Unit/OwlPropertyEnhancerTest |
| `rdfs:subPropertyOf` | implemented | inherited from `parser-rdf` | -- |
| `owl:equivalentProperty` | implemented | `OwlPropertyEnhancer` | -- |
| `owl:propertyDisjointWith` | implemented | `OwlPropertyEnhancer` | -- |
| `rdfs:domain` / `rdfs:range` | implemented | inherited from `parser-rdf` | -- |
| `owl:FunctionalProperty` (data) | implemented | `OwlPropertyEnhancer` | -- |

### Ontology-Level Metadata (8/9)

| Feature | Status | Location | Tests |
|---|---|---|---|
| `owl:Ontology` declaration | implemented | `OntologyMetadataExtractor` | Unit/OntologyMetadataExtractorTest, Conformance/OntologyStructure |
| `owl:imports` (multiple) | implemented | `OntologyMetadataExtractor` | Unit/OntologyMetadataExtractorTest, Conformance/OntologyStructure |
| `owl:versionIRI` | implemented | `OntologyMetadataExtractor` | Unit/OntologyMetadataExtractorTest, Conformance/OntologyStructure |
| `owl:versionInfo` | implemented | `OntologyMetadataExtractor` | Unit/OntologyMetadataExtractorTest, Conformance/OntologyStructure |
| `owl:deprecated` | implemented | `OntologyMetadataExtractor` | Unit/OntologyMetadataExtractorTest, Conformance/OntologyStructure |
| `owl:priorVersion` | implemented | `OntologyMetadataExtractor` | Unit/OntologyMetadataExtractorTest (Resolved in Story 13.6) |
| `owl:backwardCompatibleWith` | implemented | `OntologyMetadataExtractor` | Unit/OntologyMetadataExtractorTest (Resolved in Story 13.6) |
| `owl:incompatibleWith` | implemented | `OntologyMetadataExtractor` | Unit/OntologyMetadataExtractorTest (Resolved in Story 13.6) |
| Ontology annotations (arbitrary) | **not implemented** | only specific predicates above are extracted; deferred -- rarely needed for parsing | -- |

### Individuals & Assertions (10/10)

| Feature | Status | Location | Tests |
|---|---|---|---|
| `owl:NamedIndividual` (URI, types, label) | implemented | `IndividualExtractor` | Unit/IndividualExtractorTest, Conformance/Individual |
| `owl:sameAs` | implemented | `IndividualExtractor` | Unit/IndividualExtractorTest, Conformance/Individual |
| `owl:differentFrom` | implemented | `IndividualExtractor` | Unit/IndividualExtractorTest, Conformance/Individual |
| `owl:AllDifferent` + `owl:distinctMembers` | implemented | `IndividualExtractor` | Unit/IndividualExtractorTest, Conformance/Individual |
| `ClassAssertion` (rdf:type on individuals) | implemented | `IndividualExtractor` (types extracted for NamedIndividual) | Unit/IndividualExtractorTest |
| Anonymous individuals (blank nodes) | implemented | `IndividualExtractor` (via `includeSkolemizedBlankNodes` option) | Unit/IndividualExtractorTest (Resolved in Story 13.4) |
| `ObjectPropertyAssertion` | implemented | `IndividualExtractor::extractPropertyAssertions()` | Unit/IndividualExtractorTest (Resolved in Story 13.3) |
| `DataPropertyAssertion` | implemented | `IndividualExtractor::extractPropertyAssertions()` | Unit/IndividualExtractorTest (Resolved in Story 13.3) |
| `NegativeObjectPropertyAssertion` | implemented | `IndividualExtractor::processNegativeAssertions()` | Unit/IndividualExtractorTest (Resolved in Story 13.3) |
| `NegativeDataPropertyAssertion` | implemented | `IndividualExtractor::processNegativeAssertions()` | Unit/IndividualExtractorTest (Resolved in Story 13.3) |

### Data Ranges (7/7)

| Feature | Status | Location | Tests |
|---|---|---|---|
| `rdfs:Datatype` detection | implemented | `DataRangeExtractor` | Unit/DataRangeExtractorTest, Conformance/DataRange |
| `owl:onDatatype` | implemented | `DataRangeExtractor` | Unit/DataRangeExtractorTest, Conformance/DataRange |
| `owl:withRestrictions` (9 facets incl. rdf:langRange) | implemented | `DataRangeExtractor` | Unit/DataRangeExtractorTest, Conformance/DataRange |
| `owl:datatypeComplementOf` | implemented | `DataRangeExtractor` | Unit/DataRangeExtractorTest, Conformance/DataRange |
| `DataIntersectionOf` (`owl:intersectionOf` on datatypes) | implemented | `DataRangeExtractor` | Unit/DataRangeExtractorTest (Resolved in Story 13.5) |
| `DataUnionOf` (`owl:unionOf` on datatypes) | implemented | `DataRangeExtractor` | Unit/DataRangeExtractorTest (Resolved in Story 13.5) |
| `DataOneOf` (`owl:oneOf` on datatypes with literals) | implemented | `DataRangeExtractor` | Unit/DataRangeExtractorTest (Resolved in Story 13.5) |

#### Supported Constraining Facets

All 8 XSD facets defined in OWL 2 are supported, plus `rdf:langRange`:

| Facet | Status |
|---|---|
| `xsd:minInclusive` | implemented |
| `xsd:maxInclusive` | implemented |
| `xsd:minExclusive` | implemented |
| `xsd:maxExclusive` | implemented |
| `xsd:pattern` | implemented |
| `xsd:length` | implemented |
| `xsd:minLength` | implemented |
| `xsd:maxLength` | implemented |
| `rdf:langRange` | implemented (Resolved in Story 13.5) |

### Annotations (3/6)

| Feature | Status | Location | Tests |
|---|---|---|---|
| `owl:AnnotationProperty` type detection | implemented | `OwlPropertyEnhancer` | Unit/OwlPropertyEnhancerTest |
| `AnnotationAssertion` (`rdfs:label`, `rdfs:comment`) | implemented | inherited from `parser-rdf` ResourceHelperTrait | Unit/OwlParserTest |
| Axiom annotations (`owl:Axiom` reification) | implemented | `OntologyMetadataExtractor::extractAxiomAnnotations()` | Unit/OntologyMetadataExtractorTest (Resolved in Story 13.6) |
| Annotation on annotations (`owl:Annotation` reification) | **not implemented** | deferred -- rarely used in practice | -- |
| `SubAnnotationPropertyOf` | **not implemented** | deferred -- rarely used in practice; annotation property hierarchy not needed for parsing | -- |
| `AnnotationPropertyDomain` / `AnnotationPropertyRange` | **not implemented** | deferred -- rarely used in practice; consumers can inspect rdfs:domain/rdfs:range | -- |

### Keys & Datatype Definitions (2/2)

| Feature | Status | Location | Tests |
|---|---|---|---|
| `owl:hasKey` | implemented | `OwlClassEnhancer::extractHasKey()` | Unit/OwlClassEnhancerTest (Resolved in Story 13.1) |
| `DatatypeDefinition` (`owl:equivalentClass` on datatypes) | implemented | `DataRangeExtractor` | Unit/DataRangeExtractorTest (Resolved in Story 13.5) |

### Global Restrictions

In addition to per-class constraints, the parser extracts all `owl:Restriction` resources globally:

| Feature | Status | Location | Tests |
|---|---|---|---|
| Global `owl:Restriction` extraction | implemented | `OwlPropertyEnhancer` | Unit/OwlPropertyEnhancerTest, Conformance/Restriction |
| All cardinality/value/self fields | implemented | `OwlPropertyEnhancer` | Conformance/Restriction |
| `owl:onDataRange` (qualified data range) | implemented | `OwlPropertyEnhancer`, `OwlClassEnhancer` | Unit/OwlPropertyEnhancerTest (Resolved in Story 13.2) |

---

## RDFS (RDF Schema) (9/13)

Reference: [RDF Schema W3C Recommendation](https://www.w3.org/TR/rdf-schema/)

| Feature | Status | Location | Tests |
|---|---|---|---|
| `rdfs:Class` | implemented | inherited from `parser-rdf` ClassExtractor | Unit/OwlParserTest |
| `rdfs:subClassOf` | implemented | inherited; blank node filtering in `OwlClassEnhancer` | Conformance/ClassExpression |
| `rdfs:subPropertyOf` | implemented | inherited from `parser-rdf` PropertyExtractor | -- |
| `rdfs:domain` | implemented | inherited from `parser-rdf` PropertyExtractor | -- |
| `rdfs:range` | implemented | inherited from `parser-rdf` PropertyExtractor | -- |
| `rdfs:label` (multilingual) | implemented | inherited from `parser-rdf` ResourceHelperTrait | Unit/OwlParserTest |
| `rdfs:comment` (multilingual) | implemented | inherited from `parser-rdf` ResourceHelperTrait | Unit/OwlParserTest |
| `rdf:type` | implemented | used throughout for type detection | -- |
| `rdfs:Datatype` | implemented | `DataRangeExtractor` | Unit/DataRangeExtractorTest |
| `rdfs:seeAlso` | **not implemented** | captured only as custom annotation | -- |
| `rdfs:isDefinedBy` | **not implemented** | captured only as custom annotation | -- |
| `rdfs:Container` / `rdfs:member` | **not implemented** | -- | -- |
| `rdfs:Literal` | **not implemented** | -- | -- |

---

## Serialization Formats

### RDF/XML (7/12)

Reference: [RDF/XML Syntax W3C Recommendation](https://www.w3.org/TR/rdf-syntax-grammar/)

| Feature | Status | Location | Tests |
|---|---|---|---|
| Basic XML parsing | implemented | inherited from `parser-rdf` RdfXmlHandler | Unit/OwlParserTest |
| `xmlns:` namespace declarations | implemented | inherited from `parser-rdf` RdfXmlHandler | Unit/OwlParserTest |
| `rdf:about` attributes | implemented | inherited from `parser-rdf` ClassExtractor | Unit/OwlParserTest |
| `rdf:resource` references | implemented | inherited from `parser-rdf` ClassExtractor | -- |
| Nested elements | implemented | via SimpleXML fallback | -- |
| HTML content guard | implemented | inherited from `parser-rdf` RdfXmlHandler | -- |
| Invalid XML error handling | implemented | inherited from `parser-rdf` RdfXmlHandler | -- |
| `rdf:parseType="Collection"` | **not implemented** | handler-delegated (Epic 10) | -- |
| `rdf:parseType="Literal"` | **not implemented** | handler-delegated (Epic 10) | -- |
| `rdf:parseType="Resource"` | **not implemented** | handler-delegated (Epic 10) | -- |
| `rdf:ID` | **not implemented** | handler-delegated (Epic 10) | -- |
| `rdf:nodeID` | **not implemented** | handler-delegated (Epic 10) | -- |

### Turtle (8/10)

Reference: [Turtle W3C Recommendation](https://www.w3.org/TR/turtle/)

| Feature | Status | Location | Tests |
|---|---|---|---|
| `@prefix` declarations | implemented | inherited from `parser-rdf` TurtleHandler/PrefixExtractor | Unit/OwlParserTest |
| `PREFIX` (SPARQL style) | implemented | inherited from `parser-rdf` TurtleHandler/PrefixExtractor | -- |
| Blank nodes `[]` | implemented | via EasyRdf | Conformance/Restriction |
| Collections / list syntax `()` | implemented | via EasyRdf | -- |
| Multi-valued properties `;` | implemented | via EasyRdf | Unit/OwlParserTest |
| Object lists `,` | implemented | via EasyRdf | Unit/OwlParserTest |
| Typed literals `^^` | implemented | via EasyRdf | Conformance/Restriction |
| Language-tagged strings `@en` | implemented | via EasyRdf | -- |
| `@base` / `BASE` | **not implemented** | handler-delegated (Epic 9) | -- |
| String escape sequences | **not implemented** | delegated to EasyRdf (partial) | -- |

### N-Triples (5/6)

Reference: [N-Triples W3C Recommendation](https://www.w3.org/TR/n-triples/)

| Feature | Status | Location | Tests |
|---|---|---|---|
| Basic triple parsing | implemented | inherited from `parser-rdf` NTriplesHandler | -- |
| Format detection (first 10 lines) | implemented | inherited from `parser-rdf` NTriplesHandler | -- |
| Comment lines `#` | implemented | inherited from `parser-rdf` NTriplesHandler | -- |
| Blank node subjects | implemented | via EasyRdf | -- |
| Language-tagged literals | implemented | via EasyRdf | -- |
| N-Quads support | **not implemented** | -- | -- |

---

## Out of Scope

The following are intentionally **not** covered by this library and belong in separate repositories:

| Area | Target Repository |
|---|---|
| SHACL (Shapes Constraint Language) | `parser-shacl` |
| JSON-LD format handling | separate JSON-LD library |
| OWL reasoning / inference | out of scope (parser extracts declared structure only) |

---

## Test Coverage

**321 tests passed** (670 assertions) across a three-tier testing strategy:

### Unit Tests (178 tests across 7 files)

| File | Tests | Coverage |
|---|---|---|
| `Unit/OwlParserTest` | 18 | Parser API, canParse, getSupportedFormats, error handling |
| `Unit/AliasesTest` | 13 | Backward compatibility alias, deprecation warnings |
| `Unit/Extractors/OwlClassEnhancerTest` | 44 | Equivalent classes, disjoint, disjoint union, has key, expressions, constraints |
| `Unit/Extractors/OwlPropertyEnhancerTest` | 29 | Property types, characteristics, relationships, chains, qualified data cardinality, AllDisjointProperties |
| `Unit/Extractors/IndividualExtractorTest` | 30 | Individuals, sameAs, differentFrom, AllDifferent, property assertions, negative assertions, anonymous individuals |
| `Unit/Extractors/DataRangeExtractorTest` | 19 | Datatypes, facets, complement, DataIntersectionOf, DataUnionOf, DataOneOf, DatatypeDefinition, rdf:langRange |
| `Unit/Extractors/OntologyMetadataExtractorTest` | 25 | Ontology, imports, version, deprecated, priorVersion, compatibility IRIs, axiom annotations |

### Conformance Tests (42 tests across 7 files)

Validate parsing against the W3C OWL 2 specification using dedicated Turtle fixture files.

| File | Tests | Spec Refs |
|---|---|---|
| `Conformance/OwlClassExpressionConformanceTest` | 8 | S8.1.1-S8.1.4, S9.1, S9.1.3 |
| `Conformance/OwlPropertyConformanceTest` | 8 | S8.2, S8.3, S9.2 |
| `Conformance/OwlRestrictionConformanceTest` | 9 | S8.4-S8.5 |
| `Conformance/OwlIndividualConformanceTest` | 6 | S9.5, S9.6.2 |
| `Conformance/OwlDataRangeConformanceTest` | 3 | S7 |
| `Conformance/OwlOntologyStructureConformanceTest` | 6 | S3 |
| `Conformance/OwlPipelineIntegrationTest` | 3 | Full pipeline integration |

### Characterization Tests (97 tests across 6 files)

Lock in current behavior of the refactored architecture against the old monolithic parser. Includes 23 skipped tests that document known behavioral differences.

---

## Architecture Notes

The implementation follows a **handler-extractor-enhancer pattern**:

- **Format handlers** (inherited from `parser-rdf`) parse raw content into `ParsedRdf` value objects via EasyRdf + SimpleXML fallback for RDF/XML.
- **Base extractors** (inherited from `parser-rdf`) pull prefixes, classes, and properties from the parsed graph.
- **5 OWL enhancers/extractors** add OWL-specific post-processing:

| Component | File | Responsibility |
|---|---|---|
| `OwlParser` | `src/OwlParser.php` | Orchestrates OWL enhancement pipeline |
| `OwlClassEnhancer` | `src/Extractors/OwlClassEnhancer.php` | Equivalent classes, disjoint, disjoint union, has key, expressions, constraints |
| `OwlPropertyEnhancer` | `src/Extractors/OwlPropertyEnhancer.php` | Types, characteristics, relationships, chains, AllDisjointProperties, global restrictions, qualified data cardinality |
| `IndividualExtractor` | `src/Extractors/IndividualExtractor.php` | Named + anonymous individuals, sameAs, differentFrom, AllDifferent, property assertions, negative assertions |
| `DataRangeExtractor` | `src/Extractors/DataRangeExtractor.php` | Datatypes, facet restrictions, complement, DataIntersectionOf, DataUnionOf, DataOneOf, DatatypeDefinition |
| `OntologyMetadataExtractor` | `src/Extractors/OntologyMetadataExtractor.php` | Ontology IRI, imports, version, deprecated, priorVersion, compatibility IRIs, axiom annotations |

Key design decisions:

1. Heavy reliance on **EasyRdf** for Turtle and N-Triples means format-level coverage depends on EasyRdf's own spec compliance.
2. **SimpleXML fallback** for RDF/XML provides robust extraction but limits support to features expressible via XPath queries.
3. **No reasoning engine** -- the parser extracts declared structure only; inferred axioms (e.g., class hierarchy closure) are out of scope.
4. **RDF list traversal** is implemented independently in extractors via `rdf:first`/`rdf:rest` chain walking.

---

## Remaining Gaps

### Parser-level (6 remaining, all low priority)

| Gap | Spec Ref | Rationale for Deferral |
|---|---|---|
| `owl:onProperties` (n-ary data restrictions) | S8.4 | Rarely used; complex multi-property RDF pattern |
| Ontology annotations (arbitrary) | S3 | Only specific predicates extracted; arbitrary annotations rarely needed for structural parsing |
| Annotation on annotations (`owl:Annotation` reification) | S10 | Rarely used in practice |
| `SubAnnotationPropertyOf` | S10 | Annotation property hierarchy not needed for parsing; rarely used in practice |
| `AnnotationPropertyDomain` / `AnnotationPropertyRange` | S10 | Consumers can inspect rdfs:domain/rdfs:range directly; rarely used |
| Unqualified data cardinality already counted above | -- | Already implemented (shared code with object cardinality) |

### Handler-delegated (serialization-level gaps)

These gaps are in the serialization format handlers and are tracked in separate epics:

| Gap | Format | Epic |
|---|---|---|
| `rdf:parseType` (Collection/Literal/Resource) | RDF/XML | Epic 10 |
| `rdf:ID` / `rdf:nodeID` | RDF/XML | Epic 10 |
| `@base` / `BASE` directives | Turtle | Epic 9 |
| String escape sequences | Turtle | Epic 9 / EasyRdf |
| N-Quads | N-Triples | Epic 12 |

### Not Planned

| Feature | Reason |
|---|---|
| OWL reasoning / entailment | Out of scope -- parser only |
| `owl:Thing` / `owl:Nothing` special handling | Recognized as regular classes; no special semantics needed for parsing |
| Built-in datatype validation | Datatype IRIs are preserved; validation is a consumer concern |
| `rdfs:Container` / `rdfs:member` | Rarely used in OWL ontologies |

---

## Change Log

### Epic 13 (Stories 13.1-13.6) -- 2026-02-20

Before: 57/80 OWL 2 features (71%)
After: 74/80 OWL 2 features (93%)

| Story | Features Added |
|---|---|
| 13.1 | `owl:disjointUnionOf`, `owl:hasKey` |
| 13.2 | `owl:onDataRange` (qualified data cardinality), `owl:AllDisjointProperties` |
| 13.3 | `ObjectPropertyAssertion`, `DataPropertyAssertion`, `NegativeObjectPropertyAssertion`, `NegativeDataPropertyAssertion` |
| 13.4 | Anonymous individuals (blank node skolemization) |
| 13.5 | `DataIntersectionOf`, `DataUnionOf`, `DataOneOf`, `DatatypeDefinition`, `rdf:langRange` facet |
| 13.6 | Axiom annotations (`owl:Axiom` reification), `owl:priorVersion`, `owl:backwardCompatibleWith`, `owl:incompatibleWith` |
