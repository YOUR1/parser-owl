<?php

declare(strict_types=1);

namespace Youri\vandenBogert\Software\ParserOwl\Extractors;

use EasyRdf\Resource;
use Youri\vandenBogert\Software\ParserCore\ValueObjects\ParsedRdf;

/**
 * Enhances base RDF property data with OWL-specific types, characteristics, and relationships.
 *
 * Adds property type detection, characteristic flags, inverse/equivalent/disjoint
 * relationships, property chain axioms, and extracts global restrictions.
 */
final class OwlPropertyEnhancer
{
    private const OWL_NS = 'http://www.w3.org/2002/07/owl#';

    /** @var array<string, string> */
    private const PROPERTY_TYPE_MAP = [
        'http://www.w3.org/2002/07/owl#ObjectProperty' => 'object',
        'http://www.w3.org/2002/07/owl#DatatypeProperty' => 'datatype',
        'http://www.w3.org/2002/07/owl#AnnotationProperty' => 'annotation',
    ];

    /** @var array<string, string> */
    private const CHARACTERISTIC_MAP = [
        'FunctionalProperty' => 'is_functional',
        'InverseFunctionalProperty' => 'is_inverse_functional',
        'TransitiveProperty' => 'is_transitive',
        'SymmetricProperty' => 'is_symmetric',
        'AsymmetricProperty' => 'is_asymmetric',
        'ReflexiveProperty' => 'is_reflexive',
        'IrreflexiveProperty' => 'is_irreflexive',
    ];

    /**
     * Enhance properties with OWL-specific data.
     *
     * @param array<string, array<string, mixed>> $properties Properties keyed by URI
     * @return array<string, array<string, mixed>> Enhanced properties keyed by URI
     */
    public function enhance(array $properties, ParsedRdf $parsedRdf): array
    {
        $graph = $parsedRdf->graph;

        foreach ($properties as $uri => &$property) {
            $resource = $graph->resource($uri);
            $typeUris = $this->getResourceTypeUris($resource);
            /** @var array<string, mixed> $metadata */
            $metadata = $property['metadata'] ?? [];

            // Property type detection
            $property['property_type'] = $this->detectPropertyType($typeUris);

            // Characteristic flags
            foreach (self::CHARACTERISTIC_MAP as $characteristic => $flag) {
                $property[$flag] = $this->hasCharacteristic($typeUris, $metadata, $characteristic);
            }

            // Relationship extraction
            $property['inverse_of'] = $this->extractRelationshipUris($resource, 'owl:inverseOf');
            $property['equivalent_properties'] = $this->extractRelationshipUris($resource, 'owl:equivalentProperty');
            $property['property_disjoint_with'] = $this->extractRelationshipUris($resource, 'owl:propertyDisjointWith');

            // Property chain axiom (conditionally added -- key absent when no chain)
            /** @var mixed $chain */
            $chain = $resource->get('owl:propertyChainAxiom');
            if ($chain !== null) {
                $property['property_chain_axiom'] = $this->extractListMembers($chain);
            }
        }
        unset($property);

        // Handle owl:AllDisjointProperties (pairwise disjoint)
        $properties = $this->processAllDisjointProperties($properties, $parsedRdf);

        return $properties;
    }

    /**
     * Extract global owl:Restriction resources from the graph.
     *
     * @return array<string, array<string, mixed>> Restrictions keyed by generated key
     */
    public function extractRestrictions(ParsedRdf $parsedRdf): array
    {
        $restrictions = [];
        $graph = $parsedRdf->graph;

        foreach ($graph->allOfType('owl:Restriction') as $resource) {
            /** @var Resource $resource */
            /** @var mixed $onProperty */
            $onProperty = $resource->get('owl:onProperty');
            if ($onProperty === null) {
                continue;
            }

            $propertyUri = $this->graphUri($onProperty);
            if ($propertyUri === null) {
                continue;
            }

            $restriction = [
                'type' => 'owl:Restriction',
                'property' => $propertyUri,
                'value' => $this->graphValue($resource, 'owl:hasValue'),
                'cardinality' => $this->graphValue($resource, 'owl:cardinality'),
                'min_cardinality' => $this->graphValue($resource, 'owl:minCardinality'),
                'max_cardinality' => $this->graphValue($resource, 'owl:maxCardinality'),
                'qualified_cardinality' => $this->graphValue($resource, 'owl:qualifiedCardinality'),
                'min_qualified_cardinality' => $this->graphValue($resource, 'owl:minQualifiedCardinality'),
                'max_qualified_cardinality' => $this->graphValue($resource, 'owl:maxQualifiedCardinality'),
                'all_values_from' => $this->graphUri($resource->get('owl:allValuesFrom')),
                'some_values_from' => $this->graphUri($resource->get('owl:someValuesFrom')),
                'on_class' => $this->graphUri($resource->get('owl:onClass')),
                'on_data_range' => $this->graphUri($resource->get('owl:onDataRange')),
                'has_self' => null,
            ];

            /** @var mixed $hasSelf */
            $hasSelf = $resource->get('owl:hasSelf');
            if ($hasSelf !== null) {
                $restriction['has_self'] = filter_var((string) $hasSelf, FILTER_VALIDATE_BOOLEAN);
            }

            // Key by blank node ID or constructed key
            $key = $resource->isBNode()
                ? $resource->getUri()
                : $propertyUri . '_restriction_' . count($restrictions);

            $restrictions[$key] = $restriction;
        }

        return $restrictions;
    }

    /**
     * Process owl:AllDisjointProperties and add pairwise disjoint relationships.
     *
     * @param array<string, array<string, mixed>> $properties
     * @return array<string, array<string, mixed>>
     */
    private function processAllDisjointProperties(array $properties, ParsedRdf $parsedRdf): array
    {
        $graph = $parsedRdf->graph;

        foreach ($graph->allOfType('owl:AllDisjointProperties') as $resource) {
            /** @var Resource $resource */
            $members = $resource->get('owl:members');
            if ($members === null) {
                continue;
            }

            $memberUris = $this->extractListMembers($members);

            // Add pairwise disjoint relationships
            foreach ($memberUris as $memberUri) {
                if (! isset($properties[$memberUri])) {
                    continue;
                }

                $others = array_values(array_filter($memberUris, fn (string $u): bool => $u !== $memberUri));

                /** @var array<string> $existing */
                $existing = $properties[$memberUri]['property_disjoint_with'] ?? [];
                $properties[$memberUri]['property_disjoint_with'] = array_values(
                    array_unique(array_merge($existing, $others))
                );
            }
        }

        return $properties;
    }

    /**
     * Get all rdf:type URIs from a resource.
     *
     * @return array<string>
     */
    private function getResourceTypeUris(Resource $resource): array
    {
        $types = [];

        foreach ($resource->all('rdf:type') as $type) {
            if ($type instanceof Resource) {
                /** @var string|null $uri */
                $uri = $type->getUri();
                if ($uri !== null) {
                    $types[] = $uri;
                }
            }
        }

        return $types;
    }

    /**
     * Detect OWL property type from rdf:type URIs.
     *
     * @param array<string> $typeUris
     */
    private function detectPropertyType(array $typeUris): ?string
    {
        foreach (self::PROPERTY_TYPE_MAP as $owlType => $label) {
            if (in_array($owlType, $typeUris, true)) {
                return $label;
            }
        }

        return null;
    }

    /**
     * Check if a property has a specific OWL characteristic.
     *
     * @param array<string> $typeUris Full type URIs from graph
     * @param array<string, mixed> $metadata Property metadata from base extraction
     */
    private function hasCharacteristic(array $typeUris, array $metadata, string $characteristic): bool
    {
        // Check full URI in types (from EasyRdf graph)
        if (in_array(self::OWL_NS . $characteristic, $typeUris, true)) {
            return true;
        }

        // Backward compat: check types array in metadata
        /** @var array<string> $metaTypes */
        $metaTypes = $metadata['types'] ?? [];
        if (in_array(self::OWL_NS . $characteristic, $metaTypes, true)) {
            return true;
        }

        // Check prefixed form in metadata @type
        /** @var mixed $atTypeRaw */
        $atTypeRaw = $metadata['@type'] ?? [];
        $atTypes = is_array($atTypeRaw) ? $atTypeRaw : [$atTypeRaw];

        return in_array('owl:' . $characteristic, $atTypes, true);
    }

    /**
     * Extract relationship URIs from a resource property.
     *
     * @return array<string>
     */
    private function extractRelationshipUris(Resource $resource, string $property): array
    {
        $uris = [];

        foreach ($resource->all($property) as $value) {
            if ($value instanceof Resource) {
                $uri = $this->graphUri($value);
                if ($uri !== null) {
                    $uris[] = $uri;
                }
            }
        }

        return $uris;
    }

    /**
     * Extract URI from an EasyRdf Resource, skipping blank nodes.
     */
    private function graphUri(mixed $resource): ?string
    {
        if (! ($resource instanceof Resource)) {
            return null;
        }

        /** @var string|null $uri */
        $uri = $resource->getUri();

        return ($uri !== null && ! str_starts_with($uri, '_:')) ? $uri : null;
    }

    /**
     * Extract string value from an EasyRdf Resource property.
     */
    private function graphValue(Resource $resource, string $property): ?string
    {
        /** @var mixed $val */
        $val = $resource->get($property);

        return $val !== null ? (string) $val : null;
    }

    /**
     * Traverse an RDF list (rdf:first/rdf:rest) and return URI members, skipping blank nodes.
     *
     * @return array<string>
     */
    private function extractListMembers(mixed $listNode): array
    {
        $members = [];
        /** @var Resource|null $current */
        $current = $listNode instanceof Resource ? $listNode : null;

        while ($current !== null) {
            /** @var string|null $uri */
            $uri = $current->getUri();
            if ($uri === 'http://www.w3.org/1999/02/22-rdf-syntax-ns#nil') {
                break;
            }

            /** @var mixed $first */
            $first = $current->get('rdf:first');
            if ($first instanceof Resource) {
                /** @var string|null $memberUri */
                $memberUri = $first->getUri();
                if ($memberUri !== null && ! str_starts_with($memberUri, '_:')) {
                    $members[] = $memberUri;
                }
            }

            /** @var mixed $rest */
            $rest = $current->get('rdf:rest');
            $current = $rest instanceof Resource ? $rest : null;
        }

        return $members;
    }
}
