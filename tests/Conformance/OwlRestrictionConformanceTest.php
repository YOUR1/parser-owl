<?php

declare(strict_types=1);

use Youri\vandenBogert\Software\ParserOwl\OwlParser;

/*
 * OWL 2 Restriction Conformance Tests
 * Based on W3C OWL 2 Primer Section 6 and Structural Specification S8.4-S8.5
 * https://www.w3.org/TR/owl2-primer/
 * https://www.w3.org/TR/owl2-syntax/
 */
describe('OWL 2 Restriction Conformance', function () {
    beforeEach(function () {
        $this->parser = new OwlParser();
    });

    // [OWL2-Syntax S8.5] Cardinality Restrictions
    describe('cardinality restrictions [OWL2-Syntax S8.5]', function () {
        beforeEach(function () {
            $this->result = $this->parser->parse(owlFixture('restrictions/cardinality-restrictions.ttl'));
        });

        it('extracts owl:minCardinality restriction', function () {
            $parent = $this->result->classes['http://example.org/Parent'];
            expect($parent['constraints'])->not->toBeEmpty();

            $constraint = $parent['constraints'][0];
            expect($constraint['type'])->toBe('owl:Restriction');
            expect($constraint['property'])->toBe('http://example.org/hasChild');
            expect($constraint['min_cardinality'])->toBe('1');
        });

        it('extracts owl:cardinality (exact) restriction', function () {
            $single = $this->result->classes['http://example.org/SingleParent'];
            $constraint = $single['constraints'][0];
            expect($constraint['cardinality'])->toBe('1');
        });

        it('extracts owl:maxCardinality restriction', function () {
            $small = $this->result->classes['http://example.org/SmallFamily'];
            $constraint = $small['constraints'][0];
            expect($constraint['max_cardinality'])->toBe('3');
        });
    });

    // [OWL2-Syntax S8.4] Value Restrictions
    describe('value restrictions [OWL2-Syntax S8.4]', function () {
        beforeEach(function () {
            $this->result = $this->parser->parse(owlFixture('restrictions/value-restrictions.ttl'));
        });

        it('extracts owl:allValuesFrom (universal) restriction', function () {
            $cls = $this->result->classes['http://example.org/PersonWithHumanParent'];
            $constraint = $cls['constraints'][0];
            expect($constraint['all_values_from'])->toBe('http://example.org/Human');
        });

        it('extracts owl:someValuesFrom (existential) restriction', function () {
            $cls = $this->result->classes['http://example.org/PersonWithSomeParent'];
            $constraint = $cls['constraints'][0];
            expect($constraint['some_values_from'])->toBe('http://example.org/Person');
        });
    });

    // [OWL2-Syntax S8.5] Qualified Cardinality
    describe('qualified cardinality restrictions [OWL2-Syntax S8.5]', function () {
        it('extracts owl:qualifiedCardinality with owl:onClass', function () {
            $result = $this->parser->parse(owlFixture('restrictions/qualified-cardinality.ttl'));

            $cls = $result->classes['http://example.org/ParentOfBoys'];
            $constraint = $cls['constraints'][0];
            expect($constraint['qualified_cardinality'])->toBe('2');
            expect($constraint['on_class'])->toBe('http://example.org/Boy');
        });
    });

    // [OWL2-Syntax S8.4.3] HasValue
    describe('has value restriction [OWL2-Syntax S8.4.3]', function () {
        it('extracts owl:hasValue as URI for named individual', function () {
            $result = $this->parser->parse(owlFixture('restrictions/has-value.ttl'));

            $cls = $result->classes['http://example.org/Australian'];
            $constraint = $cls['constraints'][0];
            expect($constraint['type'])->toBe('owl:Restriction');
            expect($constraint['property'])->toBe('http://example.org/country');
            expect($constraint['value'])->toBe('http://example.org/Australia');
        });
    });

    // [OWL2-Syntax S8.4.4] hasSelf
    describe('self restriction [OWL2-Syntax S8.4.4]', function () {
        it('extracts owl:hasSelf as boolean true', function () {
            $result = $this->parser->parse(owlFixture('restrictions/has-value-self.ttl'));

            $cls = $result->classes['http://example.org/NarcissisticPerson'];
            $constraint = $cls['constraints'][0];
            expect($constraint['has_self'])->toBeTrue();
        });
    });

    // Global restrictions
    describe('global restrictions via ParsedOntology.restrictions', function () {
        it('extracts owl:Restriction resources into restrictions array', function () {
            $result = $this->parser->parse(owlFixture('restrictions/cardinality-restrictions.ttl'));

            expect($result->restrictions)->not->toBeEmpty();
        });
    });
});
