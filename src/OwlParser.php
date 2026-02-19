<?php

declare(strict_types=1);

namespace Youri\vandenBogert\Software\ParserOwl;

use Youri\vandenBogert\Software\ParserCore\Contracts\RdfFormatHandlerInterface;
use Youri\vandenBogert\Software\ParserCore\ValueObjects\ParsedOntology;
use Youri\vandenBogert\Software\ParserCore\ValueObjects\ParsedRdf;
use Youri\vandenBogert\Software\ParserOwl\Extractors\DataRangeExtractor;
use Youri\vandenBogert\Software\ParserOwl\Extractors\IndividualExtractor;
use Youri\vandenBogert\Software\ParserOwl\Extractors\OntologyMetadataExtractor;
use Youri\vandenBogert\Software\ParserOwl\Extractors\OwlClassEnhancer;
use Youri\vandenBogert\Software\ParserOwl\Extractors\OwlPropertyEnhancer;
use Youri\vandenBogert\Software\ParserRdf\RdfParser;

/**
 * OWL Parser - Extends RdfParser with OWL-specific post-processing.
 *
 * Inherits the full RDF parsing pipeline (format handlers, extractors)
 * and adds OWL-specific enhancement via processOwlFeatures().
 *
 * NOT final: designed for potential extension.
 */
class OwlParser extends RdfParser
{
    private readonly OwlClassEnhancer $owlClassEnhancer;

    private readonly OwlPropertyEnhancer $owlPropertyEnhancer;

    private readonly IndividualExtractor $individualExtractor;

    private readonly DataRangeExtractor $dataRangeExtractor;

    private readonly OntologyMetadataExtractor $ontologyMetadataExtractor;

    public function __construct()
    {
        parent::__construct();
        $this->owlClassEnhancer = new OwlClassEnhancer();
        $this->owlPropertyEnhancer = new OwlPropertyEnhancer();
        $this->individualExtractor = new IndividualExtractor();
        $this->dataRangeExtractor = new DataRangeExtractor();
        $this->ontologyMetadataExtractor = new OntologyMetadataExtractor();
    }

    protected function buildParsedOntology(
        ParsedRdf $parsedRdf,
        RdfFormatHandlerInterface $handler,
        string $content,
    ): ParsedOntology {
        $base = parent::buildParsedOntology($parsedRdf, $handler, $content);

        return $this->processOwlFeatures($base, $parsedRdf);
    }

    /**
     * Apply OWL-specific post-processing to the base RDF parse result.
     */
    protected function processOwlFeatures(
        ParsedOntology $base,
        ParsedRdf $parsedRdf,
    ): ParsedOntology {
        $enhancedClasses = $this->owlClassEnhancer->enhance($base->classes, $parsedRdf);
        $enhancedProperties = $this->owlPropertyEnhancer->enhance($base->properties, $parsedRdf);
        $restrictions = $this->owlPropertyEnhancer->extractRestrictions($parsedRdf);

        /** @var array<string, mixed> $enhancedMetadata */
        $enhancedMetadata = $base->metadata;
        $enhancedMetadata['ontology'] = $this->ontologyMetadataExtractor->extract($parsedRdf);
        $enhancedMetadata['individuals'] = $this->individualExtractor->extract($parsedRdf);
        $enhancedMetadata['data_ranges'] = $this->dataRangeExtractor->extract($parsedRdf);

        return new ParsedOntology(
            classes: $enhancedClasses,
            properties: $enhancedProperties,
            prefixes: $base->prefixes,
            shapes: $base->shapes,
            restrictions: $restrictions,
            metadata: $enhancedMetadata,
            rawContent: $base->rawContent,
        );
    }
}
