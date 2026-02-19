<?php

declare(strict_types=1);

use Youri\vandenBogert\Software\ParserOwl\OwlParser;

/*
 * OWL 2 Individual Conformance Tests
 * Based on W3C OWL 2 Primer Section 5 and Structural Specification S9.6
 * https://www.w3.org/TR/owl2-primer/
 * https://www.w3.org/TR/owl2-syntax/
 */
describe('OWL 2 Individual Conformance', function () {
    beforeEach(function () {
        $this->parser = new OwlParser();
    });

    // [OWL2-Syntax S9.5] Named Individuals
    describe('named individuals [OWL2-Syntax S9.5]', function () {
        beforeEach(function () {
            $this->result = $this->parser->parse(owlFixture('individuals/named-individuals.ttl'));
        });

        it('extracts owl:NamedIndividual with URI and types', function () {
            $individuals = $this->result->metadata['individuals'];
            expect($individuals)->not->toBeEmpty();

            $john = collect_individual($individuals, 'http://example.org/John');
            expect($john)->not->toBeNull();
            expect($john['types'])->toContain('http://example.org/Person');
            expect($john['types'])->toContain('http://example.org/Employee');
        });

        it('extracts rdfs:label for individual', function () {
            $individuals = $this->result->metadata['individuals'];
            $john = collect_individual($individuals, 'http://example.org/John');
            expect($john['label'])->toBe('John Doe');
        });

        it('extracts owl:sameAs assertion', function () {
            $individuals = $this->result->metadata['individuals'];
            $john = collect_individual($individuals, 'http://example.org/John');
            expect($john['same_as'])->toContain('http://example.org/JohnDoe');
        });

        it('extracts owl:differentFrom assertion', function () {
            $individuals = $this->result->metadata['individuals'];
            $jane = collect_individual($individuals, 'http://example.org/Jane');
            expect($jane['different_from'])->toContain('http://example.org/John');
        });
    });

    // [OWL2-Syntax S9.6.2] AllDifferent
    describe('AllDifferent folding [OWL2-Syntax S9.6.2]', function () {
        beforeEach(function () {
            $this->result = $this->parser->parse(owlFixture('individuals/all-different.ttl'));
        });

        it('folds owl:AllDifferent into pairwise different_from', function () {
            $individuals = $this->result->metadata['individuals'];

            $alice = collect_individual($individuals, 'http://example.org/Alice');
            expect($alice['different_from'])->toContain('http://example.org/Bob');
            expect($alice['different_from'])->toContain('http://example.org/Charlie');

            $bob = collect_individual($individuals, 'http://example.org/Bob');
            expect($bob['different_from'])->toContain('http://example.org/Alice');
            expect($bob['different_from'])->toContain('http://example.org/Charlie');
        });

        it('merges AllDifferent with existing differentFrom without duplicates', function () {
            $individuals = $this->result->metadata['individuals'];

            // Alice has both owl:differentFrom ex:Charlie AND AllDifferent(Alice,Bob,Charlie)
            $alice = collect_individual($individuals, 'http://example.org/Alice');
            $charlieCount = count(array_filter(
                $alice['different_from'],
                fn (string $u): bool => $u === 'http://example.org/Charlie'
            ));
            expect($charlieCount)->toBe(1);
        });
    });
});

