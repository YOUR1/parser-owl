<?php

declare(strict_types=1);

use Youri\vandenBogert\Software\ParserOwl\OwlParser;

describe('IndividualExtractor', function () {
    beforeEach(function () {
        $this->parser = new OwlParser();
    });

    describe('named individuals', function () {
        it('extracts owl:NamedIndividual into individuals array', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:john a owl:NamedIndividual .';

            $result = $this->parser->parse($content);

            expect($result->metadata['individuals'])->toHaveCount(1);
        });

        it('has individual URI as full URI', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:john a owl:NamedIndividual .';

            $result = $this->parser->parse($content);

            expect($result->metadata['individuals'][0]['uri'])->toBe('http://example.org/john');
        });

        it('excludes owl:NamedIndividual from types', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Person a owl:Class .
ex:john a owl:NamedIndividual, ex:Person .';

            $result = $this->parser->parse($content);
            $john = $result->metadata['individuals'][0];

            expect($john['types'])->toContain('http://example.org/Person');
            expect($john['types'])->not->toContain('http://www.w3.org/2002/07/owl#NamedIndividual');
        });

        it('extracts label from rdfs:label', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:john a owl:NamedIndividual ;
    rdfs:label "John" .';

            $result = $this->parser->parse($content);

            expect($result->metadata['individuals'][0]['label'])->toBe('John');
        });

        it('has null label when no rdfs:label', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:john a owl:NamedIndividual .';

            $result = $this->parser->parse($content);

            expect($result->metadata['individuals'][0]['label'])->toBeNull();
        });

        it('extracts owl:sameAs into same_as array', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:john a owl:NamedIndividual ;
    owl:sameAs ex:johnDoe .';

            $result = $this->parser->parse($content);

            expect($result->metadata['individuals'][0]['same_as'])->toContain('http://example.org/johnDoe');
        });

        it('extracts owl:differentFrom into different_from array', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:john a owl:NamedIndividual ;
    owl:differentFrom ex:jane .';

            $result = $this->parser->parse($content);

            expect($result->metadata['individuals'][0]['different_from'])->toContain('http://example.org/jane');
        });

        it('has empty same_as and different_from arrays by default', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:john a owl:NamedIndividual .';

            $result = $this->parser->parse($content);

            expect($result->metadata['individuals'][0]['same_as'])->toBe([]);
            expect($result->metadata['individuals'][0]['different_from'])->toBe([]);
        });

        it('extracts multiple individuals', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:john a owl:NamedIndividual .
ex:jane a owl:NamedIndividual .';

            $result = $this->parser->parse($content);

            expect($result->metadata['individuals'])->toHaveCount(2);
        });

        it('returns empty array when no owl:NamedIndividual', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Thing a owl:Class .';

            $result = $this->parser->parse($content);

            expect($result->metadata['individuals'])->toBe([]);
        });
    });

    describe('AllDifferent folding', function () {
        it('populates different_from from owl:AllDifferent', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:a a owl:NamedIndividual .
ex:b a owl:NamedIndividual .

[] a owl:AllDifferent ;
    owl:distinctMembers (ex:a ex:b) .';

            $result = $this->parser->parse($content);
            $a = $result->metadata['individuals'][0];
            $b = $result->metadata['individuals'][1];

            expect($a['different_from'])->toContain('http://example.org/b');
            expect($b['different_from'])->toContain('http://example.org/a');
        });

        it('creates pairwise different_from with 3+ members', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:a a owl:NamedIndividual .
ex:b a owl:NamedIndividual .
ex:c a owl:NamedIndividual .

[] a owl:AllDifferent ;
    owl:distinctMembers (ex:a ex:b ex:c) .';

            $result = $this->parser->parse($content);
            $a = $result->metadata['individuals'][0];

            expect($a['different_from'])->toHaveCount(2);
            expect($a['different_from'])->toContain('http://example.org/b');
            expect($a['different_from'])->toContain('http://example.org/c');
            expect($a['different_from'])->not->toContain('http://example.org/a');
        });

        it('merges AllDifferent with existing differentFrom without duplicates', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:a a owl:NamedIndividual ;
    owl:differentFrom ex:b .
ex:b a owl:NamedIndividual .

[] a owl:AllDifferent ;
    owl:distinctMembers (ex:a ex:b) .';

            $result = $this->parser->parse($content);
            $a = $result->metadata['individuals'][0];

            $bCount = count(array_filter($a['different_from'], fn ($uri) => $uri === 'http://example.org/b'));
            expect($bCount)->toBe(1);
        });

        it('does not include self in different_from', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:a a owl:NamedIndividual .
ex:b a owl:NamedIndividual .

[] a owl:AllDifferent ;
    owl:distinctMembers (ex:a ex:b) .';

            $result = $this->parser->parse($content);
            $a = $result->metadata['individuals'][0];

            expect($a['different_from'])->not->toContain('http://example.org/a');
        });
    });
});
