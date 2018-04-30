<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2018 Daniyal Hamid (https://designcise.com)
 * @license   https://github.com/designcise/bitframe/blob/master/LICENSE.md MIT License
 *
 * @author    Zend Framework
 * @copyright Copyright (c) 2016-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace BitFrame\Message;

use \Psr\Http\Message\{ServerRequestInterface, ResponseInterface};
use \Psr\Http\Server\{RequestHandlerInterface, MiddlewareInterface};
use \Zend\Diactoros\Response\EmitterInterface;

use BitFrame\Delegate\CallableMiddlewareTrait;

/**
 * Zend Diactoros wrapper class to emit http response
 * as a middleware.
 */
class DiactorosResponseEmitter implements MiddlewareInterface
{
	use CallableMiddlewareTrait;
	
    /** @var EmitterInterface */
    private $emitter;
	
	/** @var bool */
	private $forceEmit;

	/**
	 * @var EmitterInterface|null $emitter (optional)
	 */
    public function __construct(?EmitterInterface $emitter = null)
    {
        $this->emitter = $emitter;
		
		$this->forceEmit = true;
    }
	
	/**
     * {@inheritdoc}
	 *
	 * @throws \RuntimeException
     */
	public function process(
		ServerRequestInterface $request, 
		RequestHandlerInterface $handler
	): ResponseInterface 
	{
		// continue processing all requests
		$response = $handler->handle($request);
		
		// case 1: headers not already sent?
		// case 2: is output present in the output buffer
		if (! ($headersSent = headers_sent()) && ob_get_level() === 0 && ob_get_length() === 0) {
			// emit response!
			$this->getEmitter()->emit($response);
		} else {
			// headers already sent, and not forcing emit...
			if (! $this->forceEmit) {
				throw new \RuntimeException('Unable to emit response; ' . 
					(($headersSent) ? 
						'headers already sent' : 'output has been emitted previously'
					)
				);
			}
			
			// 1: emit headers
			// 2: emit status line
			if (! $headersSent) {
				// emit headers
				$statusCode = $response->getStatusCode();

				foreach ($response->getHeaders() as $header => $values) {
					$name  = $this->filterHeader($header);
					$first = $name === 'Set-Cookie' ? false : true;
					foreach ($values as $value) {
						header(sprintf(
							'%s: %s',
							$name,
							$value
						), $first, $statusCode);
						$first = false;
					}
				}

				// emit status line
				$reasonPhrase = $response->getReasonPhrase();

				header(sprintf(
					'HTTP/%s %d%s',
					$response->getProtocolVersion(),
					$statusCode,
					($reasonPhrase ? ' ' . $reasonPhrase : '')
				), true, $statusCode);
			}
			
			// 3: force-emit the response body
			$body = $response->getBody();

			if ($body->isSeekable()) {
				$body->rewind();
			}

			// no readable data in stream?
			if (! $body->isReadable()) {
				echo $body;
			} else {
				// read data till end of stream is reached...
				while (! $body->eof()) {
					// read 8mb (max buffer length) of binary data at a time and output it
					echo $body->read(1024 * 8);
				}
			}
		}

        return $response;
	}

	/**
	 * Get Emitter.
	 *
	 * @return EmitterInterface
	 */
    public function getEmitter(): EmitterInterface
    {
		$this->emitter = $this->emitter ?? new \Zend\Diactoros\Response\SapiEmitter();
		
        return $this->emitter;
    }

	/**
	 * Set Emitter.
	 *
	 * @param EmitterInterface $emitter
	 *
	 * @return $this
	 */
    public function setEmitter(EmitterInterface $emitter): self
    {
        $this->emitter = $emitter;
		
		return $this;
    }
	
	
	/**
	 * Get flag that determines whether the response is going to forcefully emitted
	 * when headers have already been sent.
	 *
	 * @return bool
	 */
    public function isForceEmit(): bool
    {
        return $this->forceEmit;
    }
	
	
	/**
	 * Set flag that determines whether the response is going to forcefully emitted
	 * when headers have already been sent.
	 * 
	 * @param bool $forceEmit
	 *
	 * @return $this
	 */
	public function setForceEmit($forceEmit): self
	{
		$this->forceEmit = $forceEmit;
		
		return $this;
	}
	
	/**
     * Filter a header name to wordcase
     *
     * @param string $header
     * @return string
     */
    private function filterHeader($header)
    {
        $filtered = str_replace('-', ' ', $header);
        $filtered = ucwords($filtered);
        return str_replace(' ', '-', $filtered);
    }
}