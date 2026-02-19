<?php

declare(strict_types=1);

use Youri\vandenBogert\Software\ParserOwl\OwlParser;

describe('OwlParser individual extraction', function () {
    beforeEach(function () {
        $this->parser = new OwlParser();
    });

    // ── Named individual extraction ─────────────────────────────────

    describe('named individuals', function () {
        it('extracts owl:NamedIndividual type declaration into individuals array', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:john a owl:NamedIndividual .';

            $result = $this->parser->parse($content);

            expect($result->metadata['individuals'])->toHaveCount(1);
        });

        it('has individual uri as a full URI', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:john a owl:NamedIndividual .';

            $result = $this->parser->parse($content);

            expect($result->metadata['individuals'][0]['uri'])->toBe('http://example.org/john');
            expect($result->metadata['individuals'][0]['uri'])->toStartWith('http://');
        });

        it('has types excluding owl:NamedIndividual itself', function () {
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
            $john = $result->metadata['individuals'][0];

            expect($john['label'])->toBe('John');
        });

        it('has null label for individual without label', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:john a owl:NamedIndividual .';

            $result = $this->parser->parse($content);

            expect($result->metadata['individuals'][0]['label'])->toBeNull();
        });

        it('extracts owl:sameAs into same_as array with full URIs', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:john a owl:NamedIndividual ;
    owl:sameAs ex:johnDoe .';

            $result = $this->parser->parse($content);
            $john = $result->metadata['individuals'][0];

            expect($john['same_as'])->toContain('http://example.org/johnDoe');
        });

        it('extracts owl:differentFrom into different_from array with full URIs', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:john a owl:NamedIndividual ;
    owl:differentFrom ex:jane .';

            $result = $this->parser->parse($content);
            $john = $result->metadata['individuals'][0];

            expect($john['different_from'])->toContain('http://example.org/jane');
        });

        it('has empty same_as array for individual with no sameAs', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:john a owl:NamedIndividual .';

            $result = $this->parser->parse($content);

            expect($result->metadata['individuals'][0]['same_as'])->toBe([]);
        });

        it('has empty different_from array for individual with no differentFrom', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:john a owl:NamedIndividual .';

            $result = $this->parser->parse($content);

            expect($result->metadata['individuals'][0]['different_from'])->toBe([]);
        });

        it('extracts multiple individuals correctly', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:Person a owl:Class .
ex:john a owl:NamedIndividual, ex:Person ;
    rdfs:label "John" .
ex:jane a owl:NamedIndividual, ex:Person ;
    rdfs:label "Jane" .';

            $result = $this->parser->parse($content);

            expect($result->metadata['individuals'])->toHaveCount(2);
            $uris = array_column($result->metadata['individuals'], 'uri');
            expect($uris)->toContain('http://example.org/john');
            expect($uris)->toContain('http://example.org/jane');
        });

        it('returns empty individuals array when no owl:NamedIndividual', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Thing a owl:Class .';

            $result = $this->parser->parse($content);

            expect($result->metadata['individuals'])->toBe([]);
        });
    });

    // ── AllDifferent extraction and folding ──────────────────────────

    describe('AllDifferent folding', function () {
        it('populates different_from on matching individuals from owl:AllDifferent', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:a a owl:NamedIndividual ;
    rdfs:label "A" .
ex:b a owl:NamedIndividual ;
    rdfs:label "B" .

[] a owl:AllDifferent ;
    owl:distinctMembers (ex:a ex:b) .';

            $result = $this->parser->parse($content);
            $a = $result->metadata['individuals'][0];
            $b = $result->metadata['individuals'][1];

            expect($a['different_from'])->toContain('http://example.org/b');
            expect($b['different_from'])->toContain('http://example.org/a');
        });

        it('ensures each member has all OTHER members in different_from', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:a a owl:NamedIndividual .
ex:b a owl:NamedIndividual .
ex:c a owl:NamedIndividual .

[] a owl:AllDifferent ;
    owl:distinctMembers (ex:a ex:b ex:c) .';

            $result = $this->parser->parse($content);
            $a = $result->metadata['individuals'][0];

            expect($a['different_from'])->toContain('http://example.org/b');
            expect($a['different_from'])->toContain('http://example.org/c');
            expect($a['different_from'])->not->toContain('http://example.org/a');
        });

        it('merges AllDifferent with existing owl:differentFrom declarations without duplicates', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:a a owl:NamedIndividual ;
    owl:differentFrom ex:b .
ex:b a owl:NamedIndividual .
ex:c a owl:NamedIndividual .

[] a owl:AllDifferent ;
    owl:distinctMembers (ex:a ex:b ex:c) .';

            $result = $this->parser->parse($content);
            $a = $result->metadata['individuals'][0];

            $bCount = count(array_filter($a['different_from'], fn ($uri) => $uri === 'http://example.org/b'));
            expect($bCount)->toBe(1);
            expect($a['different_from'])->toContain('http://example.org/c');
        });

        it('produces correct pairwise different_from with 3+ members', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:a a owl:NamedIndividual .
ex:b a owl:NamedIndividual .
ex:c a owl:NamedIndividual .

[] a owl:AllDifferent ;
    owl:distinctMembers (ex:a ex:b ex:c) .';

            $result = $this->parser->parse($content);

            $a = $result->metadata['individuals'][0];
            $b = $result->metadata['individuals'][1];
            $c = $result->metadata['individuals'][2];

            expect($a['different_from'])->toHaveCount(2);
            expect($a['different_from'])->toContain('http://example.org/b');
            expect($a['different_from'])->toContain('http://example.org/c');

            expect($b['different_from'])->toHaveCount(2);
            expect($b['different_from'])->toContain('http://example.org/a');
            expect($b['different_from'])->toContain('http://example.org/c');

            expect($c['different_from'])->toHaveCount(2);
            expect($c['different_from'])->toContain('http://example.org/a');
            expect($c['different_from'])->toContain('http://example.org/b');
        });

        it('does not affect individual different_from when no AllDifferent is present', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:john a owl:NamedIndividual ;
    owl:differentFrom ex:jane .
ex:jane a owl:NamedIndividual .';

            $result = $this->parser->parse($content);
            $john = $result->metadata['individuals'][0];
            $jane = $result->metadata['individuals'][1];

            expect($john['different_from'])->toContain('http://example.org/jane');
            expect($jane['different_from'])->toBeEmpty();
        });
    });
});
