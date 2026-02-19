<?php

declare(strict_types=1);

use Youri\vandenBogert\Software\ParserOwl\OwlParser;

/*
 * OWL 2 Pipeline Integration Conformance Tests
 * Validates the full OwlParser pipeline with a comprehensive ontology.
 * https://www.w3.org/TR/owl2-primer/
 */
describe('OWL 2 Pipeline Integration Conformance', function () {
    beforeEach(function () {
        $this->parser = new OwlParser();
    });

    // Full pipeline test
    describe('comprehensive OWL ontology parsing', function () {
        it('parses a comprehensive OWL ontology with all construct types', function () {
            $result = $this->parser->parse(owlFixture('integration/comprehensive-owl.ttl'));

            // Ontology metadata
            $ontologies = $result->metadata['ontology'];
            expect($ontologies)->not->toBeEmpty();

            $ontology = collect_ontology($ontologies, 'http://example.org/familyOntology');
            expect($ontology)->not->toBeNull();
            expect($ontology['version_info'])->toBe('2.0.0');
            expect($ontology['imports'])->toContain('http://example.org/basePersonOntology');

            // Classes
            expect($result->classes)->toHaveKey('http://example.org/Person');
            expect($result->classes)->toHaveKey('http://example.org/Parent');
            expect($result->classes)->toHaveKey('http://example.org/Mother');
            expect($result->classes)->toHaveKey('http://example.org/Father');

            // Class hierarchy
            $mother = $result->classes['http://example.org/Mother'];
            expect($mother['parent_classes'])->toContain('http://example.org/Parent');

            // Class expressions (union)
            $parentOrGuardian = $result->classes['http://example.org/ParentOrGuardian'];
            expect($parentOrGuardian['class_expressions']['union_of'])->toHaveCount(2);

            // Disjoint classes
            expect($mother['disjoint_with'])->toContain('http://example.org/Father');

            // Properties
            expect($result->properties)->toHaveKey('http://example.org/hasChild');
            expect($result->properties)->toHaveKey('http://example.org/hasParent');
            expect($result->properties)->toHaveKey('http://example.org/hasAge');

            $hasParent = $result->properties['http://example.org/hasParent'];
            expect($hasParent['is_functional'])->toBeTrue();
            expect($hasParent['property_type'])->toBe('object');

            $hasChild = $result->properties['http://example.org/hasChild'];
            expect($hasChild['inverse_of'])->toContain('http://example.org/hasParent');

            // Restrictions (on Parent class)
            $parent = $result->classes['http://example.org/Parent'];
            expect($parent['constraints'])->not->toBeEmpty();
            $constraint = $parent['constraints'][0];
            expect($constraint['type'])->toBe('owl:Restriction');
            expect($constraint['property'])->toBe('http://example.org/hasChild');
            expect($constraint['min_cardinality'])->toBe('1');

            // Individuals
            $individuals = $result->metadata['individuals'];
            expect($individuals)->not->toBeEmpty();

            $alice = collect_individual($individuals, 'http://example.org/Alice');
            expect($alice)->not->toBeNull();
            expect($alice['label'])->toBe('Alice');
            expect($alice['types'])->toContain('http://example.org/Mother');

            // Data ranges
            $dataRanges = $result->metadata['data_ranges'];
            expect($dataRanges)->not->toBeEmpty();

            $validAge = collect_data_range($dataRanges, 'http://example.org/ValidAge');
            expect($validAge)->not->toBeNull();
            expect($validAge['on_datatype'])->toBe('http://www.w3.org/2001/XMLSchema#integer');
        });
    });

    // canParse and getSupportedFormats
    describe('parser API', function () {
        it('canParse returns true for OWL content', function () {
            $content = owlFixture('integration/comprehensive-owl.ttl');
            expect($this->parser->canParse($content))->toBeTrue();
        });

        it('getSupportedFormats returns inherited RdfParser formats', function () {
            $formats = $this->parser->getSupportedFormats();
            expect($formats)->not->toBeEmpty();
            expect($formats)->toContain('turtle');
        });
    });
});
