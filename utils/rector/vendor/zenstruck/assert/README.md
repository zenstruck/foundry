# zenstruck/assert

[![CI Status](https://github.com/zenstruck/assert/workflows/CI/badge.svg)](https://github.com/zenstruck/assert/actions?query=workflow%3ACI)
[![Code Coverage](https://codecov.io/gh/zenstruck/assert/branch/1.x/graph/badge.svg?token=R7OHYYGPKM)](https://codecov.io/gh/zenstruck/assert)

This library allows dependency-free test assertions. When using a PHPUnit-based test
library (PHPUnit itself, Pest, Codeception), failed assertions are automatically converted
to PHPUnit failures and successful assertions are added to PHPUnit's successful assertion
count.

This library differs from other popular assertion libraries
([webmozart/assert](https://github.com/webmozarts/assert) &
[beberlei/assert](https://github.com/beberlei/assert)) in that it is purely for _test
assertions_ opposed to what these libraries provide: _type safety assertions_.

With the exception of the [Expectation API](#expectation-api) (specifically, the
[Throws Expectation](#throws-expectation) which provides a nice API for making exception
assertions), this library is really only useful for 3rd party libraries that would like
to provide test assertions but not have a direct dependency on a specific test library.

## Installation

```bash
$ composer require zenstruck/assert
```

## `Zenstruck\Assert`

This is the main entry point for making assertions. When the methods on this class
are called, they throw a `Zenstruck\Assert\AssertionFailed` on failure. If they
do not throw this exception, they are considered successful.

When using a PHPUnit-based framework, failed assertions are auto-converted to PHPUnit
test failures and successful assertions are added to PHPUnit's successful assertion
count.

## True/False Assertions

```php
use Zenstruck\Assert;

// passes
Assert::true(true === true, 'The condition was not true.');

// fails
Assert::true(true === false, 'The condition was not true.');

// passes
Assert::false(true === false, 'The condition was not false.');

// fails
Assert::false(true === true, 'The condition was not false.');
```

## Generic Fail/Pass

```php
use Zenstruck\Assert;

// trigger a "fail"
Assert::fail('This is a failure.');

// trigger a "pass"
Assert::pass();
```

## Try

Attempt to run a callback and return the result. If an exception is thrown while
running, a *fail* is triggered. If run successfully, a *pass* is triggered.

```php
use Zenstruck\Assert;

$ret = Assert::try(fn() => 'value'); // $ret === 'value'

Assert::try(fn() => throw new \RuntimeException('exception message')); // "fails" with message "exception message"

// customize the failure message
Assert::try(
    fn() => throw new \RuntimeException('exception message'),
    'Tried to run the code but {exception} with message "{message}" was thrown.'
); // "fails" with message 'Tried to run the code but RuntimeException with message "exception message" was thrown.'
```

## _Run_ Assertions

`Assert::run()` executes a `callable`. A successful execution is considered
a pass and if `Zenstruck\Assert\AssertionFailed` is thrown, it is a fail.

```php
use Zenstruck\Assert;
use Zenstruck\Assert\AssertionFailed;

// failure
Assert::run(function(): void {
    if (true) {
        AssertionFailed::throw('This failed.');
    }
});

// pass
Assert::run(function(): void {
    if (false) {
        AssertionFailed::throw('This failed.');
    }
});
```

## Expectation API

While the above assertions can be used to create *any* assertion, a simple, fluent,
readable, expectation API is provided. This API is heavily inspired by
[Pest PHP](https://pestphp.com/).

```php
use Zenstruck\Assert;

// empty
Assert::that([])->isEmpty(); // pass
Assert::that(['foo'])->isEmpty(); // fail

Assert::that(null)->isNotEmpty(); // fail
Assert::that('value')->isNotEmpty(); // pass

// null
Assert::that(null)->isNull(); // pass
Assert::that('foo')->isNull(); // fail

Assert::that(null)->isNotNull(); // fail
Assert::that('value')->isNotNull(); // pass

// count
Assert::that([1, 2])->hasCount(2); // pass
Assert::that(new \ArrayIterator([1, 2, 3]))->hasCount(2); // fail

Assert::that(new \ArrayIterator([1, 2]))->doesNotHaveCount(5); // pass
Assert::that($countableObjectWithCountOf5)->doesNotHaveCount(5); // fail

// contains
Assert::that('foobar')->contains('foo'); // pass
Assert::that(['foo', 'bar'])->contains('foo'); // pass
Assert::that('foobar')->contains('baz'); // fail
Assert::that(['foo', 'bar'])->contains(6); // fail

Assert::that('foobar')->doesNotContain('baz'); // pass
Assert::that(new \ArrayIterator(['bar']))->doesNotContain('foo'); // pass
Assert::that('foobar')->doesNotContain('bar'); // fail
Assert::that(['foo', 'bar'])->doesNotContain('bar'); // fail

// array subsets
Assert::that(['foo' => 'bar'])->isSubsetOf(['foo' => 'bar', 'bar' => 'foo']); // pass
Assert::that(['foo' => 'bar'])->isSubsetOf(['bar' => 'foo']); // fail

Assert::that(['foo' => 'bar', 'bar' => 'foo'])->hasSubset(['foo' => 'bar']); // pass
Assert::that(['foo' => 'bar'])->hasSubset(['bar' => 'foo']); // fail

// array subset assertions can also be performed on non-associated arrays (lists/sets).
// Keep in mind that order does not matter.
Assert::that([
    'users' => [
        ['name' => 'user3', 'age' => 20],
        ['name' => 'user1'],
    ]
])->isSubsetOf([
    'users' => [
        ['name' => 'user1', 'age' => 25],
        ['name' => 'user2', 'age' => 23],
        ['name' => 'user3', 'age' => 20],
    ]
]); // pass

// also works with json strings that decode to arrays
Assert::that('[3, 1]')->isSubsetOf('[1, 2, 3]'); // pass

// equals (== comparison)
Assert::that('foo')->equals('foo'); // pass
Assert::that('6')->equals(6); // pass
Assert::that('foo')->equals('bar'); // fail
Assert::that(6)->equals(7); // fail

Assert::that('foo')->isNotEqualTo('bar'); // pass
Assert::that(6)->isNotEqualTo('6'); // fail

// is (=== comparison)
Assert::that('foo')->is('foo'); // pass
Assert::that(6)->is(6); // pass
Assert::that('foo')->is('bar'); // fail
Assert::that(6)->is('6'); // fail

Assert::that('foo')->isNot('foo'); // fail
Assert::that(6)->isNot(6); // fail
Assert::that('foo')->isNot('bar'); // pass
Assert::that(6)->isNot('6'); // pass

// boolean (===)
Assert::that(true)->isTrue(); // pass
Assert::that(false)->isTrue(); // fail
Assert::that(true)->isFalse(); // fail
Assert::that(false)->isFalse(); // pass

// boolean (==)
Assert::that(1)->isTruthy(); // pass
Assert::that(new \stdClass())->isTruthy(); // pass
Assert::that('text')->isTruthy(); // pass
Assert::that(null)->isTruthy(); // fail
Assert::that(0)->isFalsy(); // pass
Assert::that(null)->isFalsy(); // pass
Assert::that('')->isFalsy(); // pass
Assert::that(1)->isFalsy(); // fail

// instanceof
Assert::that($object)->isInstanceOf(Some::class);

Assert::that($object)->isNotInstanceOf(Some::class);

// greater than
Assert::that(2)->isGreaterThan(1); // pass
Assert::that(2)->isGreaterThan(1); // fail
Assert::that(2)->isGreaterThan(2); // fail

// greater than or equal to
Assert::that(2)->isGreaterThanOrEqualTo(1); // pass
Assert::that(2)->isGreaterThanOrEqualTo(1); // fail
Assert::that(2)->isGreaterThanOrEqualTo(2); // pass

// less than
Assert::that(3)->isLessThan(4); // pass
Assert::that(3)->isLessThan(2); // fail
Assert::that(3)->isLessThan(3); // fail

// less than or equal to
Assert::that(3)->isLessThanOrEqualTo(4); // pass
Assert::that(3)->isLessThanOrEqualTo(2); // fail
Assert::that(3)->isLessThanOrEqualTo(3); // pass
```

### Type Expectations

```php
use Zenstruck\Assert;
use Zenstruck\Assert\Type;

Assert::that($something)->is(Type::bool());
Assert::that($something)->is(Type::int());
Assert::that($something)->is(Type::float());
Assert::that($something)->is(Type::numeric());
Assert::that($something)->is(Type::string());
Assert::that($something)->is(Type::callable());
Assert::that($something)->is(Type::iterable());
Assert::that($something)->is(Type::countable());
Assert::that($something)->is(Type::object());
Assert::that($something)->is(Type::resource());
Assert::that($something)->is(Type::array());
Assert::that($something)->is(Type::arrayList()); // [1, 2, 3] passes but ['foo' => 'bar'] does not
Assert::that($something)->is(Type::arrayAssoc()); // ['foo' => 'bar'] passes but [1, 2, 3] does not
Assert::that($something)->is(Type::arrayEmpty()); // [] passes but [1, 2, 3] does not
Assert::that($something)->is(Type::json()); // valid json string

// "Not's"
Assert::that($something)->isNot(Type::bool());
Assert::that($something)->isNot(Type::int());
Assert::that($something)->isNot(Type::float());
Assert::that($something)->isNot(Type::numeric());
Assert::that($something)->isNot(Type::string());
Assert::that($something)->isNot(Type::callable());
Assert::that($something)->isNot(Type::iterable());
Assert::that($something)->isNot(Type::countable());
Assert::that($something)->isNot(Type::object());
Assert::that($something)->isNot(Type::resource());
Assert::that($something)->isNot(Type::array());
Assert::that($something)->isNot(Type::arrayList());
Assert::that($something)->isNot(Type::arrayAssoc());
Assert::that($something)->isNot(Type::arrayEmpty());
Assert::that($something)->isNot(Type::json());
```

### Throws Expectation

This expectation provides a nice API for exceptions. It is an alternative to PHPUnit's
`expectException()` which has the following limitations:

1. Can only assert 1 exception is thrown per test.
2. Cannot make assertions on the exception itself (other than the message).
3. Cannot make post-exception assertions (think side effects).

```php
use Zenstruck\Assert;

// the following can all be used within a single PHPUnit test

// fails if exception not thrown
// fails if exception is thrown but not instance of \RuntimeException
Assert::that(fn() => $code->thatThrowsException())->throws(\RuntimeException::class);

// fails if exception not thrown
// fails if exception is thrown but not instance of \RuntimeException
// fails if exception is thrown but exception message doesn't contain "some message"
Assert::that(fn() => $code->thatThrowsException())->throws(\RuntimeException::class, 'some message');

// a callable can be used for the expected exception. The first parameter's type
// hint is used as the expected exception and the callable is executed with the
// caught exception
//
// fails if exception not thrown
// fails if exception is thrown but not instance of CustomException
Assert::that(fn() => $code->thatThrowsException())->throws(
    function(CustomException $e) use ($database) {
        // make assertions on the exception
        Assert::that($e->getMessage())->contains('some message');
        Assert::that($e->getSomeValue())->is('value');

        // make side effect assertions
        Assert::true($database->userTableEmpty(), 'The user table is not empty');

        // If using within the context of a PHPUnit test, you can use standard PHPUnit assertions
        $this->assertStringContainsString('some message', $e->getMessage());
        $this->assertSame('value', $e->getSomeValue());
        $this->assertTrue($database->userTableEmpty());
    }
);
```

### Fluent Expectations

```php
use Zenstruck\Assert;

// chain expectations on the same "value"
Assert::that(['foo', 'bar'])
    ->hasCount(2)
    ->contains('foo')
    ->contains('bar')
    ->doesNotContain('baz')
;

// start an additional expectation without breaking
Assert::that(['foo', 'bar'])
    ->hasCount(2)
    ->contains('foo')
    ->and('foobar') // start a new expectation with "foobar" as the new expectation value
    ->contains('bar')
;
```

## `AssertionFailed` Exception

When triggering a failed assertion, it is important to provide a useful failure
message to the user. The `AssertionFailed` exception has some features to help.

```php
use Zenstruck\Assert\AssertionFailed;

// The `throw()` named constructor creates the exception and immediately throws it
AssertionFailed::throw('Some message');

// a second "context" parameter can be used as sprintf values for the message
AssertionFailed::throw('Expected "%s" but got "%s"', ['value 1', 'value 2']); // Expected "value 1" but got "value 2"

// when an associated array passed as the context parameter, the message is constructed
// with a simple template system
AssertionFailed::throw('Expected "{expected}" but got "{actual}"', [ // Expected "value 1" but got "value 2"
    'expected' => 'value 1',
    'actual' => 'value 2',
]);
```

**NOTES:**
1. When the message is constructed with context, non-scalar values are run through
`get_debug_type()` and strings longer than _100_ characters are trimmed. The full context
is available via `AssertionFailed::context()`.
2. When using with PHPUnit, the full context is exported with the failure message if in
_verbose-mode_ (`--verbose|-v`).

## Assertion Objects

Since `Zenstruck\Assert::run()` accepts any `callable`, complex assertions can be wrapped
up into `invokable` objects:

```php
use Zenstruck\Assert;
use Zenstruck\Assert\AssertionFailed;

class StringContains
{
    public function __construct(private string $haystack, private string $needle) {}

    public function __invoke(): void
    {
        if (!str_contains($this->haystack, $this->needle)) {
            AssertionFailed::throw(
                'Expected string "{haystack}" to contain "{needle}" but it did not.',
                get_object_vars($this)
            ]);
        }
    }
}

// use the above assertion:

// passes
Assert::run(new StringContains('quick brown fox', 'fox'));

// fails
Assert::run(new StringContains('quick brown fox', 'dog'));
```

## Negatable Assertion Objects

`Zenstruck\Assert` has a `not()` method that can be used with _Negatable_
[Assertion Objects](#assertion-objects). This can be helpful to create
custom assertions that can be easily negated. Let's convert the example above
into a _Negatable Assertion Object_:

```php
use Zenstruck\Assert;
use Zenstruck\Assert\AssertionFailed;
use Zenstruck\Assert\Assertion\Negatable;

class StringContains implements Negatable
{
    public function __construct(private string $haystack, private string $needle) {}

    public function __invoke(): void
    {
        if (!str_contains($this->haystack, $this->needle)) {
            AssertionFailed::throw(
                'Expected string "{haystack}" to contain "{needle}" but it did not.',
                get_object_vars($this)
            ]);
        }
    }

    public function notFailure(): AssertionFailed
    {
        return new AssertionFailed(
            'Expected string "{haystack}" to not contain "{needle}" but it did.',
            get_object_vars($this)
        );
    }
}

// use the above assertion:

// fails
Assert::not(new StringContains('quick brown fox', 'fox'));

// passes
Assert::not(new StringContains('quick brown fox', 'dog'));
```
