<?php

declare(strict_types=1);

namespace Youri\vandenBogert\Software\ParserOwl\Extractors;

use EasyRdf\Literal;
use EasyRdf\Resource;
use Youri\vandenBogert\Software\ParserCore\ValueObjects\ParsedRdf;

/**
 * Extracts OWL named individuals and folds owl:AllDifferent assertions.
 */
final class IndividualExtractor
{
    /**
     * Extract named individuals from the graph.
     *
     * @return array<int, array<string, mixed>>
     */
    public function extract(ParsedRdf $parsedRdf, bool $includeSkolemizedBlankNodes = false): array
    {
        $graph = $parsedRdf->graph;
        $individuals = [];

        foreach ($graph->allOfType('owl:NamedIndividual') as $resource) {
            /** @var Resource $resource */
            /** @var string|null $uri */
            $uri = $resource->getUri();
            if ($uri === null) {
                continue;
            }

            $isBlankNode = str_starts_with($uri, '_:');

            if ($isBlankNode && ! $includeSkolemizedBlankNodes) {
                continue;
            }

            // Skolemize blank node URI
            if ($isBlankNode) {
                $bnodeId = substr($uri, 2); // Remove '_:' prefix
                $uri = 'urn:bnode:' . $bnodeId;
            }

            $typeUris = [];
            foreach ($resource->all('rdf:type') as $type) {
                if ($type instanceof Resource) {
                    /** @var string|null $typeUri */
                    $typeUri = $type->getUri();
                    if ($typeUri !== null && $typeUri !== 'http://www.w3.org/2002/07/owl#NamedIndividual') {
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
                'properties' => [],
                'negative_assertions' => [],
                'is_anonymous' => $isBlankNode,
            ];

            /** @var mixed $label */
            $label = $resource->get('rdfs:label');
            if ($label !== null) {
                $individual['label'] = (string) $label;
            }

            foreach ($resource->all('owl:sameAs') as $same) {
                $sameUri = $this->graphUri($same);
                if ($sameUri !== null) {
                    $individual['same_as'][] = $sameUri;
                }
            }

            foreach ($resource->all('owl:differentFrom') as $diff) {
                $diffUri = $this->graphUri($diff);
                if ($diffUri !== null) {
                    $individual['different_from'][] = $diffUri;
                }
            }

            // Extract property assertions (object + data properties)
            $individual['properties'] = $this->extractPropertyAssertions($resource);

            $individuals[] = $individual;
        }

        // Fold owl:AllDifferent groups into individual different_from lists
        $allDifferentGroups = [];
        foreach ($graph->allOfType('owl:AllDifferent') as $resource) {
            /** @var Resource $resource */
            /** @var mixed $members */
            $members = $resource->get('owl:distinctMembers');
            if ($members !== null) {
                $memberUris = $this->extractListMembers($members);
                if ($memberUris !== []) {
                    $allDifferentGroups[] = $memberUris;
                }
            }
        }

        if ($allDifferentGroups !== []) {
            foreach ($allDifferentGroups as $group) {
                foreach ($group as $memberUri) {
                    $others = array_values(array_filter($group, fn (string $u): bool => $u !== $memberUri));
                    foreach ($individuals as &$individual) {
                        if ($individual['uri'] === $memberUri) {
                            /** @var array<string> $merged */
                            $merged = array_merge($individual['different_from'], $others);
                            $individual['different_from'] = array_values(array_unique($merged));
                        }
                    }
                    unset($individual);
                }
            }
        }

        // Fold owl:NegativePropertyAssertion into individual negative_assertions
        $individuals = $this->processNegativeAssertions($individuals, $parsedRdf);

        return $individuals;
    }

    /** @var array<string> Known OWL/RDF/RDFS predicates to skip when extracting property assertions. */
    private const SKIP_PREDICATES = [
        'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
        'http://www.w3.org/2000/01/rdf-schema#label',
        'http://www.w3.org/2000/01/rdf-schema#comment',
        'http://www.w3.org/2002/07/owl#sameAs',
        'http://www.w3.org/2002/07/owl#differentFrom',
    ];

    /**
     * Extract property assertions (object + data) from an individual resource.
     *
     * @return array<string, array<string>>
     */
    private function extractPropertyAssertions(Resource $resource): array
    {
        /** @var array<string, array<string>> $properties */
        $properties = [];

        /** @var array<string> $propertyUris */
        $propertyUris = $resource->propertyUris();

        foreach ($propertyUris as $propUri) {
            if (in_array($propUri, self::SKIP_PREDICATES, true)) {
                continue;
            }

            $values = $resource->all('<' . $propUri . '>');
            if ($values === []) {
                continue;
            }

            /** @var array<string> $propValues */
            $propValues = [];
            foreach ($values as $val) {
                if ($val instanceof Resource) {
                    $uri = $this->graphUri($val);
                    if ($uri !== null) {
                        $propValues[] = $uri;
                    }
                } elseif ($val instanceof Literal) {
                    $propValues[] = (string) $val;
                }
            }

            if ($propValues !== []) {
                $properties[$propUri] = $propValues;
            }
        }

        return $properties;
    }

    /**
     * Process owl:NegativePropertyAssertion resources and fold into individuals.
     *
     * @param array<int, array<string, mixed>> $individuals
     * @return array<int, array<string, mixed>>
     */
    private function processNegativeAssertions(array $individuals, ParsedRdf $parsedRdf): array
    {
        $graph = $parsedRdf->graph;

        foreach ($graph->allOfType('owl:NegativePropertyAssertion') as $resource) {
            /** @var Resource $resource */
            $sourceUri = $this->graphUri($resource->get('owl:sourceIndividual'));
            $propertyUri = $this->graphUri($resource->get('owl:assertionProperty'));

            if ($sourceUri === null || $propertyUri === null) {
                continue;
            }

            // Determine target value (individual URI or literal)
            /** @var string|null $targetValue */
            $targetValue = null;
            $targetIndividual = $resource->get('owl:targetIndividual');
            if ($targetIndividual !== null) {
                $targetValue = $this->graphUri($targetIndividual);
            }

            if ($targetValue === null) {
                /** @var mixed $targetVal */
                $targetVal = $resource->get('owl:targetValue');
                if ($targetVal !== null) {
                    $targetValue = (string) $targetVal;
                }
            }

            if ($targetValue === null) {
                continue;
            }

            // Fold into the matching individual
            foreach ($individuals as &$individual) {
                if ($individual['uri'] === $sourceUri) {
                    /** @var array<string, array<string>> $negAssertions */
                    $negAssertions = $individual['negative_assertions'];
                    if (! isset($negAssertions[$propertyUri])) {
                        $negAssertions[$propertyUri] = [];
                    }
                    $negAssertions[$propertyUri][] = $targetValue;
                    $individual['negative_assertions'] = $negAssertions;
                }
            }
            unset($individual);
        }

        return $individuals;
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
}
