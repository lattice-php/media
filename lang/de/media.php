<?php

declare(strict_types=1);

return [
    'library' => [
        'heading' => 'Mediathek',
        'empty' => 'Noch keine Medien. Dateien zum Hochladen hier ablegen.',
    ],
    'columns' => [
        'preview' => 'Vorschau',
        'name' => 'Name',
        'type' => 'Typ',
        'size' => 'Größe',
        'alt' => 'Alt-Text',
        'uploaded-at' => 'Hochgeladen',
        'usage' => 'Verwendet',
    ],
    'filters' => [
        'type' => [
            'label' => 'Typ',
            'image' => 'Bilder',
            'video' => 'Video',
            'audio' => 'Audio',
            'document' => 'Dokumente',
        ],
    ],
    'actions' => [
        'upload' => [
            'label' => 'Hochladen',
            'toast' => ':count Datei(en) hochgeladen',
        ],
        'update' => [
            'label' => 'Bearbeiten',
            'toast' => 'Datei aktualisiert',
        ],
        'delete' => [
            'confirm-description' => 'Diese Datei ist mit :count Datensatz(en) verknüpft. Das Löschen entfernt sie überall.',
            'confirm-title' => 'Datei löschen?',
            'label' => 'Löschen',
            'toast' => 'Datei gelöscht',
        ],
        'delete-selected' => [
            'label' => 'Ausgewählte löschen',
            'toast' => ':count Datei(en) gelöscht',
        ],
    ],
];
