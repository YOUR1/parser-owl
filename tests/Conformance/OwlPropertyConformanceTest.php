<?php

declare(strict_types=1);

use Youri\vandenBogert\Software\ParserOwl\OwlParser;

/*
 * OWL 2 Property Conformance Tests
 * Based on W3C OWL 2 Primer Section 5 and Structural Specification
 * https://www.w3.org/TR/owl2-primer/
 * https://www.w3.org/TR/owl2-syntax/
 */
describe('OWL 2 Property Conformance', function () {
    beforeEach(function () {
        $this->parser = new OwlParser();
    });

    // [OWL2-Syntax S8.2] Property Types
    describe('property types [OWL2-Syntax S8.2]', function () {
        beforeEach(function () {
            $this->result = $this->parser->parse(owlFixture('property-axioms/all-property-types.ttl'));
        });

        it('detects owl:ObjectProperty as property_type object', function () {
            $knows = $this->result->properties['http://example.org/knows'];
            expect($knows['property_type'])->toBe('object');
        });

        it('detects owl:DatatypeProperty as property_type datatype', function () {
            $hasAge = $this->result->properties['http://example.org/hasAge'];
            expect($hasAge['property_type'])->toBe('datatype');
        });

        it('detects owl:AnnotationProperty as property_type annotation', function () {
            $creator = $this->result->properties['http://example.org/creator'];
            expect($creator['property_type'])->toBe('annotation');
        });
    });

    // [OWL2-Syntax S8.3] Property Characteristics
    describe('property characteristics [OWL2-Syntax S8.3]', function () {
        it('detects all 7 property characteristics', function () {
            $result = $this->parser->parse(owlFixture('property-axioms/all-characteristics.ttl'));

            expect($result->properties['http://example.org/hasMother']['is_functional'])->toBeTrue();
            expect($result->properties['http://example.org/hasSSN']['is_inverse_functional'])->toBeTrue();
            expect($result->properties['http://example.org/ancestorOf']['is_transitive'])->toBeTrue();
            expect($result->properties['http://example.org/marriedTo']['is_symmetric'])->toBeTrue();
            expect($result->properties['http://example.org/isChildOf']['is_asymmetric'])->toBeTrue();
            expect($result->properties['http://example.org/isChildOf']['is_irreflexive'])->toBeTrue();
            expect($result->properties['http://example.org/knows']['is_reflexive'])->toBeTrue();
        });
    });

    // [OWL2-Syntax S9.2] Property Relationships
    describe('property relationships [OWL2-Syntax S9.2]', function () {
        beforeEach(function () {
            $this->result = $this->parser->parse(owlFixture('property-axioms/property-relationships.ttl'));
        });

        it('extracts owl:inverseOf relationship', function () {
            $hasChild = $this->result->properties['http://example.org/hasChild'];
            expect($hasChild['inverse_of'])->toContain('http://example.org/hasParent');
        });

        it('extracts owl:equivalentProperty relationship', function () {
            $hasFather = $this->result->properties['http://example.org/hasFather'];
            expect($hasFather['equivalent_properties'])->toContain('http://example.org/hasDad');
        });

        it('extracts owl:propertyDisjointWith relationship', function () {
            $likes = $this->result->properties['http://example.org/likes'];
            expect($likes['property_disjoint_with'])->toContain('http://example.org/dislikes');
        });

        it('extracts owl:propertyChainAxiom with correct URIs', function () {
            $hasGrandparent = $this->result->properties['http://example.org/hasGrandparent'];
            expect($hasGrandparent['property_chain_axiom'])->toHaveCount(2);
            expect($hasGrandparent['property_chain_axiom'][0])->toBe('http://example.org/hasParent');
            expect($hasGrandparent['property_chain_axiom'][1])->toBe('http://example.org/hasParent');
        });
    });
});
