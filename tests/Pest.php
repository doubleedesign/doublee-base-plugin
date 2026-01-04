<?php
use Spies\Spy;
use function Brain\Monkey\Functions\when;
use function Patchwork\relay;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Doubleedesign\BasePlugin\Tests\TestCase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function() {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

uses()->beforeEach(function() {
    when('plugin_dir_path')->justReturn('/');

    $this->actions = [];
    $this->filters = [];

    /**
     * These patches intercept the given WordPress functions before BrainMonkey does if real instances
     * of plugin classes are instantiated, allowing us to pass in method spies as well as run the real methods,
     * which allows us to both assert that the methods were called and that the result would be correct.
     */
    when('add_action')->alias(function($hook, $callback) {
        // Store the added action in the test instance
        $this->actions[$hook][] = $callback;
        // Call the BrainMonkey mock too
        relay(func_get_args());
    });

    when('do_action')->alias(function($hook, ...$args) {
        // Run the functions registered for this hook
        if (isset($this->actions[$hook])) {
            foreach ($this->actions[$hook] as $callback) {
                call_user_func_array($callback, $args);
            }
        }

        // Call the BrainMonkey mock too
        relay(func_get_args());
    });

    when('add_filter')->alias(function($hook, $callback) {
        // Store the added filter in the test instance
        $this->filters[$hook][] = $callback;
        // Call the BrainMonkey mock too
        relay(func_get_args());
    });

    // TODO: Fix this not working with multiple arguments being passed to the callback
    when('apply_filters')->alias(function($hook, $value, ...$extra) {
        // Run the functions registered for this hook
        if (isset($this->filters[$hook])) {
            foreach ($this->filters[$hook] as $callback) {
                // If a spy is provided as a third argument, call that as well as the registered callback
                // This fixes the issue where the result is correct because the correct method gets called,
                // but $this inside the class being tested is the original mock not the spy object created from that mock
                if (isset($extra[0]) && $extra[0] instanceof Spy) {
                    $extra[0]->call($value);
                }

                $value = call_user_func($callback, $value);
            }
        }

        // Call the BrainMonkey mock too
        relay(func_get_args());

        // Return the final value after all available filters have been applied, or the default value if there were none
        return $value;
    });

    when('__')->returnArg(1);

})->in('Unit');
