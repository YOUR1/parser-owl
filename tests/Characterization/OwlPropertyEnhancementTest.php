<?php

declare(strict_types=1);

use Youri\vandenBogert\Software\ParserOwl\OwlParser;

describe('OwlParser property enhancement', function () {
    beforeEach(function () {
        $this->parser = new OwlParser();
    });

    // ── Property type detection ─────────────────────────────────────

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

    // ── Property characteristics ────────────────────────────────────

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

        it('has all 7 is_* flags set to false for property without OWL characteristics', function () {
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

    // ── owl:inverseOf extraction ────────────────────────────────────

    describe('inverse of', function () {
        it('extracts owl:inverseOf value', function () {
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
    });

    // ── owl:equivalentProperty extraction ───────────────────────────

    describe('equivalent properties', function () {
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
    });

    // ── owl:propertyDisjointWith extraction ─────────────────────────

    describe('property disjoint with', function () {
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
    });

    // ── Restriction extraction ────────────────────────────────────────

    describe('restrictions', function () {
        it('populates restrictions array when owl:Restriction is present', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:Person a owl:Class .
ex:hasAge a owl:DatatypeProperty .

ex:Person rdfs:subClassOf [
    a owl:Restriction ;
    owl:onProperty ex:hasAge ;
    owl:minCardinality "1"
] .';

            $result = $this->parser->parse($content);

            expect($result->restrictions)->not->toBeEmpty();
        });

        it('has proper structure with type and property fields', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:Person a owl:Class .
ex:hasAge a owl:DatatypeProperty .

ex:Person rdfs:subClassOf [
    a owl:Restriction ;
    owl:onProperty ex:hasAge ;
    owl:someValuesFrom ex:Integer
] .';

            $result = $this->parser->parse($content);
            $restriction = array_values($result->restrictions)[0];

            expect($restriction)->toHaveKey('type');
            expect($restriction['type'])->toBe('owl:Restriction');
            expect($restriction)->toHaveKey('property');
            expect($restriction['property'])->toBe('http://example.org/hasAge');
        });
    });

    // ── Property chain axiom extraction ─────────────────────────────

    describe('property chain axiom', function () {
        it('extracts owl:propertyChainAxiom with list of two properties', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:hasGrandparent a owl:ObjectProperty ;
    rdfs:label "has grandparent" ;
    owl:propertyChainAxiom (ex:hasParent ex:hasParent) .';

            $result = $this->parser->parse($content);
            $prop = $result->properties['http://example.org/hasGrandparent'];

            expect($prop['property_chain_axiom'])->toHaveCount(2);
        });

        it('extracts full URIs from RDF list in property chain axiom', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:hasGrandparent a owl:ObjectProperty ;
    owl:propertyChainAxiom (ex:hasParent ex:hasParent) .';

            $result = $this->parser->parse($content);
            $prop = $result->properties['http://example.org/hasGrandparent'];

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
});
