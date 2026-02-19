<?php

declare(strict_types=1);

use Youri\vandenBogert\Software\ParserOwl\OwlParser;

/*
 * OWL 2 Data Range Conformance Tests
 * Based on W3C OWL 2 Structural Specification S7
 * https://www.w3.org/TR/owl2-syntax/
 */
describe('OWL 2 Data Range Conformance', function () {
    beforeEach(function () {
        $this->parser = new OwlParser();
    });

    // [OWL2-Syntax S7] Datatype Definitions
    describe('datatype definitions [OWL2-Syntax S7]', function () {
        beforeEach(function () {
            $this->result = $this->parser->parse(owlFixture('data-ranges/datatype-restrictions.ttl'));
        });

        it('extracts rdfs:Datatype with owl:onDatatype and facet restrictions', function () {
            $dataRanges = $this->result->metadata['data_ranges'];
            expect($dataRanges)->not->toBeEmpty();

            $adultAge = collect_data_range($dataRanges, 'http://example.org/AdultAge');
            expect($adultAge)->not->toBeNull();
            expect($adultAge['on_datatype'])->toBe('http://www.w3.org/2001/XMLSchema#integer');
        });

        it('extracts xsd facet restrictions from owl:withRestrictions', function () {
            $dataRanges = $this->result->metadata['data_ranges'];
            $adultAge = collect_data_range($dataRanges, 'http://example.org/AdultAge');
            expect($adultAge)->not->toBeNull();
            expect($adultAge['with_restrictions'])->toHaveCount(2);
            expect($adultAge['with_restrictions'][0])->toHaveKey('xsd:minInclusive');
            expect($adultAge['with_restrictions'][0]['xsd:minInclusive'])->toBe('18');
            expect($adultAge['with_restrictions'][1])->toHaveKey('xsd:maxInclusive');
            expect($adultAge['with_restrictions'][1]['xsd:maxInclusive'])->toBe('150');
        });
    });

    // [OWL2-Syntax S7.2] DataComplementOf
    describe('datatype complement [OWL2-Syntax S7.2]', function () {
        it('extracts owl:datatypeComplementOf URI', function () {
            $result = $this->parser->parse(owlFixture('data-ranges/datatype-complement.ttl'));

            $dataRanges = $result->metadata['data_ranges'];
            expect($dataRanges)->not->toBeEmpty();

            $nonInteger = collect_data_range($dataRanges, 'http://example.org/NonInteger');
            expect($nonInteger)->not->toBeNull();
            expect($nonInteger['complement_of'])->toBe('http://www.w3.org/2001/XMLSchema#integer');
        });
    });
});
