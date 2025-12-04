<?php

namespace EasyPack\ApiDocs\Docs;

use Illuminate\Http\Response;
use Illuminate\Support\Str;
use EasyPack\ApiDocs\Domain\Traits\NamesAndPathLocations;

class APICall
{
    use NamesAndPathLocations;

    const CONSUME_JSON = 'application/json';
    const CONSUME_MULTIPART_FORM = 'multipart/form-data';
    const CONSUME_FORM_URLENCODED = 'application/x-www-form-urlencoded';

    protected $version = '1.0.0';
    protected $method = '';
    protected $name;
    protected $route;
    protected $group;
    protected $params = [];
    protected $description;
    protected $successParams = [];
    protected $requestExample = [];
    protected $headers = [];
    protected $define = [];
    protected $use = [];
    protected $addDefaultHeaders = true;
    protected $successExamples = [];
    protected $errorExamples = [];
    protected $successObject;
    protected $successPaginatedObject;
    protected $successMessageOnly = false;
    protected $operationId;
    protected $consumes = [];

    public function getRoute()
    {
        return $this->route;
    }

    public function setRoute($route)
    {
        $this->route = $route;
        return $this;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function setParams(array $params)
    {
        $tempParams = [];

        foreach ($params as $param) {
            // parse strings
            if (is_string($param)) {
                $args = explode('|', $param);

                if (!isset($args[0])) {
                    throw new \InvalidArgumentException("Invalid param value. You've given {$param}, and the format is incorrect.");
                }
                $param = new Param($args[0]);

                // start from 1, because 0 is already handled above
                for ($i = 1, $iMax = count($args); $i < $iMax; $i++) {
                    $arg = $args[$i];
                    if (empty($arg)) {
                        continue;
                    }

                    // check for data types
                    if (in_array($arg, Param::getDataTypes())) {
                        $param->setDataType($arg);
                        continue;
                    }

                    // check other known values
                    if ($arg === 'required') {
                        $param->required();
                        continue;
                    }
                    if ($arg === 'optional') {
                        $param->optional();
                        continue;
                    }

                    // check variables
                    if (str_starts_with($arg, '{{')) {
                        $param->setVariable($arg);
                        continue;
                    }

                    // other values
                    $argTypes = explode(':', $arg);
                    if (is_countable($argTypes) && count($argTypes) > 1) {
                        $type = $argTypes[0];
                        $arg  = $argTypes[1];

                        // if we have a function for that argument type, set it
                        $functionName = 'set' . Str::studly($type);
                        if (method_exists($param, $functionName)) {
                            $param->$functionName($arg);
                            continue;
                        }
                    }

                    // if nothing else matches, set as description
                    $param->setDescription($arg);
                }
            }

            if (!$param instanceof Param) {
                throw new \InvalidArgumentException("setParams can only accept Param objects or strings");
            }

            $tempParams[] = $param;
        }

        $this->params = $tempParams;
        return $this;
    }

    public function getGroup()
    {
        return $this->group;
    }

    public function setGroup($group)
    {
        $this->group = $group;
        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method)
    {
        $this->method = $method;
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getRequestExample(): array
    {
        return $this->requestExample;
    }

    public function setRequestExample(array $requestExample)
    {
        $this->requestExample = $requestExample;
        return $this;
    }

    public function setSuccessParams(array $successParams)
    {
        $this->successParams = $successParams;
        return $this;
    }

    public function setHeaders(array $headers)
    {
        foreach ($headers as $header) {
            $this->addHeader($header);
        }
        return $this;
    }

    public function addHeader(Param $param, $allowDuplicate = false)
    {
        if ($param instanceof Param) {
            $param->setLocation(Param::LOCATION_HEADER);
        }

        $isAdded = false;
        if (!$allowDuplicate) {
            foreach ($this->headers as &$header) {
                if ($header->getName() === $param->getName()) {
                    $header = $param;
                    $isAdded = true;
                }
            }
        }

        if (!$isAdded) {
            $this->headers[] = $param;
        }

        return $this;
    }

    public function setApiKeyHeader()
    {
        $this->noDefaultHeaders();

        $this->setHeaders([
            (new Param('Accept', Param::TYPE_STRING, '`application/json`'))
                ->setDefaultValue(self::CONSUME_JSON),
            (new Param('x-api-key', 'String', 'API Key'))
                ->setDefaultValue('{{x-api-key}}'),
        ]);

        return $this;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version)
    {
        $this->version = $version;
        return $this;
    }

    public function getDefine()
    {
        return $this->define;
    }

    public function setDefine($title, $description = '')
    {
        $this->define = [
            'title' => $title,
            'description' => $description,
        ];
        return $this;
    }

    public function getUse(): array
    {
        return $this->use;
    }

    public function setUse($definedName)
    {
        $this->use[] = $definedName;
        return $this;
    }

    public function isAddDefaultHeaders(): bool
    {
        return $this->addDefaultHeaders;
    }

    public function noDefaultHeaders()
    {
        $this->addDefaultHeaders = false;
        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setSuccessExample($successExample, $statusCode = 200, $statusMessage = null): APICall
    {
        $this->successExamples[] = [
            'text' => $successExample,
            'statusCode' => $statusCode,
            'message' => $this->getStatusTextByCode($statusCode, $statusMessage),
        ];
        return $this;
    }

    public function setErrorExample($errorExample, $statusCode = 404, $message = null): APICall
    {
        $this->errorExamples[] = [
            'text' => $errorExample,
            'statusCode' => $statusCode,
            'message' => $this->getStatusTextByCode($statusCode, $message),
        ];
        return $this;
    }

    protected function getStatusTextByCode($statusCode, $text = null)
    {
        if ($text) {
            return $text;
        }

        $statusCodes = Response::$statusTexts;
        return isset($statusCodes[$statusCode]) ? $statusCodes[$statusCode] : 'Unknown';
    }

    public function getConsumes(): array
    {
        return $this->consumes;
    }

    public function setConsumes(array $consumes)
    {
        $this->consumes = $consumes;
        return $this;
    }

    public function hasFileUploads()
    {
        $this->setConsumes([APICall::CONSUME_MULTIPART_FORM]);
        return $this;
    }

    public function getSuccessObject()
    {
        return $this->successObject;
    }

    public function setSuccessObject($successObject)
    {
        $this->successObject = $successObject;
        return $this;
    }

    public function getSuccessPaginatedObject()
    {
        return $this->successPaginatedObject;
    }

    public function setSuccessPaginatedObject($successPaginatedObject)
    {
        $this->successPaginatedObject = $successPaginatedObject;
        return $this;
    }

    /**
     * Check if this endpoint returns only a message (no payload data)
     *
     * @return bool
     */
    public function isSuccessMessageOnly(): bool
    {
        return $this->successMessageOnly;
    }

    /**
     * Mark this endpoint as returning only a success message without payload data.
     * Use this for endpoints like logout, delete, or other actions that don't return model data.
     *
     * @return $this
     */
    public function setSuccessMessageOnly()
    {
        $this->successMessageOnly = true;
        return $this;
    }

    public function getOperationId()
    {
        if (empty($this->operationId)) {
            // build an operation ID
            $name = [];
            if (!empty($this->group)) {
                $name[] = $this->group;
            }
            if (!empty($this->method)) {
                $name[] = $this->method;
            }
            if (!empty($this->name)) {
                $name[] = $this->name;
            }
            $name = implode('_', $name);

            // if still nothing, create a random one
            if (empty($name)) {
                $name = Str::random(10);
            }

            $this->operationId = Str::snake(strtolower($name));
        }

        return $this->operationId;
    }

    public function setOperationId($operationId): APICall
    {
        $this->operationId = $operationId;
        return $this;
    }

    public function getSuccessExamples(): array
    {
        return $this->successExamples;
    }

    public function getErrorExamples(): array
    {
        return $this->errorExamples;
    }

    public function getSuccessParams(): array
    {
        return $this->successParams;
    }

    /**
     * Returns the composed ApiDoc
     *
     * @return string
     * @throws \Exception
     */
    public function getApiDoc()
    {
        $lines = [];

        $lines[] = "###";

        if (!empty($define = $this->getDefine())) {
            $lines[] = "@apiDefine {$define['title']} {$define['description']}";
        }

        $description = $this->getDescription();
        if (!empty($description)) {
            $lines[] = "@apiDescription {$description}";
        }

        $lines[] = "@apiVersion {$this->getVersion()}";
        $lines[] = "@api {{$this->getMethod()}} {$this->getRoute()} {$this->getName()}";
        $lines[] = "@apiGroup " . ucwords($this->getGroup());

        // params
        foreach ($this->params as $param) {
            if ($param instanceof Param) {
                $fieldName = $param->getName();

                if (empty($fieldName)) {
                    throw new \Exception('The parameters requires a fieldname');
                }

                if (!$param->getRequired()) {
                    $fieldName = '[' . $fieldName . ']';
                }
                $lines[] = "@apiParam {{$param->getDataType()}} {$fieldName} {$param->getDescription()}";
            } else {
                $lines[] = "@apiParam {$param}";
            }
        }

        // success params
        foreach ($this->successParams as $param) {
            if ($param instanceof Param) {
                $fieldName = $param->getName();

                if (empty($fieldName)) {
                    throw new \Exception('The parameters requires a fieldname');
                }

                if (!$param->getRequired()) {
                    $fieldName = '[' . $fieldName . ']';
                }
                $lines[] = "@apiSuccess {{$param->getDataType()}} {$fieldName} {$param->getDescription()}";
            } else {
                $lines[] = "@apiSuccess {$param}";
            }
        }

        // headers
        foreach ($this->headers as $param) {
            if ($param instanceof Param) {
                $fieldName = $param->getName();

                if (empty($fieldName)) {
                    throw new \Exception('The parameters requires a fieldname');
                }

                if (!$param->getRequired()) {
                    $fieldName = '[' . $fieldName . ']';
                }
                $lines[] = "@apiHeader {{$param->getDataType()}} {$fieldName} {$param->getDescription()}";
            } else {
                $lines[] = "@apiHeader {$param}";
            }
        }

        // use
        foreach ($this->use as $use) {
            $lines[] = "@apiUse $use";
        }

        $requestExampleParams = $this->getRequestExample();
        if (!empty($requestExampleParams)) {
            $lines[] = "@apiParamExample {json} Request Example ";
            $lines[] = json_encode($requestExampleParams);
        }

        $this->addStoredApiResponses();

        $successExamples = $this->successExamples;
        foreach ($successExamples as $successExample) {
            $lines[] = "@apiSuccessExample {json} Success-Response / HTTP {$successExample['statusCode']} {$successExample['message']}";
            $lines[] = $successExample['text'];
        }

        $errorExamples = $this->errorExamples;
        foreach ($errorExamples as $errorExample) {
            $lines[] = "@apiErrorExample {json} Error-Response / HTTP {$errorExample['statusCode']} {$errorExample['message']}";
            $lines[] = $errorExample['text'];
        }

        $lines[] = "###";

        return implode("\r\n", $lines);
    }

    /**
     * Add stored API responses from files
     */
    protected function addStoredApiResponses()
    {
        // if there are no responses, see if we have saved examples
        if (empty($this->successExamples)) {
            $responses = $this->getStoredApiResponses();

            foreach ($responses as $code => $content) {
                // all 2xx responses are taken as success responses
                if (strpos($code, '2') === 0) {
                    $this->setSuccessExample($content, $code);
                } else {
                    $this->setErrorExample($content, $code);
                }
            }
        }
    }

    /**
     * Get stored API responses from files
     *
     * @return array
     */
    protected function getStoredApiResponses()
    {
        $response = [];
        $processedFileNames = [];

        // get files matching this operationId
        // files names will look like `auth_post_login_200.json`, `auth_post_login_422.json`

        $dirPath = self::getApiResponsesManualDir();
        $manualFiles = glob($dirPath . DIRECTORY_SEPARATOR . $this->getOperationId() . '_*');

        $dirPath = self::getApiResponsesAutoGenDir();
        $autoGenFiles = glob($dirPath . DIRECTORY_SEPARATOR . $this->getOperationId() . '_*');

        // if there's a file in manual folder, take that and ignore others
        foreach (array_merge($manualFiles, $autoGenFiles) as $file) {
            $fileName = pathinfo($file, PATHINFO_FILENAME);

            if (in_array($fileName, $processedFileNames)) {
                continue;
            }
            $processedFileNames[] = $fileName;

            // split operationID and status code
            // (auth_post_login)_(422)
            preg_match('/(.*)_(\d*)/', $fileName, $matches);
            if (count($matches) === 3) {
                $response[$matches[2]] = file_get_contents($file);
            }
        }

        return $response;
    }
}
