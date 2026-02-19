<?php

declare(strict_types=1);

pest()->in('Unit', 'Characterization', 'Conformance');

function owlFixture(string $relativePath): string
{
    $path = __DIR__ . '/Fixtures/W3c/' . $relativePath;
    if (! file_exists($path)) {
        throw new RuntimeException("Fixture not found: {$path}");
    }

    $content = file_get_contents($path);
    if ($content === false) {
        throw new RuntimeException("Failed to read fixture: {$path}");
    }

    return $content;
}

/**
 * Find an individual by URI in the individuals array.
 *
 * @param array<int, array<string, mixed>> $individuals
 * @return array<string, mixed>|null
 */
function collect_individual(array $individuals, string $uri): ?array
{
    foreach ($individuals as $individual) {
        if ($individual['uri'] === $uri) {
            return $individual;
        }
    }

    return null;
}

/**
 * Find a data range by URI in the data_ranges array.
 *
 * @param array<int, array<string, mixed>> $dataRanges
 * @return array<string, mixed>|null
 */
function collect_data_range(array $dataRanges, string $uri): ?array
{
    foreach ($dataRanges as $range) {
        if (($range['uri'] ?? null) === $uri) {
            return $range;
        }
    }

    return null;
}

/**
 * Find an ontology by URI in the ontology metadata array.
 *
 * @param array<int, array<string, mixed>> $ontologies
 * @return array<string, mixed>|null
 */
function collect_ontology(array $ontologies, string $uri): ?array
{
    foreach ($ontologies as $ontology) {
        if (($ontology['uri'] ?? null) === $uri) {
            return $ontology;
        }
    }

    return null;
}
