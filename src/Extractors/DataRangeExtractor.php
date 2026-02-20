<?php

declare(strict_types=1);

namespace Youri\vandenBogert\Software\ParserOwl\Extractors;

use EasyRdf\Literal;
use EasyRdf\Resource;
use Youri\vandenBogert\Software\ParserCore\ValueObjects\ParsedRdf;

/**
 * Extracts OWL data range definitions (rdfs:Datatype with facet restrictions).
 */
final class DataRangeExtractor
{
    /** @var array<string> */
    private const FACETS = [
        'xsd:minInclusive',
        'xsd:maxInclusive',
        'xsd:minExclusive',
        'xsd:maxExclusive',
        'xsd:pattern',
        'xsd:length',
        'xsd:minLength',
        'xsd:maxLength',
        'rdf:langRange',
    ];

    /**
     * Extract data range definitions from the graph.
     *
     * @return array<int, array<string, mixed>>
     */
    public function extract(ParsedRdf $parsedRdf): array
    {
        $graph = $parsedRdf->graph;
        $dataRanges = [];

        foreach ($graph->allOfType('rdfs:Datatype') as $resource) {
            /** @var Resource $resource */
            /** @var string|null $uri */
            $uri = $resource->getUri();
            if ($uri === null || str_starts_with($uri, '_:')) {
                continue;
            }

            $dataRange = [
                'uri' => $uri,
                'on_datatype' => $this->graphUri($resource->get('owl:onDatatype')),
                'with_restrictions' => [],
                'complement_of' => $this->graphUri($resource->get('owl:datatypeComplementOf')),
                'intersection_of' => [],
                'union_of' => [],
                'one_of' => [],
                'equivalent_class' => null,
            ];

            /** @var mixed $withRestrictions */
            $withRestrictions = $resource->get('owl:withRestrictions');
            if ($withRestrictions !== null) {
                $dataRange['with_restrictions'] = $this->extractDataRestrictionList($withRestrictions);
            }

            // DataIntersectionOf
            /** @var mixed $intersectionOf */
            $intersectionOf = $resource->get('owl:intersectionOf');
            if ($intersectionOf !== null) {
                $dataRange['intersection_of'] = $this->extractListMembers($intersectionOf);
            }

            // DataUnionOf
            /** @var mixed $unionOf */
            $unionOf = $resource->get('owl:unionOf');
            if ($unionOf !== null) {
                $dataRange['union_of'] = $this->extractListMembers($unionOf);
            }

            // DataOneOf (literal enumeration)
            /** @var mixed $oneOf */
            $oneOf = $resource->get('owl:oneOf');
            if ($oneOf !== null) {
                $dataRange['one_of'] = $this->extractLiteralListMembers($oneOf);
            }

            // DatatypeDefinition (owl:equivalentClass on datatype)
            /** @var mixed $equivClass */
            $equivClass = $resource->get('owl:equivalentClass');
            if ($equivClass !== null) {
                $dataRange['equivalent_class'] = $this->graphUri($equivClass);
            }

            $dataRanges[] = $dataRange;
        }

        return $dataRanges;
    }

    /**
     * Traverse an RDF list of facet restriction nodes.
     *
     * @return array<int, array<string, string>>
     */
    private function extractDataRestrictionList(mixed $listNode): array
    {
        $restrictions = [];
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
                /** @var array<string, string> $facetValues */
                $facetValues = [];
                foreach (self::FACETS as $facet) {
                    /** @var mixed $val */
                    $val = $first->get($facet);
                    if ($val !== null) {
                        $facetValues[$facet] = (string) $val;
                    }
                }
                if ($facetValues !== []) {
                    $restrictions[] = $facetValues;
                }
            }

            /** @var mixed $rest */
            $rest = $current->get('rdf:rest');
            $current = $rest instanceof Resource ? $rest : null;
        }

        return $restrictions;
    }

    /**
     * Traverse an RDF list and return URI members, skipping blank nodes.
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

    /**
     * Traverse an RDF list and return literal string values.
     *
     * @return array<string>
     */
    private function extractLiteralListMembers(mixed $listNode): array
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
            if ($first instanceof Literal) {
                $members[] = (string) $first->getValue();
            } elseif ($first !== null && ! ($first instanceof Resource)) {
                $members[] = (string) $first;
            }

            /** @var mixed $rest */
            $rest = $current->get('rdf:rest');
            $current = $rest instanceof Resource ? $rest : null;
        }

        return $members;
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
}
