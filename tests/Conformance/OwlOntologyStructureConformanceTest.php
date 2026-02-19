<?php

declare(strict_types=1);

use Youri\vandenBogert\Software\ParserOwl\OwlParser;

/*
 * OWL 2 Ontology Structure Conformance Tests
 * Based on W3C OWL 2 Structural Specification S3
 * https://www.w3.org/TR/owl2-syntax/
 */
describe('OWL 2 Ontology Structure Conformance', function () {
    beforeEach(function () {
        $this->parser = new OwlParser();
    });

    // [OWL2-Syntax S3] Ontology Header
    describe('ontology header [OWL2-Syntax S3]', function () {
        beforeEach(function () {
            $this->result = $this->parser->parse(owlFixture('ontology-structure/ontology-metadata.ttl'));
        });

        it('extracts owl:Ontology URI', function () {
            $ontologies = $this->result->metadata['ontology'];
            expect($ontologies)->not->toBeEmpty();

            $ontology = collect_ontology($ontologies, 'http://example.org/myOntology');
            expect($ontology)->not->toBeNull();
        });

        it('extracts owl:versionIRI', function () {
            $ontology = collect_ontology($this->result->metadata['ontology'], 'http://example.org/myOntology');
            expect($ontology)->not->toBeNull();
            expect($ontology['version_iri'])->toBe('http://example.org/myOntology/v1.0');
        });

        it('extracts owl:versionInfo', function () {
            $ontology = collect_ontology($this->result->metadata['ontology'], 'http://example.org/myOntology');
            expect($ontology)->not->toBeNull();
            expect($ontology['version_info'])->toBe('1.0.0');
        });
    });

    // [OWL2-Syntax S3.4] Imports
    describe('ontology imports [OWL2-Syntax S3.4]', function () {
        it('extracts multiple owl:imports URIs', function () {
            $result = $this->parser->parse(owlFixture('ontology-structure/ontology-imports.ttl'));

            $ontology = collect_ontology($result->metadata['ontology'], 'http://example.org/myOntology');
            expect($ontology)->not->toBeNull();
            expect($ontology['imports'])->toHaveCount(2);
            expect($ontology['imports'])->toContain('http://example.org/baseOntology');
            expect($ontology['imports'])->toContain('http://example.org/domainOntology');
        });
    });

    // [OWL2-Syntax] Deprecation
    describe('ontology deprecation [OWL2-Syntax]', function () {
        it('extracts owl:deprecated as boolean true', function () {
            $result = $this->parser->parse(owlFixture('ontology-structure/ontology-deprecated.ttl'));

            $ontology = collect_ontology($result->metadata['ontology'], 'http://example.org/oldOntology');
            expect($ontology)->not->toBeNull();
            expect($ontology['deprecated'])->toBeTrue();
        });

        it('defaults deprecated to false when not specified', function () {
            $result = $this->parser->parse(owlFixture('ontology-structure/ontology-metadata.ttl'));

            $ontology = collect_ontology($result->metadata['ontology'], 'http://example.org/myOntology');
            expect($ontology)->not->toBeNull();
            expect($ontology['deprecated'])->toBeFalse();
        });
    });
});
