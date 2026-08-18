<?php

namespace Tests\Unit\AI\Context;

use App\AI\Context\ContextRetriever;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use ReflectionNamedType;
use Tests\TestCase;

class ContextRetrieverTest extends TestCase
{
    public function test_it_defines_the_retrieve_method_with_expected_signature(): void
    {
        $method = new ReflectionMethod(ContextRetriever::class, 'retrieve');

        $this->assertTrue($method->isPublic());
        $this->assertCount(2, $method->getParameters());

        $queryParameter = $method->getParameters()[0];
        $limitParameter = $method->getParameters()[1];

        $this->assertSame('query', $queryParameter->getName());
        $this->assertFalse($queryParameter->allowsNull());

        $this->assertSame('limit', $limitParameter->getName());
        $this->assertFalse($limitParameter->allowsNull());
        $this->assertTrue($limitParameter->isDefaultValueAvailable());
        $this->assertSame(5, $limitParameter->getDefaultValue());

        $queryType = $queryParameter->getType();
        $limitType = $limitParameter->getType();
        $returnType = $method->getReturnType();

        $this->assertInstanceOf(ReflectionNamedType::class, $queryType);
        $this->assertInstanceOf(ReflectionNamedType::class, $limitType);
        $this->assertInstanceOf(ReflectionNamedType::class, $returnType);

        $this->assertSame('string', $queryType->getName());
        $this->assertSame('int', $limitType->getName());
        $this->assertSame(Collection::class, $returnType->getName());
    }
}
