<?php

declare(strict_types=1);

use Youri\vandenBogert\Software\ParserOwl\OwlParser;

describe('OntologyMetadataExtractor', function () {
    beforeEach(function () {
        $this->parser = new OwlParser();
    });

    it('extracts owl:Ontology into ontology array', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:myOntology a owl:Ontology .';

        $result = $this->parser->parse($content);

        expect($result->metadata['ontology'])->toHaveCount(1);
    });

    it('has ontology URI as full URI', function () {
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

    it('extracts multiple owl:imports', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:myOntology a owl:Ontology ;
    owl:imports ex:other, ex:another .';

        $result = $this->parser->parse($content);

        expect($result->metadata['ontology'][0]['imports'])->toHaveCount(2);
    });

    it('extracts owl:versionIRI as full URI', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:myOntology a owl:Ontology ;
    owl:versionIRI <http://example.org/myOntology/1.0> .';

        $result = $this->parser->parse($content);

        expect($result->metadata['ontology'][0]['version_iri'])->toBe('http://example.org/myOntology/1.0');
    });

    it('extracts owl:versionInfo as string', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:myOntology a owl:Ontology ;
    owl:versionInfo "1.0.0" .';

        $result = $this->parser->parse($content);

        expect($result->metadata['ontology'][0]['version_info'])->toBe('1.0.0');
    });

    it('extracts owl:deprecated true as boolean true', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:oldOntology a owl:Ontology ;
    owl:deprecated "true"^^xsd:boolean .';

        $result = $this->parser->parse($content);

        expect($result->metadata['ontology'][0]['deprecated'])->toBeTrue();
    });

    it('has deprecated false as default', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:myOntology a owl:Ontology .';

        $result = $this->parser->parse($content);

        expect($result->metadata['ontology'][0]['deprecated'])->toBeFalse();
    });

    it('has null version_iri when not declared', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:myOntology a owl:Ontology .';

        $result = $this->parser->parse($content);

        expect($result->metadata['ontology'][0]['version_iri'])->toBeNull();
    });

    it('has null version_info when not declared', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:myOntology a owl:Ontology .';

        $result = $this->parser->parse($content);

        expect($result->metadata['ontology'][0]['version_info'])->toBeNull();
    });

    it('has empty imports array when no imports', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:myOntology a owl:Ontology .';

        $result = $this->parser->parse($content);

        expect($result->metadata['ontology'][0]['imports'])->toBe([]);
    });

    it('returns empty array when no owl:Ontology', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Thing a owl:Class .';

        $result = $this->parser->parse($content);

        expect($result->metadata['ontology'])->toBe([]);
    });

    it('returns ontology as array even with single ontology', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:myOntology a owl:Ontology .';

        $result = $this->parser->parse($content);

        expect($result->metadata['ontology'])->toBeArray();
        expect($result->metadata['ontology'][0])->toBeArray();
        expect($result->metadata['ontology'][0])->toHaveKeys(['uri', 'imports', 'version_iri', 'version_info', 'deprecated']);
    });

    describe('owl:priorVersion', function () {
        it('extracts prior version IRI from ontology metadata', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:myOntology a owl:Ontology ;
    owl:priorVersion <http://example.org/myOntology/0.9> .';

            $result = $this->parser->parse($content);
            $ont = collect_ontology($result->metadata['ontology'], 'http://example.org/myOntology');

            expect($ont)->not->toBeNull();
            expect($ont['prior_version'])->toBe('http://example.org/myOntology/0.9');
        });

        it('has null prior_version when not declared', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:myOntology a owl:Ontology .';

            $result = $this->parser->parse($content);
            $ont = collect_ontology($result->metadata['ontology'], 'http://example.org/myOntology');

            expect($ont['prior_version'])->toBeNull();
        });
    });

    describe('compatibility IRIs', function () {
        it('extracts owl:backwardCompatibleWith IRI', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:myOntology a owl:Ontology ;
    owl:backwardCompatibleWith <http://example.org/myOntology/1.0> .';

            $result = $this->parser->parse($content);
            $ont = collect_ontology($result->metadata['ontology'], 'http://example.org/myOntology');

            expect($ont['backward_compatible_with'])->toContain('http://example.org/myOntology/1.0');
        });

        it('extracts owl:incompatibleWith IRI', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:myOntology a owl:Ontology ;
    owl:incompatibleWith <http://example.org/oldOntology/0.5> .';

            $result = $this->parser->parse($content);
            $ont = collect_ontology($result->metadata['ontology'], 'http://example.org/myOntology');

            expect($ont['incompatible_with'])->toContain('http://example.org/oldOntology/0.5');
        });

        it('supports multiple compatibility IRIs', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:myOntology a owl:Ontology ;
    owl:backwardCompatibleWith <http://example.org/myOntology/1.0>, <http://example.org/myOntology/1.1> .';

            $result = $this->parser->parse($content);
            $ont = collect_ontology($result->metadata['ontology'], 'http://example.org/myOntology');

            expect($ont['backward_compatible_with'])->toHaveCount(2);
        });

        it('has empty arrays when no compatibility IRIs declared', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:myOntology a owl:Ontology .';

            $result = $this->parser->parse($content);
            $ont = collect_ontology($result->metadata['ontology'], 'http://example.org/myOntology');

            expect($ont['backward_compatible_with'])->toBe([]);
            expect($ont['incompatible_with'])->toBe([]);
        });
    });

    describe('axiom annotations', function () {
        it('detects owl:Axiom reification pattern', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:Dog a owl:Class ;
    rdfs:subClassOf ex:Animal .
ex:Animal a owl:Class .

_:ax a owl:Axiom ;
    owl:annotatedSource ex:Dog ;
    owl:annotatedProperty rdfs:subClassOf ;
    owl:annotatedTarget ex:Animal ;
    rdfs:comment "Dogs are animals" .';

            $result = $this->parser->parse($content);

            expect($result->metadata)->toHaveKey('axiom_annotations');
            expect($result->metadata['axiom_annotations'])->toHaveCount(1);
        });

        it('extracts annotation property and value', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:Dog a owl:Class ;
    rdfs:subClassOf ex:Animal .
ex:Animal a owl:Class .

_:ax a owl:Axiom ;
    owl:annotatedSource ex:Dog ;
    owl:annotatedProperty rdfs:subClassOf ;
    owl:annotatedTarget ex:Animal ;
    rdfs:comment "Dogs are animals" .';

            $result = $this->parser->parse($content);
            $axiomAnnotation = $result->metadata['axiom_annotations'][0];

            expect($axiomAnnotation['annotations'])->not->toBeEmpty();
            expect($axiomAnnotation['annotations'][0]['value'])->toBe('Dogs are animals');
        });

        it('preserves axiom source as full URI', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:Dog a owl:Class ;
    rdfs:subClassOf ex:Animal .
ex:Animal a owl:Class .

_:ax a owl:Axiom ;
    owl:annotatedSource ex:Dog ;
    owl:annotatedProperty rdfs:subClassOf ;
    owl:annotatedTarget ex:Animal ;
    rdfs:comment "Dogs are animals" .';

            $result = $this->parser->parse($content);
            $axiomAnnotation = $result->metadata['axiom_annotations'][0];

            expect($axiomAnnotation['source'])->toBe('http://example.org/Dog');
        });

        it('preserves axiom predicate as full URI', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:Dog a owl:Class ;
    rdfs:subClassOf ex:Animal .
ex:Animal a owl:Class .

_:ax a owl:Axiom ;
    owl:annotatedSource ex:Dog ;
    owl:annotatedProperty rdfs:subClassOf ;
    owl:annotatedTarget ex:Animal ;
    rdfs:comment "Dogs are animals" .';

            $result = $this->parser->parse($content);
            $axiomAnnotation = $result->metadata['axiom_annotations'][0];

            expect($axiomAnnotation['property'])->toBe('http://www.w3.org/2000/01/rdf-schema#subClassOf');
        });

        it('preserves axiom object as full URI', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:Dog a owl:Class ;
    rdfs:subClassOf ex:Animal .
ex:Animal a owl:Class .

_:ax a owl:Axiom ;
    owl:annotatedSource ex:Dog ;
    owl:annotatedProperty rdfs:subClassOf ;
    owl:annotatedTarget ex:Animal ;
    rdfs:comment "Dogs are animals" .';

            $result = $this->parser->parse($content);
            $axiomAnnotation = $result->metadata['axiom_annotations'][0];

            expect($axiomAnnotation['target'])->toBe('http://example.org/Animal');
        });

        it('returns empty axiom_annotations array when none exist', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:myOntology a owl:Ontology .';

            $result = $this->parser->parse($content);

            expect($result->metadata['axiom_annotations'])->toBe([]);
        });
    });
});
