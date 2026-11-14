# Migration guide from Foundry 2.x to 3.0

## Stories declared on a test class are built once per class

With [DAMADoctrineTestBundle](https://github.com/dmaicher/doctrine-test-bundle) 8.8 or greater and its PHPUnit extension,
a `#[WithStory]` declared on a test class is built once, before the first test of the class, instead of before each test.
Each test is still rolled back to the state right after the story was built.
The same goes for a `#[WithStory]` declared on a test method with a data provider: it is built once for all its data sets.

What a story's `build()` method does outside of the database (static properties, files, calls to external services...)
now only happens once per test class.
