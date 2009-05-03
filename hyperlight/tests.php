<?php

class Test {
    private static $trace;

    public static function run($tests, $trace = true) {
        self::$trace = $trace;

        if (!is_array($tests))
            $tests = array($tests);

        foreach ($tests as $test) {
            $kname = "{$test}Tests";
            $klass = new ReflectionClass($kname);

            foreach ($klass->getMethods() as $method) {
                if ($method->isPublic() and $method->isStatic()) {
                    $name = $method->getName();
                    if (self::$trace)
                        echo "$kname::$name:\n";

                    $method->invoke(null);
                    if (self::$trace)
                        echo "\n";
                }
            }
            if (self::$trace)
                echo "<br>";
        }

        if (self::$trace)
            echo "<strong style='color: green'>All tests passed.</strong>";
    }

    public static function assert($cond, $description = '', $message = '') {
        $passed = $cond ?
            "<span style='color: green'>passed</span>." :
            "<span style='color: red'>failed</span>!";
        if (self::$trace)
            echo "    $description … $passed\n";

        if ($cond)
            return true;

        // Get stack trace.
        $bt = debug_backtrace();
        while ($bt[0]['class'] ==  'Test')
            array_shift($bt);

        // Exit with error.
        $caller = $bt[0];
        $function = $caller['function'];
        ob_start();
        var_dump($bt);
        $trace = ob_get_clean();
        exit(<<<FAIL
<strong style='color: red'>Assertion failed</strong> in $function.
$message
Complete stack trace: $trace
FAIL
        );
    }

    public static function assertEqual($a, $b, $message = '') {
        self::assert($a === $b, "$a === $b", $message);
    }

    public static function assertSimilar($a, $b, $message = '') {
        self::assert($a == $b, "$a == $b", $message);
    }

    public static function assertException($code, $message = '') {
        try {
            eval($code);
            self::assert(false, "No exception", $message);
        }
        catch (Exception $ex) {
            self::assert(true, "Expected exception $ex", $message);
        }
    }

    public static function assertNoException($code, $message = '') {
        try {
            eval($code);
            self::assert(true, "No exception", $message);
        }
        catch (Exception $ex) {
            self::assert(false, "Unexpected exception $ex", $message);
        }
    }

    public static function assertMatch($regex, $text, $message = '') {
        self::assert(
            preg_match($regex, $text) === 1,
            "Match \"$text\" with $regex", $message
        );
    }

    public static function assertNoMatch($regex, $text, $message = '') {
        self::assert(
            preg_match($regex, $text) !== 1,
            "Do not match \"$text\" with $regex", $message
        );
    }

    public static function assertMatchEqual($regex, $text, $match, $group = 0, $message = '') {
        self::assert(
            preg_match($regex, $text, $matches) === 1,
            "Match \"$text\" with $regex", $message
        );
        self::assert(
            $matches[$group] === $match,
            "    … with index '$group' against \"$match\"", $message
        );
    }

    public static function assertMatchNotEqual($regex, $text, $match, $group = 0, $message = '') {
        self::assert(
            preg_match($regex, $text, $matches) === 1,
            "Match \"$text\" with $regex", $message
        );
        self::assert(
            $matches[$group] !== $match,
            "    … with index '$group' not against \"$match\"", $message
        );
    }
}

class PregMergeTests {
    static function basic() {
        $r = preg_merge('', array('/a/', '/b/'));

        Test::assert($r, 'preg_merge');
        Test::assertMatch($r, 'ab');
        Test::assertNoMatch($r, 'a');
        Test::assertNoMatch($r, 'b');

        $r = preg_merge('|', array('/a/', '/b/'));

        Test::assert($r, 'preg_merge');
        Test::assertMatch($r, 'ab');
        Test::assertMatch($r, 'a');
        Test::assertMatch($r, 'b');
        Test::assertNoMatch($r, 'x');

        $r = preg_merge('|', array('/^a/', '/bc$/', '/d/', '/^e/'));

        Test::assert($r, 'preg_merge');
        Test::assertMatch($r, 'ade');
        Test::assertMatch($r, 'de');
        Test::assertMatch($r, 'exc');
        Test::assertMatch($r, 'foobc');
        Test::assertMatch($r, 'abc');
        Test::assertNoMatch($r, 'bca');
        Test::assertNoMatch($r, 'xe');
    }

    static function delims() {
        $r = preg_merge('', array('/a/', '#b#'));

        Test::assert($r, 'preg_merge');
        Test::assertMatch($r, 'ab');

        $r = preg_merge('', array('/a#/', '#/b#'));

        Test::assert($r, 'preg_merge');
        Test::assertMatch($r, 'a#/b');

        $r = preg_merge('', array(',1,', '|2|', '/3|4/'));

        Test::assert($r, 'preg_merge');
        Test::assertMatch($r, '01234');
        Test::assertMatch($r, '01245');
        Test::assertNoMatch($r, '0134');
    }

    static function modifiers() {
        $r = preg_merge('', array('/a/i', '/b/'));

        Test::assert($r, 'preg_merge');
        Test::assertMatch($r, 'ab');
        Test::assertMatch($r, 'Ab');
        Test::assertNoMatch($r, 'aB');
        Test::assertNoMatch($r, 'AB');

        $r = preg_merge('', array('/a b/x', '/c /'));

        Test::assert($r, 'preg_merge');
        Test::assertMatch($r, 'abc ');
        Test::assertNoMatch($r, 'abc');
        Test::assertNoMatch($r, 'a bc ');

        $r = preg_merge('', array('/a/', '/bc./si', '/d./'));

        Test::assert($r, 'preg_merge');
        Test::assertMatch($r, "abc\nde");
        Test::assertMatch($r, "aBc\nde");
        Test::assertNoMatch($r, "Abc\nde");
        Test::assertNoMatch($r, "abc\nDe");
        Test::assertNoMatch($r, "abc\nd\n");

        $r = preg_merge('', array('/.*/', '/a./'));

        Test::assert($r, 'preg_merge');
        Test::assertMatchEqual($r, 'cbaax', 'cbaax');
        Test::assertNoMatch($r, 'cba');
        Test::assertMatchNotEqual($r, 'cbaax', 'cbaa');

        $r = preg_merge('', array('/.*/U', '/a./'));

        Test::assert($r, 'preg_merge');
        Test::assertMatchEqual($r, 'cbaax', 'cbaa');
        Test::assertNoMatch($r, 'cba');
        Test::assertMatchNotEqual($r, 'cbaax', 'cbaax');
    }

    static function names() {
        $r = preg_merge('', array('/a/', '/b/'), array('one'));

        Test::assert(!$r, 'preg_merge invalid');

        $r = preg_merge('', array('/a/', '/b/'), array('one', 'two'));

        Test::assert($r, 'preg_merge');
        Test::assertMatchEqual($r, 'xabc', 'a', 'one');
        Test::assertMatchEqual($r, 'xabc', 'b', 'two');
    }

    static function backrefs() {
        $r = preg_merge('', array('/(a)/', '/(b)\\1/'));

        Test::assert($r, 'preg_merge');
        Test::assertNoMatch($r, 'aba');
        Test::assertMatch($r, 'abb');
        Test::assertNoMatch($r, 'ab');
        Test::assertNoMatch($r, 'xbb');

        $r = preg_merge('', array('/(a)/', '/(b)\\\\1/'));

        Test::assert($r, 'preg_merge');
        Test::assertMatch($r, 'ab\\1');

        $r = preg_merge('', array('/(a)/', '/(b)\\\\\\1/'));
        var_dump('/(b)\\\\\\1/');
        var_dump($r);

        Test::assert($r, 'preg_merge');
        Test::assertMatch($r, 'ab\\b');

        $r = preg_merge('', array('/(a)/', '/(b)\\\\\\1/', '/c((d)\\2)/'));

        Test::assert($r, 'preg_merge');
        Test::assertMatch($r, 'ab\\bcdd');
    }
}

?>
