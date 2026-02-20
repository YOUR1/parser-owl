# Spec Completeness

> Assessment of parser-owl implementation coverage against W3C OWL 2 and related specifications.
> Last updated: 2026-02-20

## Scope

This library focuses exclusively on **OWL (Web Ontology Language)** parsing. SHACL and JSON-LD
are handled by separate repositories (`parser-shacl`, etc.) and are intentionally out of scope here.

Supported serialization formats: **RDF/XML**, **Turtle**, **N-Triples** (inherited from `parser-rdf`).

## Summary

| Spec Area | Implemented | Total | Coverage |
|---|---|---|---|
| OWL 2 — Class Expressions | 15 | 15 | 100% |
| OWL 2 — Data Property Restrictions | 3 | 6 | 50% |
| OWL 2 — Class Axioms | 3 | 4 | 75% |
| OWL 2 — Object Property Axioms | 14 | 15 | 93% |
| OWL 2 — Data Property Axioms | 6 | 6 | 100% |
| OWL 2 — Data Ranges | 4 | 7 | 57% |
| OWL 2 — Ontology Metadata | 5 | 9 | 56% |
| OWL 2 — Individuals & Assertions | 5 | 10 | 50% |
| OWL 2 — Annotations | 2 | 6 | 33% |
| OWL 2 — Keys & Datatype Definitions | 0 | 2 | 0% |
| RDFS | 9 | 13 | 69% |
| Serialization — RDF/XML | 7 | 12 | 58% |
| Serialization — Turtle | 8 | 10 | 80% |
| Serialization — N-Triples | 5 | 6 | 83% |
| **Overall OWL 2 (weighted)** | **57** | **80** | **~71%** |

---

## OWL 2 Web Ontology Language

Reference: [OWL 2 Structural Specification](https://www.w3.org/TR/owl2-syntax/), [OWL 2 RDF Mapping](https://www.w3.org/TR/owl2-mapping-to-rdf/)

### Class Expressions — Object Property Side (15/15)

| Feature | Status | Location | Tests |
|---|---|---|---|
| `owl:Class` detection | implemented | inherited from `parser-rdf` ClassExtractor | Unit/OwlParserTest, Conformance |
| `owl:intersectionOf` | implemented | `OwlClassEnhancer:173-176` | Unit/OwlClassEnhancerTest, Conformance/ClassExpression |
| `owl:unionOf` | implemented | `OwlClassEnhancer:178-181` | Unit/OwlClassEnhancerTest, Conformance/ClassExpression |
| `owl:complementOf` | implemented | `OwlClassEnhancer:184-190` | Unit/OwlClassEnhancerTest, Conformance/ClassExpression |
| `owl:oneOf` (enumerated classes) | implemented | `OwlClassEnhancer:193-197` | Unit/OwlClassEnhancerTest, Conformance/ClassExpression |
| `owl:someValuesFrom` | implemented | `OwlClassEnhancer:231` | Unit/OwlClassEnhancerTest, Conformance/Restriction |
| `owl:allValuesFrom` | implemented | `OwlClassEnhancer:230` | Unit/OwlClassEnhancerTest, Conformance/Restriction |
| `owl:hasValue` (literal + URI) | implemented | `OwlClassEnhancer:223, 277-288` | Unit/OwlClassEnhancerTest, Conformance/Restriction |
| `owl:hasSelf` | implemented | `OwlClassEnhancer:237-239` | Unit/OwlClassEnhancerTest, Conformance/Restriction |
| `owl:cardinality` (unqualified) | implemented | `OwlClassEnhancer:224` | Unit/OwlClassEnhancerTest, Conformance/Restriction |
| `owl:minCardinality` (unqualified) | implemented | `OwlClassEnhancer:225` | Unit/OwlClassEnhancerTest, Conformance/Restriction |
| `owl:maxCardinality` (unqualified) | implemented | `OwlClassEnhancer:226` | Unit/OwlClassEnhancerTest, Conformance/Restriction |
| `owl:qualifiedCardinality` + `owl:onClass` | implemented | `OwlClassEnhancer:227, 232` | Unit/OwlClassEnhancerTest, Conformance/Restriction |
| `owl:minQualifiedCardinality` + `owl:onClass` | implemented | `OwlClassEnhancer:228` | Unit/OwlClassEnhancerTest |
| `owl:maxQualifiedCardinality` + `owl:onClass` | implemented | `OwlClassEnhancer:229` | Unit/OwlClassEnhancerTest |

### Class Expressions — Data Property Side (3/6)

These use the same restriction mechanism as object property restrictions. The parser does not distinguish between object and data property restrictions — the same `owl:Restriction` + `owl:onProperty` pattern is matched for both.

| Feature | Status | Location | Tests |
|---|---|---|---|
| `DataSomeValuesFrom` | implemented | `OwlClassEnhancer:231` (via `owl:someValuesFrom`) | — |
| `DataAllValuesFrom` | implemented | `OwlClassEnhancer:230` (via `owl:allValuesFrom`) | — |
| `DataHasValue` | implemented | `OwlClassEnhancer:223` (via `owl:hasValue`) | — |
| Qualified data cardinality + `owl:onDataRange` | **not implemented** | `owl:onDataRange` is not extracted | — |
| `owl:onProperties` (n-ary data restrictions) | **not implemented** | — | — |
| Unqualified data cardinality | implemented | shares code with object cardinality | — |

### Class Axioms (3/4)

| Feature | Status | Location | Tests |
|---|---|---|---|
| `rdfs:subClassOf` | implemented | inherited from `parser-rdf`; blank node filtering in `OwlClassEnhancer:36-43` | Conformance/ClassExpression |
| `owl:equivalentClass` (named + bnode class expressions) | implemented | `OwlClassEnhancer:57-79, 155-162` | Unit/OwlClassEnhancerTest, Conformance/ClassExpression |
| `owl:disjointWith` + `owl:AllDisjointClasses` | implemented | `OwlClassEnhancer:86-103, 111-141` | Unit/OwlClassEnhancerTest, Conformance/ClassExpression |
| `owl:disjointUnionOf` | **not implemented** | — | — |

### Object Property Axioms (14/15)

| Feature | Status | Location | Tests |
|---|---|---|---|
| `owl:ObjectProperty` type detection | implemented | `OwlPropertyEnhancer:22` | Unit/OwlPropertyEnhancerTest, Conformance/Property |
| `rdfs:subPropertyOf` | implemented | inherited from `parser-rdf` | — |
| `owl:propertyChainAxiom` (RDF list traversal) | implemented | `OwlPropertyEnhancer:68-72` | Unit/OwlPropertyEnhancerTest, Conformance/Property |
| `owl:equivalentProperty` | implemented | `OwlPropertyEnhancer:64` | Unit/OwlPropertyEnhancerTest, Conformance/Property |
| `owl:propertyDisjointWith` | implemented | `OwlPropertyEnhancer:65` | Unit/OwlPropertyEnhancerTest, Conformance/Property |
| `owl:inverseOf` | implemented | `OwlPropertyEnhancer:63` | Unit/OwlPropertyEnhancerTest, Conformance/Property |
| `rdfs:domain` | implemented | inherited from `parser-rdf` | — |
| `rdfs:range` | implemented | inherited from `parser-rdf` | — |
| `owl:FunctionalProperty` | implemented | `OwlPropertyEnhancer:29` | Unit/OwlPropertyEnhancerTest, Conformance/Property |
| `owl:InverseFunctionalProperty` | implemented | `OwlPropertyEnhancer:30` | Unit/OwlPropertyEnhancerTest, Conformance/Property |
| `owl:TransitiveProperty` | implemented | `OwlPropertyEnhancer:31` | Unit/OwlPropertyEnhancerTest, Conformance/Property |
| `owl:SymmetricProperty` | implemented | `OwlPropertyEnhancer:32` | Unit/OwlPropertyEnhancerTest, Conformance/Property |
| `owl:AsymmetricProperty` | implemented | `OwlPropertyEnhancer:33` | Unit/OwlPropertyEnhancerTest, Conformance/Property |
| `owl:ReflexiveProperty` | implemented | `OwlPropertyEnhancer:34` | Unit/OwlPropertyEnhancerTest, Conformance/Property |
| `owl:IrreflexiveProperty` | implemented | `OwlPropertyEnhancer:35` | Unit/OwlPropertyEnhancerTest, Conformance/Property |
| `owl:AllDisjointProperties` | **not implemented** | — | — |

### Data Property Axioms (6/6)

These share implementation with object property axioms — the same code handles both since the RDF predicates are identical.

| Feature | Status | Location | Tests |
|---|---|---|---|
| `owl:DatatypeProperty` type detection | implemented | `OwlPropertyEnhancer:23` | Unit/OwlPropertyEnhancerTest |
| `rdfs:subPropertyOf` | implemented | inherited from `parser-rdf` | — |
| `owl:equivalentProperty` | implemented | `OwlPropertyEnhancer:64` | — |
| `owl:propertyDisjointWith` | implemented | `OwlPropertyEnhancer:65` | — |
| `rdfs:domain` / `rdfs:range` | implemented | inherited from `parser-rdf` | — |
| `owl:FunctionalProperty` (data) | implemented | `OwlPropertyEnhancer:29` | — |

### Ontology-Level Metadata (5/9)

| Feature | Status | Location | Tests |
|---|---|---|---|
| `owl:Ontology` declaration | implemented | `OntologyMetadataExtractor:25-26` | Unit/OntologyMetadataExtractorTest, Conformance/OntologyStructure |
| `owl:imports` (multiple) | implemented | `OntologyMetadataExtractor:41-46` | Unit/OntologyMetadataExtractorTest, Conformance/OntologyStructure |
| `owl:versionIRI` | implemented | `OntologyMetadataExtractor:49-52` | Unit/OntologyMetadataExtractorTest, Conformance/OntologyStructure |
| `owl:versionInfo` | implemented | `OntologyMetadataExtractor:55-58` | Unit/OntologyMetadataExtractorTest, Conformance/OntologyStructure |
| `owl:deprecated` | implemented | `OntologyMetadataExtractor:61-63` | Unit/OntologyMetadataExtractorTest, Conformance/OntologyStructure |
| `owl:priorVersion` | **not implemented** | — | — |
| `owl:backwardCompatibleWith` | **not implemented** | — | — |
| `owl:incompatibleWith` | **not implemented** | — | — |
| Ontology annotations (arbitrary) | **not implemented** | only specific predicates above are extracted | — |

### Individuals & Assertions (5/10)

| Feature | Status | Location | Tests |
|---|---|---|---|
| `owl:NamedIndividual` (URI, types, label) | implemented | `IndividualExtractor:25-73` | Unit/IndividualExtractorTest, Conformance/Individual |
| `owl:sameAs` | implemented | `IndividualExtractor:58-63` | Unit/IndividualExtractorTest, Conformance/Individual |
| `owl:differentFrom` | implemented | `IndividualExtractor:65-70` | Unit/IndividualExtractorTest, Conformance/Individual |
| `owl:AllDifferent` + `owl:distinctMembers` | implemented | `IndividualExtractor:76-103` | Unit/IndividualExtractorTest, Conformance/Individual |
| `ClassAssertion` (rdf:type on individuals) | implemented | `IndividualExtractor:34-42` (types extracted for NamedIndividual) | Unit/IndividualExtractorTest |
| Anonymous individuals (blank nodes) | **not implemented** | only named individuals are extracted | — |
| `ObjectPropertyAssertion` | **not implemented** | individual property values not extracted | — |
| `DataPropertyAssertion` | **not implemented** | individual property values not extracted | — |
| `NegativeObjectPropertyAssertion` | **not implemented** | `owl:NegativePropertyAssertion` not recognized | — |
| `NegativeDataPropertyAssertion` | **not implemented** | `owl:NegativePropertyAssertion` not recognized | — |

### Data Ranges (4/7)

| Feature | Status | Location | Tests |
|---|---|---|---|
| `rdfs:Datatype` detection | implemented | `DataRangeExtractor:37` | Unit/DataRangeExtractorTest, Conformance/DataRange |
| `owl:onDatatype` | implemented | `DataRangeExtractor:47` | Unit/DataRangeExtractorTest, Conformance/DataRange |
| `owl:withRestrictions` (8 XSD facets) | implemented | `DataRangeExtractor:16-25, 53-56, 69-105` | Unit/DataRangeExtractorTest, Conformance/DataRange |
| `owl:datatypeComplementOf` | implemented | `DataRangeExtractor:49` | Unit/DataRangeExtractorTest, Conformance/DataRange |
| `DataIntersectionOf` (`owl:intersectionOf` on datatypes) | **not implemented** | — | — |
| `DataUnionOf` (`owl:unionOf` on datatypes) | **not implemented** | — | — |
| `DataOneOf` (`owl:oneOf` on datatypes with literals) | **not implemented** | — | — |

#### Supported Constraining Facets

All 8 XSD facets defined in OWL 2 are supported:

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
| `rdf:langRange` | **not implemented** |

### Annotations (2/6)

| Feature | Status | Location | Tests |
|---|---|---|---|
| `owl:AnnotationProperty` type detection | implemented | `OwlPropertyEnhancer:24` | Unit/OwlPropertyEnhancerTest |
| `AnnotationAssertion` (`rdfs:label`, `rdfs:comment`) | implemented | inherited from `parser-rdf` ResourceHelperTrait | Unit/OwlParserTest |
| Axiom annotations (`owl:Axiom` reification) | **not implemented** | — | — |
| Annotation on annotations (`owl:Annotation` reification) | **not implemented** | — | — |
| `SubAnnotationPropertyOf` | **not implemented** | — | — |
| `AnnotationPropertyDomain` / `AnnotationPropertyRange` | **not implemented** | — | — |

### Keys & Datatype Definitions (0/2)

| Feature | Status | Location | Tests |
|---|---|---|---|
| `owl:hasKey` | **not implemented** | — | — |
| `DatatypeDefinition` (`owl:equivalentClass` on datatypes) | **not implemented** | — | — |

### Global Restrictions

In addition to per-class constraints, the parser extracts all `owl:Restriction` resources globally:

| Feature | Status | Location | Tests |
|---|---|---|---|
| Global `owl:Restriction` extraction | implemented | `OwlPropertyEnhancer:84-133` | Unit/OwlPropertyEnhancerTest, Conformance/Restriction |
| All cardinality/value/self fields | implemented | `OwlPropertyEnhancer:102-122` | Conformance/Restriction |

---

## RDFS (RDF Schema) (9/13)

Reference: [RDF Schema W3C Recommendation](https://www.w3.org/TR/rdf-schema/)

| Feature | Status | Location | Tests |
|---|---|---|---|
| `rdfs:Class` | implemented | inherited from `parser-rdf` ClassExtractor | Unit/OwlParserTest |
| `rdfs:subClassOf` | implemented | inherited; blank node filtering in `OwlClassEnhancer:36-43` | Conformance/ClassExpression |
| `rdfs:subPropertyOf` | implemented | inherited from `parser-rdf` PropertyExtractor | — |
| `rdfs:domain` | implemented | inherited from `parser-rdf` PropertyExtractor | — |
| `rdfs:range` | implemented | inherited from `parser-rdf` PropertyExtractor | — |
| `rdfs:label` (multilingual) | implemented | inherited from `parser-rdf` ResourceHelperTrait | Unit/OwlParserTest |
| `rdfs:comment` (multilingual) | implemented | inherited from `parser-rdf` ResourceHelperTrait | Unit/OwlParserTest |
| `rdf:type` | implemented | used throughout for type detection | — |
| `rdfs:Datatype` | implemented | `DataRangeExtractor:37` | Unit/DataRangeExtractorTest |
| `rdfs:seeAlso` | **not implemented** | captured only as custom annotation | — |
| `rdfs:isDefinedBy` | **not implemented** | captured only as custom annotation | — |
| `rdfs:Container` / `rdfs:member` | **not implemented** | — | — |
| `rdfs:Literal` | **not implemented** | — | — |

---

## Serialization Formats

### RDF/XML (7/12)

Reference: [RDF/XML Syntax W3C Recommendation](https://www.w3.org/TR/rdf-syntax-grammar/)

| Feature | Status | Location | Tests |
|---|---|---|---|
| Basic XML parsing | implemented | inherited from `parser-rdf` RdfXmlHandler | Unit/OwlParserTest |
| `xmlns:` namespace declarations | implemented | inherited from `parser-rdf` RdfXmlHandler | Unit/OwlParserTest |
| `rdf:about` attributes | implemented | inherited from `parser-rdf` ClassExtractor | Unit/OwlParserTest |
| `rdf:resource` references | implemented | inherited from `parser-rdf` ClassExtractor | — |
| Nested elements | implemented | via SimpleXML fallback | — |
| HTML content guard | implemented | inherited from `parser-rdf` RdfXmlHandler | — |
| Invalid XML error handling | implemented | inherited from `parser-rdf` RdfXmlHandler | — |
| `rdf:parseType="Collection"` | **not implemented** | — | — |
| `rdf:parseType="Literal"` | **not implemented** | — | — |
| `rdf:parseType="Resource"` | **not implemented** | — | — |
| `rdf:ID` | **not implemented** | — | — |
| `rdf:nodeID` | **not implemented** | — | — |

### Turtle (8/10)

Reference: [Turtle W3C Recommendation](https://www.w3.org/TR/turtle/)

| Feature | Status | Location | Tests |
|---|---|---|---|
| `@prefix` declarations | implemented | inherited from `parser-rdf` TurtleHandler/PrefixExtractor | Unit/OwlParserTest |
| `PREFIX` (SPARQL style) | implemented | inherited from `parser-rdf` TurtleHandler/PrefixExtractor | — |
| Blank nodes `[]` | implemented | via EasyRdf | Conformance/Restriction |
| Collections / list syntax `()` | implemented | via EasyRdf | — |
| Multi-valued properties `;` | implemented | via EasyRdf | Unit/OwlParserTest |
| Object lists `,` | implemented | via EasyRdf | Unit/OwlParserTest |
| Typed literals `^^` | implemented | via EasyRdf | Conformance/Restriction |
| Language-tagged strings `@en` | implemented | via EasyRdf | — |
| `@base` / `BASE` | **not implemented** | — | — |
| String escape sequences | **not implemented** | delegated to EasyRdf (partial) | — |

### N-Triples (5/6)

Reference: [N-Triples W3C Recommendation](https://www.w3.org/TR/n-triples/)

| Feature | Status | Location | Tests |
|---|---|---|---|
| Basic triple parsing | implemented | inherited from `parser-rdf` NTriplesHandler | — |
| Format detection (first 10 lines) | implemented | inherited from `parser-rdf` NTriplesHandler | — |
| Comment lines `#` | implemented | inherited from `parser-rdf` NTriplesHandler | — |
| Blank node subjects | implemented | via EasyRdf | — |
| Language-tagged literals | implemented | via EasyRdf | — |
| N-Quads support | **not implemented** | — | — |

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

**263 tests passed** (554 assertions) across a three-tier testing strategy:

### Unit Tests (123 tests across 6 files)

| File | Tests | Coverage |
|---|---|---|
| `Unit/OwlParserTest` | 18 | Parser API, canParse, getSupportedFormats, error handling |
| `Unit/AliasesTest` | 13 | Backward compatibility alias, deprecation warnings |
| `Unit/Extractors/OwlClassEnhancerTest` | 32 | Equivalent classes, disjoint, expressions, constraints |
| `Unit/Extractors/OwlPropertyEnhancerTest` | 20 | Property types, characteristics, relationships, chains |
| `Unit/Extractors/IndividualExtractorTest` | 14 | Individuals, sameAs, differentFrom, AllDifferent |
| `Unit/Extractors/DataRangeExtractorTest` | 13 | Datatypes, facets, complement |
| `Unit/Extractors/OntologyMetadataExtractorTest` | 13 | Ontology, imports, version, deprecated |

### Conformance Tests (43 tests across 7 files)

Validate parsing against the W3C OWL 2 specification using dedicated Turtle fixture files.

| File | Tests | Spec Refs |
|---|---|---|
| `Conformance/OwlClassExpressionConformanceTest` | 8 | S8.1.1–S8.1.4, S9.1, S9.1.3 |
| `Conformance/OwlPropertyConformanceTest` | 8 | S8.2, S8.3, S9.2 |
| `Conformance/OwlRestrictionConformanceTest` | 9 | S8.4–S8.5 |
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
| `OwlParser` | `src/OwlParser.php` | Orchestrates OWL enhancement pipeline (lines 60-83) |
| `OwlClassEnhancer` | `src/Extractors/OwlClassEnhancer.php` | Equivalent classes, disjoint, expressions, constraints |
| `OwlPropertyEnhancer` | `src/Extractors/OwlPropertyEnhancer.php` | Types, characteristics, relationships, chains, global restrictions |
| `IndividualExtractor` | `src/Extractors/IndividualExtractor.php` | Named individuals, sameAs, differentFrom, AllDifferent |
| `DataRangeExtractor` | `src/Extractors/DataRangeExtractor.php` | Datatypes, facet restrictions, complement |
| `OntologyMetadataExtractor` | `src/Extractors/OntologyMetadataExtractor.php` | Ontology IRI, imports, version, deprecated |

Key design decisions:

1. Heavy reliance on **EasyRdf** for Turtle and N-Triples means format-level coverage depends on EasyRdf's own spec compliance.
2. **SimpleXML fallback** for RDF/XML provides robust extraction but limits support to features expressible via XPath queries.
3. **No reasoning engine** — the parser extracts declared structure only; inferred axioms (e.g., class hierarchy closure) are out of scope.
4. **RDF list traversal** is implemented independently in three extractors via `rdf:first`/`rdf:rest` chain walking.

---

## Remaining Gaps

### High Priority (commonly used OWL 2 features)

| Gap | Spec Ref | Impact |
|---|---|---|
| `owl:disjointUnionOf` | S9.1.4 | Cannot express class defined as disjoint union |
| `owl:hasKey` | S9.4 | Cannot express key constraints |
| `owl:onDataRange` (qualified data cardinality) | S8.5 | Data restrictions lose qualified range info |
| `owl:AllDisjointProperties` | S9.2.4 | N-ary property disjointness not captured |
| `owl:NegativePropertyAssertion` | S9.6.4–S9.6.5 | Negative assertions on individuals invisible |
| Property assertions on individuals | S9.6.1–S9.6.3 | Individual property values not extracted |
| Anonymous individuals | S9.5 | Blank-node individuals skipped |

### Medium Priority (useful but less common)

| Gap | Spec Ref | Impact |
|---|---|---|
| `DataIntersectionOf` / `DataUnionOf` / `DataOneOf` | S7.2–S7.4 | Complex data ranges not supported |
| `DatatypeDefinition` (`owl:equivalentClass` on datatypes) | S9.4 | Named datatype aliases not recognized |
| `owl:onProperties` (n-ary data restrictions) | S8.4 | Multi-property data restrictions not supported |
| `rdf:langRange` facet | S7.5 | Language range restrictions on strings |
| Axiom annotations (`owl:Axiom` reification) | S10 | Annotations on axioms not captured |
| `owl:priorVersion` / `owl:backwardCompatibleWith` / `owl:incompatibleWith` | S3.4 | Ontology versioning metadata incomplete |

### Low Priority (serialization / edge cases)

| Gap | Spec Ref | Impact |
|---|---|---|
| `rdf:parseType` (Collection/Literal/Resource) | RDF/XML | Limits RDF/XML compatibility |
| `rdf:ID` / `rdf:nodeID` | RDF/XML | Less common identification mechanisms |
| `@base` / `BASE` directives | Turtle | Relative IRI resolution in Turtle |
| N-Quads | N-Triples extension | Named graphs not supported |
| `rdfs:seeAlso` / `rdfs:isDefinedBy` | RDFS | Captured only as opaque custom annotations |
| `SubAnnotationPropertyOf` / annotation domain & range | S10 | Annotation property hierarchy not modeled |

### Not Planned

| Feature | Reason |
|---|---|
| OWL reasoning / entailment | Out of scope — parser only |
| `owl:Thing` / `owl:Nothing` special handling | Recognized as regular classes; no special semantics needed for parsing |
| Built-in datatype validation | Datatype IRIs are preserved; validation is a consumer concern |
| `rdfs:Container` / `rdfs:member` | Rarely used in OWL ontologies |
