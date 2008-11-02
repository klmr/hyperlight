<?php

class Test {
    public static function run($tests) {
        if (!is_array($tests))
            $tests = array($tests);

        // TODO iterate over all classes in $tests.
        // TODO iterate over all methods.
    }

    public static function assert($cond, $message) {
        if ($cond)
            return true;

        // Get stack trace and exit with error message.
        $bt = debug_backtrace();
        $n = 0;
        while ($bt[$n]['class'] ==  'Test')
            ++$n;
        $caller = $bt[$n];
        exit($message);
    }

    public static function assertEqual($a, $b, $message) {
        self::assert($a === $b, $message);
    }

    public static function assertSimilar($a, $b, $message) {
        self::assert($a == $b, $message);
    }

    public static function assertException($code, $message) {
        try {
            eval($code);
            self::assert(false, $message);
        }
        catch (Exception $ex) {
            self::assert(true, $message);
        }
    }
}

class PregMergeTests {
    static function basic() {

    }
}

?>
