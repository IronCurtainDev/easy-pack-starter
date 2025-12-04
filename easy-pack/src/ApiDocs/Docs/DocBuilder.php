<?php

namespace EasyPack\ApiDocs\Docs;

use EasyPack\ApiDocs\Exceptions\DocumentationModeEnabledException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DocBuilder
{
    protected $apiCalls;
    protected $attributes = [];

    public function __construct()
    {
        $this->apiCalls = new Collection();
    }

    public function reset()
    {
        $this->apiCalls = new Collection();
    }

    /**
     * Register an API Call with the Doc Builder
     */
    public function register(APICall $apiCall)
    {
        if (!env('DOCUMENTATION_MODE', false)) {
            return;
        }

        // set defines or uses
        $define = $apiCall->getDefine();
        if (!empty($define)) {
            $group = $apiCall->getGroup();
            if (empty($group)) {
                $apiCall->setGroup(Str::snake($define['title']));
            }
            $this->apiCalls->push($apiCall);
            return;
        } else {
            if ($apiCall->isAddDefaultHeaders()) {
                $apiCall->setUse('default_headers');
            }
        }

        if (empty($apiCall->getRoute())) {
            if (!empty($this->attributes['uri'])) {
                $apiCall->setRoute($this->attributes['uri']);
            } else {
                throw new \Exception("The route must be set for the API call");
            }
        }

        if (empty($apiCall->getMethod())) {
            if (!empty($this->attributes['method'])) {
                $apiCall->setMethod($this->attributes['method']);
            } else {
                $apiCall->setMethod('get');
            }
        }

        // set default group
        $group = $apiCall->getGroup();
        if (empty($group)) {
            // Get the full controller name and extract the Prefix from {Prefix}Controller as the default group
            if (isset($this->attributes['action'])) {
                $parts = explode('@', $this->attributes['action']);
                $reflection = new \ReflectionClass($parts[0]);
                if ($reflection) {
                    $group = str_replace('Controller', '', $reflection->getShortName());
                    $apiCall->setGroup($group);
                }
            }
        }

        // try to set a default name
        $name = $apiCall->getName();
        if (empty($name)) {
            $singularGroup = Str::singular($group);
            $method = strtolower($apiCall->getMethod());
            $newName = match ($method) {
                'post' => "Create a $singularGroup",
                'delete' => "Delete a $singularGroup",
                'put', 'patch' => "Update a $singularGroup",
                'get' => $this->getDefaultGetName($singularGroup),
                default => "Get a $singularGroup",
            };

            if (empty($newName)) {
                $newName = '<UNKNOWN NAME>';
            }
            $apiCall->setName($newName);
        }

        // if there's still no group, set a default group
        if (empty($group)) {
            $apiCall->setGroup('Misc');
        }

        $this->apiCalls->push($apiCall);
    }

    protected function getDefaultGetName($singularGroup)
    {
        $newName = "Get a $singularGroup";
        if (isset($this->attributes['action'])) {
            $action = strtolower($this->attributes['action']);
            if (str_contains($action, 'search')) {
                $newName = "List " . Str::plural($singularGroup);
            }
            if (str_contains($action, 'index')) {
                $newName = "Search $singularGroup";
            }
        }
        return $newName;
    }

    public function findByDefinition($defineName)
    {
        $apiCalls = $this->apiCalls->filter(function (APICall $item) use ($defineName) {
            $define = $item->getDefine();
            if (isset($define['title'])) {
                return $define['title'] === $defineName;
            }
            return false;
        });

        if ($apiCalls->isNotEmpty()) {
            return $apiCalls->first();
        }

        return null;
    }

    public function setInterceptor($method, $uri, $action)
    {
        $this->attributes['method'] = $method;
        $this->attributes['uri'] = $uri;
        $this->attributes['action'] = $action;
    }

    public function clearInterceptor()
    {
        $this->attributes = [];
    }

    public function getApiCalls()
    {
        return $this->apiCalls;
    }

    public function throwDocumentationModeException()
    {
        throw new DocumentationModeEnabledException("Requests cannot be executed while in documentation mode.");
    }
}
