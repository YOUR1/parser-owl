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

    describe('object property assertions', function () {
        it('extracts object property assertion target individual URI', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:john a owl:NamedIndividual ;
    ex:knows ex:jane .
ex:jane a owl:NamedIndividual .';

            $result = $this->parser->parse($content);
            $john = collect_individual($result->metadata['individuals'], 'http://example.org/john');

            expect($john)->not->toBeNull();
            expect($john['properties'])->toHaveKey('http://example.org/knows');
            expect($john['properties']['http://example.org/knows'])->toContain('http://example.org/jane');
        });

        it('extracts multiple object property assertions on same individual', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:john a owl:NamedIndividual ;
    ex:knows ex:jane, ex:bob .
ex:jane a owl:NamedIndividual .
ex:bob a owl:NamedIndividual .';

            $result = $this->parser->parse($content);
            $john = collect_individual($result->metadata['individuals'], 'http://example.org/john');

            expect($john['properties']['http://example.org/knows'])->toHaveCount(2);
            expect($john['properties']['http://example.org/knows'])->toContain('http://example.org/jane');
            expect($john['properties']['http://example.org/knows'])->toContain('http://example.org/bob');
        });

        it('extracts multiple properties on same individual', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:john a owl:NamedIndividual ;
    ex:knows ex:jane ;
    ex:worksAt ex:acme .
ex:jane a owl:NamedIndividual .';

            $result = $this->parser->parse($content);
            $john = collect_individual($result->metadata['individuals'], 'http://example.org/john');

            expect($john['properties'])->toHaveKey('http://example.org/knows');
            expect($john['properties'])->toHaveKey('http://example.org/worksAt');
        });

        it('uses full URIs never prefixed in property assertions', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:john a owl:NamedIndividual ;
    ex:knows ex:jane .
ex:jane a owl:NamedIndividual .';

            $result = $this->parser->parse($content);
            $john = collect_individual($result->metadata['individuals'], 'http://example.org/john');

            foreach ($john['properties'] as $propUri => $values) {
                expect($propUri)->toStartWith('http://');
                foreach ($values as $val) {
                    if (is_string($val)) {
                        expect($val)->not->toContain('ex:');
                    }
                }
            }
        });

        it('has empty properties array by default', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:john a owl:NamedIndividual .';

            $result = $this->parser->parse($content);
            $john = collect_individual($result->metadata['individuals'], 'http://example.org/john');

            expect($john['properties'])->toBe([]);
        });
    });

    describe('data property assertions', function () {
        it('extracts data property assertion with literal value and datatype', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:john a owl:NamedIndividual ;
    ex:hasAge "30"^^xsd:integer .';

            $result = $this->parser->parse($content);
            $john = collect_individual($result->metadata['individuals'], 'http://example.org/john');

            expect($john['properties'])->toHaveKey('http://example.org/hasAge');
            expect($john['properties']['http://example.org/hasAge'])->toContain('30');
        });

        it('extracts string property values', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:john a owl:NamedIndividual ;
    ex:hasName "John Doe" .';

            $result = $this->parser->parse($content);
            $john = collect_individual($result->metadata['individuals'], 'http://example.org/john');

            expect($john['properties']['http://example.org/hasName'])->toContain('John Doe');
        });
    });

    describe('negative property assertions', function () {
        it('extracts negative object property assertion', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:john a owl:NamedIndividual .
ex:jane a owl:NamedIndividual .

_:npa a owl:NegativePropertyAssertion ;
    owl:sourceIndividual ex:john ;
    owl:assertionProperty ex:knows ;
    owl:targetIndividual ex:jane .';

            $result = $this->parser->parse($content);
            $john = collect_individual($result->metadata['individuals'], 'http://example.org/john');

            expect($john)->not->toBeNull();
            expect($john['negative_assertions'])->toHaveKey('http://example.org/knows');
            expect($john['negative_assertions']['http://example.org/knows'])->toContain('http://example.org/jane');
        });

        it('extracts negative data property assertion', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:john a owl:NamedIndividual .

_:npa a owl:NegativePropertyAssertion ;
    owl:sourceIndividual ex:john ;
    owl:assertionProperty ex:hasAge ;
    owl:targetValue "21"^^xsd:integer .';

            $result = $this->parser->parse($content);
            $john = collect_individual($result->metadata['individuals'], 'http://example.org/john');

            expect($john['negative_assertions'])->toHaveKey('http://example.org/hasAge');
            expect($john['negative_assertions']['http://example.org/hasAge'])->toContain('21');
        });

        it('distinguishes negative from positive assertions', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:john a owl:NamedIndividual ;
    ex:knows ex:bob .
ex:bob a owl:NamedIndividual .
ex:jane a owl:NamedIndividual .

_:npa a owl:NegativePropertyAssertion ;
    owl:sourceIndividual ex:john ;
    owl:assertionProperty ex:knows ;
    owl:targetIndividual ex:jane .';

            $result = $this->parser->parse($content);
            $john = collect_individual($result->metadata['individuals'], 'http://example.org/john');

            expect($john['properties']['http://example.org/knows'])->toContain('http://example.org/bob');
            expect($john['negative_assertions']['http://example.org/knows'])->toContain('http://example.org/jane');
            expect($john['properties']['http://example.org/knows'])->not->toContain('http://example.org/jane');
        });

        it('has empty negative_assertions array by default', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:john a owl:NamedIndividual .';

            $result = $this->parser->parse($content);
            $john = collect_individual($result->metadata['individuals'], 'http://example.org/john');

            expect($john['negative_assertions'])->toBe([]);
        });
    });

    describe('anonymous individuals', function () {
        it('named individual has isAnonymous false', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:john a owl:NamedIndividual .';

            $result = $this->parser->parse($content);
            $john = collect_individual($result->metadata['individuals'], 'http://example.org/john');

            expect($john)->not->toBeNull();
            expect($john['is_anonymous'])->toBeFalse();
        });

        it('extracts blank node individual with skolemized URI when option enabled', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

_:anon1 a owl:NamedIndividual, ex:Person .';

            $result = $this->parser->parse($content, ['includeSkolemizedBlankNodes' => true]);
            $individuals = $result->metadata['individuals'];

            // Should have at least one individual
            $anonymous = array_filter($individuals, fn ($i) => $i['is_anonymous'] === true);
            expect(count($anonymous))->toBeGreaterThanOrEqual(1);
        });

        it('anonymous individual is distinguishable from named individual', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:john a owl:NamedIndividual .
_:anon1 a owl:NamedIndividual .';

            $result = $this->parser->parse($content, ['includeSkolemizedBlankNodes' => true]);
            $individuals = $result->metadata['individuals'];

            $named = array_filter($individuals, fn ($i) => $i['is_anonymous'] === false);
            $anonymous = array_filter($individuals, fn ($i) => $i['is_anonymous'] === true);

            expect(count($named))->toBeGreaterThanOrEqual(1);
            expect(count($anonymous))->toBeGreaterThanOrEqual(1);
        });

        it('excludes blank node individuals by default for backward compat', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:john a owl:NamedIndividual .
_:anon1 a owl:NamedIndividual .';

            $result = $this->parser->parse($content);
            $individuals = $result->metadata['individuals'];

            // Default: only named
            foreach ($individuals as $ind) {
                expect($ind['is_anonymous'])->toBeFalse();
            }
        });

        it('all existing tests still pass without modification', function () {
            // Just a sanity check: named individual still works
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:john a owl:NamedIndividual .';

            $result = $this->parser->parse($content);

            expect($result->metadata['individuals'])->toHaveCount(1);
            expect($result->metadata['individuals'][0]['uri'])->toBe('http://example.org/john');
        });
    });
});
