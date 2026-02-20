<?php

declare(strict_types=1);

namespace Youri\vandenBogert\Software\ParserOwl\Extractors;

use EasyRdf\Resource;
use Youri\vandenBogert\Software\ParserCore\ValueObjects\ParsedRdf;

/**
 * Enhances base RDF class data with OWL-specific relationships.
 *
 * Adds equivalent classes, disjoint classes, class expressions,
 * and OWL restrictions to classes extracted by the base ClassExtractor.
 */
final class OwlClassEnhancer
{
    /**
     * Enhance classes with OWL-specific data.
     *
     * @param array<string, array<string, mixed>> $classes Classes keyed by URI
     * @return array<string, array<string, mixed>> Enhanced classes keyed by URI
     */
    public function enhance(array $classes, ParsedRdf $parsedRdf): array
    {
        $graph = $parsedRdf->graph;

        foreach ($classes as $uri => $class) {
            $resource = $graph->resource($uri);

            $classes[$uri]['equivalent_classes'] = $this->extractEquivalentClasses($resource);
            $classes[$uri]['disjoint_with'] = $this->extractDisjointWith($resource);
            $classes[$uri]['disjoint_union_of'] = $this->extractDisjointUnionOf($resource);
            $classes[$uri]['has_key'] = $this->extractHasKey($resource);
            $classes[$uri]['class_expressions'] = $this->extractClassExpressions($resource);
            $classes[$uri]['constraints'] = $this->extractClassConstraints($resource);

            if (isset($classes[$uri]['parent_classes'])) {
                $classes[$uri]['parent_classes'] = array_values(
                    array_filter(
                        $classes[$uri]['parent_classes'],
                        fn(string $parentUri): bool => !str_starts_with($parentUri, '_:')
                    )
                );
            }
        }

        // Handle owl:AllDisjointClasses (pairwise disjoint)
        $classes = $this->processAllDisjointClasses($classes, $parsedRdf);

        return $classes;
    }

    /**
     * Extract equivalent class URIs (named classes only, not blank nodes).
     *
     * @return array<string>
     */
    private function extractEquivalentClasses(Resource $resource): array
    {
        $equivalents = [];

        foreach ($resource->all('owl:equivalentClass') as $equiv) {
            if (! ($equiv instanceof Resource)) {
                continue;
            }

            if ($equiv->isBNode()) {
                // Blank node class expressions go to class_expressions, not here
                continue;
            }

            /** @var string|null $uri */
            $uri = $equiv->getUri();
            if ($uri !== null && $uri !== '') {
                $equivalents[] = $uri;
            }
        }

        return $equivalents;
    }

    /**
     * Extract disjoint class URIs from owl:disjointWith.
     *
     * @return array<string>
     */
    private function extractDisjointWith(Resource $resource): array
    {
        $disjoints = [];

        foreach ($resource->all('owl:disjointWith') as $disjoint) {
            if (! ($disjoint instanceof Resource)) {
                continue;
            }

            /** @var string|null $uri */
            $uri = $this->graphUri($disjoint);
            if ($uri !== null) {
                $disjoints[] = $uri;
            }
        }

        return $disjoints;
    }

    /**
     * Process owl:AllDisjointClasses and add pairwise disjoint relationships.
     *
     * @param array<string, array<string, mixed>> $classes
     * @return array<string, array<string, mixed>>
     */
    private function processAllDisjointClasses(array $classes, ParsedRdf $parsedRdf): array
    {
        $graph = $parsedRdf->graph;

        foreach ($graph->allOfType('owl:AllDisjointClasses') as $resource) {
            /** @var Resource $resource */
            $members = $resource->get('owl:members');
            if ($members === null) {
                continue;
            }

            $memberUris = $this->extractListMembers($members);

            // Add pairwise disjoint relationships
            foreach ($memberUris as $memberUri) {
                if (! isset($classes[$memberUri])) {
                    continue;
                }

                $others = array_values(array_filter($memberUris, fn (string $u): bool => $u !== $memberUri));

                /** @var array<string> $existing */
                $existing = $classes[$memberUri]['disjoint_with'] ?? [];
                $classes[$memberUri]['disjoint_with'] = array_values(
                    array_unique(array_merge($existing, $others))
                );
            }
        }

        return $classes;
    }

    /**
     * Extract owl:disjointUnionOf member URIs from a class resource.
     *
     * @return array<string>
     */
    private function extractDisjointUnionOf(Resource $resource): array
    {
        /** @var mixed $listNode */
        $listNode = $resource->get('owl:disjointUnionOf');
        if ($listNode === null) {
            return [];
        }

        return $this->extractListMembers($listNode);
    }

    /**
     * Extract owl:hasKey property URIs from a class resource.
     *
     * @return array<string>
     */
    private function extractHasKey(Resource $resource): array
    {
        /** @var mixed $listNode */
        $listNode = $resource->get('owl:hasKey');
        if ($listNode === null) {
            return [];
        }

        return $this->extractListMembers($listNode);
    }

    /**
     * Extract class expressions from resource and its owl:equivalentClass blank nodes.
     *
     * @return array<string, string|array<string>>
     */
    private function extractClassExpressions(Resource $resource): array
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

    /**
     * Collect class expression fields from a resource.
     *
     * @param array<string, string|array<string>> $expressions
     */
    private function collectClassExpressionFields(Resource $resource, array &$expressions): void
    {
        /** @var mixed $intersectionOf */
        $intersectionOf = $resource->get('owl:intersectionOf');
        if ($intersectionOf !== null) {
            $expressions['intersection_of'] = $this->extractListMembers($intersectionOf);
        }

        /** @var mixed $unionOf */
        $unionOf = $resource->get('owl:unionOf');
        if ($unionOf !== null) {
            $expressions['union_of'] = $this->extractListMembers($unionOf);
        }

        /** @var mixed $complementOf */
        $complementOf = $resource->get('owl:complementOf');
        if ($complementOf !== null) {
            $uri = $this->graphUri($complementOf);
            if ($uri !== null) {
                $expressions['complement_of'] = $uri;
            }
        }

        /** @var mixed $oneOf */
        $oneOf = $resource->get('owl:oneOf');
        if ($oneOf !== null) {
            $expressions['one_of'] = $this->extractListMembers($oneOf);
        }
    }

    /**
     * Extract class constraints from rdfs:subClassOf blank nodes with owl:onProperty.
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractClassConstraints(Resource $resource): array
    {
        $restrictions = [];

        foreach ($resource->all('rdfs:subClassOf') as $subClass) {
            if (! ($subClass instanceof Resource) || ! $subClass->isBNode()) {
                continue;
            }

            /** @var mixed $onProperty */
            $onProperty = $subClass->get('owl:onProperty');
            if ($onProperty === null) {
                continue;
            }

            $restriction = [
                'type' => 'owl:Restriction',
                'property' => $this->graphUri($onProperty),
                'value' => $this->extractHasValue($subClass),
                'cardinality' => $this->graphValue($subClass, 'owl:cardinality'),
                'min_cardinality' => $this->graphValue($subClass, 'owl:minCardinality'),
                'max_cardinality' => $this->graphValue($subClass, 'owl:maxCardinality'),
                'qualified_cardinality' => $this->graphValue($subClass, 'owl:qualifiedCardinality'),
                'min_qualified_cardinality' => $this->graphValue($subClass, 'owl:minQualifiedCardinality'),
                'max_qualified_cardinality' => $this->graphValue($subClass, 'owl:maxQualifiedCardinality'),
                'all_values_from' => $this->graphUri($subClass->get('owl:allValuesFrom')),
                'some_values_from' => $this->graphUri($subClass->get('owl:someValuesFrom')),
                'on_class' => $this->graphUri($subClass->get('owl:onClass')),
                'on_data_range' => $this->graphUri($subClass->get('owl:onDataRange')),
                'has_self' => null,
            ];

            /** @var mixed $hasSelf */
            $hasSelf = $subClass->get('owl:hasSelf');
            if ($hasSelf !== null) {
                $restriction['has_self'] = filter_var((string) $hasSelf, FILTER_VALIDATE_BOOLEAN);
            }

            $restrictions[] = $restriction;
        }

        return $restrictions;
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
     * Extract owl:hasValue, handling both literals and Resources (URIs).
     */
    private function extractHasValue(Resource $resource): ?string
    {
        $val = $resource->get('owl:hasValue');
        if ($val === null) {
            return null;
        }
        if ($val instanceof Resource) {
            return $this->graphUri($val);
        }

        return (string) $val;
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
