<?php

declare(strict_types=1);

namespace Youri\vandenBogert\Software\ParserOwl\Extractors;

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
    public function extract(ParsedRdf $parsedRdf): array
    {
        $graph = $parsedRdf->graph;
        $individuals = [];

        foreach ($graph->allOfType('owl:NamedIndividual') as $resource) {
            /** @var Resource $resource */
            /** @var string|null $uri */
            $uri = $resource->getUri();
            if ($uri === null || str_starts_with($uri, '_:')) {
                continue;
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
