<?php

namespace App\Modernization\Domain;

class SeoUrlRewrite
{
    /**
     * @return array{request_path: string, target_path: string, canonical: string, redirect: bool, sitemap: bool, RSS: bool, SEO: bool}
     */
    public function resolve(string $requestPath, string $targetPath, string $storeCode = 'default'): array
    {
        return [
            'request_path' => trim($requestPath, '/'),
            'target_path' => trim($targetPath, '/'),
            'canonical' => "/{$storeCode}/".trim($requestPath, '/'),
            'redirect' => $requestPath !== $targetPath,
            'sitemap' => true,
            'RSS' => true,
            'SEO' => true,
        ];
    }
}
