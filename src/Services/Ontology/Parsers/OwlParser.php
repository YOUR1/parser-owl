<?php

namespace App\Services\Ontology\Parsers;

use EasyRdf\Graph;
use EasyRdf\Resource;

class OwlParser extends RdfParser
{
    public function parse(string $content, array $options = []): array
    {
        $result = parent::parse($content, $options);

        // Enhance with OWL-specific features
        $result['metadata']['type'] = 'owl';
        $result = $this->processOwlFeatures($result, $content, $options);

        return $result;
    }

    public function canParse(string $content): bool
    {
        return parent::canParse($content) &&
               (str_contains($content, 'owl:') ||
                str_contains($content, 'http://www.w3.org/2002/07/owl#'));
    }

    public function getSupportedFormats(): array
    {
        return ['owl'];
    }

    protected function processOwlFeatures(array $result, string $content, array $options): array
    {
        $graph = $this->lastParsedRdf?->graph;

        $result['classes'] = $this->enhanceClassesWithOwl($result['classes'], $graph);
        $result['properties'] = $this->enhancePropertiesWithOwl($result['properties'], $graph);
        $result['ontology'] = $graph ? $this->extractOntologyMetadata($graph) : [];
        $result['individuals'] = $graph ? $this->extractIndividuals($graph) : [];
        $result['data_ranges'] = $graph ? $this->extractDataRanges($graph) : [];

        return $result;
    }

    // ── Class Enhancement ────────────────────────────────────────────

    protected function enhanceClassesWithOwl(array $classes, ?Graph $graph): array
    {
        return array_map(function ($class) use ($graph) {
            // Metadata-based restrictions (backward compat)
            $class['constraints'] = $this->extractOwlRestrictions($class['metadata'] ?? []);
            $class['class_expressions'] = [];

            if ($graph) {
                $resource = $graph->resource($class['uri']);
                $class['constraints'] = $this->extractClassConstraints($resource);
                $class['class_expressions'] = $this->extractClassExpressions($resource);
            }

            return $class;
        }, $classes);
    }

    protected function extractClassConstraints(Resource $resource): array
    {
        $restrictions = [];

        foreach ($resource->all('rdfs:subClassOf') as $subClass) {
            if (! ($subClass instanceof Resource) || ! $subClass->isBNode()) {
                continue;
            }

            $onProperty = $subClass->get('owl:onProperty');
            if (! $onProperty) {
                continue;
            }

            $restriction = [
                'type' => 'owl:Restriction',
                'property' => $this->graphUri($onProperty),
                'value' => $this->graphValue($subClass, 'owl:hasValue'),
                'cardinality' => $this->graphValue($subClass, 'owl:cardinality'),
                'min_cardinality' => $this->graphValue($subClass, 'owl:minCardinality'),
                'max_cardinality' => $this->graphValue($subClass, 'owl:maxCardinality'),
                'qualified_cardinality' => $this->graphValue($subClass, 'owl:qualifiedCardinality'),
                'min_qualified_cardinality' => $this->graphValue($subClass, 'owl:minQualifiedCardinality'),
                'max_qualified_cardinality' => $this->graphValue($subClass, 'owl:maxQualifiedCardinality'),
                'all_values_from' => $this->graphUri($subClass->get('owl:allValuesFrom')),
                'some_values_from' => $this->graphUri($subClass->get('owl:someValuesFrom')),
                'on_class' => $this->graphUri($subClass->get('owl:onClass')),
                'has_self' => null,
            ];

            $hasSelf = $subClass->get('owl:hasSelf');
            if ($hasSelf) {
                $restriction['has_self'] = filter_var((string) $hasSelf, FILTER_VALIDATE_BOOLEAN);
            }

            $restrictions[] = $restriction;
        }

        return $restrictions;
    }

    protected function extractClassExpressions(Resource $resource): array
    {
        $expressions = [];

        // Direct class expressions on the resource
        $this->collectClassExpressionFields($resource, $expressions);

        // Class expressions on blank nodes under owl:equivalentClass
        foreach ($resource->all('owl:equivalentClass') as $equiv) {
            if ($equiv instanceof Resource && $equiv->isBNode()) {
                $this->collectClassExpressionFields($equiv, $expressions);
            }
        }

        return $expressions;
    }

    private function collectClassExpressionFields(Resource $resource, array &$expressions): void
    {
        $intersectionOf = $resource->get('owl:intersectionOf');
        if ($intersectionOf) {
            $expressions['intersection_of'] = $this->extractListMembers($intersectionOf);
        }

        $unionOf = $resource->get('owl:unionOf');
        if ($unionOf) {
            $expressions['union_of'] = $this->extractListMembers($unionOf);
        }

        $complementOf = $resource->get('owl:complementOf');
        if ($complementOf) {
            $uri = $this->graphUri($complementOf);
            if ($uri) {
                $expressions['complement_of'] = $uri;
            }
        }

        $oneOf = $resource->get('owl:oneOf');
        if ($oneOf) {
            $expressions['one_of'] = $this->extractListMembers($oneOf);
        }
    }

    // ── Property Enhancement ─────────────────────────────────────────

    protected function enhancePropertiesWithOwl(array $properties, ?Graph $graph): array
    {
        return array_map(function ($property) use ($graph) {
            $metadata = $property['metadata'] ?? [];
            $types = $metadata['types'] ?? [];

            // Detect all property characteristics
            $property['is_functional'] = $this->hasCharacteristic($types, $metadata, 'FunctionalProperty');
            $property['is_inverse_functional'] = $this->hasCharacteristic($types, $metadata, 'InverseFunctionalProperty');
            $property['is_transitive'] = $this->hasCharacteristic($types, $metadata, 'TransitiveProperty');
            $property['is_symmetric'] = $this->hasCharacteristic($types, $metadata, 'SymmetricProperty');
            $property['is_asymmetric'] = $this->hasCharacteristic($types, $metadata, 'AsymmetricProperty');
            $property['is_reflexive'] = $this->hasCharacteristic($types, $metadata, 'ReflexiveProperty');
            $property['is_irreflexive'] = $this->hasCharacteristic($types, $metadata, 'IrreflexiveProperty');

            if (isset($metadata['owl:inverseOf'])) {
                $property['inverse_of'] = $this->normalizeArray($metadata['owl:inverseOf']);
            }

            // Graph-based extraction for property chain axiom
            if ($graph && isset($property['uri'])) {
                $resource = $graph->resource($property['uri']);
                $chain = $resource->get('owl:propertyChainAxiom');
                if ($chain) {
                    $property['property_chain_axiom'] = $this->extractListMembers($chain);
                }
            }

            return $property;
        }, $properties);
    }

    protected function hasCharacteristic(array $types, array $metadata, string $characteristic): bool
    {
        // Check full URI in types array (from EasyRdf extraction)
        if (in_array('http://www.w3.org/2002/07/owl#'.$characteristic, $types)) {
            return true;
        }

        // Backward compat: check @type in metadata (for direct metadata input)
        return $this->hasOwlType($metadata, 'owl:'.$characteristic);
    }

    // ── Ontology Metadata ────────────────────────────────────────────

    protected function extractOntologyMetadata(Graph $graph): array
    {
        $ontologies = [];

        foreach ($graph->allOfType('owl:Ontology') as $resource) {
            $ontology = [
                'uri' => $resource->getUri(),
                'imports' => [],
                'version_iri' => null,
                'version_info' => null,
                'deprecated' => false,
            ];

            foreach ($resource->all('owl:imports') as $import) {
                $uri = $this->graphUri($import);
                if ($uri) {
                    $ontology['imports'][] = $uri;
                }
            }

            $versionIri = $resource->get('owl:versionIRI');
            if ($versionIri) {
                $ontology['version_iri'] = $this->graphUri($versionIri) ?? (string) $versionIri;
            }

            $versionInfo = $resource->get('owl:versionInfo');
            if ($versionInfo) {
                $ontology['version_info'] = (string) $versionInfo;
            }

            $deprecated = $resource->get('owl:deprecated');
            if ($deprecated) {
                $ontology['deprecated'] = filter_var((string) $deprecated, FILTER_VALIDATE_BOOLEAN);
            }

            $ontologies[] = $ontology;
        }

        return $ontologies;
    }

    // ── Individuals ──────────────────────────────────────────────────

    protected function extractIndividuals(Graph $graph): array
    {
        $individuals = [];

        foreach ($graph->allOfType('owl:NamedIndividual') as $resource) {
            $uri = $resource->getUri();
            if (! $uri || str_starts_with($uri, '_:')) {
                continue;
            }

            $typeUris = [];
            foreach ($resource->all('rdf:type') as $type) {
                if ($type instanceof Resource) {
                    $typeUri = $type->getUri();
                    if ($typeUri !== 'http://www.w3.org/2002/07/owl#NamedIndividual') {
                        $typeUris[] = $typeUri;
                    }
                }
            }

            $individual = [
                'uri' => $uri,
                'types' => $typeUris,
                'label' => null,
                'same_as' => [],
                'different_from' => [],
            ];

            $label = $resource->get('rdfs:label');
            if ($label) {
                $individual['label'] = (string) $label;
            }

            foreach ($resource->all('owl:sameAs') as $same) {
                $sameUri = $this->graphUri($same);
                if ($sameUri) {
                    $individual['same_as'][] = $sameUri;
                }
            }

            foreach ($resource->all('owl:differentFrom') as $diff) {
                $diffUri = $this->graphUri($diff);
                if ($diffUri) {
                    $individual['different_from'][] = $diffUri;
                }
            }

            $individuals[] = $individual;
        }

        // Extract owl:AllDifferent groups
        $allDifferentGroups = [];
        foreach ($graph->allOfType('owl:AllDifferent') as $resource) {
            $members = $resource->get('owl:distinctMembers');
            if ($members) {
                $memberUris = $this->extractListMembers($members);
                if (! empty($memberUris)) {
                    $allDifferentGroups[] = $memberUris;
                }
            }
        }

        if (! empty($allDifferentGroups)) {
            // Fold AllDifferent groups into individual different_from lists
            foreach ($allDifferentGroups as $group) {
                foreach ($group as $memberUri) {
                    $others = array_values(array_filter($group, fn ($u) => $u !== $memberUri));
                    foreach ($individuals as &$individual) {
                        if ($individual['uri'] === $memberUri) {
                            $individual['different_from'] = array_values(
                                array_unique(array_merge($individual['different_from'], $others))
                            );
                        }
                    }
                    unset($individual);
                }
            }
        }

        return $individuals;
    }

    // ── Data Ranges ──────────────────────────────────────────────────

    protected function extractDataRanges(Graph $graph): array
    {
        $dataRanges = [];

        foreach ($graph->allOfType('rdfs:Datatype') as $resource) {
            $uri = $resource->getUri();
            if (! $uri || str_starts_with($uri, '_:')) {
                continue;
            }

            $dataRange = [
                'uri' => $uri,
                'on_datatype' => $this->graphUri($resource->get('owl:onDatatype')),
                'with_restrictions' => [],
                'complement_of' => $this->graphUri($resource->get('owl:datatypeComplementOf')),
            ];

            $withRestrictions = $resource->get('owl:withRestrictions');
            if ($withRestrictions) {
                $dataRange['with_restrictions'] = $this->extractDataRestrictionList($withRestrictions);
            }

            $dataRanges[] = $dataRange;
        }

        return $dataRanges;
    }

    private function extractDataRestrictionList($listNode): array
    {
        $restrictions = [];
        $current = $listNode;

        $facets = [
            'xsd:minInclusive', 'xsd:maxInclusive',
            'xsd:minExclusive', 'xsd:maxExclusive',
            'xsd:pattern', 'xsd:length',
            'xsd:minLength', 'xsd:maxLength',
        ];

        while ($current) {
            $uri = method_exists($current, 'getUri') ? $current->getUri() : null;
            if ($uri === 'http://www.w3.org/1999/02/22-rdf-syntax-ns#nil') {
                break;
            }

            $first = $current->get('rdf:first');
            if ($first && $first instanceof Resource) {
                $facetValues = [];
                foreach ($facets as $facet) {
                    $val = $first->get($facet);
                    if ($val) {
                        $facetValues[$facet] = (string) $val;
                    }
                }
                if (! empty($facetValues)) {
                    $restrictions[] = $facetValues;
                }
            }

            $current = $current->get('rdf:rest');
        }

        return $restrictions;
    }

    // ── RDF List Traversal ───────────────────────────────────────────

    protected function extractListMembers($listNode): array
    {
        $members = [];
        $current = $listNode;

        while ($current) {
            $uri = method_exists($current, 'getUri') ? $current->getUri() : null;
            if ($uri === 'http://www.w3.org/1999/02/22-rdf-syntax-ns#nil') {
                break;
            }

            $first = $current->get('rdf:first');
            if ($first) {
                $memberUri = method_exists($first, 'getUri') ? $first->getUri() : null;
                if ($memberUri && ! str_starts_with($memberUri, '_:')) {
                    $members[] = $memberUri;
                }
            }

            $current = $current->get('rdf:rest');
        }

        return $members;
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function graphUri($resource): ?string
    {
        if (! $resource) {
            return null;
        }

        if (method_exists($resource, 'getUri')) {
            $uri = $resource->getUri();

            return ($uri && ! str_starts_with($uri, '_:')) ? $uri : null;
        }

        return null;
    }

    private function graphValue(Resource $resource, string $property): ?string
    {
        $val = $resource->get($property);

        return $val ? (string) $val : null;
    }

    protected function extractOwlRestrictions(array $metadata): array
    {
        $restrictions = [];

        if (! isset($metadata['rdfs:subClassOf'])) {
            return $restrictions;
        }
        $subClasses = $this->normalizeArray($metadata['rdfs:subClassOf']);

        foreach ($subClasses as $subClass) {
            if (! (is_array($subClass) && isset($subClass['@type']) && $subClass['@type'] === 'owl:Restriction')) {
                continue;
            }
            $restrictions[] = [
                'type' => 'owl:Restriction',
                'property' => $subClass['owl:onProperty'] ?? null,
                'value' => $subClass['owl:hasValue'] ?? null,
                'cardinality' => $subClass['owl:cardinality'] ?? $subClass['owl:minCardinality'] ?? $subClass['owl:maxCardinality'] ?? null,
                'all_values_from' => $subClass['owl:allValuesFrom'] ?? null,
                'some_values_from' => $subClass['owl:someValuesFrom'] ?? null,
            ];
        }

        return $restrictions;
    }

    protected function hasOwlType(array $metadata, string $owlType): bool
    {
        $types = $this->normalizeArray($metadata['@type'] ?? []);

        return in_array($owlType, $types);
    }

    protected function normalizeArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value === null) {
            return [];
        }

        return [$value];
    }
}
