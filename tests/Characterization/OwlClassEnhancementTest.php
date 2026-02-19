<?php

declare(strict_types=1);

use Youri\vandenBogert\Software\ParserOwl\OwlParser;

describe('OwlParser class enhancement', function () {
    beforeEach(function () {
        $this->parser = new OwlParser();
    });

    // ── Equivalent class extraction ─────────────────────────────────

    describe('equivalent classes', function () {
        it('extracts owl:equivalentClass with a named class URI into equivalent_classes array', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Human a owl:Class ;
    owl:equivalentClass ex:Person .
ex:Person a owl:Class .';

            $result = $this->parser->parse($content);
            $human = $result->classes['http://example.org/Human'];

            expect($human['equivalent_classes'])->toContain('http://example.org/Person');
        });

        it('does NOT include blank node class expressions in equivalent_classes', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:WorkingMother a owl:Class ;
    owl:equivalentClass [
        owl:intersectionOf (ex:Mother ex:Worker)
    ] .
ex:Mother a owl:Class .
ex:Worker a owl:Class .';

            $result = $this->parser->parse($content);
            $wm = $result->classes['http://example.org/WorkingMother'];

            expect($wm['equivalent_classes'])->toBeEmpty();
            expect($wm['class_expressions'])->toHaveKey('intersection_of');
        });

        it('extracts multiple equivalent classes on a single class', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Human a owl:Class ;
    owl:equivalentClass ex:Person, ex:HomoSapiens .
ex:Person a owl:Class .
ex:HomoSapiens a owl:Class .';

            $result = $this->parser->parse($content);
            $human = $result->classes['http://example.org/Human'];

            expect($human['equivalent_classes'])->toContain('http://example.org/Person');
            expect($human['equivalent_classes'])->toContain('http://example.org/HomoSapiens');
            expect($human['equivalent_classes'])->toHaveCount(2);
        });

        it('has equivalent class URIs as full URIs not prefixed notation', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Human a owl:Class ;
    owl:equivalentClass ex:Person .
ex:Person a owl:Class .';

            $result = $this->parser->parse($content);
            $human = $result->classes['http://example.org/Human'];

            expect($human['equivalent_classes'][0])->toBe('http://example.org/Person');
            expect($human['equivalent_classes'][0])->toStartWith('http://');
        });

        it('has empty equivalent_classes array for class with no equivalents', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Animal a owl:Class .';

            $result = $this->parser->parse($content);
            $animal = $result->classes['http://example.org/Animal'];

            expect($animal['equivalent_classes'])->toBeEmpty();
            expect($animal['equivalent_classes'])->toBe([]);
        });
    });

    // ── Disjoint class extraction ───────────────────────────────────

    describe('disjoint classes', function () {
        it('extracts owl:disjointWith into disjoint_with array', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Cat a owl:Class ;
    owl:disjointWith ex:Dog .
ex:Dog a owl:Class .';

            $result = $this->parser->parse($content);
            $cat = $result->classes['http://example.org/Cat'];

            expect($cat['disjoint_with'])->toContain('http://example.org/Dog');
        });

        it('extracts disjoint with multiple classes', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Cat a owl:Class ;
    owl:disjointWith ex:Dog, ex:Fish .
ex:Dog a owl:Class .
ex:Fish a owl:Class .';

            $result = $this->parser->parse($content);
            $cat = $result->classes['http://example.org/Cat'];

            expect($cat['disjoint_with'])->toContain('http://example.org/Dog');
            expect($cat['disjoint_with'])->toContain('http://example.org/Fish');
            expect($cat['disjoint_with'])->toHaveCount(2);
        });

        it('has disjoint class URIs as full URIs', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Cat a owl:Class ;
    owl:disjointWith ex:Dog .
ex:Dog a owl:Class .';

            $result = $this->parser->parse($content);
            $cat = $result->classes['http://example.org/Cat'];

            expect($cat['disjoint_with'][0])->toBe('http://example.org/Dog');
            expect($cat['disjoint_with'][0])->toStartWith('http://');
        });

        it('has empty disjoint_with array for class with no disjoint declarations', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Animal a owl:Class .';

            $result = $this->parser->parse($content);
            $animal = $result->classes['http://example.org/Animal'];

            expect($animal['disjoint_with'])->toBeEmpty();
            expect($animal['disjoint_with'])->toBe([]);
        });
    });

    // ── Class expression extraction ─────────────────────────────────

    describe('class expressions', function () {
        it('extracts owl:intersectionOf via owl:equivalentClass blank node', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:WorkingMother a owl:Class ;
    owl:equivalentClass [
        owl:intersectionOf (ex:Mother ex:Worker)
    ] .
ex:Mother a owl:Class .
ex:Worker a owl:Class .';

            $result = $this->parser->parse($content);
            $wm = $result->classes['http://example.org/WorkingMother'];

            expect($wm['class_expressions'])->toHaveKey('intersection_of');
            expect($wm['class_expressions']['intersection_of'])->toContain('http://example.org/Mother');
            expect($wm['class_expressions']['intersection_of'])->toContain('http://example.org/Worker');
        });

        it('extracts owl:unionOf via owl:equivalentClass blank node', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Pet a owl:Class ;
    owl:equivalentClass [
        owl:unionOf (ex:Cat ex:Dog ex:Fish)
    ] .
ex:Cat a owl:Class .
ex:Dog a owl:Class .
ex:Fish a owl:Class .';

            $result = $this->parser->parse($content);
            $pet = $result->classes['http://example.org/Pet'];

            expect($pet['class_expressions'])->toHaveKey('union_of');
            expect($pet['class_expressions']['union_of'])->toContain('http://example.org/Cat');
            expect($pet['class_expressions']['union_of'])->toContain('http://example.org/Dog');
            expect($pet['class_expressions']['union_of'])->toContain('http://example.org/Fish');
        });

        it('extracts owl:complementOf as a single URI string', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:NotPerson a owl:Class ;
    owl:complementOf ex:Person .
ex:Person a owl:Class .';

            $result = $this->parser->parse($content);
            $np = $result->classes['http://example.org/NotPerson'];

            expect($np['class_expressions'])->toHaveKey('complement_of');
            expect($np['class_expressions']['complement_of'])->toBe('http://example.org/Person');
            expect($np['class_expressions']['complement_of'])->toBeString();
        });

        it('extracts owl:oneOf with member URIs', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Suit a owl:Class ;
    owl:oneOf (ex:Hearts ex:Diamonds ex:Clubs ex:Spades) .';

            $result = $this->parser->parse($content);
            $suit = $result->classes['http://example.org/Suit'];

            expect($suit['class_expressions'])->toHaveKey('one_of');
            expect($suit['class_expressions']['one_of'])->toHaveCount(4);
            expect($suit['class_expressions']['one_of'])->toContain('http://example.org/Hearts');
            expect($suit['class_expressions']['one_of'])->toContain('http://example.org/Spades');
        });

        it('has empty class_expressions array for class without expressions', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Animal a owl:Class .';

            $result = $this->parser->parse($content);
            $animal = $result->classes['http://example.org/Animal'];

            expect($animal['class_expressions'])->toBe([]);
        });

        it('skips blank nodes in list members', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Complex a owl:Class ;
    owl:equivalentClass [
        owl:intersectionOf (
            ex:Cat
            [ a owl:Restriction ;
              owl:onProperty ex:hasColor ;
              owl:someValuesFrom ex:Color ]
        )
    ] .
ex:Cat a owl:Class .
ex:Color a owl:Class .';

            $result = $this->parser->parse($content);
            $complex = $result->classes['http://example.org/Complex'];

            expect($complex['class_expressions']['intersection_of'])->toContain('http://example.org/Cat');
            foreach ($complex['class_expressions']['intersection_of'] as $member) {
                expect($member)->not->toStartWith('_:');
            }
            expect($complex['class_expressions']['intersection_of'])->toHaveCount(1);
        });

        it('extracts owl:intersectionOf via direct resource not equivalentClass blank node', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:SmallDog a owl:Class ;
    owl:intersectionOf (ex:Small ex:Dog) .
ex:Small a owl:Class .
ex:Dog a owl:Class .';

            $result = $this->parser->parse($content);
            $sd = $result->classes['http://example.org/SmallDog'];

            expect($sd['class_expressions'])->toHaveKey('intersection_of');
            expect($sd['class_expressions']['intersection_of'])->toContain('http://example.org/Small');
            expect($sd['class_expressions']['intersection_of'])->toContain('http://example.org/Dog');
        });
    });

    // ── Class constraint/restriction extraction ─────────────────────

    describe('class constraints', function () {
        it('extracts rdfs:subClassOf with owl:Restriction blank node into constraints array', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:Parent a owl:Class ;
    rdfs:subClassOf [
        a owl:Restriction ;
        owl:onProperty ex:hasChild ;
        owl:minCardinality "1"^^xsd:nonNegativeInteger
    ] .';

            $result = $this->parser->parse($content);
            $parent = $result->classes['http://example.org/Parent'];

            expect($parent['constraints'])->toHaveCount(1);
        });

        it('has restriction property field as a full URI', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:Parent a owl:Class ;
    rdfs:subClassOf [
        a owl:Restriction ;
        owl:onProperty ex:hasChild ;
        owl:minCardinality "1"^^xsd:nonNegativeInteger
    ] .';

            $result = $this->parser->parse($content);
            $parent = $result->classes['http://example.org/Parent'];

            expect($parent['constraints'][0]['property'])->toBe('http://example.org/hasChild');
        });

        it('extracts owl:cardinality as a string value', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:Bicycle a owl:Class ;
    rdfs:subClassOf [
        a owl:Restriction ;
        owl:onProperty ex:hasWheel ;
        owl:cardinality "2"^^xsd:nonNegativeInteger
    ] .';

            $result = $this->parser->parse($content);
            $bicycle = $result->classes['http://example.org/Bicycle'];

            expect($bicycle['constraints'][0]['cardinality'])->toBe('2');
            expect($bicycle['constraints'][0]['cardinality'])->toBeString();
        });

        it('extracts owl:minCardinality', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:Parent a owl:Class ;
    rdfs:subClassOf [
        a owl:Restriction ;
        owl:onProperty ex:hasChild ;
        owl:minCardinality "1"^^xsd:nonNegativeInteger
    ] .';

            $result = $this->parser->parse($content);
            $parent = $result->classes['http://example.org/Parent'];

            expect($parent['constraints'][0]['min_cardinality'])->toBe('1');
        });

        it('extracts owl:maxCardinality', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:SingleChild a owl:Class ;
    rdfs:subClassOf [
        a owl:Restriction ;
        owl:onProperty ex:hasSibling ;
        owl:maxCardinality "0"^^xsd:nonNegativeInteger
    ] .';

            $result = $this->parser->parse($content);
            $sc = $result->classes['http://example.org/SingleChild'];

            expect($sc['constraints'][0]['max_cardinality'])->toBe('0');
        });

        it('extracts owl:qualifiedCardinality and owl:onClass', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:TwoWheeler a owl:Class ;
    rdfs:subClassOf [
        a owl:Restriction ;
        owl:onProperty ex:hasWheel ;
        owl:qualifiedCardinality "2"^^xsd:nonNegativeInteger ;
        owl:onClass ex:Wheel
    ] .';

            $result = $this->parser->parse($content);
            $tw = $result->classes['http://example.org/TwoWheeler'];

            expect($tw['constraints'][0]['qualified_cardinality'])->toBe('2');
            expect($tw['constraints'][0]['on_class'])->toBe('http://example.org/Wheel');
        });

        it('extracts owl:minQualifiedCardinality', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:Parent a owl:Class ;
    rdfs:subClassOf [
        a owl:Restriction ;
        owl:onProperty ex:hasChild ;
        owl:minQualifiedCardinality "1"^^xsd:nonNegativeInteger ;
        owl:onClass ex:Person
    ] .';

            $result = $this->parser->parse($content);
            $parent = $result->classes['http://example.org/Parent'];

            expect($parent['constraints'][0]['min_qualified_cardinality'])->toBe('1');
        });

        it('extracts owl:maxQualifiedCardinality', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:Limited a owl:Class ;
    rdfs:subClassOf [
        a owl:Restriction ;
        owl:onProperty ex:hasItem ;
        owl:maxQualifiedCardinality "5"^^xsd:nonNegativeInteger ;
        owl:onClass ex:Item
    ] .';

            $result = $this->parser->parse($content);
            $limited = $result->classes['http://example.org/Limited'];

            expect($limited['constraints'][0]['max_qualified_cardinality'])->toBe('5');
        });

        it('extracts owl:allValuesFrom as full URI', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:VegetarianPizza a owl:Class ;
    rdfs:subClassOf [
        a owl:Restriction ;
        owl:onProperty ex:hasTopping ;
        owl:allValuesFrom ex:VegetarianTopping
    ] .';

            $result = $this->parser->parse($content);
            $vp = $result->classes['http://example.org/VegetarianPizza'];

            expect($vp['constraints'][0]['all_values_from'])->toBe('http://example.org/VegetarianTopping');
        });

        it('extracts owl:someValuesFrom as full URI', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:Parent a owl:Class ;
    rdfs:subClassOf [
        a owl:Restriction ;
        owl:onProperty ex:hasChild ;
        owl:someValuesFrom ex:Person
    ] .';

            $result = $this->parser->parse($content);
            $parent = $result->classes['http://example.org/Parent'];

            expect($parent['constraints'][0]['some_values_from'])->toBe('http://example.org/Person');
        });

        it('extracts owl:hasValue into value key as string', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:AustralianWine a owl:Class ;
    rdfs:subClassOf [
        a owl:Restriction ;
        owl:onProperty ex:hasOrigin ;
        owl:hasValue "Australia"
    ] .';

            $result = $this->parser->parse($content);
            $aw = $result->classes['http://example.org/AustralianWine'];

            expect($aw['constraints'][0]['value'])->toBe('Australia');
            expect($aw['constraints'][0]['value'])->toBeString();
        });

        it('extracts owl:hasSelf as boolean true', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:Narcissist a owl:Class ;
    rdfs:subClassOf [
        a owl:Restriction ;
        owl:onProperty ex:loves ;
        owl:hasSelf "true"^^xsd:boolean
    ] .';

            $result = $this->parser->parse($content);
            $narc = $result->classes['http://example.org/Narcissist'];

            expect($narc['constraints'][0]['has_self'])->toBeTrue();
            expect($narc['constraints'][0]['has_self'])->toBeBool();
        });

        it('has restriction type field always owl:Restriction', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:Parent a owl:Class ;
    rdfs:subClassOf [
        a owl:Restriction ;
        owl:onProperty ex:hasChild ;
        owl:minCardinality "1"^^xsd:nonNegativeInteger
    ] .';

            $result = $this->parser->parse($content);
            $parent = $result->classes['http://example.org/Parent'];

            expect($parent['constraints'][0]['type'])->toBe('owl:Restriction');
        });

        it('has empty constraints array for class with no restrictions', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Animal a owl:Class .';

            $result = $this->parser->parse($content);
            $animal = $result->classes['http://example.org/Animal'];

            expect($animal['constraints'])->toBe([]);
        });

        it('only converts blank node subClassOf with owl:onProperty to constraints', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:Dog a owl:Class ;
    rdfs:subClassOf ex:Animal ;
    rdfs:subClassOf [
        a owl:Restriction ;
        owl:onProperty ex:hasLegs ;
        owl:cardinality "4"^^xsd:nonNegativeInteger
    ] .
ex:Animal a owl:Class .';

            $result = $this->parser->parse($content);
            $dog = $result->classes['http://example.org/Dog'];

            expect($dog['parent_classes'])->toContain('http://example.org/Animal');
            expect($dog['constraints'])->toHaveCount(1);
            expect($dog['constraints'][0]['property'])->toBe('http://example.org/hasLegs');
        });

        it('may include blank node URIs in parent_classes from ClassExtractor', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:Dog a owl:Class ;
    rdfs:subClassOf ex:Animal ;
    rdfs:subClassOf [
        a owl:Restriction ;
        owl:onProperty ex:hasLegs ;
        owl:cardinality "4"^^xsd:nonNegativeInteger
    ] .
ex:Animal a owl:Class .';

            $result = $this->parser->parse($content);
            $dog = $result->classes['http://example.org/Dog'];

            expect(count($dog['parent_classes']))->toBeGreaterThanOrEqual(1);
            expect($dog['parent_classes'])->toContain('http://example.org/Animal');
            $blankNodes = array_filter($dog['parent_classes'], fn ($uri) => str_starts_with($uri, '_:'));
            expect(count($blankNodes))->toBe(0, 'Blank nodes should be filtered from parent_classes after constraint extraction');
        });
    });
});
