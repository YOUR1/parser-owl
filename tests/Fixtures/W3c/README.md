# W3C OWL 2 Test Fixtures

OWL 2 ontology fixture files for conformance testing of parser-owl.

## Source

Fixtures are derived from examples in:
- [W3C OWL 2 Structural Specification](https://www.w3.org/TR/owl2-syntax/) (S3-S9)
- [W3C OWL 2 Primer](https://www.w3.org/TR/owl2-primer/) (Sections 3-5)

All fixtures are valid OWL 2 ontologies in Turtle serialization.

## Directory Structure

```
W3c/
├── class-expressions/        # S8.1 - intersectionOf, unionOf, complementOf, oneOf
├── property-axioms/          # S8.2-S8.3 - property types, characteristics, relationships
├── restrictions/             # S8.4-S8.5 - value and cardinality restrictions
├── individuals/              # S9.5-S9.6 - NamedIndividual, AllDifferent
├── data-ranges/              # S7 - rdfs:Datatype, facet restrictions
├── ontology-structure/       # S3 - owl:Ontology, imports, versioning
└── integration/              # Multi-construct comprehensive ontologies
```

## Attribution

Copyright (c) W3C. Test fixtures derived from W3C OWL 2 specification examples.
Licensed under the W3C Document License.
