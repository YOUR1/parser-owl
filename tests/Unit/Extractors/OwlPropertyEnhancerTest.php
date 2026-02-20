<?php

declare(strict_types=1);

use Youri\vandenBogert\Software\ParserOwl\OwlParser;

describe('OwlPropertyEnhancer', function () {
    beforeEach(function () {
        $this->parser = new OwlParser();
    });

    describe('property types', function () {
        it('detects owl:ObjectProperty as property_type object', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:knows a owl:ObjectProperty ;
    rdfs:label "knows" .';

            $result = $this->parser->parse($content);
            $prop = $result->properties['http://example.org/knows'];

            expect($prop['property_type'])->toBe('object');
        });

        it('detects owl:DatatypeProperty as property_type datatype', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:hasAge a owl:DatatypeProperty ;
    rdfs:label "has age" .';

            $result = $this->parser->parse($content);
            $prop = $result->properties['http://example.org/hasAge'];

            expect($prop['property_type'])->toBe('datatype');
        });

        it('detects owl:AnnotationProperty as property_type annotation', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:creator a owl:AnnotationProperty ;
    rdfs:label "creator" .';

            $result = $this->parser->parse($content);
            $prop = $result->properties['http://example.org/creator'];

            expect($prop['property_type'])->toBe('annotation');
        });
    });

    describe('property characteristics', function () {
        it('detects owl:FunctionalProperty with is_functional true', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:hasMother a owl:FunctionalProperty, owl:ObjectProperty ;
    rdfs:label "has mother" .';

            $result = $this->parser->parse($content);
            $prop = $result->properties['http://example.org/hasMother'];

            expect($prop['is_functional'])->toBeTrue();
        });

        it('detects owl:InverseFunctionalProperty with is_inverse_functional true', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:hasSSN a owl:InverseFunctionalProperty, owl:DatatypeProperty ;
    rdfs:label "has SSN" .';

            $result = $this->parser->parse($content);
            $prop = $result->properties['http://example.org/hasSSN'];

            expect($prop['is_inverse_functional'])->toBeTrue();
        });

        it('detects owl:TransitiveProperty with is_transitive true', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:ancestorOf a owl:TransitiveProperty, owl:ObjectProperty ;
    rdfs:label "ancestor of" .';

            $result = $this->parser->parse($content);
            $prop = $result->properties['http://example.org/ancestorOf'];

            expect($prop['is_transitive'])->toBeTrue();
        });

        it('detects owl:SymmetricProperty with is_symmetric true', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:marriedTo a owl:SymmetricProperty, owl:ObjectProperty ;
    rdfs:label "married to" .';

            $result = $this->parser->parse($content);
            $prop = $result->properties['http://example.org/marriedTo'];

            expect($prop['is_symmetric'])->toBeTrue();
        });

        it('detects owl:AsymmetricProperty with is_asymmetric true', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:isChildOf a owl:AsymmetricProperty, owl:ObjectProperty ;
    rdfs:label "is child of" .';

            $result = $this->parser->parse($content);
            $prop = $result->properties['http://example.org/isChildOf'];

            expect($prop['is_asymmetric'])->toBeTrue();
        });

        it('detects owl:ReflexiveProperty with is_reflexive true', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:knows a owl:ReflexiveProperty, owl:ObjectProperty ;
    rdfs:label "knows" .';

            $result = $this->parser->parse($content);
            $prop = $result->properties['http://example.org/knows'];

            expect($prop['is_reflexive'])->toBeTrue();
        });

        it('detects owl:IrreflexiveProperty with is_irreflexive true', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:isParentOf a owl:IrreflexiveProperty, owl:ObjectProperty ;
    rdfs:label "is parent of" .';

            $result = $this->parser->parse($content);
            $prop = $result->properties['http://example.org/isParentOf'];

            expect($prop['is_irreflexive'])->toBeTrue();
        });

        it('has all 7 is_* flags default to false for property without OWL characteristics', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:hasName a owl:DatatypeProperty ;
    rdfs:label "has name" .';

            $result = $this->parser->parse($content);
            $prop = $result->properties['http://example.org/hasName'];

            expect($prop['is_functional'])->toBeFalse();
            expect($prop['is_inverse_functional'])->toBeFalse();
            expect($prop['is_transitive'])->toBeFalse();
            expect($prop['is_symmetric'])->toBeFalse();
            expect($prop['is_asymmetric'])->toBeFalse();
            expect($prop['is_reflexive'])->toBeFalse();
            expect($prop['is_irreflexive'])->toBeFalse();
        });

        it('supports property with multiple characteristics', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:isChildOf a owl:AsymmetricProperty, owl:IrreflexiveProperty, owl:ObjectProperty ;
    rdfs:label "is child of" .';

            $result = $this->parser->parse($content);
            $prop = $result->properties['http://example.org/isChildOf'];

            expect($prop['is_asymmetric'])->toBeTrue();
            expect($prop['is_irreflexive'])->toBeTrue();
            expect($prop['is_symmetric'])->toBeFalse();
            expect($prop['is_reflexive'])->toBeFalse();
        });
    });

    describe('property relationships', function () {
        it('extracts owl:inverseOf into inverse_of array', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:hasChild a owl:ObjectProperty ;
    rdfs:label "has child" ;
    owl:inverseOf ex:hasParent .
ex:hasParent a owl:ObjectProperty .';

            $result = $this->parser->parse($content);
            $prop = $result->properties['http://example.org/hasChild'];

            expect($prop['inverse_of'])->toContain('http://example.org/hasParent');
        });

        it('extracts owl:equivalentProperty into equivalent_properties array', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:hasFather a owl:ObjectProperty ;
    rdfs:label "has father" ;
    owl:equivalentProperty ex:hasDad .
ex:hasDad a owl:ObjectProperty .';

            $result = $this->parser->parse($content);
            $prop = $result->properties['http://example.org/hasFather'];

            expect($prop['equivalent_properties'])->toContain('http://example.org/hasDad');
        });

        it('extracts owl:propertyDisjointWith into property_disjoint_with array', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:hasFather a owl:ObjectProperty ;
    rdfs:label "has father" ;
    owl:propertyDisjointWith ex:hasMother .
ex:hasMother a owl:ObjectProperty .';

            $result = $this->parser->parse($content);
            $prop = $result->properties['http://example.org/hasFather'];

            expect($prop['property_disjoint_with'])->toContain('http://example.org/hasMother');
        });

        it('extracts owl:propertyChainAxiom with correct URIs', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:hasGrandparent a owl:ObjectProperty ;
    rdfs:label "has grandparent" ;
    owl:propertyChainAxiom (ex:hasParent ex:hasParent) .';

            $result = $this->parser->parse($content);
            $prop = $result->properties['http://example.org/hasGrandparent'];

            expect($prop['property_chain_axiom'])->toHaveCount(2);
            expect($prop['property_chain_axiom'][0])->toBe('http://example.org/hasParent');
            expect($prop['property_chain_axiom'][1])->toBe('http://example.org/hasParent');
        });

        it('does NOT have property_chain_axiom key for property without chain axiom', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:hasName a owl:DatatypeProperty ;
    rdfs:label "has name" .';

            $result = $this->parser->parse($content);
            $prop = $result->properties['http://example.org/hasName'];

            expect($prop)->not->toHaveKey('property_chain_axiom');
        });
    });

    describe('global restrictions', function () {
        it('extracts owl:Restriction resources into ParsedOntology.restrictions with correct fields', function () {
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

            expect($result->restrictions)->not->toBeEmpty();

            $restriction = array_values($result->restrictions)[0];
            expect($restriction['type'])->toBe('owl:Restriction');
            expect($restriction['property'])->toBe('http://example.org/hasChild');
            expect($restriction['min_cardinality'])->toBe('1');
            expect($restriction['cardinality'])->toBeNull();
            expect($restriction['max_cardinality'])->toBeNull();
            expect($restriction['all_values_from'])->toBeNull();
            expect($restriction['some_values_from'])->toBeNull();
            expect($restriction['value'])->toBeNull();
        });

        it('extracts owl:allValuesFrom restriction into ParsedOntology.restrictions', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:hasChild a owl:ObjectProperty .
ex:Person a owl:Class .

ex:Parent a owl:Class ;
    rdfs:subClassOf [
        a owl:Restriction ;
        owl:onProperty ex:hasChild ;
        owl:allValuesFrom ex:Person
    ] .';

            $result = $this->parser->parse($content);

            expect($result->restrictions)->not->toBeEmpty();

            $restriction = array_values($result->restrictions)[0];
            expect($restriction['type'])->toBe('owl:Restriction');
            expect($restriction['property'])->toBe('http://example.org/hasChild');
            expect($restriction['all_values_from'])->toBe('http://example.org/Person');
        });

        it('extracts owl:hasSelf restriction into ParsedOntology.restrictions', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:loves a owl:ObjectProperty .

ex:Narcissist a owl:Class ;
    rdfs:subClassOf [
        a owl:Restriction ;
        owl:onProperty ex:loves ;
        owl:hasSelf "true"^^xsd:boolean
    ] .';

            $result = $this->parser->parse($content);

            expect($result->restrictions)->not->toBeEmpty();

            $restriction = array_values($result->restrictions)[0];
            expect($restriction['type'])->toBe('owl:Restriction');
            expect($restriction['property'])->toBe('http://example.org/loves');
            expect($restriction['has_self'])->toBeTrue();
        });
    });

    describe('qualified data cardinality', function () {
        it('extracts owl:onDataRange with owl:qualifiedCardinality', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:Person a owl:Class ;
    rdfs:subClassOf [
        a owl:Restriction ;
        owl:onProperty ex:hasAge ;
        owl:qualifiedCardinality "1"^^xsd:nonNegativeInteger ;
        owl:onDataRange xsd:integer
    ] .';

            $result = $this->parser->parse($content);

            expect($result->restrictions)->not->toBeEmpty();

            $restriction = array_values($result->restrictions)[0];
            expect($restriction['qualified_cardinality'])->toBe('1');
            expect($restriction['on_data_range'])->toBe('http://www.w3.org/2001/XMLSchema#integer');
        });

        it('extracts owl:minQualifiedCardinality with owl:onDataRange', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:Person a owl:Class ;
    rdfs:subClassOf [
        a owl:Restriction ;
        owl:onProperty ex:hasPhone ;
        owl:minQualifiedCardinality "1"^^xsd:nonNegativeInteger ;
        owl:onDataRange xsd:string
    ] .';

            $result = $this->parser->parse($content);
            $restriction = array_values($result->restrictions)[0];

            expect($restriction['min_qualified_cardinality'])->toBe('1');
            expect($restriction['on_data_range'])->toBe('http://www.w3.org/2001/XMLSchema#string');
        });

        it('extracts owl:maxQualifiedCardinality with owl:onDataRange', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:Person a owl:Class ;
    rdfs:subClassOf [
        a owl:Restriction ;
        owl:onProperty ex:hasNickname ;
        owl:maxQualifiedCardinality "3"^^xsd:nonNegativeInteger ;
        owl:onDataRange xsd:string
    ] .';

            $result = $this->parser->parse($content);
            $restriction = array_values($result->restrictions)[0];

            expect($restriction['max_qualified_cardinality'])->toBe('3');
            expect($restriction['on_data_range'])->toBe('http://www.w3.org/2001/XMLSchema#string');
        });

        it('has on_data_range as full URI not prefixed', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:Person a owl:Class ;
    rdfs:subClassOf [
        a owl:Restriction ;
        owl:onProperty ex:hasAge ;
        owl:qualifiedCardinality "1"^^xsd:nonNegativeInteger ;
        owl:onDataRange xsd:integer
    ] .';

            $result = $this->parser->parse($content);
            $restriction = array_values($result->restrictions)[0];

            expect($restriction['on_data_range'])->toStartWith('http://');
        });

        it('does not drop qualified data range when present', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:Person a owl:Class ;
    rdfs:subClassOf [
        a owl:Restriction ;
        owl:onProperty ex:hasAge ;
        owl:qualifiedCardinality "1"^^xsd:nonNegativeInteger ;
        owl:onDataRange xsd:integer
    ] .';

            $result = $this->parser->parse($content);
            $restriction = array_values($result->restrictions)[0];

            expect($restriction['on_data_range'])->not->toBeNull();
        });

        it('extracts owl:onDataRange in class constraints too', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:Person a owl:Class ;
    rdfs:subClassOf [
        a owl:Restriction ;
        owl:onProperty ex:hasAge ;
        owl:qualifiedCardinality "1"^^xsd:nonNegativeInteger ;
        owl:onDataRange xsd:integer
    ] .';

            $result = $this->parser->parse($content);
            $person = $result->classes['http://example.org/Person'];

            expect($person['constraints'][0]['on_data_range'])->toBe('http://www.w3.org/2001/XMLSchema#integer');
        });
    });

    describe('AllDisjointProperties', function () {
        it('extracts pairwise property_disjoint_with from owl:AllDisjointProperties', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:hasParent a owl:ObjectProperty .
ex:hasSpouse a owl:ObjectProperty .
ex:hasSibling a owl:ObjectProperty .

_:d a owl:AllDisjointProperties ;
    owl:members (ex:hasParent ex:hasSpouse ex:hasSibling) .';

            $result = $this->parser->parse($content);

            $parent = $result->properties['http://example.org/hasParent'];
            $spouse = $result->properties['http://example.org/hasSpouse'];
            $sibling = $result->properties['http://example.org/hasSibling'];

            expect($parent['property_disjoint_with'])->toContain('http://example.org/hasSpouse');
            expect($parent['property_disjoint_with'])->toContain('http://example.org/hasSibling');
            expect($parent['property_disjoint_with'])->toHaveCount(2);

            expect($spouse['property_disjoint_with'])->toContain('http://example.org/hasParent');
            expect($spouse['property_disjoint_with'])->toContain('http://example.org/hasSibling');

            expect($sibling['property_disjoint_with'])->toContain('http://example.org/hasParent');
            expect($sibling['property_disjoint_with'])->toContain('http://example.org/hasSpouse');
        });

        it('supports data property disjointness', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:hasAge a owl:DatatypeProperty .
ex:hasBirthYear a owl:DatatypeProperty .

_:d a owl:AllDisjointProperties ;
    owl:members (ex:hasAge ex:hasBirthYear) .';

            $result = $this->parser->parse($content);

            $age = $result->properties['http://example.org/hasAge'];
            $birth = $result->properties['http://example.org/hasBirthYear'];

            expect($age['property_disjoint_with'])->toContain('http://example.org/hasBirthYear');
            expect($birth['property_disjoint_with'])->toContain('http://example.org/hasAge');
        });

        it('merges AllDisjointProperties with existing propertyDisjointWith without duplicates', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .

ex:hasParent a owl:ObjectProperty ;
    owl:propertyDisjointWith ex:hasSpouse .
ex:hasSpouse a owl:ObjectProperty .

_:d a owl:AllDisjointProperties ;
    owl:members (ex:hasParent ex:hasSpouse) .';

            $result = $this->parser->parse($content);
            $parent = $result->properties['http://example.org/hasParent'];

            $spouseCount = count(array_filter(
                $parent['property_disjoint_with'],
                fn($uri) => $uri === 'http://example.org/hasSpouse'
            ));
            expect($spouseCount)->toBe(1);
        });
    });
});
