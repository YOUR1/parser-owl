<?php

declare(strict_types=1);

use Youri\vandenBogert\Software\ParserOwl\OwlParser;

describe('OwlParser ontology metadata extraction', function () {
    beforeEach(function () {
        $this->parser = new OwlParser();
    });

    it('extracts owl:Ontology type declaration into ontology array', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:myOntology a owl:Ontology .';

        $result = $this->parser->parse($content);

        expect($result->metadata['ontology'])->not->toBeEmpty();
        expect($result->metadata['ontology'])->toHaveCount(1);
    });

    it('extracts ontology uri field as the ontology IRI', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:myOntology a owl:Ontology .';

        $result = $this->parser->parse($content);

        expect($result->metadata['ontology'][0]['uri'])->toBe('http://example.org/myOntology');
    });

    it('extracts owl:imports as array of full URIs', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:myOntology a owl:Ontology ;
    owl:imports ex:other .';

        $result = $this->parser->parse($content);

        expect($result->metadata['ontology'][0]['imports'])->toContain('http://example.org/other');
    });

    it('extracts multiple owl:imports on single ontology', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:myOntology a owl:Ontology ;
    owl:imports ex:other, ex:another .';

        $result = $this->parser->parse($content);

        expect($result->metadata['ontology'][0]['imports'])->toContain('http://example.org/other');
        expect($result->metadata['ontology'][0]['imports'])->toContain('http://example.org/another');
        expect($result->metadata['ontology'][0]['imports'])->toHaveCount(2);
    });

    it('extracts owl:versionIRI as a full URI', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:myOntology a owl:Ontology ;
    owl:versionIRI <http://example.org/myOntology/1.0> .';

        $result = $this->parser->parse($content);

        expect($result->metadata['ontology'][0]['version_iri'])->toBe('http://example.org/myOntology/1.0');
    });

    it('extracts owl:versionInfo as a string', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:myOntology a owl:Ontology ;
    owl:versionInfo "1.0.0" .';

        $result = $this->parser->parse($content);

        expect($result->metadata['ontology'][0]['version_info'])->toBe('1.0.0');
        expect($result->metadata['ontology'][0]['version_info'])->toBeString();
    });

    it('extracts owl:deprecated true as boolean true', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:oldOntology a owl:Ontology ;
    owl:deprecated "true"^^xsd:boolean .';

        $result = $this->parser->parse($content);

        expect($result->metadata['ontology'][0]['deprecated'])->toBeTrue();
        expect($result->metadata['ontology'][0]['deprecated'])->toBeBool();
    });

    it('has deprecated false as default when not declared', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:myOntology a owl:Ontology .';

        $result = $this->parser->parse($content);

        expect($result->metadata['ontology'][0]['deprecated'])->toBeFalse();
    });

    it('returns empty ontology array when no owl:Ontology declaration', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Thing a owl:Class .';

        $result = $this->parser->parse($content);

        expect($result->metadata['ontology'])->toBe([]);
    });

    it('has empty imports array for ontology without imports', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:myOntology a owl:Ontology ;
    owl:versionInfo "1.0" .';

        $result = $this->parser->parse($content);

        expect($result->metadata['ontology'][0]['imports'])->toBe([]);
    });

    it('has null version_iri for ontology without versionIRI', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:myOntology a owl:Ontology ;
    owl:versionInfo "1.0" .';

        $result = $this->parser->parse($content);

        expect($result->metadata['ontology'][0]['version_iri'])->toBeNull();
    });

    it('has null version_info for ontology without versionInfo', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:myOntology a owl:Ontology ;
    owl:versionIRI <http://example.org/myOntology/1.0> .';

        $result = $this->parser->parse($content);

        expect($result->metadata['ontology'][0]['version_info'])->toBeNull();
    });

    it('returns ontology key as an array even with single ontology', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:myOntology a owl:Ontology ;
    owl:versionInfo "1.0" .';

        $result = $this->parser->parse($content);

        expect($result->metadata['ontology'])->toBeArray();
        expect($result->metadata['ontology'][0])->toBeArray();
        expect($result->metadata['ontology'][0])->toHaveKeys(['uri', 'imports', 'version_iri', 'version_info', 'deprecated']);
    });
});
