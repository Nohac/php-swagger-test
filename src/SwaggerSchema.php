<?php
/**
 * User: jg
 * Date: 22/05/17
 * Time: 09:29
 */

namespace ByJG\Swagger;

use ByJG\Swagger\Exception\DefinitionNotFoundException;
use ByJG\Swagger\Exception\HttpMethodNotFoundException;
use ByJG\Swagger\Exception\InvalidDefinitionException;
use ByJG\Swagger\Exception\NotMatchedException;
use ByJG\Swagger\Exception\PathNotFoundException;
use ByJG\Swagger\Exception\SchemaParseException;

class SwaggerSchema
{
    protected $schema;
    protected $allowNullValues;
    protected $specificationVersion;

    const SWAGGER_PATHS="paths";
    const SWAGGER_PARAMETERS="parameters";

    public function __construct($schema, $allowNullValues = false)
    {
        $this->schema = json_decode($schema, true)??yaml_parse($schema)??null;

        $this->allowNullValues = (bool) $allowNullValues;
        $this->specificationVersion = isset($this->schema['swagger']) ? '2' : '3';
    }

    /**
     * Returns the major specification version
     * @return string
     */
    public function getSpecificationVersion()
    {
        return $this->specificationVersion;
    }

    public function getServerUrl()
    {
        return isset($this->schema['servers']) ? $this->schema['servers'][0]['url'] : '';
    }

    public function getHttpSchema()
    {
        return isset($this->schema['schemes']) ? $this->schema['schemes'][0] : '';
    }

    public function getHost()
    {
        return isset($this->schema['host']) ? $this->schema['host'] : '';
    }

    public function getBasePath()
    {
        if ($this->getSpecificationVersion() === '3') {
            $basePath =isset($this->schema['servers']) ? explode('/', $this->schema['servers'][0]['url']) : '';
            return is_array($basePath) ? '/' . end($basePath) : $basePath;
        }

        return isset($this->schema['basePath']) ? $this->schema['basePath'] : '';
    }

    /**
     * @param $path
     * @param $method
     * @return mixed
     * @throws \ByJG\Swagger\Exception\HttpMethodNotFoundException
     * @throws \ByJG\Swagger\Exception\NotMatchedException
     * @throws \ByJG\Swagger\Exception\PathNotFoundException
     */
    public function getPathDefinition($path, $method)
    {
        $method = strtolower($method);

        $path = preg_replace('~^' . $this->getBasePath() . '~', '', $path);

        // Try direct match
        if (isset($this->schema[self::SWAGGER_PATHS][$path])) {
            if (isset($this->schema[self::SWAGGER_PATHS][$path][$method])) {
                return $this->schema[self::SWAGGER_PATHS][$path][$method];
            }
            throw new HttpMethodNotFoundException("The http method '$method' not found in '$path'");
        }

        // Try inline parameter
        foreach (array_keys($this->schema[self::SWAGGER_PATHS]) as $pathItem) {
            if (strpos($pathItem, '{') === false) {
                continue;
            }

            $pathItemPattern = '~^' . preg_replace('~\{(.*?)\}~', '(?<\1>[^/]+)', $pathItem) . '$~';

            $matches = [];
            if (preg_match($pathItemPattern, $path, $matches)) {
                $pathDef = $this->schema[self::SWAGGER_PATHS][$pathItem];
                if (!isset($pathDef[$method])) {
                    throw new HttpMethodNotFoundException("The http method '$method' not found in '$path'");
                }

                $this->validateArguments('path', $pathDef[$method][self::SWAGGER_PARAMETERS]??[], $matches);

                return $pathDef[$method];
            }
        }

        throw new PathNotFoundException('Path "' . $path . '" not found');
    }

    /**
     * @param $parameterIn
     * @param $parameters
     * @param $arguments
     * @throws \ByJG\Swagger\Exception\NotMatchedException
     */
    private function validateArguments($parameterIn, $parameters, $arguments)
    {
        if ($this->getSpecificationVersion() === '3') {
            foreach ($parameters as $parameter) {
                if ($parameter['schema']['type'] === "integer"
                    && filter_var($arguments[$parameter['name']], FILTER_VALIDATE_INT) === false) {
                    throw new NotMatchedException('Path expected an integer value');
                }
            }
            return;
        }

        foreach ($parameters as $parameter) {
            if ($parameter['in'] === $parameterIn
                && $parameter['type'] === "integer"
                && filter_var($arguments[$parameter['name']], FILTER_VALIDATE_INT) === false) {
                throw new NotMatchedException('Path expected an integer value');
            }
        }
    }

    /**
     * @param $name
     * @return mixed
     * @throws DefinitionNotFoundException
     * @throws InvalidDefinitionException
     */
    public function getDefintion($name)
    {
        $nameParts = explode('/', $name);

        if ($this->getSpecificationVersion() === '3') {
            if (count($nameParts) < 4 || $nameParts[0] !== '#') {
                throw new InvalidDefinitionException('Invalid Component');
            }

            if (!isset($this->schema[$nameParts[1]][$nameParts[2]][$nameParts[3]])) {
                throw new DefinitionNotFoundException("Component'$name' not found");
            }

            $def = $this->schema[$nameParts[1]][$nameParts[2]][$nameParts[3]];
            if (isset($def["allOf"])) {
                $props = $this->resolveAllOfProps($def["allOf"]);
                unset($def["allOf"]);
                $def["properties"] = $props;
            }

            return $def;
        }

        if (count($nameParts) < 3 || $nameParts[0] !== '#') {
            throw new InvalidDefinitionException('Invalid Definition');
        }

        if (!isset($this->schema[$nameParts[1]][$nameParts[2]])) {
            throw new DefinitionNotFoundException("Definition '$name' not found");
        }

        return $this->schema[$nameParts[1]][$nameParts[2]];
    }

    private function resolveAllOfProps($all)
    {
        $props = [];
        foreach ($all as $one) {
            if (isset($one['$ref'])) {
                $d = $this->getDefintion($one['$ref']);
                $props += $d["properties"];
                continue;
            }

            $props += $one["properties"];
        }

        return $props;
    }

    /**
     * @param $path
     * @param $method
     * @return \ByJG\Swagger\SwaggerRequestBody
     * @throws \ByJG\Swagger\Exception\HttpMethodNotFoundException
     * @throws \ByJG\Swagger\Exception\NotMatchedException
     * @throws \ByJG\Swagger\Exception\PathNotFoundException
     */
    public function getRequestParameters($path, $method)
    {
        $structure = $this->getPathDefinition($path, $method);

        if ($this->getSpecificationVersion() === '3') {
            if (!isset($structure['requestBody'])) {
                return new SwaggerRequestBody($this, "$method $path", []);
            }
            return new SwaggerRequestBody($this, "$method $path", $structure['requestBody']);
        }

        if (!isset($structure[self::SWAGGER_PARAMETERS])) {
            return new SwaggerRequestBody($this, "$method $path", []);
        }
        return new SwaggerRequestBody($this, "$method $path", $structure[self::SWAGGER_PARAMETERS]);
    }

    /**
     * @param $path
     * @param $method
     * @param $status
     * @return \ByJG\Swagger\SwaggerResponseBody
     * @throws \ByJG\Swagger\Exception\HttpMethodNotFoundException
     * @throws \ByJG\Swagger\Exception\InvalidDefinitionException
     * @throws \ByJG\Swagger\Exception\NotMatchedException
     * @throws \ByJG\Swagger\Exception\PathNotFoundException
     */
    public function getResponseParameters($path, $method, $status)
    {
        $structure = $this->getPathDefinition($path, $method);

        if (!isset($structure['responses'][$status])) {
            throw new InvalidDefinitionException("Could not found status code '$status' in '$path' and '$method'");
        }

        return new SwaggerResponseBody($this, "$method $status $path", $structure['responses'][$status]);
    }

    /**
     * OpenApi 2.0 doesn't describe null values, so this flag defines,
     * if match is ok when one of property
     *
     * @return bool
     */
    public function isAllowNullValues()
    {
        return $this->allowNullValues;
    }

    /**
     * OpenApi 2.0 doesn't describe null values, so this flag defines,
     * if match is ok when one of property
     *
     * @param $value
     */
    public function setAllowNullValues($value)
    {
        $this->allowNullValues = (bool) $value;
    }
}
