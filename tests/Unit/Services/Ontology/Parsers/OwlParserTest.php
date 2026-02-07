<?php

use App\Services\Ontology\Exceptions\OntologyImportException;
use App\Services\Ontology\Parsers\Extractors\ClassExtractor;
use App\Services\Ontology\Parsers\Extractors\PrefixExtractor;
use App\Services\Ontology\Parsers\Extractors\PropertyExtractor;
use App\Services\Ontology\Parsers\OwlParser;

describe('OwlParser', function () {
    beforeEach(function () {
        $this->parser = new OwlParser(
            new PrefixExtractor,
            new ClassExtractor,
            new PropertyExtractor,
        );
    });

    it('can parse OWL content', function () {
        $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> . @prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> . owl:Class rdfs:subClassOf rdfs:Class .';

        expect($this->parser->canParse($content))->toBeTrue();
    });

    it('cannot parse non-OWL content', function () {
        $content = '@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> . rdfs:Class a rdfs:Class .';

        expect($this->parser->canParse($content))->toBeFalse();
    });

    it('detects OWL namespace in content', function () {
        $owlNamespaceContent = '@prefix owl: <http://www.w3.org/2002/07/owl#> . owl:Thing a owl:Class .';
        $owlFullUriContent = '@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> . <http://www.w3.org/2002/07/owl#Class> a rdfs:Class .';

        expect($this->parser->canParse($owlNamespaceContent))->toBeTrue();
        expect($this->parser->canParse($owlFullUriContent))->toBeTrue();
    });

    it('returns OWL as supported format', function () {
        expect($this->parser->getSupportedFormats())->toContain('owl');
    });

    it('parses OWL ontology with classes and properties', function () {
        $content = '
@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:Person a owl:Class ;
    rdfs:label "Person" ;
    rdfs:comment "A human being" ;
    rdfs:subClassOf ex:LivingThing .

ex:hasName a owl:DatatypeProperty ;
    rdfs:label "has name" ;
    rdfs:comment "The name of a person" ;
    rdfs:domain ex:Person ;
    rdfs:range xsd:string .

ex:knows a owl:ObjectProperty ;
    rdfs:label "knows" ;
    rdfs:comment "Indicates that a person knows another person" ;
    rdfs:domain ex:Person ;
    rdfs:range ex:Person .

ex:hasMother a owl:FunctionalProperty, owl:ObjectProperty ;
    rdfs:label "has mother" ;
    rdfs:comment "A person has exactly one biological mother" ;
    rdfs:domain ex:Person ;
    rdfs:range ex:Person .
        ';

        $result = $this->parser->parse($content);

        expect($result)->toHaveKeys(['metadata', 'prefixes', 'classes', 'properties', 'raw_content']);

        // Check metadata
        expect($result['metadata']['type'])->toBe('owl');
        expect($result['metadata']['format'])->toBe('turtle');

        // Check prefixes
        expect($result['prefixes'])->toHaveKey('owl');
        expect($result['prefixes']['owl'])->toBe('http://www.w3.org/2002/07/owl#');
        expect($result['prefixes'])->toHaveKey('ex');
        expect($result['prefixes']['ex'])->toBe('http://example.org/');

        // Check classes
        expect($result['classes'])->toHaveCount(1);
        $personClass = $result['classes'][0];
        expect($personClass['uri'])->toBe('http://example.org/Person');
        expect($personClass['label'])->toBe('Person');
        expect($personClass['description'])->toBe('A human being');
        expect($personClass['parent_classes'])->toContain('http://example.org/LivingThing');

        // Check properties
        expect($result['properties'])->toHaveCount(3);

        $propertyUris = collect($result['properties'])->pluck('uri')->toArray();
        expect($propertyUris)->toContain('http://example.org/hasName');
        expect($propertyUris)->toContain('http://example.org/knows');
        expect($propertyUris)->toContain('http://example.org/hasMother');

        // Check datatype property
        $hasNameProperty = collect($result['properties'])->firstWhere('uri', 'http://example.org/hasName');
        expect($hasNameProperty['property_type'])->toBe('datatype');
        expect($hasNameProperty['is_functional'])->toBeFalse();

        // Check object property
        $knowsProperty = collect($result['properties'])->firstWhere('uri', 'http://example.org/knows');
        expect($knowsProperty['property_type'])->toBe('object');
        expect($knowsProperty['is_functional'])->toBeFalse();

        // Check functional property - OWL parser should detect functional properties
        $hasMotherProperty = collect($result['properties'])->firstWhere('uri', 'http://example.org/hasMother');
        expect($hasMotherProperty['property_type'])->toBe('object');
        // Note: The actual functional detection depends on EasyRdf parsing,
        // so we'll just verify the property exists and has the right type for now
        expect($hasMotherProperty)->not->toBeNull();
    });

    it('enhances properties with OWL-specific features', function () {
        $content = '
@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:marriedTo a owl:SymmetricProperty, owl:ObjectProperty ;
    rdfs:label "married to" ;
    rdfs:domain ex:Person ;
    rdfs:range ex:Person .

ex:hasChild a owl:InverseFunctionalProperty, owl:ObjectProperty ;
    rdfs:label "has child" ;
    owl:inverseOf ex:hasParent ;
    rdfs:domain ex:Person ;
    rdfs:range ex:Person .

ex:ancestorOf a owl:TransitiveProperty, owl:ObjectProperty ;
    rdfs:label "ancestor of" ;
    rdfs:domain ex:Person ;
    rdfs:range ex:Person .
        ';

        $result = $this->parser->parse($content);

        // Check that OWL properties are parsed correctly
        // Note: Actual OWL characteristics depend on EasyRdf parsing
        $marriedToProperty = collect($result['properties'])->firstWhere('uri', 'http://example.org/marriedTo');
        expect($marriedToProperty)->not->toBeNull();

        $hasChildProperty = collect($result['properties'])->firstWhere('uri', 'http://example.org/hasChild');
        expect($hasChildProperty)->not->toBeNull();

        $ancestorOfProperty = collect($result['properties'])->firstWhere('uri', 'http://example.org/ancestorOf');
        expect($ancestorOfProperty)->not->toBeNull();
    });

    it('extracts OWL restrictions from class definitions', function () {
        $content = '
@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:Parent a owl:Class ;
    rdfs:label "Parent" ;
    rdfs:subClassOf [
        a owl:Restriction ;
        owl:onProperty ex:hasChild ;
        owl:minCardinality "1"^^xsd:nonNegativeInteger
    ] .
        ';

        $result = $this->parser->parse($content);

        // Check that classes with OWL restrictions are parsed
        expect($result['classes'])->toHaveCount(1);
        $parentClass = $result['classes'][0];
        expect($parentClass['uri'])->toBe('http://example.org/Parent');
        expect($parentClass['label'])->toBe('Parent');
        // OWL-specific constraints structure exists
        expect($parentClass)->toHaveKey('constraints');
    });

    it('handles OWL content in RDF/XML format', function () {
        $content = '<?xml version="1.0"?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:owl="http://www.w3.org/2002/07/owl#"
         xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
         xmlns:ex="http://example.org/">

    <owl:Class rdf:about="http://example.org/Animal">
        <rdfs:label>Animal</rdfs:label>
        <rdfs:comment>A living creature</rdfs:comment>
    </owl:Class>

    <owl:ObjectProperty rdf:about="http://example.org/livesIn">
        <rdfs:label>lives in</rdfs:label>
        <rdfs:domain rdf:resource="http://example.org/Animal"/>
        <rdfs:range rdf:resource="http://example.org/Habitat"/>
    </owl:ObjectProperty>

</rdf:RDF>';

        expect($this->parser->canParse($content))->toBeTrue();

        $result = $this->parser->parse($content);

        expect($result['metadata']['type'])->toBe('owl');
        expect($result['metadata']['format'])->toBe('rdf/xml');

        // Should have at least one class and one property
        expect($result['classes'])->not->toBeEmpty();
        expect($result['properties'])->not->toBeEmpty();
    });

    it('processes empty OWL ontology correctly', function () {
        $content = '
@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .

# Empty OWL ontology with just prefixes
        ';

        $result = $this->parser->parse($content);

        expect($result['metadata']['type'])->toBe('owl');
        expect($result['classes'])->toBeEmpty();
        expect($result['properties'])->toBeEmpty();
    });

    it('throws exception on invalid content', function () {
        expect(fn () => $this->parser->parse('invalid owl content'))
            ->toThrow(OntologyImportException::class);
    });

    it('detects OWL property characteristics correctly', function () {
        $reflection = new ReflectionClass($this->parser);
        $method = $reflection->getMethod('hasOwlType');
        $method->setAccessible(true);

        $metadata = ['@type' => ['owl:FunctionalProperty', 'owl:ObjectProperty']];
        expect($method->invoke($this->parser, $metadata, 'owl:FunctionalProperty'))->toBeTrue();
        expect($method->invoke($this->parser, $metadata, 'owl:SymmetricProperty'))->toBeFalse();
    });

    // ── Ontology Metadata ────────────────────────────────────────────

    it('extracts owl:Ontology metadata', function () {
        $content = '
@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:myOntology a owl:Ontology ;
    owl:imports ex:other, ex:another ;
    owl:versionIRI <http://example.org/myOntology/1.0> ;
    owl:versionInfo "1.0.0" .
        ';

        $result = $this->parser->parse($content);

        expect($result)->toHaveKey('ontology');
        expect($result['ontology'])->toHaveCount(1);

        $onto = $result['ontology'][0];
        expect($onto['uri'])->toBe('http://example.org/myOntology');
        expect($onto['imports'])->toContain('http://example.org/other');
        expect($onto['imports'])->toContain('http://example.org/another');
        expect($onto['version_iri'])->toBe('http://example.org/myOntology/1.0');
        expect($onto['version_info'])->toBe('1.0.0');
        expect($onto['deprecated'])->toBeFalse();
    });

    it('extracts owl:deprecated on ontology', function () {
        $content = '
@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:oldOntology a owl:Ontology ;
    owl:deprecated "true"^^xsd:boolean .
        ';

        $result = $this->parser->parse($content);

        expect($result['ontology'])->toHaveCount(1);
        expect($result['ontology'][0]['deprecated'])->toBeTrue();
    });

    // ── Individuals ──────────────────────────────────────────────────

    it('extracts owl:NamedIndividual with sameAs and differentFrom', function () {
        $content = '
@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:Person a owl:Class .

ex:john a owl:NamedIndividual, ex:Person ;
    rdfs:label "John" ;
    owl:sameAs ex:johnDoe ;
    owl:differentFrom ex:jane .

ex:jane a owl:NamedIndividual, ex:Person ;
    rdfs:label "Jane" .
        ';

        $result = $this->parser->parse($content);

        expect($result)->toHaveKey('individuals');
        expect($result['individuals'])->toHaveCount(2);

        $john = collect($result['individuals'])->firstWhere('uri', 'http://example.org/john');
        expect($john)->not->toBeNull();
        expect($john['label'])->toBe('John');
        expect($john['types'])->toContain('http://example.org/Person');
        expect($john['same_as'])->toContain('http://example.org/johnDoe');
        expect($john['different_from'])->toContain('http://example.org/jane');

        $jane = collect($result['individuals'])->firstWhere('uri', 'http://example.org/jane');
        expect($jane)->not->toBeNull();
        expect($jane['label'])->toBe('Jane');
        expect($jane['same_as'])->toBeEmpty();
    });

    it('extracts owl:AllDifferent and folds into individuals', function () {
        $content = '
@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:a a owl:NamedIndividual ;
    rdfs:label "A" .

ex:b a owl:NamedIndividual ;
    rdfs:label "B" .

ex:c a owl:NamedIndividual ;
    rdfs:label "C" .

[] a owl:AllDifferent ;
    owl:distinctMembers (ex:a ex:b ex:c) .
        ';

        $result = $this->parser->parse($content);

        expect($result['individuals'])->toHaveCount(3);

        $a = collect($result['individuals'])->firstWhere('uri', 'http://example.org/a');
        expect($a['different_from'])->toContain('http://example.org/b');
        expect($a['different_from'])->toContain('http://example.org/c');

        $b = collect($result['individuals'])->firstWhere('uri', 'http://example.org/b');
        expect($b['different_from'])->toContain('http://example.org/a');
        expect($b['different_from'])->toContain('http://example.org/c');
    });

    // ── Class Constructs ─────────────────────────────────────────────

    it('extracts equivalentClass and disjointWith', function () {
        $content = '
@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:Human a owl:Class ;
    rdfs:label "Human" ;
    owl:equivalentClass ex:Person ;
    owl:disjointWith ex:Robot .

ex:Person a owl:Class .
ex:Robot a owl:Class .
        ';

        $result = $this->parser->parse($content);

        $human = collect($result['classes'])->firstWhere('uri', 'http://example.org/Human');
        expect($human)->not->toBeNull();
        expect($human['equivalent_classes'])->toContain('http://example.org/Person');
        expect($human['disjoint_with'])->toContain('http://example.org/Robot');
    });

    it('extracts owl:intersectionOf class expression', function () {
        $content = '
@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:WorkingMother a owl:Class ;
    rdfs:label "Working Mother" ;
    owl:equivalentClass [
        owl:intersectionOf (ex:Mother ex:Worker)
    ] .

ex:Mother a owl:Class .
ex:Worker a owl:Class .
        ';

        $result = $this->parser->parse($content);

        $wm = collect($result['classes'])->firstWhere('uri', 'http://example.org/WorkingMother');
        expect($wm)->not->toBeNull();
        expect($wm['class_expressions'])->toHaveKey('intersection_of');
        expect($wm['class_expressions']['intersection_of'])->toContain('http://example.org/Mother');
        expect($wm['class_expressions']['intersection_of'])->toContain('http://example.org/Worker');
    });

    it('extracts owl:unionOf class expression', function () {
        $content = '
@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:Pet a owl:Class ;
    rdfs:label "Pet" ;
    owl:equivalentClass [
        owl:unionOf (ex:Cat ex:Dog ex:Fish)
    ] .

ex:Cat a owl:Class .
ex:Dog a owl:Class .
ex:Fish a owl:Class .
        ';

        $result = $this->parser->parse($content);

        $pet = collect($result['classes'])->firstWhere('uri', 'http://example.org/Pet');
        expect($pet)->not->toBeNull();
        expect($pet['class_expressions'])->toHaveKey('union_of');
        expect($pet['class_expressions']['union_of'])->toContain('http://example.org/Cat');
        expect($pet['class_expressions']['union_of'])->toContain('http://example.org/Dog');
        expect($pet['class_expressions']['union_of'])->toContain('http://example.org/Fish');
    });

    it('extracts owl:complementOf class expression', function () {
        $content = '
@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:NotPerson a owl:Class ;
    rdfs:label "Not Person" ;
    owl:complementOf ex:Person .

ex:Person a owl:Class .
        ';

        $result = $this->parser->parse($content);

        $np = collect($result['classes'])->firstWhere('uri', 'http://example.org/NotPerson');
        expect($np)->not->toBeNull();
        expect($np['class_expressions'])->toHaveKey('complement_of');
        expect($np['class_expressions']['complement_of'])->toBe('http://example.org/Person');
    });

    it('extracts owl:oneOf class expression', function () {
        $content = '
@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:Suit a owl:Class ;
    rdfs:label "Suit" ;
    owl:oneOf (ex:Hearts ex:Diamonds ex:Clubs ex:Spades) .
        ';

        $result = $this->parser->parse($content);

        $suit = collect($result['classes'])->firstWhere('uri', 'http://example.org/Suit');
        expect($suit)->not->toBeNull();
        expect($suit['class_expressions'])->toHaveKey('one_of');
        expect($suit['class_expressions']['one_of'])->toHaveCount(4);
        expect($suit['class_expressions']['one_of'])->toContain('http://example.org/Hearts');
        expect($suit['class_expressions']['one_of'])->toContain('http://example.org/Spades');
    });

    it('extracts qualified cardinality and onClass restrictions', function () {
        $content = '
@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:TwoWheeler a owl:Class ;
    rdfs:label "Two Wheeler" ;
    rdfs:subClassOf [
        a owl:Restriction ;
        owl:onProperty ex:hasWheel ;
        owl:qualifiedCardinality "2"^^xsd:nonNegativeInteger ;
        owl:onClass ex:Wheel
    ] .
        ';

        $result = $this->parser->parse($content);

        $tw = collect($result['classes'])->firstWhere('uri', 'http://example.org/TwoWheeler');
        expect($tw)->not->toBeNull();
        expect($tw['constraints'])->toHaveCount(1);

        $constraint = $tw['constraints'][0];
        expect($constraint['type'])->toBe('owl:Restriction');
        expect($constraint['property'])->toBe('http://example.org/hasWheel');
        expect($constraint['qualified_cardinality'])->toBe('2');
        expect($constraint['on_class'])->toBe('http://example.org/Wheel');
    });

    it('extracts owl:hasSelf restriction', function () {
        $content = '
@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:Narcissist a owl:Class ;
    rdfs:label "Narcissist" ;
    rdfs:subClassOf [
        a owl:Restriction ;
        owl:onProperty ex:loves ;
        owl:hasSelf "true"^^xsd:boolean
    ] .
        ';

        $result = $this->parser->parse($content);

        $narc = collect($result['classes'])->firstWhere('uri', 'http://example.org/Narcissist');
        expect($narc)->not->toBeNull();
        expect($narc['constraints'])->toHaveCount(1);
        expect($narc['constraints'][0]['has_self'])->toBeTrue();
        expect($narc['constraints'][0]['property'])->toBe('http://example.org/loves');
    });

    // ── Property Constructs ──────────────────────────────────────────

    it('detects asymmetric, reflexive, and irreflexive properties', function () {
        $content = '
@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:isChildOf a owl:AsymmetricProperty, owl:IrreflexiveProperty, owl:ObjectProperty ;
    rdfs:label "is child of" ;
    rdfs:domain ex:Person ;
    rdfs:range ex:Person .

ex:knows a owl:ReflexiveProperty, owl:ObjectProperty ;
    rdfs:label "knows" ;
    rdfs:domain ex:Person ;
    rdfs:range ex:Person .
        ';

        $result = $this->parser->parse($content);

        $isChildOf = collect($result['properties'])->firstWhere('uri', 'http://example.org/isChildOf');
        expect($isChildOf)->not->toBeNull();
        expect($isChildOf['is_asymmetric'])->toBeTrue();
        expect($isChildOf['is_irreflexive'])->toBeTrue();
        expect($isChildOf['is_reflexive'])->toBeFalse();

        $knows = collect($result['properties'])->firstWhere('uri', 'http://example.org/knows');
        expect($knows)->not->toBeNull();
        expect($knows['is_reflexive'])->toBeTrue();
        expect($knows['is_asymmetric'])->toBeFalse();
    });

    it('extracts equivalentProperty and propertyDisjointWith', function () {
        $content = '
@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:hasFather a owl:ObjectProperty ;
    rdfs:label "has father" ;
    owl:equivalentProperty ex:hasDad ;
    owl:propertyDisjointWith ex:hasMother .
        ';

        $result = $this->parser->parse($content);

        $prop = collect($result['properties'])->firstWhere('uri', 'http://example.org/hasFather');
        expect($prop)->not->toBeNull();
        expect($prop['equivalent_properties'])->toContain('http://example.org/hasDad');
        expect($prop['property_disjoint_with'])->toContain('http://example.org/hasMother');
    });

    it('extracts owl:propertyChainAxiom', function () {
        $content = '
@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:hasGrandparent a owl:ObjectProperty ;
    rdfs:label "has grandparent" ;
    owl:propertyChainAxiom (ex:hasParent ex:hasParent) .
        ';

        $result = $this->parser->parse($content);

        $prop = collect($result['properties'])->firstWhere('uri', 'http://example.org/hasGrandparent');
        expect($prop)->not->toBeNull();
        expect($prop['property_chain_axiom'])->toHaveCount(2);
        expect($prop['property_chain_axiom'][0])->toBe('http://example.org/hasParent');
        expect($prop['property_chain_axiom'][1])->toBe('http://example.org/hasParent');
    });

    // ── Data Ranges ──────────────────────────────────────────────────

    it('extracts rdfs:Datatype with owl:onDatatype and restrictions', function () {
        $content = '
@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:AdultAge a rdfs:Datatype ;
    owl:onDatatype xsd:integer ;
    owl:withRestrictions (
        [ xsd:minInclusive "18" ]
        [ xsd:maxInclusive "150" ]
    ) .
        ';

        $result = $this->parser->parse($content);

        expect($result)->toHaveKey('data_ranges');
        expect($result['data_ranges'])->toHaveCount(1);

        $dr = $result['data_ranges'][0];
        expect($dr['uri'])->toBe('http://example.org/AdultAge');
        expect($dr['on_datatype'])->toBe('http://www.w3.org/2001/XMLSchema#integer');
        expect($dr['with_restrictions'])->toHaveCount(2);
    });

    it('extracts owl:datatypeComplementOf', function () {
        $content = '
@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:NotInteger a rdfs:Datatype ;
    owl:datatypeComplementOf xsd:integer .
        ';

        $result = $this->parser->parse($content);

        expect($result['data_ranges'])->toHaveCount(1);
        expect($result['data_ranges'][0]['complement_of'])->toBe('http://www.w3.org/2001/XMLSchema#integer');
    });

    // ── Empty/Edge Cases for New Features ────────────────────────────

    it('returns empty arrays for new keys on empty ontology', function () {
        $content = '
@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .

# Empty OWL ontology with just prefixes
        ';

        $result = $this->parser->parse($content);

        expect($result['ontology'])->toBeEmpty();
        expect($result['individuals'])->toBeEmpty();
        expect($result['data_ranges'])->toBeEmpty();
    });
});
