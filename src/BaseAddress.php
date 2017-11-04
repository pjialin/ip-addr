<?php
/**
 * IP-Addr library
 * 
 * @author Dmitry A. Nezhelskoy <dmitry@nezhelskoy.pro>
 * @copyright 2014-2017 Barzmann Internet Solutions GmbH
 */

namespace IPAddr;

abstract class BaseAddress implements Address
{
    protected $version;
    protected $addr;
    protected $mask;
    protected $maxPrefixLength;

    /**
     * @param Address $addr
     * @return bool
     */
    abstract public function ltEq(Address $addr);

    /**
     * @param Address $addr
     * @return bool
     */
    abstract public function gtEq(Address $addr);

    /**
     * @param string $value
     * @return mixed
     */
    abstract protected function fromTextual($value);

    /**
     * @param int $prefixLength
     * @return mixed
     */
    abstract protected function maskFromPrefixLength($prefixLength);

    public function version()
    {
        return $this->version;
    }

    public static function create($anyFormat = null, $mask = null)
    {
        return new static($anyFormat, $mask);
    }

    public function assign($anyFormat, $maskString = null)
    {
        if (static::isNumeric($anyFormat)) {
            if ($maskString !== null) {
                throw new \InvalidArgumentException('Mask argument not allowed');
            }
            $this->addr = $this->fromNumeric($anyFormat);
        } else if (static::isTextual($anyFormat)) {
            if ($maskString !== null) {
                if (static::isTextual($maskString)) {
                    $this->mask = $this->fromTextual($maskString);
                } else {
                    throw new \InvalidArgumentException('Mask argument must have textual format');
                }
            }
            $this->addr = $this->fromTextual($anyFormat);
        } else if (static::isCIDR($anyFormat)) {
            if ($maskString !== null) {
                throw new \InvalidArgumentException('Mask argument not allowed');
            }
            $cidrParts = explode('/', $anyFormat);
            $this->addr = $this->fromTextual($cidrParts[0]);
            $this->mask = $this->maskFromPrefixLength(intval($cidrParts[1]));
        } else {
            throw new \InvalidArgumentException('Wrong arguments');
        }

        return $this;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public static function isNumeric($value)
    {
        throw new \BadMethodCallException('Unimplemented method, must be overrided in a child class');
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public static function isTextual($value)
    {
        throw new \BadMethodCallException('Unimplemented method, must be overrided in a child class');
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public static function isCIDR($value)
    {
        throw new \BadMethodCallException('Unimplemented method, must be overrided in a child class');
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    protected function fromNumeric($value)
    {
        return $value;
    }

    /**
     * @return int
     */
    public function hostBits()
    {
        return $this->maxPrefixLength - $this->prefixLength();
    }

    /**
     * @param array|Address $scope
     * @return bool
     */
    public function contains($scope)
    {
        if (is_array($scope)) {
            for ($i=0; $i<count($scope); $i++) {
                if (
                    $scope[$i] instanceof Address
                    && $this->contains($scope[$i])
                ) {
                    return true;
                } else if ($this->contains(self::create($scope[$i]))) {
                    return true;
                }
            }
        } else if ($scope instanceof Address) {
            return $this->gtEq($scope->first()) && $this->ltEq($scope->last());
        } else {
            throw new \InvalidArgumentException('Wrong scope argument');
        }

        return false;
    }

    /**
     * @param Address $addr
     * @return bool
     */
    public function within(Address $addr)
    {
        return $addr->first()->gtEq($this->first()) && $addr->last()->ltEq($this->last());
    }

    public function __toString()
    {
        return $this->cidr();
    }
}