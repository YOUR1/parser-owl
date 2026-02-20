<?php

declare(strict_types=1);

namespace Youri\vandenBogert\Software\ParserOwl\Extractors;

use EasyRdf\Resource;
use Youri\vandenBogert\Software\ParserCore\ValueObjects\ParsedRdf;

/**
 * Extracts OWL ontology metadata (imports, version, deprecated status, compatibility, axiom annotations).
 */
final class OntologyMetadataExtractor
{
    /** @var array<string> Predicates to skip when extracting axiom annotations. */
    private const AXIOM_SKIP_PREDICATES = [
        'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
        'http://www.w3.org/2002/07/owl#annotatedSource',
        'http://www.w3.org/2002/07/owl#annotatedProperty',
        'http://www.w3.org/2002/07/owl#annotatedTarget',
    ];

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
                'prior_version' => null,
                'backward_compatible_with' => [],
                'incompatible_with' => [],
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

            /** @var mixed $priorVersion */
            $priorVersion = $resource->get('owl:priorVersion');
            if ($priorVersion !== null) {
                $ontology['prior_version'] = $this->graphUri($priorVersion) ?? (string) $priorVersion;
            }

            foreach ($resource->all('owl:backwardCompatibleWith') as $compat) {
                $compatUri = $this->graphUri($compat);
                if ($compatUri !== null) {
                    $ontology['backward_compatible_with'][] = $compatUri;
                }
            }

            foreach ($resource->all('owl:incompatibleWith') as $incompat) {
                $incompatUri = $this->graphUri($incompat);
                if ($incompatUri !== null) {
                    $ontology['incompatible_with'][] = $incompatUri;
                }
            }

            $ontologies[] = $ontology;
        }

        return $ontologies;
    }

    /**
     * Extract axiom annotations (owl:Axiom reification pattern) from the graph.
     *
     * @return array<int, array<string, mixed>>
     */
    public function extractAxiomAnnotations(ParsedRdf $parsedRdf): array
    {
        $graph = $parsedRdf->graph;
        $axiomAnnotations = [];

        foreach ($graph->allOfType('owl:Axiom') as $resource) {
            /** @var Resource $resource */
            $sourceUri = $this->graphUri($resource->get('owl:annotatedSource'));
            $propertyUri = $this->graphUri($resource->get('owl:annotatedProperty'));

            if ($sourceUri === null || $propertyUri === null) {
                continue;
            }

            // Target can be a URI or a literal
            /** @var string|null $targetValue */
            $targetValue = null;
            /** @var mixed $target */
            $target = $resource->get('owl:annotatedTarget');
            if ($target instanceof Resource) {
                $targetValue = $this->graphUri($target) ?? (string) $target;
            } elseif ($target !== null) {
                $targetValue = (string) $target;
            }

            // Extract annotations (all predicates except the structural ones)
            /** @var array<int, array<string, string>> $annotations */
            $annotations = [];

            /** @var array<string> $propertyUris */
            $propertyUris = $resource->propertyUris();

            foreach ($propertyUris as $propUri) {
                if (in_array($propUri, self::AXIOM_SKIP_PREDICATES, true)) {
                    continue;
                }

                $values = $resource->all('<' . $propUri . '>');
                foreach ($values as $val) {
                    $annotations[] = [
                        'property' => $propUri,
                        'value' => (string) $val,
                    ];
                }
            }

            $axiomAnnotations[] = [
                'source' => $sourceUri,
                'property' => $propertyUri,
                'target' => $targetValue,
                'annotations' => $annotations,
            ];
        }

        return $axiomAnnotations;
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
