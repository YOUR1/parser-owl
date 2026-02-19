<?php

declare(strict_types=1);

namespace Youri\vandenBogert\Software\ParserOwl\Extractors;

use EasyRdf\Resource;
use Youri\vandenBogert\Software\ParserCore\ValueObjects\ParsedRdf;

/**
 * Extracts OWL ontology metadata (imports, version, deprecated status).
 */
final class OntologyMetadataExtractor
{
    /**
     * Extract ontology metadata from the graph.
     *
     * @return array<int, array<string, mixed>>
     */
    public function extract(ParsedRdf $parsedRdf): array
    {
        $graph = $parsedRdf->graph;
        $ontologies = [];

        foreach ($graph->allOfType('owl:Ontology') as $resource) {
            /** @var Resource $resource */
            /** @var string|null $uri */
            $uri = $resource->getUri();
            if ($uri === null || str_starts_with($uri, '_:')) {
                continue;
            }

            $ontology = [
                'uri' => $uri,
                'imports' => [],
                'version_iri' => null,
                'version_info' => null,
                'deprecated' => false,
            ];

            foreach ($resource->all('owl:imports') as $import) {
                $importUri = $this->graphUri($import);
                if ($importUri !== null) {
                    $ontology['imports'][] = $importUri;
                }
            }

            /** @var mixed $versionIri */
            $versionIri = $resource->get('owl:versionIRI');
            if ($versionIri !== null) {
                $ontology['version_iri'] = $this->graphUri($versionIri) ?? (string) $versionIri;
            }

            /** @var mixed $versionInfo */
            $versionInfo = $resource->get('owl:versionInfo');
            if ($versionInfo !== null) {
                $ontology['version_info'] = (string) $versionInfo;
            }

            /** @var mixed $deprecated */
            $deprecated = $resource->get('owl:deprecated');
            if ($deprecated !== null) {
                $ontology['deprecated'] = filter_var((string) $deprecated, FILTER_VALIDATE_BOOLEAN);
            }

            $ontologies[] = $ontology;
        }

        return $ontologies;
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
