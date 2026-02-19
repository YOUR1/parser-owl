# OWL 2 Conformance Tests

W3C OWL 2 conformance tests for parser-owl, validating parsing and structure extraction against the official specification.

## Source

Tests are based on:
- [W3C OWL 2 Structural Specification](https://www.w3.org/TR/owl2-syntax/)
- [W3C OWL 2 Primer](https://www.w3.org/TR/owl2-primer/)
- [W3C OWL 2 Test Cases](https://www.w3.org/TR/owl2-test/)

## Scope

parser-owl is a **parser**, not a reasoner. These conformance tests focus on:
1. **Syntax**: Can the parser handle valid OWL 2 documents in Turtle serialization?
2. **Structure extraction**: Does the parser correctly identify and extract OWL constructs?

Out of scope: consistency tests, entailment tests, and profile identification tests (these require a reasoner).

## Test Files

| File | OWL 2 Constructs | Spec Reference |
|---|---|---|
| `OwlClassExpressionConformanceTest.php` | intersectionOf, unionOf, complementOf, oneOf, equivalentClass, disjointWith, AllDisjointClasses, subClassOf | S8.1, S9.1 |
| `OwlPropertyConformanceTest.php` | ObjectProperty, DatatypeProperty, AnnotationProperty, 7 characteristics, inverseOf, equivalentProperty, propertyDisjointWith, propertyChainAxiom | S8.2, S8.3, S9.2 |
| `OwlRestrictionConformanceTest.php` | cardinality, minCardinality, maxCardinality, allValuesFrom, someValuesFrom, qualifiedCardinality, onClass, hasSelf | S8.4, S8.5 |
| `OwlIndividualConformanceTest.php` | NamedIndividual, sameAs, differentFrom, AllDifferent | S9.5, S9.6 |
| `OwlDataRangeConformanceTest.php` | rdfs:Datatype, onDatatype, withRestrictions, datatypeComplementOf | S7 |
| `OwlOntologyStructureConformanceTest.php` | owl:Ontology, versionIRI, versionInfo, imports, deprecated | S3 |
| `OwlPipelineIntegrationTest.php` | Full pipeline integration with all construct types | Multiple |

## Test Results

- **43** conformance tests (41 original + 1 owl:hasValue + 1 owl:hasSelf)
- **0** failed
- **0** skipped
- Known limitations documented below

## Known Limitations

- Parser extracts only named class members from RDF lists (blank node class expressions in lists are skipped)
- Nested class expressions (intersection containing a restriction) extract only named URIs
- Datatype facet restrictions use prefixed facet keys (e.g., `xsd:minInclusive`)

## Attribution

Test fixtures derived from examples in the W3C OWL 2 Web Ontology Language specifications.
Copyright (c) W3C. Licensed under the W3C Document License.
