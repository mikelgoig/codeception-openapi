<?php

declare(strict_types=1);

namespace MikelGoig\Codeception\Module;

use Codeception\Lib\Interfaces\DependsOnModule;
use Codeception\Lib\Interfaces\PartedModule;
use Codeception\Module;
use Codeception\Module\REST as RestModule;
use Codeception\Module\Symfony as SymfonyModule;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Response as Psr7Response;
use League\OpenAPIValidation\PSR7\Exception\ValidationFailed;
use League\OpenAPIValidation\PSR7\OperationAddress;
use League\OpenAPIValidation\PSR7\RequestValidator;
use League\OpenAPIValidation\PSR7\ResponseValidator;
use League\OpenAPIValidation\PSR7\ValidatorBuilder;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\BrowserKit\Response;
use Webmozart\Assert\Assert;

class OpenApi extends Module implements DependsOnModule, PartedModule
{
    protected array $config = [
        'openapi' => null,
        'multipart_boundary' => null,
    ];

    protected RestModule $restModule;
    protected SymfonyModule $symfonyModule;
    protected string $openapiFile;
    protected ?string $multipartBoundary;
    protected string $errorMessage = '';
    protected string $dependencyMessage = <<<EOF
Example configuring module:
--
modules:
    enabled:
        - MikelGoig\Codeception\Module\OpenApi:
            depends: [ REST, Symfony ]
            openapi: path/to/openapi.yaml
--
EOF;

    public function _depends(): array
    {
        return [
            RestModule::class => $this->dependencyMessage,
            SymfonyModule::class => $this->dependencyMessage,
        ];
    }

    public function _parts(): array
    {
        return ['gherkin'];
    }

    public function _inject(RestModule $restModule, SymfonyModule $symfonyModule): void
    {
        $this->restModule = $restModule;
        $this->symfonyModule = $symfonyModule;
    }

    public function _initialize(): void
    {
        Assert::string($this->config['openapi']);
        $this->openapiFile = $this->config['openapi'];
        Assert::nullOrString($this->config['multipart_boundary']);
        $this->multipartBoundary = $this->config['multipart_boundary'];
    }

    /**
     * Checks that a request matches the OpenAPI specification.
     *
     * @part gherkin
     */
    public function seeRequestMatchesOpenApiSpecification(): void
    {
        $this->assertTrue($this->validateRequest(), $this->errorMessage);
    }

    /**
     * Checks that a response matches the OpenAPI specification.
     *
     * @part gherkin
     */
    public function seeResponseMatchesOpenApiSpecification(): void
    {
        $this->assertTrue($this->validateResponse(), $this->errorMessage);
    }

    #--------------------------------------------------------------------------
    # Gherkin Steps
    #--------------------------------------------------------------------------

    /**
     * @Then /^the request matches the OpenAPI specification$/
     *
     * @part gherkin
     */
    public function stepSeeRequestMatchesOpenApiSpecification(): void
    {
        $this->seeRequestMatchesOpenApiSpecification();
    }

    /**
     * @Then /^the response matches the OpenAPI specification$/
     *
     * @part gherkin
     */
    public function stepSeeResponseMatchesOpenApiSpecification(): void
    {
        $this->seeResponseMatchesOpenApiSpecification();
    }

    #--------------------------------------------------------------------------

    protected function validateRequest(): bool
    {
        $validator = $this->requestValidator();
        $request = $this->psr7Request();
        try {
            $validator->validate($request);
        } catch (ValidationFailed $e) {
            $this->setErrorMessage($e);
            return false;
        }
        return true;
    }

    protected function requestValidator(): RequestValidator
    {
        return (new ValidatorBuilder())->fromYamlFile($this->openapiFile)->getRequestValidator();
    }

    protected function psr7Request(): Psr7Request
    {
        $internalRequest = $this->request();
        $method = $internalRequest->getMethod();
        $uri = $internalRequest->getUri();
        $request = $this->symfonyModule->client?->getRequest();
        \assert($request instanceof \Symfony\Component\HttpFoundation\Request);

        // Transform headers to meet Guzzle's requirements
        $headers = array_map(
            fn (array $values) => array_filter($values, fn ($value) => $value !== null), // Remove nulls
            $request->headers->all(),
        );

        if ($this->isRequestMultipart()) {
            $multipartData = [];

            // Add form fields
            foreach ($internalRequest->getParameters() as $name => $value) {
                $multipartData[] = [
                    'name' => $name,
                    'contents' => $value,
                ];
            }

            // Add files
            foreach ($internalRequest->getFiles() as $name => $file) {
                if (is_array($file) && isset($file['tmp_name']) && is_string($file['tmp_name'])) {
                    $multipartData[] = [
                        'name' => $name,
                        'contents' => fopen($file['tmp_name'], 'r'),
                        'filename' => $file['name'],
                        'headers' => [
                            'Content-Type' => $file['type'],
                        ],
                    ];
                }
            }

            $multipartStream = new MultipartStream($multipartData, $this->multipartBoundary);
            return new Psr7Request($method, $uri, $headers, $multipartStream);
        }

        return new Psr7Request($method, $uri, $headers, $internalRequest->getContent());
    }

    protected function request(): Request
    {
        return $this->restModule->client->getInternalRequest();
    }

    protected function isRequestMultipart(): bool
    {
        return $this->symfonyModule->client?->getRequest()->headers->contains(
            'content-type',
            'multipart/form-data',
        ) ?? false;
    }

    protected function validateResponse(): bool
    {
        $validator = $this->responseValidator();
        $request = $this->psr7Request();
        $response = $this->psr7Response();
        $operation = new OperationAddress($this->pathPattern($request), strtolower($request->getMethod()));
        try {
            $validator->validate($operation, $response);
        } catch (ValidationFailed $e) {
            $this->setErrorMessage($e);
            return false;
        }
        return true;
    }

    protected function responseValidator(): ResponseValidator
    {
        return (new ValidatorBuilder())->fromYamlFile($this->openapiFile)->getResponseValidator();
    }

    protected function psr7Response(): Psr7Response
    {
        $internalResponse = $this->response();
        return new Psr7Response(
            $internalResponse->getStatusCode(),
            $internalResponse->getHeaders(),
            $internalResponse->getContent(),
        );
    }

    protected function response(): Response
    {
        return $this->restModule->client->getInternalResponse();
    }

    protected function pathPattern(RequestInterface $request): string
    {
        $path = $request->getUri()->getPath();
        return preg_replace(
            '/\/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/',
            '/{id}',
            $path,
        ) ?? $path;
    }

    private function setErrorMessage(ValidationFailed $exception): void
    {
        $errorMessage = $exception->getMessage();
        $previousMessage = $exception->getPrevious()?->getMessage();
        if ($previousMessage !== null) {
            $errorMessage .= ' -> ' . $previousMessage;
        }
        $this->errorMessage = $errorMessage;
    }
}
