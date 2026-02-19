<?php

declare(strict_types=1);

use Youri\vandenBogert\Software\ParserOwl\OwlParser;

describe('OwlParser data range extraction', function () {
    beforeEach(function () {
        $this->parser = new OwlParser();
    });

    it('extracts rdfs:Datatype with owl:onDatatype into data_ranges array', function () {
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

        expect($result->metadata['data_ranges'])->toHaveCount(1);
    });

    it('has data range uri as a full URI', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:AdultAge a rdfs:Datatype ;
    owl:onDatatype xsd:integer .';

        $result = $this->parser->parse($content);

        expect($result->metadata['data_ranges'][0]['uri'])->toBe('http://example.org/AdultAge');
        expect($result->metadata['data_ranges'][0]['uri'])->toStartWith('http://');
    });

    it('extracts owl:onDatatype as a full URI', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:AdultAge a rdfs:Datatype ;
    owl:onDatatype xsd:integer .';

        $result = $this->parser->parse($content);

        expect($result->metadata['data_ranges'][0]['on_datatype'])->toBe('http://www.w3.org/2001/XMLSchema#integer');
    });

    it('extracts owl:withRestrictions as array of facet value maps', function () {
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
        expect($result->metadata['data_ranges'][0]['with_restrictions'])->toBeArray();
    });

    it('extracts xsd:minInclusive facet in withRestrictions', function () {
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
        $minRestriction = null;
        foreach ($restrictions as $r) {
            if (isset($r['xsd:minInclusive'])) {
                $minRestriction = $r;
                break;
            }
        }

        expect($minRestriction)->not->toBeNull();
        expect($minRestriction['xsd:minInclusive'])->toBe('18');
    });

    it('extracts xsd:maxInclusive facet in withRestrictions', function () {
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
        $maxRestriction = null;
        foreach ($restrictions as $r) {
            if (isset($r['xsd:maxInclusive'])) {
                $maxRestriction = $r;
                break;
            }
        }

        expect($maxRestriction)->not->toBeNull();
        expect($maxRestriction['xsd:maxInclusive'])->toBe('17');
    });

    it('extracts xsd:pattern facet in withRestrictions', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:PhoneNumber a rdfs:Datatype ;
    owl:onDatatype xsd:string ;
    owl:withRestrictions (
        [ xsd:pattern "[0-9]{3}-[0-9]{4}" ]
    ) .';

        $result = $this->parser->parse($content);

        $restrictions = $result->metadata['data_ranges'][0]['with_restrictions'];
        $patternRestriction = null;
        foreach ($restrictions as $r) {
            if (isset($r['xsd:pattern'])) {
                $patternRestriction = $r;
                break;
            }
        }

        expect($patternRestriction)->not->toBeNull();
        expect($patternRestriction['xsd:pattern'])->toBe('[0-9]{3}-[0-9]{4}');
    });

    it('extracts xsd:minExclusive and xsd:maxExclusive facets in withRestrictions', function () {
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

        expect($minExcl)->not->toBeNull();
        expect($minExcl['xsd:minExclusive'])->toBe('0');
        expect($maxExcl)->not->toBeNull();
        expect($maxExcl['xsd:maxExclusive'])->toBe('100');
    });

    it('extracts xsd:length, xsd:minLength, xsd:maxLength facets in withRestrictions', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:ConstrainedString a rdfs:Datatype ;
    owl:onDatatype xsd:string ;
    owl:withRestrictions (
        [ xsd:minLength "1" ]
        [ xsd:maxLength "255" ]
    ) .

ex:FixedString a rdfs:Datatype ;
    owl:onDatatype xsd:string ;
    owl:withRestrictions (
        [ xsd:length "10" ]
    ) .';

        $result = $this->parser->parse($content);

        // Find ConstrainedString by URI
        $constrained = null;
        $fixed = null;
        foreach ($result->metadata['data_ranges'] as $dr) {
            if ($dr['uri'] === 'http://example.org/ConstrainedString') {
                $constrained = $dr;
            }
            if ($dr['uri'] === 'http://example.org/FixedString') {
                $fixed = $dr;
            }
        }

        expect($constrained)->not->toBeNull();
        $minLen = null;
        $maxLen = null;
        foreach ($constrained['with_restrictions'] as $r) {
            if (isset($r['xsd:minLength'])) {
                $minLen = $r;
            }
            if (isset($r['xsd:maxLength'])) {
                $maxLen = $r;
            }
        }
        expect($minLen['xsd:minLength'])->toBe('1');
        expect($maxLen['xsd:maxLength'])->toBe('255');

        expect($fixed)->not->toBeNull();
        $length = null;
        foreach ($fixed['with_restrictions'] as $r) {
            if (isset($r['xsd:length'])) {
                $length = $r;
            }
        }
        expect($length['xsd:length'])->toBe('10');
    });

    it('extracts owl:datatypeComplementOf as a full URI', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:NotInteger a rdfs:Datatype ;
    owl:datatypeComplementOf xsd:integer .';

        $result = $this->parser->parse($content);

        expect($result->metadata['data_ranges'][0]['complement_of'])->toBe('http://www.w3.org/2001/XMLSchema#integer');
    });

    it('has empty with_restrictions for data range without withRestrictions', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:NotInteger a rdfs:Datatype ;
    owl:datatypeComplementOf xsd:integer .';

        $result = $this->parser->parse($content);

        expect($result->metadata['data_ranges'][0]['with_restrictions'])->toBe([]);
    });

    it('returns empty data_ranges array when no rdfs:Datatype', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Thing a owl:Class .';

        $result = $this->parser->parse($content);

        expect($result->metadata['data_ranges'])->toBe([]);
    });
});
