<?php

declare(strict_types=1);

/**
 * Utility class for making PHP reflection easier to use.
 */
final class ReflectionHelper
{
    /** @var array<class-string, array<string, mixed>> */
    private static array $privateVarsToReset = [];

    /**
     * Resets any static vars that were set to their
     * original values (to not screw up later unit test runs).
     */
    public static function reset(): void
    {
        foreach (self::$privateVarsToReset as $class => $valueNames) {
            foreach ($valueNames as $valueName => $originalValue) {
                self::setStaticValue($class, $valueName, $originalValue, $saveOriginalValue = false);
            }
        }
        self::$privateVarsToReset = [];
    }

    /**
     * Set the value of a static private or public class property.
     * Used to test internals of class without having to make the property public.
     *
     * @param class-string $class
     * @param null|mixed   $value
     */
    public static function setStaticValue(string $class, string $valueName, $value, bool $saveOriginalValue = true): void
    {
        $reflectionClass = new ReflectionClass($class);
        $reflectionProperty = $reflectionClass->getProperty($valueName);
        $reflectionProperty->setAccessible(true);

        // to prevent side-effects in later tests, we need to remember the original value and reset it on tear down
        // @NOTE: we need to check isset in case the original value was null or array()
        if ($saveOriginalValue && (!isset(self::$privateVarsToReset[$class]) || !isset(self::$privateVarsToReset[$class][$valueName]))) {
            self::$privateVarsToReset[$class][$valueName] = $reflectionProperty->getValue();
        }
        $reflectionProperty->setValue($value);

        // clean up
        $reflectionProperty->setAccessible(false);
    }

    /**
     * @return null|mixed
     */
    public static function getValueOnObject(object $object, string $valueName): mixed
    {
        $reflectionObject = new ReflectionObject($object);
        $reflectionProperty = $reflectionObject->getProperty($valueName);
        $reflectionProperty->setAccessible(true);
        $value = $reflectionProperty->getValue($object);

        // clean up
        $reflectionProperty->setAccessible(false);

        return $value;
    }

    /**
     * Invoke a the given public or protected method on the given object.
     *
     * @return null|mixed
     */
    public static function callMethodOnObject(object $object, string $methodName): mixed
    {
        $params = func_get_args();
        array_shift($params); // object
        array_shift($params); // methodName

        $className = $object::class;
        $class = new ReflectionClass($className);
        $method = $class->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $params);
    }
}
