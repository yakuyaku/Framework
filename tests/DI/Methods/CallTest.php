<?php
namespace Wandu\DI\Methods;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;
use Wandu\Assertions;
use Wandu\DI\Container;
use Wandu\DI\Exception\CannotResolveException;
use Wandu\Reflection\ReflectionCallable;

class CallTest extends TestCase 
{
    use Assertions;
    
    public function testCallAutoResolveFail()
    {
        $container = new Container();

        /** @var \Wandu\DI\Exception\CannotResolveException $exception */
        $exception = static::catchException(function () use ($container) {
            $container->call(__NAMESPACE__ . '\\callTestHasTypedParam');
        });

        static::assertInstanceOf(CannotResolveException::class, $exception);
        static::assertEquals('param', $exception->getParameter());
        static::assertEquals(__FILE__, $exception->getFile());
        static::assertEquals((new ReflectionCallable(__NAMESPACE__ . '\\callTestHasTypedParam'))->getStartLine(),
            $exception->getLine());
    }

    public function testCallAutoResolveSuccess()
    {
        $container = new Container();

        $container->bind(CallTestDependencyInterface::class, CallTestDependency::class);

        $result = $container->call(__NAMESPACE__ . '\\callTestHasTypedParam');
        static::assertInstanceOf(CallTestDependencyInterface::class, $result);
        static::assertInstanceOf(CallTestDependency::class, $result);
    }

    public function testCallSingleParamClassWithArguments()
    {
        $container = new Container();

        /** @var \Wandu\DI\Exception\CannotResolveException $exception */
        $exception = static::catchException(function () use ($container) {
            $container->call(__NAMESPACE__ . '\\callTestHasUntypedParam');
        });

        static::assertInstanceOf(CannotResolveException::class, $exception);
        static::assertEquals('param', $exception->getParameter());
        static::assertEquals(__FILE__, $exception->getFile());
        static::assertEquals(
            (new ReflectionCallable(__NAMESPACE__ . '\\callTestHasUntypedParam'))->getStartLine(),
            $exception->getLine()
        );

        // single param class
        $result = $container->call(__NAMESPACE__ . '\\callTestHasUntypedParam', [['username' => 'wan2land']]);
        static::assertSame(['username' => 'wan2land'], $result);

        $result = $container->call(__NAMESPACE__ . '\\callTestHasUntypedParam', ['param' => ['username' => 'wan3land']]);
        static::assertSame(['username' => 'wan3land'], $result);
    }

    public function testCallMultiParamsClassWithArguments()
    {
        $container = new Container();

        /** @var \Wandu\DI\Exception\CannotResolveException $exception */
        $exception = static::catchException(function () use ($container) {
            $container->call(__NAMESPACE__ . '\\callTestHasComplexParam');
        });

        static::assertInstanceOf(CannotResolveException::class, $exception);
        static::assertEquals('param1', $exception->getParameter());
        static::assertEquals(__FILE__, $exception->getFile());
        static::assertEquals(
            (new ReflectionCallable(__NAMESPACE__ . '\\callTestHasComplexParam'))->getStartLine(),
            $exception->getLine()
        );
        
        $container->bind(CallTestDependencyInterface::class, CallTestDependency::class);

        $exception = static::catchException(function () use ($container) {
            $container->call(__NAMESPACE__ . '\\callTestHasComplexParam');
        });

        static::assertInstanceOf(CannotResolveException::class, $exception);
        static::assertEquals('param2', $exception->getParameter());
        static::assertEquals(__FILE__, $exception->getFile());
        static::assertEquals(
            (new ReflectionCallable(__NAMESPACE__ . '\\callTestHasComplexParam'))->getStartLine(),
            $exception->getLine()
        );

        // only sequential
        $result = $container->call(__NAMESPACE__ . '\\callTestHasComplexParam', [
            $param1 = new CallTestDependency(),
            $param2 = new stdClass,
        ]);
        static::assertSame($param1, $result[0]);
        static::assertSame($param2, $result[1]);
        static::assertSame('param3', $result[2]);
        static::assertSame('param4', $result[3]);

        // only assoc
        $result = $container->call(__NAMESPACE__ . '\\callTestHasComplexParam', [
            'param2' => $param2 = new stdClass,
            'param4' => $param4 = new stdClass,
        ]);
        static::assertInstanceOf(CallTestDependencyInterface::class, $result[0]);
        static::assertSame($param2, $result[1]);
        static::assertSame('param3', $result[2]);
        static::assertSame($param4, $result[3]);

        // complex
        $result = $container->call(__NAMESPACE__ . '\\callTestHasComplexParam', [
            $param1 = new CallTestDependency(),
            $param2 = new stdClass,
            'param4' => $param4 = new stdClass,
            'param3' => $param3 = new stdClass,
        ]);
        static::assertSame($param1, $result[0]);
        static::assertSame($param2, $result[1]);
        static::assertSame($param3, $result[2]);
        static::assertSame($param4, $result[3]);
    }
    
    public function testCallCallable()
    {
        $container = new Container();

        $closure = function () {
            return ['$CLOSURE', func_get_args()];
        };

        // closure
        $result = $container->call($closure, ['param1', 'param2']);
        static::assertEquals(['$CLOSURE', ['param1', 'param2']], $result);

        // function
        $result = $container->call(__NAMESPACE__ . '\\callTestFunction', ['param2', 'param3']);
        static::assertEquals(['function', ['param2', 'param3']], $result);

        // static method
        $result = $container->call(CallTestInvokers::class . '::staticMethod', ['param3', 'param4']); 
        static::assertEquals(['staticMethod', ['param3', 'param4']], $result);

        // array of static
        $result = $container->call([CallTestInvokers::class, 'staticMethod'], ['param4', 'param5']);
        static::assertEquals(['staticMethod', ['param4', 'param5']], $result);

        // array of method
        $result = $container->call([new CallTestInvokers, 'instanceMethod'], ['param5', 'param6']);
        static::assertEquals(['instanceMethod', ['param5', 'param6']], $result);

        // invoker
        $result = $container->call(new CallTestInvokers(), ['param6', 'param7']);
        static::assertEquals(['__invoke', ['param6', 'param7']], $result);

        // __call
        $result = $container->call([new CallTestInvokers(), 'callViaCallMagicMethod'], ['param7', 'param8']); 
        static::assertEquals(['__call', 'callViaCallMagicMethod', ['param7', 'param8']], $result);

        // __staticCall
        $result = $container->call([CallTestInvokers::class, 'callViaStaticCallMagicMethod'], ['param8', 'param9']);
        static::assertEquals(['__callStatic', 'callViaStaticCallMagicMethod', ['param8', 'param9']], $result);
    }


    public function testCallWithOnlyAlias()
    {
        $container = new Container();
        $container->alias(CallTestCallWithOnlyAliasInterface::class, CallTestCallWithOnlyAlias::class);

        /** @var \Wandu\DI\Exception\CannotResolveException $exception */
        $exception = static::catchException(function () use ($container) {
            $container->call(function (CallTestCallWithOnlyAliasInterface $depend) {
                return $depend;
            });
        });

        static::assertInstanceOf(CannotResolveException::class, $exception);
        static::assertEquals('param', $exception->getParameter());
        static::assertEquals(__FILE__, $exception->getFile());
        static::assertEquals(
            (new ReflectionClass(CallTestCallWithOnlyAlias::class))->getConstructor()->getStartLine(),
            $exception->getLine()
        );

        $expected = new CallTestCallWithOnlyAlias(1111);
        
        $actual = $container->with([
            CallTestCallWithOnlyAlias::class => $expected
        ])->call(function (CallTestCallWithOnlyAliasInterface $depend) {
            return $depend;
        });
        static::assertSame($expected, $actual);
    }
}

interface CallTestDependencyInterface {}
class CallTestDependency implements CallTestDependencyInterface {}

interface CallTestCallWithOnlyAliasInterface {}
class CallTestCallWithOnlyAlias implements CallTestCallWithOnlyAliasInterface {
    public function __construct($param) {}
}

function callTestHasTypedParam(CallTestDependencyInterface $param) { return $param; }
function callTestHasUntypedParam($param) { return $param; }
function callTestHasComplexParam(CallTestDependencyInterface $param1, $param2, $param3 = 'param3', $param4 = 'param4')
{
    return [$param1, $param2, $param3, $param4];
}
function callTestFunction()
{
    return ['function', func_get_args()];
}
class CallTestInvokers
{
    /**
     * @return string
     */
    public static function staticMethod()
    {
        return ['staticMethod', func_get_args()];
    }

    /**
     * @return string
     */
    public function instanceMethod()
    {
        return ['instanceMethod', func_get_args()];
    }

    /**
     * @return string
     */
    public function __invoke()
    {
        return ['__invoke', func_get_args()];
    }

    /**
     * @param $name
     * @param $arguments
     * @return array
     */
    public function __call($name, $arguments)
    {
        return ['__call', $name, $arguments];
    }

    /**
     * @param $name
     * @param $arguments
     * @return array
     */
    public static function __callStatic($name, $arguments)
    {
        return ['__callStatic', $name, $arguments];
    }
}
