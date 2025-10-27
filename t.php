<?php
$format = [
    0 => ['manufacturerId', '/^A(.{3})/', '/^[A-Z0-9]{3}$/'],
    1 => ['serialNumber', '/^([A-Z0-9]{3,})(?:-|$)/', '/^[A-Z0-9]{3,}$/'],
    'additionalData' => ['additionalData']
];

foreach ($format as $index => $field) {
    echo "Index: $index, Field: ";
    var_dump($field);
    echo "Count: " . count($field) . "\n\n";
}
