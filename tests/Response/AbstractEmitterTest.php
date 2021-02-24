<?php
namespace Selline\HttpServer\Tests\Response;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\StreamInterface;
use Selline\Http\Response;
use Selline\HttpServer\Response\AbstractEmitter;
use Selline\HttpServer\Tests\Response\Helpers\HeaderStack;
use RuntimeException;


abstract class AbstractEmitterTest extends TestCase
{
    use ProphecyTrait;

    protected AbstractEmitter $emitter;

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        HeaderStack::reset();

        HeaderStack::$headersSent = false;
        HeaderStack::$headersFile = null;
        HeaderStack::$headersLine = null;
    }

    public function testEmitThrowsSentHeadersException(): void
    {
        HeaderStack::$headersSent = true;
        HeaderStack::$headersFile = 'src/AbstractSapiEmitter.php';
        HeaderStack::$headersLine = 20;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(\sprintf(
            'Unable to emit response: Headers already sent in file %s on line %s. This happens if echo, print, printf, print_r, var_dump, var_export or similar statement that writes to the output buffer are used.',
            HeaderStack::$headersFile,
            (string) HeaderStack::$headersLine
        ));

        $this->emitter->emit($this->arrangeStatus200AndTypeTextResponse());
    }

    public function testEmitsMessageBody(): void
    {
        $response = $this->arrangeStatus200AndTypeTextResponse();
        $response->getBody()->write('Content!');

        $this->expectOutputString('Content!');

        $this->emitter->emit($response);

        self::assertTrue(HeaderStack::has('HTTP/1.1 200 OK'));
        self::assertTrue(HeaderStack::has('Content-Type: text/plain'));
    }

    public function testMultipleSetCookieHeadersAreNotReplaced(): void
    {
        $response = (new Response())
            ->withStatus(200)
            ->withAddedHeader('Set-Cookie', 'foo=bar')
            ->withAddedHeader('Set-Cookie', 'bar=baz');

        $this->emitter->emit($response);

        $expectedStack = [
            ['header' => 'Set-Cookie: foo=bar', 'replace' => false, 'status_code' => 200],
            ['header' => 'Set-Cookie: bar=baz', 'replace' => false, 'status_code' => 200],
            ['header' => 'HTTP/1.1 200 OK', 'replace' => true, 'status_code' => 200],
        ];

        self::assertSame($expectedStack, HeaderStack::stack());
    }

    public function testDoesNotLetResponseCodeBeOverriddenByPHP(): void
    {
        $response = (new Response())
            ->withStatus(202)
            ->withAddedHeader('Location', 'http://api.my-service.com/12345678')
            ->withAddedHeader('Content-Type', 'text/plain');

        $this->emitter->emit($response);

        $expectedStack = [
            ['header' => 'Location: http://api.my-service.com/12345678', 'replace' => true, 'status_code' => 202],
            ['header' => 'Content-Type: text/plain', 'replace' => true, 'status_code' => 202],
            ['header' => 'HTTP/1.1 202 Accepted', 'replace' => true, 'status_code' => 202],
        ];

        self::assertSame($expectedStack, HeaderStack::stack());
    }

    public function testEmitterRespectLocationHeader(): void
    {
        $response = (new Response())
            ->withStatus(200)
            ->withAddedHeader('Location', 'http://api.my-service.com/12345678');

        $this->emitter->emit($response);

        $expectedStack = [
            ['header' => 'Location: http://api.my-service.com/12345678', 'replace' => true, 'status_code' => 200],
            ['header' => 'HTTP/1.1 200 OK', 'replace' => true, 'status_code' => 200],
        ];

        self::assertSame($expectedStack, HeaderStack::stack());
    }

    public function testDoesNotInjectContentLengthHeaderIfStreamSizeIsUnknown(): void
    {
        $stream = $this->prophesize(StreamInterface::class);
        $stream->__toString()->willReturn('Content!');
        $stream->getSize()->willReturn(null);
        $response = (new Response())
            ->withStatus(200)
            ->withBody($stream->reveal());

        \ob_start();
        $this->emitter->emit($response);
        \ob_end_clean();

        foreach (HeaderStack::stack() as $header) {
            self::assertStringNotContainsStringIgnoringCase('Content-Length:', $header['header']);
        }
    }

    /**
     * @return Response
     */
    private function arrangeStatus200AndTypeTextResponse(): Response
    {
        return (new Response())
            ->withStatus(200)
            ->withAddedHeader('Content-Type', 'text/plain');
    }
}