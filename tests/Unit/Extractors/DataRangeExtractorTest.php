<?php

declare(strict_types=1);

use Youri\vandenBogert\Software\ParserOwl\OwlParser;

describe('DataRangeExtractor', function () {
    beforeEach(function () {
        $this->parser = new OwlParser();
    });

    it('extracts rdfs:Datatype into data_ranges array', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:AdultAge a rdfs:Datatype ;
    owl:onDatatype xsd:integer .';

        $result = $this->parser->parse($content);

        expect($result->metadata['data_ranges'])->toHaveCount(1);
    });

    it('has data range URI as full URI', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:AdultAge a rdfs:Datatype ;
    owl:onDatatype xsd:integer .';

        $result = $this->parser->parse($content);

        expect($result->metadata['data_ranges'][0]['uri'])->toBe('http://example.org/AdultAge');
    });

    it('extracts owl:onDatatype as full URI', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:AdultAge a rdfs:Datatype ;
    owl:onDatatype xsd:integer .';

        $result = $this->parser->parse($content);

        expect($result->metadata['data_ranges'][0]['on_datatype'])->toBe('http://www.w3.org/2001/XMLSchema#integer');
    });

    it('extracts owl:withRestrictions as array of facet-value maps', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:AdultAge a rdfs:Datatype ;
    owl:onDatatype xsd:integer ;
    owl:withRestrictions (
        [ xsd:minInclusive "18" ]
        [ xsd:maxInclusive "150" ]
    ) .';

        $result = $this->parser->parse($content);

        expect($result->metadata['data_ranges'][0]['with_restrictions'])->toHaveCount(2);
    });

    it('extracts xsd:minInclusive facet', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:AdultAge a rdfs:Datatype ;
    owl:onDatatype xsd:integer ;
    owl:withRestrictions (
        [ xsd:minInclusive "18" ]
    ) .';

        $result = $this->parser->parse($content);
        $restrictions = $result->metadata['data_ranges'][0]['with_restrictions'];

        expect($restrictions[0]['xsd:minInclusive'])->toBe('18');
    });

    it('extracts xsd:maxInclusive facet', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:ChildAge a rdfs:Datatype ;
    owl:onDatatype xsd:integer ;
    owl:withRestrictions (
        [ xsd:maxInclusive "17" ]
    ) .';

        $result = $this->parser->parse($content);
        $restrictions = $result->metadata['data_ranges'][0]['with_restrictions'];

        expect($restrictions[0]['xsd:maxInclusive'])->toBe('17');
    });

    it('extracts xsd:minExclusive and xsd:maxExclusive facets', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:OpenRange a rdfs:Datatype ;
    owl:onDatatype xsd:integer ;
    owl:withRestrictions (
        [ xsd:minExclusive "0" ]
        [ xsd:maxExclusive "100" ]
    ) .';

        $result = $this->parser->parse($content);
        $restrictions = $result->metadata['data_ranges'][0]['with_restrictions'];

        $minExcl = null;
        $maxExcl = null;
        foreach ($restrictions as $r) {
            if (isset($r['xsd:minExclusive'])) {
                $minExcl = $r;
            }
            if (isset($r['xsd:maxExclusive'])) {
                $maxExcl = $r;
            }
        }

        expect($minExcl['xsd:minExclusive'])->toBe('0');
        expect($maxExcl['xsd:maxExclusive'])->toBe('100');
    });

    it('extracts xsd:pattern facet', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:Phone a rdfs:Datatype ;
    owl:onDatatype xsd:string ;
    owl:withRestrictions (
        [ xsd:pattern "[0-9]+" ]
    ) .';

        $result = $this->parser->parse($content);

        expect($result->metadata['data_ranges'][0]['with_restrictions'][0]['xsd:pattern'])->toBe('[0-9]+');
    });

    it('extracts xsd:length facet', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:FixedString a rdfs:Datatype ;
    owl:onDatatype xsd:string ;
    owl:withRestrictions (
        [ xsd:length "10" ]
    ) .';

        $result = $this->parser->parse($content);

        expect($result->metadata['data_ranges'][0]['with_restrictions'][0]['xsd:length'])->toBe('10');
    });

    it('extracts xsd:minLength and xsd:maxLength facets', function () {
        $content = '@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:BoundedString a rdfs:Datatype ;
    owl:onDatatype xsd:string ;
    owl:withRestrictions (
        [ xsd:minLength "2" ]
        [ xsd:maxLength "50" ]
    ) .';

        $result = $this->parser->parse($content);
        $restrictions = $result->metadata['data_ranges'][0]['with_restrictions'];

        expect($restrictions)->toHaveCount(2);

        $minLen = null;
        $maxLen = null;
        foreach ($restrictions as $r) {
            if (isset($r['xsd:minLength'])) {
                $minLen = $r;
            }
            if (isset($r['xsd:maxLength'])) {
                $maxLen = $r;
            }
        }

        expect($minLen)->not->toBeNull();
        expect($minLen['xsd:minLength'])->toBe('2');
        expect($maxLen)->not->toBeNull();
        expect($maxLen['xsd:maxLength'])->toBe('50');
    });

    it('extracts owl:datatypeComplementOf as full URI', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:NotInteger a rdfs:Datatype ;
    owl:datatypeComplementOf xsd:integer .';

        $result = $this->parser->parse($content);

        expect($result->metadata['data_ranges'][0]['complement_of'])->toBe('http://www.w3.org/2001/XMLSchema#integer');
    });

    it('has empty with_restrictions when no restrictions', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:NotInteger a rdfs:Datatype ;
    owl:datatypeComplementOf xsd:integer .';

        $result = $this->parser->parse($content);

        expect($result->metadata['data_ranges'][0]['with_restrictions'])->toBe([]);
    });

    it('returns empty array when no rdfs:Datatype', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Thing a owl:Class .';

        $result = $this->parser->parse($content);

        expect($result->metadata['data_ranges'])->toBe([]);
    });

    describe('DataIntersectionOf', function () {
        it('extracts intersection with two data ranges', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:IntRange a rdfs:Datatype ;
    owl:intersectionOf (xsd:integer xsd:nonNegativeInteger) .';

            $result = $this->parser->parse($content);
            $range = collect_data_range($result->metadata['data_ranges'], 'http://example.org/IntRange');

            expect($range)->not->toBeNull();
            expect($range['intersection_of'])->toHaveCount(2);
            expect($range['intersection_of'])->toContain('http://www.w3.org/2001/XMLSchema#integer');
            expect($range['intersection_of'])->toContain('http://www.w3.org/2001/XMLSchema#nonNegativeInteger');
        });

        it('extracts intersection with multiple data ranges', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:ComplexRange a rdfs:Datatype ;
    owl:intersectionOf (xsd:integer xsd:nonNegativeInteger xsd:int) .';

            $result = $this->parser->parse($content);
            $range = collect_data_range($result->metadata['data_ranges'], 'http://example.org/ComplexRange');

            expect($range['intersection_of'])->toHaveCount(3);
        });
    });

    describe('DataUnionOf', function () {
        it('extracts union with two data ranges', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:NumberOrString a rdfs:Datatype ;
    owl:unionOf (xsd:integer xsd:string) .';

            $result = $this->parser->parse($content);
            $range = collect_data_range($result->metadata['data_ranges'], 'http://example.org/NumberOrString');

            expect($range)->not->toBeNull();
            expect($range['union_of'])->toHaveCount(2);
            expect($range['union_of'])->toContain('http://www.w3.org/2001/XMLSchema#integer');
            expect($range['union_of'])->toContain('http://www.w3.org/2001/XMLSchema#string');
        });
    });

    describe('DataOneOf', function () {
        it('extracts literal enumeration with multiple values', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:SmallNumbers a rdfs:Datatype ;
    owl:oneOf ("1"^^xsd:integer "2"^^xsd:integer "3"^^xsd:integer) .';

            $result = $this->parser->parse($content);
            $range = collect_data_range($result->metadata['data_ranges'], 'http://example.org/SmallNumbers');

            expect($range)->not->toBeNull();
            expect($range['one_of'])->toHaveCount(3);
            expect($range['one_of'])->toContain('1');
            expect($range['one_of'])->toContain('2');
            expect($range['one_of'])->toContain('3');
        });
    });

    describe('DatatypeDefinition', function () {
        it('extracts named datatype with equivalent data range expression', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:MyInteger a rdfs:Datatype ;
    owl:equivalentClass xsd:integer .';

            $result = $this->parser->parse($content);
            $range = collect_data_range($result->metadata['data_ranges'], 'http://example.org/MyInteger');

            expect($range)->not->toBeNull();
            expect($range['equivalent_class'])->toBe('http://www.w3.org/2001/XMLSchema#integer');
        });
    });

    describe('rdf:langRange facet', function () {
        it('recognizes rdf:langRange as a facet type', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:EnglishStrings a rdfs:Datatype ;
    owl:onDatatype rdf:langString ;
    owl:withRestrictions (
        [ rdf:langRange "en" ]
    ) .';

            $result = $this->parser->parse($content);
            $range = collect_data_range($result->metadata['data_ranges'], 'http://example.org/EnglishStrings');

            expect($range)->not->toBeNull();
            expect($range['with_restrictions'][0])->toHaveKey('rdf:langRange');
            expect($range['with_restrictions'][0]['rdf:langRange'])->toBe('en');
        });
    });
});
