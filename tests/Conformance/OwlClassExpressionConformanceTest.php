<?php

declare(strict_types=1);

use Youri\vandenBogert\Software\ParserOwl\OwlParser;

/*
 * OWL 2 Class Expression Conformance Tests
 * Based on W3C OWL 2 Primer and Structural Specification
 * https://www.w3.org/TR/owl2-primer/
 * https://www.w3.org/TR/owl2-syntax/
 */
describe('OWL 2 Class Expression Conformance', function () {
    beforeEach(function () {
        $this->parser = new OwlParser();
    });

    // [OWL2-Syntax S8.1.1] ObjectIntersectionOf
    describe('intersection class expressions [OWL2-Syntax S8.1.1]', function () {
        it('parses owl:intersectionOf with two named classes', function () {
            $result = $this->parser->parse(owlFixture('class-expressions/intersection-two-classes.ttl'));

            expect($result->classes)->toHaveKey('http://example.org/WorkingMother');
            $wm = $result->classes['http://example.org/WorkingMother'];
            expect($wm['class_expressions'])->toHaveKey('intersection_of');
            expect($wm['class_expressions']['intersection_of'])->toHaveCount(2);
            expect($wm['class_expressions']['intersection_of'])->toContain('http://example.org/Mother');
            expect($wm['class_expressions']['intersection_of'])->toContain('http://example.org/Worker');
        });
    });

    // [OWL2-Syntax S8.1.2] ObjectUnionOf
    describe('union class expressions [OWL2-Syntax S8.1.2]', function () {
        it('parses owl:unionOf with two named classes', function () {
            $result = $this->parser->parse(owlFixture('class-expressions/union-two-classes.ttl'));

            expect($result->classes)->toHaveKey('http://example.org/Pet');
            $pet = $result->classes['http://example.org/Pet'];
            expect($pet['class_expressions'])->toHaveKey('union_of');
            expect($pet['class_expressions']['union_of'])->toHaveCount(2);
            expect($pet['class_expressions']['union_of'])->toContain('http://example.org/Cat');
            expect($pet['class_expressions']['union_of'])->toContain('http://example.org/Dog');
        });
    });

    // [OWL2-Syntax S8.1.3] ObjectComplementOf
    describe('complement class expressions [OWL2-Syntax S8.1.3]', function () {
        it('parses owl:complementOf as single URI', function () {
            $result = $this->parser->parse(owlFixture('class-expressions/complement-class.ttl'));

            expect($result->classes)->toHaveKey('http://example.org/NonPerson');
            $np = $result->classes['http://example.org/NonPerson'];
            expect($np['class_expressions'])->toHaveKey('complement_of');
            expect($np['class_expressions']['complement_of'])->toBe('http://example.org/Person');
        });
    });

    // [OWL2-Syntax S8.1.4] ObjectOneOf
    describe('enumeration class expressions [OWL2-Syntax S8.1.4]', function () {
        it('parses owl:oneOf with member URIs', function () {
            $result = $this->parser->parse(owlFixture('class-expressions/oneof-enumeration.ttl'));

            expect($result->classes)->toHaveKey('http://example.org/Weekday');
            $wd = $result->classes['http://example.org/Weekday'];
            expect($wd['class_expressions'])->toHaveKey('one_of');
            expect($wd['class_expressions']['one_of'])->toHaveCount(3);
            expect($wd['class_expressions']['one_of'])->toContain('http://example.org/Monday');
            expect($wd['class_expressions']['one_of'])->toContain('http://example.org/Tuesday');
            expect($wd['class_expressions']['one_of'])->toContain('http://example.org/Wednesday');
        });
    });

    // [OWL2-Syntax S9.1] EquivalentClasses and DisjointClasses
    describe('equivalent and disjoint classes [OWL2-Syntax S9.1]', function () {
        beforeEach(function () {
            $this->result = $this->parser->parse(owlFixture('class-expressions/equivalent-disjoint.ttl'));
        });

        it('parses owl:equivalentClass with named class equivalence', function () {
            $person = $this->result->classes['http://example.org/Person'];
            expect($person['equivalent_classes'])->toContain('http://example.org/Human');
        });

        it('parses owl:disjointWith pairwise disjointness', function () {
            $cat = $this->result->classes['http://example.org/Cat'];
            expect($cat['disjoint_with'])->toContain('http://example.org/Person');
        });
    });

    // [OWL2-Syntax S9.1.3] AllDisjointClasses
    describe('all disjoint classes [OWL2-Syntax S9.1.3]', function () {
        it('parses owl:AllDisjointClasses with pairwise disjoint members', function () {
            $result = $this->parser->parse(owlFixture('class-expressions/all-disjoint-classes.ttl'));

            $cat = $result->classes['http://example.org/Cat'];
            expect($cat['disjoint_with'])->toContain('http://example.org/Dog');
            expect($cat['disjoint_with'])->toContain('http://example.org/Fish');

            $dog = $result->classes['http://example.org/Dog'];
            expect($dog['disjoint_with'])->toContain('http://example.org/Cat');
            expect($dog['disjoint_with'])->toContain('http://example.org/Fish');
        });
    });

    // [OWL2-Primer S3] Class Hierarchy
    describe('class hierarchy [OWL2-Primer S3]', function () {
        it('parses rdfs:subClassOf hierarchy', function () {
            $result = $this->parser->parse(owlFixture('class-expressions/subclass-hierarchy.ttl'));

            expect($result->classes)->toHaveKey('http://example.org/Dog');
            $dog = $result->classes['http://example.org/Dog'];
            expect($dog['parent_classes'])->toContain('http://example.org/Mammal');

            $mammal = $result->classes['http://example.org/Mammal'];
            expect($mammal['parent_classes'])->toContain('http://example.org/Animal');
        });
    });
});
