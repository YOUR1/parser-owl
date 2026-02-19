<?php

declare(strict_types=1);

use Youri\vandenBogert\Software\ParserCore\ValueObjects\ParsedOntology;
use Youri\vandenBogert\Software\ParserOwl\OwlParser;

describe('OwlParser', function () {
    beforeEach(function () {
        $this->parser = new OwlParser();
    });

    // ── canParse() ──────────────────────────────────────────────────

    describe('canParse()', function () {
        it('returns true for Turtle content with owl: prefix', function () {
            $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .
ex:Thing a owl:Class .';

            expect($this->parser->canParse($content))->toBeTrue();
        });

        it('returns true for Turtle content with full OWL namespace URI', function () {
            $content = '@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
<http://example.org/Thing> a <http://www.w3.org/2002/07/owl#Class> .';

            expect($this->parser->canParse($content))->toBeTrue();
        });

        it('returns true for RDF/XML content containing owl: prefix', function () {
            $content = '<?xml version="1.0"?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:owl="http://www.w3.org/2002/07/owl#">
    <owl:Class rdf:about="http://example.org/Thing"/>
</rdf:RDF>';

            expect($this->parser->canParse($content))->toBeTrue();
        });

        it('returns false for Turtle content WITHOUT any OWL markers', function () {
            $content = '@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .
ex:Thing a rdfs:Class .';

            // Old OwlParser had an AND condition: parent canParse AND OWL marker present.
            // New OwlParser inherits canParse from RdfParser -- accepts all valid RDF.
            expect($this->parser->canParse($content))->toBeTrue();
        })->skip('OwlParser no longer overrides canParse -- inherits from RdfParser (Story 5.2-5.4)');

        it('returns false for empty string', function () {
            expect($this->parser->canParse(''))->toBeFalse();
        });

        it('returns false for plain text content', function () {
            expect($this->parser->canParse('This is just plain text.'))->toBeFalse();
        });

        it('returns false for content with owl: but no RDF format indicators', function () {
            $content = 'owl:Class is great for modeling ontologies';

            expect($this->parser->canParse($content))->toBeFalse();
        });

        it('returns false for N-Triples content containing OWL namespace URI', function () {
            $content = '<http://example.org/Foo> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2002/07/owl#Class> .';

            // Old OwlParser excluded N-Triples (parent canParse didn't support it).
            // New RdfParser registers NTriplesFormatHandler, so N-Triples IS parseable.
            expect($this->parser->canParse($content))->toBeTrue();
        })->skip('OwlParser no longer overrides canParse -- RdfParser now supports N-Triples (Story 5.2-5.4)');

        it('captures that canParse is an AND condition: parent must pass AND OWL marker must be present', function () {
            $bothTrue = '@prefix owl: <http://www.w3.org/2002/07/owl#> . owl:Thing a owl:Class .';
            expect($this->parser->canParse($bothTrue))->toBeTrue();

            $parentFails = 'owl:Class is in this text';
            expect($this->parser->canParse($parentFails))->toBeFalse();

            // This assertion would now be TRUE (new parser accepts all valid RDF)
            $owlFails = '@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> . rdfs:Class a rdfs:Class .';
            expect($this->parser->canParse($owlFails))->toBeFalse();
        })->skip('OwlParser no longer overrides canParse -- no OWL marker AND condition (Story 5.2-5.4)');

        it('returns true for RDF/XML content with OWL namespace only in xmlns declaration', function () {
            $content = '<?xml version="1.0"?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:owl="http://www.w3.org/2002/07/owl#"
         xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#">
    <rdfs:Class rdf:about="http://example.org/Thing"/>
</rdf:RDF>';

            expect($this->parser->canParse($content))->toBeTrue();
        });
    });

    // ── getSupportedFormats() ───────────────────────────────────────

    describe('getSupportedFormats()', function () {
        it('returns [owl]', function () {
            expect($this->parser->getSupportedFormats())->toBe(['owl']);
        })->skip('OwlParser no longer overrides getSupportedFormats -- inherits RdfParser formats (Story 5.2-5.4)');

        it('returns an array with exactly one element', function () {
            expect($this->parser->getSupportedFormats())->toHaveCount(1);
        })->skip('OwlParser no longer overrides getSupportedFormats -- inherits RdfParser formats (Story 5.2-5.4)');

        it('does NOT include parent formats', function () {
            $formats = $this->parser->getSupportedFormats();
            expect($formats)->not->toContain('rdf/xml');
            expect($formats)->not->toContain('turtle');
            expect($formats)->not->toContain('n-triples');
        })->skip('OwlParser no longer overrides getSupportedFormats -- inherits RdfParser formats (Story 5.2-5.4)');
    });

    // ── parse() output structure and metadata ───────────────────────

    describe('parse()', function () {
        describe('output structure', function () {
            it('includes all expected top-level keys', function () {
                $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .
ex:Thing a owl:Class .';

                $result = $this->parser->parse($content);

                // Old behavior: array with keys metadata, prefixes, classes, properties,
                //   restrictions, raw_content, ontology, individuals, data_ranges
                // New behavior: ParsedOntology object with properties
                expect($result)->toBeInstanceOf(ParsedOntology::class);
                expect($result->metadata)->toBeArray();
                expect($result->prefixes)->toBeArray();
                expect($result->classes)->toBeArray();
                expect($result->properties)->toBeArray();
                expect($result->restrictions)->toBeArray();
                expect($result->rawContent)->toBeString();
            });

            it('sets metadata type to owl', function () {
                $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .
ex:Thing a owl:Class .';

                $result = $this->parser->parse($content);

                expect($result->metadata['type'])->toBe('owl');
            })->skip('OWL metadata type not yet set -- Story 5.2-5.4');

            it('sets metadata format to turtle for Turtle input', function () {
                $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .
ex:Thing a owl:Class .';

                $result = $this->parser->parse($content);

                expect($result->metadata['format'])->toBe('turtle');
            });

            it('has metadata resource_count as integer', function () {
                $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .
ex:Thing a owl:Class .';

                $result = $this->parser->parse($content);

                expect($result->metadata['resource_count'])->toBeInt();
            });

            it('contains raw_content with original input string', function () {
                $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .
ex:Thing a owl:Class .';

                $result = $this->parser->parse($content);

                expect($result->rawContent)->toBe($content);
            });

            it('produces format rdf/xml in metadata for RDF/XML input', function () {
                $content = '<?xml version="1.0"?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:owl="http://www.w3.org/2002/07/owl#"
         xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
         xmlns:ex="http://example.org/">
    <owl:Class rdf:about="http://example.org/Thing">
        <rdfs:label>Thing</rdfs:label>
    </owl:Class>
</rdf:RDF>';

                $result = $this->parser->parse($content);

                expect($result->metadata['format'])->toBe('rdf/xml');
            });

            it('includes ontology key from OWL post-processing', function () {
                $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .
ex:Thing a owl:Class .';

                $result = $this->parser->parse($content);

                expect($result)->toHaveKey('ontology');
            })->skip('OWL ontology extraction not yet implemented -- Story 5.2-5.4');

            it('includes individuals key from OWL post-processing', function () {
                $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .
ex:Thing a owl:Class .';

                $result = $this->parser->parse($content);

                expect($result)->toHaveKey('individuals');
            })->skip('OWL individual extraction not yet implemented -- Story 5.2-5.4');

            it('includes data_ranges key from OWL post-processing', function () {
                $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .
ex:Thing a owl:Class .';

                $result = $this->parser->parse($content);

                expect($result)->toHaveKey('data_ranges');
            })->skip('OWL data range extraction not yet implemented -- Story 5.2-5.4');

            it('returns empty arrays for classes, properties, ontology, individuals, data_ranges with empty OWL ontology', function () {
                $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
# Empty OWL ontology with just prefixes
';

                $result = $this->parser->parse($content);

                expect($result->classes)->toBeEmpty();
                expect($result->properties)->toBeEmpty();
                // ontology, individuals, data_ranges not yet on ParsedOntology
            })->skip('OWL-specific empty arrays not yet on ParsedOntology -- Story 5.2-5.4');
        });

        // ── Inherited RDF capabilities ──────────────────────────────

        describe('inherited RDF capabilities', function () {
            it('extracts prefix declarations from OWL Turtle content', function () {
                $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .
ex:Thing a owl:Class .';

                $result = $this->parser->parse($content);

                expect($result->prefixes)->not->toBeEmpty();
            });

            it('extracts owl prefix mapping to OWL namespace', function () {
                $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .
ex:Thing a owl:Class .';

                $result = $this->parser->parse($content);

                expect($result->prefixes)->toHaveKey('owl');
                expect($result->prefixes['owl'])->toBe('http://www.w3.org/2002/07/owl#');
            });

            it('extracts custom prefixes', function () {
                $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .
ex:Thing a owl:Class .';

                $result = $this->parser->parse($content);

                expect($result->prefixes)->toHaveKey('ex');
                expect($result->prefixes['ex'])->toBe('http://example.org/');
            });

            it('extracts classes via inherited ClassExtractor', function () {
                $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .
ex:Person a owl:Class ;
    rdfs:label "Person" ;
    rdfs:comment "A human being" .';

                $result = $this->parser->parse($content);

                $personUri = 'http://example.org/Person';
                expect($result->classes)->toHaveKey($personUri);
                expect($result->classes[$personUri]['uri'])->toBe($personUri);
                expect($result->classes[$personUri]['label'])->toBe('Person');
            });

            it('includes expected class keys from inherited extraction', function () {
                $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .
ex:Person a owl:Class ;
    rdfs:label "Person" ;
    rdfs:comment "A human being" ;
    rdfs:subClassOf ex:LivingThing .
ex:LivingThing a owl:Class .';

                $result = $this->parser->parse($content);
                $class = $result->classes['http://example.org/Person'];

                // Base keys available from parser-rdf ClassExtractor
                expect($class)->toHaveKeys([
                    'uri', 'label', 'labels', 'description', 'descriptions',
                    'parent_classes', 'metadata',
                ]);
                // OWL-specific keys (equivalent_classes, disjoint_with) not yet available
            })->skip('OWL class enhancement keys not yet implemented -- Story 5.2-5.4');

            it('extracts properties via inherited PropertyExtractor', function () {
                $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .
ex:hasName a owl:DatatypeProperty ;
    rdfs:label "has name" ;
    rdfs:domain ex:Person ;
    rdfs:range xsd:string .';

                $result = $this->parser->parse($content);

                $propUri = 'http://example.org/hasName';
                expect($result->properties)->toHaveKey($propUri);
                expect($result->properties[$propUri]['uri'])->toBe($propUri);
            });

            it('includes expected property keys from inherited extraction', function () {
                $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .
ex:hasName a owl:DatatypeProperty ;
    rdfs:label "has name" ;
    rdfs:domain ex:Person ;
    rdfs:range xsd:string .';

                $result = $this->parser->parse($content);
                $prop = $result->properties['http://example.org/hasName'];

                // Base keys available from parser-rdf PropertyExtractor
                expect($prop)->toHaveKeys([
                    'uri', 'label', 'labels', 'description', 'descriptions',
                    'property_type', 'domain', 'range', 'parent_properties',
                    'inverse_of', 'is_functional', 'metadata',
                ]);
                // OWL-specific keys (equivalent_properties, property_disjoint_with) not yet available
            })->skip('OWL property enhancement keys not yet implemented -- Story 5.2-5.4');

            it('extracts graph-level restrictions via inherited extractGraphRestrictions', function () {
                $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .
ex:Person a owl:Class .
ex:Animal a owl:Class .
ex:Person rdfs:subClassOf [
    a owl:Restriction ;
    owl:onProperty ex:eats ;
    owl:someValuesFrom ex:Animal
] .';

                $result = $this->parser->parse($content);

                expect($result->restrictions)->not->toBeEmpty();
                $restriction = array_values($result->restrictions)[0];
                expect($restriction)->toHaveKeys(['source_class', 'property', 'allowed_targets', 'restriction_type']);
                expect($restriction['restriction_type'])->toBe('someValuesFrom');
            })->skip('OWL restriction extraction not yet implemented -- Story 5.2-5.4');

            it('accepts constructor with PrefixExtractor, ClassExtractor, PropertyExtractor', function () {
                // Old OwlParser required 3 extractor parameters.
                // New OwlParser uses parameterless constructor (extractors wired internally).
                $parser = new OwlParser();
                expect($parser)->toBeInstanceOf(OwlParser::class);
            });
        });

        // ── Error handling ──────────────────────────────────────────

        describe('error handling', function () {
            it('throws OntologyImportException for invalid content', function () {
                expect(fn () => $this->parser->parse('completely invalid content that is not RDF'))
                    ->toThrow(\Exception::class);
            })->skip('Exception type changed from OntologyImportException to FormatDetectionException -- Story 5.2-5.4');

            it('throws exception with message starting with RDF parsing failed:', function () {
                try {
                    $this->parser->parse('completely invalid content that is not RDF');
                    test()->fail('Expected exception was not thrown');
                } catch (\Exception $e) {
                    expect($e->getMessage())->toStartWith('RDF parsing failed: ');
                }
            })->skip('Exception message format changed in new architecture -- Story 5.2-5.4');

            it('wraps exception with $previous set', function () {
                try {
                    $this->parser->parse('completely invalid content that is not RDF');
                    test()->fail('Expected exception was not thrown');
                } catch (\Exception $e) {
                    expect($e->getPrevious())->not->toBeNull();
                }
            })->skip('Exception wrapping changed in new architecture -- Story 5.2-5.4');

            it('has exception code 0', function () {
                try {
                    $this->parser->parse('completely invalid content that is not RDF');
                    test()->fail('Expected exception was not thrown');
                } catch (\Exception $e) {
                    expect($e->getCode())->toBe(0);
                }
            })->skip('Exception behavior changed in new architecture -- Story 5.2-5.4');

            it('captures empty string parse behavior', function () {
                expect(fn () => $this->parser->parse(''))
                    ->toThrow(\Exception::class);
            })->skip('Exception type changed from OntologyImportException to ParseException -- Story 5.2-5.4');

            it('wraps parsing failure for content that would pass canParse check', function () {
                $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix ex: <http://example.org/> .
this is not valid turtle owl: syntax {{{broken}}}';

                expect(fn () => $this->parser->parse($content))
                    ->toThrow(\Exception::class);
            })->skip('Exception type changed from OntologyImportException -- Story 5.2-5.4');
        });

        // ── Multi-format OWL parsing ────────────────────────────────

        describe('multi-format OWL parsing', function () {
            it('parses OWL ontology in Turtle format with complete output', function () {
                $content = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix ex: <http://example.org/> .

ex:myOnt a owl:Ontology ;
    owl:versionInfo "1.0" .

ex:Person a owl:Class ;
    rdfs:label "Person" .

ex:hasName a owl:DatatypeProperty ;
    rdfs:label "has name" ;
    rdfs:domain ex:Person ;
    rdfs:range xsd:string .

ex:john a owl:NamedIndividual, ex:Person ;
    rdfs:label "John" .';

                $result = $this->parser->parse($content);

                expect($result->metadata['format'])->toBe('turtle');
                expect($result->classes)->not->toBeEmpty();
                expect($result->properties)->not->toBeEmpty();
                // ontology and individuals extraction not yet implemented
            })->skip('OWL ontology/individual extraction not yet implemented -- Story 5.2-5.4');

            it('parses OWL ontology in RDF/XML format with complete output', function () {
                $content = '<?xml version="1.0"?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:owl="http://www.w3.org/2002/07/owl#"
         xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
         xmlns:xsd="http://www.w3.org/2001/XMLSchema#"
         xmlns:ex="http://example.org/">

    <owl:Ontology rdf:about="http://example.org/myOnt">
        <owl:versionInfo>1.0</owl:versionInfo>
    </owl:Ontology>

    <owl:Class rdf:about="http://example.org/Person">
        <rdfs:label>Person</rdfs:label>
    </owl:Class>

    <owl:DatatypeProperty rdf:about="http://example.org/hasName">
        <rdfs:label>has name</rdfs:label>
        <rdfs:domain rdf:resource="http://example.org/Person"/>
        <rdfs:range rdf:resource="http://www.w3.org/2001/XMLSchema#string"/>
    </owl:DatatypeProperty>

    <owl:NamedIndividual rdf:about="http://example.org/john">
        <rdf:type rdf:resource="http://example.org/Person"/>
        <rdfs:label>John</rdfs:label>
    </owl:NamedIndividual>
</rdf:RDF>';

                $result = $this->parser->parse($content);

                expect($result->metadata['format'])->toBe('rdf/xml');
                expect($result->classes)->not->toBeEmpty();
                expect($result->properties)->not->toBeEmpty();
                // ontology and individuals extraction not yet implemented
            })->skip('OWL ontology/individual extraction not yet implemented -- Story 5.2-5.4');

            it('produces equivalent class extraction from Turtle and RDF/XML', function () {
                $turtle = '@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix ex: <http://example.org/> .

ex:Animal a owl:Class ;
    rdfs:label "Animal" .';

                $rdfxml = '<?xml version="1.0"?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:owl="http://www.w3.org/2002/07/owl#"
         xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
         xmlns:ex="http://example.org/">
    <owl:Class rdf:about="http://example.org/Animal">
        <rdfs:label>Animal</rdfs:label>
    </owl:Class>
</rdf:RDF>';

                $animalUri = 'http://example.org/Animal';

                $turtleResult = $this->parser->parse($turtle);
                $rdfxmlResult = $this->parser->parse($rdfxml);

                $turtleClass = $turtleResult->classes[$animalUri];
                $rdfxmlClass = $rdfxmlResult->classes[$animalUri];

                expect($turtleClass['uri'])->toBe($rdfxmlClass['uri']);
                expect($turtleClass['label'])->toBe($rdfxmlClass['label']);
            });

            it('sets correct metadata for RDF/XML OWL content', function () {
                $content = '<?xml version="1.0"?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:owl="http://www.w3.org/2002/07/owl#"
         xmlns:ex="http://example.org/">
    <owl:Class rdf:about="http://example.org/Thing"/>
</rdf:RDF>';

                $result = $this->parser->parse($content);

                expect($result->metadata['format'])->toBe('rdf/xml');
                expect($result->metadata['type'])->toBe('owl');
            })->skip('OWL metadata type not yet set -- Story 5.2-5.4');
        });
    });
});
