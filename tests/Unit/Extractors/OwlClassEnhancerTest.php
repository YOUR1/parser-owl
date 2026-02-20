<?php

declare(strict_types=1);

use Youri\vandenBogert\Software\ParserCore\ValueObjects\ParsedOntology;
use Youri\vandenBogert\Software\ParserOwl\OwlParser;

describe('OwlClassEnhancer', function () {
    beforeEach(function () {
        $this->parser = new OwlParser();
    });

    describe('equivalent classes', function () {
        it('extracts owl:equivalentClass with a named class URI', function () {
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

        it('has empty equivalent_classes array for class with no equivalents', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Animal a owl:Class .';

            $result = $this->parser->parse($content);
            $animal = $result->classes['http://example.org/Animal'];

            expect($animal['equivalent_classes'])->toBe([]);
        });
    });

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

        it('extracts multiple disjoint classes', function () {
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

        it('extracts pairwise disjoint_with from owl:AllDisjointClasses with owl:members', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Cat a owl:Class .
ex:Dog a owl:Class .
ex:Fish a owl:Class .
_:d a owl:AllDisjointClasses ;
    owl:members (ex:Cat ex:Dog ex:Fish) .';

            $result = $this->parser->parse($content);

            $cat = $result->classes['http://example.org/Cat'];
            $dog = $result->classes['http://example.org/Dog'];
            $fish = $result->classes['http://example.org/Fish'];

            expect($cat['disjoint_with'])->toContain('http://example.org/Dog');
            expect($cat['disjoint_with'])->toContain('http://example.org/Fish');
            expect($cat['disjoint_with'])->toHaveCount(2);

            expect($dog['disjoint_with'])->toContain('http://example.org/Cat');
            expect($dog['disjoint_with'])->toContain('http://example.org/Fish');
            expect($dog['disjoint_with'])->toHaveCount(2);

            expect($fish['disjoint_with'])->toContain('http://example.org/Cat');
            expect($fish['disjoint_with'])->toContain('http://example.org/Dog');
            expect($fish['disjoint_with'])->toHaveCount(2);
        });

        it('has empty disjoint_with array for class with no disjoint declarations', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Animal a owl:Class .';

            $result = $this->parser->parse($content);
            $animal = $result->classes['http://example.org/Animal'];

            expect($animal['disjoint_with'])->toBe([]);
        });
    });

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

        it('extracts owl:complementOf as single URI string', function () {
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

        it('extracts owl:intersectionOf directly on resource', function () {
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

        it('has empty class_expressions for class without expressions', function () {
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
    });

    describe('class constraints', function () {
        it('extracts rdfs:subClassOf with owl:Restriction blank node', function () {
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

        it('has restriction property field as full URI', function () {
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

        it('extracts all cardinality fields', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:Bicycle a owl:Class ;
    rdfs:subClassOf [
        a owl:Restriction ;
        owl:onProperty ex:hasWheel ;
        owl:cardinality "2"^^xsd:nonNegativeInteger
    ] .

ex:Parent a owl:Class ;
    rdfs:subClassOf [
        a owl:Restriction ;
        owl:onProperty ex:hasChild ;
        owl:minCardinality "1"^^xsd:nonNegativeInteger
    ] .

ex:SingleChild a owl:Class ;
    rdfs:subClassOf [
        a owl:Restriction ;
        owl:onProperty ex:hasSibling ;
        owl:maxCardinality "0"^^xsd:nonNegativeInteger
    ] .

ex:TwoWheeler a owl:Class ;
    rdfs:subClassOf [
        a owl:Restriction ;
        owl:onProperty ex:hasWheel ;
        owl:qualifiedCardinality "2"^^xsd:nonNegativeInteger ;
        owl:onClass ex:Wheel
    ] .

ex:Family a owl:Class ;
    rdfs:subClassOf [
        a owl:Restriction ;
        owl:onProperty ex:hasChild ;
        owl:minQualifiedCardinality "1"^^xsd:nonNegativeInteger ;
        owl:onClass ex:Person
    ] .

ex:Limited a owl:Class ;
    rdfs:subClassOf [
        a owl:Restriction ;
        owl:onProperty ex:hasItem ;
        owl:maxQualifiedCardinality "5"^^xsd:nonNegativeInteger ;
        owl:onClass ex:Item
    ] .';

            $result = $this->parser->parse($content);

            expect($result->classes['http://example.org/Bicycle']['constraints'][0]['cardinality'])->toBe('2');
            expect($result->classes['http://example.org/Parent']['constraints'][0]['min_cardinality'])->toBe('1');
            expect($result->classes['http://example.org/SingleChild']['constraints'][0]['max_cardinality'])->toBe('0');
            expect($result->classes['http://example.org/TwoWheeler']['constraints'][0]['qualified_cardinality'])->toBe('2');
            expect($result->classes['http://example.org/Family']['constraints'][0]['min_qualified_cardinality'])->toBe('1');
            expect($result->classes['http://example.org/Limited']['constraints'][0]['max_qualified_cardinality'])->toBe('5');
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

        it('extracts owl:hasValue as string', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
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

        it('extracts owl:onClass as full URI', function () {
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

            expect($tw['constraints'][0]['on_class'])->toBe('http://example.org/Wheel');
        });

        it('has empty constraints for class without restrictions', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Animal a owl:Class .';

            $result = $this->parser->parse($content);
            $animal = $result->classes['http://example.org/Animal'];

            expect($animal['constraints'])->toBe([]);
        });

        it('named class rdfs:subClassOf stays in parent_classes, blank node becomes constraint', function () {
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
    });

    describe('disjoint union of', function () {
        it('extracts owl:disjointUnionOf member URIs as array', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Pet a owl:Class ;
    owl:disjointUnionOf (ex:Cat ex:Dog ex:Fish) .
ex:Cat a owl:Class .
ex:Dog a owl:Class .
ex:Fish a owl:Class .';

            $result = $this->parser->parse($content);
            $pet = $result->classes['http://example.org/Pet'];

            expect($pet)->toHaveKey('disjoint_union_of');
            expect($pet['disjoint_union_of'])->toHaveCount(3);
            expect($pet['disjoint_union_of'])->toContain('http://example.org/Cat');
            expect($pet['disjoint_union_of'])->toContain('http://example.org/Dog');
            expect($pet['disjoint_union_of'])->toContain('http://example.org/Fish');
        });

        it('is distinct from owl:unionOf in output', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Pet a owl:Class ;
    owl:disjointUnionOf (ex:Cat ex:Dog) .
ex:Cat a owl:Class .
ex:Dog a owl:Class .';

            $result = $this->parser->parse($content);
            $pet = $result->classes['http://example.org/Pet'];

            expect($pet)->toHaveKey('disjoint_union_of');
            expect($pet['class_expressions'])->not->toHaveKey('union_of');
        });

        it('is distinct from owl:disjointWith in output', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Pet a owl:Class ;
    owl:disjointUnionOf (ex:Cat ex:Dog) .
ex:Cat a owl:Class .
ex:Dog a owl:Class .';

            $result = $this->parser->parse($content);
            $pet = $result->classes['http://example.org/Pet'];

            expect($pet)->toHaveKey('disjoint_union_of');
            expect($pet['disjoint_with'])->toBeEmpty();
        });

        it('extracts multiple members in disjoint union', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Vehicle a owl:Class ;
    owl:disjointUnionOf (ex:Car ex:Truck ex:Motorcycle ex:Bicycle) .
ex:Car a owl:Class .
ex:Truck a owl:Class .
ex:Motorcycle a owl:Class .
ex:Bicycle a owl:Class .';

            $result = $this->parser->parse($content);
            $vehicle = $result->classes['http://example.org/Vehicle'];

            expect($vehicle['disjoint_union_of'])->toHaveCount(4);
        });

        it('uses full URIs never prefixed', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Pet a owl:Class ;
    owl:disjointUnionOf (ex:Cat ex:Dog) .
ex:Cat a owl:Class .
ex:Dog a owl:Class .';

            $result = $this->parser->parse($content);
            $pet = $result->classes['http://example.org/Pet'];

            foreach ($pet['disjoint_union_of'] as $uri) {
                expect($uri)->toStartWith('http://');
                expect($uri)->not->toContain(':Cat');
                expect($uri)->not->toContain(':Dog');
            }
        });

        it('has empty disjoint_union_of array for class without disjoint union', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Animal a owl:Class .';

            $result = $this->parser->parse($content);
            $animal = $result->classes['http://example.org/Animal'];

            expect($animal['disjoint_union_of'])->toBe([]);
        });
    });

    describe('has key', function () {
        it('extracts owl:hasKey property URIs as array', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Person a owl:Class ;
    owl:hasKey (ex:hasSSN) .';

            $result = $this->parser->parse($content);
            $person = $result->classes['http://example.org/Person'];

            expect($person)->toHaveKey('has_key');
            expect($person['has_key'])->toHaveCount(1);
            expect($person['has_key'])->toContain('http://example.org/hasSSN');
        });

        it('extracts object property keys', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Person a owl:Class ;
    owl:hasKey (ex:hasPassport) .
ex:hasPassport a owl:ObjectProperty .';

            $result = $this->parser->parse($content);
            $person = $result->classes['http://example.org/Person'];

            expect($person['has_key'])->toContain('http://example.org/hasPassport');
        });

        it('extracts data property keys', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Person a owl:Class ;
    owl:hasKey (ex:hasSSN) .
ex:hasSSN a owl:DatatypeProperty .';

            $result = $this->parser->parse($content);
            $person = $result->classes['http://example.org/Person'];

            expect($person['has_key'])->toContain('http://example.org/hasSSN');
        });

        it('extracts mixed object and data property keys', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Person a owl:Class ;
    owl:hasKey (ex:hasPassport ex:hasSSN) .
ex:hasPassport a owl:ObjectProperty .
ex:hasSSN a owl:DatatypeProperty .';

            $result = $this->parser->parse($content);
            $person = $result->classes['http://example.org/Person'];

            expect($person['has_key'])->toHaveCount(2);
            expect($person['has_key'])->toContain('http://example.org/hasPassport');
            expect($person['has_key'])->toContain('http://example.org/hasSSN');
        });

        it('uses full URIs never prefixed for key properties', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Person a owl:Class ;
    owl:hasKey (ex:hasSSN) .';

            $result = $this->parser->parse($content);
            $person = $result->classes['http://example.org/Person'];

            foreach ($person['has_key'] as $uri) {
                expect($uri)->toStartWith('http://');
            }
        });

        it('has empty has_key array for class without key declaration', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:Animal a owl:Class .';

            $result = $this->parser->parse($content);
            $animal = $result->classes['http://example.org/Animal'];

            expect($animal['has_key'])->toBe([]);
        });
    });
});
