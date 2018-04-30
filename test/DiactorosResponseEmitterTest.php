<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2018 Daniyal Hamid (https://designcise.com)
 *
 * @license   https://github.com/designcise/bitframe/blob/master/LICENSE.md MIT License
 */

namespace BitFrame\Test;

use \PHPUnit\Framework\TestCase;

use \Psr\Http\Message\ResponseInterface;

use \BitFrame\Factory\HttpMessageFactory;

/**
 * @covers \BitFrame\Message\DiactorosResponseEmitter
 */
class DiactorosResponseEmitterTest extends TestCase
{
	/** @var \Psr\Http\Message\ServerRequestInterface */
	private $request;
	
	/** @var \BitFrame\Message\DiactorosResponseEmitter */
	private $responder;
	
    protected function setUp()
    {
		$this->request = HttpMessageFactory::createServerRequest();
		$this->responder = new \BitFrame\Message\DiactorosResponseEmitter();
    }
	
	/**
     * @runInSeparateProcess
     */
	public function testEmitResponse() 
	{
		$response = HttpMessageFactory::createResponse();
		$response->getBody()->write('Hello World!');
		
		$handler = $this->createMock('\Psr\Http\Server\RequestHandlerInterface');
		$handler->method('handle')->willReturn($response);
		
		$this->expectOutputString('Hello World!');
		
		$response = $this->responder
					->setForceEmit(true)
					->process($this->request, $handler);
		
		$this->assertInstanceOf(ResponseInterface::class, $response);
	}
	
	/**
     * @runInSeparateProcess
     */
	public function testForceEmitWhenOutputEmittedPreviously() 
	{
		$response = HttpMessageFactory::createResponse();
		
		// output response
		$this->emitBody($response);
		
		$handler = $this->createMock('\Psr\Http\Server\RequestHandlerInterface');
		$handler->method('handle')->willReturn($response);
		
		// output has been emitted previously
		// expect a RuntimeException after setting forceEmit to false
		$this->expectException(\RuntimeException::class);
		
		$response = $this->responder
					->setForceEmit(false)
					->process($this->request, $handler);
		
		$this->assertInstanceOf(ResponseInterface::class, $response);
	}
	
	public function testNoForceEmitWhenHeadersAlreadySent() 
	{
		$response = HttpMessageFactory::createResponse();
		$response->getBody()->write('Hello World!');
		
		$handler = $this->createMock('\Psr\Http\Server\RequestHandlerInterface');
		$handler->method('handle')->willReturn($response);
		
		// when running phpunit in cli, headers should already be sent at this point
		// because we're not using the 'runInSeparateProcess' annotation, so we should
		// expect a RuntimeException after setting forceEmit to false
		$this->expectException(\RuntimeException::class);
		
		$response = $this->responder
					->setForceEmit(false)
					->process($this->request, $handler);
	}
	
	public function testDiactorosResponseEmitterCanSetAndGetAllProperties()
    {
        $emitter = $this->createMock('\Zend\Diactoros\Response\EmitterInterface');
        $this->assertSame($emitter, $this->responder->setEmitter($emitter)->getEmitter());
		
		$forceEmit = false;
		$this->assertSame($forceEmit, $this->responder->setForceEmit($forceEmit)->isForceEmit());
    }
	
	private function emitBody(ResponseInterface $response, $maxBufferLength = 8192)
    {
        $body = $response->getBody();

        if ($body->isSeekable()) {
            $body->rewind();
        }

        if (! $body->isReadable()) {
            echo $body;
            return;
        }

        while (! $body->eof()) {
            echo $body->read($maxBufferLength);
        }
    }
}