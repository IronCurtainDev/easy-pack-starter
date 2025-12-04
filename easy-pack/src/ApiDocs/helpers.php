<?php

if (!function_exists('document')) {
    /**
     * Add a call to API documentation
     *
     * @param Closure $closure
     * @return boolean
     */
    function document(Closure $closure)
    {
        if (!env('DOCUMENTATION_MODE', false)) {
            return;
        }

        $apiRequest = $closure();

        /** @var \EasyPack\ApiDocs\Docs\DocBuilder $docBuilder */
        $docBuilder = app('api-docs.builder');

        /** @var \EasyPack\ApiDocs\Docs\APICall $apiRequest */
        $docBuilder->register($apiRequest);

        if (env('DOCUMENTATION_MODE')) {
            $docBuilder->throwDocumentationModeException();
        }

        return true;
    }
}
