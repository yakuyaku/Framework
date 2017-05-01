<?php
namespace Wandu\Router\ClassLoader;

use Closure;
use Mockery;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Wandu\Assertions;
use Wandu\DI\Container;
use Wandu\DI\ContainerInterface;
use Wandu\DI\Exception\CannotResolveException;
use Wandu\Http\Contracts\CookieJarInterface;
use Wandu\Http\Contracts\ParsedBodyInterface;
use Wandu\Http\Contracts\QueryParamsInterface;
use Wandu\Http\Contracts\ServerParamsInterface;
use Wandu\Http\Contracts\SessionInterface;
use Wandu\Http\Parameters\CookieJar;
use Wandu\Http\Parameters\ParsedBody;
use Wandu\Http\Parameters\QueryParams;
use Wandu\Http\Parameters\ServerParams;
use Wandu\Http\Parameters\Session;
use Wandu\Http\Psr\ServerRequest;
use Wandu\Router\Contracts\MiddlewareInterface;
use Wandu\Router\Exception\HandlerNotFoundException;

class WanduLoaderTest extends PHPUnit_Framework_TestCase
{
    use Assertions;
    
    public function tearDown()
    {
        Mockery::close();
    }

    public function testCreate()
    {
        $loader = new WanduLoader(new Container());

        static::assertInstanceOf(WanduLoaderTestMiddleware::class, $loader->middleware(WanduLoaderTestMiddleware::class));
    }

    public function testCreateFail()
    {
        $loader = new WanduLoader(new Container());

        static::assertExceptionEquals(new HandlerNotFoundException('ThereIsNoClass'), function () use ($loader) {
            $loader->middleware('ThereIsNoClass');
        });
    }

    public function testCall()
    {
        $loader = new WanduLoader(new Container());

        $request = new ServerRequest();

        static::assertEquals(
            'callFromLoader@StubInLoader',
            $loader->execute(WanduLoaderTestController::class, 'callFromLoader', $request)
        );
    }

    public function testCallFromMagicMethod()
    {
        $loader = new WanduLoader(new Container());

        $request = new ServerRequest();

        static::assertEquals(
            '__call->callFromMagicMethod@StubInLoader',
            $loader->execute(WanduLoaderTestController::class, 'callFromMagicMethod', $request)
        );
    }

    public function testCookieAndSession()
    {
        $loader = new WanduLoader(new Container());

        $request = new ServerRequest();

        static::assertExceptionInstanceOf(CannotResolveException::class, function () use ($loader, $request) {
            $loader->execute(WanduLoaderTestController::class, 'equalQueryParams', $request);
        });
        static::assertExceptionInstanceOf(CannotResolveException::class, function () use ($loader, $request) {
            $loader->execute(WanduLoaderTestController::class, 'equalParsedBody', $request);
        });
        static::assertExceptionInstanceOf(CannotResolveException::class, function () use ($loader, $request) {
            $loader->execute(WanduLoaderTestController::class, 'equalCookie', $request);
        });
        static::assertExceptionInstanceOf(CannotResolveException::class, function () use ($loader, $request) {
            $loader->execute(WanduLoaderTestController::class, 'equalSession', $request);
        });

        $request = $request->withAttribute('server_params', new ServerParams($request));
        $request = $request->withAttribute('query_params', new QueryParams());
        $request = $request->withAttribute('parsed_body', new ParsedBody());
        $request = $request->withAttribute('cookie', Mockery::mock(CookieJar::class));
        $request = $request->withAttribute('session', Mockery::mock(Session::class));

        static::assertTrue($loader->execute(WanduLoaderTestController::class, 'equalCookie', $request));
        static::assertTrue($loader->execute(WanduLoaderTestController::class, 'equalSession', $request));
        static::assertTrue($loader->execute(WanduLoaderTestController::class, 'equalServerParams', $request));
        static::assertTrue($loader->execute(WanduLoaderTestController::class, 'equalQueryParams', $request));
        static::assertTrue($loader->execute(WanduLoaderTestController::class, 'equalParsedBody', $request));
    }
}

class WanduLoaderTestMiddleware implements MiddlewareInterface
{
    public function __invoke(ServerRequestInterface $request, Closure $next)
    {
    }
}

class WanduLoaderTestController
{
    public function __call($name, $arguments = [])
    {
        return "__call->{$name}@StubInLoader";
    }

    public function callFromLoader()
    {
        return "callFromLoader@StubInLoader";
    }

    public function equalQueryParams(
        QueryParams $queryParams,
        QueryParamsInterface $queryParamsInterface,
        ServerRequestInterface $request,
        ContainerInterface $container
    ) {
        return $queryParams === $queryParamsInterface
            && $queryParams === $request->getAttribute('query_params')
            && $container->get('request') === $request
            && $container->get(ServerRequest::class) === $request
            && $container->get(ServerRequestInterface::class) === $request;
    }

    public function equalServerParams(
        ServerParams $serverParams,
        ServerParamsInterface $serverParamsInterface,
        ServerRequestInterface $request,
        ContainerInterface $container
    ) {
        return $serverParams === $serverParamsInterface
            && $serverParams === $request->getAttribute('server_params')
            && $container->get('request') === $request
            && $container->get(ServerRequest::class) === $request
            && $container->get(ServerRequestInterface::class) === $request;
    }

    public function equalParsedBody(
        ParsedBody $parsedBody,
        ParsedBodyInterface $parsedBodyInterface,
        ServerRequestInterface $request,
        ContainerInterface $container
    ) {
        return $parsedBody === $parsedBodyInterface
            && $parsedBody === $request->getAttribute('parsed_body')
            && $container->get('request') === $request
            && $container->get(ServerRequest::class) === $request
            && $container->get(ServerRequestInterface::class) === $request;
    }
    
    public function equalCookie(
        CookieJar $cookie,
        CookieJarInterface $cookieInterface,
        ServerRequestInterface $request,
        ContainerInterface $container
    ) {
        return $cookie === $cookieInterface
            && $cookie === $request->getAttribute('cookie')
            && $container->get('request') === $request
            && $container->get(ServerRequest::class) === $request
            && $container->get(ServerRequestInterface::class) === $request;
    }
    
    public function equalSession(
        Session $session,
        SessionInterface $sessionInterface,
        ServerRequestInterface $request,
        ContainerInterface $container
    ) {
        return $session === $sessionInterface
            && $session === $request->getAttribute('session')
            && $container->get('request') === $request
            && $container->get(ServerRequest::class) === $request
            && $container->get(ServerRequestInterface::class) === $request;
    }
}
