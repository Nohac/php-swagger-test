<?php

namespace Test;

use ByJG\Swagger\SwaggerSchema;
use PHPUnit\Framework\TestCase;

class OpenApiSchemaTest extends TestCase
{
    /**
     * @var SwaggerSchema
     */
    protected $openapiObject;

    public function setUp()
    {
        $this->openapiObject = new SwaggerSchema(file_get_contents(__DIR__ . '/example/openapi.json'));
    }

    public function tearDown()
    {
        $this->openapiObject = null;
    }

    public function testGetBasePath()
    {
        $this->assertEquals('/v2', $this->openapiObject->getBasePath());
    }

    /**
     * @throws \ByJG\Swagger\Exception\HttpMethodNotFoundException
     * @throws \ByJG\Swagger\Exception\NotMatchedException
     * @throws \ByJG\Swagger\Exception\PathNotFoundException
     */
    public function testGetPathDirectMatch()
    {
        $this->assertEquals(
            [
                'tags' => [
                    'pet',
                ],
                'summary' => 'Add a new pet to the store',
                'description' => '',
                'operationId' => 'addPet',
                'requestBody' => [
                    '$ref' => '#/components/requestBodies/Pet',
                ],
                'responses' => [
                    '405' => [
                        'description' => 'Invalid input',
                    ],
                ],
                'security' => [
                    [
                        'petstore_auth' => [
                            'write:pets',
                            'read:pets',
                        ],
                    ],
                ],
            ],
            $this->openapiObject->getPathDefinition('/v2/pet', 'post')
        );
        $this->assertEquals(
            [
                'tags' => [
                    'pet',
                ],
                'summary' => 'Update an existing pet',
                'description' => '',
                'operationId' => 'updatePet',
                'requestBody' => [
                    '$ref' => '#/components/requestBodies/Pet',
                ],
                'responses' => [
                    '400' => [
                        'description' => 'Invalid ID supplied',
                    ],
                    '404' => [
                        'description' => 'Pet not found',
                    ],
                    '405' => [
                        'description' => 'Validation exception',
                    ],
                ],
                'security' => [
                    [
                        'petstore_auth' => [
                            'write:pets',
                            'read:pets',
                        ],
                    ],
                ],
            ],
            $this->openapiObject->getPathDefinition('/v2/pet', 'put')
        );
    }

    /**
     * @throws \ByJG\Swagger\Exception\HttpMethodNotFoundException
     * @throws \ByJG\Swagger\Exception\NotMatchedException
     * @throws \ByJG\Swagger\Exception\PathNotFoundException
     */
    public function testGetPathPatternMatch()
    {
        $this->assertEquals(
            [
                'tags' => [
                    'pet',
                ],
                'summary' => 'Find pet by ID',
                'description' => 'Returns a single pet',
                'operationId' => 'getPetById',
                'parameters' => [
                    [
                        'name' => 'petId',
                        'in' => 'path',
                        'description' => 'ID of pet to return',
                        'required' => true,
                        'schema' => [
                            'type' => 'integer',
                            'format' => 'int64',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'successful operation',
                        'content' => [
                            'application/xml' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/Pet',
                                ],
                            ],
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/Pet',
                                ],
                            ],
                        ],
                    ],
                    '400' => [
                        'description' => 'Invalid ID supplied',
                    ],
                    '404' => [
                        'description' => 'Pet not found',
                    ],
                ],
                'security' => [
                    [
                        'api_key' => [],
                    ],
                ],
            ],
            $this->openapiObject->getPathDefinition('/v2/pet/10', 'get')
        );
        $this->assertEquals(
            [
                'tags' => [
                    'pet',
                ],
                'summary' => 'Updates a pet in the store with form data',
                'description' => '',
                'operationId' => 'updatePetWithForm',
                'parameters' => [
                    [
                        'name' => 'petId',
                        'in' => 'path',
                        'description' => 'ID of pet that needs to be updated',
                        'required' => true,
                        'schema' => [
                            'type' => 'integer',
                            'format' => 'int64',
                        ],
                    ],
                ],
                'requestBody' => [
                    'content' => [
                        'application/x-www-form-urlencoded' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'name' => [
                                        'description' => 'Updated name of the pet',
                                        'type' => 'string',
                                    ],
                                    'status' => [
                                        'description' => 'Updated status of the pet',
                                        'type' => 'string',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '405' => [
                        'description' => 'Invalid input',
                    ],
                ],
                'security' => [
                    [
                        'petstore_auth' => [
                            'write:pets',
                            'read:pets',
                        ],
                    ],
                ],
            ],
            $this->openapiObject->getPathDefinition('/v2/pet/10', 'post')
        );
        $this->assertEquals(
            [
                'tags' => [
                    'pet',
                ],
                'summary' => 'Deletes a pet',
                'description' => '',
                'operationId' => 'deletePet',
                'parameters' => [
                    [
                        'name' => 'api_key',
                        'in' => 'header',
                        'required' => false,
                        'schema' => [
                            'type' => 'string',
                        ],
                    ],
                    [
                        'name' => 'petId',
                        'in' => 'path',
                        'description' => 'Pet id to delete',
                        'required' => true,
                        'schema' => [
                            'type' => 'integer',
                            'format' => 'int64',
                        ],
                    ],
                ],
                'responses' => [
                    '400' => [
                        'description' => 'Invalid ID supplied',
                    ],
                    '404' => [
                        'description' => 'Pet not found',
                    ],
                ],
                'security' => [
                    [
                        'petstore_auth' => [
                            'write:pets',
                            'read:pets',
                        ],
                    ],
                ],
            ],
            $this->openapiObject->getPathDefinition('/v2/pet/10', 'delete')
        );
    }

    /**
     * @throws \ByJG\Swagger\Exception\HttpMethodNotFoundException
     * @throws \ByJG\Swagger\Exception\NotMatchedException
     * @throws \ByJG\Swagger\Exception\PathNotFoundException
     */
    public function testGetPathPatternMatch2()
    {
        $this->assertEquals(
            [
                'tags' => [
                    'pet',
                ],
                'summary' => 'uploads an image',
                'description' => '',
                'operationId' => 'uploadFile',
                'parameters' => [
                    [
                        'name' => 'petId',
                        'in' => 'path',
                        'description' => 'ID of pet to update',
                        'required' => true,
                        'schema' => [
                            'type' => 'integer',
                            'format' => 'int64',
                        ],
                    ],
                ],
                'requestBody' => [
                    'content' => [
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'additionalMetadata' => [
                                        'description' => 'Additional data to pass to server',
                                        'type' => 'string',
                                    ],
                                    'file' => [
                                        'description' => 'file to upload',
                                        'type' => 'string',
                                        'format' => 'binary',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'successful operation',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/ApiResponse',
                                ],
                            ],
                        ],
                    ],
                ],
                'security' => [
                    [
                        'petstore_auth' => [
                            'write:pets',
                            'read:pets',
                        ],
                    ],
                ],
            ],
            $this->openapiObject->getPathDefinition('/v2/pet/10/uploadImage', 'post')
        );
    }

    /**
     * @expectedException \ByJG\Swagger\Exception\PathNotFoundException
     *
     * @throws \ByJG\Swagger\Exception\HttpMethodNotFoundException
     * @throws \ByJG\Swagger\Exception\NotMatchedException
     * @throws \ByJG\Swagger\Exception\PathNotFoundException
     */
    public function testGetPathFail()
    {
        $this->openapiObject->getPathDefinition('/v2/pets', 'get');
    }

    /**
     * @expectedException \ByJG\Swagger\Exception\HttpMethodNotFoundException
     *
     * @throws \ByJG\Swagger\Exception\HttpMethodNotFoundException
     * @throws \ByJG\Swagger\Exception\NotMatchedException
     * @throws \ByJG\Swagger\Exception\PathNotFoundException
     */
    public function testPathExistsButMethodDont()
    {
        $this->openapiObject->getPathDefinition('/v2/pet', 'GET');
    }

    /**
     * @throws \ByJG\Swagger\Exception\HttpMethodNotFoundException
     * @throws \ByJG\Swagger\Exception\NotMatchedException
     * @throws \ByJG\Swagger\Exception\PathNotFoundException
     */
    public function testGetPathStructure()
    {
        $pathDefintion = $this->openapiObject->getPathDefinition('/v2/pet', 'PUT');
        $this->assertEquals(
            [
                'tags' => [
                    'pet',
                ],
                'summary' => 'Update an existing pet',
                'description' => '',
                'operationId' => 'updatePet',
                'requestBody' => [
                    '$ref' => '#/components/requestBodies/Pet',
                ],
                'responses' => [
                    '400' => [
                        'description' => 'Invalid ID supplied',
                    ],
                    '404' => [
                        'description' => 'Pet not found',
                    ],
                    '405' => [
                        'description' => 'Validation exception',
                    ],
                ],
                'security' => [
                    [
                        'petstore_auth' => [
                            'write:pets',
                            'read:pets',
                        ],
                    ]
                ],
            ],
            $pathDefintion
        );
    }

    /**
     * @expectedException \ByJG\Swagger\Exception\InvalidDefinitionException
     *
     * @throws \ByJG\Swagger\Exception\DefinitionNotFoundException
     * @throws \ByJG\Swagger\Exception\InvalidDefinitionException
     */
    public function testGetDefinitionFailed()
    {
        $this->openapiObject->getDefintion('Order');
    }


    public function testGetDefinitionAllOf()
    {
        $dog = $this->openapiObject->getDefintion('#/components/schemas/Dog');
        $assert = [
            'properties' => [
                'created_at' => [
                    'type' => 'string',
                ],
                'updated_at' => [
                    'type' => 'string',
                ],
                'id' => [
                    'type' => 'integer',
                    'format' => 'int64',
                ],
                'category' => [
                    '$ref' => '#/components/schemas/Category',
                ],
                'name' => [
                    'type' => 'string',
                    'example' => 'doggie',
                ],
                'photoUrls' => [
                    'type' => 'array',
                    'xml' => [
                        'name' => 'photoUrl',
                        'wrapped' => true,
                    ],
                    'items' => [
                        'type' => 'string',
                    ],
                ],
                'tags' => [
                    'type' => 'array',
                    'xml' => [
                        'name' => 'tag',
                        'wrapped' => true,
                    ],
                    'items' => [
                        '$ref' => '#/components/schemas/Tag',
                    ],
                ],
                'status' => [
                    'type' => 'string',
                    'description' => 'pet status in the store',
                    'enum' => [
                        'available',
                        'pending',
                        'sold',
                    ],
                ],
                'breed' => [
                    'type' => 'string',
                    'enum' => [
                        'Dingo',
                        'Husky',
                        'Retriever',
                        'Shepherd',
                    ],
                ],
            ],
        ];

        $this->assertEquals($assert, $dog);
    }

    /**
     * @expectedException \ByJG\Swagger\Exception\InvalidDefinitionException
     *
     * @throws \ByJG\Swagger\Exception\DefinitionNotFoundException
     * @throws \ByJG\Swagger\Exception\InvalidDefinitionException
     */
    public function testGetDefinitionFailed2()
    {
        $this->openapiObject->getDefintion('1/2/Order');
    }

    /**
     * @expectedException \ByJG\Swagger\Exception\DefinitionNotFoundException
     *
     * @throws \ByJG\Swagger\Exception\DefinitionNotFoundException
     * @throws \ByJG\Swagger\Exception\InvalidDefinitionException
     */
    public function testGetDefinitionFailed3()
    {
        $this->openapiObject->getDefintion('#/components/schemas/OrderNOtFound');
    }

    /**
     * @throws \ByJG\Swagger\Exception\DefinitionNotFoundException
     * @throws \ByJG\Swagger\Exception\InvalidDefinitionException
     */
    public function testGetDefinition()
    {
        $expected = [
            "type"       => "object",
            "properties" => [
                "id"       => [
                    "type"   => "integer",
                    "format" => "int64",
                ],
                "petId"    => [
                    "type"   => "integer",
                    "format" => "int64",
                ],
                "quantity" => [
                    "type"   => "integer",
                    "format" => "int32",
                ],
                "shipDate" => [
                    "type"   => "string",
                    "format" => "date-time",
                ],
                "status"   => [
                    "type"        => "string",
                    "description" => "Order Status",
                    "enum"        => [
                        "placed",
                        "approved",
                        "delivered",
                    ],
                ],
                "complete" => [
                    "type"    => "boolean",
                    "default" => false,
                ],
            ],
            "xml"        => [
                "name" => "Order",
            ],
        ];

        $order = $this->openapiObject->getDefintion('#/components/schemas/Order');
        $this->assertEquals($expected, $order);
    }

    public function testItNotAllowsNullValuesByDefault()
    {
        $schema = new SwaggerSchema('{}');
        $this->assertFalse($schema->isAllowNullValues());
    }

    public function testItAllowsNullValues()
    {
        $allowNullValues = true;
        $schema = new SwaggerSchema('{}', $allowNullValues);
        $this->assertTrue($schema->isAllowNullValues());
    }
}
