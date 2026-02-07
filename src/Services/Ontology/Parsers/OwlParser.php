<?php

namespace App\Services\Ontology\Parsers;

class OwlParser extends RdfParser
{
    public function parse(string $content, array $options = []): array
    {
        $result = parent::parse($content, $options);

        // Enhance with OWL-specific features
        $result['metadata']['type'] = 'owl';
        $result = $this->processOwlFeatures($result, $content, $options);

        return $result;
    }

    public function canParse(string $content): bool
    {
        return parent::canParse($content) &&
               (str_contains($content, 'owl:') ||
                str_contains($content, 'http://www.w3.org/2002/07/owl#'));
    }

    public function getSupportedFormats(): array
    {
        return ['owl'];
    }

    protected function processOwlFeatures(array $result, string $content, array $options): array
    {
        // Process OWL-specific constructs
        $result['classes'] = $this->enhanceClassesWithOwl($result['classes']);
        $result['properties'] = $this->enhancePropertiesWithOwl($result['properties']);

        return $result;
    }

    protected function enhanceClassesWithOwl(array $classes): array
    {
        return array_map(function ($class) {
            // Add OWL-specific class features
            $class['constraints'] = $this->extractOwlRestrictions($class['metadata'] ?? []);

            return $class;
        }, $classes);
    }

    protected function enhancePropertiesWithOwl(array $properties): array
    {
        return array_map(function ($property) {
            // Add OWL-specific property features
            $metadata = $property['metadata'] ?? [];

            $property['is_functional'] = $this->hasOwlType($metadata, 'owl:FunctionalProperty');
            $property['is_inverse_functional'] = $this->hasOwlType($metadata, 'owl:InverseFunctionalProperty');
            $property['is_transitive'] = $this->hasOwlType($metadata, 'owl:TransitiveProperty');
            $property['is_symmetric'] = $this->hasOwlType($metadata, 'owl:SymmetricProperty');

            if (isset($metadata['owl:inverseOf'])) {
                $property['inverse_of'] = $this->normalizeArray($metadata['owl:inverseOf']);
            }

            return $property;
        }, $properties);
    }

    protected function extractOwlRestrictions(array $metadata): array
    {
        $restrictions = [];

        // Extract OWL restrictions (simplified)
        if (! isset($metadata['rdfs:subClassOf'])) {

            return $restrictions;
        }
        $subClasses = $this->normalizeArray($metadata['rdfs:subClassOf']);

        foreach ($subClasses as $subClass) {
            if (! (is_array($subClass) && isset($subClass['@type']) && $subClass['@type'] === 'owl:Restriction')) {
                continue;
            }
            $restrictions[] = [
                'type' => 'owl:Restriction',
                'property' => $subClass['owl:onProperty'] ?? null,
                'value' => $subClass['owl:hasValue'] ?? null,
                'cardinality' => $subClass['owl:cardinality'] ?? $subClass['owl:minCardinality'] ?? $subClass['owl:maxCardinality'] ?? null,
                'all_values_from' => $subClass['owl:allValuesFrom'] ?? null,
                'some_values_from' => $subClass['owl:someValuesFrom'] ?? null,
            ];

        }

        return $restrictions;
    }

    protected function hasOwlType(array $metadata, string $owlType): bool
    {
        $types = $this->normalizeArray($metadata['@type'] ?? []);

        return in_array($owlType, $types);
    }

    /**
     * Normalize a value to an array
     */
    protected function normalizeArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value === null) {
            return [];
        }

        return [$value];
    }
}
