<?php

namespace App\Services\General;

class MetadataService
{
    private const SITE_NAME = 'Zenovate Health';
    private const SITE_URL = 'https://alto2.zenovate.health';
    private const DEFAULT_DESCRIPTION = 'Evidence-based wellness injections tailored to your unique needs and delivered to your doorstep.';
    private const SITE_IMAGE = self::SITE_URL . '/og-image.jpg';

    public function createMetadata(array $pageMetadata = []): array
    {
        $title = $pageMetadata['title'] ?? self::SITE_NAME;
        $description = $pageMetadata['description'] ?? self::DEFAULT_DESCRIPTION;

        return [
            'title' => $title,
            'description' => $description,
            'openGraph' => [
                'type' => 'website',
                'locale' => 'en_US',
                'url' => self::SITE_URL,
                'siteName' => self::SITE_NAME,
                'title' => $title,
                'description' => $description,
                'images' => [
                    [
                        'url' => self::SITE_IMAGE,
                        'width' => 1200,
                        'height' => 630,
                        'alt' => $title,
                    ],
                ],
            ],
            'twitter' => [
                'card' => 'summary_large_image',
                'title' => $title,
                'description' => $description,
                'images' => [self::SITE_IMAGE],
            ],
        ];
    }

    public function renderMetaTags(array $metadata): string
    {
        return '
        <title>' . ($metadata['title'] ?? self::SITE_NAME) . '</title>
        <meta name="description" content="' . ($metadata['description'] ?? self::DEFAULT_DESCRIPTION) . '">
        <meta property="og:title" content="' . ($metadata['openGraph']['title'] ?? $metadata['title']) . '">
        <meta property="og:description" content="' . ($metadata['openGraph']['description'] ?? $metadata['description']) . '">
        <meta property="og:type" content="' . $metadata['openGraph']['type'] . '">
        <meta property="og:url" content="' . $metadata['openGraph']['url'] . '">
        <meta property="og:image" content="' . $metadata['openGraph']['images'][0]['url'] . '">
        <meta property="og:image:width" content="' . $metadata['openGraph']['images'][0]['width'] . '">
        <meta property="og:image:height" content="' . $metadata['openGraph']['images'][0]['height'] . '">
        <meta property="og:site_name" content="' . $metadata['openGraph']['siteName'] . '">
        <meta name="twitter:card" content="' . $metadata['twitter']['card'] . '">
        <meta name="twitter:title" content="' . ($metadata['twitter']['title'] ?? $metadata['title']) . '">
        <meta name="twitter:description" content="' . ($metadata['twitter']['description'] ?? $metadata['description']) . '">
        <meta name="twitter:image" content="' . $metadata['twitter']['images'][0] . '">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" href="/favicon.ico">
        ';
    }
}
